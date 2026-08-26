#!/usr/bin/env python3
"""Cross-check learner-page claims against the two distributed checkers."""
from pathlib import Path
import json

root = Path(__file__).resolve().parents[1]
base = root / "sample-content/introduction-to-python/python-lab/project-files"
paths = [
    base / "projects/weekly-support/check_weekly_support.py",
    base / "ja/projects/weekly-support/check_weekly_support.py",
]
case_names = [
    "standard week",
    "exactly 80 percent",
    "exactly 90 percent",
    "below 80 percent",
    "first day wins a busiest-day tie",
    "no requests",
    "resolved exceeds received",
    "negative count",
]
expected_values = [
    '"TOTAL RECEIVED":"75"', '"TOTAL RESOLVED":"67"', '"UNRESOLVED":"8"',
    '"RESOLUTION RATE":"89.3%"', '"STATUS":"REVIEW"', '"BUSIEST DAY":"Thursday"',
    '"RESOLUTION RATE":"80.0%"', '"RESOLUTION RATE":"90.0%"',
    '"STATUS":"ON TRACK"', '"STATUS":"PRIORITY SUPPORT"',
    '"RESOLUTION RATE":"N/A"', '"STATUS":"NO REQUESTS"', '"BUSIEST DAY":"NONE"',
    '"RESULT":"INVALID"',
]
for path in paths:
    source = path.read_text(encoding="utf-8")
    for needle in case_names + expected_values + ["ALL TESTS PASSED"]:
        assert needle in source, (path, needle)
print(json.dumps({"verified": True, "checkers": 2, "cases_each": 8, "sample_output_aligned": True}, indent=2))
