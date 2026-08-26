#!/usr/bin/env python3
"""Verify the Project 2.4 source, language, data, and checker contract."""
from __future__ import annotations

import ast
import hashlib
import json
import os
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
EN = BASE / "python-lab/project-files/projects/library-manager"
JA = BASE / "python-lab/project-files/ja/projects/library-manager"
REF = BASE / "reference-solutions/project-2-4"
CHECKER = EN / "check_library_manager.py"
REQUIRED = ["parse_read", "load_books", "find_book", "add_book", "rename_book", "mark_as_read", "remove_book", "summarise_books", "save_books", "run_project"]


def digest(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def functions(path: Path) -> list[str]:
    tree = ast.parse(path.read_text(encoding="utf-8"))
    names = {node.name for node in tree.body if isinstance(node, ast.FunctionDef)}
    return [name for name in REQUIRED if name in names]


for directory in (EN, JA, REF):
    assert (directory / "data/books.csv").is_file(), directory
assert len({digest(directory / "data/books.csv") for directory in (EN, JA, REF)}) == 1
assert functions(EN / "library_manager.py") == REQUIRED
assert functions(JA / "library_manager.py") == REQUIRED
assert functions(REF / "library_manager.py") == REQUIRED

before = digest(EN / "data/books.csv")
with tempfile.TemporaryDirectory(prefix="project24-contract-") as temporary:
    cwd = Path(temporary)
    reference_copy = cwd / "library_manager.py"
    shutil.copy2(REF / "library_manager.py", reference_copy)
    shutil.copytree(REF / "data", cwd / "data")
    env = os.environ.copy()
    env["LIBRARY_MANAGER_TARGET"] = str(reference_copy)
    passed = subprocess.run([sys.executable, str(CHECKER)], cwd=cwd, env=env, text=True, capture_output=True, timeout=30)
    assert passed.returncode == 0, passed.stdout + passed.stderr
    assert passed.stdout.count("[OK]") == 10, passed.stdout
    assert "ALL TESTS PASSED" in passed.stdout

    for language, target, expected in (
        ("en", EN / "library_manager.py", "[NG] parse_read / load_books: sample CSV and Boolean conversion"),
        ("ja", JA / "library_manager.py", "[NG] parse_read / load_books：サンプルCSVとブール値変換"),
    ):
        env = os.environ.copy()
        env["LIBRARY_MANAGER_TARGET"] = str(target)
        env["LIBRARY_MANAGER_CHECK_LANGUAGE"] = language
        failed = subprocess.run([sys.executable, str(CHECKER)], cwd=cwd, env=env, text=True, capture_output=True, timeout=30)
        assert failed.returncode != 0, language
        assert failed.stdout.count("[NG]") == 10, failed.stdout
        assert expected in failed.stdout, failed.stdout
        assert "PROGRAM INCOMPLETE" in failed.stdout, failed.stdout

assert digest(EN / "data/books.csv") == before
assert "LIBRARY_MANAGER_SAMPLE" in (JA / "check_library_manager.py").read_text(encoding="utf-8")

for language, path in (
    ("en", BASE / "python-lab/templates/P2_csv_library_manager.ipynb"),
    ("ja", BASE / "python-lab/templates/ja/P2_csv_library_manager.ipynb"),
):
    notebook = json.loads(path.read_text(encoding="utf-8"))
    assert notebook["nbformat"] == 4 and len(notebook["cells"]) == 8, language
    cellids = [cell["id"] for cell in notebook["cells"]]
    assert len(cellids) == len(set(cellids)), language
    joined = "".join("".join(cell["source"]) for cell in notebook["cells"])
    for token in ("data/books.csv", "library_manager.py", "check_library_manager.py", "books_updated.csv", "PROGRAM INCOMPLETE", "ALL TESTS PASSED"):
        assert token in joined, (language, token)

for readme in (EN / "README.md", JA / "README.md"):
    text = readme.read_text(encoding="utf-8")
    for token in ("B005", "B003", "B001", "B004", "data/books.csv", "output/books_updated.csv", "PROGRAM INCOMPLETE", "ALL TESTS PASSED"):
        assert token in text, (readme, token)

print(json.dumps({"functions": len(REQUIRED), "checker_areas": 10, "languages": 2, "csv_sha256": before, "status": "ok"}, ensure_ascii=False, indent=2))
