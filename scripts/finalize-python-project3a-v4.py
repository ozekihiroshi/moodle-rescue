#!/usr/bin/env python3
"""Apply the final display-contract correction to the Chapter 3A package."""
from __future__ import annotations

import subprocess
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"


def replace_exact(path: Path, old: str, new: str) -> None:
    text = path.read_text(encoding="utf-8")
    count = text.count(old)
    if count != 1:
        raise RuntimeError(f"Expected one display example in {path}, found {count}")
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        stream.write(text.replace(old, new))


def main() -> None:
    subprocess.run([sys.executable, str(ROOT / "scripts/finalize-python-project3a-v3.py")], check=True)
    for path in [
        BASE / "project-3a-brief-en.md",
        BASE / "project-3a-brief-ja.md",
        BASE / "python-lab/project-files/projects/school-meal-review/PROJECT_BRIEF.md",
        BASE / "python-lab/project-files/ja/projects/school-meal-review/PROJECT_BRIEF.md",
    ]:
        replace_exact(path, "FIRST DELIVERY: ... - ...", "FIRST DELIVERY: ... — ...")
    print("Display contract aligned in four public briefs.")


if __name__ == "__main__":
    main()
