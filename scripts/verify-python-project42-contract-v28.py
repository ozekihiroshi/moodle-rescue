#!/usr/bin/env python3
"""Verify the concrete Project 4.2 notebook contract in both languages."""
import json
from pathlib import Path

root = Path(__file__).resolve().parents[1]
paths = {
    "en": root / "sample-content/introduction-to-python/python-lab/templates/P3_learning_centres_analysis.ipynb",
    "ja": root / "sample-content/introduction-to-python/python-lab/templates/ja/P3_learning_centres_analysis.ipynb",
}
shared = [
    "find_course_data",
    "/home/jovyan/work/data/learning-centres-practice.csv",
    "assert raw.shape == (24, 10)",
    "Python Foundations",
    "Digital Skills",
    "district_raw",
    "analysis_ready",
    "completion_rate",
    "cost_per_completion",
    "set_xlim(0,100)",
]
language_specific = {
    "en": [
        "Concrete question",
        "percentage-point difference",
        "three separate quality checks",
        "Answer these five prompts in order",
        "Pre-submission checklist",
    ],
    "ja": [
        "具体的な問い",
        "パーセントポイント",
        "三つの品質検査",
        "次の5項目へ順番に答える",
        "提出前チェックリスト",
    ],
}
for language, path in paths.items():
    doc = json.loads(path.read_text(encoding="utf-8"))
    assert doc["metadata"]["pyai"] == {
        "project": "4.2", "language": language, "revision": 28
    }
    source = "".join("".join(cell.get("source", [])) for cell in doc["cells"])
    for needle in shared + language_specific[language]:
        assert needle in source, (language, needle)
    for forbidden in ["Naledi", "AI use declaration", "AI-use declaration", "model answer", "teacher guide"]:
        assert forbidden.lower() not in source.lower(), (language, forbidden)

print(json.dumps({"verified": True, "project": "4.2", "languages": 2, "revision": 28}, indent=2))
