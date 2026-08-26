#!/usr/bin/env python3
"""Verify the machine-readable Lesson 1.2 evidence contract."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MAP = ROOT / "sample-content/introduction-to-python/localization/lesson-1-2-concept-map-v2.json"


def main() -> None:
    data = json.loads(MAP.read_text(encoding="utf-8"))
    concepts = data["concepts"]
    expected = [f"V{i:02d}" for i in range(1, 11)]
    actual = [item["id"] for item in concepts]
    assert actual == expected, (actual, expected)
    assert sum(item["level"] == "M" for item in concepts) == 7
    assert sum(item["level"] == "I" for item in concepts) == 3
    assert len({item["question"] for item in concepts}) == 10
    implementation = (ROOT / data["implementation"]).read_text(encoding="utf-8")
    for item in concepts:
        assert all(item[key] for key in ("lesson", "notebook", "question", "teacher"))
        assert item["id"] in implementation
        assert item["question"] in implementation
    for language, relative in data["notebooks"].items():
        notebook = json.loads((ROOT / relative).read_text(encoding="utf-8"))
        content = "\n".join("".join(cell.get("source", [])) for cell in notebook["cells"])
        for item in concepts:
            assert item["id"] in content, f"{language} Notebook lacks {item['id']}"
    print(json.dumps({"verified": True, "concepts": len(concepts), "questions": 10, "languages": 2}, indent=2))


if __name__ == "__main__":
    main()
