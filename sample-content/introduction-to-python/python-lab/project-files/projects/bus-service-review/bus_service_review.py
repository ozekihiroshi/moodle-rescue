from __future__ import annotations

from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
INPUT_FILE = HERE / "data" / "bus-service-practice.csv"
OUTPUT_DIR = HERE / "output"
REQUIRED_COLUMNS = [
    "date", "route_id", "route_name", "district", "scheduled_trips",
    "completed_trips", "passengers", "delay_minutes",
]
NUMBER_COLUMNS = ["scheduled_trips", "completed_trips", "passengers", "delay_minutes"]
ISSUES = [
    ("missing_number", "missing required number"),
    ("negative_number", "negative number"),
    ("impossible_trips", "completed trips exceeds scheduled trips"),
    ("passengers_without_trip", "passengers recorded with zero completed trips"),
    ("duplicate_route_date", "duplicate route/date"),
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


def summarise_routes(analysis):
    """TODO: implement this published function contract."""
    raise NotImplementedError("TODO")


def select_first_review(summary):
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
    print("BUS SERVICE REVIEW")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"RECORDS TO VERIFY: {result['records_to_verify']}")
    print(f"ANALYSIS RECORDS: {result['analysis_records']}")
    print(f"FIRST REVIEW: {result['first_review_id']} — {result['first_review_name']}")


if __name__ == "__main__":
    main()
