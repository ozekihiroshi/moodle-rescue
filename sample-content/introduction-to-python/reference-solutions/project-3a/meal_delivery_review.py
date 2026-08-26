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
    records = pd.read_csv(path)
    missing_columns = [column for column in REQUIRED_COLUMNS if column not in records.columns]
    if missing_columns:
        raise ValueError(f"Missing required columns: {missing_columns}")
    records = records[REQUIRED_COLUMNS].copy()
    records.insert(0, "source_row", range(2, len(records) + 2))
    return records


def add_quality_flags(records):
    flagged = records.copy(deep=True)
    for column in ["date", "school_id"]:
        flagged[column] = flagged[column].astype("string").str.strip()
    flagged["district_raw"] = flagged["district"]
    flagged["district"] = flagged["district"].astype("string").str.strip().str.title()
    for column in NUMERIC_COLUMNS:
        flagged[column] = pd.to_numeric(flagged[column], errors="coerce")

    flagged["missing_number"] = flagged[NUMERIC_COLUMNS].isna().any(axis=1)
    flagged["negative_number"] = flagged[NUMERIC_COLUMNS].lt(0).any(axis=1)
    served_over_present = (
        flagged["meals_served"].notna()
        & flagged["pupils_present"].notna()
        & (flagged["meals_served"] > flagged["pupils_present"])
    )
    served_over_delivered = (
        flagged["meals_served"].notna()
        & flagged["meals_delivered"].notna()
        & (flagged["meals_served"] > flagged["meals_delivered"])
    )
    flagged["impossible_service"] = served_over_present | served_over_delivered
    flagged["duplicate_school_date"] = flagged.duplicated(
        ["date", "school_id"], keep=False
    )

    flagged["issue"] = ""
    flag_labels = [
        ("missing_number", "missing required number"),
        ("negative_number", "negative number"),
        ("impossible_service", "meals served exceeds limit"),
        ("duplicate_school_date", "duplicate school/date"),
    ]
    for column, label in flag_labels:
        mask = flagged[column]
        flagged.loc[mask, "issue"] = flagged.loc[mask, "issue"] + label + "; "
    flagged["issue"] = flagged["issue"].str.rstrip("; ")
    flag_columns = [column for column, _ in flag_labels]
    flagged["is_valid"] = ~flagged[flag_columns].any(axis=1)
    return flagged


def build_verification_report(flagged):
    columns = ["source_row", "date", "school_id", "school_name", "issue"]
    return flagged.loc[~flagged["is_valid"], columns].reset_index(drop=True)


def build_analysis_data(flagged):
    analysis = flagged.loc[flagged["is_valid"], REQUIRED_COLUMNS].copy()
    analysis["unmet_meals"] = analysis["pupils_present"] - analysis["meals_served"]
    return analysis.reset_index(drop=True)


def summarise_schools(analysis):
    summary = (
        analysis.groupby(["school_id", "school_name"], as_index=False)
        .agg(
            valid_days=("date", "nunique"),
            pupils_present=("pupils_present", "sum"),
            meals_served=("meals_served", "sum"),
            unmet_meals=("unmet_meals", "sum"),
            shortage_days=("unmet_meals", lambda values: (values > 0).sum()),
        )
    )
    summary["meal_coverage_rate"] = 0.0
    has_pupils = summary["pupils_present"] != 0
    summary.loc[has_pupils, "meal_coverage_rate"] = (
        summary.loc[has_pupils, "meals_served"]
        / summary.loc[has_pupils, "pupils_present"]
        * 100
    )
    summary["average_unmet_meals"] = summary["unmet_meals"] / summary["valid_days"]
    summary = summary.sort_values(
        ["average_unmet_meals", "shortage_days", "school_id"],
        ascending=[False, False, True],
    ).reset_index(drop=True)
    summary.insert(0, "priority", range(1, len(summary) + 1))
    summary["meal_coverage_rate"] = summary["meal_coverage_rate"].round(1)
    summary["average_unmet_meals"] = summary["average_unmet_meals"].round(1)
    integer_columns = [
        "valid_days", "pupils_present", "meals_served", "unmet_meals", "shortage_days"
    ]
    summary[integer_columns] = summary[integer_columns].astype(int)
    return summary


def select_first_delivery(summary):
    if summary.empty:
        raise ValueError("No valid school records")
    first = summary.iloc[0]
    return {"school_id": first["school_id"], "school_name": first["school_name"]}


def save_outputs(audit, summary, output_dir):
    output_dir = Path(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    audit.to_csv(output_dir / "records_to_verify.csv", index=False)
    summary.to_csv(output_dir / "school_delivery_summary.csv", index=False)


def run_project(input_path, output_dir):
    records = load_records(input_path)
    flagged = add_quality_flags(records)
    audit = build_verification_report(flagged)
    analysis = build_analysis_data(flagged)
    summary = summarise_schools(analysis)
    first = select_first_delivery(summary)
    save_outputs(audit, summary, output_dir)
    return {
        "source_records": len(records),
        "records_to_verify": len(audit),
        "analysis_records": len(analysis),
        "first_delivery_id": first["school_id"],
        "first_delivery_name": first["school_name"],
    }


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
