from __future__ import annotations

from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
INPUT_FILE = HERE / "data" / "water-points-practice.csv"


def load_records(path):
    """Read the supplied CSV and return its DataFrame."""
    raise NotImplementedError("TODO 1: read and return the CSV")


def build_key_date_view(records):
    """Return a new facility_id/date-sorted DataFrame without changing records."""
    raise NotImplementedError("TODO 2: sort a copy by facility_id and date")


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
    print("\nALL SOURCE RECORDS:")
    print(records.to_string(index=False))
    print("\nFACILITY/DATE VIEW:")
    print(ordered.to_string(index=False))
    print("\nMISSING VALUES:")
    print(records.isna().sum().to_string())
    print("\nRAW DISTRICT VALUES:")
    print(count_raw_values(records, "district").to_string(index=False))
    if "sensor_status" in records.columns:
        print("\nRAW SENSOR STATUS VALUES:")
        print(count_raw_values(records, "sensor_status").to_string(index=False))


if __name__ == "__main__":
    main()
