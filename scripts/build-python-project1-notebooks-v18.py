#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for Chapter 1 applied project."""

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
            md("p1-ja-title", "# 1.7 実践プロジェクト — 週間サポート報告\n\n1.1〜1.6で学んだ値、変数、数値計算、文字列、条件分岐、ループを一つの実務的な報告へ統合します。関数、辞書、pandasはまだ使いません。"),
            md("p1-ja-brief", "## 課題\n\n一つの学習センターについて、各週の出席者数を検証・集計し、月間出席率と支援区分を計算して、担当者が読める週間サポート報告を作成します。"),
            md("p1-ja-rules", "## 業務ルール\n\n- 毎週の登録者数は同じ値を使います。\n- 週次出席者数が負、または登録者数を超えた場合はデータを無効とします。\n- 月間出席率が75%未満なら「重点支援」、85%未満なら「経過観察」、それ以外は「順調」です。\n- 週の出席率が75%未満だった週も数えます。\n- 空の週次データや登録者数0では割り算を行いません。"),
            md("p1-ja-input", "## 1. 入力値を確認する\n\n最初は次の値を使います。名前と値を変えても同じプログラムが動くようにしてください。"),
            code("p1-ja-input-code", "centre_name = \"North Learning Centre\"\nregistered_per_week = 40\nweekly_attendance = [28, 31, 30, 33]\n\nprint(centre_name)\nprint(registered_per_week)\nprint(weekly_attendance)\n"),
            md("p1-ja-validation", "## 2. 週次データを検証する\n\n`valid_data = True`から始めます。登録者数と空データを先に確認し、ループで各週の出席者数を確認します。無効値が見つかったらFalseへ変更します。"),
            code("p1-ja-validation-code", "valid_data = True\n\n# registered_per_week <= 0 または週次データが空ならFalseにします。\n# for文で負の値、登録者数を超える値を探します。\n\nprint(\"データは有効か:\", valid_data)\n"),
            md("p1-ja-aggregate", "## 3. 有効なデータを一回のループで集計する\n\n合計出席者数、処理週数、重点支援週数を0から始めます。`enumerate(..., start=1)`で週番号と出席者数を取り出し、各週の出席率を表示しながら状態を更新します。"),
            code("p1-ja-aggregate-code", "total_attended = 0\nweeks_processed = 0\npriority_weeks = 0\n\n# valid_dataがTrueの場合だけ集計します。\n# 各週についてtotal_attendedとweeks_processedを更新します。\n# 週の出席率が75%未満ならpriority_weeksを1増やします。\n\nprint(total_attended, weeks_processed, priority_weeks)\n"),
            md("p1-ja-classify", "## 4. 月間出席率と支援区分を決める\n\n月間登録者数は`registered_per_week * weeks_processed`です。合計出席者数を月間登録者数で割り、75%と85%の境界で区分します。"),
            code("p1-ja-classify-code", "# valid_dataがTrueでweeks_processed > 0の場合だけ計算します。\n# monthly_registered、attendance_rate、statusを作ります。\n\n"),
            md("p1-ja-output", "## 5. 読み手に意味が伝わる報告を表示する\n\nf文字列を使い、センター名、処理週数、登録者延べ数、出席者延べ数、出席率、重点支援週数、支援区分、短い推奨対応を表示します。出席率は小数第1位にします。"),
            code("p1-ja-output-code", "# 例: North Learning Centre: 122/160人、出席率 76.2%\n# 支援区分と、区分に対応する推奨対応も表示します。\n\n"),
            md("p1-ja-tests", "## 6. 境界値と無効値をテストする\n\n登録者数を20として、次のデータを一つずつ入力セルへ入れ、同じ処理を再実行します。\n\n| 週次出席者数 | 期待する結果 |\n|---|---|\n| `[15, 15, 15, 14]` | 73.8%、重点支援 |\n| `[15, 15, 15, 15]` | 75.0%、経過観察 |\n| `[17, 17, 17, 16]` | 83.8%、経過観察 |\n| `[17, 17, 17, 17]` | 85.0%、順調 |\n| `[18, 21, 17, 16]` | 登録者数を超える値があるため無効 |\n| `[]` | データなし。割り算をしない |"),
            code("p1-ja-tests-code", "# テスト結果を、入力・期待値・実際の結果の形で記録します。\n\n"),
            md("p1-ja-explain", "## 7. 説明を書く\n\nNotebookの最後に、判定結果、重点支援週が何を意味するか、境界値テストで確認したことを100〜150字程度で書きます。コードが動いたことだけでなく、結果が業務ルールと一致する根拠を説明します。"),
            md("p1-ja-submit", "## 提出前確認\n\n- すべてのコードセルを上から実行してエラーがない。\n- 無効値で通常の支援区分を表示しない。\n- 75%と85%ちょうどの結果が課題文と一致する。\n- 出力に名称、単位、割合、支援区分、推奨対応がある。\n- Notebookを保存し、この`.ipynb`ファイルをMoodleへ提出する。"),
        ]
    else:
        cells = [
            md("p1-en-title", "# 1.7 Applied project — Weekly support report\n\nIntegrate values, variables, arithmetic, strings, decisions, and loops from 1.1–1.6 into one operational report. Do not use functions, dictionaries, or pandas yet."),
            md("p1-en-brief", "## Brief\n\nFor one learning centre, validate and aggregate weekly attendance, calculate a monthly attendance rate and support category, and produce a weekly support report another staff member can read."),
            md("p1-en-rules", "## Operational rules\n\n- Use one registration count for every week.\n- A negative attendance or attendance above registration makes the data invalid.\n- A monthly rate below 75% is `priority support`; below 85% is `monitor`; otherwise it is `on track`.\n- Count weeks whose weekly rate is below 75%.\n- Do not divide when weekly data is empty or registration is zero."),
            md("p1-en-input", "## 1. Inspect the inputs\n\nBegin with these values. The same program must work when the name and values change."),
            code("p1-en-input-code", "centre_name = \"North Learning Centre\"\nregistered_per_week = 40\nweekly_attendance = [28, 31, 30, 33]\n\nprint(centre_name)\nprint(registered_per_week)\nprint(weekly_attendance)\n"),
            md("p1-en-validation", "## 2. Validate weekly data\n\nStart with `valid_data = True`. Check registration and empty data first, then use a loop to inspect every weekly attendance value. Change the flag to False when an invalid value is found."),
            code("p1-en-validation-code", "valid_data = True\n\n# Set False when registered_per_week <= 0 or weekly data is empty.\n# Use for to find a negative value or one above registration.\n\nprint(\"Data valid:\", valid_data)\n"),
            md("p1-en-aggregate", "## 3. Aggregate valid data in one loop\n\nStart total attendance, weeks processed, and priority weeks at zero. Use `enumerate(..., start=1)` to obtain a week number and attendance. Display each weekly rate while updating the three states."),
            code("p1-en-aggregate-code", "total_attended = 0\nweeks_processed = 0\npriority_weeks = 0\n\n# Aggregate only when valid_data is True.\n# Update total_attended and weeks_processed for each week.\n# Increase priority_weeks when a weekly rate is below 75%.\n\nprint(total_attended, weeks_processed, priority_weeks)\n"),
            md("p1-en-classify", "## 4. Calculate the monthly rate and support category\n\nMonthly registrations equal `registered_per_week * weeks_processed`. Divide total attendance by that value, then classify at the 75% and 85% boundaries."),
            code("p1-en-classify-code", "# Calculate only when valid_data is True and weeks_processed > 0.\n# Create monthly_registered, attendance_rate, and status.\n\n"),
            md("p1-en-output", "## 5. Display a report whose meaning is clear\n\nUse f-strings to display centre name, weeks processed, total registrations, total attendance, rate, priority-week count, support category, and one short recommended action. Display the rate to one decimal place."),
            code("p1-en-output-code", "# Example: North Learning Centre: 122/160 learners, attendance 76.2%\n# Also display the support category and a matching recommended action.\n\n"),
            md("p1-en-tests", "## 6. Test boundaries and invalid values\n\nSet registration to 20 and run the same processing for each case.\n\n| Weekly attendance | Expected result |\n|---|---|\n| `[15, 15, 15, 14]` | 73.8%, priority support |\n| `[15, 15, 15, 15]` | 75.0%, monitor |\n| `[17, 17, 17, 16]` | 83.8%, monitor |\n| `[17, 17, 17, 17]` | 85.0%, on track |\n| `[18, 21, 17, 16]` | invalid because one value exceeds registration |\n| `[]` | no data and no division |"),
            code("p1-en-tests-code", "# Record input, expected result, and actual result for every test.\n\n"),
            md("p1-en-explain", "## 7. Explain the result\n\nEnd the Notebook with about 80–120 words explaining the category, what the priority-week count adds to the monthly result, and what the boundary tests confirmed. Explain why the output matches the operational rules, not only that the code ran."),
            md("p1-en-submit", "## Submission check\n\n- Run all code cells from the top without an error.\n- Do not show an ordinary support category for invalid data.\n- Confirm that exactly 75% and 85% match the brief.\n- Include names, units, percentage, support category, and recommendation in output.\n- Save the Notebook and submit this `.ipynb` file in Moodle."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "1.7", "language": language, "concepts": [f"P{i:02d}" for i in range(1, 11)], "revision": 18},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "P1_weekly_support_report.ipynb", "ja": TEMPLATES / "ja/P1_weekly_support_report.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "use meaningful scalar and string inputs from Lessons 1.1 to 1.4",
        "validate registration empty data and each weekly value before calculation",
        "use one loop to update total attendance processed weeks and priority weeks",
        "calculate a combined monthly denominator and attendance rate",
        "classify the rate with the ordered boundaries from Lesson 1.5",
        "produce a formatted operational report and recommendation",
        "test immediately below and exactly at both internal boundaries",
        "test an impossible weekly value without ordinary classification",
        "handle empty weekly data without division",
        "explain how the evidence supports the category and recommendation",
    ]
    rubric = [
        {"id": "P01", "points": 10, "criterion": "clear inputs and meaningful names"},
        {"id": "P02", "points": 15, "criterion": "complete validation before calculation"},
        {"id": "P03", "points": 20, "criterion": "correct loop state and weekly output"},
        {"id": "P04", "points": 10, "criterion": "correct combined totals and rate"},
        {"id": "P05", "points": 15, "criterion": "correct ordered support boundaries"},
        {"id": "P06", "points": 10, "criterion": "readable formatted report and recommendation"},
        {"id": "P07", "points": 5, "criterion": "tests below and at 75 and 85 percent"},
        {"id": "P08", "points": 5, "criterion": "invalid value test"},
        {"id": "P09", "points": 5, "criterion": "empty-data test"},
        {"id": "P10", "points": 5, "criterion": "evidence-based explanation"},
    ]
    concept_map = {
        "schema_version": 1,
        "project": "1.7 Weekly support report",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"P{i:02d}", "description": description, "source_lessons": ["1.1", "1.2", "1.3", "1.4", "1.5", "1.6"], "notebook": True, "assignment": True, "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "rubric": rubric,
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/P1_weekly_support_report.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/P1_weekly_support_report.ipynb",
        },
        "implementation": "scripts/upgrade-python-project1-v18.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/project-1-7-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
