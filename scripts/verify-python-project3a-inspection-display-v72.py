#!/usr/bin/env python3
"""Measure the learner-visible output of the completed Stage 1 program."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path


def section(text: str, start: str, end: str) -> list[str]:
    return text.split(start, 1)[1].split(end, 1)[0].splitlines()


def main() -> None:
    project = Path(sys.argv[1]).resolve()
    result = subprocess.run(
        [sys.executable, str(project / "inspect_school_meals.py")],
        cwd=project,
        text=True,
        capture_output=True,
        check=True,
        timeout=30,
    )
    original = section(result.stdout, "ALL RECORDS:\n", "SCHOOL/DATE VIEW:\n")
    ordered = section(result.stdout, "SCHOOL/DATE VIEW:\n", "MISSING VALUES:\n")
    report = {
        "all_table_lines": len(original),
        "sorted_table_lines": len(ordered),
        "max_line_width": max(map(len, original + ordered)),
        "truncated": any("..." in line for line in original + ordered),
        "whitespace_label_visible": repr(" North ") in result.stdout,
    }
    expected = {
        "all_table_lines": 38,
        "sorted_table_lines": 38,
        "truncated": False,
        "whitespace_label_visible": True,
    }
    for key, value in expected.items():
        if report[key] != value:
            raise RuntimeError(f"{key}: expected {value!r}, got {report[key]!r}")
    print(report)


if __name__ == "__main__":
    main()
