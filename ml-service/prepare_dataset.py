"""Build a reproducible 3-class EcoTrack subset of the RealWaste dataset.

Downloads real waste photographs from the upstream author's public repository.
Usage: python prepare_dataset.py --per-class 300
"""
from __future__ import annotations

import argparse
import hashlib
import io
import json
import random
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import quote

import requests
from PIL import Image, ImageOps, UnidentifiedImageError
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

REPOSITORY = "sam-single/realwaste"
SOURCE = "https://github.com/sam-single/realwaste"
SOURCE_LICENSE_NOTE = "See DATA_SOURCE.md (upstream license statements differ)."
LABEL_MAPPING = {
    "organic": "Food Organics",
    "paper": "Paper",
    "plastic": "Plastic",
}
SPLITS = ("train", "val", "test")
_TLS = threading.local()


def get_session() -> requests.Session:
    if not hasattr(_TLS, "session"):
        session = requests.Session()
        retry = Retry(total=4, backoff_factor=1.0, status_forcelist=[429, 500, 502, 503, 504], allowed_methods=["GET"])
        adapter = HTTPAdapter(max_retries=retry, pool_connections=8, pool_maxsize=8)
        session.mount("https://", adapter)
        session.headers.update({"User-Agent": "EcoTrack-ML-educational-dataset-builder/1.0", "Accept": "application/vnd.github+json"})
        _TLS.session = session
    return _TLS.session


def get_source_files() -> tuple[str, dict[str, list[str]]]:
    session = get_session()
    branch = session.get(f"https://api.github.com/repos/{REPOSITORY}/commits/main", timeout=40)
    branch.raise_for_status()
    revision = branch.json()["sha"]
    tree = session.get(f"https://api.github.com/repos/{REPOSITORY}/git/trees/{revision}?recursive=1", timeout=90)
    tree.raise_for_status()
    entries = tree.json()
    if entries.get("truncated"):
        raise RuntimeError("GitHub returned a truncated source tree; cannot guarantee a representative sample.")

    by_label = {label: [] for label in LABEL_MAPPING}
    for entry in entries.get("tree", []):
        path = entry.get("path", "")
        if entry.get("type") != "blob" or not path.lower().endswith((".jpg", ".jpeg", ".png")):
            continue
        for label, folder in LABEL_MAPPING.items():
            if path.startswith(f"RealWaste/{folder}/"):
                by_label[label].append(path)
    return revision, by_label


def split_counts(n: int) -> dict[str, int]:
    if n < 50:
        raise ValueError("At least 50 original images per class are required.")
    train = int(n * 0.70)
    val = int(n * 0.15)
    return {"train": train, "val": val, "test": n - train - val}


def plan_samples(files: dict[str, list[str]], per_class: int, seed: int) -> list[tuple[str, str, str, int]]:
    rng = random.Random(seed)
    counts = split_counts(per_class)
    tasks = []
    for label in LABEL_MAPPING:
        candidates = sorted(files[label])
        if len(candidates) < per_class:
            raise RuntimeError(f"Class {label} contains only {len(candidates)} files, need {per_class}.")
        selected = rng.sample(candidates, per_class)
        index = 0
        for split in SPLITS:
            for number in range(1, counts[split] + 1):
                tasks.append((split, label, selected[index], number))
                index += 1
    return tasks


def process_image(job: tuple[str, str, str, int], revision: str, destination: Path, image_size: int) -> dict:
    split, label, source_path, index = job
    url = f"https://raw.githubusercontent.com/{REPOSITORY}/{revision}/{quote(source_path, safe='/')}"
    output = destination / split / label / f"{label}_{index:04d}.jpg"
    if output.exists() and output.stat().st_size > 100:
        raise FileExistsError(f"Output already exists: {output}; use a fresh output directory to avoid mixing datasets.")
    response = get_session().get(url, timeout=(15, 50))
    response.raise_for_status()
    original_bytes = response.content
    try:
        with Image.open(io.BytesIO(original_bytes)) as image:
            image = ImageOps.exif_transpose(image).convert("RGB")
            image.thumbnail((image_size, image_size), Image.Resampling.LANCZOS)
            canvas = Image.new("RGB", (image_size, image_size), (238, 238, 238))
            canvas.paste(image, ((image_size - image.width) // 2, (image_size - image.height) // 2))
            output.parent.mkdir(parents=True, exist_ok=True)
            canvas.save(output, "JPEG", quality=88, optimize=True)
    except (OSError, UnidentifiedImageError) as error:
        raise RuntimeError(f"Invalid image at {source_path}: {error}") from error
    processed = output.read_bytes()
    return {
        "split": split, "label": label, "path": str(output.relative_to(destination)).replace('\\', '/'),
        "source_path": source_path, "source_url": url,
        "source_sha256": hashlib.sha256(original_bytes).hexdigest(),
        "sha256": hashlib.sha256(processed).hexdigest(),
    }


def build_dataset(output: Path, per_class: int, seed: int, image_size: int, workers: int) -> None:
    if output.exists() and any(output.iterdir()):
        raise FileExistsError(f"Output {output} is not empty; choose a fresh folder so the split stays reproducible.")
    revision, files = get_source_files()
    print("RealWaste source commit:", revision)
    tasks = plan_samples(files, per_class, seed)
    output.mkdir(parents=True, exist_ok=True)
    records = []
    failures = []
    with ThreadPoolExecutor(max_workers=workers) as pool:
        future_to_task = {pool.submit(process_image, task, revision, output, image_size): task for task in tasks}
        for i, future in enumerate(as_completed(future_to_task), start=1):
            try:
                records.append(future.result())
            except Exception as exc:
                failures.append((future_to_task[future], str(exc)))
            if i % 50 == 0 or i == len(tasks):
                print(f"Processed {i}/{len(tasks)}; errors: {len(failures)}")
    if failures:
        print("Errors (first ten):", json.dumps(failures[:10], ensure_ascii=False, indent=2))
        raise RuntimeError("Incomplete dataset. Delete the partial output directory and retry.")
    hashes = [record["source_sha256"] for record in records]
    if len(hashes) != len(set(hashes)):
        raise RuntimeError("Duplicate source file bytes found across splits. Review and regenerate the dataset.")
    records.sort(key=lambda r: (SPLITS.index(r["split"]), r["label"], r["path"]))
    metadata = {
        "source": SOURCE, "source_commit": revision, "source_license_note": SOURCE_LICENSE_NOTE,
        "seed": seed, "size": image_size, "images_per_class": per_class,
        "total_images": len(records), "split_counts_per_class": split_counts(per_class),
        "class_mapping": LABEL_MAPPING, "generated_utc": datetime.now(timezone.utc).isoformat(),
        "images": records,
    }
    (output / "manifest.json").write_text(json.dumps(metadata, indent=2, ensure_ascii=False), encoding="utf-8")
    print("Success:", len(records), "REAL images in", output)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", type=Path, default=Path(__file__).resolve().parent / "dataset")
    parser.add_argument("--per-class", type=int, default=300)
    parser.add_argument("--seed", type=int, default=42)
    parser.add_argument("--image-size", type=int, default=224)
    parser.add_argument("--workers", type=int, default=6)
    args = parser.parse_args()
    if args.image_size < 96 or args.image_size > 1024:
        parser.error("--image-size must be 96..1024")
    if args.workers < 1 or args.workers > 12:
        parser.error("--workers must be 1..12")
    build_dataset(args.output, args.per_class, args.seed, args.image_size, args.workers)


if __name__ == "__main__":
    main()
