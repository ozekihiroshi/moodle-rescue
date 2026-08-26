#!/usr/bin/env python3
"""Verify Lesson 2.3 source assets before Moodle publication."""

import csv
import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
base = ROOT / "sample-content/introduction-to-python"
mapping = json.loads((base / "localization/lesson-2-3-concept-map-v1.json").read_text(encoding="utf-8"))
assert mapping["canonical_language"] == "en"
assert mapping["adaptations"] == ["ja"]
assert len(mapping["concepts"]) == 10
assert [item["question"] for item in mapping["concepts"]] == [f"L23R-{i:02d}" for i in range(1, 11)]

for language, relative in mapping["notebooks"].items():
    notebook = json.loads((ROOT / relative).read_text(encoding="utf-8"))
    assert notebook["nbformat"] == 4
    assert notebook["metadata"]["pyai"]["lesson"] == "2.3"
    assert notebook["metadata"]["pyai"]["language"] == language
    ids = [cell["id"] for cell in notebook["cells"]]
    assert len(ids) == len(set(ids)) and len(ids) >= 20
    source = "".join("".join(cell["source"]) for cell in notebook["cells"])
    for token in ["Path.cwd()", "DictReader", "parse_read", "validate_header", "load_books", "DictWriter", "ROUND TRIP OK", "SOURCE PRESERVED"]:
        assert token in source, (language, token)
    for forbidden in ["pandas", "DataFrame", "Naledi", "ナレディ"]:
        assert forbidden not in source, (language, forbidden)

dataset = base / "datasets/library-books-practice.csv"
with dataset.open("r", encoding="utf-8", newline="") as handle:
    rows = list(csv.DictReader(handle))
assert len(rows) == 4
assert list(rows[0]) == ["id", "title", "read"]
assert rows[1]["title"] == "Data, Decisions, and Evidence"
assert {row["read"] for row in rows} == {"true", "false"}

php = (ROOT / "scripts/upgrade-python-lesson23-v22.php").read_text(encoding="utf-8")
assert php.count("PYAI-V22-LESSON23-FLOW") == 3
for question_id in [f"L23R-{i:02d}" for i in range(1, 11)]:
    assert php.count(question_id) == 2, question_id
for forbidden in ["Naledi", "ナレディ", "AI checkpoint", "Teacher guide"]:
    assert forbidden not in php

generator = (base / "python-lab/generate-notebooks.py").read_text(encoding="utf-8")
assert '"library-books-practice.csv"' in generator
assert "Expected 39 reviewed Notebook templates" in generator
startup = (base / "python-lab/10-python-lab-materials.sh").read_text(encoding="utf-8")
assert ".python-lab-materials-v8" in startup
print(json.dumps({"concepts": 10, "notebooks": 2, "dataset_rows": 4, "questions_per_language": 10, "status": "ok"}, indent=2))
