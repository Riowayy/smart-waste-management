FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    TF_NUM_INTRAOP_THREADS=1 \
    TF_NUM_INTEROP_THREADS=1 \
    OMP_NUM_THREADS=1

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libgomp1 \
    && rm -rf /var/lib/apt/lists/*

COPY requirements-inference.txt ./requirements-inference.txt
RUN python -m pip install --no-cache-dir -r requirements-inference.txt

COPY api.py ./api.py
COPY artifacts/ecotrack.keras artifacts/labels.json ./artifacts/

EXPOSE 8001

CMD ["sh", "-c", "exec uvicorn api:app --host 0.0.0.0 --port \"${PORT:-8001}\" --workers 1"]
