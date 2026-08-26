#!/usr/bin/env python3
"""Bump the copy-on-start marker after adding direct submission files."""
from pathlib import Path

paths = [
    Path("D:/workspace/moodle-rescue/sample-content/introduction-to-python/python-lab/10-python-lab-materials.sh"),
    Path("D:/workspace/python-lab-rescue/singleuser/start-notebook.d/10-python-lab-materials.sh"),
]
for path in paths:
    text = path.read_text(encoding="utf-8")
    if ".python-lab-materials-v6" in text:
        continue
    if ".python-lab-materials-v5" not in text:
        raise RuntimeError(f"Expected v5 marker not found: {path}")
    path.write_text(text.replace(".python-lab-materials-v5", ".python-lab-materials-v6"), encoding="utf-8")
    print(path)
