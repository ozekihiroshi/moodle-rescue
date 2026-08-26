#!/usr/bin/env python3
"""Publish the reviewed Python Lab notebooks and course data.

The reviewed `.ipynb` files in `templates/` are the source of truth. Keeping
them as standard Notebook documents makes visual review possible and avoids
reconstructing learner material from large Python string literals.
"""

from __future__ import annotations

import argparse
import json
import shutil
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = Path(__file__).resolve().parent / "templates"
PROJECT_FILES = Path(__file__).resolve().parent / "project-files"
DATASETS = ROOT / "datasets"

DATA_FILES = (
    "learning-centres-practice.csv",
    "learning_centres_sample.csv",
    "library-books-practice.csv",
    "generate-learning-centre-data.py",
)


def validate_notebook(path: Path) -> None:
    document = json.loads(path.read_text(encoding="utf-8"))
    if document.get("nbformat") != 4 or not isinstance(document.get("cells"), list):
        raise ValueError(f"Not a supported Notebook: {path}")
    if not document["cells"]:
        raise ValueError(f"Notebook has no cells: {path}")
    ids = [cell.get("id") for cell in document["cells"]]
    if any(not cellid for cellid in ids) or len(ids) != len(set(ids)):
        raise ValueError(f"Notebook cell IDs must be present and unique: {path}")


def publish(output: Path) -> dict[str, int]:
    notebooks = sorted(TEMPLATES.rglob("*.ipynb"))
    if len(notebooks) != 53:
        raise ValueError(f"Expected 53 reviewed Notebook templates, found {len(notebooks)}")

    output.mkdir(parents=True, exist_ok=True)
    data_output = output / "data"
    data_output.mkdir(parents=True, exist_ok=True)

    for source in notebooks:
        validate_notebook(source)
        destination = output / source.relative_to(TEMPLATES)
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(source, destination)

    project_files = sorted(path for path in PROJECT_FILES.rglob("*") if path.is_file())
    if not project_files:
        raise ValueError(f"No project files found in {PROJECT_FILES}")
    for source in project_files:
        destination = output / source.relative_to(PROJECT_FILES)
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(source, destination)

    for filename in DATA_FILES:
        source = DATASETS / filename
        if not source.is_file():
            raise FileNotFoundError(source)
        shutil.copyfile(source, data_output / filename)

    return {"notebooks": len(notebooks), "project_files": len(project_files), "data_files": len(DATA_FILES)}


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    result = publish(args.output.resolve())
    print(json.dumps({"output": str(args.output.resolve()), **result}, indent=2))


if __name__ == "__main__":
    main()
