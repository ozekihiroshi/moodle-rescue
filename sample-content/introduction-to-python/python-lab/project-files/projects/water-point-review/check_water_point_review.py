from __future__ import annotations

import hashlib
import importlib.util
import subprocess
import sys
import tempfile
from pathlib import Path

from pandas.testing import assert_frame_equal

HERE = Path(__file__).resolve().parent
TARGET = HERE / "water_point_review.py"
DATA = HERE / "data" / "water-points-practice.csv"


def load_module():
    spec = importlib.util.spec_from_file_location("learner_review", TARGET)
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
if "PROGRAM INCOMPLETE" in source or "NotImplementedError" in source:
    print("[NG] production starter is not complete")
    raise SystemExit(1)

module = load_module()
records = module.load_records(DATA)
original = records.copy(deep=True)
flagged = module.add_quality_flags(records)
audit = module.build_verification_report(flagged)
analysis = module.build_analysis_data(flagged)
summary = module.summarise_facilities(analysis)


def test_load_and_preserve():
    assert len(records) == 31
    assert records["source_row"].tolist() == list(range(2, 33))
    assert_frame_equal(records, original)


def test_flags():
    assert int(flagged["missing_number"].sum()) == 1
    assert int(flagged["negative_number"].sum()) == 0
    assert int(flagged["impossible_output"].sum()) == 1
    assert int(flagged["sensor_not_ok"].sum()) == 1
    assert int(flagged["duplicate_facility_date"].sum()) == 2
    assert int((~flagged["is_valid"]).sum()) == 5


def test_audit_and_analysis():
    assert len(audit) == 5
    assert list(audit.columns) == ['source_row', 'date', 'facility_id', 'facility_name', 'issue']
    assert len(analysis) == 26


def test_summary_and_priority():
    first = summary.iloc[0]
    assert first["facility_id"] == "W004"
    assert first["facility_name"] == "East Market Water Point"
    assert float(first["valid_days"]) == 6
    assert float(first["stopped_days"]) == 2
    assert float(first["low_output_days"]) == 2
    assert float(first["output_rate"]) == 81.2
    assert summary["priority"].tolist() == list(range(1, len(summary) + 1))


def test_integration():
    before = hashlib.sha256(DATA.read_bytes()).hexdigest()
    with tempfile.TemporaryDirectory() as temporary:
        result = module.run_project(DATA, Path(temporary))
        assert result == {
            "source_records": 31,
            "records_to_verify": 5,
            "analysis_records": 26,
            "first_inspection_id": "W004",
            "first_inspection_name": "East Market Water Point",
        }
        assert (Path(temporary) / "records_to_verify.csv").is_file()
        assert (Path(temporary) / "facility_inspection_summary.csv").is_file()
    assert hashlib.sha256(DATA.read_bytes()).hexdigest() == before


def test_command_output():
    completed = subprocess.run([sys.executable, str(TARGET)], cwd=HERE, text=True, capture_output=True)
    assert completed.returncode == 0, completed.stderr
    for expected in [
        "WATER POINT REVIEW", "SOURCE RECORDS: 31",
        "RECORDS TO VERIFY: 5", "ANALYSIS RECORDS: 26",
        "FIRST INSPECTION: W004 — East Market Water Point",
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
