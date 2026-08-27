# 3.5B Midterm practical project — Public bus service review

## Situation

The transport team can fund one route investigation first. Daily reports show
scheduled trips, completed trips, passengers, and total delay minutes. The
route with the longest delay per trip is not necessarily the route causing the
largest delay across passengers. Prepare a verification list, a ranked route
summary, and the first route to investigate. Do not alter the source CSV.

## Two programs to complete

Do not start from empty files. Python Lab supplies two starters in
`projects/bus-service-review/`.

1. Complete `inspect_bus_service.py` to read and display the source. This is
   Stage 1 and is worth 20 points.
2. Complete the eight existing functions in `bus_service_review.py` to audit,
   aggregate, rank, and save the result. This is Stage 2 and is worth 80 points.

The Notebook is a work guide, not a submission. Submit the two `.py` files.
Do not edit the input CSV or either checker.

## Input data

`data/bus-service-practice.csv` contains 31 fictional daily records and no
personal information. One row is one route on one date.

| Column | Meaning |
|---|---|
| `date` | operating date |
| `route_id`, `route_name` | route identifier and name |
| `district` | district label as submitted |
| `scheduled_trips` | trips planned for that date |
| `completed_trips` | trips completed |
| `passengers` | passenger boardings recorded |
| `delay_minutes` | total delay minutes across completed trips |

The CSV header is source row 1. `load_records()` inserts `source_row` 2 for the
first data record.

## Stage 1 — Inspect the source

Complete these three functions:

| Function | Contract |
|---|---|
| `load_records(path)` | read and return the CSV without changing row or column order |
| `build_key_date_view(records)` | return a new route-ID/date ascending view with a fresh index; do not change `records` |
| `count_raw_values(records, column)` | return a `value, records` table in first-appearance order without cleaning the values |

The supplied `main()` displays shape, columns, inferred dtypes, all 31 rows,
the route/date view, missing counts, and raw district counts. At this stage do
not correct labels, add flags, remove rows, aggregate routes, or choose a route.

```text
python projects/bus-service-review/inspect_bus_service.py
python projects/bus-service-review/check_inspect_bus_service.py
```

## Stage 2 — Audit and rank routes

Apply the following rules to a deep copy of the loaded records.

1. Strip surrounding whitespace from `date` and `route_id`.
2. Preserve the submitted district in `district_raw`; create working
   `district` with strip and title case.
3. Convert all four numeric columns with
   `pd.to_numeric(..., errors="coerce")`.
4. `missing_number`: any required number is missing after conversion.
5. `negative_number`: any converted number is below zero.
6. `impossible_trips`: completed trips exceed scheduled trips.
7. `passengers_without_trip`: passengers are positive while completed trips
   are zero.
8. `duplicate_route_date`: mark every row in a duplicated stripped
   `date + route_id` key, using the equivalent of `keep=False`.

Build `issue` from every true flag in the listed order using these texts:

```text
missing required number
negative number
completed trips exceeds scheduled trips
passengers recorded with zero completed trips
duplicate route/date
```

Join multiple texts with `; `. A row is valid only when all five flags are
false. Missing comparisons do not create an additional flag by themselves.

## Analysis and ranking

For each valid row:

```text
cancelled_trips = scheduled_trips - completed_trips
passenger_delay_minutes = delay_minutes / completed_trips * passengers
```

When completed trips are zero and passengers are also zero, passenger delay is
`0.0`. Group by both route ID and route name, then calculate:

- distinct valid dates;
- total scheduled, completed, and cancelled trips;
- total passengers and delay minutes;
- total estimated passenger-delay minutes;
- average delay minutes = total delay / total completed trips;
- cancellation rate = total cancelled / total scheduled × 100.

Rank before rounding by passenger-delay minutes descending, cancellation rate
descending, then route ID ascending. Add priorities from 1 and round the three
calculated decimal columns to one decimal with pandas `.round(1)`.

## Published checkpoints

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: 4
ANALYSIS RECORDS: 27
FIRST REVIEW: R002 — Market Loop
```

The flags contain one missing-number row, no negative row, one impossible-trip
row, no passengers-without-trip row, and two duplicate-key rows. The final
summary contains five routes. R002 has 6 valid days, 25,920.0 estimated
passenger-delay minutes, and 6.0 average delay minutes. R003 has the longest
average delay, 15.0 minutes, but is not first after passenger impact is added.

## Files and eight-function contract

Save `output/records_to_verify.csv` with
`source_row,date,route_id,route_name,issue`. Save
`output/route_review_summary.csv` with the route summary and priority. Save
without pandas indexes.

| Function | Result |
|---|---|
| `load_records(path)` | validate required columns, select them, insert source rows |
| `add_quality_flags(records)` | return a deep copy with cleaned keys, numeric values, five flags, issue, and validity |
| `build_verification_report(flagged)` | invalid rows in source order with the five audit columns |
| `build_analysis_data(flagged)` | valid rows plus cancelled trips and passenger-delay minutes |
| `summarise_routes(analysis)` | ranked route summary; do not fix logic to five routes or known IDs |
| `select_first_review(summary)` | return the priority-1 ID and name; raise `ValueError` when empty |
| `save_outputs(audit, summary, output_dir)` | create the directory and save both CSVs |
| `run_project(input_path, output_dir)` | connect all stages and return the five printed checkpoint values |

Do not change constants, function names, parameters, or the completed `main()`.

## Check and submit

```text
python projects/bus-service-review/bus_service_review.py
python projects/bus-service-review/check_bus_service_review.py
```

Inspect the two generated CSVs before running the checker. Completion requires
`ALL INSPECTION TESTS PASSED`, `ALL TESTS PASSED`, and `REVIEW READY`. Submit
exactly `inspect_bus_service.py` and `bus_service_review.py`.
