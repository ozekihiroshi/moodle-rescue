#!/usr/bin/env python3
"""Align Project 3A public rules, examples, implementation, and checks."""
from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
PROJECTS = BASE / "python-lab/project-files"


EN_BRIEF = """# Chapter 3 midterm practical project A — Decide tomorrow's additional delivery

## Situation

Six schools have submitted six days of meal-service records. Regular deliveries for tomorrow are arranged, but one vehicle can make one additional school visit. Before the morning meeting, prepare a list of records that require verification and a ranked school summary.

Some records are incomplete, contradictory, duplicated, or use inconsistent district labels. Do not guess uncertain replacements. Separate records that cannot support the decision and rank the schools from the remaining records.

## Program to complete

Open `projects/school-meal-review/meal_delivery_review.py`. Constants, a completed `main()`, and eight unfinished functions are supplied. Complete those functions instead of starting another program. The program must read the supplied CSV, separate records requiring verification, aggregate valid records, rank schools, save two CSV files, and print the first delivery destination. Do not edit or overwrite the input CSV.

## Input data

The input is `projects/school-meal-review/data/school-meals-practice.csv`. It contains fictional operational data and no personal information. One row represents one school's report for one date.

| Column | Meaning |
|---|---|
| `date` | meal-service date |
| `school_id` | school identifier |
| `school_name` | school name |
| `district` | district label as submitted |
| `pupils_present` | pupils present that day |
| `meals_delivered` | meals delivered that day |
| `meals_served` | meals actually served that day |

Treat the CSV header as source row 1 and insert `source_row` 2 for the first data record. The source file contains 37 data rows.

## Processing order and verification rules

Apply the rules in this order so that the same source produces the same flags.

1. Strip surrounding whitespace from `date` and `school_id`. Do not convert or otherwise normalise the date format. Duplicate matching uses these stripped strings.
2. Convert `pupils_present`, `meals_delivered`, and `meals_served` with `pd.to_numeric(..., errors="coerce")`. An empty cell, whitespace-only value, or non-numeric value therefore becomes missing.
3. Set `missing_number` when any required numeric value is missing after conversion.
4. Set `negative_number` when any successfully converted numeric value is below zero. Missing values do not themselves make this flag true.
5. Set `impossible_service` when `meals_served > pupils_present` or `meals_served > meals_delivered`. Evaluate each comparison only when both values needed for that comparison are present. A missing value does not by itself make this flag true.
6. Set `duplicate_school_date` on every row whose stripped `date` and `school_id` key occurs more than once. Use the equivalent of `duplicated(..., keep=False)`.

Preserve the submitted district in `district_raw`. Create the working `district` with the equivalent of `.astype("string").str.strip().str.title()`. The supplied file has no blank district, date, school-ID, or school-name values. This project does not add a quality flag for those fields, and it does not correct school-name variation.

Build `issue` from every true flag, in the order shown below, joined by `; `:

| Flag | Public issue text |
|---|---|
| `missing_number` | `missing required number` |
| `negative_number` | `negative number` |
| `impossible_service` | `meals served exceeds limit` |
| `duplicate_school_date` | `duplicate school/date` |

One row may contain several issue texts. Set `is_valid` to true only when all four flags are false. `records_to_verify` means the number of invalid rows, not the number of issue labels.

## Summary and ranking

For valid rows, calculate `unmet_meals = pupils_present - meals_served`. Group by both `school_id` and `school_name`. If one school ID appears with different school names, treat those names as separate groups; this project does not repair that variation.

Return columns in this order:

```text
priority, school_id, school_name, valid_days, pupils_present,
meals_served, unmet_meals, shortage_days,
meal_coverage_rate, average_unmet_meals
```

- `valid_days`: number of distinct valid dates
- `shortage_days`: number of valid rows where `unmet_meals > 0`
- `meal_coverage_rate`: total meals served divided by total pupils present, times 100
- `average_unmet_meals`: total unmet meals divided by valid days

If a group's total pupils present is zero, set `meal_coverage_rate` to `0.0`. Rank before rounding by average unmet descending, shortage days descending, then school ID ascending. The school ID is the deterministic tie-break key. Add priorities from 1, then use pandas `.round(1)` for coverage and average unmet.

## Expected checkpoints for the supplied CSV

These values let you check your own work without revealing which source rows are invalid.

```text
SOURCE RECORDS: 37
RECORDS TO VERIFY: 4
ANALYSIS RECORDS: 33
FIRST DELIVERY: S004 — Market Road School
```

The four flags contain 1 missing-number row, 0 negative-number rows, 1 impossible-service row, and 2 duplicate-key rows. The final summary contains 6 groups. Its first row has `average_unmet_meals` 7.5 and `shortage_days` 6. If your values differ, inspect the intermediate flags and summary before running the checker.

## Output files

Save without pandas indexes:

1. `output/records_to_verify.csv` with `source_row,date,school_id,school_name,issue`
2. `output/school_delivery_summary.csv` with the ten summary columns

The completed `main()` prints the four-line checkpoint above after the heading `SCHOOL MEAL DELIVERY REVIEW`.

## Eight-function contract

| Function | Input and result |
|---|---|
| `load_records(path)` | Validate required columns, select them, and insert source rows |
| `add_quality_flags(records)` | Return a deep copy with stripped keys, converted numbers, district fields, four flags, `issue`, and `is_valid`; do not change `records` |
| `build_verification_report(flagged)` | Return invalid rows in source order with the five required columns |
| `build_analysis_data(flagged)` | Return valid required columns plus `unmet_meals` with a fresh index |
| `summarise_schools(analysis)` | Return the ranked ten-column summary without fixing logic to six schools or known IDs |
| `select_first_delivery(summary)` | Return `{"school_id": ..., "school_name": ...}` for priority 1; raise `ValueError` if empty |
| `save_outputs(audit, summary, output_dir)` | Create the directory and save both output CSV files |
| `run_project(input_path, output_dir)` | Connect all stages and return the five values below |

| `run_project()` key | Meaning |
|---|---|
| `source_records` | input CSV data-row count |
| `records_to_verify` | invalid-row count; a multi-issue row is counted once |
| `analysis_records` | valid rows used for aggregation |
| `first_delivery_id` | priority-1 school ID |
| `first_delivery_name` | priority-1 school name |

Do not change constants, function names, parameters, or the supplied `main()`.

## Work in stages, check, and submit

Implement in stages: load and inspect; add flags; split audit and analysis data; aggregate and rank; save and connect the pipeline. This project combines APIs already practised in 3.3 and 3.4, including `pd.to_numeric(..., errors="coerce")`, `copy(deep=True)`, `duplicated(..., keep=False)`, Boolean masks, `groupby().agg()`, mixed-direction `sort_values()`, `reset_index()`, CSV saving, and output re-reading.

Save and run `meal_delivery_review.py`, inspect both CSV outputs, and compare the published checkpoints. Then run:

```text
python projects/school-meal-review/check_meal_delivery_review.py
```

The checker tests only this public contract and uses small additional data to reject sample-specific code and verify boundary rules. Do not edit the checker to obtain a pass. Submit only `meal_delivery_review.py` after all ten checks pass and the checker ends with `ALL TESTS PASSED` and `REVIEW READY`.
"""


JA_BRIEF = """# 第3章 中間実践課題A — 明日の追加配送先を決める

## 課題の状況

業務終了時刻になり、6校から6日分の給食提供記録が届きました。明日の通常配送は決まっていますが、車両一台だけが追加で一校を訪問できます。朝の打合せまでに、担当者は「原資料を確認すべき記録」と「学校別の配送優先順位」を用意しなければなりません。

提出記録には、空欄、項目間の矛盾、二重登録、地区名の表記ゆれがあります。根拠のない値を推測で直してはいけません。判断に使えない記録を分け、残った記録から優先順位を作ってください。

## 今回完成させるプログラム

`projects/school-meal-review/meal_delivery_review.py`を開きます。定数、完成済みの`main()`、未完成の8関数が用意されています。別のプログラムを一から作らず、この8関数を完成させてください。プログラムは、配布CSVの読込、要確認記録の分離、有効記録の集計、配送順位の決定、2つのCSV保存、最初の配送先の画面表示を順に行います。入力CSVは編集も上書きもしません。

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

CSVの見出しを原本の1行目と数え、最初のデータ行には`source_row`として2を付けます。原本には37件のデータ行があります。

## 判定する順序と品質規則

同じ原本から同じフラグが得られるよう、次の順序で処理します。

1. `date`と`school_id`の前後空白を除きます。日付形式の変換や表記統一はしません。重複判定には空白除去後の文字列を使います。
2. `pupils_present`、`meals_delivered`、`meals_served`を`pd.to_numeric(..., errors="coerce")`で変換します。空セル、空白だけの値、数値に変換できない値は欠損値になります。
3. 変換後の必須数値に欠損が一つでもあれば`missing_number`をTrueにします。
4. 数値へ変換できた値のいずれかが負なら`negative_number`をTrueにします。欠損値だけを理由にこのフラグをTrueにはしません。
5. `meals_served > pupils_present`または`meals_served > meals_delivered`なら`impossible_service`をTrueにします。それぞれの比較は、比較する二つの値が揃った場合だけ行います。欠損値だけを理由にTrueにはしません。
6. 空白除去後の`date`と`school_id`の組が二行以上に現れた場合、重複グループの全行で`duplicate_school_date`をTrueにします。`duplicated(..., keep=False)`に相当する判定です。

提出時の地区名を`district_raw`へ残します。処理用の`district`は`.astype("string").str.strip().str.title()`と同じ方法で整えます。配布CSVには地区名、日付、学校ID、学校名の空欄はありません。この課題ではこれらの項目へ別の品質フラグを追加せず、学校名の表記ゆれも補正しません。

Trueになったすべてのフラグから、次の順序で`issue`を作り、複数ある場合は`; `で連結します。

| フラグ | `issue`へ入れる文字列 |
|---|---|
| `missing_number` | `missing required number` |
| `negative_number` | `negative number` |
| `impossible_service` | `meals served exceeds limit` |
| `duplicate_school_date` | `duplicate school/date` |

一行に複数の問題が入る場合があります。4フラグがすべてFalseの場合だけ`is_valid`をTrueにします。`records_to_verify`は問題文字列の総数ではなく、無効と判定された行数です。

## 集計と順位の規則

有効行へ`unmet_meals = pupils_present - meals_served`を追加します。`school_id`と`school_name`の両方でまとめます。同じ`school_id`でも`school_name`が異なる行は別グループとし、この課題では学校名の表記ゆれを直しません。

次の順序で10列を作ります。

```text
priority, school_id, school_name, valid_days, pupils_present,
meals_served, unmet_meals, shortage_days,
meal_coverage_rate, average_unmet_meals
```

- `valid_days`：有効な異なる日付の数
- `shortage_days`：`unmet_meals > 0`だった有効行数
- `meal_coverage_rate`：提供食数合計 ÷ 出席児童数合計 × 100
- `average_unmet_meals`：未提供食数合計 ÷ 有効日数

あるグループの出席児童数合計が0なら、`meal_coverage_rate`を`0.0`とします。丸める前の`average_unmet_meals`を降順、次に`shortage_days`を降順、最後に`school_id`を昇順として順位を決めます。学校IDは同順位を再現可能に解消するキーです。先頭から`priority`を付けた後、率と平均をpandasの`.round(1)`で小数第1位へ丸めます。

## 配布CSVで確認できる代表値

どの行が異常かという答えは示しませんが、自分の処理を確認できる値は次のとおりです。

```text
SOURCE RECORDS: 37
RECORDS TO VERIFY: 4
ANALYSIS RECORDS: 33
FIRST DELIVERY: S004 — Market Road School
```

4フラグの件数は、欠損数値1行、負数0行、提供数の矛盾1行、重複キー2行です。学校別集計は6グループになり、1位の`average_unmet_meals`は7.5、`shortage_days`は6です。値が違う場合は、自動確認を実行する前に中間フラグと集計表を見直してください。

## 作成するファイル

pandasのindexを含めず、次の2ファイルを保存します。

1. `output/records_to_verify.csv`：`source_row,date,school_id,school_name,issue`
2. `output/school_delivery_summary.csv`：上記の学校別集計10列

完成済みの`main()`は、`SCHOOL MEAL DELIVERY REVIEW`という見出しに続けて、上記4行の代表値を表示します。

## 8関数の契約

| 関数 | 入力と完成時の動作 |
|---|---|
| `load_records(path)` | 必須列を検証・選択し、原本行番号を追加する |
| `add_quality_flags(records)` | ディープコピーへキーの空白除去、数値変換、地区列、4フラグ、`issue`、`is_valid`を追加し、`records`自体は変更しない |
| `build_verification_report(flagged)` | 無効行を原本順に並べ、指定5列で返す |
| `build_analysis_data(flagged)` | 有効行の必須列と`unmet_meals`を新しいindexで返す |
| `summarise_schools(analysis)` | 6校や既知IDへ固定せず、指定10列の順位表を返す |
| `select_first_delivery(summary)` | 1位を`{"school_id": ..., "school_name": ...}`で返し、空なら`ValueError` |
| `save_outputs(audit, summary, output_dir)` | 出力フォルダを作り、2つのCSVを保存する |
| `run_project(input_path, output_dir)` | 全工程を接続し、下記5項目を返す |

| `run_project()`のキー | 値の定義 |
|---|---|
| `source_records` | 入力CSVのデータ行数 |
| `records_to_verify` | 無効行数。一行に複数問題があっても一行と数える |
| `analysis_records` | 集計に使用した有効行数 |
| `first_delivery_id` | 優先順位1位の学校ID |
| `first_delivery_name` | 優先順位1位の学校名 |

定数、関数名、引数、完成済み`main()`は変更しません。

## 段階的に実装し、確認して提出する

読込と確認、品質フラグ、確認用／分析用データの分離、集計と順位、保存と全体接続の順に実装します。この課題で使う`pd.to_numeric(..., errors="coerce")`、`copy(deep=True)`、`duplicated(..., keep=False)`、ブールマスク、`groupby().agg()`、方向の異なる`sort_values()`、`reset_index()`、CSV保存と再読込は3.3・3.4までに練習した内容です。

`meal_delivery_review.py`を保存して実行し、2つのCSVを開いて代表値と照合します。その後、次を実行します。

```text
python projects/school-meal-review/check_meal_delivery_review.py
```

確認プログラムは、この課題文で公開した規則だけを検査します。特定の6校へ固定したコードや境界規則の取り違えを見つけるため、小さな別データも使います。合格のために確認プログラムを変更してはいけません。10項目すべてが合格し、最後に`ALL TESTS PASSED`と`REVIEW READY`が表示されたら、`meal_delivery_review.py`だけを提出します。
"""


EN_README = """# Midterm choice A — School meal delivery review

Read `PROJECT_BRIEF.md` first; it contains the complete public contract and expected checkpoints.

Do not start from an empty file. Complete the eight TODO functions in `meal_delivery_review.py`. Edit and submit that file only. Do not change the source CSV or checker.

Work in stages: load and inspect; add flags; split audit and analysis data; aggregate and rank; save and connect the pipeline. Save with Ctrl+S, then run from the course-materials directory:

```text
python projects/school-meal-review/meal_delivery_review.py
python projects/school-meal-review/check_meal_delivery_review.py
```

Before the checker, inspect both generated CSVs and compare the published screen values, flag counts, group count, and first-row checks. Completion requires all ten checks, `ALL TESTS PASSED`, and `REVIEW READY`.
"""


JA_README = """# 中間実践課題A — 学校給食の追加配送

最初に`PROJECT_BRIEF.md`を読んでください。公開仕様と、配布CSVで確認できる代表値がすべて記載されています。

空のファイルから作る必要はありません。`meal_delivery_review.py`の8個のTODO関数を完成させます。編集して提出するのはこのファイルだけです。原本CSVと確認プログラムは変更しません。

読込と確認、品質フラグ、確認用／分析用データの分離、集計と順位、保存と全体接続の順に進めます。Ctrl+Sで保存し、教材ルートで次を実行します。

```text
python projects/school-meal-review/meal_delivery_review.py
python projects/school-meal-review/check_meal_delivery_review.py
```

自動確認の前に2つのCSVを開き、公開された画面表示、フラグ件数、集計数、1位の代表値と照合します。10項目すべてと`ALL TESTS PASSED`、`REVIEW READY`が完成条件です。
"""


def write_text(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        stream.write(text)


def replace_once(path: Path, old: str, new: str) -> None:
    text = path.read_text(encoding="utf-8")
    if text.count(old) != 1:
        raise RuntimeError(f"Expected one occurrence in {path}: {old[:60]!r}")
    write_text(path, text.replace(old, new))


def update_solution() -> None:
    path = BASE / "reference-solutions/project-3a/meal_delivery_review.py"
    replace_once(path, '    flagged = records.copy(deep=True)\n    flagged["district_raw"] = flagged["district"]', '    flagged = records.copy(deep=True)\n    for column in ["date", "school_id"]:\n        flagged[column] = flagged[column].astype("string").str.strip()\n    flagged["district_raw"] = flagged["district"]')
    replace_once(path, '    flagged["impossible_service"] = (\n        flagged["meals_served"].notna()\n        & (\n            (flagged["meals_served"] > flagged["pupils_present"])\n            | (flagged["meals_served"] > flagged["meals_delivered"])\n        )\n    )', '    served_over_present = (\n        flagged["meals_served"].notna()\n        & flagged["pupils_present"].notna()\n        & (flagged["meals_served"] > flagged["pupils_present"])\n    )\n    served_over_delivered = (\n        flagged["meals_served"].notna()\n        & flagged["meals_delivered"].notna()\n        & (flagged["meals_served"] > flagged["meals_delivered"])\n    )\n    flagged["impossible_service"] = served_over_present | served_over_delivered')
    replace_once(path, '    summary["meal_coverage_rate"] = (\n        summary["meals_served"] / summary["pupils_present"] * 100\n    )', '    summary["meal_coverage_rate"] = 0.0\n    has_pupils = summary["pupils_present"] != 0\n    summary.loc[has_pupils, "meal_coverage_rate"] = (\n        summary.loc[has_pupils, "meals_served"]\n        / summary.loc[has_pupils, "pupils_present"]\n        * 100\n    )')


def update_starters() -> None:
    for relative in ["python-lab/project-files/projects/school-meal-review/meal_delivery_review.py", "python-lab/project-files/ja/projects/school-meal-review/meal_delivery_review.py"]:
        path = BASE / relative
        replace_once(path, '    # TODO 2: do not change records itself\n    raise NotImplementedError', '    # TODO 2: deep-copy first; strip date/ID, convert numbers, then add all flags\n    # Compare each impossible pair only when both required values are present.\n    raise NotImplementedError')
        replace_once(path, '    # TODO 5\n    raise NotImplementedError', '    # TODO 5: group by ID and name, rank before rounding, and use 0.0 for 0/0 coverage\n    raise NotImplementedError')


def update_checker() -> None:
    source = PROJECTS / "projects/school-meal-review/check_meal_delivery_review.py"
    text = source.read_text(encoding="utf-8")
    old = '''def test_quality_flags():
    assert int(flagged["missing_number"].sum()) == 1
    assert int(flagged["negative_number"].sum()) == 0
    assert int(flagged["impossible_service"].sum()) == 1
    assert int(flagged["duplicate_school_date"].sum()) == 2
'''
    new = '''def test_quality_flags():
    assert int(flagged["missing_number"].sum()) == 1
    assert int(flagged["negative_number"].sum()) == 0
    assert int(flagged["impossible_service"].sum()) == 1
    assert int(flagged["duplicate_school_date"].sum()) == 2

    edge = pd.DataFrame([
        {"date": " 2026-02-01 ", "school_id": " A ", "school_name": "A School", "district": " south east ", "pupils_present": "   ", "meals_delivered": "10", "meals_served": "11"},
        {"date": "2026-02-01", "school_id": "A", "school_name": "A School", "district": "SOUTH EAST", "pupils_present": "12", "meals_delivered": "10", "meals_served": "11"},
        {"date": "2026-02-02", "school_id": "B", "school_name": "B School", "district": "North", "pupils_present": "-5", "meals_delivered": "", "meals_served": "10"},
    ])
    edge.insert(0, "source_row", [2, 3, 4])
    prepared = module.add_quality_flags(edge)
    assert prepared["school_id"].tolist() == ["A", "A", "B"]
    assert prepared["date"].tolist()[:2] == ["2026-02-01", "2026-02-01"]
    assert prepared["duplicate_school_date"].tolist() == [True, True, False]
    assert prepared["missing_number"].tolist() == [True, False, True]
    assert prepared["negative_number"].tolist() == [False, False, True]
    assert prepared["impossible_service"].tolist() == [True, True, True]
    assert prepared.loc[0, "issue"] == "missing required number; meals served exceeds limit; duplicate school/date"
'''
    if text.count(old) != 1:
        raise RuntimeError("Checker quality block changed")
    text = text.replace(old, new)
    old = '''def test_not_fixed_to_sample():
    small = pd.DataFrame([
        {"date": "2026-01-01", "school_id": "A", "school_name": "A School", "district": " north ", "pupils_present": 10, "meals_delivered": 10, "meals_served": 8},
        {"date": "2026-01-01", "school_id": "B", "school_name": "B School", "district": "South", "pupils_present": 10, "meals_delivered": 10, "meals_served": 10},
    ])
    small.insert(0, "source_row", [2, 3])
    prepared = module.add_quality_flags(small)
    result = module.summarise_schools(module.build_analysis_data(prepared))
    assert result["school_id"].tolist() == ["A", "B"]
'''
    new = '''def test_not_fixed_to_sample():
    small = pd.DataFrame([
        {"date": "2026-01-01", "school_id": "A", "school_name": "A School", "district": " north ", "pupils_present": 10, "meals_delivered": 10, "meals_served": 8},
        {"date": "2026-01-01", "school_id": "B", "school_name": "B School", "district": "South", "pupils_present": 10, "meals_delivered": 10, "meals_served": 10},
        {"date": "2026-01-01", "school_id": "C", "school_name": "C School", "district": "East", "pupils_present": 0, "meals_delivered": 0, "meals_served": 0},
        {"date": "2026-01-01", "school_id": "D", "school_name": "D North", "district": "North", "pupils_present": 5, "meals_delivered": 5, "meals_served": 5},
        {"date": "2026-01-02", "school_id": "D", "school_name": "D South", "district": "South", "pupils_present": 5, "meals_delivered": 5, "meals_served": 5},
    ])
    small.insert(0, "source_row", range(2, 7))
    prepared = module.add_quality_flags(small)
    result = module.summarise_schools(module.build_analysis_data(prepared))
    assert set(result["school_id"]) == {"A", "B", "C", "D"}
    assert len(result.loc[result["school_id"] == "D"]) == 2
    assert float(result.loc[result["school_id"] == "C", "meal_coverage_rate"].iloc[0]) == 0.0
'''
    if text.count(old) != 1:
        raise RuntimeError("Checker generic-data block changed")
    write_text(source, text.replace(old, new))
    write_text(PROJECTS / "ja/projects/school-meal-review/check_meal_delivery_review.py", source.read_text(encoding="utf-8"))


def update_notebook(path: Path, ja: bool) -> None:
    document = json.loads(path.read_text(encoding="utf-8"))
    cell_id = "p3a-ja-08b" if ja else "p3a-en-08b"
    document["cells"] = [cell for cell in document["cells"] if cell.get("id") != cell_id]
    marker = "p3a-ja-08" if ja else "p3a-en-08"
    index = next(i for i, cell in enumerate(document["cells"]) if cell.get("id") == marker) + 1
    text = ("## 代表値を自分で照合する\n\n成功した直前の実行では、原本37行、要確認4行、分析対象33行、学校別6グループになります。最初の配送先は`S004 — Market Road School`で、1位の平均未提供食数は7.5、未提供があった日は6日です。フラグ件数は欠損1、負数0、矛盾1、重複2です。違う場合は自動確認へ進まず、どの段階で件数が変わったか表示して確認します。" if ja else "## Reconcile your own checkpoints\n\nA successful run of the supplied file has 37 source rows, 4 review rows, 33 analysis rows, and 6 summary groups. The first delivery is `S004 — Market Road School`; its average unmet meals is 7.5 and its shortage-day count is 6. Flag counts are missing 1, negative 0, impossible 1, and duplicate 2. If a value differs, display the stage where the count first changes before running the checker.")
    document["cells"].insert(index, {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)})
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        json.dump(document, stream, ensure_ascii=False, indent=1)
        stream.write("\n")


def update_lesson_copy() -> None:
    for path in [BASE / "python-lab/templates/09_cleaning_audit_trail.ipynb", BASE / "python-lab/templates/ja/09_cleaning_audit_trail.ipynb"]:
        document = json.loads(path.read_text(encoding="utf-8"))
        changed = 0
        for cell in document["cells"]:
            source = "".join(cell.get("source", []))
            if "clean = raw.copy()" in source:
                cell["source"] = source.replace("clean = raw.copy()", "clean = raw.copy(deep=True)").splitlines(keepends=True)
                changed += 1
        if changed != 2:
            raise RuntimeError(f"Expected two explicit-copy examples in {path}, found {changed}")
        with path.open("w", encoding="utf-8", newline="\n") as stream:
            json.dump(document, stream, ensure_ascii=False, indent=1)
            stream.write("\n")


def main() -> None:
    subprocess.run([sys.executable, str(ROOT / "scripts/finalize-python-chapter3-path-v35.py")], check=True)
    for path, text in [(BASE / "project-3a-brief-en.md", EN_BRIEF), (BASE / "project-3a-brief-ja.md", JA_BRIEF), (PROJECTS / "projects/school-meal-review/PROJECT_BRIEF.md", EN_BRIEF), (PROJECTS / "ja/projects/school-meal-review/PROJECT_BRIEF.md", JA_BRIEF), (PROJECTS / "projects/school-meal-review/README.md", EN_README), (PROJECTS / "ja/projects/school-meal-review/README.md", JA_README)]:
        write_text(path, text)
    update_solution()
    update_starters()
    update_checker()
    update_notebook(BASE / "python-lab/templates/P3A_school_meal_delivery_review.ipynb", False)
    update_notebook(BASE / "python-lab/templates/ja/P3A_school_meal_delivery_review.ipynb", True)
    update_lesson_copy()
    print(json.dumps({"briefs": 4, "readmes": 2, "starters": 2, "checkers": 2, "notebooks": 4, "solution": 1}))


if __name__ == "__main__":
    main()
