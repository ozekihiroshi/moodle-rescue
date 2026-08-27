from pathlib import Path
import importlib.util
import subprocess
import sys
import tempfile

import pandas as pd


HERE = Path(__file__).resolve().parent
TARGET = HERE / "clinic_wait_evidence.py"
SOURCE = HERE / "data" / "clinic-waits-practice.csv"


def load_module():
    spec = importlib.util.spec_from_file_location("clinic_wait_evidence", TARGET)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def check(name, action):
    try:
        action()
    except Exception as error:
        print(f"[NG] {name}: {error}")
        raise
    print(f"[OK] {name}")


def main():
    source_text = TARGET.read_text(encoding="utf-8")
    if "NotImplementedError" in source_text:
        print("[NG] starter program is not complete")
        print("Complete each published function and remove its NotImplementedError.")
        raise SystemExit(1)

    module = load_module()
    source_before = SOURCE.read_bytes()

    def loading_and_validation():
        records = module.load_records(SOURCE)
        assert records.columns.tolist() == module.REQUIRED_COLUMNS
        assert len(records) == 36
        module.validate_records(records)
        invalid = records.copy()
        invalid.loc[0, "over_60_minutes"] = invalid.loc[0, "patients_seen"] + 1
        try:
            module.validate_records(invalid)
        except ValueError:
            pass
        else:
            raise AssertionError("impossible over-60 count was accepted")

    def summaries_and_targets():
        records = module.load_records(SOURCE)
        burden = module.build_burden_summary(records)
        service = module.build_service_summary(records)
        assert len(burden) == 3
        assert len(service) == 6
        assert burden.iloc[0]["clinic_id"] == "C001"
        assert int(burden.iloc[0]["total_wait_minutes"]) == 22972
        assert service.iloc[0]["clinic_id"] == "C002"
        assert service.iloc[0]["time_slot"] == "Evening"
        targets = module.choose_targets(burden, service)
        assert targets["burden_clinic_id"] == "C001"
        assert targets["support_clinic_id"] == "C002"
        assert targets["support_time_slot"] == "Evening"
        assert round(targets["support_average_wait"], 1) == 48.1
        assert round(targets["support_over_60_rate"], 1) == 32.4

    def alternate_table():
        records = pd.DataFrame([
            ["W1", "A", "Alpha", "Morning", 10, 100, 1],
            ["W1", "A", "Alpha", "Evening", 10, 400, 5],
            ["W1", "B", "Beta", "Morning", 5, 300, 4],
            ["W1", "B", "Beta", "Evening", 5, 100, 1],
        ], columns=module.REQUIRED_COLUMNS)
        module.validate_records(records)
        burden = module.build_burden_summary(records)
        service = module.build_service_summary(records)
        targets = module.choose_targets(burden, service)
        assert targets["burden_clinic_id"] == "A"
        assert targets["support_clinic_id"] == "B"
        assert targets["support_time_slot"] == "Morning"

    def evidence_and_outputs():
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            summary_path = root / "output" / "summary.csv"
            figure_path = root / "output" / "evidence.png"
            result = module.run_project(SOURCE, summary_path, figure_path)
            assert summary_path.is_file()
            assert figure_path.is_file() and figure_path.stat().st_size > 10000
            saved = pd.read_csv(summary_path)
            assert saved.iloc[0]["clinic_id"] == "C002"
            assert saved.iloc[0]["time_slot"] == "Evening"
            note = result["evidence_note"]
            assert "22972" in note and "48.1" in note and "32.4%" in note
            assert "do not establish its cause" in note
            assert SOURCE.read_bytes() == source_before

    def command_line_contract():
        completed = subprocess.run(
            [sys.executable, str(TARGET)], cwd=HERE, text=True, capture_output=True
        )
        assert completed.returncode == 0, completed.stderr
        expected = [
            "SOURCE RECORDS: 36",
            "TOTAL BURDEN CLINIC: C001 — Central Clinic",
            "SUPPORT TARGET: C002 — Riverside Clinic — Evening",
            "TARGET AVERAGE WAIT: 48.1 MINUTES",
            "TARGET OVER-60 RATE: 32.4%",
        ]
        for line in expected:
            assert line in completed.stdout, line

    check("loading and validation", loading_and_validation)
    check("summaries and target selection", summaries_and_targets)
    check("functions work with another table", alternate_table)
    check("evidence text, saved CSV, PNG, and source protection", evidence_and_outputs)
    check("command-line checkpoints", command_line_contract)
    print("ALL TESTS PASSED")
    print("EVIDENCE READY")


if __name__ == "__main__":
    main()
