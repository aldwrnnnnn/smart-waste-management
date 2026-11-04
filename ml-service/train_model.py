import json
from pathlib import Path
import random

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import numpy as np
from PIL import Image, UnidentifiedImageError
import seaborn as sns
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.model_selection import train_test_split
import tensorflow as tf
from tensorflow.keras import layers, models
from tensorflow.keras.applications import MobileNetV2
from tensorflow.keras.applications.mobilenet_v2 import preprocess_input
from tensorflow.keras.callbacks import EarlyStopping, ReduceLROnPlateau

SEED = 42
IMG_SIZE = (224, 224)
BATCH_SIZE = 32
EPOCHS = 15

BASE_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = BASE_DIR.parent
DATASET_DIR = PROJECT_ROOT / "dataset"
EVAL_DIR = PROJECT_ROOT / "docs" / "ml_evaluation"
MODEL_PATH = BASE_DIR / "waste_classifier_model.keras"
CLASS_INDICES_PATH = BASE_DIR / "class_indices.json"

FOLDER_TO_LABEL = {
    "organic": "Organic Waste",
    "paper": "Paper Waste",
    "plastic": "Plastic Waste",
}
CLASS_NAMES = ["Organic Waste", "Paper Waste", "Plastic Waste"]


def set_seeds(seed: int = SEED) -> None:
    random.seed(seed)
    np.random.seed(seed)
    tf.random.set_seed(seed)


def load_valid_image_paths(dataset_dir: Path) -> tuple[list[Path], list[int]]:
    file_paths: list[Path] = []
    labels: list[int] = []
    valid_extensions = {".jpg", ".jpeg", ".png", ".bmp", ".webp"}

    for folder_name, class_label in FOLDER_TO_LABEL.items():
        folder_path = dataset_dir / folder_name
        if not folder_path.exists():
            raise FileNotFoundError(f"Dataset folder not found: {folder_path}")

        class_idx = CLASS_NAMES.index(class_label)
        for img_path in sorted(folder_path.iterdir()):
            if img_path.suffix.lower() not in valid_extensions:
                continue
            try:
                with Image.open(img_path) as img:
                    img.verify()
                file_paths.append(img_path)
                labels.append(class_idx)
            except (UnidentifiedImageError, OSError) as exc:
                print(f"Skipping corrupted image {img_path.name}: {exc}")

    return file_paths, labels


def load_images_to_array(paths: list[Path]) -> np.ndarray:
    images = np.zeros((len(paths), IMG_SIZE[0], IMG_SIZE[1], 3), dtype=np.float32)
    for i, path in enumerate(paths):
        with Image.open(path) as img:
            rgb_img = img.convert("RGB").resize(IMG_SIZE, Image.Resampling.BILINEAR)
            images[i] = np.asarray(rgb_img, dtype=np.float32)
    return images


def build_datasets(
    x_train: np.ndarray,
    y_train: np.ndarray,
    x_val: np.ndarray,
    y_val: np.ndarray,
    x_test: np.ndarray,
    y_test: np.ndarray,
) -> tuple[tf.data.Dataset, tf.data.Dataset, tf.data.Dataset]:
    augmentation = models.Sequential(
        [
            layers.RandomFlip("horizontal"),
            layers.RandomRotation(0.15),
            layers.RandomZoom(0.15),
            layers.RandomContrast(0.1),
        ],
        name="image_augmentation",
    )

    def preprocess_train(image: tf.Tensor, label: tf.Tensor) -> tuple[tf.Tensor, tf.Tensor]:
        image = augmentation(image, training=True)
        image = preprocess_input(image)
        return image, label

    def preprocess_eval(image: tf.Tensor, label: tf.Tensor) -> tuple[tf.Tensor, tf.Tensor]:
        image = preprocess_input(image)
        return image, label

    train_ds = (
        tf.data.Dataset.from_tensor_slices((x_train, y_train))
        .shuffle(buffer_size=len(x_train), seed=SEED)
        .batch(BATCH_SIZE)
        .map(preprocess_train, num_parallel_calls=tf.data.AUTOTUNE)
        .prefetch(tf.data.AUTOTUNE)
    )

    val_ds = (
        tf.data.Dataset.from_tensor_slices((x_val, y_val))
        .batch(BATCH_SIZE)
        .map(preprocess_eval, num_parallel_calls=tf.data.AUTOTUNE)
        .prefetch(tf.data.AUTOTUNE)
    )

    test_ds = (
        tf.data.Dataset.from_tensor_slices((x_test, y_test))
        .batch(BATCH_SIZE)
        .map(preprocess_eval, num_parallel_calls=tf.data.AUTOTUNE)
        .prefetch(tf.data.AUTOTUNE)
    )

    return train_ds, val_ds, test_ds


def build_model(num_classes: int) -> tf.keras.Model:
    base_model = MobileNetV2(
        input_shape=(IMG_SIZE[0], IMG_SIZE[1], 3),
        include_top=False,
        weights="imagenet",
    )
    base_model.trainable = False

    inputs = layers.Input(shape=(IMG_SIZE[0], IMG_SIZE[1], 3), name="input_image")
    # Force BatchNormalization inside MobileNetV2 to stay in inference mode during fine-tuning.
    x = base_model(inputs, training=False)
    x = layers.GlobalAveragePooling2D(name="global_avg_pool")(x)
    x = layers.BatchNormalization(name="head_bn")(x)
    x = layers.Dropout(0.3, name="head_dropout")(x)
    x = layers.Dense(128, activation="relu", name="head_dense")(x)
    x = layers.Dropout(0.2, name="head_dropout_2")(x)
    outputs = layers.Dense(num_classes, activation="softmax", name="predictions")(x)

    model = models.Model(inputs=inputs, outputs=outputs, name="waste_classifier_mobilenetv2")
    model.compile(
        optimizer=tf.keras.optimizers.Adam(learning_rate=1e-3),
        loss="sparse_categorical_crossentropy",
        metrics=["accuracy"],
    )
    return model, base_model


def save_training_history_plot(history_dict: dict[str, list[float]], output_path: Path) -> None:
    epochs_range = range(1, len(history_dict["accuracy"]) + 1)
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(13, 5))

    ax1.plot(epochs_range, history_dict["accuracy"], "o-", label="Training Accuracy", linewidth=2)
    ax1.plot(epochs_range, history_dict["val_accuracy"], "s-", label="Validation Accuracy", linewidth=2)
    ax1.axhline(0.80, color="red", linestyle="--", linewidth=1.2, label="Target Min (80%)")
    ax1.set_title("Model Accuracy Across Epochs", fontsize=13, fontweight="bold")
    ax1.set_xlabel("Epoch")
    ax1.set_ylabel("Accuracy")
    ax1.set_ylim(0.0, 1.05)
    ax1.grid(True, linestyle="--", alpha=0.5)
    ax1.legend(loc="lower right")

    ax2.plot(epochs_range, history_dict["loss"], "o-", label="Training Loss", linewidth=2)
    ax2.plot(epochs_range, history_dict["val_loss"], "s-", label="Validation Loss", linewidth=2)
    ax2.set_title("Model Loss Across Epochs", fontsize=13, fontweight="bold")
    ax2.set_xlabel("Epoch")
    ax2.set_ylabel("Loss")
    ax2.grid(True, linestyle="--", alpha=0.5)
    ax2.legend(loc="upper right")

    plt.tight_layout()
    fig.savefig(output_path, dpi=300)
    plt.close(fig)


def save_confusion_matrix_plot(
    y_true: np.ndarray,
    y_pred: np.ndarray,
    class_names: list[str],
    output_path: Path,
) -> None:
    cm = confusion_matrix(y_true, y_pred)
    fig, ax = plt.subplots(figsize=(8, 6))
    sns.heatmap(
        cm,
        annot=True,
        fmt="d",
        cmap="Blues",
        xticklabels=class_names,
        yticklabels=class_names,
        cbar=True,
        annot_kws={"size": 13, "weight": "bold"},
        ax=ax,
    )
    ax.set_title("Confusion Matrix - Waste Classification (Test Set)", fontsize=13, fontweight="bold", pad=12)
    ax.set_xlabel("Predicted Label", fontsize=11)
    ax.set_ylabel("True Label", fontsize=11)
    plt.tight_layout()
    fig.savefig(output_path, dpi=300)
    plt.close(fig)


def main() -> None:
    set_seeds(SEED)
    EVAL_DIR.mkdir(parents=True, exist_ok=True)

    print(f"Loading dataset from: {DATASET_DIR}")
    file_paths, labels = load_valid_image_paths(DATASET_DIR)
    print(f"Total valid images found: {len(file_paths)}")

    # Split dataset 80:20 into train+val and hold-out test set first.
    train_val_paths, test_paths, y_train_val_list, y_test_list = train_test_split(
        file_paths,
        labels,
        test_size=0.20,
        random_state=SEED,
        stratify=labels,
    )

    # Split train_val into training (85% of 80% = 68%) and validation (15% of 80% = 12%)
    # so test_ds remains strictly unseen during EarlyStopping and ReduceLROnPlateau.
    train_paths, val_paths, y_train_list, y_val_list = train_test_split(
        train_val_paths,
        y_train_val_list,
        test_size=0.15,
        random_state=SEED,
        stratify=y_train_val_list,
    )

    x_train = load_images_to_array(train_paths)
    x_val = load_images_to_array(val_paths)
    x_test = load_images_to_array(test_paths)
    y_train = np.array(y_train_list, dtype=np.int32)
    y_val = np.array(y_val_list, dtype=np.int32)
    y_test = np.array(y_test_list, dtype=np.int32)

    print(f"Training samples: {len(x_train)} | Validation samples: {len(x_val)} | Testing samples: {len(x_test)}")

    train_ds, val_ds, test_ds = build_datasets(x_train, y_train, x_val, y_val, x_test, y_test)
    model, base_model = build_model(num_classes=len(CLASS_NAMES))

    callbacks = [
        EarlyStopping(monitor="val_accuracy", patience=5, restore_best_weights=True, verbose=1),
        ReduceLROnPlateau(monitor="val_loss", factor=0.5, patience=2, min_lr=1e-6, verbose=1),
    ]

    print("Starting transfer learning feature extraction training...")
    history = model.fit(
        train_ds,
        validation_data=val_ds,
        epochs=EPOCHS,
        callbacks=callbacks,
        verbose=1,
    )

    combined_history: dict[str, list[float]] = {
        "accuracy": list(history.history["accuracy"]),
        "val_accuracy": list(history.history["val_accuracy"]),
        "loss": list(history.history["loss"]),
        "val_loss": list(history.history["val_loss"]),
    }

    val_probs = model.predict(val_ds, verbose=0)
    val_pred = np.argmax(val_probs, axis=1)
    val_acc = accuracy_score(y_val, val_pred)

    # Unfreeze the top 30 MobileNetV2 layers if initial feature extraction falls below 85% validation accuracy.
    if val_acc < 0.85:
        print(f"Initial validation accuracy ({val_acc:.4f}) below 0.85, running fine-tuning phase...")
        base_model.trainable = True
        for layer in base_model.layers[:-30]:
            layer.trainable = False

        model.compile(
            optimizer=tf.keras.optimizers.Adam(learning_rate=1e-4),
            loss="sparse_categorical_crossentropy",
            metrics=["accuracy"],
        )
        ft_history = model.fit(
            train_ds,
            validation_data=val_ds,
            epochs=8,
            callbacks=callbacks,
            verbose=1,
        )
        for key in combined_history:
            combined_history[key].extend(ft_history.history[key])

    y_probs = model.predict(test_ds, verbose=0)
    y_pred = np.argmax(y_probs, axis=1)
    test_acc = accuracy_score(y_test, y_pred)

    report_text = classification_report(y_test, y_pred, target_names=CLASS_NAMES, digits=4)
    summary_header = (
        "Smart Waste Management System - Model Evaluation Report\n"
        "Model Architecture: MobileNetV2 (ImageNet Transfer Learning)\n"
        f"Total Dataset: {len(file_paths)} images (Train: {len(x_train)}, Val: {len(x_val)}, Test: {len(x_test)})\n"
        f"Test Accuracy: {test_acc * 100:.2f}% ({test_acc:.4f})\n\n"
        "Classification Report:\n"
    )
    full_report = summary_header + report_text
    print("\n" + full_report)

    if test_acc < 0.80:
        raise RuntimeError(f"Testing accuracy {test_acc * 100:.2f}% did not reach the 80% minimum threshold.")

    model.save(MODEL_PATH)
    print(f"Saved trained model to: {MODEL_PATH}")

    class_mapping = {str(idx): name for idx, name in enumerate(CLASS_NAMES)}
    CLASS_INDICES_PATH.write_text(json.dumps(class_mapping, indent=2), encoding="utf-8")

    report_file = EVAL_DIR / "classification_report.txt"
    cm_file = EVAL_DIR / "confusion_matrix.png"
    history_file = EVAL_DIR / "training_history.png"

    report_file.write_text(full_report, encoding="utf-8")
    save_confusion_matrix_plot(y_test, y_pred, CLASS_NAMES, cm_file)
    save_training_history_plot(combined_history, history_file)

    print(f"Saved evaluation artifacts to: {EVAL_DIR}")


if __name__ == "__main__":
    main()
