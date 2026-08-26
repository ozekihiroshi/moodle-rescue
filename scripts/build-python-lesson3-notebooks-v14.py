#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for scalar types and arithmetic."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id: str, text: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code(cell_id: str, text: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


def make_notebook(language: str) -> dict:
    if language == "ja":
        cells = [
            md("l13-ja-title", "# 1.3 — 基本データ型・型変換・算術\n\n変数名が指す値には種類があります。このNotebookでは、値の型を確認し、型に合う演算と明示的な変換を行い、実務で使える割合を計算します。"),
            md("l13-ja-types", "## 値の型を確認する\n\n`int`は整数、`float`は小数を含む数、`str`は文字列、`bool`は真偽、`NoneType`は値がない状態を表します。`type()`で実際の型を確認します。"),
            code("l13-ja-types-code", "values = [34, 82.5, \"34\", True, None]\nfor value in values:\n    print(repr(value), type(value))\n"),
            md("l13-ja-arithmetic", "## 数値型で計算する\n\n`+`、`-`、`*`、`/`、`//`、`%`、`**`を順に実行します。`/`は通常`float`を返し、`//`は商を床方向へ丸め、`%`は余りを返します。"),
            code("l13-ja-arithmetic-code", "print(10 + 3)\nprint(10 - 3)\nprint(10 * 3)\nprint(10 / 3)\nprint(10 // 3)\nprint(10 % 3)\nprint(10 ** 2)\n"),
            md("l13-ja-precedence", "## 計算順序を明示する\n\nべき乗、乗除算、加減算の順に評価されます。仕事上の意味が読み取れるよう、必要なら括弧を使います。"),
            code("l13-ja-precedence-code", "print(2 + 3 * 4)\nprint((2 + 3) * 4)\nbooks = 53\nper_box = 12\nprint(\"満杯の箱:\", books // per_box)\nprint(\"余る冊数:\", books % per_box)\n"),
            md("l13-ja-conversion", "## 必要な型へ明示的に変換する\n\n`int()`、`float()`、`str()`で変換します。変換できない文字列は`ValueError`になります。`bool()`は内容を読んで真偽を判断するのではなく、空かどうかを基準にするため、`bool(\"False\")`も`True`です。"),
            code("l13-ja-conversion-code", "learners_text = \"36\"\nlearners = int(learners_text)\nrate_text = \"80.5\"\nrate = float(rate_text)\nprint(learners + 4, type(learners))\nprint(rate, type(rate))\nprint(str(learners) + \" learners\")\nprint(bool(\"False\"), bool(\"\"))\n"),
            md("l13-ja-conversion-error", "## 変換エラーを読む\n\n小数を表す文字列`\"12.5\"`は直接`int()`へ変換できません。まず`float()`へ変換し、その後整数化が本当に必要か判断します。"),
            code("l13-ja-conversion-error-code", "try:\n    int(\"12.5\")\nexcept ValueError as error:\n    print(type(error).__name__ + \":\", error)\n\nvalue = float(\"12.5\")\nprint(value, type(value))\n"),
            md("l13-ja-numeric-caution", "## 数値結果を検証する\n\n0で割ると`ZeroDivisionError`になります。浮動小数点数は2進数で近似されるため、`0.1 + 0.2`が画面上で厳密な0.3にならない場合があります。報告表示には`round()`を使えますが、丸める桁と理由を明確にします。"),
            code("l13-ja-numeric-caution-code", "print(0.1 + 0.2)\nprint(round(0.1 + 0.2, 1))\ntry:\n    print(10 / 0)\nexcept ZeroDivisionError as error:\n    print(type(error).__name__ + \":\", error)\n"),
            md("l13-ja-guided", "## 例題：修了率を計算する\n\n登録者36人、修了者29人です。割る順序、100を掛ける位置、表示する小数桁を予想してから実行します。"),
            code("l13-ja-guided-code", "registered = 36\ncompleted = 29\ncompletion_rate = completed / registered * 100\nprint(\"修了率:\", round(completion_rate, 1), \"%\")\n"),
            md("l13-ja-transfer", "## 応用練習\n\n教材53冊を1箱12冊で梱包します。満杯になる箱数、余る冊数、全冊を入れるために必要な箱数を表示してください。次に、教材費の文字列`\"487.50\"`を数値へ変換し、登録者30人で割った1人当たり教材費を小数第2位まで表示します。0人の場合に何が起こるかも説明します。"),
            code("l13-ja-work", "# ここに応用練習の解答を書きます。\n\n"),
            md("l13-ja-complete", "## 完了確認\n\n値と型を区別し、7つの算術演算子、優先順位、明示的な型変換、`ValueError`、`ZeroDivisionError`、浮動小数点の丸めを説明できれば、保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l13-en-title", "# 1.3 — Basic scalar types, conversion, and arithmetic\n\nEvery value referred to by a name has a type. This Notebook inspects those types, applies suitable operations, converts explicitly, and calculates a practical rate."),
            md("l13-en-types", "## Inspect value types\n\n`int` represents whole numbers, `float` represents numeric values with a fractional form, `str` represents text, `bool` represents truth values, and `NoneType` represents absence of a value. Use `type()` to inspect the actual value."),
            code("l13-en-types-code", "values = [34, 82.5, \"34\", True, None]\nfor value in values:\n    print(repr(value), type(value))\n"),
            md("l13-en-arithmetic", "## Calculate with numeric types\n\nRun `+`, `-`, `*`, `/`, `//`, `%`, and `**`. `/` normally returns a `float`; `//` floors the quotient; `%` returns the remainder."),
            code("l13-en-arithmetic-code", "print(10 + 3)\nprint(10 - 3)\nprint(10 * 3)\nprint(10 / 3)\nprint(10 // 3)\nprint(10 % 3)\nprint(10 ** 2)\n"),
            md("l13-en-precedence", "## Make calculation order explicit\n\nExponentiation, multiplication and division, then addition and subtraction are evaluated in order. Use parentheses when they make the work meaning easier to read."),
            code("l13-en-precedence-code", "print(2 + 3 * 4)\nprint((2 + 3) * 4)\nbooks = 53\nper_box = 12\nprint(\"Full boxes:\", books // per_box)\nprint(\"Books left:\", books % per_box)\n"),
            md("l13-en-conversion", "## Convert explicitly when a different type is required\n\nUse `int()`, `float()`, and `str()`. Text that cannot represent the requested value raises `ValueError`. `bool()` tests emptiness rather than understanding the word, so `bool(\"False\")` is still `True`."),
            code("l13-en-conversion-code", "learners_text = \"36\"\nlearners = int(learners_text)\nrate_text = \"80.5\"\nrate = float(rate_text)\nprint(learners + 4, type(learners))\nprint(rate, type(rate))\nprint(str(learners) + \" learners\")\nprint(bool(\"False\"), bool(\"\"))\n"),
            md("l13-en-conversion-error", "## Read a conversion error\n\nThe text `\"12.5\"` cannot be converted directly with `int()`. Convert it to `float` first, then decide whether discarding its fractional part is truly appropriate."),
            code("l13-en-conversion-error-code", "try:\n    int(\"12.5\")\nexcept ValueError as error:\n    print(type(error).__name__ + \":\", error)\n\nvalue = float(\"12.5\")\nprint(value, type(value))\n"),
            md("l13-en-numeric-caution", "## Validate numeric results\n\nDivision by zero raises `ZeroDivisionError`. Floating-point values are binary approximations, so `0.1 + 0.2` may not display as exact 0.3. Use `round()` for reporting when the chosen precision has a clear reason."),
            code("l13-en-numeric-caution-code", "print(0.1 + 0.2)\nprint(round(0.1 + 0.2, 1))\ntry:\n    print(10 / 0)\nexcept ZeroDivisionError as error:\n    print(type(error).__name__ + \":\", error)\n"),
            md("l13-en-guided", "## Guided example: calculate completion rate\n\nA centre has 36 registered learners and 29 completions. Predict the division order, placement of multiplication by 100, and displayed precision before running."),
            code("l13-en-guided-code", "registered = 36\ncompleted = 29\ncompletion_rate = completed / registered * 100\nprint(\"Completion rate:\", round(completion_rate, 1), \"%\")\n"),
            md("l13-en-transfer", "## Transfer exercise\n\nPack 53 learning books with 12 books per box. Display full boxes, remaining books, and boxes required to hold every book. Then convert material-cost text `\"487.50\"` to a number and display cost per learner for 30 registrations to two decimal places. Explain what happens with zero registrations."),
            code("l13-en-work", "# Write the transfer solution here.\n\n"),
            md("l13-en-complete", "## Completion check\n\nWhen you can distinguish value and type and explain seven arithmetic operators, precedence, explicit conversion, `ValueError`, `ZeroDivisionError`, and floating-point rounding, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "1.3", "language": language, "concepts": [f"T{i:02d}" for i in range(1, 11)], "revision": 14},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "03_basic_scalar_types.ipynb", "ja": TEMPLATES / "ja/03_basic_scalar_types.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(make_notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "inspect int, float, str, bool, and NoneType values",
        "distinguish numeric values from quoted text",
        "use true division and floor division appropriately",
        "use remainder for grouping and validation",
        "apply precedence and parentheses",
        "convert valid text explicitly",
        "diagnose invalid conversion with ValueError",
        "understand truth conversion of non-empty strings",
        "diagnose division by zero",
        "calculate and round a practical rate",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "1.3 Basic scalar types, conversion, and arithmetic",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"T{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L13R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/03_basic_scalar_types.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/03_basic_scalar_types.ipynb",
        },
        "implementation": "scripts/upgrade-python-lesson3-v14.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-1-3-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
