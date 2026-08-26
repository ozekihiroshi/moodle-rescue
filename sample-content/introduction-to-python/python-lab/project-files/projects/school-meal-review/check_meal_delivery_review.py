from __future__ import annotations

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
