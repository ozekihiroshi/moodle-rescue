# 6.4 Project brief and completion criteria

## Situation

A regional health office receives daily medicine-stock records from several clinics. It must identify the district and medicine where stock shortages turned away the most patients, then choose the first resupply for the coming week.

The source contains 120,000 records, so the program must not depend on loading the entire file at once. Verify the logic with a small fixture, then process only required columns in chunks.

## Program to complete

Edit the supplied file below. This is not a project where you start another program from a blank file.

```text
projects/clinic-stock-scaleup/clinic_stock_scaleup.py
```

The finished program must account for every row as either analysis or review, aggregate by district and medicine, and identify the pair with the greatest number of patients turned away.

## Supplied files

- `data/clinic-stock-fixture.csv`: 48 manually inspectable records
- `generate_clinic_stock_data.py`: completed generator for 120,000 fictional records
- `clinic_stock_scaleup.py`: starter to complete
- `check_clinic_stock_scaleup.py`: automated checker
- `README.md`: implementation order and function contracts

The data are fictional and contain no personal information. Do not edit the generator or checker.

## Workflow

1. Inspect the 48-row fixture as a table.
2. Implement the nine starter functions in order.
3. Process the fixture and reconcile every source row.
4. Generate the 120,000-row CSV from the Notebook.
5. Process the large CSV in chunks.
6. Confirm that another chunk size gives the same summary.
7. Pass the checker and inspect the saved CSV and PNG.

## Quality rules

Send a row to review and exclude it from aggregation when it has any of these problems:

- a required text value is blank;
- a number is blank or cannot be converted;
- a numeric value is negative;
- `stockout_hours` is outside 0–24;
- `closing_units != opening_units + received_units - dispensed_units`.

Join multiple reasons with `|`. Never modify the source CSV.

## Aggregation and priority

Group valid rows by `district` and `medicine`, calculating `clinic_days`, `stockout_days`, `stockout_hours`, `patients_turned_away`, and `stockout_rate`.

Sort by patients turned away descending, stockout hours descending, then district and medicine ascending. The first row is the first resupply.

## Reconciliation

The following control must always hold:

```text
SOURCE RECORDS = ANALYSIS RECORDS + RECORDS TO REVIEW
```

Chunk size changes how records are processed, not the result. Sizes such as `997`, `2,048`, and `10,000` must produce the same final summary and priority.

## Checkpoints for 120,000 records

```text
SOURCE RECORDS: 120000
ANALYSIS RECORDS: 119977
RECORDS TO REVIEW: 23
RECONCILED: True
FIRST RESUPPLY: East — Insulin
PATIENTS TURNED AWAY: 367492
```

Use these values to check your processing. Do not write them directly into your program.

## Submit

1. `clinic_stock_scaleup.py`
2. `clinic_stock_summary.csv`
3. `clinic_stock_evidence.png`

Do not submit the generated 120,000-row source, Notebook, or checker.

## Completion

Run:

```text
python projects/clinic-stock-scaleup/check_clinic_stock_scaleup.py
```

The final lines must be:

```text
ALL TESTS PASSED
SCALE-UP VERIFIED
```
