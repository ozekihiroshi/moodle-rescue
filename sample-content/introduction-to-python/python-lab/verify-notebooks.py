#!/usr/bin/env python3
"""Execute every generated learner notebook without modifying the source."""

from __future__ import annotations

import argparse
import shutil
import tempfile
from pathlib import Path

import nbformat
from nbclient import NotebookClient


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("directory", type=Path)
    args = parser.parse_args()
    directory = args.directory.resolve()
    paths = sorted(directory.glob("*.ipynb"))
    if not paths:
        raise SystemExit("No notebooks found")
    with tempfile.TemporaryDirectory() as temporary:
        workspace = Path(temporary) / "course-materials"
        shutil.copytree(directory, workspace)
        for copied in workspace.rglob("*"):
            copied.chmod(copied.stat().st_mode | 0o200)
        for path in paths:
            workpath = workspace / path.name
            document = nbformat.read(workpath, as_version=4)
            NotebookClient(document, timeout=120, kernel_name="python3").execute(cwd=workspace)
            print(f"ok: {path.name}")
    print(f"executed: {len(paths)} notebooks")


if __name__ == "__main__":
    main()
