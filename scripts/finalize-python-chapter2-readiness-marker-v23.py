#!/usr/bin/env python3
"""Make the source verifier recognise the updater's language-neutral marker."""
from pathlib import Path
path = Path(__file__).with_name("verify-python-chapter2-project24-readiness-contract-v23.py")
text = path.read_text(encoding="utf-8")
old = '''for marker in ["PYAI-V23-2.1-PROJECT24-READY", "PYAI-V23-2.2-PROJECT24-READY"]:
    assert marker in php
'''
new = '''assert 'PYAI-V23-{$lesson}-PROJECT24-READY' in php
'''
if new not in text:
    if text.count(old) != 1:
        raise RuntimeError("Expected marker assertion was not found")
    path.write_text(text.replace(old, new), encoding="utf-8")
print(path)
