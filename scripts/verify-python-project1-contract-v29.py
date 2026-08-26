#!/usr/bin/env python3
"""Verify Project 1.7 scope and the black-box checker itself."""
from __future__ import annotations

import ast
import json
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LAB = ROOT / "sample-content/introduction-to-python/python-lab"

REFERENCE = '''total_received = 0
total_resolved = 0
busiest_received = -1
busiest_day = ""
valid_data = True
for day_number in range(1, 6):
    if day_number == 1: day_name = "Monday"
    elif day_number == 2: day_name = "Tuesday"
    elif day_number == 3: day_name = "Wednesday"
    elif day_number == 4: day_name = "Thursday"
    else: day_name = "Friday"
    received = int(input())
    resolved = int(input())
    if received < 0 or resolved < 0 or resolved > received:
        valid_data = False
    total_received += received
    total_resolved += resolved
    if received > busiest_received:
        busiest_received = received
        busiest_day = day_name
if not valid_data:
    print("RESULT: INVALID")
else:
    unresolved = total_received - total_resolved
    if total_received == 0:
        rate_text = "N/A"
        status = "NO REQUESTS"
        busiest_day = "NONE"
    else:
        rate = total_resolved / total_received * 100
        rate_text = f"{rate:.1f}%"
        if rate >= 90: status = "ON TRACK"
        elif rate >= 80: status = "REVIEW"
        else: status = "PRIORITY SUPPORT"
    print("WEEKLY SUPPORT REPORT")
    print(f"TOTAL RECEIVED: {total_received}")
    print(f"TOTAL RESOLVED: {total_resolved}")
    print(f"UNRESOLVED: {unresolved}")
    print(f"RESOLUTION RATE: {rate_text}")
    print(f"STATUS: {status}")
    print(f"BUSIEST DAY: {busiest_day}")
'''

for language, notebook_rel, project_rel in [
    ("en", "templates/P1_weekly_support_report.ipynb", "project-files/projects/weekly-support"),
    ("ja", "templates/ja/P1_weekly_support_report.ipynb", "project-files/ja/projects/weekly-support"),
]:
    notebook_path = LAB / notebook_rel
    document = json.loads(notebook_path.read_text(encoding="utf-8"))
    assert document["metadata"]["pyai"] == {
        "lesson": "1.7", "language": language, "revision": 29, "artifact": "weekly_support.py"
    }
    notebook_source = "".join("".join(cell.get("source", [])) for cell in document["cells"])
    for required in ["weekly_support.py", "check_weekly_support.py", "ALL TESTS PASSED", "input()", "for"]:
        assert required in notebook_source, (language, required)
    for forbidden in ["pandas", "AI use", "AI利用", "Naledi", "ナレディ", "teacher guide", "教師用ガイド", "model answer", "模範解答"]:
        assert forbidden.lower() not in notebook_source.lower(), (language, forbidden)

    project = LAB / project_rel
    starter = project / "weekly_support.py"
    checker = project / "check_weekly_support.py"
    tree = ast.parse(starter.read_text(encoding="utf-8"))
    assert not any(isinstance(node, (ast.List, ast.Dict, ast.FunctionDef, ast.AsyncFunctionDef, ast.Import, ast.ImportFrom)) for node in ast.walk(tree)), language
    assert sum(isinstance(node, ast.For) for node in ast.walk(tree)) == 1, language
    compile(starter.read_text(encoding="utf-8"), str(starter), "exec")
    compile(checker.read_text(encoding="utf-8"), str(checker), "exec")

    with tempfile.TemporaryDirectory() as temporary:
        temp = Path(temporary)
        shutil.copyfile(checker, temp / "check_weekly_support.py")
        shutil.copyfile(starter, temp / "weekly_support.py")
        incomplete = subprocess.run([sys.executable, str(temp / "check_weekly_support.py")], text=True, capture_output=True)
        assert incomplete.returncode == 1 and "[NG]" in incomplete.stdout and "ALL TESTS PASSED" not in incomplete.stdout
        (temp / "weekly_support.py").write_text(REFERENCE, encoding="utf-8")
        complete = subprocess.run([sys.executable, str(temp / "check_weekly_support.py")], text=True, capture_output=True)
        assert complete.returncode == 0, complete.stdout + complete.stderr
        assert complete.stdout.count("[OK]") == 8 and "ALL TESTS PASSED" in complete.stdout

print(json.dumps({"verified": True, "project": "1.7", "revision": 29, "languages": 2, "checker_cases": 8}, indent=2))
