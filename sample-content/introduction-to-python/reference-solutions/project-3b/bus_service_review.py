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
    records = pd.read_csv(path)
    missing = [column for column in REQUIRED_COLUMNS if column not in records.columns]
    if missing:
        raise ValueError(f"Missing required columns: {', '.join(missing)}")
    records = records[REQUIRED_COLUMNS].copy()
    records.insert(0, "source_row", range(2, len(records) + 2))
    return records


def add_quality_flags(records):
    result = records.copy(deep=True)
    for column in ["date", "route_id"]:
        result[column] = result[column].astype("string").str.strip()
    result["district_raw"] = result["district"]
    result["district"] = result["district"].astype("string").str.strip().str.title()
    for column in NUMBER_COLUMNS:
        result[column] = pd.to_numeric(result[column], errors="coerce")
    result["missing_number"] = result[NUMBER_COLUMNS].isna().any(axis=1)
    result["negative_number"] = result[NUMBER_COLUMNS].lt(0).any(axis=1)
    result["impossible_trips"] = result["completed_trips"].gt(result["scheduled_trips"])
    result["passengers_without_trip"] = result["completed_trips"].eq(0) & result["passengers"].gt(0)
    result["duplicate_route_date"] = result.duplicated(["date", "route_id"], keep=False)
    result["issue"] = result.apply(
        lambda row: "; ".join(text for flag, text in ISSUES if bool(row[flag])), axis=1
    )
    result["is_valid"] = ~result[[flag for flag, _ in ISSUES]].any(axis=1)
    return result


def build_verification_report(flagged):
    columns = ["source_row", "date", "route_id", "route_name", "issue"]
    return flagged.loc[~flagged["is_valid"], columns].sort_values("source_row").reset_index(drop=True)


def build_analysis_data(flagged):
    columns = REQUIRED_COLUMNS.copy()
    analysis = flagged.loc[flagged["is_valid"], columns].copy().reset_index(drop=True)
    analysis["cancelled_trips"] = analysis["scheduled_trips"] - analysis["completed_trips"]
    denominator = analysis["completed_trips"].replace(0, pd.NA)
    analysis["passenger_delay_minutes"] = (
        analysis["delay_minutes"].div(denominator).mul(analysis["passengers"]).fillna(0.0)
    )
    return analysis


def summarise_routes(analysis):
    summary = analysis.groupby(["route_id", "route_name"], as_index=False).agg(
        valid_days=("date", "nunique"),
        scheduled_trips=("scheduled_trips", "sum"),
        completed_trips=("completed_trips", "sum"),
        cancelled_trips=("cancelled_trips", "sum"),
        passengers=("passengers", "sum"),
        delay_minutes=("delay_minutes", "sum"),
        passenger_delay_minutes=("passenger_delay_minutes", "sum"),
    )
    summary["average_delay_minutes"] = summary["delay_minutes"].div(summary["completed_trips"].replace(0, pd.NA)).fillna(0.0)
    summary["cancellation_rate"] = summary["cancelled_trips"].div(summary["scheduled_trips"].replace(0, pd.NA)).mul(100).fillna(0.0)
    summary = summary.sort_values(
        ["passenger_delay_minutes", "cancellation_rate", "route_id"],
        ascending=[False, False, True],
    ).reset_index(drop=True)
    summary.insert(0, "priority", range(1, len(summary) + 1))
    for column in ["average_delay_minutes", "cancellation_rate", "passenger_delay_minutes"]:
        summary[column] = summary[column].round(1)
    return summary


def select_first_review(summary):
    if summary.empty:
        raise ValueError("No route can be ranked")
    first = summary.iloc[0]
    return {"route_id": first["route_id"], "route_name": first["route_name"]}


def save_outputs(audit, summary, output_dir):
    output_dir = Path(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    audit.to_csv(output_dir / "records_to_verify.csv", index=False)
    summary.to_csv(output_dir / "route_review_summary.csv", index=False)


def run_project(input_path=INPUT_FILE, output_dir=OUTPUT_DIR):
    records = load_records(input_path)
    flagged = add_quality_flags(records)
    audit = build_verification_report(flagged)
    analysis = build_analysis_data(flagged)
    summary = summarise_routes(analysis)
    first = select_first_review(summary)
    save_outputs(audit, summary, output_dir)
    return {
        "source_records": len(records),
        "records_to_verify": len(audit),
        "analysis_records": len(analysis),
        "first_review_id": first["route_id"],
        "first_review_name": first["route_name"],
    }


def main():
    result = run_project()
    print("BUS SERVICE REVIEW")
    print(f"SOURCE RECORDS: {result['source_records']}")
    print(f"RECORDS TO VERIFY: {result['records_to_verify']}")
    print(f"ANALYSIS RECORDS: {result['analysis_records']}")
    print(f"FIRST REVIEW: {result['first_review_id']} — {result['first_review_name']}")


if __name__ == "__main__":
    main()
