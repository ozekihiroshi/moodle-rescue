from __future__ import annotations

import ast
import json
import shutil
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "sample-content" / "introduction-to-python"
PROJECTS = CONTENT / "python-lab" / "project-files" / "projects"
JA_PROJECTS = CONTENT / "python-lab" / "project-files" / "ja" / "projects"
LAB = ROOT.parent / "python-lab-rescue" / "course-materials"


CONFIGS = {
    "bus-service-review": {
        "title": "Public bus service review",
        "title_ja": "公共バスの改善調査",
        "inspect": "inspect_bus_service.py",
        "program": "bus_service_review.py",
        "checker": "check_bus_service_review.py",
        "inspect_checker": "check_inspect_bus_service.py",
        "data": "bus-service-practice.csv",
        "key": "route_id",
        "name": "route_name",
        "sort_label": "route/date",
        "summary_function": "summarise_routes",
        "select_function": "select_first_review",
        "first_key": "first_review_id",
        "first_name_key": "first_review_name",
        "first_id": "R002",
        "first_name": "Market Loop",
        "invalid": 4,
        "valid": 27,
        "flags": {
            "missing_number": 1,
            "negative_number": 0,
            "impossible_trips": 1,
            "passengers_without_trip": 0,
            "duplicate_route_date": 2,
        },
        "audit_columns": ["source_row", "date", "route_id", "route_name", "issue"],
        "output": "route_review_summary.csv",
        "heading": "BUS SERVICE REVIEW",
        "first_label": "FIRST REVIEW",
        "first_checks": {"valid_days": 6, "passenger_delay_minutes": 25920.0, "average_delay_minutes": 6.0},
        "story": "The transport team can investigate one route first. Separate unreliable daily records, then rank routes by estimated passenger-delay minutes rather than delay alone.",
        "story_ja": "交通担当部署は最初に一路線だけ改善調査できます。信頼できない日次記録を分け、遅延時間だけでなく乗客への遅延影響から調査順位を作ります。",
        "discovery": "The longest average delay is R003, but the largest passenger impact is R002.",
        "discovery_ja": "一便平均遅延が最長なのはR003ですが、乗客への遅延影響が最大なのはR002です。",
    },
    "water-point-review": {
        "title": "Community water-point inspection",
        "title_ja": "地域給水設備の点検",
        "inspect": "inspect_water_points.py",
        "program": "water_point_review.py",
        "checker": "check_water_point_review.py",
        "inspect_checker": "check_inspect_water_points.py",
        "data": "water-points-practice.csv",
        "key": "facility_id",
        "name": "facility_name",
        "sort_label": "facility/date",
        "summary_function": "summarise_facilities",
        "select_function": "select_first_inspection",
        "first_key": "first_inspection_id",
        "first_name_key": "first_inspection_name",
        "first_id": "W004",
        "first_name": "East Market Water Point",
        "invalid": 5,
        "valid": 26,
        "flags": {
            "missing_number": 1,
            "negative_number": 0,
            "impossible_output": 1,
            "sensor_not_ok": 1,
            "duplicate_facility_date": 2,
        },
        "audit_columns": ["source_row", "date", "facility_id", "facility_name", "issue"],
        "output": "facility_inspection_summary.csv",
        "heading": "WATER POINT REVIEW",
        "first_label": "FIRST INSPECTION",
        "first_checks": {"valid_days": 6, "stopped_days": 2, "low_output_days": 2, "output_rate": 81.2},
        "story": "The maintenance team can visit one facility first. Separate failed-sensor and contradictory records, then rank repeated stoppages and low-output days.",
        "story_ja": "保守チームは最初に一施設だけ現地点検できます。故障センサーや矛盾した記録を分け、停止の継続と低出力日から点検順位を作ります。",
        "discovery": "The raw minimum includes a failed sensor; the valid-record priority is W004.",
        "discovery_ja": "原資料の最小値には故障センサーが含まれ、信頼できる記録からの優先施設はW004です。",
    },
}


def starter_from_reference(source: str) -> str:
    tree = ast.parse(source)
    functions = {
        node.name: node
        for node in tree.body
        if isinstance(node, (ast.FunctionDef, ast.AsyncFunctionDef))
        and node.name not in {"main"}
    }
    lines = source.splitlines()
    for number, node in enumerate(sorted(functions.values(), key=lambda item: item.lineno, reverse=True), start=1):
        indentation = " " * (node.col_offset + 4)
        replacement = [
            f'{indentation}"""TODO: implement this published function contract."""',
            f'{indentation}raise NotImplementedError("TODO")',
        ]
        lines[node.body[0].lineno - 1 : node.end_lineno] = replacement
    marker = "\n# PROGRAM INCOMPLETE: complete every TODO, then remove this line.\n"
    insert_at = next(index for index, line in enumerate(lines) if line.startswith("def main"))
    lines[insert_at:insert_at] = marker.strip("\n").splitlines() + [""]
    return "\n".join(lines) + "\n"


def inspection_program(config: dict) -> str:
    key = config["key"]
    return f'''from __future__ import annotations

from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
INPUT_FILE = HERE / "data" / "{config['data']}"


def load_records(path):
    """Read the supplied CSV and return its DataFrame."""
    raise NotImplementedError("TODO 1: read and return the CSV")


def build_key_date_view(records):
    """Return a new {key}/date-sorted DataFrame without changing records."""
    raise NotImplementedError("TODO 2: sort a copy by {key} and date")


def count_raw_values(records, column):
    """Return value, records columns in first-appearance order without cleaning."""
    raise NotImplementedError("TODO 3: count the raw values")


# PROGRAM INCOMPLETE: complete the three TODOs, then remove this line.


def main():
    records = load_records(INPUT_FILE)
    ordered = build_key_date_view(records)
    print("SOURCE SHAPE:", records.shape)
    print("COLUMNS:", records.columns.tolist())
    print("DTYPES:")
    print(records.dtypes.to_string())
    print("\\nALL SOURCE RECORDS:")
    print(records.to_string(index=False))
    print("\\n{config['sort_label'].upper()} VIEW:")
    print(ordered.to_string(index=False))
    print("\\nMISSING VALUES:")
    print(records.isna().sum().to_string())
    print("\\nRAW DISTRICT VALUES:")
    print(count_raw_values(records, "district").to_string(index=False))
    if "sensor_status" in records.columns:
        print("\\nRAW SENSOR STATUS VALUES:")
        print(count_raw_values(records, "sensor_status").to_string(index=False))


if __name__ == "__main__":
    main()
'''


def inspection_checker(config: dict) -> str:
    return f'''from __future__ import annotations

import hashlib
import importlib.util
from pathlib import Path

import pandas as pd
from pandas.testing import assert_frame_equal

HERE = Path(__file__).resolve().parent
TARGET = HERE / "{config['inspect']}"
DATA = HERE / "data" / "{config['data']}"


def load_module():
    spec = importlib.util.spec_from_file_location("learner_inspection", TARGET)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def check(name, operation):
    try:
        operation()
    except Exception as error:
        print(f"[NG] {{name}}")
        print(f"     {{type(error).__name__}}: {{error}}")
        return False
    print(f"[OK] {{name}}")
    return True


source = TARGET.read_text(encoding="utf-8")
if "PROGRAM INCOMPLETE" in source or "NotImplementedError" in source:
    print("[NG] source-inspection starter is not complete")
    raise SystemExit(1)

module = load_module()
before = hashlib.sha256(DATA.read_bytes()).hexdigest()
records = module.load_records(DATA)
original = records.copy(deep=True)


def test_load():
    assert isinstance(records, pd.DataFrame)
    assert len(records) == 31
    assert list(records.columns)[0] == "date"


def test_sorted_copy():
    ordered = module.build_key_date_view(records)
    assert_frame_equal(records, original)
    expected = records.sort_values(["{config['key']}", "date"]).reset_index(drop=True)
    assert_frame_equal(ordered, expected)


def test_raw_counts():
    counts = module.count_raw_values(records, "district")
    assert list(counts.columns) == ["value", "records"]
    assert int(counts["records"].sum()) == len(records)
    assert counts["value"].tolist()[0] == records["district"].iloc[0]
    if "sensor_status" in records.columns:
        sensor = module.count_raw_values(records, "sensor_status")
        assert int(sensor["records"].sum()) == len(records)


def test_other_data():
    sample = pd.DataFrame([
        {{"date": "2026-01-02", "{config['key']}": "B", "district": " North "}},
        {{"date": "2026-01-01", "{config['key']}": "A", "district": "North"}},
    ])
    view = module.build_key_date_view(sample)
    assert view["{config['key']}"].tolist() == ["A", "B"]
    assert module.count_raw_values(sample, "district")["value"].tolist() == [" North ", "North"]


tests = [
    ("CSV loading", test_load),
    ("sorted copy and source preservation", test_sorted_copy),
    ("raw value counts", test_raw_counts),
    ("functions work with another table", test_other_data),
]
passed = sum(check(name, operation) for name, operation in tests)
assert hashlib.sha256(DATA.read_bytes()).hexdigest() == before
if passed == len(tests):
    print("ALL INSPECTION TESTS PASSED")
    raise SystemExit(0)
raise SystemExit(1)
'''


def production_checker(config: dict) -> str:
    flag_assertions = "\n".join(
        f'    assert int(flagged["{flag}"].sum()) == {count}'
        for flag, count in config["flags"].items()
    )
    first_assertions = "\n".join(
        f'    assert float(first["{column}"]) == {value}'
        for column, value in config["first_checks"].items()
    )
    return f'''from __future__ import annotations

import hashlib
import importlib.util
import subprocess
import sys
import tempfile
from pathlib import Path

from pandas.testing import assert_frame_equal

HERE = Path(__file__).resolve().parent
TARGET = HERE / "{config['program']}"
DATA = HERE / "data" / "{config['data']}"


def load_module():
    spec = importlib.util.spec_from_file_location("learner_review", TARGET)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def check(name, operation):
    try:
        operation()
    except Exception as error:
        print(f"[NG] {{name}}")
        print(f"     {{type(error).__name__}}: {{error}}")
        return False
    print(f"[OK] {{name}}")
    return True


source = TARGET.read_text(encoding="utf-8")
if "PROGRAM INCOMPLETE" in source or "NotImplementedError" in source:
    print("[NG] production starter is not complete")
    raise SystemExit(1)

module = load_module()
records = module.load_records(DATA)
original = records.copy(deep=True)
flagged = module.add_quality_flags(records)
audit = module.build_verification_report(flagged)
analysis = module.build_analysis_data(flagged)
summary = module.{config['summary_function']}(analysis)


def test_load_and_preserve():
    assert len(records) == 31
    assert records["source_row"].tolist() == list(range(2, 33))
    assert_frame_equal(records, original)


def test_flags():
{flag_assertions}
    assert int((~flagged["is_valid"]).sum()) == {config['invalid']}


def test_audit_and_analysis():
    assert len(audit) == {config['invalid']}
    assert list(audit.columns) == {config['audit_columns']!r}
    assert len(analysis) == {config['valid']}


def test_summary_and_priority():
    first = summary.iloc[0]
    assert first["{config['key']}"] == "{config['first_id']}"
    assert first["{config['name']}"] == "{config['first_name']}"
{first_assertions}
    assert summary["priority"].tolist() == list(range(1, len(summary) + 1))


def test_integration():
    before = hashlib.sha256(DATA.read_bytes()).hexdigest()
    with tempfile.TemporaryDirectory() as temporary:
        result = module.run_project(DATA, Path(temporary))
        assert result == {{
            "source_records": 31,
            "records_to_verify": {config['invalid']},
            "analysis_records": {config['valid']},
            "{config['first_key']}": "{config['first_id']}",
            "{config['first_name_key']}": "{config['first_name']}",
        }}
        assert (Path(temporary) / "records_to_verify.csv").is_file()
        assert (Path(temporary) / "{config['output']}").is_file()
    assert hashlib.sha256(DATA.read_bytes()).hexdigest() == before


def test_command_output():
    completed = subprocess.run([sys.executable, str(TARGET)], cwd=HERE, text=True, capture_output=True)
    assert completed.returncode == 0, completed.stderr
    for expected in [
        "{config['heading']}", "SOURCE RECORDS: 31",
        "RECORDS TO VERIFY: {config['invalid']}", "ANALYSIS RECORDS: {config['valid']}",
        "{config['first_label']}: {config['first_id']} — {config['first_name']}",
    ]:
        assert expected in completed.stdout


tests = [
    ("loading and source preservation", test_load_and_preserve),
    ("published quality flags", test_flags),
    ("audit and analysis separation", test_audit_and_analysis),
    ("summary and priority", test_summary_and_priority),
    ("outputs and source protection", test_integration),
    ("command-line checkpoints", test_command_output),
]
passed = sum(check(name, operation) for name, operation in tests)
if passed == len(tests):
    print("ALL TESTS PASSED")
    print("REVIEW READY")
    raise SystemExit(0)
raise SystemExit(1)
'''


def brief(config: dict, ja: bool) -> str:
    title = config["title_ja"] if ja else config["title"]
    story = config["story_ja"] if ja else config["story"]
    discovery = config["discovery_ja"] if ja else config["discovery"]
    if ja:
        return f'''# 第3章 中間実践課題 — {title}

## 課題の状況

{story}

## 二つのプログラム

空のファイルから作りません。Python Labの`projects/{next(k for k,v in CONFIGS.items() if v is config)}/`にあるスターターを完成させます。

1. `{config['inspect']}`（20点）：原資料31件を読み、全件、{config['sort_label']}順、型、欠損、原文のカテゴリ値を表示します。
2. `{config['program']}`（80点）：公開された品質規則、確認対象の分離、集計、順位付け、CSV保存を行います。

編集・提出するのはこの2ファイルです。入力CSV、確認プログラム、Notebookは変更・提出しません。

## 入力と作業順

入力は`data/{config['data']}`です。一行は一対象の一日分の記録です。原本を変更せず、最初に第1段階を完成させて自分の目で全件を確認してから、第2段階へ進みます。

```text
原資料を読む → 全件と並べ替え表示を確認 → 第1段階のテスト
→ 品質フラグ → 確認対象と分析対象を分離 → 集計と順位
→ CSVを確認 → 第2段階のテスト → 2ファイルを提出
```

## 公開チェックポイント

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: {config['invalid']}
ANALYSIS RECORDS: {config['valid']}
{config['first_label']}: {config['first_id']} — {config['first_name']}
```

{discovery}

第2段階では、`load_records`、`add_quality_flags`、`build_verification_report`、`build_analysis_data`、集計、優先対象選択、保存、`run_project`の8関数を完成させます。関数名、引数、定数、完成済み`main()`は変更しません。

## 確認と完成条件

```text
python projects/{next(k for k,v in CONFIGS.items() if v is config)}/{config['inspect']}
python projects/{next(k for k,v in CONFIGS.items() if v is config)}/{config['inspect_checker']}
python projects/{next(k for k,v in CONFIGS.items() if v is config)}/{config['program']}
python projects/{next(k for k,v in CONFIGS.items() if v is config)}/{config['checker']}
```

両方の確認プログラムへ合格し、最後に`ALL TESTS PASSED`と`REVIEW READY`が表示されたら完成です。
'''
    return f'''# Chapter 3 midterm practical project — {title}

## Situation

{story}

## Two programs

Do not start from empty files. Complete the starters in `projects/{next(k for k,v in CONFIGS.items() if v is config)}/`.

1. `{config['inspect']}` (20 points) reads all 31 source records and displays the full table, the {config['sort_label']} view, dtypes, missing counts, and raw category values.
2. `{config['program']}` (80 points) applies the published quality rules, separates review records, aggregates valid records, ranks the result, and saves CSV evidence.

Edit and submit these two files only. Do not change or submit the source CSV, checkers, or Notebook.

## Input and work order

The source is `data/{config['data']}`. One row is one operational unit's record for one date. Do not change the source. Finish and inspect Stage 1 before implementing Stage 2.

```text
read source → view all and sorted records → pass Stage 1
→ create quality flags → separate review and analysis records
→ aggregate and rank → inspect saved CSVs → pass Stage 2 → submit two files
```

## Published checkpoints

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: {config['invalid']}
ANALYSIS RECORDS: {config['valid']}
{config['first_label']}: {config['first_id']} — {config['first_name']}
```

{discovery}

Stage 2 implements eight supplied functions: loading, quality flags, verification report, analysis data, summary, priority selection, saving, and `run_project`. Do not change their names, parameters, constants, or the completed `main()`.

## Checking and completion

```text
python projects/{next(k for k,v in CONFIGS.items() if v is config)}/{config['inspect']}
python projects/{next(k for k,v in CONFIGS.items() if v is config)}/{config['inspect_checker']}
python projects/{next(k for k,v in CONFIGS.items() if v is config)}/{config['program']}
python projects/{next(k for k,v in CONFIGS.items() if v is config)}/{config['checker']}
```

The project is complete when both checkers pass and Stage 2 prints `ALL TESTS PASSED` and `REVIEW READY`.
'''


def notebook(config: dict, ja: bool) -> dict:
    slug = next(key for key, value in CONFIGS.items() if value is config)
    title = config["title_ja"] if ja else config["title"]
    guidance = (
        "Notebookは提出物ではありません。二つの.pyファイルを保存してから実行します。"
        if ja else
        "The Notebook is not submitted. Save each .py file before running it."
    )
    commands = [
        f"!python projects/{slug}/{config['inspect']}",
        f"!python projects/{slug}/{config['inspect_checker']}",
        f"!python projects/{slug}/{config['program']}",
        f"!python projects/{slug}/{config['checker']}",
    ]
    cells = [
        {"cell_type": "markdown", "metadata": {}, "source": [f"# {title}\n", guidance]},
        {"cell_type": "markdown", "metadata": {}, "source": [config["story_ja"] if ja else config["story"]]},
    ]
    labels = ["Stage 1: display the source", "Check Stage 1", "Stage 2: run the review", "Check Stage 2"]
    if ja:
        labels = ["第1段階：原資料を表示", "第1段階を確認", "第2段階：本番処理を実行", "第2段階を確認"]
    for label, command in zip(labels, commands):
        cells.append({"cell_type": "markdown", "metadata": {}, "source": [f"## {label}"]})
        cells.append({"cell_type": "code", "execution_count": None, "metadata": {}, "outputs": [], "source": [command]})
    language = "ja" if ja else "en"
    for index, cell in enumerate(cells, start=1):
        cell["id"] = f"{language}-{slug}-{index:02d}"
    return {
        "cells": cells,
        "metadata": {"kernelspec": {"display_name": "Python 3", "language": "python", "name": "python3"}, "language_info": {"name": "python", "version": "3"}},
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def build() -> None:
    for slug, config in CONFIGS.items():
        canonical = PROJECTS / slug
        reference_name = "project-3b" if slug == "bus-service-review" else "project-3c"
        reference = CONTENT / "reference-solutions" / reference_name / config["program"]
        source = reference.read_text(encoding="utf-8")
        canonical.mkdir(parents=True, exist_ok=True)
        (canonical / config["program"]).write_text(starter_from_reference(source), encoding="utf-8")
        (canonical / config["inspect"]).write_text(inspection_program(config), encoding="utf-8")
        (canonical / config["inspect_checker"]).write_text(inspection_checker(config), encoding="utf-8")
        (canonical / config["checker"]).write_text(production_checker(config), encoding="utf-8")
        (canonical / "PROJECT_BRIEF.md").write_text(brief(config, False), encoding="utf-8")
        (canonical / "README.md").write_text(brief(config, False), encoding="utf-8")

        adapted = JA_PROJECTS / slug
        if adapted.exists():
            shutil.rmtree(adapted)
        shutil.copytree(canonical, adapted)
        (adapted / "PROJECT_BRIEF.md").write_text(brief(config, True), encoding="utf-8")
        (adapted / "README.md").write_text(brief(config, True), encoding="utf-8")

        notebook_name = "P3B_bus_service_review.ipynb" if slug == "bus-service-review" else "P3C_water_point_review.ipynb"
        template_root = CONTENT / "python-lab" / "templates"
        (template_root / notebook_name).write_text(json.dumps(notebook(config, False), ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        (template_root / "ja").mkdir(parents=True, exist_ok=True)
        (template_root / "ja" / notebook_name).write_text(json.dumps(notebook(config, True), ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

        for language, destination in [("en", LAB), ("ja", LAB / "ja")]:
            project_destination = destination / "projects" / slug
            if project_destination.exists():
                shutil.rmtree(project_destination)
            shutil.copytree(canonical if language == "en" else adapted, project_destination)
            notebook_source = template_root / ("ja" if language == "ja" else "") / notebook_name
            shutil.copy2(notebook_source, destination / notebook_name)

        print(f"built {slug}")


if __name__ == "__main__":
    build()
