#!/usr/bin/env python3
"""Apply the small distribution-manifest changes required by Lesson 2.3."""

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def replace_once(path, old, new):
    text = path.read_text(encoding="utf-8")
    if new in text:
        return False
    if text.count(old) != 1:
        raise RuntimeError(f"Expected one occurrence in {path}: {old!r}")
    path.write_text(text.replace(old, new), encoding="utf-8")
    return True


generator = ROOT / "sample-content/introduction-to-python/python-lab/generate-notebooks.py"
replace_once(
    generator,
    '    "learning_centres_sample.csv",\n    "generate-learning-centre-data.py",',
    '    "learning_centres_sample.csv",\n    "library-books-practice.csv",\n    "generate-learning-centre-data.py",',
)
replace_once(
    generator,
    'if len(notebooks) != 37:\n        raise ValueError(f"Expected 37 reviewed Notebook templates, found {len(notebooks)}")',
    'if len(notebooks) != 39:\n        raise ValueError(f"Expected 39 reviewed Notebook templates, found {len(notebooks)}")',
)

startup = ROOT / "sample-content/introduction-to-python/python-lab/10-python-lab-materials.sh"
replace_once(startup, '.python-lab-materials-v7', '.python-lab-materials-v8')

print(generator)
print(startup)
