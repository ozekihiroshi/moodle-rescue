#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for selection and Boolean logic."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id: str, text: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code(cell_id: str, text: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


SETUP = '''from pathlib import Path
import pandas as pd


def find_course_data(filename):
    """Find course data without depending on the Notebook start directory."""
    roots = [Path.cwd(), *Path.cwd().parents, Path.home() / "work", Path("/opt/python-lab/course-materials")]
    checked = []
    for root in roots:
        for candidate in (root / "data" / filename, root / filename):
            candidate = candidate.expanduser()
            if candidate in checked:
                continue
            checked.append(candidate)
            if candidate.is_file():
                return candidate
    locations = "\\n".join(f"- {path}" for path in checked)
    raise FileNotFoundError(f"Course data file {filename!r} was not found. Checked:\\n{locations}")


data_file = find_course_data("learning-centres-practice.csv")
print("Loading:", data_file.resolve())
df = pd.read_csv(data_file, encoding="utf-8", dtype={"centre_id": "string", "month": "string"})
print("Shape:", df.shape)
'''


def notebook(language: str) -> dict:
    if language == "ja":
        cells = [
            md("l32-ja-title", "# 3.2 — データの選択・抽出とブール論理\n\n分析の問いを「表示する列」と「残す行の条件」へ分け、条件を検証可能なブールマスクとして表します。"),
            md("l32-ja-question", "## 問いを列と行へ翻訳する\n\n「2026年2月と3月について、登録者30人以上で出席率80%未満のセンター名を確認する」なら、表示列、月の所属条件、登録者数の下限、出席率の上限へ分解します。抽出前に必要列が存在するか確認します。"),
            code("l32-ja-setup", SETUP),
            md("l32-ja-columns", "## 列名のリストで必要な列だけを選ぶ\n\n一列はSeries、複数列は列名リストを使うDataFrameです。存在しない列名は`KeyError`になるため、必要列と`df.columns`の差集合を先に確認できます。"),
            code("l32-ja-columns-code", "required = {\"month\", \"centre_name\", \"registered\", \"attended\", \"completed\"}\nmissing = required - set(df.columns)\nif missing:\n    raise KeyError(f\"必要な列がありません: {sorted(missing)}\")\n\nfocused = df[[\"month\", \"centre_name\", \"registered\", \"attended\", \"completed\"]]\nprint(focused.head(3))\n"),
            md("l32-ja-loc", "## locはラベル、ilocは位置で選ぶ\n\n`loc[行条件, 列名]`はindexラベルと列名を使い、分析の意味をコードへ残せます。`iloc[行位置, 列位置]`は0から始まる位置を使い、先頭数行の確認などに向きます。indexラベルが0, 1, 2とは限らないため、両者を混同しません。"),
            code("l32-ja-loc-code", "print(df.loc[df.index[:2], [\"centre_id\", \"centre_name\"]])\nprint(df.iloc[:2, [1, 2]])\n"),
            md("l32-ja-mask", "## 比較式は各行のTrue・Falseを持つマスクを作る\n\n列と値を比較すると、元のindexと対応したブール値のSeriesができます。`True`の行だけが残り、`mask.sum()`は該当件数になります。抽出前後の件数を表示すると、条件の誤りに気づきやすくなります。"),
            code("l32-ja-mask-code", "large = df[\"registered\"] >= 30\nprint(large.head())\nprint(\"該当件数:\", int(large.sum()))\nprint(df.loc[large, [\"month\", \"centre_name\", \"registered\"]].head())\n"),
            md("l32-ja-bool", "## pandasではand・or・notではなく&・|・~を使う\n\nPythonの単一の真偽値には`and`、`or`、`not`を使います。pandasのSeriesを行ごとに組み合わせるときは`&`、`|`、`~`を使い、各比較を括弧で囲みます。`and`へSeriesを渡すと、Series全体を一つの真偽値にできずエラーになります。"),
            code("l32-ja-truth", "truth = pd.DataFrame({\"A\": [False, False, True, True], \"B\": [False, True, False, True]})\ntruth[\"A & B\"] = truth[\"A\"] & truth[\"B\"]\ntruth[\"A | B\"] = truth[\"A\"] | truth[\"B\"]\ntruth[\"~A\"] = ~truth[\"A\"]\ntruth\n"),
            md("l32-ja-combine", "## ANDは両方、ORはいずれか、NOTは反転\n\nANDは条件を狭め、ORは通常広げます。否定はTrueとFalseを反転します。ド・モルガンの法則により`~(A | B)`は`(~A) & (~B)`と同じですが、業務上の意味が読みやすい形を選びます。"),
            code("l32-ja-combine-code", "report = df.assign(\n    attendance_rate=df[\"attended\"] / df[\"registered\"] * 100,\n    completion_rate=df[\"completed\"] / df[\"registered\"] * 100,\n)\nlarge = report[\"registered\"] >= 30\nlow_attendance = report[\"attendance_rate\"] < 80\nprint(\"AND件数:\", int((large & low_attendance).sum()))\nprint(\"OR件数:\", int((large | low_attendance).sum()))\n"),
            md("l32-ja-membership", "## isinは所属、betweenは範囲を表す\n\n複数候補のいずれかに一致する条件は`isin()`、下限と上限を持つ条件は`between()`で表せます。`between()`は既定で両端を含むため、境界を含めるかを問題文と一致させます。"),
            code("l32-ja-membership-code", "months = report[\"month\"].isin([\"2026-02\", \"2026-03\"])\nmedium_size = report[\"registered\"].between(25, 35, inclusive=\"both\")\nprint(report.loc[months & medium_size, [\"month\", \"centre_name\", \"registered\"]])\n"),
            md("l32-ja-missing", "## 欠損を条件の外へ偶然落とさず、明示する\n\n欠損値との大小比較は通常Falseになり、理由を示さないまま抽出から消えることがあります。値が必要な条件には`notna()`、欠損を調べる条件には`isna()`を組み込み、該当件数を別に記録します。ここでは値を補完せず、3.3で扱う品質問題として残します。"),
            code("l32-ja-missing-code", "has_attendance = report[\"attended\"].notna()\nlow_attendance_known = has_attendance & (report[\"attendance_rate\"] < 80)\nprint(\"出席値あり・80%未満:\", int(low_attendance_known.sum()))\nprint(\"出席値欠損:\", int(report[\"attended\"].isna().sum()))\n"),
            md("l32-ja-result", "## locで行条件と表示列を一度に指定する\n\n問いを名前付きマスクへ分け、最後に`loc`で組み合わせると、条件を一つずつ検証できます。抽出結果を勝手に「全データ」と呼ばず、元件数、該当件数、条件を一緒に記録します。"),
            code("l32-ja-result-code", "selected_columns = [\"month\", \"centre_id\", \"centre_name\", \"registered\", \"attendance_rate\", \"completion_rate\"]\npriority_mask = (\n    report[\"month\"].isin([\"2026-02\", \"2026-03\"])\n    & (report[\"registered\"] >= 30)\n    & report[\"attended\"].notna()\n    & (report[\"attendance_rate\"] < 80)\n)\npriority = report.loc[priority_mask, selected_columns].sort_values([\"month\", \"centre_id\"])\nprint(\"元の行数:\", len(report), \"抽出行数:\", len(priority))\npriority\n"),
            md("l32-ja-index", "## マスクはindexで行へ対応する\n\npandasはブールマスクを位置だけでなくindexラベルで対応させます。別のDataFrameから作ったマスクや、indexを変更した後の古いマスクを流用すると、ずれやエラーの原因になります。原則として抽出対象と同じDataFrameからマスクを作ります。"),
            md("l32-ja-transfer", "## 応用練習\n\n「2026年2月または3月、Python Foundations、登録者25～40人、修了率75%未満、修了値が欠損していないセンター」を抽出してください。必要列を検証し、各部分マスクと最終マスクの件数を表示し、結果を月・センターID順に並べます。条件の否定を一つ含む別の問いも作って比較してください。"),
            code("l32-ja-work", "# ここに応用練習の解答を書きます。\n"),
            md("l32-ja-complete", "## 完了確認\n\n問いから列と行条件を分け、列名リスト、`loc`、`iloc`、比較マスク、`& | ~`と括弧、AND・OR・NOT、`isin()`、`between()`、`isna()`・`notna()`、index対応、件数検証を説明できたら保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l32-en-title", "# 3.2 — Data selection, filtering, and Boolean logic\n\nTranslate an analysis question into displayed columns and row conditions, expressed as verifiable Boolean masks."),
            md("l32-en-question", "## Translate a question into columns and rows\n\nFor “show centre names in February and March with at least 30 registrations and attendance below 80%,” separate display columns, month membership, a registration lower bound, and an attendance-rate upper bound. Confirm required columns before filtering."),
            code("l32-en-setup", SETUP),
            md("l32-en-columns", "## Select only needed columns with a list of names\n\nOne column is a Series; several columns form a DataFrame selected with a list of names. A missing name raises `KeyError`, so compare required names with `df.columns` first."),
            code("l32-en-columns-code", "required = {\"month\", \"centre_name\", \"registered\", \"attended\", \"completed\"}\nmissing = required - set(df.columns)\nif missing:\n    raise KeyError(f\"Missing required columns: {sorted(missing)}\")\n\nfocused = df[[\"month\", \"centre_name\", \"registered\", \"attended\", \"completed\"]]\nprint(focused.head(3))\n"),
            md("l32-en-loc", "## loc selects by labels; iloc selects by positions\n\n`loc[row condition, column names]` uses index labels and named columns, keeping analytical meaning in the code. `iloc[row positions, column positions]` uses zero-based positions and is useful for checks such as the first few rows. Index labels are not guaranteed to be 0, 1, 2, so do not confuse the two."),
            code("l32-en-loc-code", "print(df.loc[df.index[:2], [\"centre_id\", \"centre_name\"]])\nprint(df.iloc[:2, [1, 2]])\n"),
            md("l32-en-mask", "## A comparison creates a True/False mask for every row\n\nComparing a column with a value creates a Boolean Series aligned with the source index. Only `True` rows remain. `mask.sum()` counts matching rows. Print counts before and after selection to expose mistaken conditions."),
            code("l32-en-mask-code", "large = df[\"registered\"] >= 30\nprint(large.head())\nprint(\"Matching rows:\", int(large.sum()))\nprint(df.loc[large, [\"month\", \"centre_name\", \"registered\"]].head())\n"),
            md("l32-en-bool", "## Use & | ~ for pandas, not and or not\n\nUse Python `and`, `or`, and `not` for individual Boolean values. Combine pandas Series row by row with `&`, `|`, and `~`, placing each comparison in parentheses. Passing Series to `and` asks for one truth value for the whole Series and raises an error."),
            code("l32-en-truth", "truth = pd.DataFrame({\"A\": [False, False, True, True], \"B\": [False, True, False, True]})\ntruth[\"A & B\"] = truth[\"A\"] & truth[\"B\"]\ntruth[\"A | B\"] = truth[\"A\"] | truth[\"B\"]\ntruth[\"~A\"] = ~truth[\"A\"]\ntruth\n"),
            md("l32-en-combine", "## AND requires both, OR either, and NOT reverses\n\nAND narrows a condition, while OR usually broadens it. NOT reverses True and False. By De Morgan's law, `~(A | B)` equals `(~A) & (~B)`, although the form that best communicates the operational meaning is preferable."),
            code("l32-en-combine-code", "report = df.assign(\n    attendance_rate=df[\"attended\"] / df[\"registered\"] * 100,\n    completion_rate=df[\"completed\"] / df[\"registered\"] * 100,\n)\nlarge = report[\"registered\"] >= 30\nlow_attendance = report[\"attendance_rate\"] < 80\nprint(\"AND count:\", int((large & low_attendance).sum()))\nprint(\"OR count:\", int((large | low_attendance).sum()))\n"),
            md("l32-en-membership", "## isin represents membership and between represents a range\n\nUse `isin()` for membership in several candidate values and `between()` for lower and upper bounds. `between()` includes both endpoints by default, so match boundary inclusion to the wording of the question."),
            code("l32-en-membership-code", "months = report[\"month\"].isin([\"2026-02\", \"2026-03\"])\nmedium_size = report[\"registered\"].between(25, 35, inclusive=\"both\")\nprint(report.loc[months & medium_size, [\"month\", \"centre_name\", \"registered\"]])\n"),
            md("l32-en-missing", "## Make missingness explicit instead of losing rows accidentally\n\nComparisons with missing values are usually False, so rows can disappear without explaining why. Add `notna()` when a value is required or `isna()` when investigating missingness, and record the count separately. Do not fill values here; retain them as quality issues for Lesson 3.3."),
            code("l32-en-missing-code", "has_attendance = report[\"attended\"].notna()\nlow_attendance_known = has_attendance & (report[\"attendance_rate\"] < 80)\nprint(\"Known attendance below 80%:\", int(low_attendance_known.sum()))\nprint(\"Missing attendance:\", int(report[\"attended\"].isna().sum()))\n"),
            md("l32-en-result", "## Use loc for the row condition and displayed columns together\n\nBuild named masks for each part of the question, verify them separately, then combine them in `loc`. Do not call a subset “all data”; record the source count, selected count, and condition."),
            code("l32-en-result-code", "selected_columns = [\"month\", \"centre_id\", \"centre_name\", \"registered\", \"attendance_rate\", \"completion_rate\"]\npriority_mask = (\n    report[\"month\"].isin([\"2026-02\", \"2026-03\"])\n    & (report[\"registered\"] >= 30)\n    & report[\"attended\"].notna()\n    & (report[\"attendance_rate\"] < 80)\n)\npriority = report.loc[priority_mask, selected_columns].sort_values([\"month\", \"centre_id\"])\nprint(\"Source rows:\", len(report), \"selected rows:\", len(priority))\npriority\n"),
            md("l32-en-index", "## A mask aligns to rows by index\n\npandas aligns a Boolean mask by index label, not only by physical position. Reusing a mask from another DataFrame or from before an index change can cause misalignment or an error. Normally build a mask from the same DataFrame being selected."),
            md("l32-en-transfer", "## Transfer exercise\n\nSelect centres in February or March, for Python Foundations, with 25–40 registrations, completion below 75%, and a known completion value. Validate required columns, print counts for each partial and final mask, and sort by month and centre ID. Create and compare a second question containing one negated condition."),
            code("l32-en-work", "# Write the transfer solution here.\n"),
            md("l32-en-complete", "## Completion check\n\nWhen you can separate a question into columns and row conditions and explain column-name lists, `loc`, `iloc`, comparison masks, parentheses with `& | ~`, AND/OR/NOT, `isin()`, `between()`, `isna()`/`notna()`, index alignment, and count verification, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "3.2", "language": language, "concepts": [f"B{i:02d}" for i in range(1, 11)], "revision": 23},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "08_filtering_boolean_logic.ipynb", "ja": TEMPLATES / "ja/08_filtering_boolean_logic.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "translate an analysis question into displayed columns and row conditions",
        "validate and select columns by exact names",
        "distinguish label selection with loc from positional selection with iloc",
        "create inspect and count an index-aligned Boolean mask",
        "combine pandas conditions with parenthesised ampersand pipe and tilde",
        "reason about AND OR NOT and De Morgan equivalence",
        "select memberships and inclusive ranges with isin and between",
        "handle missingness explicitly with isna and notna during selection",
        "combine named masks in loc and verify source and result counts",
        "avoid reusing a Boolean mask with a different index",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "3.2 Data selection, filtering, and Boolean logic",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"B{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L32R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/08_filtering_boolean_logic.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/08_filtering_boolean_logic.ipynb",
        },
        "implementation": "scripts/upgrade-python-lesson32-v23.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-3-2-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
