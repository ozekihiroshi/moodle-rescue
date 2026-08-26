#!/usr/bin/env python3
"""Build Project 1.7 as a script task with a black-box acceptance checker."""
from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LAB = ROOT / "sample-content/introduction-to-python/python-lab"
TEMPLATES = LAB / "templates"
PROJECT_FILES = LAB / "project-files"


def md(cell_id: str, text: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code(cell_id: str, text: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


def notebook(language: str) -> dict:
    ja = language == "ja"
    folder = "projects/weekly-support"
    if ja:
        cells = [
            md("p1-title", "# 1.7 小プロジェクト — 週間サポート報告\n\nここまでに学んだ変数、数値、文字列入出力、型変換、条件分岐、ループだけを使い、実際に動くPythonスクリプトを完成させます。関数、リスト、辞書、ファイル入出力は使いません。"),
            md("p1-problem", "## 解決する課題\n\n学習センターでは、月曜日から金曜日までの問い合わせ件数と解決件数を週末に手作業で集計しています。計算間違いや判定の不一致を防ぐため、10個の整数を入力すると週間報告を表示するプログラムを作成します。成果物はNotebookではなく、`weekly_support.py`です。"),
            md("p1-contract", "## 自動確認のために守る約束\n\n- ファイル名を`weekly_support.py`から変更しない。\n- 月曜日から金曜日の順で、各日の問い合わせ件数、解決件数を`input()`で受け取る。\n- 入力は`int()`で整数へ変換する。\n- 最終出力の項目名を、課題に示した英大文字から変更しない。\n- 確認用の`check_weekly_support.py`は変更しない。\n\nこの約束は、確認プログラムが異なる入力を自動的に渡し、結果を読み取るために必要です。確認プログラムの中身を理解する必要はありません。"),
            md("p1-rules", "## 業務ルール\n\n1. 5日分を一つの`for`ループで処理します。\n2. 問い合わせ件数または解決件数が負、または解決件数が問い合わせ件数を超えた場合は`RESULT: INVALID`だけを結果として表示します。\n3. 有効な場合は、週間の問い合わせ合計、解決合計、未解決件数、解決率、状態、最繁忙日を表示します。\n4. 問い合わせ合計が0なら、解決率は`N/A`、状態は`NO REQUESTS`、最繁忙日は`NONE`とします。\n5. 解決率90%以上は`ON TRACK`、80%以上90%未満は`REVIEW`、80%未満は`PRIORITY SUPPORT`です。\n6. 問い合わせ件数が同じ最大値の日が複数ある場合は、最初の曜日を表示します。"),
            md("p1-example", "## 最初に自分で確認するデータ\n\n| 曜日 | 問い合わせ | 解決 |\n|---|---:|---:|\n| Monday | 12 | 10 |\n| Tuesday | 18 | 16 |\n| Wednesday | 15 | 15 |\n| Thursday | 20 | 17 |\n| Friday | 10 | 9 |\n\n期待される結果は、問い合わせ75件、解決67件、未解決8件、解決率89.3%、状態`REVIEW`、最繁忙日`Thursday`です。これは実装コードの答えではなく、自分で実行した結果を照合するための値です。"),
            md("p1-output", "## 出力形式\n\n```text\nWEEKLY SUPPORT REPORT\nTOTAL RECEIVED: 75\nTOTAL RESOLVED: 67\nUNRESOLVED: 8\nRESOLUTION RATE: 89.3%\nSTATUS: REVIEW\nBUSIEST DAY: Thursday\n```\n\n空白の追加は構いませんが、各項目名と値を一行ずつ表示します。不正データの場合は`RESULT: INVALID`を表示します。"),
            md("p1-work", f"## 作業するファイル\n\n1. [{folder}/weekly_support.py]({folder}/weekly_support.py)を開きます。\n2. `TODO`を上から順に完成させ、`PROGRAM INCOMPLETE`を削除します。\n3. ファイルを保存します。\n4. 次の実行用セルのコメント記号を外して、自分で初期データを入力します。\n5. 出力を目で確認してから、自動確認へ進みます。"),
            code("p1-run", f"# 完成後、次の行頭の # を削除して実行します。\n# %run {folder}/weekly_support.py\n"),
            md("p1-check", "## 確認プログラムでロジックを検査する\n\n確認プログラムは、通常データ、80%と90%の境界、同率の最繁忙日、問い合わせ0件、不正値を自動的に入力します。確認プログラムを作ったり、内容を理解したりする必要はありません。`NG`では期待値と実際の値を読み、自分の`weekly_support.py`だけを修正します。"),
            code("p1-check-command", f"# weekly_support.pyを保存してから、次の行頭の # を削除して実行します。\n# !python {folder}/check_weekly_support.py\n"),
            md("p1-done", "## 完成条件と提出物\n\n- 自分で初期データを入力し、期待される週間報告を確認した。\n- 確認プログラムの全項目が`OK`になり、最後に`ALL TESTS PASSED`と表示された。\n- 完成した`weekly_support.py`をMoodleへ提出する。\n\n確認用プログラムやNotebookは提出しません。"),
        ]
    else:
        cells = [
            md("p1-title", "# 1.7 Mini-project — Weekly support report\n\nUse only variables, numbers, string input/output, conversion, decisions, and loops learned so far to complete a working Python script. Do not use functions, lists, dictionaries, or file input/output."),
            md("p1-problem", "## Problem to solve\n\nA learning centre manually totals support requests and resolutions from Monday to Friday. To prevent calculation and classification mistakes, build a program that accepts ten integers and displays a weekly report. The deliverable is `weekly_support.py`, not this Notebook."),
            md("p1-contract", "## Contract required for automatic checking\n\n- Keep the filename `weekly_support.py`.\n- In Monday-to-Friday order, read each day's received and resolved counts with `input()`.\n- Convert each input with `int()`.\n- Keep the uppercase result labels shown in the brief.\n- Do not change `check_weekly_support.py`.\n\nThe checker needs this contract so that it can supply different inputs and read the results. You do not need to understand the checker's internal code."),
            md("p1-rules", "## Operational rules\n\n1. Process five days in one `for` loop.\n2. If either count is negative, or resolved exceeds received, output `RESULT: INVALID`.\n3. For valid data, output weekly received, resolved, unresolved, resolution rate, status, and busiest day.\n4. If total received is zero, use rate `N/A`, status `NO REQUESTS`, and busiest day `NONE`.\n5. A rate of at least 90% is `ON TRACK`; at least 80% but below 90% is `REVIEW`; below 80% is `PRIORITY SUPPORT`.\n6. If several days share the largest received count, report the first one."),
            md("p1-example", "## Data for your first manual run\n\n| Day | Received | Resolved |\n|---|---:|---:|\n| Monday | 12 | 10 |\n| Tuesday | 18 | 16 |\n| Wednesday | 15 | 15 |\n| Thursday | 20 | 17 |\n| Friday | 10 | 9 |\n\nExpected: 75 received, 67 resolved, 8 unresolved, 89.3%, `REVIEW`, and `Thursday`. These are reconciliation values, not implementation code to copy."),
            md("p1-output", "## Output format\n\n```text\nWEEKLY SUPPORT REPORT\nTOTAL RECEIVED: 75\nTOTAL RESOLVED: 67\nUNRESOLVED: 8\nRESOLUTION RATE: 89.3%\nSTATUS: REVIEW\nBUSIEST DAY: Thursday\n```\n\nAdditional spacing is acceptable, but put each label and value on one line. For invalid data, output `RESULT: INVALID`."),
            md("p1-work", f"## Files and workflow\n\n1. Open [{folder}/weekly_support.py]({folder}/weekly_support.py).\n2. Complete each `TODO` and remove `PROGRAM INCOMPLETE`.\n3. Save the file.\n4. Uncomment the run command in the next cell and enter the manual data.\n5. Inspect the output yourself before running the automatic checker."),
            code("p1-run", f"# After completing the file, remove the first # on the next line and run it.\n# %run {folder}/weekly_support.py\n"),
            md("p1-check", "## Check the logic automatically\n\nThe supplied checker runs your script with normal data, exact 80% and 90% boundaries, a tied busiest day, no requests, and invalid counts. You do not need to write or understand the checker. When a check is `NG`, compare expected and actual values and change only `weekly_support.py`."),
            code("p1-check-command", f"# Save weekly_support.py, then remove the first # on the next line and run it.\n# !python {folder}/check_weekly_support.py\n"),
            md("p1-done", "## Completion and submission\n\n- Run the initial data yourself and inspect the report.\n- Make every checker item display `OK` and finish with `ALL TESTS PASSED`.\n- Submit the completed `weekly_support.py` in Moodle.\n\nDo not submit the checker or this Notebook."),
        ]
    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "1.7", "language": language, "revision": 29, "artifact": "weekly_support.py"},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


STARTER_EN = '''"""Project 1.7: complete this weekly support report program.

Keep the filename, input order, and uppercase output labels unchanged.
Do not change check_weekly_support.py.
"""

total_received = 0
total_resolved = 0
busiest_received = -1
busiest_day = ""
valid_data = True

for day_number in range(1, 6):
    if day_number == 1:
        day_name = "Monday"
    elif day_number == 2:
        day_name = "Tuesday"
    elif day_number == 3:
        day_name = "Wednesday"
    elif day_number == 4:
        day_name = "Thursday"
    else:
        day_name = "Friday"

    received = int(input(f"{day_name} received: "))
    resolved = int(input(f"{day_name} resolved: "))

    # TODO 1: Mark invalid data using the operational rules.
    # TODO 2: Add the two counts to their weekly totals.
    # TODO 3: Update the busiest count and day. Keep the first day on a tie.

# TODO 4: For invalid data, output RESULT: INVALID.
# TODO 5: Handle a week with zero received requests.
# TODO 6: Otherwise calculate unresolved, rate, and status.
# TODO 7: Print every required uppercase output label exactly as specified.

print("\\nPROGRAM INCOMPLETE")
'''

STARTER_JA = '''"""プロジェクト1.7：週間サポート報告を完成させます。

ファイル名、入力順、英大文字の出力項目名は変更しません。
check_weekly_support.pyは変更しません。
"""

total_received = 0
total_resolved = 0
busiest_received = -1
busiest_day = ""
valid_data = True

for day_number in range(1, 6):
    if day_number == 1:
        day_name = "Monday"
    elif day_number == 2:
        day_name = "Tuesday"
    elif day_number == 3:
        day_name = "Wednesday"
    elif day_number == 4:
        day_name = "Thursday"
    else:
        day_name = "Friday"

    received = int(input(f"{day_name}の問い合わせ件数: "))
    resolved = int(input(f"{day_name}の解決件数: "))

    # TODO 1：業務ルールに従い、不正データを記録します。
    # TODO 2：二つの値を週間合計へ加えます。
    # TODO 3：最繁忙件数と曜日を更新します。同数なら最初の曜日を残します。

# TODO 4：不正データならRESULT: INVALIDを表示します。
# TODO 5：問い合わせ合計0件を処理します。
# TODO 6：それ以外では、未解決件数、解決率、状態を計算します。
# TODO 7：指定された英大文字の出力項目名を正確に表示します。

print("\\nPROGRAM INCOMPLETE")
'''


def checker(language: str) -> str:
    ja = language == "ja"
    heading = "週間サポート報告の自動確認" if ja else "Weekly support report automatic check"
    fix = "weekly_support.pyだけを修正し、もう一度実行してください。" if ja else "Change only weekly_support.py and run this checker again."
    start = "まずTODOを完成させ、最後のPROGRAM INCOMPLETEを削除してください。" if ja else "Complete the TODOs first and remove the final PROGRAM INCOMPLETE line."
    return f'''#!/usr/bin/env python3
"""Run the learner script as a black box with several input cases."""
from __future__ import annotations
import re
import subprocess
import sys
from pathlib import Path

TARGET = Path(__file__).with_name("weekly_support.py")
LABELS = ["TOTAL RECEIVED", "TOTAL RESOLVED", "UNRESOLVED", "RESOLUTION RATE", "STATUS", "BUSIEST DAY"]

CASES = [
    ("standard week", [(12, 10), (18, 16), (15, 15), (20, 17), (10, 9)], {{"TOTAL RECEIVED":"75", "TOTAL RESOLVED":"67", "UNRESOLVED":"8", "RESOLUTION RATE":"89.3%", "STATUS":"REVIEW", "BUSIEST DAY":"Thursday"}}),
    ("exactly 80 percent", [(10, 8)] * 5, {{"TOTAL RECEIVED":"50", "TOTAL RESOLVED":"40", "UNRESOLVED":"10", "RESOLUTION RATE":"80.0%", "STATUS":"REVIEW", "BUSIEST DAY":"Monday"}}),
    ("exactly 90 percent", [(10, 9)] * 5, {{"TOTAL RECEIVED":"50", "TOTAL RESOLVED":"45", "UNRESOLVED":"5", "RESOLUTION RATE":"90.0%", "STATUS":"ON TRACK", "BUSIEST DAY":"Monday"}}),
    ("below 80 percent", [(10, 8), (10, 8), (10, 8), (10, 8), (10, 7)], {{"TOTAL RECEIVED":"50", "TOTAL RESOLVED":"39", "UNRESOLVED":"11", "RESOLUTION RATE":"78.0%", "STATUS":"PRIORITY SUPPORT", "BUSIEST DAY":"Monday"}}),
    ("first day wins a busiest-day tie", [(20, 20), (20, 20), (10, 10), (5, 5), (0, 0)], {{"TOTAL RECEIVED":"55", "TOTAL RESOLVED":"55", "UNRESOLVED":"0", "RESOLUTION RATE":"100.0%", "STATUS":"ON TRACK", "BUSIEST DAY":"Monday"}}),
    ("no requests", [(0, 0)] * 5, {{"TOTAL RECEIVED":"0", "TOTAL RESOLVED":"0", "UNRESOLVED":"0", "RESOLUTION RATE":"N/A", "STATUS":"NO REQUESTS", "BUSIEST DAY":"NONE"}}),
    ("resolved exceeds received", [(4, 3), (5, 6), (2, 2), (1, 1), (3, 2)], {{"RESULT":"INVALID"}}),
    ("negative count", [(4, 3), (-1, 0), (2, 2), (1, 1), (3, 2)], {{"RESULT":"INVALID"}}),
]

def run_case(data):
    supplied = "".join(f"{{received}}\\n{{resolved}}\\n" for received, resolved in data)
    try:
        result = subprocess.run([sys.executable, str(TARGET)], input=supplied, text=True, capture_output=True, timeout=5)
    except subprocess.TimeoutExpired:
        return None, "program did not finish within 5 seconds"
    if result.returncode != 0:
        detail = "\\n".join((result.stderr or result.stdout).strip().splitlines()[-6:])
        return None, f"program stopped with exit code {{result.returncode}}\\n{{detail}}"
    values = {{}}
    if re.search(r"(?:^|\\n)RESULT:\\s*INVALID\\s*$", result.stdout, re.MULTILINE):
        values["RESULT"] = "INVALID"
    for label in LABELS:
        match = re.search(rf"(?:^|\\n){{re.escape(label)}}:\\s*(.*?)\\s*$", result.stdout, re.MULTILINE)
        if match:
            values[label] = match.group(1)
    return values, result.stdout

if "PROGRAM INCOMPLETE" in TARGET.read_text(encoding="utf-8"):
    print("{heading}")
    print("Target:", TARGET)
    print("[NG] starter program is not complete")
    print("     {start}")
    raise SystemExit(1)

print("{heading}")
print("Target:", TARGET)
failures = 0
for name, data, expected in CASES:
    actual, detail = run_case(data)
    differences = []
    if actual is None:
        differences.append(detail)
    else:
        for key, expected_value in expected.items():
            actual_value = actual.get(key, "<missing>")
            if actual_value != expected_value:
                differences.append(f"{{key}}: expected {{expected_value!r}}, got {{actual_value!r}}")
    if differences:
        failures += 1
        print(f"[NG] {{name}}")
        for difference in differences:
            print("     " + difference.replace("\\n", "\\n     "))
    else:
        print(f"[OK] {{name}}")

if failures:
    print(f"\\n{{failures}} check(s) need attention.")
    print("{fix}")
    raise SystemExit(1)
print("\\nALL TESTS PASSED")
'''


README_EN = """# Weekly support mini-project\n\nEdit `weekly_support.py`; do not edit `check_weekly_support.py`. Run the program yourself first, then run `python check_weekly_support.py`. Submit only `weekly_support.py`.\n"""
README_JA = """# 週間サポート報告・小プロジェクト\n\n`weekly_support.py`を編集し、`check_weekly_support.py`は変更しません。最初に自分でプログラムを実行し、その後`python check_weekly_support.py`を実行します。提出するのは`weekly_support.py`だけです。\n"""


def write(path: Path, content: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8", newline="\n")
    print("wrote", path.relative_to(ROOT))


def main() -> None:
    for language, relative in [("en", "P1_weekly_support_report.ipynb"), ("ja", "ja/P1_weekly_support_report.ipynb")]:
        write(TEMPLATES / relative, json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n")
    for language, prefix, starter, readme in [
        ("en", Path("projects/weekly-support"), STARTER_EN, README_EN),
        ("ja", Path("ja/projects/weekly-support"), STARTER_JA, README_JA),
    ]:
        write(PROJECT_FILES / prefix / "weekly_support.py", starter)
        write(PROJECT_FILES / prefix / "check_weekly_support.py", checker(language))
        write(PROJECT_FILES / prefix / "README.md", readme)


if __name__ == "__main__":
    main()
