#!/usr/bin/env python3
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
TARGET = HERE / "inspect_school_meals.py"
SOURCE = HERE / "data" / "school-meals-practice.csv"


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def load_module():
    spec = importlib.util.spec_from_file_location("learner_inspection", TARGET)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def check(name, action):
    try:
        action()
    except Exception as error:
        print(f"[NG] {name}: {type(error).__name__}: {error}")
        return False
    print(f"[OK] {name}")
    return True


def main():
    print("School meal source inspection — automatic check")
    if not TARGET.is_file():
        raise SystemExit(f"Missing: {TARGET}")
    module = load_module()
    raw = pd.read_csv(SOURCE)
    before = digest(SOURCE)

    def source_loading():
        actual = module.load_records(SOURCE)
        assert_frame_equal(actual, raw)
        assert digest(SOURCE) == before

    def alternate_loading():
        alternate = raw.iloc[:3].copy()
        alternate.loc[:, "school_id"] = ["T101", "T102", "T103"]
        with tempfile.TemporaryDirectory() as temporary:
            path = Path(temporary) / "alternate.csv"
            alternate.to_csv(path, index=False)
            actual = module.load_records(path)
            assert_frame_equal(actual, pd.read_csv(path))

    def sorted_view():
        original = raw.copy(deep=True)
        actual = module.build_school_date_view(raw)
        expected = raw.sort_values(["school_id", "date"], kind="stable").reset_index(drop=True)
        assert_frame_equal(actual.reset_index(drop=True), expected)
        assert_frame_equal(raw, original)
        assert actual is not raw

    def district_counts():
        actual = module.count_district_values(raw)
        counts = raw["district"].value_counts(dropna=False, sort=False)
        expected = counts.rename_axis("district").reset_index(name="records")
        assert_frame_equal(actual, expected)

    def command_output():
        result = subprocess.run([sys.executable, str(TARGET)], cwd=HERE, text=True, capture_output=True, timeout=30)
        assert result.returncode == 0, result.stderr
        for token in ["ROWS: 37", "COLUMNS: 7", "COLUMN NAMES:", "INFERRED DTYPES:", "ALL RECORDS:", "SCHOOL/DATE VIEW:", "MISSING VALUES:", "DISTRICT VALUES:"]:
            assert token in result.stdout, f"missing output label: {token}"
        def section_lines(start, end):
            section = result.stdout.split(start, 1)[1].split(end, 1)[0]
            return [line for line in section.splitlines() if line.strip()]

        all_rows = section_lines("ALL RECORDS:\n", "SCHOOL/DATE VIEW:\n")
        sorted_rows = section_lines("SCHOOL/DATE VIEW:\n", "MISSING VALUES:\n")
        assert len(all_rows) == len(raw) + 1, f"ALL RECORDS has {len(all_rows) - 1} data rows"
        assert len(sorted_rows) == len(raw) + 1, f"SCHOOL/DATE VIEW has {len(sorted_rows) - 1} data rows"
        assert not any("..." in line for line in all_rows + sorted_rows), "a table was truncated"
        for school_id in raw["school_id"].unique():
            assert school_id in result.stdout
        whitespace_value = next(value for value in raw["district"] if value != value.strip())
        assert repr(whitespace_value) in result.stdout, "district whitespace is not visible"
        assert digest(SOURCE) == before

    checks = [
        ("CSV loading and source protection", source_loading),
        ("not fixed to the 37 sample rows", alternate_loading),
        ("school/date inspection view", sorted_view),
        ("raw district value counts", district_counts),
        ("complete command-line inspection", command_output),
    ]
    passed = sum(check(name, action) for name, action in checks)
    if passed != len(checks):
        raise SystemExit(f"{passed}/{len(checks)} checks passed")
    print("\nALL INSPECTION CHECKS PASSED")
    print("STAGE 1 COMPLETE")


if __name__ == "__main__":
    main()
