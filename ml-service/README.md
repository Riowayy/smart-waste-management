# EcoTrack Machine Learning — Starter Kit

A reproducible 3-class image-classification pipeline for **Organic Waste**, **Paper Waste**, and **Plastic Waste**. All images come from a real public research dataset; no fabricated labels, photos, or performance results.

## 1. Install (Windows PowerShell)

Install **Python 3.11** first. From the project root `C:\xampp\htdocs\smart-waste-management` place this `ml-service/` folder directly in your repository.

```powershell
cd C:\xampp\htdocs\smart-waste-management\ml-service
py -3.11 -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements-data.txt
```

> If PowerShell blocks activation, use `\.venv\Scripts\python.exe` in the commands instead. Do not change system execution policy just for this project.

## 2. Build the actual dataset

```powershell
python prepare_dataset.py --per-class 300 --seed 42
```

Requires an internet connection to GitHub. Downloads real labeled photographs from the author's repository and transforms them into 224×224 JPEG images. Expected output **900 unique source images** (300/category):

```text
dataset/
  train/organic/ (210), paper/ (210), plastic/ (210)
  val/organic/   (45),  paper/  (45),  plastic/  (45)
  test/organic/  (45),  paper/  (45),  plastic/  (45)
  manifest.json
```

The script uses a fixed random seed, records the upstream Git commit, checks for duplicate source-image bytes, and aborts on incomplete downloads. It refuses to mix data into a nonempty folder. If downloads fail, remove **only the incomplete** `ml-service/dataset` directory and retry; never delete unrelated application data. You can start with `--per-class 60` to test the download, then use a new empty output folder for the full dataset. 900 images is a planned count, **not downloaded in this supplied ZIP**.

## 3. Train, validate, evaluate

```powershell
pip install -r requirements.txt
python train.py --epochs 18 --fine-tune-epochs 8 --batch-size 16
```

Pre-trained MobileNetV2 weights will be downloaded on first use. Training can run CPU-only; GPU/Google Colab is an alternative when available.

Outputs after **actual training**:

```text
artifacts/
  ecotrack.keras                 # trained model
  labels.json                    # fixed label order
  metrics.json                   # genuine test accuracy and confusion matrix values
  classification_report.txt     # precision/recall/F1
  confusion_matrix.png          # test-only confusion matrix plot
  training_history.json
```

Test data is not used for training or model selection. The 80% accuracy target is a *goal*, not guaranteed by this kit.

## 4. Run the prediction REST API

```powershell
uvicorn api:app --host 127.0.0.1 --port 8001
```

Open http://127.0.0.1:8001/docs for the Swagger UI. `POST /predict` takes multipart `image`, accepting JPEG/PNG/WebP up to 5 MB. Example:

```powershell
curl.exe -X POST "http://127.0.0.1:8001/predict" -F "image=@C:\\path\\to\\sample.jpg"
```

Response **shape only (not an actual prediction)**:

```json
{"category":"Organic Waste","slug":"organic-waste","confidence":0.0,"probabilities":{"Organic Waste":0.0,"Paper Waste":0.0,"Plastic Waste":0.0},"model":"ecotrack-mobilenetv2"}
```

Probability numbers above are placeholders showing the JSON fields, not model outputs. The API will not start until `artifacts/ecotrack.keras` and `labels.json` are produced by training.

## 5. Integrate with Laravel later

Keep FastAPI as a separate Python service. Future Laravel HTTP client may call `POST /predict`, validate response/timeout, map slug to the database categories, and save `ai_category_id` and `ai_confidence` only after actual inference succeeds. The current starter does **not** change Laravel/Expo routes, schema, or production reports.

## Before assessment submission

1. Verify dataset image counts/labels via `dataset/manifest.json`.
2. Save **actual** test metrics, classification report, and confusion matrix.
3. Run a real image through FastAPI docs and then test Laravel–FastAPI integration.
4. Test several smartphone photos captured outside the original dataset; note failures.
5. Include attribution and review the conflicting license statements in `DATA_SOURCE.md`.
