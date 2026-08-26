#!/usr/bin/env python3
"""Finalize Chapter 3 midterm project A after the prototype has been verified."""
from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
PROJECTS = BASE / "python-lab/project-files"
TEMPLATES = BASE / "python-lab/templates"


BRIEF_JA = '''# 第3章 中間実践課題A — 明日の追加配送先を決める

## 課題の状況

業務終了時刻になり、6校から6日分の給食提供記録が届きました。明日の通常配送は決まっていますが、車両一台だけが追加で一校を訪問できます。朝の打合せまでに、担当者は「原資料を確認すべき記録」と「学校別の配送優先順位」を用意しなければなりません。

提出された記録には、空欄、項目間の矛盾、二重登録、地区名の表記ゆれが含まれています。根拠のない値を推測で直してはいけません。判断に使えない記録を分け、残った記録から優先順位を作ってください。

## 今回完成させるもの

`projects/school-meal-review/meal_delivery_review.py`を開きます。このファイルには定数、完成済みの`main()`、未完成の8関数が用意されています。別のプログラムを一から作らず、この8関数を完成させてください。

完成したプログラムは、配布CSVの読込、要確認記録の分離、有効記録の集計、配送順位の決定、2つのCSV保存、最初の配送先の画面表示を順に行います。入力CSVは変更しません。

## 入力データ

使用するファイルは`projects/school-meal-review/data/school-meals-practice.csv`です。すべて架空の業務記録で、個人情報は含みません。一行は「一校の一日分の記録」を表します。

| 列 | 内容 |
|---|---|
| `date` | 給食を提供した日 |
| `school_id` | 学校ID |
| `school_name` | 学校名 |
| `district` | 提出時の地区名 |
| `pupils_present` | 当日の出席児童数 |
| `meals_delivered` | 当日の配送食数 |
| `meals_served` | 当日に実際に提供できた食数 |

CSVの見出しを原本の1行目と数え、最初のデータ行には`source_row`として2を付けます。入力CSVを編集または上書きしてはいけません。

## 確認が必要な記録

| フラグ | Trueにする条件 |
|---|---|
| `missing_number` | 必須数値のいずれかが空欄、または数値に変換できない |
| `negative_number` | 必須数値のいずれかが負数 |
| `impossible_service` | 提供食数が出席児童数または配送食数を超える |
| `duplicate_school_date` | `date`と`school_id`が別の行と重なる。重複グループの全行をTrueにする |

必須数値は`pupils_present`、`meals_delivered`、`meals_served`です。いずれかのフラグがTrueの行は集計から外します。

地区名の表記ゆれだけでは行を外しません。提出時の値を`district_raw`へ残し、処理に使う`district`は前後の空白を除き、各単語の先頭を大文字へ統一します。

違反したすべての規則から`issue`を作ります。複数ある場合は表の順序で`; `を挟んで連結します。使用する文字列は`missing required number`、`negative number`、`meals served exceeds limit`、`duplicate school/date`です。4フラグがすべてFalseの場合だけ`is_valid`をTrueにします。

## 集計と順位の規則

有効な各行へ`unmet_meals = pupils_present - meals_served`を追加します。`school_id`と`school_name`ごとにまとめ、次の順序で10列を作ります。

```text
priority, school_id, school_name, valid_days, pupils_present,
meals_served, unmet_meals, shortage_days,
meal_coverage_rate, average_unmet_meals
```

- `valid_days`：有効な異なる日付の数
- `shortage_days`：`unmet_meals > 0`だった有効日数
- `meal_coverage_rate`：提供食数合計 ÷ 出席児童数合計 × 100
- `average_unmet_meals`：未提供食数合計 ÷ 有効日数

丸める前の`average_unmet_meals`を降順、次に`shortage_days`を降順、最後に`school_id`を昇順として順位を決めます。先頭から`priority`を付け、率と平均は出力時に小数第1位へ丸めます。

## 作成するファイル

pandasのindexを含めず、次の2ファイルを保存します。

1. `output/records_to_verify.csv`：`source_row,date,school_id,school_name,issue`
2. `output/school_delivery_summary.csv`：上記の学校別集計10列

画面表示は次の形式です。値はプログラムが求めます。

```text
SCHOOL MEAL DELIVERY REVIEW
SOURCE RECORDS: ...
RECORDS TO VERIFY: ...
ANALYSIS RECORDS: ...
FIRST DELIVERY: ... - ...
```

## 8関数の契約

| 関数 | 完成時の動作 |
|---|---|
| `load_records(path)` | CSVを読み、必須列がなければ`ValueError`。必須列を選び、`source_row`を先頭へ追加する |
| `add_quality_flags(records)` | `records`自体を変更せず、ディープコピーへ地区名、4フラグ、`issue`、`is_valid`を追加する |
| `build_verification_report(flagged)` | 無効行を原本順に並べ、指定された5列で返す |
| `build_analysis_data(flagged)` | 有効行の必須列と`unmet_meals`を、新しい連番indexで返す |
| `summarise_schools(analysis)` | 指定された10列の順位表を返す。6校や既知の学校IDへ処理を固定しない |
| `select_first_delivery(summary)` | 1位の学校を`{"school_id": ..., "school_name": ...}`で返す。空なら`ValueError` |
| `save_outputs(audit, summary, output_dir)` | 出力フォルダを作り、2つのCSVをindexなしで保存する |
| `run_project(input_path, output_dir)` | 全工程を接続し、完成済み`main()`が使う5項目を返す |

`run_project()`は`source_records`、`records_to_verify`、`analysis_records`、`first_delivery_id`、`first_delivery_name`をキーとする辞書を返します。定数、関数名、引数、完成済み`main()`は変更しません。

## 自分で確認してから自動確認する

`meal_delivery_review.py`を保存して実行し、作られた2つのCSVを自分で開いて確認します。その後、次を実行します。

```text
python projects/school-meal-review/check_meal_delivery_review.py
```

確認プログラムが検査するのは、この課題文で公開した規則だけです。特定の6校へ処理が固定されていないことを確認するため、小さな別データも使用します。合格させるために確認プログラムを変更してはいけません。全項目合格後、`meal_delivery_review.py`だけを提出します。
'''


BRIEF_EN = '''# Chapter 3 midterm practical project A — Decide tomorrow's additional delivery

## Situation

Six schools have submitted six days of meal-service records. Regular deliveries for tomorrow are arranged, but one vehicle can make one additional school visit. Before the morning meeting, prepare a list of records that require verification and a ranked school summary.

Some records are incomplete, contradictory, duplicated, or use inconsistent district labels. Do not guess uncertain replacements. Separate unsafe records and make the decision from the remaining records.

## Program to complete

Open `projects/school-meal-review/meal_delivery_review.py`. Constants, `main()`, and eight unfinished functions are supplied. Complete those functions instead of starting another program. Do not edit or overwrite the input CSV.

The input is `projects/school-meal-review/data/school-meals-practice.csv`. One row represents one school's report for one date. Required columns are `date`, `school_id`, `school_name`, `district`, `pupils_present`, `meals_delivered`, and `meals_served`. Treat the header as source row 1 and insert `source_row` 2 for the first record.

## Verification rules

Create four Boolean flags. `missing_number` means any required numeric value is missing or non-numeric. `negative_number` means a required numeric value is below zero. `impossible_service` means meals served exceed pupils present or meals delivered. `duplicate_school_date` flags every row sharing the same `date` and `school_id` with another row.

Required numeric columns are `pupils_present`, `meals_delivered`, and `meals_served`. Exclude any flagged row from analysis. Preserve the submitted district as `district_raw`; normalise working `district` by stripping surrounding whitespace and title-casing each word. District normalisation alone does not exclude a row.

Build `issue` from all matching labels in flag order, joined by `; `: `missing required number`, `negative number`, `meals served exceeds limit`, `duplicate school/date`. Set `is_valid` only when all four flags are false.

## Summary and ranking

For valid rows, calculate `unmet_meals = pupils_present - meals_served`. Group by `school_id` and `school_name`. Return columns in this order:

```text
priority, school_id, school_name, valid_days, pupils_present,
meals_served, unmet_meals, shortage_days,
meal_coverage_rate, average_unmet_meals
```

`valid_days` counts distinct valid dates. `shortage_days` counts valid days with unmet meals above zero. Coverage is total served divided by total present times 100. Average unmet is total unmet divided by valid days. Rank before rounding by average unmet descending, shortage days descending, then school ID ascending. Number rows from 1 and round the two decimal measures to one decimal place.

## Outputs and screen report

Save without indexes: `output/records_to_verify.csv` with `source_row,date,school_id,school_name,issue`, and `output/school_delivery_summary.csv` with the ten summary columns. Print:

```text
SCHOOL MEAL DELIVERY REVIEW
SOURCE RECORDS: ...
RECORDS TO VERIFY: ...
ANALYSIS RECORDS: ...
FIRST DELIVERY: ... - ...
```

## Function contract

`load_records(path)` validates required columns, selects them, and inserts source rows. `add_quality_flags(records)` returns a deep copy with the normalised district, four flags, issue, and validity without changing its argument. `build_verification_report(flagged)` returns invalid rows and the five required columns in source order. `build_analysis_data(flagged)` returns valid required columns plus unmet meals with a fresh index. `summarise_schools(analysis)` returns the ranked ten-column summary without fixing its logic to six schools or known IDs. `select_first_delivery(summary)` returns a school-ID/name dictionary and raises `ValueError` on an empty summary. `save_outputs(audit, summary, output_dir)` creates the directory and saves both files. `run_project(input_path, output_dir)` connects the stages and returns `source_records`, `records_to_verify`, `analysis_records`, `first_delivery_id`, and `first_delivery_name`.

Do not change constants, function names, parameters, or the supplied `main()`.

## Check and submit

Save and run the program, inspect both CSV outputs, then run:

```text
python projects/school-meal-review/check_meal_delivery_review.py
```

The checker tests only this public contract and also uses a small second dataset to reject sample-specific code. Do not edit the checker to obtain a pass. Submit only `meal_delivery_review.py` after all ten checks pass and the checker ends with `ALL TESTS PASSED` and `REVIEW READY`.
'''


README_JA = '''# 中間実践課題A — 学校給食の追加配送

最初に`PROJECT_BRIEF.md`を読んでください。公開仕様のすべてがあります。

空のファイルから作る必要はありません。`meal_delivery_review.py`を開き、8個のTODO関数を完成させます。編集して提出するのはこのファイルだけです。入力CSVと`check_meal_delivery_review.py`は変更しません。

Ctrl+Sで保存してから、教材のルートで次を実行します。

```text
python projects/school-meal-review/meal_delivery_review.py
python projects/school-meal-review/check_meal_delivery_review.py
```

最初のコマンドで2つのCSVが作られます。自動確認の前に内容を確認してください。10項目すべてが合格し、最後に`ALL TESTS PASSED`と`REVIEW READY`が表示されれば完成です。
'''


README_EN = '''# Midterm choice A — School meal delivery review

Read `PROJECT_BRIEF.md` first; it contains the complete public contract.

Do not start from an empty file. Open `meal_delivery_review.py` and complete its eight TODO functions. Edit and submit that file only. Do not change the input CSV or `check_meal_delivery_review.py`.

Save the file, then run from the course-materials directory:

```text
python projects/school-meal-review/meal_delivery_review.py
python projects/school-meal-review/check_meal_delivery_review.py
```

Inspect the two CSV files created by the first command before running the checker. The work is complete when all ten checks pass and it ends with `ALL TESTS PASSED` and `REVIEW READY`.
'''


def make_notebook(language: str) -> dict:
    ja = language == "ja"
    def markdown(text: str) -> dict:
        return {"cell_type": "markdown", "metadata": {}, "source": text.splitlines(keepends=True)}
    def code(text: str) -> dict:
        return {"cell_type": "code", "execution_count": None, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}

    title = "# 第3章 中間実践課題A — 明日の追加配送先を決める" if ja else "# Chapter 3 midterm practical project A — Decide tomorrow's additional delivery"
    opening = ("第3章の選択課題のうち一つを完成させると中間実践課題の必須条件を満たします。このNotebookは作業案内であり提出物ではありません。`projects/school-meal-review/meal_delivery_review.py`の8関数を完成させます。" if ja else "Complete one Chapter 3 choice to satisfy the midterm requirement. This notebook guides the work and is not submitted. Complete the eight functions in `projects/school-meal-review/meal_delivery_review.py`.")
    locate = ("## 原本CSVを確認する\n\nデータはファイルとして配布されています。プログラムへ行を書き写したり元CSVを編集したりしません。次のセルは場所、形、先頭8行だけを表示します。" if ja else "## Inspect the supplied source CSV\n\nThe data is supplied as a file. Do not copy rows into the program or edit the source. The next cell displays its path, shape, and first eight rows.")
    contract = ("## 公開仕様を読んでから編集する\n\n`projects/school-meal-review/PROJECT_BRIEF.md`に必要列、品質規則、出力、8関数の契約があります。`README.md`には短い作業順があります。TODOが残るスターターが最初に失敗するのは正常です。" if ja else "## Read the public contract before editing\n\n`projects/school-meal-review/PROJECT_BRIEF.md` defines columns, quality rules, outputs, and eight function contracts. `README.md` contains the short workflow. The starter is expected to fail while TODOs remain.")
    run_text = ("## 保存して実行する\n\n実行前にCtrl+Sで保存します。未完成関数のエラーはPython Labの故障ではありません。表示された工程を実装し、保存して再実行します。" if ja else "## Save and run\n\nSave with Ctrl+S before running. An unfinished-function error is not a Python Lab failure. Implement the named stage, save, and run again.")
    inspect = ("## 直前の実行で作られた2ファイルを確認する\n\n古い出力を今回の結果と誤認しないよう、直前の実行が成功した場合だけ表示します。" if ja else "## Inspect both files from the latest successful run\n\nTo avoid mistaking an older file for the current result, outputs are shown only when the immediately preceding run succeeded.")
    auto = ("## 自動確認を実行する\n\n自分の出力を確認した後で実行します。確認プログラムは編集しません。NGになった工程を直し、10項目すべてを通します。" if ja else "## Run the automatic check\n\nRun this after inspecting your own output. Do not edit the checker. Fix the named stage until all ten checks pass.")
    submit = ("## 提出する\n\n`ALL TESTS PASSED`と`REVIEW READY`を確認したら、Moodleへ`meal_delivery_review.py`だけを提出します。2つのCSVはPython Labに作業根拠として残します。" if ja else "## Submit\n\nAfter `ALL TESTS PASSED` and `REVIEW READY`, submit only `meal_delivery_review.py` to Moodle. Keep the two CSV outputs in Python Lab as working evidence.")
    paths_code = '''from pathlib import Path
import pandas as pd

project = Path.cwd() / "projects" / "school-meal-review"
source_file = project / "data" / "school-meals-practice.csv"
print("Project found:", project.is_dir())
print("Source found:", source_file.is_file())
preview = pd.read_csv(source_file)
print("Shape:", preview.shape)
preview.head(8)
'''
    run_code = '''import subprocess
import sys

program_result = subprocess.run([sys.executable, str(project / "meal_delivery_review.py")])
program_succeeded = program_result.returncode == 0
print("Program exit code:", program_result.returncode)
if not program_succeeded:
    print("The program is incomplete or has an error. Review the traceback and public contract, then edit, save, and run again.")
'''
    inspect_code = '''if not globals().get("program_succeeded", False):
    print("Output is hidden because the immediately preceding run failed. Older files may still exist.")
else:
    for filename in ["records_to_verify.csv", "school_delivery_summary.csv"]:
        output_file = project / "output" / filename
        print(f"\\n--- {filename} ---")
        if output_file.is_file():
            display(pd.read_csv(output_file))
        else:
            print("Missing output. Review save_outputs() and run_project().")
'''
    return {
        "cells": [
            markdown(title + "\n\n" + opening), markdown(locate), code(paths_code),
            markdown(contract), markdown(run_text), code(run_code), markdown(inspect),
            code(inspect_code), markdown(auto), code("!python projects/school-meal-review/check_meal_delivery_review.py\n"),
            markdown(submit),
        ],
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def write_text(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(text, encoding="utf-8", newline="\n")


def main() -> None:
    subprocess.run([sys.executable, str(ROOT / "scripts/build-python-project3a-prototype-v1.py")], check=True)
    write_text(BASE / "project-3a-brief-en.md", BRIEF_EN)
    write_text(BASE / "project-3a-brief-ja.md", BRIEF_JA)
    for prefix, brief, readme in [(Path(), BRIEF_EN, README_EN), (Path("ja"), BRIEF_JA, README_JA)]:
        project = PROJECTS / prefix / "projects/school-meal-review"
        write_text(project / "PROJECT_BRIEF.md", brief)
        write_text(project / "README.md", readme)
    for language in ["en", "ja"]:
        target = TEMPLATES / (Path("ja") if language == "ja" else Path()) / "P3A_school_meal_delivery_review.ipynb"
        write_text(target, json.dumps(make_notebook(language), ensure_ascii=False, indent=1) + "\n")
    print(json.dumps({"briefs": 2, "project_copies": 2, "notebooks": 2}, ensure_ascii=False))


if __name__ == "__main__":
    main()
