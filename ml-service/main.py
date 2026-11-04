from contextlib import asynccontextmanager
import io
import json
import os
from pathlib import Path
from typing import Any

from fastapi import FastAPI, File, HTTPException, UploadFile, status
from fastapi.concurrency import run_in_threadpool
from fastapi.middleware.cors import CORSMiddleware
import numpy as np
from PIL import Image, UnidentifiedImageError
import tensorflow as tf
from tensorflow.keras.applications.mobilenet_v2 import preprocess_input

BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "waste_classifier_model.keras"
CLASS_INDICES_PATH = BASE_DIR / "class_indices.json"
IMG_SIZE = (224, 224)
MAX_FILE_SIZE = 5 * 1024 * 1024  # 5 MB
Image.MAX_IMAGE_PIXELS = 25_000_000
ALLOWED_EXTENSIONS = {".jpg", ".jpeg", ".png", ".webp", ".bmp"}

DEFAULT_CLASSES = {
    0: "Organic Waste",
    1: "Paper Waste",
    2: "Plastic Waste",
}

ml_state: dict[str, Any] = {
    "model": None,
    "class_names": DEFAULT_CLASSES,
}


def load_class_mapping() -> dict[int, str]:
    if CLASS_INDICES_PATH.exists():
        raw_map = json.loads(CLASS_INDICES_PATH.read_text(encoding="utf-8"))
        return {int(k): str(v) for k, v in raw_map.items()}
    return DEFAULT_CLASSES


@asynccontextmanager
async def lifespan(app: FastAPI):
    if not MODEL_PATH.exists():
        raise RuntimeError(f"Trained model file not found at {MODEL_PATH}. Run train_model.py first.")
    ml_state["model"] = tf.keras.models.load_model(MODEL_PATH)
    ml_state["class_names"] = load_class_mapping()
    yield
    ml_state["model"] = None


app = FastAPI(
    title="Smart Waste Management - ML Service API",
    description="FastAPI service using MobileNetV2 Transfer Learning for classifying Organic, Paper, and Plastic waste.",
    version="1.0.0",
    lifespan=lifespan,
)

raw_origins = os.getenv(
    "CORS_ALLOWED_ORIGINS",
    "http://127.0.0.1:8000,http://localhost:8000",
)
allowed_origins = [origin.strip() for origin in raw_origins.split(",") if origin.strip()]
allow_all_origins = "*" in allowed_origins

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"] if allow_all_origins else allowed_origins,
    allow_credentials=not allow_all_origins,
    allow_methods=["GET", "POST", "OPTIONS"],
    allow_headers=["*"],
)


def preprocess_uploaded_image(file_bytes: bytes) -> np.ndarray:
    try:
        with Image.open(io.BytesIO(file_bytes)) as img:
            rgb_img = img.convert("RGB").resize(IMG_SIZE, Image.Resampling.BILINEAR)
            img_array = np.asarray(rgb_img, dtype=np.float32)
    except (UnidentifiedImageError, Image.DecompressionBombError, OSError) as exc:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Invalid image file format. Allowed formats: jpg, jpeg, png, webp, bmp.",
        ) from exc

    batch_array = np.expand_dims(img_array, axis=0)
    return preprocess_input(batch_array)


def _run_inference(processed_tensor: np.ndarray) -> np.ndarray:
    return ml_state["model"].predict(processed_tensor, verbose=0)[0]


@app.get("/health")
async def health_check() -> dict[str, Any]:
    model_loaded = ml_state["model"] is not None
    return {
        "status": "healthy" if model_loaded else "unhealthy",
        "model_loaded": model_loaded,
        "model_path": MODEL_PATH.name,
        "classes": list(ml_state["class_names"].values()),
    }


@app.post("/predict")
async def predict_waste(
    file: UploadFile | None = File(default=None),
    photo: UploadFile | None = File(default=None),
) -> dict[str, Any]:
    if ml_state["model"] is None:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Model is not loaded yet.",
        )

    uploaded = file if hasattr(file, "read") else (photo if hasattr(photo, "read") else None)
    if uploaded is None:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Missing image file field ('file' or 'photo').",
        )

    filename = uploaded.filename or ""
    ext = Path(filename).suffix.lower()
    if ext and ext not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Invalid image file format. Allowed formats: jpg, jpeg, png, webp, bmp.",
        )

    file_bytes = await uploaded.read(MAX_FILE_SIZE + 1)
    if not file_bytes:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Uploaded file is empty.",
        )

    if len(file_bytes) > MAX_FILE_SIZE:
        raise HTTPException(
            status_code=status.HTTP_413_CONTENT_TOO_LARGE,
            detail="Uploaded file exceeds the 5 MB maximum size limit.",
        )

    processed_tensor = await run_in_threadpool(preprocess_uploaded_image, file_bytes)

    try:
        predictions = await run_in_threadpool(_run_inference, processed_tensor)
    except Exception as exc:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Model inference failed.",
        ) from exc

    predicted_idx = int(np.argmax(predictions))
    class_map: dict[int, str] = ml_state["class_names"]
    waste_type = class_map.get(predicted_idx, "Unknown")
    confidence_score = round(float(predictions[predicted_idx]), 4)

    all_probabilities = {
        class_map[idx]: round(float(prob), 4)
        for idx, prob in enumerate(predictions)
    }

    return {
        "success": True,
        "message": "Prediction successful",
        "data": {
            "waste_type": waste_type,
            "confidence_score": confidence_score,
            "all_probabilities": all_probabilities,
        },
        "waste_type": waste_type,
        "confidence_score": confidence_score,
    }
