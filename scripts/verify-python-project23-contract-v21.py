#!/usr/bin/env python3
"""Verify the Chapter 2.3 project contract and bilingual Notebook metadata."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MAP = ROOT / "sample-content/introduction-to-python/localization/project-2-3-concept-map-v1.json"


def main() -> None:
    data = json.loads(MAP.read_text(encoding="utf-8"))
    expected = [f"P23-{i:02d}" for i in range(1, 11)]
    concepts = data["concepts"]
    assert data["canonical_language"] == "en"
    assert data["adaptations"] == ["ja"]
    assert [item["id"] for item in concepts] == expected
    assert all(item["assignment"] and item["notebook"] and not item["teacher"] for item in concepts)

    implementation = (ROOT / data["implementation"]).read_text(encoding="utf-8")
    for required in ("validate_centre", "safe_percentage", "safe_unit_cost", "centre_metrics", "summarise_centres", "PYAI-V21-PROJECT23-BRIEF"):
        assert required in implementation

    required = ("validate_centre", "safe_percentage", "safe_unit_cost", "centre_metrics", "summarise_centres", "KeyError", "ValueError", "75%", "70%", "invalid_records")
    for language, relative in data["notebooks"].items():
        document = json.loads((ROOT / relative).read_text(encoding="utf-8"))
        metadata = document["metadata"]["pyai"]
        assert metadata["lesson"] == "2.3"
        assert metadata["language"] == language
        assert metadata["revision"] == 21
        assert metadata["concepts"] == expected
        assert document["nbformat"] == 4
        source = "".join("".join(cell["source"]) for cell in document["cells"])
        for needle in required:
            assert needle in source, (language, needle)
        for forbidden in ("Naledi", "ナレディ", "AI use declaration", "AI利用申告", "Teacher guide", "教師用ガイド", "model answer", "模範解答"):
            assert forbidden not in source

    print(json.dumps({"verified": True, "project_outcomes": 10, "languages": 2, "revision": 21}, indent=2))


if __name__ == "__main__":
    main()
