# Chapter 3 midterm practical project A — Decide tomorrow's additional delivery

## Situation

Six schools have submitted six days of meal-service records. Regular deliveries for tomorrow are arranged, but one vehicle can make one additional school visit. Before the morning meeting, prepare a list of records that require verification and a ranked school summary.

Some records are incomplete, contradictory, duplicated, or use inconsistent district labels. Do not guess uncertain replacements. Separate records that cannot support the decision and rank the schools from the remaining records.

## Two programs to complete

Do not start from empty files. Python Lab supplies two starters in `projects/school-meal-review/`.

1. Complete `inspect_school_meals.py`, a small program that reads and displays the source data. This is Stage 1 and is worth 20 points.
2. Then complete the existing eight functions in `meal_delivery_review.py`. This is Stage 2 and is worth 80 points.

The Project Notebook is a work guide used to run both programs and view their output. It is not submitted. Neither program may edit or overwrite the source CSV.

## Input data

The input is `projects/school-meal-review/data/school-meals-practice.csv`. It contains fictional operational data and no personal information. One row represents one school's report for one date.

| Column | Meaning |
|---|---|
| `date` | meal-service date |
| `school_id` | school identifier |
| `school_name` | school name |
| `district` | district label as submitted |
| `pupils_present` | pupils present that day |
| `meals_delivered` | meals delivered that day |
| `meals_served` | meals actually served that day |

Treat the CSV header as source row 1 and insert `source_row` 2 for the first data record. The source file contains 37 data rows.

## Stage 1 — Complete the source inspection program

Data work begins by checking the shape and content of the source before applying quality decisions. Open `inspect_school_meals.py` and complete its three small functions. The finished program must:

1. read the supplied CSV with pandas;
2. display the row and column counts;
3. display the column names and pandas-inferred dtypes;
4. display all 37 records without truncating rows;
5. display a separate view sorted by `school_id` and then `date`;
6. display missing-value counts for every column; and
7. display each raw `district` value and its count.

Use the raw district strings exactly as recorded at this stage. Do not add quality flags, remove records, correct values, aggregate schools, or decide delivery priority. `load_records(path)` must work with another CSV of the same columns rather than containing the 37 sample rows in source code. `build_school_date_view(records)` returns a sorted copy without changing `records`. `count_district_values(records)` counts raw values, including inconsistent spelling or whitespace.

### Stage 1 function contract

| Function | Return value |
|---|---|
| `load_records(path)` | A pandas DataFrame read from `path`, preserving the CSV row order and column order |
| `build_school_date_view(records)` | A new DataFrame sorted by `school_id` ascending and then `date` ascending, with a new index starting at 0; `records` is unchanged |
| `count_district_values(records)` | A two-column DataFrame named `district, records`; raw district strings remain distinct and rows follow the order in which each value first appears |

The completed `main()` is supplied. It displays both 37-row tables with `to_string(index=False)` so pandas cannot replace middle rows with an ellipsis. It displays district values with Python quotes, making leading or trailing whitespace visible.

Run the program and its small checker before Stage 2:

```text
python projects/school-meal-review/inspect_school_meals.py
python projects/school-meal-review/check_inspect_school_meals.py
```

The full table and sorted table are intentionally verbose: their purpose is to let a person see the supplied source before the more difficult processing begins.

## Stage 2 — Check quality and decide the additional delivery

After completing the source-inspection program, open `meal_delivery_review.py`. Complete its existing eight functions to turn the observed source into review records, analysis data, a school ranking, and two saved CSV files. The following sections are the public contract for Stage 2.

### Processing order and verification rules

Apply the rules in this order so that the same source produces the same flags.

1. Strip surrounding whitespace from `date` and `school_id`. Do not convert or otherwise normalise the date format. Duplicate matching uses these stripped strings.
2. Convert `pupils_present`, `meals_delivered`, and `meals_served` with `pd.to_numeric(..., errors="coerce")`. An empty cell, whitespace-only value, or non-numeric value therefore becomes missing.
3. Set `missing_number` when any required numeric value is missing after conversion.
4. Set `negative_number` when any successfully converted numeric value is below zero. Missing values do not themselves make this flag true.
5. Set `impossible_service` when `meals_served > pupils_present` or `meals_served > meals_delivered`. Evaluate each comparison only when both values needed for that comparison are present. A missing value does not by itself make this flag true.
6. Set `duplicate_school_date` on every row whose stripped `date` and `school_id` key occurs more than once. Use the equivalent of `duplicated(..., keep=False)`.

Preserve the submitted district in `district_raw`. Create the working `district` with the equivalent of `.astype("string").str.strip().str.title()`. The supplied file has no blank district, date, school-ID, or school-name values. This project does not add a quality flag for those fields, and it does not correct school-name variation.

Build `issue` from every true flag, in the order shown below, joined by `; `:

| Flag | Public issue text |
|---|---|
| `missing_number` | `missing required number` |
| `negative_number` | `negative number` |
| `impossible_service` | `meals served exceeds limit` |
| `duplicate_school_date` | `duplicate school/date` |

One row may contain several issue texts. Set `is_valid` to true only when all four flags are false. `records_to_verify` means the number of invalid rows, not the number of issue labels.

### Summary and ranking

For valid rows, calculate `unmet_meals = pupils_present - meals_served`. Group by both `school_id` and `school_name`. If one school ID appears with different school names, treat those names as separate groups; this project does not repair that variation.

Return columns in this order:

```text
priority, school_id, school_name, valid_days, pupils_present,
meals_served, unmet_meals, shortage_days,
meal_coverage_rate, average_unmet_meals
```

- `valid_days`: number of distinct valid dates
- `shortage_days`: number of valid rows where `unmet_meals > 0`
- `meal_coverage_rate`: total meals served divided by total pupils present, times 100
- `average_unmet_meals`: total unmet meals divided by valid days

If a group's total pupils present is zero, set `meal_coverage_rate` to `0.0`. Rank before rounding by average unmet descending, shortage days descending, then school ID ascending. The school ID is the deterministic tie-break key. Add priorities from 1, then use pandas `.round(1)` for coverage and average unmet.

### Expected checkpoints for the supplied CSV

These values let you check your own work without revealing which source rows are invalid.

```text
SOURCE RECORDS: 37
RECORDS TO VERIFY: 4
ANALYSIS RECORDS: 33
FIRST DELIVERY: S004 — Market Road School
```

The four flags contain 1 missing-number row, 0 negative-number rows, 1 impossible-service row, and 2 duplicate-key rows. The final summary contains 6 groups. Its first row has `average_unmet_meals` 7.5 and `shortage_days` 6. If your values differ, inspect the intermediate flags and summary before running the checker.

### Output files

Save without pandas indexes:

1. `output/records_to_verify.csv` with `source_row,date,school_id,school_name,issue`
2. `output/school_delivery_summary.csv` with the ten summary columns

The completed `main()` prints the four-line checkpoint above after the heading `SCHOOL MEAL DELIVERY REVIEW`.

### Eight-function contract

| Function | Input and result |
|---|---|
| `load_records(path)` | Validate required columns, select them, and insert source rows |
| `add_quality_flags(records)` | Return a deep copy with stripped keys, converted numbers, district fields, four flags, `issue`, and `is_valid`; do not change `records` |
| `build_verification_report(flagged)` | Return invalid rows in source order with the five required columns |
| `build_analysis_data(flagged)` | Return valid required columns plus `unmet_meals` with a fresh index |
| `summarise_schools(analysis)` | Return the ranked ten-column summary without fixing logic to six schools or known IDs |
| `select_first_delivery(summary)` | Return `{"school_id": ..., "school_name": ...}` for priority 1; raise `ValueError` if empty |
| `save_outputs(audit, summary, output_dir)` | Create the directory and save both output CSV files |
| `run_project(input_path, output_dir)` | Connect all stages and return the five values below |

| `run_project()` key | Meaning |
|---|---|
| `source_records` | input CSV data-row count |
| `records_to_verify` | invalid-row count; a multi-issue row is counted once |
| `analysis_records` | valid rows used for aggregation |
| `first_delivery_id` | priority-1 school ID |
| `first_delivery_name` | priority-1 school name |

Do not change constants, function names, parameters, or the supplied `main()`.

## Work order, checking, and submission

Complete the project in this order:

```text
read the CSV
→ display and inspect the source
→ complete inspect_school_meals.py and pass its automatic check
→ read the quality rules
→ implement the eight production functions
→ inspect the generated CSVs
→ complete meal_delivery_review.py and pass its automatic check
→ submit both programs
```

The Notebook only guides these operations. No observation essay or final report is required.

### Assessment

| Submitted program | Points | What is checked |
|---|---:|---|
| `inspect_school_meals.py` | 20 | reads the supplied and alternate CSVs; preserves the source; shows shape, names, dtypes, every row, a school/date view, missing counts, and raw district counts |
| `meal_delivery_review.py` | 80 | the existing eight functions implement the published quality, separation, aggregation, ranking, and saving contract; all ten production checks pass |

Run both checkers after inspecting the program output:

```text
python projects/school-meal-review/check_inspect_school_meals.py
python projects/school-meal-review/check_meal_delivery_review.py
```

Submit exactly these two files to Moodle:

1. `inspect_school_meals.py`
2. `meal_delivery_review.py`

Generated CSVs and the Project Notebook remain in Python Lab as working material and are not submitted.
