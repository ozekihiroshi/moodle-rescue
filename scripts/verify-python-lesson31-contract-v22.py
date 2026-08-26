#!/usr/bin/env python3
"""Verify the Lesson 3.1 concept map and bilingual Notebook metadata."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MAP = ROOT / "sample-content/introduction-to-python/localization/lesson-3-1-concept-map-v1.json"


def main() -> None:
    data = json.loads(MAP.read_text(encoding="utf-8"))
    expected = [f"T{i:02d}" for i in range(1, 11)]
    concepts = data["concepts"]
    assert data["canonical_language"] == "en"
    assert data["adaptations"] == ["ja"]
    assert [item["id"] for item in concepts] == expected
    assert [item["question"] for item in concepts] == [f"L31R-{i:02d}" for i in range(1, 11)]
    assert all(item["lesson"] and item["notebook"] and not item["teacher"] for item in concepts)

    implementation = (ROOT / data["implementation"]).read_text(encoding="utf-8")
    for item in concepts:
        assert item["question"] in implementation

    required = ("find_course_data", "Path.cwd()", "read_csv", "encoding=\"utf-8\"", "dtype=", "head(", "shape", "columns", "dtypes", "info()", "isna().sum()", "Series", "DataFrame", "assign(", "to_csv(", "index=False")
    for language, relative in data["notebooks"].items():
        document = json.loads((ROOT / relative).read_text(encoding="utf-8"))
        metadata = document["metadata"]["pyai"]
        assert metadata["lesson"] == "3.1"
        assert metadata["language"] == language
        assert metadata["revision"] == 22
        assert metadata["concepts"] == expected
        assert document["nbformat"] == 4
        source = "".join("".join(cell["source"]) for cell in document["cells"])
        for needle in required:
            assert needle in source, (language, needle)
        for forbidden in ("Naledi", "ナレディ", "Teacher guide", "教師用ガイド"):
            assert forbidden not in source

    print(json.dumps({"verified": True, "concepts": 10, "questions": 10, "languages": 2, "revision": 22}, indent=2))


if __name__ == "__main__":
    main()
