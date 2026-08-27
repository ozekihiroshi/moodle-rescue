from __future__ import annotations

import hashlib
import importlib.util
import subprocess
import sys
import tempfile
from pathlib import Path

import pandas as pd


PROJECT = Path(__file__).resolve().parent
PROGRAM = PROJECT / "clinic_stock_scaleup.py"
GENERATOR = PROJECT / "generate_clinic_stock_data.py"


def digest(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def load_program():
    spec = importlib.util.spec_from_file_location("clinic_stock_scaleup", PROGRAM)
    module = importlib.util.module_from_spec(spec)
    assert spec.loader is not None
    spec.loader.exec_module(module)
    return module


def check(condition: bool, message: str) -> None:
    if not condition:
        raise AssertionError(message)


def main() -> None:
    source_text = PROGRAM.read_text(encoding="utf-8")
    if "raise NotImplementedError" in source_text:
        print("[NG] starter program is not complete")
        print("Complete every TODO function before running the checker.")
        raise SystemExit(1)

    module = load_program()
    expected_functions = [
        "required_columns", "validate_schema", "prepare_chunk", "update_totals",
        "build_summary", "select_priority", "process_file", "save_outputs", "run_project",
    ]
    for name in expected_functions:
        check(callable(getattr(module, name, None)), f"missing function: {name}")
    print("[OK] public functions")

    with tempfile.TemporaryDirectory(prefix="clinic-stock-check-") as temporary:
        root = Path(temporary)
        source = root / "source.csv"
        subprocess.run([sys.executable, str(GENERATOR), str(source), "--rows", "12000"], check=True, capture_output=True, text=True)
        before = digest(source)

        first = module.process_file(source, chunksize=997)
        second = module.process_file(source, chunksize=2048)
        check(first["source_records"] == 12000, "source row count is incorrect")
        check(first["reconciled"] is True, "source, analysis, and review counts do not reconcile")
        check(first["analysis_records"] + first["review_records"] == 12000, "row accounting is incomplete")
        pd.testing.assert_frame_equal(first["summary"], second["summary"], check_dtype=False, atol=1e-9)
        check(first["review_records"] > 0, "invalid rows were not sent for review")
        print("[OK] chunk-size-independent processing and reconciliation")

        sample = pd.DataFrame([
            {"date": "2026-01-01", "clinic_id": "C1", "clinic_name": "One", "district": "North", "medicine": "Insulin", "opening_units": 10, "received_units": 2, "dispensed_units": 8, "closing_units": 4, "stockout_hours": 3, "patients_turned_away": 7},
            {"date": "2026-01-02", "clinic_id": "C1", "clinic_name": "One", "district": "North", "medicine": "Insulin", "opening_units": 8, "received_units": 2, "dispensed_units": 6, "closing_units": 99, "stockout_hours": 4, "patients_turned_away": 8},
            {"date": "2026-01-03", "clinic_id": "C2", "clinic_name": "Two", "district": "South", "medicine": "Antibiotics", "opening_units": 9, "received_units": 0, "dispensed_units": 9, "closing_units": 0, "stockout_hours": 2, "patients_turned_away": 4},
        ])
        original = sample.copy(deep=True)
        valid, review = module.prepare_chunk(sample)
        pd.testing.assert_frame_equal(sample, original)
        check(len(valid) == 2 and len(review) == 1, "prepare_chunk did not separate the known fixture")
        check("inventory_balance" in review.iloc[0]["issue_reason"], "review reason is missing")
        print("[OK] validation on a manually checkable fixture")

        output = root / "output"
        result = module.run_project(source, output, chunksize=1500)
        check(result["reconciled"] is True, "run_project did not reconcile")
        check((output / "clinic_stock_summary.csv").is_file(), "summary CSV was not saved")
        check((output / "clinic_stock_evidence.png").is_file(), "evidence PNG was not saved")
        saved = pd.read_csv(output / "clinic_stock_summary.csv")
        check(list(saved.columns) == ["district", "medicine", "clinic_days", "stockout_days", "stockout_hours", "patients_turned_away", "stockout_rate"], "saved columns are incorrect")
        check(digest(source) == before, "source CSV was modified")
        print("[OK] outputs and source protection")

    print("ALL TESTS PASSED")
    print("SCALE-UP VERIFIED")


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        print(f"[NG] {error}")
        raise SystemExit(1)
