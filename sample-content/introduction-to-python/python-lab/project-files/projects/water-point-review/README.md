# Chapter 3 midterm practical project — Community water-point inspection

## Situation

The maintenance team can visit one facility first. Separate failed-sensor and contradictory records, then rank repeated stoppages and low-output days.

## Two programs

Do not start from empty files. Complete the starters in `projects/water-point-review/`.

1. `inspect_water_points.py` (20 points) reads all 31 source records and displays the full table, the facility/date view, dtypes, missing counts, and raw category values.
2. `water_point_review.py` (80 points) applies the published quality rules, separates review records, aggregates valid records, ranks the result, and saves CSV evidence.

Edit and submit these two files only. Do not change or submit the source CSV, checkers, or Notebook.

## Input and work order

The source is `data/water-points-practice.csv`. One row is one operational unit's record for one date. Do not change the source. Finish and inspect Stage 1 before implementing Stage 2.

```text
read source → view all and sorted records → pass Stage 1
→ create quality flags → separate review and analysis records
→ aggregate and rank → inspect saved CSVs → pass Stage 2 → submit two files
```

## Published checkpoints

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: 5
ANALYSIS RECORDS: 26
FIRST INSPECTION: W004 — East Market Water Point
```

The raw minimum includes a failed sensor; the valid-record priority is W004.

Stage 2 implements eight supplied functions: loading, quality flags, verification report, analysis data, summary, priority selection, saving, and `run_project`. Do not change their names, parameters, constants, or the completed `main()`.

## Checking and completion

```text
python projects/water-point-review/inspect_water_points.py
python projects/water-point-review/check_inspect_water_points.py
python projects/water-point-review/water_point_review.py
python projects/water-point-review/check_water_point_review.py
```

The project is complete when both checkers pass and Stage 2 prints `ALL TESTS PASSED` and `REVIEW READY`.
