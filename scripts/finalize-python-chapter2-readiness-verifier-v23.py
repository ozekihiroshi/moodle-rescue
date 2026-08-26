#!/usr/bin/env python3
"""Correct the exact English search token used by the generated Notebook."""
from pathlib import Path
path = Path(__file__).with_name("verify-python-chapter2-project24-readiness-contract-v23.py")
text = path.read_text(encoding="utf-8")
old = '["find", "None", "existing_ids", "enumerate", "CRUD", "equipment register"]'
new = '["found", "None", "existing_ids", "enumerate", "CRUD", "equipment register"]'
if new not in text:
    if text.count(old) != 1:
        raise RuntimeError("Expected verifier token was not found")
    path.write_text(text.replace(old, new), encoding="utf-8")
print(path)
