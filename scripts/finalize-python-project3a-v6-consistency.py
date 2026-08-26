#!/usr/bin/env python3
"""Remove two contradictory remnants from the Project 3A v6 materials."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
PROJECTS = BASE / "python-lab/project-files"


def replace_exact(path: Path, old: str, new: str) -> None:
    text = path.read_text(encoding="utf-8")
    count = text.count(old)
    if count != 1:
        raise RuntimeError(f"Expected one occurrence in {path}, found {count}: {old[:70]!r}")
    path.write_text(text.replace(old, new), encoding="utf-8", newline="\n")


def update_briefs() -> None:
    for path in [BASE / "project-3a-brief-en.md", PROJECTS / "projects/school-meal-review/PROJECT_BRIEF.md"]:
        replace_exact(
            path,
            "Save it after completing both memo sections and after running the cells that display the complete source, structural checks, and latest output tables.",
            "Save it after completing the observation, processing-plan, and final-decision sections and after running the cells that display the complete source, structural checks, and latest output tables.",
        )
    for path in [BASE / "project-3a-brief-ja.md", PROJECTS / "ja/projects/school-meal-review/PROJECT_BRIEF.md"]:
        replace_exact(
            path,
            "全原資料、構造確認、最新結果表を表示した状態で、二つのメモ欄を記入してCtrl+Sで保存します。",
            "全原資料、構造確認、最新結果表を表示した状態で、観察、処理計画、最終判断の三つの記入欄を完成させ、Ctrl+Sで保存します。",
        )


def update_notebooks() -> None:
    settings = [
        (
            BASE / "python-lab/templates/P3A_school_meal_delivery_review.ipynb",
            "p3a-en-01",
            "# Chapter 3 midterm practical project A — Decide tomorrow's additional delivery\n\nComplete one Chapter 3 choice to satisfy the midterm requirement. This Notebook is both your workspace and one of the two assessed deliverables. Complete its observation, plan, result, and decision sections while implementing the eight functions in `projects/school-meal-review/meal_delivery_review.py`.",
        ),
        (
            BASE / "python-lab/templates/ja/P3A_school_meal_delivery_review.ipynb",
            "p3a-ja-01",
            "# 第3章 中間実践課題A — 明日の追加配送先を決める\n\n第3章の選択課題のうち一つを完成させると中間実践課題の必須条件を満たします。このNotebookは作業場所であると同時に、評価対象となる二つの提出物の一つです。`projects/school-meal-review/meal_delivery_review.py`の8関数を実装しながら、観察、計画、結果、最終判断を完成させます。",
        ),
    ]
    for path, cell_id, source in settings:
        document = json.loads(path.read_text(encoding="utf-8"))
        cells = [cell for cell in document["cells"] if cell.get("id") == cell_id]
        if len(cells) != 1:
            raise RuntimeError(f"Expected one {cell_id} in {path}")
        cells[0]["source"] = source.splitlines(keepends=True)
        path.write_text(json.dumps(document, ensure_ascii=False, indent=1) + "\n", encoding="utf-8", newline="\n")


def update_generator_source() -> None:
    path = ROOT / "scripts/finalize-python-project3a-observation-v6.py"
    replace_exact(
        path,
        "Save it after completing both memo sections and after running the cells that display the complete source, structural checks, and latest output tables.",
        "Save it after completing the observation, processing-plan, and final-decision sections and after running the cells that display the complete source, structural checks, and latest output tables.",
    )
    replace_exact(
        path,
        "全原資料、構造確認、最新結果表を表示した状態で、二つのメモ欄を記入してCtrl+Sで保存します。",
        "全原資料、構造確認、最新結果表を表示した状態で、観察、処理計画、最終判断の三つの記入欄を完成させ、Ctrl+Sで保存します。",
    )


def main() -> None:
    update_briefs()
    update_notebooks()
    update_generator_source()
    print({"briefs": 4, "notebooks": 2, "generator": 1, "contradictions": 0})


if __name__ == "__main__":
    main()
