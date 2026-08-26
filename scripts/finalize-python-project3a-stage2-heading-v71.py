#!/usr/bin/env python3
"""Make Stage 2 structurally explicit in all Project 3A briefs."""

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
PROJECTS = BASE / "python-lab/project-files"


def replace_once(path: Path, old: str, new: str) -> None:
    text = path.read_text(encoding="utf-8")
    count = text.count(old)
    if count != 1:
        raise RuntimeError(f"Expected one occurrence in {path}, found {count}: {old!r}")
    path.write_text(text.replace(old, new), encoding="utf-8", newline="\n")


def update_english(path: Path) -> None:
    replace_once(
        path,
        "## Processing order and verification rules\n",
        "## Stage 2 — Check quality and decide the additional delivery\n\n"
        "After completing the source-inspection program, open `meal_delivery_review.py`. "
        "Complete its existing eight functions to turn the observed source into review records, "
        "analysis data, a school ranking, and two saved CSV files. The following sections are the "
        "public contract for Stage 2.\n\n"
        "### Processing order and verification rules\n",
    )
    for old, new in [
        ("## Summary and ranking\n", "### Summary and ranking\n"),
        ("## Expected checkpoints for the supplied CSV\n", "### Expected checkpoints for the supplied CSV\n"),
        ("## Output files\n", "### Output files\n"),
        ("## Eight-function contract\n", "### Eight-function contract\n"),
    ]:
        replace_once(path, old, new)


def update_japanese(path: Path) -> None:
    replace_once(
        path,
        "## 判定する順序と品質規則\n",
        "## 第2段階 — 品質を判定し、追加配送先を決める\n\n"
        "原資料確認プログラムを完成させたら、`meal_delivery_review.py`を開きます。 "
        "既存の8関数を完成させ、確認した原資料から要確認記録、分析対象、学校別順位、 "
        "二つの出力CSVを作ります。以下は第2段階の公開仕様です。\n\n"
        "### 判定する順序と品質規則\n",
    )
    for old, new in [
        ("## 集計と順位の規則\n", "### 集計と順位の規則\n"),
        ("## 配布CSVで確認できる代表値\n", "### 配布CSVで確認できる代表値\n"),
        ("## 作成するファイル\n", "### 作成するファイル\n"),
        ("## 8関数の契約\n", "### 8関数の契約\n"),
    ]:
        replace_once(path, old, new)


def main() -> None:
    english = [
        BASE / "project-3a-brief-en.md",
        PROJECTS / "projects/school-meal-review/PROJECT_BRIEF.md",
    ]
    japanese = [
        BASE / "project-3a-brief-ja.md",
        PROJECTS / "ja/projects/school-meal-review/PROJECT_BRIEF.md",
    ]
    for path in english:
        update_english(path)
    for path in japanese:
        update_japanese(path)
    print({"briefs": 4, "stage1_headings": 4, "stage2_headings": 4})


if __name__ == "__main__":
    main()
