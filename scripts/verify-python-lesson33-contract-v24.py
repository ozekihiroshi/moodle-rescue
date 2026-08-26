#!/usr/bin/env python3
"""Verify the bilingual Lesson 3.3 concept, notebook, and question contract."""

from __future__ import annotations

import json
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
mapping = json.loads((ROOT / "sample-content/introduction-to-python/localization/lesson-3-3-concept-map-v1.json").read_text(encoding="utf-8"))
implementation = (ROOT / mapping["implementation"]).read_text(encoding="utf-8")

assert mapping["canonical_language"] == "en"
assert mapping["adaptations"] == ["ja"]
assert len(mapping["concepts"]) == 10
assert [item["id"] for item in mapping["concepts"]] == [f"C{i:02d}" for i in range(1, 11)]
assert [item["question"] for item in mapping["concepts"]] == [f"L33R-{i:02d}" for i in range(1, 11)]
assert all(item["lesson"] and item["notebook"] and not item["teacher"] for item in mapping["concepts"])

question_ids = re.findall(r"v24_question\('(L33R-\d{2})'", implementation)
assert question_ids == [f"L33R-{i:02d}" for i in range(1, 11)] * 2, question_ids

required_code = [
    "raw.copy()", "pd.to_numeric", 'errors="coerce"', ".isna()", ".notna()",
    ".str.strip()", ".str.title()", ".duplicated(", "keep=False", "analysis_ready", "assert ",
]
for language, relative in mapping["notebooks"].items():
    path = ROOT / relative
    document = json.loads(path.read_text(encoding="utf-8"))
    assert document["metadata"]["pyai"] == {
        "lesson": "3.3", "language": language,
        "concepts": [f"C{i:02d}" for i in range(1, 11)], "revision": 24,
    }
    source = "".join("".join(cell.get("source", [])) for cell in document["cells"])
    for needle in required_code:
        assert needle in source, f"{language}: missing {needle}"
    for forbidden in ["Naledi", "ナレディ", "AI checkpoint", "AI利用", "Teacher guide", "教師用ガイド"]:
        assert forbidden not in source, f"{language}: forbidden {forbidden}"

print(json.dumps({"verified": True, "concepts": 10, "questions": 10, "languages": 2, "revision": 24}, indent=2))
