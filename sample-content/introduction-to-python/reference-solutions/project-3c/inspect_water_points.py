from __future__ import annotations

from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
INPUT_FILE = HERE / "data" / "water-points-practice.csv"


def load_records(path):
    return pd.read_csv(path)


def build_key_date_view(records):
    return records.sort_values(["facility_id", "date"]).reset_index(drop=True)


def count_raw_values(records, column):
    return (
        records[column]
        .value_counts(sort=False, dropna=False)
        .rename_axis("value")
        .reset_index(name="records")
    )


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
    print("\nRAW SENSOR STATUS VALUES:")
    print(count_raw_values(records, "sensor_status").to_string(index=False))


if __name__ == "__main__":
    main()
