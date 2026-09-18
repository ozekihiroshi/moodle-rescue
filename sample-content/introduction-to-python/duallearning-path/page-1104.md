# 5.4 Applied project — Clinic waiting-time evidence

## Situation

A district health coordinator has one temporary support team available next week. Three clinics have recorded, for six weeks, the number of patients seen, total waiting minutes, and the number who waited more than 60 minutes in Morning and Evening service.

The coordinator needs two different answers. Which clinic carried the greatest total waiting burden? Which clinic and time slot gave an individual patient the most difficult waiting experience? A single total or a single average cannot answer both questions.

## Your task

Do not start from an empty file. Open and complete:

```text
projects/clinic-wait-evidence/clinic_wait_evidence.py
```

The program reads the supplied CSV, validates it, creates two summaries, selects both targets, saves a summary CSV and a two-panel PNG, and prints a short evidence note. Edit only this Python file.

## Input data

The source is:

```text
projects/clinic-wait-evidence/data/clinic-waits-practice.csv
```

It contains 36 fictional records: three clinics × two time slots × six weeks. One row represents one clinic's Morning or Evening service in one week. Do not edit or overwrite it.

| Column | Meaning |
|---|---|
| `week` | reporting week |
| `clinic_id`, `clinic_name` | stable clinic identity |
| `time_slot` | `Morning` or `Evening` |
| `patients_seen` | patients included in the record |
| `total_wait_minutes` | sum of their waiting times |
| `over_60_minutes` | patients who waited more than 60 minutes |

## Work to complete

Implement the nine supplied functions in this order:

1. `load_records(path)` — read the required columns and convert the three numeric columns.
2. `validate_records(records)` — reject empty input, missing required values, negative numbers, over-60 counts greater than patients, duplicate week/clinic/time-slot rows, and unknown time slots.
3. `build_burden_summary(records)` — one row per clinic with compatible totals, calculated average wait and over-60 rate; order by total waiting minutes descending, then clinic ID.
4. `build_service_summary(records)` — one row per clinic and time slot with the same measures; order by unrounded average wait descending, over-60 rate descending, clinic ID, and time slot.
5. `choose_targets(...)` — return the published target dictionary from the first row of each summary.
6. `create_evidence_figure(...)` — save one PNG containing total burden by clinic and average wait by clinic/time slot. Bar-length axes start at zero; titles, units, period, and the support target are visible.
7. `build_evidence_note(...)` — return exactly three sentences: total-burden observation, targeted-service observation with both numbers, and a limitation that the records do not establish cause.
8. `save_summary(...)` — round only the saved copy to one decimal place and write the service summary without an index.
9. `run_project(...)` — connect the complete work and return source count, both summaries, targets, and evidence note.

The completed `main()` is provided. Do not change function names, parameters, constants, or output labels.

## Manual checkpoints

Correct processing of the supplied data produces:

```text
SOURCE RECORDS: 36
TOTAL BURDEN CLINIC: C001 — Central Clinic
SUPPORT TARGET: C002 — Riverside Clinic — Evening
TARGET AVERAGE WAIT: 48.1 MINUTES
TARGET OVER-60 RATE: 32.4%
```

The result is intentional: the clinic carrying the greatest total burden is not the same as the clinic/time slot with the worst average patient experience.

## Run and check

Save with `Ctrl+S`, then run:

```text
python projects/clinic-wait-evidence/clinic_wait_evidence.py
python projects/clinic-wait-evidence/check_clinic_wait_evidence.py
```

Open `output/clinic_wait_summary.csv` and `output/clinic_wait_evidence.png`. Confirm that the plotted categories and values agree with the saved table. Continue until the checker ends with `ALL TESTS PASSED` and `EVIDENCE READY`.

## Submission

Submit these two files:

1. `clinic_wait_evidence.py`
2. `clinic_wait_evidence.png`

The generated summary CSV remains in Python Lab as working evidence. Do not submit the source CSV, checker, Notebook, or output CSV.
