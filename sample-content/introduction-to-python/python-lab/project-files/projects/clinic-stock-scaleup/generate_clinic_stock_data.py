from __future__ import annotations

import argparse
import csv
from datetime import date, timedelta
from pathlib import Path


FIELDS = [
    "date", "clinic_id", "clinic_name", "district", "medicine",
    "opening_units", "received_units", "dispensed_units", "closing_units",
    "stockout_hours", "patients_turned_away",
]


def generate(path: Path, rows: int) -> None:
    """Create deterministic fictional clinic-stock records."""
    districts = ["North", "East", "South", "West"]
    medicines = ["Antibiotics", "Malaria treatment", "Insulin", "Pain relief"]
    start = date(2026, 1, 1)
    path.parent.mkdir(parents=True, exist_ok=True)

    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=FIELDS)
        writer.writeheader()
        for index in range(rows):
            clinic_number = index % 24
            district = districts[clinic_number % len(districts)]
            medicine_number = (index // 24) % len(medicines)
            medicine = medicines[medicine_number]
            day = start + timedelta(days=index // 96)

            demand = 18 + (clinic_number % 6) * 2 + medicine_number * 3
            opening = demand + 7 + index % 5
            received = 4 + (index * 3) % 9
            dispensed = min(opening + received, demand + (index % 7))
            closing = opening + received - dispensed

            if district == "East" and medicine == "Insulin":
                stockout = 9 + index % 7
            elif district == "South" and medicine == "Malaria treatment":
                stockout = 5 + index % 5
            else:
                stockout = (index * 5) % 4
            turned_away = stockout * (2 + medicine_number) + index % 3

            record = {
                "date": day.isoformat(),
                "clinic_id": f"C{clinic_number + 1:03d}",
                "clinic_name": f"Clinic {clinic_number + 1:02d}",
                "district": district,
                "medicine": medicine,
                "opening_units": opening,
                "received_units": received,
                "dispensed_units": dispensed,
                "closing_units": closing,
                "stockout_hours": stockout,
                "patients_turned_away": turned_away,
            }

            number = index + 1
            if number % 10007 == 0:
                record["closing_units"] = closing + 5
            elif number % 16001 == 0:
                record["dispensed_units"] = -1
            elif number % 23003 == 0:
                record["district"] = ""

            writer.writerow(record)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("output", type=Path)
    parser.add_argument("--rows", type=int, default=120_000)
    args = parser.parse_args()
    if args.rows <= 0:
        raise SystemExit("--rows must be positive")
    generate(args.output, args.rows)
    print(f"Generated {args.rows} rows: {args.output}")


if __name__ == "__main__":
    main()
