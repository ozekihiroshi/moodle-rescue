# 3.5C Midterm practical project — Community water-point inspection

## Situation

The maintenance team can add one field visit tomorrow. Remote records contain
failed sensors as well as real stoppages. Choosing the smallest raw delivery
value can therefore send the team to the wrong place. Prepare a verification
list, a ranked facility summary, and the first water point to inspect. Do not
alter the source CSV.

## Two programs to complete

Complete the supplied starters in `projects/water-point-review/`.

1. `inspect_water_points.py` (20 points) reads and displays the source.
2. `water_point_review.py` (80 points) completes eight functions for quality
   flags, separation, aggregation, ranking, and CSV saving.

The Notebook is a work guide. Submit the two `.py` files only. Do not change the
source CSV or checkers.

## Input data

`data/water-points-practice.csv` contains 31 fictional daily records and no
personal information. One row is one facility on one date.

| Column | Meaning |
|---|---|
| `date` | operating date |
| `facility_id`, `facility_name` | facility identifier and name |
| `district` | district label as submitted |
| `rated_litres_per_hour` | rated hourly output |
| `operating_hours` | hours operated that day |
| `water_delivered_litres` | delivered litres recorded by the meter |
| `households_served` | households relying on the facility |
| `sensor_status` | submitted sensor state |

The header is source row 1. Insert `source_row` from 2 for the data records.

## Stage 1 — Inspect the source

| Function | Contract |
|---|---|
| `load_records(path)` | read and return the CSV, preserving row and column order |
| `build_key_date_view(records)` | return a facility-ID/date ascending copy with a fresh index; leave `records` unchanged |
| `count_raw_values(records, column)` | return a `value, records` table in first-appearance order without cleaning |

The completed `main()` displays shape, columns, inferred dtypes, all 31 rows,
the facility/date view, missing counts, raw district counts, and raw sensor
status counts. Do not correct, flag, remove, aggregate, or rank in Stage 1.

```text
python projects/water-point-review/inspect_water_points.py
python projects/water-point-review/check_inspect_water_points.py
```

## Stage 2 — Audit and rank facilities

Work on a deep copy and apply these rules.

1. Strip surrounding whitespace from `date` and `facility_id`.
2. Preserve `district_raw`; create working district with strip and title case.
3. Preserve `sensor_status_raw`; create working sensor status with strip and
   lowercase.
4. Convert the four numeric columns with
   `pd.to_numeric(..., errors="coerce")`.
5. `missing_number`: any required number is missing after conversion.
6. `negative_number`: any converted number is below zero.
7. `impossible_output`: delivered litres exceed
   `rated litres/hour × operating hours × 1.05`.
8. `sensor_not_ok`: the cleaned sensor status is not exactly `ok`.
9. `duplicate_facility_date`: mark every row in a duplicated stripped
   date/facility key (`keep=False`).

Issue texts, in order, are:

```text
missing required number
negative number
delivery exceeds rated capacity
sensor status is not ok
duplicate facility/date
```

Join multiple texts with `; `. A row is valid only when all five flags are
false. A missing comparison does not alone create an impossible-output flag.

## Analysis and ranking

For valid records:

```text
rated_capacity_litres = rated_litres_per_hour * operating_hours
stopped_day = operating_hours == 0 and water_delivered_litres == 0
low_output_day = operating_hours > 0 and
                 water_delivered_litres < rated_capacity_litres * 0.70
```

Group by facility ID and name. Calculate distinct valid dates, stopped days,
low-output days, total operating hours, total rated capacity, total delivered
litres, maximum households served, and output rate. Output rate is total
delivered divided by total rated capacity × 100; use `0.0` when total capacity
is zero.

Rank before rounding by stopped days descending, low-output days descending,
households served descending, then facility ID ascending. Add priorities from
1 and round output rate with pandas `.round(1)`.

## Published checkpoints

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: 5
ANALYSIS RECORDS: 26
FIRST INSPECTION: W004 — East Market Water Point
```

The flags contain one missing-number row, no negative row, one
impossible-output row, one sensor-not-ok row, and two duplicated-key rows. The
summary contains five facilities. W004 has 6 valid days, 2 stopped days, 2
low-output days, and an 81.2% output rate. A raw zero from W002 belongs to a
failed sensor and must not be treated as evidence of a real stoppage.

## Files and eight-function contract

Save `output/records_to_verify.csv` with
`source_row,date,facility_id,facility_name,issue`. Save
`output/facility_inspection_summary.csv` with the facility summary and
priority, both without pandas indexes.

| Function | Result |
|---|---|
| `load_records(path)` | validate required columns, select them, and insert source rows |
| `add_quality_flags(records)` | return a deep copy with cleaned keys/categories, numeric values, flags, issue, and validity |
| `build_verification_report(flagged)` | invalid rows in source order with the five audit columns |
| `build_analysis_data(flagged)` | valid rows plus capacity, stopped-day, and low-output-day fields |
| `summarise_facilities(analysis)` | ranked facility summary; do not fix logic to known IDs or five facilities |
| `select_first_inspection(summary)` | return priority-1 ID and name; raise `ValueError` when empty |
| `save_outputs(audit, summary, output_dir)` | create the directory and save both CSVs |
| `run_project(input_path, output_dir)` | connect all stages and return the five checkpoint values |

Do not change constants, function names, parameters, or the completed `main()`.

## Check and submit

```text
python projects/water-point-review/water_point_review.py
python projects/water-point-review/check_water_point_review.py
```

Inspect the generated CSVs before the checker. Completion requires
`ALL INSPECTION TESTS PASSED`, `ALL TESTS PASSED`, and `REVIEW READY`. Submit
exactly `inspect_water_points.py` and `water_point_review.py`.
