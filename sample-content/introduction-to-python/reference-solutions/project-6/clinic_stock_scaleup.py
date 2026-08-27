from __future__ import annotations

from pathlib import Path

import matplotlib.pyplot as plt
import pandas as pd


PROJECT = Path(__file__).resolve().parent
SOURCE = PROJECT / "data" / "clinic-stock-120000.csv"
OUTPUT = PROJECT / "output"
NUMERIC_COLUMNS = [
    "opening_units", "received_units", "dispensed_units", "closing_units",
    "stockout_hours", "patients_turned_away",
]


def required_columns() -> list[str]:
    return [
        "date", "clinic_id", "clinic_name", "district", "medicine",
        *NUMERIC_COLUMNS,
    ]


def validate_schema(columns) -> None:
    missing = [column for column in required_columns() if column not in columns]
    if missing:
        raise ValueError("Missing required columns: " + ", ".join(missing))


def prepare_chunk(chunk: pd.DataFrame) -> tuple[pd.DataFrame, pd.DataFrame]:
    validate_schema(chunk.columns)
    work = chunk.loc[:, required_columns()].copy(deep=True)
    for column in ["date", "clinic_id", "clinic_name", "district", "medicine"]:
        work[column] = work[column].astype("string").str.strip()
    for column in NUMERIC_COLUMNS:
        work[column] = pd.to_numeric(work[column], errors="coerce")

    missing_text = work[["date", "clinic_id", "clinic_name", "district", "medicine"]].isna().any(axis=1)
    missing_text |= work[["date", "clinic_id", "clinic_name", "district", "medicine"]].eq("").any(axis=1)
    missing_number = work[NUMERIC_COLUMNS].isna().any(axis=1)
    negative_number = work[NUMERIC_COLUMNS].lt(0).any(axis=1)
    invalid_hours = work["stockout_hours"].notna() & ~work["stockout_hours"].between(0, 24)
    expected_closing = work["opening_units"] + work["received_units"] - work["dispensed_units"]
    invalid_balance = work[NUMERIC_COLUMNS[:4]].notna().all(axis=1) & work["closing_units"].ne(expected_closing)

    work["issue_reason"] = ""
    checks = [
        (missing_text, "missing_text"),
        (missing_number, "missing_number"),
        (negative_number, "negative_number"),
        (invalid_hours, "invalid_stockout_hours"),
        (invalid_balance, "inventory_balance"),
    ]
    for mask, label in checks:
        work.loc[mask & work["issue_reason"].ne(""), "issue_reason"] += "|"
        work.loc[mask, "issue_reason"] += label

    review = work.loc[work["issue_reason"].ne("")].copy()
    valid = work.loc[work["issue_reason"].eq("")].drop(columns="issue_reason").copy()
    return valid.reset_index(drop=True), review.reset_index(drop=True)


def update_totals(totals: dict, valid: pd.DataFrame) -> None:
    if valid.empty:
        return
    part = valid.assign(stockout_day=valid["stockout_hours"].gt(0).astype(int)).groupby(
        ["district", "medicine"], as_index=False
    ).agg(
        clinic_days=("date", "size"),
        stockout_days=("stockout_day", "sum"),
        stockout_hours=("stockout_hours", "sum"),
        patients_turned_away=("patients_turned_away", "sum"),
    )
    for row in part.itertuples(index=False):
        key = (row.district, row.medicine)
        current = totals.setdefault(key, {
            "clinic_days": 0, "stockout_days": 0,
            "stockout_hours": 0.0, "patients_turned_away": 0.0,
        })
        for field in current:
            current[field] += getattr(row, field)


def build_summary(totals: dict) -> pd.DataFrame:
    rows = []
    for (district, medicine), values in totals.items():
        row = {"district": district, "medicine": medicine, **values}
        row["stockout_rate"] = values["stockout_days"] / values["clinic_days"] * 100 if values["clinic_days"] else 0.0
        rows.append(row)
    columns = [
        "district", "medicine", "clinic_days", "stockout_days",
        "stockout_hours", "patients_turned_away", "stockout_rate",
    ]
    summary = pd.DataFrame(rows, columns=columns)
    if summary.empty:
        return summary
    return summary.sort_values(
        ["patients_turned_away", "stockout_hours", "district", "medicine"],
        ascending=[False, False, True, True],
    ).reset_index(drop=True)


def select_priority(summary: pd.DataFrame) -> pd.Series:
    if summary.empty:
        raise ValueError("No valid records are available for priority selection")
    return summary.iloc[0].copy()


def process_file(path: Path, chunksize: int = 10_000) -> dict:
    if chunksize <= 0:
        raise ValueError("chunksize must be positive")
    header = pd.read_csv(path, nrows=0)
    validate_schema(header.columns)
    totals: dict = {}
    source_records = analysis_records = review_records = 0
    for chunk in pd.read_csv(path, usecols=required_columns(), chunksize=chunksize):
        source_records += len(chunk)
        valid, review = prepare_chunk(chunk)
        analysis_records += len(valid)
        review_records += len(review)
        update_totals(totals, valid)
    return {
        "source_records": source_records,
        "analysis_records": analysis_records,
        "review_records": review_records,
        "reconciled": source_records == analysis_records + review_records,
        "summary": build_summary(totals),
    }


def save_outputs(summary: pd.DataFrame, priority: pd.Series, output_dir: Path) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)
    saved = summary.copy()
    saved["stockout_hours"] = saved["stockout_hours"].round(1)
    saved["patients_turned_away"] = saved["patients_turned_away"].round(0).astype(int)
    saved["stockout_rate"] = saved["stockout_rate"].round(1)
    saved.to_csv(output_dir / "clinic_stock_summary.csv", index=False)

    top = saved.head(8).sort_values("patients_turned_away")
    labels = top["district"] + " — " + top["medicine"]
    colours = ["#E45756" if district == priority["district"] and medicine == priority["medicine"] else "#4C78A8"
               for district, medicine in zip(top["district"], top["medicine"])]
    fig, ax = plt.subplots(figsize=(9, 5))
    ax.barh(labels, top["patients_turned_away"], color=colours)
    ax.set(title="Patients turned away during medicine shortages", xlabel="Patients turned away", ylabel="")
    ax.set_xlim(left=0)
    fig.tight_layout()
    fig.savefig(output_dir / "clinic_stock_evidence.png", dpi=150, bbox_inches="tight")
    plt.close(fig)


def run_project(source: Path = SOURCE, output_dir: Path = OUTPUT, chunksize: int = 10_000) -> dict:
    result = process_file(source, chunksize)
    if not result["reconciled"]:
        raise ValueError("Source records were not reconciled")
    priority = select_priority(result["summary"])
    save_outputs(result["summary"], priority, output_dir)
    return {**result, "priority": priority}


def main() -> None:
    result = run_project()
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
