#!/usr/bin/env python3
"""Run the learner script as a black box with several input cases."""
from __future__ import annotations
import re
import subprocess
import sys
from pathlib import Path

TARGET = Path(__file__).with_name("weekly_support.py")
LABELS = ["TOTAL RECEIVED", "TOTAL RESOLVED", "UNRESOLVED", "RESOLUTION RATE", "STATUS", "BUSIEST DAY"]

CASES = [
    ("standard week", [(12, 10), (18, 16), (15, 15), (20, 17), (10, 9)], {"TOTAL RECEIVED":"75", "TOTAL RESOLVED":"67", "UNRESOLVED":"8", "RESOLUTION RATE":"89.3%", "STATUS":"REVIEW", "BUSIEST DAY":"Thursday"}),
    ("exactly 80 percent", [(10, 8)] * 5, {"TOTAL RECEIVED":"50", "TOTAL RESOLVED":"40", "UNRESOLVED":"10", "RESOLUTION RATE":"80.0%", "STATUS":"REVIEW", "BUSIEST DAY":"Monday"}),
    ("exactly 90 percent", [(10, 9)] * 5, {"TOTAL RECEIVED":"50", "TOTAL RESOLVED":"45", "UNRESOLVED":"5", "RESOLUTION RATE":"90.0%", "STATUS":"ON TRACK", "BUSIEST DAY":"Monday"}),
    ("below 80 percent", [(10, 8), (10, 8), (10, 8), (10, 8), (10, 7)], {"TOTAL RECEIVED":"50", "TOTAL RESOLVED":"39", "UNRESOLVED":"11", "RESOLUTION RATE":"78.0%", "STATUS":"PRIORITY SUPPORT", "BUSIEST DAY":"Monday"}),
    ("first day wins a busiest-day tie", [(20, 20), (20, 20), (10, 10), (5, 5), (0, 0)], {"TOTAL RECEIVED":"55", "TOTAL RESOLVED":"55", "UNRESOLVED":"0", "RESOLUTION RATE":"100.0%", "STATUS":"ON TRACK", "BUSIEST DAY":"Monday"}),
    ("no requests", [(0, 0)] * 5, {"TOTAL RECEIVED":"0", "TOTAL RESOLVED":"0", "UNRESOLVED":"0", "RESOLUTION RATE":"N/A", "STATUS":"NO REQUESTS", "BUSIEST DAY":"NONE"}),
    ("resolved exceeds received", [(4, 3), (5, 6), (2, 2), (1, 1), (3, 2)], {"RESULT":"INVALID"}),
    ("negative count", [(4, 3), (-1, 0), (2, 2), (1, 1), (3, 2)], {"RESULT":"INVALID"}),
]

def run_case(data):
    supplied = "".join(f"{received}\n{resolved}\n" for received, resolved in data)
    try:
        result = subprocess.run([sys.executable, str(TARGET)], input=supplied, text=True, capture_output=True, timeout=5)
    except subprocess.TimeoutExpired:
        return None, "program did not finish within 5 seconds"
    if result.returncode != 0:
        detail = "\n".join((result.stderr or result.stdout).strip().splitlines()[-6:])
        return None, f"program stopped with exit code {result.returncode}\n{detail}"
    values = {}
    if re.search(r"(?:^|\n)RESULT:\s*INVALID\s*$", result.stdout, re.MULTILINE):
        values["RESULT"] = "INVALID"
    for label in LABELS:
        match = re.search(rf"(?:^|\n){re.escape(label)}:\s*(.*?)\s*$", result.stdout, re.MULTILINE)
        if match:
            values[label] = match.group(1)
    return values, result.stdout

if "PROGRAM INCOMPLETE" in TARGET.read_text(encoding="utf-8"):
    print("週間サポート報告の自動確認")
    print("Target:", TARGET)
    print("[NG] starter program is not complete")
    print("     まずTODOを完成させ、最後のPROGRAM INCOMPLETEを削除してください。")
    raise SystemExit(1)

print("週間サポート報告の自動確認")
print("Target:", TARGET)
failures = 0
for name, data, expected in CASES:
    actual, detail = run_case(data)
    differences = []
    if actual is None:
        differences.append(detail)
    else:
        for key, expected_value in expected.items():
            actual_value = actual.get(key, "<missing>")
            if actual_value != expected_value:
                differences.append(f"{key}: expected {expected_value!r}, got {actual_value!r}")
    if differences:
        failures += 1
        print(f"[NG] {name}")
        for difference in differences:
            print("     " + difference.replace("\n", "\n     "))
    else:
        print(f"[OK] {name}")

if failures:
    print(f"\n{failures} check(s) need attention.")
    print("weekly_support.pyだけを修正し、もう一度実行してください。")
    raise SystemExit(1)
print("\nALL TESTS PASSED")
