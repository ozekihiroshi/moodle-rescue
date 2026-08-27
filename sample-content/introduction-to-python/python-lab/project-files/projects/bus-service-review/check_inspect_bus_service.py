from __future__ import annotations

import hashlib
import importlib.util
from pathlib import Path

import pandas as pd
from pandas.testing import assert_frame_equal

HERE = Path(__file__).resolve().parent
TARGET = HERE / "inspect_bus_service.py"
DATA = HERE / "data" / "bus-service-practice.csv"


def load_module():
    spec = importlib.util.spec_from_file_location("learner_inspection", TARGET)
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
    expected = records.sort_values(["route_id", "date"]).reset_index(drop=True)
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
        {"date": "2026-01-02", "route_id": "B", "district": " North "},
        {"date": "2026-01-01", "route_id": "A", "district": "North"},
    ])
    view = module.build_key_date_view(sample)
    assert view["route_id"].tolist() == ["A", "B"]
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
