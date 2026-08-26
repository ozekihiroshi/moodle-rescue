from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
SOURCE_FILE = HERE / "data" / "school-meals-practice.csv"


def load_records(path):
    """Read and return the CSV without changing the source file."""
    # TODO 1: use pandas to read the path supplied to this function
    raise NotImplementedError


def build_school_date_view(records):
    """Return a new table sorted by school_id and date."""
    # TODO 2: do not change records itself
    raise NotImplementedError


def count_district_values(records):
    """Return raw district counts as a district/records DataFrame."""
    # TODO 3: do not strip, title-case, or otherwise normalise the values
    raise NotImplementedError


def main():
    records = load_records(SOURCE_FILE)
    school_date_view = build_school_date_view(records)
    district_counts = count_district_values(records)

    print("SCHOOL MEAL SOURCE INSPECTION")
    print(f"ROWS: {len(records)}")
    print(f"COLUMNS: {len(records.columns)}")
    print("COLUMN NAMES:")
    print(records.columns.tolist())
    print("INFERRED DTYPES:")
    print(records.dtypes.to_string())
    print("ALL RECORDS:")
    print(records.to_string(index=False, line_width=200))
    print("SCHOOL/DATE VIEW:")
    print(school_date_view.to_string(index=False, line_width=200))
    print("MISSING VALUES:")
    print(records.isna().sum().to_string())
    print("DISTRICT VALUES:")
    print(district_counts.to_string(index=False, formatters={"district": repr}))


if __name__ == "__main__":
    main()
