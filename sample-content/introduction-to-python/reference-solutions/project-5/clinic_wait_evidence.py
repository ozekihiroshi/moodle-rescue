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
    records = pd.read_csv(path)
    missing = [column for column in REQUIRED_COLUMNS if column not in records.columns]
    if missing:
        raise ValueError(f"missing columns: {missing}")
    records = records[REQUIRED_COLUMNS].copy()
    for column in NUMERIC_COLUMNS:
        records[column] = pd.to_numeric(records[column], errors="raise")
    return records


def validate_records(records):
    if records.empty:
        raise ValueError("source contains no records")
    if records[REQUIRED_COLUMNS].isna().any().any():
        raise ValueError("required values must not be missing")
    if (records[NUMERIC_COLUMNS] < 0).any().any():
        raise ValueError("counts and minutes must not be negative")
    if (records["over_60_minutes"] > records["patients_seen"]).any():
        raise ValueError("over-60 count exceeds patients seen")
    if records.duplicated(["week", "clinic_id", "time_slot"]).any():
        raise ValueError("duplicate clinic, week, and time-slot record")
    allowed_slots = {"Morning", "Evening"}
    if not set(records["time_slot"]).issubset(allowed_slots):
        raise ValueError("unknown time slot")


def build_burden_summary(records):
    summary = records.groupby(["clinic_id", "clinic_name"], as_index=False).agg(
        patients_seen=("patients_seen", "sum"),
        total_wait_minutes=("total_wait_minutes", "sum"),
        over_60_minutes=("over_60_minutes", "sum"),
        source_records=("week", "size"),
    )
    summary["average_wait_minutes"] = summary["total_wait_minutes"] / summary["patients_seen"]
    summary["over_60_rate"] = summary["over_60_minutes"] / summary["patients_seen"] * 100
    return summary.sort_values(
        ["total_wait_minutes", "clinic_id"], ascending=[False, True]
    ).reset_index(drop=True)


def build_service_summary(records):
    summary = records.groupby(
        ["clinic_id", "clinic_name", "time_slot"], as_index=False
    ).agg(
        patients_seen=("patients_seen", "sum"),
        total_wait_minutes=("total_wait_minutes", "sum"),
        over_60_minutes=("over_60_minutes", "sum"),
        source_records=("week", "size"),
    )
    summary["average_wait_minutes"] = summary["total_wait_minutes"] / summary["patients_seen"]
    summary["over_60_rate"] = summary["over_60_minutes"] / summary["patients_seen"] * 100
    return summary.sort_values(
        ["average_wait_minutes", "over_60_rate", "clinic_id", "time_slot"],
        ascending=[False, False, True, True],
    ).reset_index(drop=True)


def choose_targets(burden_summary, service_summary):
    burden = burden_summary.iloc[0]
    support = service_summary.iloc[0]
    return {
        "burden_clinic_id": burden["clinic_id"],
        "burden_clinic_name": burden["clinic_name"],
        "support_clinic_id": support["clinic_id"],
        "support_clinic_name": support["clinic_name"],
        "support_time_slot": support["time_slot"],
        "support_average_wait": float(support["average_wait_minutes"]),
        "support_over_60_rate": float(support["over_60_rate"]),
    }


def create_evidence_figure(burden_summary, service_summary, targets, output_path):
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    burden_plot = burden_summary.sort_values("total_wait_minutes")
    service_plot = service_summary.copy()
    service_plot["service"] = service_plot["clinic_name"] + " — " + service_plot["time_slot"]
    service_plot = service_plot.sort_values("average_wait_minutes")

    figure, axes = plt.subplots(1, 2, figsize=(13, 5.5))
    axes[0].barh(burden_plot["clinic_name"], burden_plot["total_wait_minutes"], color="#4C78A8")
    axes[0].set_title("Total waiting burden by clinic")
    axes[0].set_xlabel("Total waiting time (minutes)")
    axes[0].set_xlim(left=0)

    colours = [
        "#E45756"
        if row.clinic_id == targets["support_clinic_id"] and row.time_slot == targets["support_time_slot"]
        else "#72B7B2"
        for row in service_plot.itertuples()
    ]
    axes[1].barh(service_plot["service"], service_plot["average_wait_minutes"], color=colours)
    axes[1].set_title("Average wait by clinic and time slot")
    axes[1].set_xlabel("Average wait per patient (minutes)")
    axes[1].set_xlim(left=0)

    figure.suptitle("Clinic waiting-time evidence, weeks 2026-W01 to 2026-W06")
    figure.tight_layout()
    figure.savefig(output_path, dpi=150, bbox_inches="tight")
    plt.close(figure)


def build_evidence_note(burden_summary, service_summary, targets):
    burden = burden_summary.iloc[0]
    return (
        f"{burden['clinic_name']} carried the largest total waiting burden at "
        f"{int(burden['total_wait_minutes'])} minutes across six weeks. "
        f"{targets['support_clinic_name']} — {targets['support_time_slot']} had the highest "
        f"average wait at {targets['support_average_wait']:.1f} minutes, and "
        f"{targets['support_over_60_rate']:.1f}% of its patients waited over 60 minutes. "
        "These records identify where the measured waiting problem was concentrated, but they do not establish its cause."
    )


def save_summary(service_summary, output_path):
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    saved = service_summary.copy()
    saved["average_wait_minutes"] = saved["average_wait_minutes"].round(1)
    saved["over_60_rate"] = saved["over_60_rate"].round(1)
    saved.to_csv(output_path, index=False)


def run_project(input_path, summary_path, figure_path):
    records = load_records(input_path)
    validate_records(records)
    burden_summary = build_burden_summary(records)
    service_summary = build_service_summary(records)
    targets = choose_targets(burden_summary, service_summary)
    save_summary(service_summary, summary_path)
    create_evidence_figure(burden_summary, service_summary, targets, figure_path)
    note = build_evidence_note(burden_summary, service_summary, targets)
    return {
        "source_records": len(records),
        "burden_summary": burden_summary,
        "service_summary": service_summary,
        "targets": targets,
        "evidence_note": note,
    }


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
