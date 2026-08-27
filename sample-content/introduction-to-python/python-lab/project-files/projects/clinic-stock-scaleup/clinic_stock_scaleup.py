from __future__ import annotations

from pathlib import Path

import pandas as pd


PROJECT = Path(__file__).resolve().parent
SOURCE = PROJECT / "data" / "clinic-stock-120000.csv"
OUTPUT = PROJECT / "output"
NUMERIC_COLUMNS = [
    "opening_units", "received_units", "dispensed_units", "closing_units",
    "stockout_hours", "patients_turned_away",
]


def required_columns() -> list[str]:
    """Return the public input-column contract."""
    raise NotImplementedError("Complete required_columns")


def validate_schema(columns) -> None:
    """Raise ValueError if a required column is missing."""
    raise NotImplementedError("Complete validate_schema")


def prepare_chunk(chunk: pd.DataFrame) -> tuple[pd.DataFrame, pd.DataFrame]:
    """Return independent valid and review tables for one chunk."""
    raise NotImplementedError("Complete prepare_chunk")


def update_totals(totals: dict, valid: pd.DataFrame) -> None:
    """Merge one valid chunk into district-medicine running totals."""
    raise NotImplementedError("Complete update_totals")


def build_summary(totals: dict) -> pd.DataFrame:
    """Create the decision-sized summary table."""
    raise NotImplementedError("Complete build_summary")


def select_priority(summary: pd.DataFrame) -> pd.Series:
    """Return the first-resupply row."""
    raise NotImplementedError("Complete select_priority")


def process_file(path: Path, chunksize: int = 10_000) -> dict:
    """Process the CSV in chunks and return counts plus summary."""
    raise NotImplementedError("Complete process_file")


def save_outputs(summary: pd.DataFrame, priority: pd.Series, output_dir: Path) -> None:
    """Save the summary CSV and a labelled evidence PNG."""
    raise NotImplementedError("Complete save_outputs")


def run_project(source: Path = SOURCE, output_dir: Path = OUTPUT, chunksize: int = 10_000) -> dict:
    """Run processing, reconciliation, priority selection, and saving."""
    raise NotImplementedError("Complete run_project")


def main() -> None:
    if not SOURCE.is_file():
        print("Generate the full source file from the project Notebook first.")
        return
    try:
        result = run_project()
    except NotImplementedError as error:
        print(f"PROGRAM INCOMPLETE: {error}")
        return

    priority = result["priority"]
    print("CLINIC STOCK SCALE-UP REPORT")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"ANALYSIS RECORDS: {result['analysis_records']}")
    print(f"RECORDS TO REVIEW: {result['review_records']}")
    print(f"RECONCILED: {result['reconciled']}")
    print(f"FIRST RESUPPLY: {priority['district']} — {priority['medicine']}")
    print(f"PATIENTS TURNED AWAY: {int(priority['patients_turned_away'])}")
    print("SCALE-UP COMPLETE")


if __name__ == "__main__":
    main()
