from pathlib import Path

import matplotlib.pyplot as plt
import pandas as pd


REQUIRED_COLUMNS = [
    "week",
    "clinic_id",
    "clinic_name",
    "time_slot",
    "patients_seen",
    "total_wait_minutes",
    "over_60_minutes",
]
NUMERIC_COLUMNS = ["patients_seen", "total_wait_minutes", "over_60_minutes"]


def load_records(path):
    """Read the required source columns and convert the three numeric columns."""
    raise NotImplementedError("Complete load_records")


def validate_records(records):
    """Raise ValueError when a published source-data rule is violated."""
    raise NotImplementedError("Complete validate_records")


def build_burden_summary(records):
    """Return one row per clinic, ordered by total waiting minutes."""
    raise NotImplementedError("Complete build_burden_summary")


def build_service_summary(records):
    """Return one row per clinic and time slot, ordered by average wait."""
    raise NotImplementedError("Complete build_service_summary")


def choose_targets(burden_summary, service_summary):
    """Return the published target dictionary from the two summaries."""
    raise NotImplementedError("Complete choose_targets")


def create_evidence_figure(burden_summary, service_summary, targets, output_path):
    """Save the required two-panel PNG without changing either summary."""
    raise NotImplementedError("Complete create_evidence_figure")


def build_evidence_note(burden_summary, service_summary, targets):
    """Return three bounded evidence sentences."""
    raise NotImplementedError("Complete build_evidence_note")


def save_summary(service_summary, output_path):
    """Round only the saved copy and write the service summary CSV."""
    raise NotImplementedError("Complete save_summary")


def run_project(input_path, summary_path, figure_path):
    """Connect loading, validation, summaries, evidence, and saved outputs."""
    raise NotImplementedError("Complete run_project")


def main():
    project = Path(__file__).resolve().parent
    result = run_project(
        project / "data" / "clinic-waits-practice.csv",
        project / "output" / "clinic_wait_summary.csv",
        project / "output" / "clinic_wait_evidence.png",
    )
    targets = result["targets"]
    print("CLINIC WAIT EVIDENCE")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"TOTAL BURDEN CLINIC: {targets['burden_clinic_id']} — {targets['burden_clinic_name']}")
    print(
        f"SUPPORT TARGET: {targets['support_clinic_id']} — "
        f"{targets['support_clinic_name']} — {targets['support_time_slot']}"
    )
    print(f"TARGET AVERAGE WAIT: {targets['support_average_wait']:.1f} MINUTES")
    print(f"TARGET OVER-60 RATE: {targets['support_over_60_rate']:.1f}%")
    print(result["evidence_note"])


if __name__ == "__main__":
    main()
