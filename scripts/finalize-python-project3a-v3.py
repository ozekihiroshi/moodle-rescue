#!/usr/bin/env python3
"""Python 3.8-compatible runner for the Chapter 3A finalizer."""
from __future__ import annotations

import ast
import json
import runpy
import shutil
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
PROJECTS = BASE / "python-lab/project-files"
TEMPLATES = BASE / "python-lab/templates"


def write_text(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        stream.write(text)


def main() -> None:
    finalizer = runpy.run_path(str(ROOT / "scripts/finalize-python-project3a-v2.py"), run_name="project3a_v2")
    prototype_path = ROOT / "scripts/build-python-project3a-prototype-v1.py"
    tree = ast.parse(prototype_path.read_text(encoding="utf-8"))
    constants = {}
    for node in tree.body:
        if isinstance(node, ast.Assign) and len(node.targets) == 1 and isinstance(node.targets[0], ast.Name):
            name = node.targets[0].id
            if name in {"DESIGN", "REFERENCE_CODE", "STARTER_CODE", "CHECKER_CODE"}:
                constants[name] = ast.literal_eval(node.value)
    if set(constants) != {"DESIGN", "REFERENCE_CODE", "STARTER_CODE", "CHECKER_CODE"}:
        raise RuntimeError("Prototype constants are incomplete")

    dataset = BASE / "datasets/chapter3-midterm/school-meals-practice.csv"
    write_text(BASE / "project-3-midterm-design-ja.md", constants["DESIGN"])
    reference = BASE / "reference-solutions/project-3a"
    write_text(reference / "meal_delivery_review.py", constants["REFERENCE_CODE"])
    (reference / "data").mkdir(parents=True, exist_ok=True)
    shutil.copyfile(dataset, reference / "data/school-meals-practice.csv")

    for prefix, brief, readme in [
        (Path(), finalizer["BRIEF_EN"], finalizer["README_EN"]),
        (Path("ja"), finalizer["BRIEF_JA"], finalizer["README_JA"]),
    ]:
        project = PROJECTS / prefix / "projects/school-meal-review"
        write_text(project / "meal_delivery_review.py", constants["STARTER_CODE"])
        write_text(project / "check_meal_delivery_review.py", constants["CHECKER_CODE"])
        write_text(project / "PROJECT_BRIEF.md", brief)
        write_text(project / "README.md", readme)
        (project / "data").mkdir(parents=True, exist_ok=True)
        shutil.copyfile(dataset, project / "data/school-meals-practice.csv")

    write_text(BASE / "project-3a-brief-en.md", finalizer["BRIEF_EN"])
    write_text(BASE / "project-3a-brief-ja.md", finalizer["BRIEF_JA"])
    for language in ["en", "ja"]:
        prefix = Path("ja") if language == "ja" else Path()
        target = TEMPLATES / prefix / "P3A_school_meal_delivery_review.ipynb"
        notebook = finalizer["make_notebook"](language)
        write_text(target, json.dumps(notebook, ensure_ascii=False, indent=1) + "\n")
    print(json.dumps({"briefs": 2, "project_copies": 2, "notebooks": 2}, ensure_ascii=False))


if __name__ == "__main__":
    main()
