#!/usr/bin/env python3
"""Build the canonical and Japanese Lesson 2 notebooks and concept map."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def markdown(cell_id: str, source: str) -> dict:
    return {
        "cell_type": "markdown",
        "id": cell_id,
        "metadata": {},
        "source": source.splitlines(keepends=True),
    }


def code(cell_id: str, source: str) -> dict:
    return {
        "cell_type": "code",
        "execution_count": None,
        "id": cell_id,
        "metadata": {},
        "outputs": [],
        "source": source.splitlines(keepends=True),
    }


def notebook(language: str) -> dict:
    if language == "ja":
        cells = [
            markdown("l2-ja-title", "# レッスン1.2 — 変数・代入・プログラムの状態\n\nLesson 1では同じ値を複数の式へ直接書きました。このNotebookでは、一つの意味に一つの名前を付け、代入を右辺から追い、変更後の状態を自分で説明します。基本データ型は次のレッスンで体系的に学びます。"),
            markdown("l2-ja-one-source", "## 同じ意味を一か所で管理する\n\n次のセルを実行する前に、合計と未使用席を予想してください。その後、`morning`の代入だけを20へ変えて再実行します。"),
            code("l2-ja-one-source-code", "capacity = 40\nmorning = 18\nafternoon = 12\n\nprint(\"合計:\", morning + afternoon)\nprint(\"未使用席:\", capacity - (morning + afternoon))\n"),
            markdown("l2-ja-assignment", "## 代入と比較\n\n一つの`=`は右辺を評価して左の名前へ代入します。二つの`==`は値が等しいかを調べ、状態を変更しません。"),
            code("l2-ja-assignment-code", "registered = 40\nprint(\"登録者数:\", registered)\nprint(\"40と等しいか:\", registered == 40)\nprint(\"35と等しいか:\", registered == 35)\n"),
            markdown("l2-ja-update", "## 右辺を読んでから左辺を更新する\n\n`total = total + amount`では、右辺の現在値12と5を先に読み、17を作ってから左の`total`を更新します。`+=`は同じ更新の短縮形です。"),
            code("l2-ja-update-code", "total = 12\namount = 5\nprint(\"更新前:\", total)\ntotal = total + amount\nprint(\"長い形の後:\", total)\ntotal += amount\nprint(\"短い形の後:\", total)\n"),
            markdown("l2-ja-derived", "## 計算済みの値は自動更新されない\n\n`delivered`を計算した後で`cancelled`だけを変えても、`delivered`は自動では変わりません。二つの表示を予想してから実行してください。"),
            code("l2-ja-derived-code", "planned = 15\ncancelled = 2\ndelivered = planned - cancelled\n\ncancelled = 4\nprint(\"再計算前:\", delivered)\n\ndelivered = planned - cancelled\nprint(\"再計算後:\", delivered)\n"),
            markdown("l2-ja-names", "## 名前は規則を守り、意味を伝える\n\n`completed_learners`や`group2`は有効です。数字から始まる`2nd_group`、空白を含む名前、予約語`class`は使えません。大文字と小文字は区別されます。全大文字の`MAX_SEATS`は変更しない意図を示す慣習ですが、Pythonによる禁止ではありません。"),
            code("l2-ja-constant", "MAX_SEATS = 40\nprint(\"最初の値:\", MAX_SEATS)\nMAX_SEATS = 36  # 実行はできるが、定数として扱う慣習に反する\nprint(\"再代入後:\", MAX_SEATS)\n"),
            markdown("l2-ja-nameerror", "## NameErrorから名前と実行順を調べる\n\n下の例は、意図的につづりを一文字変えています。例外を捕捉してNotebook全体を停止させず、例外名とメッセージを表示します。"),
            code("l2-ja-nameerror-code", "completed_learners = 29\ntry:\n    print(completed_learner)\nexcept NameError as error:\n    print(type(error).__name__ + \":\", error)\n\nprint(\"正しい名前:\", completed_learners)\n"),
            markdown("l2-ja-kernel", "## Notebookの状態を再現する\n\n画面上に代入セルがあっても、実行していなければ名前は作られません。保存前にKernelを再起動し、全セルを上から実行します。これで、必要な代入が使用より前にあり、以前の実行状態へ依存していないことを確認できます。"),
            markdown("l2-ja-guided", "## 例題\n\n15回を計画し2回を中止した講座について、各行の後に三つの名前が指す値を予想します。`cancelled`を4へ変え、`delivered`の計算も再実行する必要がある理由を説明してください。"),
            code("l2-ja-guided-code", "planned = 15\ncancelled = 2\ndelivered = planned - cancelled\n\nprint(\"計画:\", planned)\nprint(\"中止:\", cancelled)\nprint(\"実施:\", delivered)\n"),
            markdown("l2-ja-transfer", "## 応用練習\n\n研修室の定員は24人、午前利用は18人、午後利用は20人です。それぞれに意味のある名前を付け、午前と午後の空席を表示してください。定員の代入一か所だけを22へ変え、Kernelを再起動して全セルを実行し、空席が4人と2人になることを確認します。最後に、各代入後の状態をMarkdownで説明します。"),
            code("l2-ja-work", "# ここに応用練習の解答を書きます。\n\n"),
            markdown("l2-ja-complete", "## 完了確認\n\n代入を右辺から追い、再代入後の状態、計算済み値を再計算する必要、`=`と`==`、`NameError`を説明できれば、保存してMoodleの理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            markdown("l2-en-title", "# Lesson 1.2 — Variables, assignment, and program state\n\nLesson 1 wrote the same value directly in several expressions. This Notebook gives one meaning one name, traces assignment from the right, and asks you to explain state after a change. Basic data types are developed systematically in the next lesson."),
            markdown("l2-en-one-source", "## Keep one source for one meaning\n\nPredict total and unused seats before running the cell. Then change only the assignment of `morning` to 20 and rerun."),
            code("l2-en-one-source-code", "capacity = 40\nmorning = 18\nafternoon = 12\n\nprint(\"Total:\", morning + afternoon)\nprint(\"Unused seats:\", capacity - (morning + afternoon))\n"),
            markdown("l2-en-assignment", "## Assignment and comparison\n\nOne `=` evaluates the right side and assigns the result to the name on the left. Two signs, `==`, compare values without changing state."),
            code("l2-en-assignment-code", "registered = 40\nprint(\"Registered:\", registered)\nprint(\"Equal to 40:\", registered == 40)\nprint(\"Equal to 35:\", registered == 35)\n"),
            markdown("l2-en-update", "## Read the right before updating the left\n\nIn `total = total + amount`, current values 12 and 5 are read first. Python makes 17, then updates `total`. `+=` is shorthand for the same state change."),
            code("l2-en-update-code", "total = 12\namount = 5\nprint(\"Before:\", total)\ntotal = total + amount\nprint(\"After long form:\", total)\ntotal += amount\nprint(\"After shorthand:\", total)\n"),
            markdown("l2-en-derived", "## A calculated value does not update itself\n\nChanging only `cancelled` after calculating `delivered` does not change `delivered` automatically. Predict both displayed values before running."),
            code("l2-en-derived-code", "planned = 15\ncancelled = 2\ndelivered = planned - cancelled\n\ncancelled = 4\nprint(\"Before recalculation:\", delivered)\n\ndelivered = planned - cancelled\nprint(\"After recalculation:\", delivered)\n"),
            markdown("l2-en-names", "## A name follows rules and communicates meaning\n\n`completed_learners` and `group2` are valid. `2nd_group`, names containing spaces, and the keyword `class` are invalid. Names are case-sensitive. All capitals in `MAX_SEATS` communicate an intention not to change the value, but Python does not enforce it."),
            code("l2-en-constant", "MAX_SEATS = 40\nprint(\"Initial value:\", MAX_SEATS)\nMAX_SEATS = 36  # This runs, although it conflicts with the constant convention.\nprint(\"After reassignment:\", MAX_SEATS)\n"),
            markdown("l2-en-nameerror", "## Use NameError to inspect names and order\n\nThe following example deliberately changes one character. It catches the exception so that the whole Notebook can continue, then displays the exception name and message."),
            code("l2-en-nameerror-code", "completed_learners = 29\ntry:\n    print(completed_learner)\nexcept NameError as error:\n    print(type(error).__name__ + \":\", error)\n\nprint(\"Correct name:\", completed_learners)\n"),
            markdown("l2-en-kernel", "## Make Notebook state reproducible\n\nA visible assignment cell has no effect until it runs. Before saving, restart the kernel and run every cell from the top. This confirms that required assignments precede their use and that the Notebook does not depend on earlier hidden state."),
            markdown("l2-en-guided", "## Guided example\n\nA course planned 15 sessions and cancelled 2. Predict what all three names refer to after each line. Change `cancelled` to 4 and explain why the `delivered` assignment must also run again."),
            code("l2-en-guided-code", "planned = 15\ncancelled = 2\ndelivered = planned - cancelled\n\nprint(\"Planned:\", planned)\nprint(\"Cancelled:\", cancelled)\nprint(\"Delivered:\", delivered)\n"),
            markdown("l2-en-transfer", "## Transfer exercise\n\nA training room has capacity 24; morning use is 18 and afternoon use is 20. Give each value a meaningful name and display unused places for both periods. Change the single capacity assignment to 22, restart the kernel, run all cells, and confirm that unused places become 4 and 2. Explain state after each assignment in Markdown."),
            code("l2-en-work", "# Write the transfer solution here.\n\n"),
            markdown("l2-en-complete", "## Completion check\n\nWhen you can trace assignment from the right and explain reassignment, derived-value recalculation, `=` versus `==`, and `NameError`, save the Notebook and continue to the Moodle learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {
                "display_name": "Python 3 (ipykernel)",
                "language": "python",
                "name": "python3",
            },
            "language_info": {"name": "python", "version": "3"},
            "pyai": {
                "lesson": "1.2",
                "language": language,
                "concepts": [f"V{i:02d}" for i in range(1, 11)],
                "revision": 13,
            },
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {
        "en": TEMPLATES / "02_variables_types_calculations.ipynb",
        "ja": TEMPLATES / "ja/02_variables_types_calculations.ipynb",
    }
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(
            json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n",
            encoding="utf-8",
        )
        print(f"wrote {target.relative_to(ROOT)}")

    concepts = []
    descriptions = [
        "assignment binds a name to an evaluated result",
        "assignment and equality comparison are different operations",
        "reassignment evaluates the right side before updating the left",
        "derived values do not update automatically",
        "derived assignments must rerun after an input change",
        "augmented assignment is an update shorthand",
        "identifiers follow syntax rules and communicate meaning",
        "NameError can reveal spelling and execution-order problems",
        "restart and run all exposes hidden Notebook state",
        "upper-case constants are a convention rather than enforcement",
    ]
    for number, description in enumerate(descriptions, start=1):
        concepts.append({
            "id": f"V{number:02d}",
            "description": description,
            "level": "M" if number != 10 else "I",
            "lesson": True,
            "notebook": True,
            "question": f"L2R-{number:02d}",
            "teacher": False,
        })
    concept_map = {
        "schema_version": 2,
        "lesson": "1.2 Variables, assignment, and program state",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": concepts,
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/02_variables_types_calculations.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/02_variables_types_calculations.ipynb",
        },
        "implementation": "scripts/upgrade-python-lesson2-v13.php",
    }
    map_path = ROOT / "sample-content/introduction-to-python/localization/lesson-1-2-concept-map-v2.json"
    map_path.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {map_path.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
