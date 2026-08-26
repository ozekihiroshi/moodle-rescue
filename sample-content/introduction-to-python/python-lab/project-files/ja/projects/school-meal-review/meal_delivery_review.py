from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
DEFAULT_INPUT = HERE / "data" / "school-meals-practice.csv"
DEFAULT_OUTPUT = HERE / "output"
REQUIRED_COLUMNS = [
    "date", "school_id", "school_name", "district",
    "pupils_present", "meals_delivered", "meals_served",
]
NUMERIC_COLUMNS = ["pupils_present", "meals_delivered", "meals_served"]


def load_records(path):
    """Read the CSV, validate required columns, and add CSV source-row numbers."""
    # TODO 1
    raise NotImplementedError


def add_quality_flags(records):
    """Return a copy with normalised districts, four quality flags, issue, and is_valid."""
    # TODO 2: deep-copy first; strip date/ID, convert numbers, then add all flags
    # Compare each impossible pair only when both required values are present.
    raise NotImplementedError


def build_verification_report(flagged):
    """Return invalid rows with source_row, date, school, and issue."""
    # TODO 3
    raise NotImplementedError


def build_analysis_data(flagged):
    """Keep valid rows and add unmet_meals."""
    # TODO 4
    raise NotImplementedError


def summarise_schools(analysis):
    """Aggregate, calculate rates, rank schools, and return the specified columns."""
    # TODO 5: group by ID and name, rank before rounding, and use 0.0 for 0/0 coverage
    raise NotImplementedError


def select_first_delivery(summary):
    """Return school_id and school_name for priority 1; reject an empty summary."""
    # TODO 6
    raise NotImplementedError


def save_outputs(audit, summary, output_dir):
    """Create output_dir and save both output CSV files without indexes."""
    # TODO 7
    raise NotImplementedError


def run_project(input_path, output_dir):
    """Connect all stages and return the five report values used by main()."""
    # TODO 8
    raise NotImplementedError


def main():
    result = run_project(DEFAULT_INPUT, DEFAULT_OUTPUT)
    print("SCHOOL MEAL DELIVERY REVIEW")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"RECORDS TO VERIFY: {result['records_to_verify']}")
    print(f"ANALYSIS RECORDS: {result['analysis_records']}")
    print(
        f"FIRST DELIVERY: {result['first_delivery_id']} — "
        f"{result['first_delivery_name']}"
    )


if __name__ == "__main__":
    main()


print("PROGRAM INCOMPLETE")
