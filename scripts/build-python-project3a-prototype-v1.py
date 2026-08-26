#!/usr/bin/env python3
"""Build the source prototype for Chapter 3 midterm choice A."""
from __future__ import annotations

import json
import shutil
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
DATASET = BASE / "datasets/chapter3-midterm/school-meals-practice.csv"
PROJECTS = BASE / "python-lab/project-files"
REFERENCE = BASE / "reference-solutions/project-3a"


DESIGN = r'''# 第3章・三択中間実践課題の設計

## 位置付け

第3章の中間実践課題は三つの選択肢から一つを完成すれば必須条件を満たす。二つ目と三つ目は任意の転用課題とする。三課題は題材と発見を変えるが、同じ第3章技能、所要時間、提出量、自動確認水準を要求する。

| 選択 | 仮題 | 発見 |
|---|---|---|
| A | 学校給食の追加配送 | 欠損を0扱いした素朴な答えと、監査後の答えが反転する |
| B | 公共バスの改善調査 | 平均遅延と利用者影響で優先路線が変わる |
| C | 地域給水設備の点検 | 低出力に見えるセンサー異常と継続停止を分ける |

まずAを完成させ、その設計からB、Cを作る。既存Moodleへは、Aの学習者経路と確認プログラムが成立するまで反映しない。

# 選択A：学校給食の追加配送

## 学習者に示す状況

6校から直近6日分の記録が届いた。翌日、倉庫の車両は通常配送とは別に一校だけ追加訪問できる。まだ利用してはいけない記録を分けたうえで、最初の配送先を一校示し、判断に使った集計表を残す。元CSVは変更しない。

社会的意義や学習目標は課題本文で説明しない。状況、規則、入力、成果物、完成条件だけを示す。

## 入力

`projects/school-meal-review/data/school-meals-practice.csv`

- 37行、7列、6校、6日分（S003の一日は重複している）
- 列：`date`, `school_id`, `school_name`, `district`, `pupils_present`, `meals_delivered`, `meals_served`
- 架空データであり個人情報を含まない
- 学習者はCSVを編集しない

## 業務規則

1. 必須数値に欠損がある行は確認対象とし、集計から外す。
2. 負数を含む行は確認対象とし、集計から外す。
3. `meals_served`が`pupils_present`または`meals_delivered`を超える行は確認対象とし、集計から外す。
4. `date + school_id`が重複する場合は重複した全行を確認対象とし、集計から外す。
5. 地区名は前後空白を除き、単語先頭を大文字に統一する。これは行を外す理由にしない。
6. 有効行に`unmet_meals = pupils_present - meals_served`を追加する。
7. 学校別に有効日数、出席児童、提供食、未提供食、未提供があった日数を集計する。
8. `meal_coverage_rate = meals_served合計 / pupils_present合計 × 100`を計算する。
9. `average_unmet_meals = unmet_meals合計 / valid_days`を計算する。
10. `average_unmet_meals`降順、`shortage_days`降順、`school_id`昇順で配送優先順位を決める。

## 編集・提出・出力

- 編集・提出：`meal_delivery_review.py`一つ
- 変更しない：入力CSV、`check_meal_delivery_review.py`
- 生成：`output/records_to_verify.csv`
- 生成：`output/school_delivery_summary.csv`

`records_to_verify.csv`には元CSV行番号、日付、学校、理由を残す。`school_delivery_summary.csv`には全校の集計と優先順位を残す。

## 期待される発見

- 元行37
- 欠損行1
- 不可能値行1
- 重複行2
- 確認対象4
- 分析対象33
- 素朴な欠損0扱い：`S002`
- 規則どおりの処理：`S004 — Market Road School`
- S004：有効6日、未提供45、平均7.5、提供率93.3%

素朴な答えと正しい答えが異なることを、この課題の採用条件とする。

## 実装単位

1. `load_records(path)`
2. `add_quality_flags(records)`
3. `build_verification_report(flagged)`
4. `build_analysis_data(flagged)`
5. `summarise_schools(analysis)`
6. `select_first_delivery(summary)`
7. `save_outputs(audit, summary, output_dir)`
8. `run_project(input_path, output_dir)`

個別関数は件数や特定学校を固定しない。`run_project()`だけが配布CSVを既定入力として接続する。

## 自動確認

確認プログラムは公開仕様だけを検査する。

1. CSV読込と元行番号
2. 元DataFrameを変更しない
3. 地区名正規化
4. 四種類の品質フラグ
5. 確認対象4行
6. 分析対象33行
7. 学校別集計値
8. 優先順位とS004
9. 任意の小規模DataFrameでも特定件数へ固定されない
10. 出力、画面表示、元CSV保護

全項目合格時は`ALL TESTS PASSED`と`REVIEW READY`を表示する。

## 第3章本文への逆算項目

プロジェクト完成後、3.1〜3.4に次が十分含まれるか監査する。

- 必須列、shape、型、元データコピー
- 複数列の欠損マスク
- `duplicated(..., keep=False)`
- 複数の不可能値条件
- `.loc`によるフラグ付与
- `str.strip().str.title()`
- `groupby().agg()`のnamed aggregation
- 件数を数える条件付き集計
- 合計から率を作る
- 複数列、異なる昇降順で並べ替える
- CSV保存と再読込照合
'''


REFERENCE_CODE = r'''from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
DEFAULT_INPUT = HERE / "data" / "school-meals-practice.csv"
DEFAULT_OUTPUT = HERE / "output"
REQUIRED_COLUMNS = [
    "date", "school_id", "school_name", "district",
    "pupils_present", "meals_delivered", "meals_served",
]
NUMERIC_COLUMNS = ["pupils_present", "meals_delivered", "meals_served"]


def load_records(path):
    records = pd.read_csv(path)
    missing_columns = [column for column in REQUIRED_COLUMNS if column not in records.columns]
    if missing_columns:
        raise ValueError(f"Missing required columns: {missing_columns}")
    records = records[REQUIRED_COLUMNS].copy()
    records.insert(0, "source_row", range(2, len(records) + 2))
    return records


def add_quality_flags(records):
    flagged = records.copy(deep=True)
    flagged["district_raw"] = flagged["district"]
    flagged["district"] = flagged["district"].astype("string").str.strip().str.title()
    for column in NUMERIC_COLUMNS:
        flagged[column] = pd.to_numeric(flagged[column], errors="coerce")

    flagged["missing_number"] = flagged[NUMERIC_COLUMNS].isna().any(axis=1)
    flagged["negative_number"] = flagged[NUMERIC_COLUMNS].lt(0).any(axis=1)
    flagged["impossible_service"] = (
        flagged["meals_served"].notna()
        & (
            (flagged["meals_served"] > flagged["pupils_present"])
            | (flagged["meals_served"] > flagged["meals_delivered"])
        )
    )
    flagged["duplicate_school_date"] = flagged.duplicated(
        ["date", "school_id"], keep=False
    )

    flagged["issue"] = ""
    flag_labels = [
        ("missing_number", "missing required number"),
        ("negative_number", "negative number"),
        ("impossible_service", "meals served exceeds limit"),
        ("duplicate_school_date", "duplicate school/date"),
    ]
    for column, label in flag_labels:
        mask = flagged[column]
        flagged.loc[mask, "issue"] = flagged.loc[mask, "issue"] + label + "; "
    flagged["issue"] = flagged["issue"].str.rstrip("; ")
    flag_columns = [column for column, _ in flag_labels]
    flagged["is_valid"] = ~flagged[flag_columns].any(axis=1)
    return flagged


def build_verification_report(flagged):
    columns = ["source_row", "date", "school_id", "school_name", "issue"]
    return flagged.loc[~flagged["is_valid"], columns].reset_index(drop=True)


def build_analysis_data(flagged):
    analysis = flagged.loc[flagged["is_valid"], REQUIRED_COLUMNS].copy()
    analysis["unmet_meals"] = analysis["pupils_present"] - analysis["meals_served"]
    return analysis.reset_index(drop=True)


def summarise_schools(analysis):
    summary = (
        analysis.groupby(["school_id", "school_name"], as_index=False)
        .agg(
            valid_days=("date", "nunique"),
            pupils_present=("pupils_present", "sum"),
            meals_served=("meals_served", "sum"),
            unmet_meals=("unmet_meals", "sum"),
            shortage_days=("unmet_meals", lambda values: (values > 0).sum()),
        )
    )
    summary["meal_coverage_rate"] = (
        summary["meals_served"] / summary["pupils_present"] * 100
    )
    summary["average_unmet_meals"] = summary["unmet_meals"] / summary["valid_days"]
    summary = summary.sort_values(
        ["average_unmet_meals", "shortage_days", "school_id"],
        ascending=[False, False, True],
    ).reset_index(drop=True)
    summary.insert(0, "priority", range(1, len(summary) + 1))
    summary["meal_coverage_rate"] = summary["meal_coverage_rate"].round(1)
    summary["average_unmet_meals"] = summary["average_unmet_meals"].round(1)
    integer_columns = [
        "valid_days", "pupils_present", "meals_served", "unmet_meals", "shortage_days"
    ]
    summary[integer_columns] = summary[integer_columns].astype(int)
    return summary


def select_first_delivery(summary):
    if summary.empty:
        raise ValueError("No valid school records")
    first = summary.iloc[0]
    return {"school_id": first["school_id"], "school_name": first["school_name"]}


def save_outputs(audit, summary, output_dir):
    output_dir = Path(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    audit.to_csv(output_dir / "records_to_verify.csv", index=False)
    summary.to_csv(output_dir / "school_delivery_summary.csv", index=False)


def run_project(input_path, output_dir):
    records = load_records(input_path)
    flagged = add_quality_flags(records)
    audit = build_verification_report(flagged)
    analysis = build_analysis_data(flagged)
    summary = summarise_schools(analysis)
    first = select_first_delivery(summary)
    save_outputs(audit, summary, output_dir)
    return {
        "source_records": len(records),
        "records_to_verify": len(audit),
        "analysis_records": len(analysis),
        "first_delivery_id": first["school_id"],
        "first_delivery_name": first["school_name"],
    }


def main():
    result = run_project(DEFAULT_INPUT, DEFAULT_OUTPUT)
    print("SCHOOL MEAL DELIVERY REVIEW")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"RECORDS TO VERIFY: {result['records_to_verify']}")
    print(f"ANALYSIS RECORDS: {result['analysis_records']}")
    print(
        f"FIRST DELIVERY: {result['first_delivery_id']} — "
        f"{result['first_delivery_name']}"
    )


if __name__ == "__main__":
    main()
'''


STARTER_CODE = r'''from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
DEFAULT_INPUT = HERE / "data" / "school-meals-practice.csv"
DEFAULT_OUTPUT = HERE / "output"
REQUIRED_COLUMNS = [
    "date", "school_id", "school_name", "district",
    "pupils_present", "meals_delivered", "meals_served",
]
NUMERIC_COLUMNS = ["pupils_present", "meals_delivered", "meals_served"]


def load_records(path):
    """Read the CSV, validate required columns, and add CSV source-row numbers."""
    # TODO 1
    raise NotImplementedError


def add_quality_flags(records):
    """Return a copy with normalised districts, four quality flags, issue, and is_valid."""
    # TODO 2: do not change records itself
    raise NotImplementedError


def build_verification_report(flagged):
    """Return invalid rows with source_row, date, school, and issue."""
    # TODO 3
    raise NotImplementedError


def build_analysis_data(flagged):
    """Keep valid rows and add unmet_meals."""
    # TODO 4
    raise NotImplementedError


def summarise_schools(analysis):
    """Aggregate, calculate rates, rank schools, and return the specified columns."""
    # TODO 5
    raise NotImplementedError


def select_first_delivery(summary):
    """Return school_id and school_name for priority 1; reject an empty summary."""
    # TODO 6
    raise NotImplementedError


def save_outputs(audit, summary, output_dir):
    """Create output_dir and save both output CSV files without indexes."""
    # TODO 7
    raise NotImplementedError


def run_project(input_path, output_dir):
    """Connect all stages and return the five report values used by main()."""
    # TODO 8
    raise NotImplementedError


def main():
    result = run_project(DEFAULT_INPUT, DEFAULT_OUTPUT)
    print("SCHOOL MEAL DELIVERY REVIEW")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"RECORDS TO VERIFY: {result['records_to_verify']}")
    print(f"ANALYSIS RECORDS: {result['analysis_records']}")
    print(
        f"FIRST DELIVERY: {result['first_delivery_id']} — "
        f"{result['first_delivery_name']}"
    )


if __name__ == "__main__":
    main()


print("PROGRAM INCOMPLETE")
'''


CHECKER_CODE = r'''from __future__ import annotations

import hashlib
import importlib.util
import subprocess
import sys
import tempfile
from pathlib import Path

import pandas as pd
from pandas.testing import assert_frame_equal


HERE = Path(__file__).resolve().parent
TARGET = HERE / "meal_delivery_review.py"
DATA = HERE / "data" / "school-meals-practice.csv"


def load_module():
    spec = importlib.util.spec_from_file_location("learner_meal_delivery_review", TARGET)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def check(name, operation):
    try:
        operation()
    except Exception as error:
        print(f"[NG] {name}")
        print(f"     {type(error).__name__}: {error}")
        return False
    print(f"[OK] {name}")
    return True


source = TARGET.read_text(encoding="utf-8")
print("School meal delivery review — automatic check")
print(f"Target: {TARGET}")
if "PROGRAM INCOMPLETE" in source or "NotImplementedError" in source:
    print("[NG] starter program is not complete")
    print("     Complete the TODOs and remove PROGRAM INCOMPLETE and unfinished errors.")
    raise SystemExit(1)

module = load_module()
records = module.load_records(DATA)
original = records.copy(deep=True)
flagged = module.add_quality_flags(records)
audit = module.build_verification_report(flagged)
analysis = module.build_analysis_data(flagged)
summary = module.summarise_schools(analysis)


def test_load():
    assert len(records) == 37
    assert records["source_row"].tolist() == list(range(2, 39))
    assert list(records.columns)[1:] == module.REQUIRED_COLUMNS


def test_preserve_and_normalise():
    assert_frame_equal(records, original)
    assert flagged["district"].isin(
        ["South", "Central", "North", "East", "West", "South East"]
    ).all()
    assert int((flagged["district_raw"] != flagged["district"]).sum()) == 4


def test_quality_flags():
    assert int(flagged["missing_number"].sum()) == 1
    assert int(flagged["negative_number"].sum()) == 0
    assert int(flagged["impossible_service"].sum()) == 1
    assert int(flagged["duplicate_school_date"].sum()) == 2


def test_audit():
    assert len(audit) == 4
    assert audit["source_row"].tolist() == [10, 18, 19, 30]
    assert list(audit.columns) == [
        "source_row", "date", "school_id", "school_name", "issue"
    ]


def test_analysis():
    assert len(analysis) == 33
    assert analysis["unmet_meals"].ge(0).all()
    assert not analysis.duplicated(["date", "school_id"], keep=False).any()


def row(school_id):
    return summary.loc[summary["school_id"] == school_id].iloc[0]


def test_summary_values():
    s004 = row("S004")
    assert int(s004["valid_days"]) == 6
    assert int(s004["pupils_present"]) == 668
    assert int(s004["meals_served"]) == 623
    assert int(s004["unmet_meals"]) == 45
    assert int(s004["shortage_days"]) == 6
    assert float(s004["meal_coverage_rate"]) == 93.3
    assert float(s004["average_unmet_meals"]) == 7.5


def test_priority():
    assert summary["school_id"].tolist()[:2] == ["S004", "S006"]
    assert summary["priority"].tolist() == list(range(1, 7))
    assert module.select_first_delivery(summary) == {
        "school_id": "S004", "school_name": "Market Road School"
    }


def test_not_fixed_to_sample():
    small = pd.DataFrame([
        {"date": "2026-01-01", "school_id": "A", "school_name": "A School", "district": " north ", "pupils_present": 10, "meals_delivered": 10, "meals_served": 8},
        {"date": "2026-01-01", "school_id": "B", "school_name": "B School", "district": "South", "pupils_present": 10, "meals_delivered": 10, "meals_served": 10},
    ])
    small.insert(0, "source_row", [2, 3])
    prepared = module.add_quality_flags(small)
    result = module.summarise_schools(module.build_analysis_data(prepared))
    assert result["school_id"].tolist() == ["A", "B"]


def test_integration_outputs():
    before = hashlib.sha256(DATA.read_bytes()).hexdigest()
    with tempfile.TemporaryDirectory() as temporary:
        output = Path(temporary)
        result = module.run_project(DATA, output)
        assert result == {
            "source_records": 37,
            "records_to_verify": 4,
            "analysis_records": 33,
            "first_delivery_id": "S004",
            "first_delivery_name": "Market Road School",
        }
        assert (output / "records_to_verify.csv").is_file()
        assert (output / "school_delivery_summary.csv").is_file()
        assert len(pd.read_csv(output / "records_to_verify.csv")) == 4
        assert len(pd.read_csv(output / "school_delivery_summary.csv")) == 6
    assert hashlib.sha256(DATA.read_bytes()).hexdigest() == before


def test_command_output():
    completed = subprocess.run(
        [sys.executable, str(TARGET)], cwd=HERE, text=True, capture_output=True
    )
    assert completed.returncode == 0, completed.stderr
    for text in [
        "SCHOOL MEAL DELIVERY REVIEW", "SOURCE RECORDS: 37",
        "RECORDS TO VERIFY: 4", "ANALYSIS RECORDS: 33",
        "FIRST DELIVERY: S004 — Market Road School",
    ]:
        assert text in completed.stdout


tests = [
    ("CSV loading and source rows", test_load),
    ("source preservation and district normalisation", test_preserve_and_normalise),
    ("four quality flags", test_quality_flags),
    ("records-to-verify report", test_audit),
    ("analysis-ready records", test_analysis),
    ("school summary values", test_summary_values),
    ("delivery priority", test_priority),
    ("functions are not fixed to the sample", test_not_fixed_to_sample),
    ("end-to-end files and source protection", test_integration_outputs),
    ("command-line report", test_command_output),
]

passed = sum(check(name, operation) for name, operation in tests)
print()
if passed == len(tests):
    print("ALL TESTS PASSED")
    print("REVIEW READY")
    raise SystemExit(0)
print(f"{passed}/{len(tests)} checks passed")
raise SystemExit(1)
'''


README_EN = r'''# Midterm choice A — School meal delivery review

Six schools submitted six days of meal records. One school can receive an additional delivery tomorrow. Separate records that are not safe to use, then rank the schools using valid records.

Edit only `meal_delivery_review.py`. Do not edit the input CSV or checker.

Run from the course-materials directory:

```text
python projects/school-meal-review/meal_delivery_review.py
python projects/school-meal-review/check_meal_delivery_review.py
```

The full public contract is maintained in the Moodle project brief. Submit only `meal_delivery_review.py`.
'''


README_JA = r'''# 中間実践課題A — 学校給食の追加配送

6校から6日分の給食記録が届きました。明日、追加配送できるのは一校です。まだ使えない記録を分けたうえで、有効な記録から配送優先順位を作ります。

編集するのは`meal_delivery_review.py`だけです。入力CSVと確認プログラムは変更しません。

教材のルートから実行します。

```text
python projects/school-meal-review/meal_delivery_review.py
python projects/school-meal-review/check_meal_delivery_review.py
```

完全な公開仕様はMoodleの課題ページに置きます。提出するのは`meal_delivery_review.py`だけです。
'''


def write(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(text, encoding="utf-8", newline="\n")


write(BASE / "project-3-midterm-design-ja.md", DESIGN)
contract = {
    "schema_version": 1,
    "project": "3A School meal delivery review",
    "position": "chapter_3_midterm_choice",
    "canonical_language": "en",
    "adaptations": ["ja"],
    "input": "projects/school-meal-review/data/school-meals-practice.csv",
    "learner_artifact": "meal_delivery_review.py",
    "outputs": ["records_to_verify.csv", "school_delivery_summary.csv"],
    "source_rows": 37,
    "verification_rows": 4,
    "analysis_rows": 33,
    "naive_first_delivery": "S002",
    "verified_first_delivery": "S004",
    "checker_cases": 10,
    "completion": ["ALL TESTS PASSED", "REVIEW READY"],
    "course_review_after_prototype": ["3.1", "3.2", "3.3", "3.4"],
}
write(BASE / "localization/project-3a-contract-v1.json", json.dumps(contract, ensure_ascii=False, indent=2) + "\n")

for language, prefix, readme in [
    ("en", Path(), README_EN),
    ("ja", Path("ja"), README_JA),
]:
    project = PROJECTS / prefix / "projects/school-meal-review"
    write(project / "meal_delivery_review.py", STARTER_CODE)
    write(project / "check_meal_delivery_review.py", CHECKER_CODE)
    write(project / "README.md", readme)
    project_data = project / "data/school-meals-practice.csv"
    project_data.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(DATASET, project_data)

write(REFERENCE / "meal_delivery_review.py", REFERENCE_CODE)
(REFERENCE / "data").mkdir(parents=True, exist_ok=True)
shutil.copyfile(DATASET, REFERENCE / "data/school-meals-practice.csv")

print(json.dumps({
    "design": str(BASE / "project-3-midterm-design-ja.md"),
    "dataset": str(DATASET),
    "projects": 2,
    "reference": str(REFERENCE / "meal_delivery_review.py"),
}, ensure_ascii=False, indent=2))
