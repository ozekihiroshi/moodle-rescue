from __future__ import annotations

from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
INPUT_FILE = HERE / "data" / "water-points-practice.csv"
OUTPUT_DIR = HERE / "output"
REQUIRED_COLUMNS = [
    "date", "facility_id", "facility_name", "district",
    "rated_litres_per_hour", "operating_hours", "water_delivered_litres",
    "households_served", "sensor_status",
]
NUMBER_COLUMNS = [
    "rated_litres_per_hour", "operating_hours", "water_delivered_litres",
    "households_served",
]
ISSUES = [
    ("missing_number", "missing required number"),
    ("negative_number", "negative number"),
    ("impossible_output", "delivery exceeds rated capacity"),
    ("sensor_not_ok", "sensor status is not ok"),
    ("duplicate_facility_date", "duplicate facility/date"),
]


def load_records(path):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


def add_quality_flags(records):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


def build_verification_report(flagged):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


def build_analysis_data(flagged):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


def summarise_facilities(analysis):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


def select_first_inspection(summary):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


def save_outputs(audit, summary, output_dir):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


def run_project(input_path=INPUT_FILE, output_dir=OUTPUT_DIR):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


# PROGRAM INCOMPLETE: complete every TODO, then remove this line.

def main():
    result = run_project()
    print("WATER POINT REVIEW")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"RECORDS TO VERIFY: {result['records_to_verify']}")
    print(f"ANALYSIS RECORDS: {result['analysis_records']}")
    print(f"FIRST INSPECTION: {result['first_inspection_id']} — {result['first_inspection_name']}")


if __name__ == "__main__":
    main()
