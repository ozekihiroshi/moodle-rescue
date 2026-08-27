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
    records = pd.read_csv(path)
    missing = [column for column in REQUIRED_COLUMNS if column not in records.columns]
    if missing:
        raise ValueError(f"Missing required columns: {', '.join(missing)}")
    records = records[REQUIRED_COLUMNS].copy()
    records.insert(0, "source_row", range(2, len(records) + 2))
    return records


def add_quality_flags(records):
    result = records.copy(deep=True)
    for column in ["date", "facility_id"]:
        result[column] = result[column].astype("string").str.strip()
    result["district_raw"] = result["district"]
    result["district"] = result["district"].astype("string").str.strip().str.title()
    result["sensor_status_raw"] = result["sensor_status"]
    result["sensor_status"] = result["sensor_status"].astype("string").str.strip().str.lower()
    for column in NUMBER_COLUMNS:
        result[column] = pd.to_numeric(result[column], errors="coerce")
    result["missing_number"] = result[NUMBER_COLUMNS].isna().any(axis=1)
    result["negative_number"] = result[NUMBER_COLUMNS].lt(0).any(axis=1)
    capacity = result["rated_litres_per_hour"] * result["operating_hours"]
    result["impossible_output"] = result["water_delivered_litres"].gt(capacity * 1.05)
    result["sensor_not_ok"] = result["sensor_status"].ne("ok")
    result["duplicate_facility_date"] = result.duplicated(["date", "facility_id"], keep=False)
    result["issue"] = result.apply(
        lambda row: "; ".join(text for flag, text in ISSUES if bool(row[flag])), axis=1
    )
    result["is_valid"] = ~result[[flag for flag, _ in ISSUES]].any(axis=1)
    return result


def build_verification_report(flagged):
    columns = ["source_row", "date", "facility_id", "facility_name", "issue"]
    return flagged.loc[~flagged["is_valid"], columns].sort_values("source_row").reset_index(drop=True)


def build_analysis_data(flagged):
    analysis = flagged.loc[flagged["is_valid"], REQUIRED_COLUMNS].copy().reset_index(drop=True)
    analysis["rated_capacity_litres"] = analysis["rated_litres_per_hour"] * analysis["operating_hours"]
    analysis["stopped_day"] = analysis["operating_hours"].eq(0) & analysis["water_delivered_litres"].eq(0)
    analysis["low_output_day"] = analysis["operating_hours"].gt(0) & analysis["water_delivered_litres"].lt(analysis["rated_capacity_litres"] * 0.70)
    return analysis


def summarise_facilities(analysis):
    summary = analysis.groupby(["facility_id", "facility_name"], as_index=False).agg(
        valid_days=("date", "nunique"),
        stopped_days=("stopped_day", "sum"),
        low_output_days=("low_output_day", "sum"),
        operating_hours=("operating_hours", "sum"),
        rated_capacity_litres=("rated_capacity_litres", "sum"),
        water_delivered_litres=("water_delivered_litres", "sum"),
        households_served=("households_served", "max"),
    )
    summary["output_rate"] = summary["water_delivered_litres"].div(summary["rated_capacity_litres"].replace(0, pd.NA)).mul(100).fillna(0.0)
    summary = summary.sort_values(
        ["stopped_days", "low_output_days", "households_served", "facility_id"],
        ascending=[False, False, False, True],
    ).reset_index(drop=True)
    summary.insert(0, "priority", range(1, len(summary) + 1))
    summary["output_rate"] = summary["output_rate"].round(1)
    return summary


def select_first_inspection(summary):
    if summary.empty:
        raise ValueError("No facility can be ranked")
    first = summary.iloc[0]
    return {"facility_id": first["facility_id"], "facility_name": first["facility_name"]}


def save_outputs(audit, summary, output_dir):
    output_dir = Path(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    audit.to_csv(output_dir / "records_to_verify.csv", index=False)
    summary.to_csv(output_dir / "facility_inspection_summary.csv", index=False)


def run_project(input_path=INPUT_FILE, output_dir=OUTPUT_DIR):
    records = load_records(input_path)
    flagged = add_quality_flags(records)
    audit = build_verification_report(flagged)
    analysis = build_analysis_data(flagged)
    summary = summarise_facilities(analysis)
    first = select_first_inspection(summary)
    save_outputs(audit, summary, output_dir)
    return {
        "source_records": len(records),
        "records_to_verify": len(audit),
        "analysis_records": len(analysis),
        "first_inspection_id": first["facility_id"],
        "first_inspection_name": first["facility_name"],
    }


def main():
    result = run_project()
    print("WATER POINT REVIEW")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"RECORDS TO VERIFY: {result['records_to_verify']}")
    print(f"ANALYSIS RECORDS: {result['analysis_records']}")
    print(f"FIRST INSPECTION: {result['first_inspection_id']} — {result['first_inspection_name']}")


if __name__ == "__main__":
    main()
