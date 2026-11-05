import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from prepare_dataset import split_counts, plan_samples, LABEL_MAPPING


class DatasetPlanTests(unittest.TestCase):
    def test_nine_hundred_images_balance(self):
        self.assertEqual(split_counts(300), {"train": 210, "val": 45, "test": 45})
        pool = {name: [f"RealWaste/{folder}/image_{i}.jpg" for i in range(400)] for name, folder in LABEL_MAPPING.items()}
        jobs = plan_samples(pool, 300, 42)
        self.assertEqual(len(jobs), 900)
        for name in LABEL_MAPPING:
            self.assertEqual(sum(1 for split, label, *_ in jobs if label == name and split == "train"), 210)
            self.assertEqual(sum(1 for split, label, *_ in jobs if label == name and split == "val"), 45)
            self.assertEqual(sum(1 for split, label, *_ in jobs if label == name and split == "test"), 45)
            self.assertEqual(len(set(path for _, label, path, _ in jobs if label == name)), 300)

    def test_deterministic(self):
        pool = {name: [f"RealWaste/{folder}/image_{i}.jpg" for i in range(100)] for name, folder in LABEL_MAPPING.items()}
        self.assertEqual(plan_samples(pool, 60, 1), plan_samples(pool, 60, 1))
        self.assertNotEqual(plan_samples(pool, 60, 1), plan_samples(pool, 60, 2))

    def test_undersized(self):
        with self.assertRaises(ValueError):
            split_counts(30)


if __name__ == "__main__":
    unittest.main()
