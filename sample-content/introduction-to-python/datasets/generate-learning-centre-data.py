"""Generate a deterministic, fictional learning-centre operations CSV.

The output contains no personal data. It is intended for the scale-up lesson,
where learners practise selected columns, explicit types, chunks, aggregation,
and reconciliation on low-specification computers.
"""

import argparse
import csv
import random
from datetime import date


DISTRICTS = ["Central", "North", "North East", "South", "West"]
COURSES = ["Python Foundations", "Digital Skills", "Data Basics", "Office Tools"]


def month_label(index: int) -> str:
    year = 2020 + index // 12
    month = index % 12 + 1
    return date(year, month, 1).strftime("%Y-%m")


def generate(path: str, rows: int, seed: int) -> None:
    random.seed(seed)
    fields = [
        "month",
        "centre_id",
        "district",
        "course",
        "registered",
        "attended",
        "completed",
        "training_hours",
        "material_cost",
    ]
    with open(path, "w", newline="", encoding="utf-8") as output:
        writer = csv.DictWriter(output, fieldnames=fields)
        writer.writeheader()
        for index in range(rows):
            registered = random.randint(12, 120)
            attended = random.randint(max(0, registered - 35), registered)
            completed = random.randint(max(0, attended - 20), attended)
            row = {
                "month": month_label(index % 72),
                "centre_id": f"C{index % 240 + 1:03d}",
                "district": DISTRICTS[index % len(DISTRICTS)],
                "course": COURSES[index % len(COURSES)],
                "registered": registered,
                "attended": attended,
                "completed": completed,
                "training_hours": random.choice([16, 20, 24, 32]),
                "material_cost": f"{random.uniform(120, 1800):.2f}",
            }
            # Deterministic quality problems for cleaning and reconciliation.
            if index > 0 and index % 9973 == 0:
                row["attended"] = ""
            if index > 0 and index % 15401 == 0:
                row["completed"] = registered + 3
            if index > 0 and index % 22193 == 0:
                row["district"] = " central "
            writer.writerow(row)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--rows", type=int, default=250_000)
    parser.add_argument("--seed", type=int, default=42)
    parser.add_argument("--output", default="learning-centres-large.csv")
    args = parser.parse_args()
    if args.rows < 1:
        parser.error("--rows must be positive")
    generate(args.output, args.rows, args.seed)
    print(f"Generated {args.rows} fictional rows in {args.output}")


if __name__ == "__main__":
    main()
