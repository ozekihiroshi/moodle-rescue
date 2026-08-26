#!/usr/bin/env python3
"""Verify source coverage for the Project 2.4 prerequisite additions."""

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
base = ROOT / "sample-content/introduction-to-python"
mapping = json.loads((base / "localization/chapter-2-project24-readiness-v1.json").read_text(encoding="utf-8"))
assert mapping["canonical_language"] == "en" and mapping["adaptations"] == ["ja"]
assert len(mapping["requirements"]) == 8
targets = [
    ("2.1", "en", base / "python-lab/templates/05_lists_dictionaries_records.ipynb", ["found", "None", "existing_ids", "enumerate", "CRUD", "equipment register"]),
    ("2.1", "ja", base / "python-lab/templates/ja/05_lists_dictionaries_records.ipynb", ["found", "None", "existing_ids", "enumerate", "CRUD", "備品台帳"]),
    ("2.2", "en", base / "python-lab/templates/06_functions_errors_testing.ipynb", ["find_book", "mark_as_read", "ValueError", "KeyError", "STATE TESTS PASSED", "supplied checker"]),
    ("2.2", "ja", base / "python-lab/templates/ja/06_functions_errors_testing.ipynb", ["find_book", "mark_as_read", "ValueError", "KeyError", "STATE TESTS PASSED", "確認プログラム"]),
]
for lesson, language, path, tokens in targets:
    notebook = json.loads(path.read_text(encoding="utf-8"))
    assert notebook["metadata"]["pyai"]["revision"] == 23
    assert notebook["metadata"]["pyai"]["lesson"] == lesson
    assert notebook["metadata"]["pyai"]["language"] == language
    ids = [cell["id"] for cell in notebook["cells"]]
    assert len(ids) == len(set(ids))
    source = "".join("".join(cell["source"]) for cell in notebook["cells"])
    for token in tokens:
        assert token in source, (path, token)
    for forbidden in ["Naledi", "ナレディ", "AI checkpoint", "Teacher guide", "教師用ガイド"]:
        assert forbidden not in source, (path, forbidden)
php = (ROOT / "scripts/upgrade-python-chapter2-project24-readiness-v23.php").read_text(encoding="utf-8")
for prefix in ["L21P", "L22P"]:
    for number in range(1, 11):
        assert php.count(f"{prefix}-{number:02d}") == 2
assert 'PYAI-V23-{$lesson}-PROJECT24-READY' in php
print(json.dumps({"requirements": 8, "notebooks": 4, "questions": 40, "status": "ok"}, indent=2))
