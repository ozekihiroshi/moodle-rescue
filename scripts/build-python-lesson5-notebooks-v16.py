#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for decisions with conditions."""

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
            md("l15-ja-title", "# 1.5 — 条件による判断\n\n比較式が作る真偽値を読み、業務ルールを順序のある分岐へ変換し、境界値で意図どおりに動くか確かめます。"),
            md("l15-ja-bool", "## 比較の結果はboolになる\n\n条件分岐の出発点は、式を `True` または `False` と判定することです。`=` は代入、`==` は等しいかの比較です。`!=`、`<`、`<=`、`>`、`>=` も実行して結果を確認します。"),
            code("l15-ja-bool-code", "score = 68\nprint(score >= 50)\nprint(score == 68)\nprint(score != 68)\nprint(type(score >= 50))\n"),
            md("l15-ja-if", "## if文はTrueのときだけ処理を実行する\n\n`if 条件式:` の末尾にはコロンが必要です。次の行を字下げすると、その処理がif文に属することを示します。条件がFalseなら、字下げされた処理を飛ばします。"),
            code("l15-ja-if-code", "score = 68\nif score >= 50:\n    print(\"合格\")\nprint(\"判定完了\")\n"),
            md("l15-ja-else", "## elseで二つの処理から一つを選ぶ\n\n`else` は直前の条件がFalseだった場合を受け持ちます。if側とelse側の両方が実行されることはありません。"),
            code("l15-ja-else-code", "score = 42\nif score >= 50:\n    result = \"合格\"\nelse:\n    result = \"要復習\"\nprint(result)\n"),
            md("l15-ja-chain", "## elifで複数の候補から最初の一つを選ぶ\n\nPythonは上から条件を調べ、最初にTrueになった分岐だけを実行します。範囲が重なるなら、より限定的な条件を先に置きます。"),
            code("l15-ja-chain-code", "score = 85\nif score >= 90:\n    grade = \"優秀\"\nelif score >= 70:\n    grade = \"合格\"\nelif score >= 50:\n    grade = \"条件付き合格\"\nelse:\n    grade = \"要復習\"\nprint(grade)\n"),
            md("l15-ja-independent", "## 独立したif文と一連の分岐を区別する\n\n独立した二つのif文は、両方の条件がTrueなら両方を実行します。`if / elif / else` は候補から一つだけ選びます。目的に応じて構造を選びます。"),
            code("l15-ja-independent-code", "score = 80\nif score >= 50:\n    print(\"合格\")\nif score >= 70:\n    print(\"上位成績\")\n\nif score >= 70:\n    label = \"上位成績\"\nelif score >= 50:\n    label = \"合格\"\nelse:\n    label = \"要復習\"\nprint(\"分類:\", label)\n"),
            md("l15-ja-logic", "## and・or・notで判定を組み合わせる\n\n`and` は両方、`or` は少なくとも一方がTrueのときTrueになります。`not` は真偽を反転します。長い式では括弧と説明的な中間変数を使い、各部分を個別に確認します。"),
            code("l15-ja-logic-code", "registered = 35\ncompletion_rate = 72\nhas_enough_learners = registered >= 30\nrate_needs_support = completion_rate < 75\npriority = has_enough_learners and rate_needs_support\nprint(has_enough_learners, rate_needs_support, priority)\nprint(not priority)\n"),
            md("l15-ja-boundary", "## 境界値は直前・一致・直後を試す\n\n`>= 50` は50を含み、`> 50` は含みません。複数の範囲では、各しきい値の直前、一致、直後を試します。Pythonでは `0 <= score <= 100` のような連鎖比較も書けます。"),
            code("l15-ja-boundary-code", "def support_status(rate):\n    if rate < 75:\n        return \"重点支援\"\n    elif rate < 85:\n        return \"経過観察\"\n    else:\n        return \"順調\"\n\nfor rate in [74.9, 75, 84.9, 85]:\n    print(rate, support_status(rate))\n\nscore = 100\nprint(0 <= score <= 100)\n"),
            md("l15-ja-validation", "## 分類より先に入力値の妥当性を確認する\n\n無効な値を通常の区分へ入れると、動作していても誤った結果になります。まず扱える範囲を確認し、その後で分類します。"),
            code("l15-ja-validation-code", "score = -5\nif not 0 <= score <= 100:\n    result = \"無効な点数\"\nelif score >= 50:\n    result = \"合格\"\nelse:\n    result = \"要復習\"\nprint(result)\n"),
            md("l15-ja-short", "## 短絡評価は不要な式を評価しない\n\n`and` は左側がFalseなら右側を調べず、`or` は左側がTrueなら右側を調べません。この性質は安全確認に使えますが、読みにくい副作用を条件式へ入れないようにします。"),
            code("l15-ja-short-code", "registered = 0\ncompleted = 0\nif registered > 0 and completed / registered >= 0.75:\n    status = \"順調\"\nelse:\n    status = \"確認が必要\"\nprint(status)\n"),
            md("l15-ja-transfer", "## 応用練習\n\n学習センターの登録者数、修了者数、欠席報告の有無から支援状態を判定します。人数が負、または修了者数が登録者数を超える場合は「データ確認」。欠席報告がある、または修了率が75%未満なら「重点支援」。修了率が85%未満なら「経過観察」。それ以外は「順調」としてください。無効値、74.9%、75%、84.9%、85%を含む例で試します。"),
            code("l15-ja-work", "# ここに応用練習の解答を書きます。\n\n"),
            md("l15-ja-complete", "## 完了確認\n\n比較演算子、bool、字下げ、if/elif/elseの順序、独立したif、and/or/not、境界値、妥当性確認、短絡評価を自分の言葉で説明できたら、保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l15-en-title", "# 1.5 — Decisions with conditions\n\nRead Boolean results, translate operational rules into ordered branches, and test boundaries to confirm that the code implements the intended decision."),
            md("l15-en-bool", "## Comparisons produce bool values\n\nA decision begins by evaluating an expression as `True` or `False`. `=` assigns a value; `==` compares for equality. Run `!=`, `<`, `<=`, `>`, and `>=` and inspect their results."),
            code("l15-en-bool-code", "score = 68\nprint(score >= 50)\nprint(score == 68)\nprint(score != 68)\nprint(type(score >= 50))\n"),
            md("l15-en-if", "## if runs a block only when its condition is True\n\nA colon ends `if condition:`. Indentation shows which statements belong to the branch. When the condition is False, Python skips the indented block."),
            code("l15-en-if-code", "score = 68\nif score >= 50:\n    print(\"Pass\")\nprint(\"Decision complete\")\n"),
            md("l15-en-else", "## else selects one of two actions\n\n`else` handles the case in which the preceding condition is False. The if and else blocks cannot both run in the same decision."),
            code("l15-en-else-code", "score = 42\nif score >= 50:\n    result = \"Pass\"\nelse:\n    result = \"Review\"\nprint(result)\n"),
            md("l15-en-chain", "## elif selects the first matching branch\n\nPython checks conditions from the top and runs only the first True branch. When ranges overlap, put the more selective condition first."),
            code("l15-en-chain-code", "score = 85\nif score >= 90:\n    grade = \"Excellent\"\nelif score >= 70:\n    grade = \"Pass\"\nelif score >= 50:\n    grade = \"Conditional pass\"\nelse:\n    grade = \"Review\"\nprint(grade)\n"),
            md("l15-en-independent", "## Distinguish independent if statements from one chain\n\nTwo independent if statements can both run when both conditions are True. An `if / elif / else` chain chooses one candidate. Select the structure that matches the rule."),
            code("l15-en-independent-code", "score = 80\nif score >= 50:\n    print(\"Pass\")\nif score >= 70:\n    print(\"Merit\")\n\nif score >= 70:\n    label = \"Merit\"\nelif score >= 50:\n    label = \"Pass\"\nelse:\n    label = \"Review\"\nprint(\"Classification:\", label)\n"),
            md("l15-en-logic", "## Combine decisions with and, or, and not\n\n`and` requires both operands; `or` requires at least one; `not` reverses a Boolean value. For a long expression, use parentheses and meaningful intermediate names, then inspect each part separately."),
            code("l15-en-logic-code", "registered = 35\ncompletion_rate = 72\nhas_enough_learners = registered >= 30\nrate_needs_support = completion_rate < 75\npriority = has_enough_learners and rate_needs_support\nprint(has_enough_learners, rate_needs_support, priority)\nprint(not priority)\n"),
            md("l15-en-boundary", "## Test immediately below, at, and above each boundary\n\n`>= 50` includes 50; `> 50` does not. For several ranges, test immediately below, exactly at, and immediately above every threshold. Python also supports a chained comparison such as `0 <= score <= 100`."),
            code("l15-en-boundary-code", "def support_status(rate):\n    if rate < 75:\n        return \"priority support\"\n    elif rate < 85:\n        return \"monitor\"\n    else:\n        return \"on track\"\n\nfor rate in [74.9, 75, 84.9, 85]:\n    print(rate, support_status(rate))\n\nscore = 100\nprint(0 <= score <= 100)\n"),
            md("l15-en-validation", "## Validate a value before classifying it\n\nAn invalid value placed in an ordinary category produces a result that runs but is wrong. Check the permitted domain first, then apply the normal classification rules."),
            code("l15-en-validation-code", "score = -5\nif not 0 <= score <= 100:\n    result = \"Invalid score\"\nelif score >= 50:\n    result = \"Pass\"\nelse:\n    result = \"Review\"\nprint(result)\n"),
            md("l15-en-short", "## Short-circuit evaluation avoids an unnecessary expression\n\n`and` does not evaluate its right side when the left side is False; `or` does not evaluate its right side when the left side is True. This can support a safety check, but avoid hiding side effects inside conditions."),
            code("l15-en-short-code", "registered = 0\ncompleted = 0\nif registered > 0 and completed / registered >= 0.75:\n    status = \"on track\"\nelse:\n    status = \"needs review\"\nprint(status)\n"),
            md("l15-en-transfer", "## Transfer exercise\n\nClassify a learning centre from registered learners, completed learners, and whether an absence report exists. If a count is negative or completions exceed registrations, return `data review`. If an absence report exists or completion is below 75%, return `priority support`. Below 85%, return `monitor`; otherwise return `on track`. Test invalid data and rates 74.9%, 75%, 84.9%, and 85%."),
            code("l15-en-work", "# Write the transfer solution here.\n\n"),
            md("l15-en-complete", "## Completion check\n\nWhen you can explain comparisons, bool, indentation, ordered branches, independent if statements, and/or/not, boundaries, validation, and short-circuit evaluation, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "1.5", "language": language, "concepts": [f"C{i:02d}" for i in range(1, 11)], "revision": 16},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "03_conditions_boundaries.ipynb", "ja": TEMPLATES / "ja/03_conditions_boundaries.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "distinguish assignment from comparisons and identify bool results",
        "write an if statement with a colon and meaningful indentation",
        "select between two actions with if and else",
        "trace an ordered if elif else chain and run only its first true branch",
        "distinguish independent if statements from an exclusive chain",
        "combine and explain conditions with and or and not",
        "test inclusive and exclusive boundaries and chained comparisons",
        "validate the permitted domain before ordinary classification",
        "explain short-circuit evaluation and use it for a safe guard",
        "translate a practical support rule into ordered tested branches",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "1.5 Decisions with conditions",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"C{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L15R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/03_conditions_boundaries.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/03_conditions_boundaries.ipynb",
        },
        "implementation": "scripts/upgrade-python-conditions-v16.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-1-5-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
