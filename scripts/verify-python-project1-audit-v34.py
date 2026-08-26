#!/usr/bin/env python3
"""Verify the learner-facing Project 1.7 contract and executable files."""
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
REFERENCE = ROOT / "scripts/fixtures/weekly_support_reference_v2.py"
FULL_RUN = "python projects/weekly-support/weekly_support.py"
FULL_CHECK = "python projects/weekly-support/check_weekly_support.py"

for language, notebook_rel, project_rel in [
    ("en", "templates/P1_weekly_support_report.ipynb", "project-files/projects/weekly-support"),
    ("ja", "templates/ja/P1_weekly_support_report.ipynb", "project-files/ja/projects/weekly-support"),
]:
    notebook = json.loads((LAB / notebook_rel).read_text(encoding="utf-8"))
    assert notebook["metadata"]["pyai"] == {
        "lesson": "1.7", "language": language, "revision": 34, "artifact": "weekly_support.py"
    }
    cells = {cell.get("id"): cell for cell in notebook["cells"]}
    output_text = "".join(cells["p1-output"]["source"])
    rules_text = "".join(cells["p1-rules"]["source"])
    assert "one `for` loop" in rules_text or "一つの`for`ループ" in rules_text
    assert "exactly as in this example" in output_text or "出力例と同じ形式" in output_text
    assert FULL_CHECK in "".join(cells["p1-check-command"]["source"])

    project = LAB / project_rel
    starter = project / "weekly_support.py"
    checker = project / "check_weekly_support.py"
    submitter = project / "submit_weekly_support.py"
    readme = (project / "README.md").read_text(encoding="utf-8")
    assert FULL_RUN in readme and FULL_CHECK in readme

    tree = ast.parse(starter.read_text(encoding="utf-8"))
    assert sum(isinstance(node, ast.For) for node in ast.walk(tree)) == 1
    assert not any(isinstance(node, (ast.List, ast.Dict, ast.FunctionDef, ast.Import, ast.ImportFrom)) for node in ast.walk(tree))

    submit_source = submitter.read_text(encoding="utf-8")
    assert "ALL TESTS PASSED" in submit_source
    assert "JUPYTERHUB_API_TOKEN" in submit_source
    assert "password" not in submit_source.lower()
    assert "secret" not in submit_source.lower()

    with tempfile.TemporaryDirectory() as temporary:
        temp = Path(temporary)
        shutil.copy2(checker, temp / "check_weekly_support.py")
        shutil.copy2(starter, temp / "weekly_support.py")
        incomplete = subprocess.run(
            [sys.executable, str(temp / "check_weekly_support.py")], text=True, capture_output=True
        )
        assert incomplete.returncode == 1
        assert "[NG]" in incomplete.stdout and "ALL TESTS PASSED" not in incomplete.stdout

        shutil.copy2(REFERENCE, temp / "weekly_support.py")
        passing = subprocess.run(
            [sys.executable, str(temp / "check_weekly_support.py")], text=True, capture_output=True
        )
        assert passing.returncode == 0, passing.stdout + passing.stderr
        assert passing.stdout.count("[OK]") == 8
        assert "ALL TESTS PASSED" in passing.stdout

print(json.dumps({
    "verified": True,
    "project": "1.7",
    "revision": 34,
    "languages": 2,
    "checker_cases_each": 8,
    "starter_scope": "one for loop; no lists, dictionaries, functions, imports",
    "credentials_embedded": False,
}, ensure_ascii=False, indent=2))
