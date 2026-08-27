# Chapter 3 midterm practical project — Public bus service review

## Situation

The transport team can investigate one route first. Separate unreliable daily records, then rank routes by estimated passenger-delay minutes rather than delay alone.

## Two programs

Do not start from empty files. Complete the starters in `projects/bus-service-review/`.

1. `inspect_bus_service.py` (20 points) reads all 31 source records and displays the full table, the route/date view, dtypes, missing counts, and raw category values.
2. `bus_service_review.py` (80 points) applies the published quality rules, separates review records, aggregates valid records, ranks the result, and saves CSV evidence.

Edit and submit these two files only. Do not change or submit the source CSV, checkers, or Notebook.

## Input and work order

The source is `data/bus-service-practice.csv`. One row is one operational unit's record for one date. Do not change the source. Finish and inspect Stage 1 before implementing Stage 2.

```text
read source → view all and sorted records → pass Stage 1
→ create quality flags → separate review and analysis records
→ aggregate and rank → inspect saved CSVs → pass Stage 2 → submit two files
```

## Published checkpoints

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: 4
ANALYSIS RECORDS: 27
FIRST REVIEW: R002 — Market Loop
```

The longest average delay is R003, but the largest passenger impact is R002.

Stage 2 implements eight supplied functions: loading, quality flags, verification report, analysis data, summary, priority selection, saving, and `run_project`. Do not change their names, parameters, constants, or the completed `main()`.

## Checking and completion

```text
python projects/bus-service-review/inspect_bus_service.py
python projects/bus-service-review/check_inspect_bus_service.py
python projects/bus-service-review/bus_service_review.py
python projects/bus-service-review/check_bus_service_review.py
```

The project is complete when both checkers pass and Stage 2 prints `ALL TESTS PASSED` and `REVIEW READY`.
