#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for repetition with loops."""

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
            md("l16-ja-title", "# 1.6 — ループによる繰り返し\n\n一つの処理を複数の値へ適用し、合計・件数・最大値などの状態を安全に更新します。反復回数と終了条件を説明できるコードを作ります。"),
            md("l16-ja-for", "## for文は反復可能な値を一つずつ取り出す\n\n`for 一時的な名前 in 値の並び:` と書き、字下げされた処理を各要素について一回ずつ実行します。ループ変数は現在処理している値を表します。"),
            code("l16-ja-for-code", "weekly_attendance = [28, 31, 32, 34]\nfor attendance in weekly_attendance:\n    print(\"今週の出席者数:\", attendance)\nprint(\"処理完了\")\n"),
            md("l16-ja-range", "## range()で回数と整数の範囲を表す\n\n`range(start, stop, step)` はstartを含み、stopを含みません。`range(1, 5)`は1、2、3、4です。反復回数を確認すると、境界の一つ多い・少ないという誤りを防げます。"),
            code("l16-ja-range-code", "print(list(range(1, 5)))\nfor week in range(1, 5):\n    print(f\"第{week}週\")\nprint(list(range(10, 4, -2)))\n"),
            md("l16-ja-total", "## アキュムレータはループの外で初期化し、内側で更新する\n\n合計のように反復をまたいで残す値をアキュムレータと呼びます。最初は0にし、各反復で現在の値を加えます。途中経過を表示すると更新順序を追跡できます。"),
            code("l16-ja-total-code", "weekly_attendance = [28, 31, 32, 34]\ntotal = 0\nfor attendance in weekly_attendance:\n    total += attendance\n    print(\"途中の合計:\", total)\nprint(\"合計:\", total)\nprint(\"平均:\", total / len(weekly_attendance))\n"),
            md("l16-ja-count", "## 条件に一致した件数を数える\n\nカウンタも0から始め、条件がTrueのときだけ1増やします。合計する値と数える件数を別の変数にすると、役割が明確になります。"),
            code("l16-ja-count-code", "weekly_attendance = [28, 31, 32, 34]\nweeks_at_least_30 = 0\nfor attendance in weekly_attendance:\n    if attendance >= 30:\n        weeks_at_least_30 += 1\nprint(weeks_at_least_30)\n"),
            md("l16-ja-maximum", "## 最大値は最初の実データから始める\n\n最大値を0で初期化すると、すべて負のデータで誤ります。空でないことが分かっているなら最初の要素から始めます。空データを許す場合は、先に明示的に扱います。"),
            code("l16-ja-maximum-code", "weekly_change = [-4, -2, -7, -3]\nif weekly_change:\n    largest = weekly_change[0]\n    for change in weekly_change[1:]:\n        if change > largest:\n            largest = change\n    print(\"最大値:\", largest)\nelse:\n    print(\"データなし\")\n"),
            md("l16-ja-enumerate", "## enumerate()で位置と値を同時に得る\n\n番号と値の両方が必要なら、手作業のカウンタより`enumerate()`を使います。`start=1`を指定すると、人が読む第1週から始められます。"),
            code("l16-ja-enumerate-code", "weekly_attendance = [28, 31, 32, 34]\nfor week, attendance in enumerate(weekly_attendance, start=1):\n    print(f\"第{week}週: {attendance}人\")\n"),
            md("l16-ja-control", "## continueは今回を飛ばし、breakはループを終了する\n\n`continue`は現在の反復の残りを飛ばして次へ進みます。`break`はループ全体を終了します。通常処理と例外的な制御を分け、なぜ飛ばす・止めるのかが読めるようにします。"),
            code("l16-ja-control-code", "readings = [28, None, 31, -1, 34]\nfor reading in readings:\n    if reading is None:\n        continue\n    if reading < 0:\n        print(\"無効値を検出。処理を停止します\")\n        break\n    print(\"有効値:\", reading)\n"),
            md("l16-ja-while", "## while文は条件がTrueの間繰り返す\n\n処理回数より終了条件で反復を決める場合は`while`を使います。条件に使う状態をループ内で更新しないと、無限ループになる可能性があります。"),
            code("l16-ja-while-code", "remaining = 3\nwhile remaining > 0:\n    print(\"残り:\", remaining)\n    remaining -= 1\nprint(\"完了\")\n"),
            md("l16-ja-choice", "## forとwhileを目的で選ぶ\n\n処理する値の並びがあるならfor、条件が満たされるまで続けるならwhileが自然です。既知のデータを添字でwhile処理するより、値を直接読むforの方が境界エラーを減らせます。"),
            code("l16-ja-choice-code", "weekly_attendance = [28, 31, 32, 34]\nfor attendance in weekly_attendance:\n    print(attendance)\n\n# 同じデータを添字で扱うwhileは、更新と境界の管理が必要です。\nindex = 0\nwhile index < len(weekly_attendance):\n    print(weekly_attendance[index])\n    index += 1\n"),
            md("l16-ja-transfer", "## 応用練習\n\n4週間の教材費`[82.5, 74.0, 91.5, 80.0]`を一つのfor文で処理し、合計、平均、最大値、80を超えた週の件数を求めます。各週を`enumerate()`で表示し、空のリストでは割り算を行わず「データなし」と表示してください。80ちょうどを件数に含めるかは、条件式と説明を一致させます。"),
            code("l16-ja-work", "# ここに応用練習の解答を書きます。\n\n"),
            md("l16-ja-complete", "## 完了確認\n\nfor、rangeの終端、アキュムレータ、カウンタ、最大値の初期化、enumerate、continue/break、whileの終了条件、空データを説明できたら、保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l16-en-title", "# 1.6 — Repetition with loops\n\nApply one operation to several values and safely update state such as a total, count, or maximum. Write code whose iteration count and stopping condition can be explained."),
            md("l16-en-for", "## for takes one value at a time from an iterable\n\nWrite `for temporary_name in values:` and Python runs the indented block once for each item. The loop variable represents the value currently being processed."),
            code("l16-en-for-code", "weekly_attendance = [28, 31, 32, 34]\nfor attendance in weekly_attendance:\n    print(\"Attendance this week:\", attendance)\nprint(\"Processing complete\")\n"),
            md("l16-en-range", "## range() expresses a count or integer interval\n\n`range(start, stop, step)` includes start and excludes stop. `range(1, 5)` produces 1, 2, 3, 4. State the expected iteration count to expose an off-by-one error."),
            code("l16-en-range-code", "print(list(range(1, 5)))\nfor week in range(1, 5):\n    print(f\"Week {week}\")\nprint(list(range(10, 4, -2)))\n"),
            md("l16-en-total", "## Initialise an accumulator before the loop and update it inside\n\nAn accumulator preserves a value across iterations. Start a total at zero and add the current item each time. Printing the running total makes the update order visible."),
            code("l16-en-total-code", "weekly_attendance = [28, 31, 32, 34]\ntotal = 0\nfor attendance in weekly_attendance:\n    total += attendance\n    print(\"Running total:\", total)\nprint(\"Total:\", total)\nprint(\"Mean:\", total / len(weekly_attendance))\n"),
            md("l16-en-count", "## Count items that satisfy a condition\n\nA counter also starts at zero and increases by one only when a condition is True. Keep a total and a count in separate variables so each role remains clear."),
            code("l16-en-count-code", "weekly_attendance = [28, 31, 32, 34]\nweeks_at_least_30 = 0\nfor attendance in weekly_attendance:\n    if attendance >= 30:\n        weeks_at_least_30 += 1\nprint(weeks_at_least_30)\n"),
            md("l16-en-maximum", "## Initialise a maximum from real data\n\nStarting a maximum at zero fails when every value is negative. If the data is known to be non-empty, start with its first item. If empty data is possible, handle that case explicitly."),
            code("l16-en-maximum-code", "weekly_change = [-4, -2, -7, -3]\nif weekly_change:\n    largest = weekly_change[0]\n    for change in weekly_change[1:]:\n        if change > largest:\n            largest = change\n    print(\"Largest:\", largest)\nelse:\n    print(\"No data\")\n"),
            md("l16-en-enumerate", "## enumerate() supplies a position and value together\n\nUse `enumerate()` instead of maintaining a manual counter when both a label and value are required. `start=1` produces reader-facing week numbers from one."),
            code("l16-en-enumerate-code", "weekly_attendance = [28, 31, 32, 34]\nfor week, attendance in enumerate(weekly_attendance, start=1):\n    print(f\"Week {week}: {attendance} learners\")\n"),
            md("l16-en-control", "## continue skips this iteration; break ends the loop\n\n`continue` skips the remaining statements for the current item and moves to the next. `break` ends the entire loop. Make the reason for skipping or stopping explicit."),
            code("l16-en-control-code", "readings = [28, None, 31, -1, 34]\nfor reading in readings:\n    if reading is None:\n        continue\n    if reading < 0:\n        print(\"Invalid value found; stopping\")\n        break\n    print(\"Valid value:\", reading)\n"),
            md("l16-en-while", "## while repeats while its condition remains True\n\nUse `while` when a stopping condition rather than a known collection controls repetition. Update the state used by the condition inside the loop; otherwise the loop may never end."),
            code("l16-en-while-code", "remaining = 3\nwhile remaining > 0:\n    print(\"Remaining:\", remaining)\n    remaining -= 1\nprint(\"Complete\")\n"),
            md("l16-en-choice", "## Choose for or while from the purpose\n\nUse for when there is an iterable of values; use while when work continues until a condition changes. A direct for loop usually avoids the boundary and update errors of indexing known data with while."),
            code("l16-en-choice-code", "weekly_attendance = [28, 31, 32, 34]\nfor attendance in weekly_attendance:\n    print(attendance)\n\n# The index-based while version needs explicit boundary and update logic.\nindex = 0\nwhile index < len(weekly_attendance):\n    print(weekly_attendance[index])\n    index += 1\n"),
            md("l16-en-transfer", "## Transfer exercise\n\nProcess four weekly material costs `[82.5, 74.0, 91.5, 80.0]` in one for loop. Calculate total, mean, maximum, and the number of weeks above 80. Display each week with `enumerate()`. For an empty list, avoid division and display `No data`. Make the condition and explanation agree about whether exactly 80 is counted."),
            code("l16-en-work", "# Write the transfer solution here.\n\n"),
            md("l16-en-complete", "## Completion check\n\nWhen you can explain for, the excluded range endpoint, accumulators, counters, maximum initialisation, enumerate, continue/break, a while stopping condition, and empty data, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "1.6", "language": language, "concepts": [f"R{i:02d}" for i in range(1, 11)], "revision": 17},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "04_loops_accumulators.ipynb", "ja": TEMPLATES / "ja/04_loops_accumulators.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "trace a for loop and identify its loop variable and indented body",
        "predict range values and prevent off by one errors",
        "initialise and update an accumulator across iterations",
        "count only items satisfying a condition",
        "initialise a maximum safely and handle empty data",
        "use enumerate when both a position and value are required",
        "distinguish continue from break and predict remaining work",
        "write a while loop whose condition state changes toward termination",
        "choose for or while from the kind of repetition",
        "combine totals counts maxima boundaries and empty-data handling in a practical loop",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "1.6 Repetition with loops",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"R{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L16R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/04_loops_accumulators.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/04_loops_accumulators.ipynb",
        },
        "implementation": "scripts/upgrade-python-loops-v17.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-1-6-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
