"""Standalone FastAPI prediction service; Laravel integration comes later."""
from __future__ import annotations

import io
import json
import os
from contextlib import asynccontextmanager
from pathlib import Path

import numpy as np
import tensorflow as tf
from fastapi import FastAPI, File, HTTPException, UploadFile
from PIL import Image, ImageOps, UnidentifiedImageError

BASE = Path(__file__).resolve().parent
MODEL = Path(os.getenv("ECOTRACK_MODEL_PATH", str(BASE / "artifacts" / "ecotrack.keras")))
LABELS = Path(os.getenv("ECOTRACK_LABELS_PATH", str(BASE / "artifacts" / "labels.json")))
MAX_BYTES = 5 * 1024 * 1024
DISPLAY_LABELS = {"organic": "Organic Waste", "paper": "Paper Waste", "plastic": "Plastic Waste"}
SLUGS = {"organic": "organic-waste", "paper": "paper-waste", "plastic": "plastic-waste"}
state = {}


@asynccontextmanager
async def lifespan(_: FastAPI):
    missing = [str(path) for path in (MODEL, LABELS) if not path.is_file()]
    if missing:
        raise RuntimeError(f"Required model artifact(s) are missing: {', '.join(missing)}")
    labels = json.loads(LABELS.read_text(encoding="utf-8"))
    if sorted(labels) != sorted(DISPLAY_LABELS):
        raise RuntimeError("Invalid class labels; model must match organic/paper/plastic.")
    try:
        state["model"] = tf.keras.models.load_model(MODEL, compile=False)
    except Exception as error:
        raise RuntimeError(f"Failed to load classifier model at {MODEL}: {error}") from error
    state["labels"] = labels
    yield
    state.clear()


app = FastAPI(title="EcoTrack Waste Classification API", version="1.0.0", lifespan=lifespan)


@app.get("/health")
def health():
    return {"status": "ok", "model_loaded": "model" in state}


@app.post("/predict")
async def predict(image: UploadFile = File(...)):
    if image.content_type not in {"image/jpeg", "image/png", "image/webp"}:
        raise HTTPException(status_code=415, detail="Only JPEG, PNG, and WebP images are supported.")
    raw = await image.read(MAX_BYTES + 1)
    if len(raw) > MAX_BYTES:
        raise HTTPException(status_code=413, detail="Image exceeds 5 MB.")
    try:
        with Image.open(io.BytesIO(raw)) as source:
            if source.format not in {"JPEG", "PNG", "WEBP"}:
                raise ValueError("Invalid image encoding")
            picture = ImageOps.exif_transpose(source).convert("RGB").resize((224, 224), Image.Resampling.LANCZOS)
            batch = np.expand_dims(np.asarray(picture, dtype=np.float32), 0)
    except (OSError, ValueError, UnidentifiedImageError):
        raise HTTPException(status_code=422, detail="Invalid or unreadable image.") from None
    probs = state["model"].predict(batch, verbose=0)[0]
    labels = state["labels"]
    idx = int(np.argmax(probs))
    label = labels[idx]
    return {
        "category": DISPLAY_LABELS[label],
        "slug": SLUGS[label],
        "confidence": round(float(probs[idx]), 6),
        "probabilities": {DISPLAY_LABELS[name]: round(float(value), 6) for name, value in zip(labels, probs)},
        "model": "ecotrack-mobilenetv2",
    }
