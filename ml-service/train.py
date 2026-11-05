"""Train and evaluate EcoTrack's 3-class classifier with MobileNetV2 transfer learning."""
from __future__ import annotations

import argparse
import json
from pathlib import Path

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import numpy as np
import tensorflow as tf
from sklearn.metrics import ConfusionMatrixDisplay, classification_report, confusion_matrix

CLASSES = ("organic", "paper", "plastic")


def load_split(root: Path, split: str, batch: int, seed: int, shuffle: bool):
    ds = tf.keras.utils.image_dataset_from_directory(
        root / split, class_names=list(CLASSES), image_size=(224, 224),
        batch_size=batch, label_mode="int", shuffle=shuffle, seed=seed,
    )
    return ds.prefetch(tf.data.AUTOTUNE)


def build_model() -> tuple[tf.keras.Model, tf.keras.Model]:
    base = tf.keras.applications.MobileNetV2(weights="imagenet", include_top=False, input_shape=(224, 224, 3))
    base.trainable = False
    augment = tf.keras.Sequential([
        tf.keras.layers.RandomFlip("horizontal"),
        tf.keras.layers.RandomRotation(0.06),
        tf.keras.layers.RandomZoom(0.10),
        tf.keras.layers.RandomContrast(0.10),
    ], name="training_augmentation")
    inputs = tf.keras.Input(shape=(224, 224, 3), name="image")
    x = augment(inputs)
    x = tf.keras.applications.mobilenet_v2.preprocess_input(x)
    x = base(x, training=False)
    x = tf.keras.layers.GlobalAveragePooling2D()(x)
    x = tf.keras.layers.Dropout(0.25)(x)
    outputs = tf.keras.layers.Dense(len(CLASSES), activation="softmax", name="class_probabilities")(x)
    model = tf.keras.Model(inputs, outputs, name="ecotrack_mobilenetv2")
    return model, base


def compile_model(model: tf.keras.Model, learning_rate: float) -> None:
    model.compile(optimizer=tf.keras.optimizers.Adam(learning_rate),
                  loss="sparse_categorical_crossentropy", metrics=["accuracy"])


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--data", type=Path, default=Path(__file__).resolve().parent / "dataset")
    parser.add_argument("--artifacts", type=Path, default=Path(__file__).resolve().parent / "artifacts")
    parser.add_argument("--epochs", type=int, default=18)
    parser.add_argument("--fine-tune-epochs", type=int, default=8)
    parser.add_argument("--batch-size", type=int, default=16)
    parser.add_argument("--seed", type=int, default=42)
    args = parser.parse_args()
    if not (args.data / "manifest.json").exists():
        parser.error(f"Dataset missing: {args.data}. Run prepare_dataset.py first.")
    args.artifacts.mkdir(parents=True, exist_ok=True)
    tf.keras.utils.set_random_seed(args.seed)
    train = load_split(args.data, "train", args.batch_size, args.seed, True)
    val = load_split(args.data, "val", args.batch_size, args.seed, False)
    test = load_split(args.data, "test", args.batch_size, args.seed, False)
    model, base = build_model()
    model_path = args.artifacts / "ecotrack.keras"
    history_data = {}

    def callbacks():
        return [
            tf.keras.callbacks.EarlyStopping(monitor="val_loss", patience=5, restore_best_weights=True),
            tf.keras.callbacks.ReduceLROnPlateau(monitor="val_loss", factor=0.4, patience=2, min_lr=1e-7),
        ]

    compile_model(model, 1e-3)
    h = model.fit(train, validation_data=val, epochs=args.epochs, callbacks=callbacks())
    history_data["head"] = h.history
    val_loss = float(model.evaluate(val, verbose=0)[0])
    model.save(model_path)

    if args.fine_tune_epochs > 0:
        # Fine-tune a small tail; keep BatchNorm frozen and run the backbone in inference mode.
        base.trainable = True
        for layer in base.layers[:-30]:
            layer.trainable = False
        for layer in base.layers[-30:]:
            layer.trainable = not isinstance(layer, tf.keras.layers.BatchNormalization)
        compile_model(model, 1e-5)
        h2 = model.fit(train, validation_data=val, epochs=args.fine_tune_epochs, callbacks=callbacks())
        history_data["fine_tune"] = h2.history
        new_loss = float(model.evaluate(val, verbose=0)[0])
        if new_loss < val_loss:
            model.save(model_path)
            val_loss = new_loss
            print("Fine-tuned model selected based only on validation loss.")
        else:
            print("Original head-trained model retained (lower validation loss).")

    # Evaluate the untouched test partition ONLY ONCE, after selection is final.
    best = tf.keras.models.load_model(model_path)
    test_loss, test_accuracy = best.evaluate(test, verbose=0)
    y_true, y_pred = [], []
    for images, labels in test:
        probabilities = best.predict(images, verbose=0)
        y_true.extend(labels.numpy().tolist())
        y_pred.extend(np.argmax(probabilities, axis=1).tolist())
    cm = confusion_matrix(y_true, y_pred, labels=list(range(len(CLASSES))))
    report = classification_report(y_true, y_pred, labels=list(range(len(CLASSES))),
                                   target_names=list(CLASSES), digits=4, zero_division=0)
    (args.artifacts / "classification_report.txt").write_text(report, encoding="utf-8")
    (args.artifacts / "labels.json").write_text(json.dumps(list(CLASSES), indent=2), encoding="utf-8")
    (args.artifacts / "training_history.json").write_text(json.dumps(history_data, indent=2), encoding="utf-8")
    (args.artifacts / "metrics.json").write_text(json.dumps({
        "test_accuracy": float(test_accuracy), "test_loss": float(test_loss),
        "validation_loss_for_selection": val_loss, "test_samples": len(y_true),
        "classes": list(CLASSES), "confusion_matrix": cm.tolist(),
        "accuracy_target_met": bool(test_accuracy >= 0.80),
    }, indent=2), encoding="utf-8")
    ConfusionMatrixDisplay(cm, display_labels=CLASSES).plot(cmap="Greens", values_format="d")
    plt.title("EcoTrack — Test confusion matrix")
    plt.tight_layout()
    plt.savefig(args.artifacts / "confusion_matrix.png", dpi=160)
    plt.close()
    print(f"TEST accuracy: {test_accuracy:.2%} / target >= 80%: {'PASS' if test_accuracy >= .8 else 'NOT MET'}")
    print("TEST confusion matrix:\n", cm)
    print("TEST classification report:\n", report)
    print("Output artifacts:", args.artifacts.resolve())


if __name__ == "__main__":
    main()
