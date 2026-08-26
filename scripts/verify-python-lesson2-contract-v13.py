#!/usr/bin/env python3
"""Verify Lesson 2 concept coverage and notebook metadata."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MAP = ROOT / "sample-content/introduction-to-python/localization/lesson-1-2-concept-map-v2.json"


def main() -> None:
    data = json.loads(MAP.read_text(encoding="utf-8"))
    assert data["schema_version"] == 2
    assert data["canonical_language"] == "en"
    concepts = data["concepts"]
    expected = [f"V{i:02d}" for i in range(1, 11)]
    assert [item["id"] for item in concepts] == expected
    assert [item["question"] for item in concepts] == [f"L2R-{i:02d}" for i in range(1, 11)]
    assert all(item["lesson"] and item["notebook"] and not item["teacher"] for item in concepts)
    assert len({item["description"] for item in concepts}) == 10

    implementation = (ROOT / data["implementation"]).read_text(encoding="utf-8")
    for item in concepts:
        assert item["question"] in implementation

    for language, relative in data["notebooks"].items():
        document = json.loads((ROOT / relative).read_text(encoding="utf-8"))
        metadata = document["metadata"]["pyai"]
        assert metadata["language"] == language
        assert metadata["revision"] == 13
        assert metadata["concepts"] == expected
        assert document["nbformat"] == 4
        assert any(cell["cell_type"] == "code" for cell in document["cells"])
        assert any(cell["cell_type"] == "markdown" for cell in document["cells"])

    print(json.dumps({
        "verified": True,
        "concepts": len(concepts),
        "questions": 10,
        "languages": 2,
        "revision": 13,
    }, indent=2))


if __name__ == "__main__":
    main()
