#!/usr/bin/env python3
"""Verify the Chapter 1 project map, rubric, and bilingual Notebooks."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MAP = ROOT / "sample-content/introduction-to-python/localization/project-1-7-concept-map-v1.json"


def main() -> None:
    data = json.loads(MAP.read_text(encoding="utf-8"))
    expected = [f"P{i:02d}" for i in range(1, 11)]
    concepts = data["concepts"]
    assert data["canonical_language"] == "en"
    assert data["adaptations"] == ["ja"]
    assert [item["id"] for item in concepts] == expected
    assert all(item["source_lessons"] == ["1.1", "1.2", "1.3", "1.4", "1.5", "1.6"] for item in concepts)
    assert all(item["notebook"] and item["assignment"] and not item["teacher"] for item in concepts)
    rubric = data["rubric"]
    assert [item["id"] for item in rubric] == expected
    assert sum(item["points"] for item in rubric) == 100
    implementation = (ROOT / data["implementation"]).read_text(encoding="utf-8")
    assert "PYAI-V18-PROJECT17" in implementation
    assert "Marking criteria (100 points)" in implementation and "採点基準（100点）" in implementation
    for language, relative in data["notebooks"].items():
        document = json.loads((ROOT / relative).read_text(encoding="utf-8"))
        metadata = document["metadata"]["pyai"]
        assert metadata["lesson"] == "1.7"
        assert metadata["language"] == language
        assert metadata["revision"] == 18
        assert metadata["concepts"] == expected
        assert document["nbformat"] == 4
        source = "".join("".join(cell["source"]) for cell in document["cells"])
        assert "Naledi" not in source and "ナレディ" not in source
        assert "AI-use" not in source and "AI利用" not in source
        assert "Teacher guide" not in source and "教師用ガイド" not in source
    print(json.dumps({"verified": True, "concepts": 10, "rubric_points": 100, "languages": 2, "revision": 18}, indent=2))


if __name__ == "__main__":
    main()
