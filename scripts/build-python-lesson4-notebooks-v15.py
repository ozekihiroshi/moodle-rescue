#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for strings, input, and formatting."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id: str, text: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code(cell_id: str, text: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


def notebook(language: str) -> dict:
    if language == "ja":
        cells = [
            md("l14-ja-title", "# 1.4 — 文字列・入力・書式付き出力\n\n文字列を一つの値として扱うだけでなく、文字を取り出し、整え、外部入力を変換し、読み手に意味が伝わる出力を作ります。"),
            md("l14-ja-create", "## 文字列を作る\n\n一重引用符と二重引用符はどちらも文字列を作ります。内容に引用符を含める場合は外側と使い分けます。`\\n`は改行、`\\t`はタブ、`\\\\`はバックスラッシュです。"),
            code("l14-ja-create-code", "course = \"Python入門\"\nmessage = '講師は「実行して確認」と言いました。'\nprint(course)\nprint(message)\nprint(\"1行目\\n2行目\")\n"),
            md("l14-ja-sequence", "## 文字の並びとして読む\n\n文字列は0から始まる位置で文字を取り出せます。スライス`[開始:終了]`は終了位置を含みません。`len()`は文字数を返します。"),
            code("l14-ja-sequence-code", "centre = \"North Centre\"\nprint(centre[0])\nprint(centre[-1])\nprint(centre[0:5])\nprint(len(centre))\n"),
            md("l14-ja-methods", "## 元の文字列を壊さずに整える\n\n文字列は変更不能です。`strip()`、`lower()`、`upper()`、`replace()`は整えた新しい文字列を返します。必要なら名前へ再代入します。"),
            code("l14-ja-methods-code", "raw_name = \"  North Centre  \"\nclean_name = raw_name.strip()\nprint(clean_name)\nprint(clean_name.lower())\nprint(clean_name.replace(\"Centre\", \"Learning Centre\"))\nprint(\"元の値:\", raw_name)\n"),
            md("l14-ja-combine", "## 文字列を連結する\n\n`+`は文字列同士を連結し、`*`は文字列を繰り返します。数値を連結する場合は`str()`で明示的に変換します。"),
            code("l14-ja-combine-code", "completed = 29\nprint(\"修了者: \" + str(completed))\nprint(\"-\" * 20)\n"),
            md("l14-ja-input", "## input()が返すものを確認する\n\n`input()`は、数字を入力しても常に文字列を返します。下のセルは自動検証で停止しないようコメントにしてあります。新しいセルへコピーしてコメントを外し、`36`を入力して型を確認してください。"),
            code("l14-ja-input-code", "# registered_text = input(\"登録者数: \")\n# print(registered_text, type(registered_text))\n# registered = int(registered_text)\n# print(registered + 4)\n\n# 自動実行できる同じ状態\nregistered_text = \"36\"\nregistered = int(registered_text)\nprint(registered + 4)\n"),
            md("l14-ja-format", "## f文字列で意味と値を一つの出力にする\n\n文字列の先頭に`f`を付けると、波括弧内の式を評価できます。`.1f`は小数第1位、`.2f`は小数第2位、`,`は桁区切りです。"),
            code("l14-ja-format-code", "centre = \"North Centre\"\nregistered = 36\ncompleted = 29\nrate = completed / registered * 100\ncost = 12345.5\nprint(f\"{centre}: {completed}/{registered}人、修了率 {rate:.1f}%\")\nprint(f\"教材費: {cost:,.2f}\")\n"),
            md("l14-ja-split", "## 区切られた文字列を分ける\n\n`split()`は区切り文字で文字列を分け、複数の文字列を返します。戻り値はリストなので、詳しい操作はコレクションの章で学びます。"),
            code("l14-ja-split-code", "record = \"North,36,29\"\nparts = record.split(\",\")\nprint(parts)\nprint(parts[0], int(parts[1]), int(parts[2]))\n"),
            md("l14-ja-transfer", "## 応用練習\n\n学習センター名の前後に空白がある文字列、登録者数`\"36\"`、修了者数`\"29\"`を用意します。センター名を整え、人数を整数へ変換し、修了率を計算して、`North Centre: 29/36人、修了率 80.6%`の形式で表示してください。次にセンター名を別の値へ変え、同じコードで表示できることを確認します。"),
            code("l14-ja-work", "# ここに応用練習の解答を書きます。\n\n"),
            md("l14-ja-complete", "## 完了確認\n\n引用符とエスケープ、添字とスライス、文字列の変更不能性、主なメソッド、`input()`の戻り型、数値変換、f文字列の書式を説明できれば、保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l14-en-title", "# 1.4 — Strings, input, and formatted output\n\nTreat text as a sequence: select characters, clean values, convert external input, and create output whose meaning is clear to another reader."),
            md("l14-en-create", "## Create strings\n\nSingle and double quotes both create strings. Choose the outer quotes so that quoted content remains readable. `\\n` is a newline, `\\t` is a tab, and `\\\\` is a backslash."),
            code("l14-en-create-code", "course = \"Introduction to Python\"\nmessage = 'The teacher said, \"Run and check.\"'\nprint(course)\nprint(message)\nprint(\"Line 1\\nLine 2\")\n"),
            md("l14-en-sequence", "## Read text as a sequence\n\nString positions start at zero. A slice `[start:end]` excludes the end position. `len()` returns the number of characters."),
            code("l14-en-sequence-code", "centre = \"North Centre\"\nprint(centre[0])\nprint(centre[-1])\nprint(centre[0:5])\nprint(len(centre))\n"),
            md("l14-en-methods", "## Clean text without modifying the original object\n\nStrings are immutable. `strip()`, `lower()`, `upper()`, and `replace()` return new strings. Assign the result when it should become the current value."),
            code("l14-en-methods-code", "raw_name = \"  North Centre  \"\nclean_name = raw_name.strip()\nprint(clean_name)\nprint(clean_name.lower())\nprint(clean_name.replace(\"Centre\", \"Learning Centre\"))\nprint(\"Original:\", raw_name)\n"),
            md("l14-en-combine", "## Combine strings\n\n`+` joins strings and `*` repeats text. Convert a numeric value explicitly with `str()` before concatenation."),
            code("l14-en-combine-code", "completed = 29\nprint(\"Completed: \" + str(completed))\nprint(\"-\" * 20)\n"),
            md("l14-en-input", "## Inspect what input() returns\n\n`input()` always returns a string, even when the user types digits. The interactive lines are commented so automated execution cannot wait indefinitely. Copy them to a new cell, remove the comments, enter `36`, and inspect its type."),
            code("l14-en-input-code", "# registered_text = input(\"Registered learners: \")\n# print(registered_text, type(registered_text))\n# registered = int(registered_text)\n# print(registered + 4)\n\n# The same state for automatic execution\nregistered_text = \"36\"\nregistered = int(registered_text)\nprint(registered + 4)\n"),
            md("l14-en-format", "## Use f-strings to put meaning and value in one output\n\nPrefix a string with `f` and Python evaluates expressions inside braces. `.1f` displays one decimal place, `.2f` two, and `,` adds a thousands separator."),
            code("l14-en-format-code", "centre = \"North Centre\"\nregistered = 36\ncompleted = 29\nrate = completed / registered * 100\ncost = 12345.5\nprint(f\"{centre}: {completed}/{registered} learners, completion {rate:.1f}%\")\nprint(f\"Materials cost: {cost:,.2f}\")\n"),
            md("l14-en-split", "## Split delimited text\n\n`split()` separates a string at a delimiter and returns several strings in a list. Collection operations are developed in their own chapter."),
            code("l14-en-split-code", "record = \"North,36,29\"\nparts = record.split(\",\")\nprint(parts)\nprint(parts[0], int(parts[1]), int(parts[2]))\n"),
            md("l14-en-transfer", "## Transfer exercise\n\nStart with a centre name containing surrounding spaces, registration text `\"36\"`, and completion text `\"29\"`. Clean the name, convert both counts to integers, calculate completion rate, and display `North Centre: 29/36 learners, completion 80.6%`. Change the centre name and confirm the same code still works."),
            code("l14-en-work", "# Write the transfer solution here.\n\n"),
            md("l14-en-complete", "## Completion check\n\nWhen you can explain quotes and escapes, indexing and slicing, string immutability, common methods, the return type of `input()`, numeric conversion, and f-string formatting, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "1.4", "language": language, "concepts": [f"S{i:02d}" for i in range(1, 11)], "revision": 15},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "04_strings_input_formatting.ipynb", "ja": TEMPLATES / "ja/04_strings_input_formatting.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "create strings with suitable quotes and escapes",
        "index and slice a string using zero-based positions",
        "inspect string length",
        "clean text with methods that return new strings",
        "combine strings and convert non-text values explicitly",
        "recognise that input always returns str",
        "convert numeric input before arithmetic",
        "format values and expressions with f-strings",
        "recognise string immutability",
        "split delimited text and convert selected fields",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "1.4 Strings, input, and formatted output",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"S{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L14R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/04_strings_input_formatting.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/04_strings_input_formatting.ipynb",
        },
        "implementation": "scripts/upgrade-python-lesson4-v15.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-1-4-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
