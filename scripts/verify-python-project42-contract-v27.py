#!/usr/bin/env python3
import json
from pathlib import Path

root = Path(__file__).resolve().parents[1]
paths = {
    "en": root / "sample-content/introduction-to-python/python-lab/templates/P3_learning_centres_analysis.ipynb",
    "ja": root / "sample-content/introduction-to-python/python-lab/templates/ja/P3_learning_centres_analysis.ipynb",
}
required = [
    "find_course_data", "required-set(raw.columns)", "raw.copy()", "district_raw",
    ".str.strip()", ".str.title()", ".isna()", ".duplicated(", "keep=False",
    "analysis_ready", ".groupby(", "registered", "completed", "material_cost",
    "completion_rate", "cost_per_completion", "assert ", "plt.subplots", "set_xlim(0,100)",
]
for language, path in paths.items():
    doc = json.loads(path.read_text(encoding="utf-8"))
    assert doc["metadata"]["pyai"] == {"project": "4.2", "language": language, "revision": 27}
    source = "".join("".join(cell.get("source", [])) for cell in doc["cells"])
    for needle in required:
        assert needle in source, (language, needle)
    for forbidden in ["AI-use declaration", "AI use declaration", "AI利用申告", "Naledi", "ナレディ", "model answer", "模範解答", "teacher guide", "教師用"]:
        assert forbidden.lower() not in source.lower(), (language, forbidden)
print(json.dumps({"verified": True, "project": "4.2", "languages": 2, "revision": 27}, indent=2))
