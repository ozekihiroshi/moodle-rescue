#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for the Chapter 2 applied project."""

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
            md("p23-ja-title", "# 2.3 実践プロジェクト — 学習センター月次実績報告\n\n2.1のデータ構造と2.2の関数・検証・テストを組み合わせ、複数センターの月次実績を確認できる小さな業務プログラムを完成させます。"),
            md("p23-ja-scenario", "## 業務場面\n\n地域の学習センターから月末に、登録者数、出席者数、修了者数、教材費が届きます。運営担当者は、入力の誤りを通常の実績に混ぜず、各センターの率と全体集計を確認し、支援が必要なセンターを特定する必要があります。"),
            md("p23-ja-outcome", "## 完成時にできること\n\nプログラムは、(1) レコードを検証し、(2) 有効なセンターの指標を計算し、(3) 無効なレコードと理由を分離し、(4) センター別と全体の月次報告を表示します。入力データ、業務規則、表示処理を分けてください。"),
            md("p23-ja-rules", "## 業務規則\n\n必須項目は `name`、`district`、`registered`、`attended`、`completed`、`material_cost` です。人数と費用は0以上、`completed <= attended <= registered`でなければなりません。登録者数0では率を`None`、修了者数0では一人当たり教材費を`None`とします。出席率75%未満または修了率70%未満を「要支援」とします。"),
            code("p23-ja-data", "centres = [\n    {\"name\": \"North\", \"district\": \"A\", \"registered\": 40, \"attended\": 34, \"completed\": 30, \"material_cost\": 450.0},\n    {\"name\": \"South\", \"district\": \"B\", \"registered\": 35, \"attended\": 25, \"completed\": 22, \"material_cost\": 390.0},\n    {\"name\": \"Central\", \"district\": \"A\", \"registered\": 28, \"attended\": 24, \"completed\": 21, \"material_cost\": 315.0},\n]\n\nrequired_fields = {\"name\", \"district\", \"registered\", \"attended\", \"completed\", \"material_cost\"}\n"),
            md("p23-ja-functions", "## 必須の関数\n\n`validate_centre(centre)`は必須キーと値の関係を確認し、無効なら`KeyError`または`ValueError`を送出します。`safe_percentage(part, whole)`と`safe_unit_cost(cost, completed)`は分母0で`None`を返します。`centre_metrics(centre)`は一件の指標、`summarise_centres(centres)`は有効データの全体集計と要支援一覧を辞書で返します。"),
            code("p23-ja-functions-code", "def validate_centre(centre):\n    # TODO: 必須キー、非負、completed <= attended <= registeredを確認する\n    pass\n\ndef safe_percentage(part, whole):\n    # TODO: wholeが0ならNone、それ以外は百分率を返す\n    pass\n\ndef safe_unit_cost(cost, completed):\n    # TODO: completedが0ならNone、それ以外は一人当たり費用を返す\n    pass\n\ndef centre_metrics(centre):\n    # TODO: 検証後、元の項目と三つの指標を持つ新しい辞書を返す\n    pass\n\ndef summarise_centres(centres):\n    # TODO: 合計、全体率、地区集合、要支援名、無効レコードと理由を返す\n    pass\n"),
            md("p23-ja-pipeline", "## 処理の流れ\n\n一件ずつ検証し、例外を捕捉したレコードは`invalid_records`へ理由とともに追加します。有効なレコードだけを集計へ渡します。入力リストを直接書き換えず、計算結果を含む新しい辞書を作ると、元データとの比較が容易です。"),
            code("p23-ja-pipeline-code", "valid_metrics = []\ninvalid_records = []\n\nfor centre in centres:\n    try:\n        metrics = centre_metrics(centre)\n    except (KeyError, ValueError) as error:\n        invalid_records.append({\"record\": centre, \"reason\": str(error)})\n    else:\n        valid_metrics.append(metrics)\n\n# TODO: summarise_centres()を呼び出し、結果を表示する\n"),
            md("p23-ja-output", "## 報告に含める内容\n\nセンター別に登録・出席・修了、出席率、修了率、修了者一人当たり教材費、要支援判定を表示します。全体では登録・出席・修了の合計、合計値から計算した全体率、教材費合計、地区の集合、要支援センター名、無効件数と理由を表示します。率の単純平均を全体率としてはいけません。"),
            code("p23-ja-output-code", "def display_optional(value, decimals=1):\n    if value is None:\n        return \"N/A\"\n    return f\"{value:.{decimals}f}\"\n\n# TODO: 有効なセンター別結果と全体集計を読みやすく表示する\n"),
            md("p23-ja-tests", "## 最低限のテスト\n\n正常な3件に加え、(1) 登録者数0・各人数0、(2) 必須キー欠落、(3) 負数、(4) 出席者数が登録者数を超える、(5) 修了者数が出席者数を超える、(6) 75%と70%のしきい値直前・一致・直後を確認します。浮動小数点数は許容誤差で比較します。"),
            code("p23-ja-tests-code", "zero_centre = {\"name\": \"Zero\", \"district\": \"C\", \"registered\": 0, \"attended\": 0, \"completed\": 0, \"material_cost\": 0.0}\nmissing_key = {\"name\": \"Missing\", \"district\": \"C\", \"registered\": 10, \"attended\": 8, \"material_cost\": 100.0}\nimpossible = {\"name\": \"Impossible\", \"district\": \"C\", \"registered\": 10, \"attended\": 11, \"completed\": 9, \"material_cost\": 100.0}\n\n# 実装後にコメントを外して確認します。\n# assert abs(safe_percentage(30, 40) - 75.0) < 0.0001\n# assert safe_percentage(0, 0) is None\n# validate_centre(zero_centre)\n# missing_keyとimpossibleが想定した例外になることも確認する\n"),
            md("p23-ja-explanation", "## 提出時の短い説明\n\n300～500字を目安に、データ構造を選んだ理由、関数の分け方、無効データを通常集計から除いた方法、最も重要だった境界テスト、報告から読み取れる運営上の事実を説明します。コードに書いていない結果を主張しないでください。"),
            md("p23-ja-check", "## 提出前確認\n\nNotebookを上から順に再実行し、エラーなく同じ結果になること、未完成の`pass`やTODOが残っていないこと、入力データを直接書き換えていないこと、無効データの理由が表示されること、提出ファイルをPython Labへ保存したことを確認します。"),
        ]
    else:
        cells = [
            md("p23-en-title", "# 2.3 Applied project — Monthly learning-centre performance report\n\nCombine the data structures from 2.1 with the functions, validation, and tests from 2.2 to complete a small operational program."),
            md("p23-en-scenario", "## Operational scenario\n\nAt month end, learning centres send registration, attendance, completion, and material-cost figures. An operations officer must keep invalid input out of ordinary results, review centre and overall metrics, and identify centres that need support."),
            md("p23-en-outcome", "## Required outcome\n\nThe program (1) validates records, (2) calculates metrics for valid centres, (3) separates invalid records with reasons, and (4) displays centre-level and overall monthly results. Keep input data, business rules, and presentation separate."),
            md("p23-en-rules", "## Business rules\n\nRequired fields are `name`, `district`, `registered`, `attended`, `completed`, and `material_cost`. Counts and cost are non-negative, and `completed <= attended <= registered`. A zero registration gives a `None` rate; zero completion gives a `None` unit cost. Flag support when attendance is below 75% or completion is below 70%."),
            code("p23-en-data", "centres = [\n    {\"name\": \"North\", \"district\": \"A\", \"registered\": 40, \"attended\": 34, \"completed\": 30, \"material_cost\": 450.0},\n    {\"name\": \"South\", \"district\": \"B\", \"registered\": 35, \"attended\": 25, \"completed\": 22, \"material_cost\": 390.0},\n    {\"name\": \"Central\", \"district\": \"A\", \"registered\": 28, \"attended\": 24, \"completed\": 21, \"material_cost\": 315.0},\n]\n\nrequired_fields = {\"name\", \"district\", \"registered\", \"attended\", \"completed\", \"material_cost\"}\n"),
            md("p23-en-functions", "## Required functions\n\n`validate_centre(centre)` checks required keys and value relationships, raising `KeyError` or `ValueError`. `safe_percentage(part, whole)` and `safe_unit_cost(cost, completed)` return `None` for a zero denominator. `centre_metrics(centre)` returns metrics for one record. `summarise_centres(centres)` returns overall valid-data totals and the support list as a dictionary."),
            code("p23-en-functions-code", "def validate_centre(centre):\n    # TODO: check required keys, non-negative values, and completed <= attended <= registered\n    pass\n\ndef safe_percentage(part, whole):\n    # TODO: return None for zero whole, otherwise return percentage\n    pass\n\ndef safe_unit_cost(cost, completed):\n    # TODO: return None for zero completed, otherwise return unit cost\n    pass\n\ndef centre_metrics(centre):\n    # TODO: validate, then return a new dictionary with original fields and three metrics\n    pass\n\ndef summarise_centres(centres):\n    # TODO: return totals, overall rates, district set, support names, and invalid records with reasons\n    pass\n"),
            md("p23-en-pipeline", "## Processing pipeline\n\nValidate one record at a time. Add a caught invalid record and its reason to `invalid_records`; pass only valid records to aggregation. Build a new dictionary for calculated results instead of mutating the input so that the source remains available for comparison."),
            code("p23-en-pipeline-code", "valid_metrics = []\ninvalid_records = []\n\nfor centre in centres:\n    try:\n        metrics = centre_metrics(centre)\n    except (KeyError, ValueError) as error:\n        invalid_records.append({\"record\": centre, \"reason\": str(error)})\n    else:\n        valid_metrics.append(metrics)\n\n# TODO: call summarise_centres() and display the returned report\n"),
            md("p23-en-output", "## Report contents\n\nFor each centre display registration, attendance, completion, attendance rate, completion rate, material cost per completion, and support status. Overall, display count totals, rates calculated from those totals, total material cost, the district set, support-centre names, and invalid record count and reasons. Do not use an unweighted average of centre percentages as the overall rate."),
            code("p23-en-output-code", "def display_optional(value, decimals=1):\n    if value is None:\n        return \"N/A\"\n    return f\"{value:.{decimals}f}\"\n\n# TODO: display valid centre metrics and overall summary readably\n"),
            md("p23-en-tests", "## Minimum test set\n\nIn addition to three normal records, test (1) zero registration with zero counts, (2) a missing required key, (3) a negative value, (4) attendance above registration, (5) completion above attendance, and (6) values immediately below, equal to, and above the 75% and 70% thresholds. Compare floats using a tolerance."),
            code("p23-en-tests-code", "zero_centre = {\"name\": \"Zero\", \"district\": \"C\", \"registered\": 0, \"attended\": 0, \"completed\": 0, \"material_cost\": 0.0}\nmissing_key = {\"name\": \"Missing\", \"district\": \"C\", \"registered\": 10, \"attended\": 8, \"material_cost\": 100.0}\nimpossible = {\"name\": \"Impossible\", \"district\": \"C\", \"registered\": 10, \"attended\": 11, \"completed\": 9, \"material_cost\": 100.0}\n\n# Uncomment after implementation.\n# assert abs(safe_percentage(30, 40) - 75.0) < 0.0001\n# assert safe_percentage(0, 0) is None\n# validate_centre(zero_centre)\n# Also confirm the expected exceptions for missing_key and impossible.\n"),
            md("p23-en-explanation", "## Short submission explanation\n\nIn 150–250 words, explain why you selected the structures, how you divided the functions, how invalid data is excluded from ordinary totals, the most important boundary test, and one operational fact supported by the report. Do not claim a result that the code did not calculate."),
            md("p23-en-check", "## Pre-submission check\n\nRestart and run the Notebook from top to bottom. Confirm that it produces the same result without errors, no unfinished `pass` or TODO remains, source records are not mutated, invalid reasons are visible, and the final file is saved in Python Lab."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "2.3", "language": language, "concepts": [f"P23-{i:02d}" for i in range(1, 11)], "revision": 21},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "P2_monthly_centre_report.ipynb", "ja": TEMPLATES / "ja/P2_monthly_centre_report.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "represent at least three centre records with the six required fields",
        "state and implement non-negative and count-order business rules",
        "separate validation safe calculations record metrics and aggregation",
        "raise specific exceptions and retain invalid records with reasons",
        "handle zero denominators explicitly with None",
        "calculate centre metrics and support thresholds without mutating input",
        "calculate overall rates from totals rather than averaging percentages",
        "test normal zero missing negative impossible and threshold cases",
        "produce a readable centre-level and overall operational report",
        "submit a reproducible notebook and evidence-based explanation",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "2.3 Applied project: Monthly centre performance report",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"P23-{i:02d}", "description": description, "assignment": True, "notebook": True, "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/P2_monthly_centre_report.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/P2_monthly_centre_report.ipynb",
        },
        "implementation": "scripts/upgrade-python-project23-v21.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/project-2-3-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
