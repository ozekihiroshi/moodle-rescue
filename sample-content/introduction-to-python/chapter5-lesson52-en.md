# Lesson 5.2: Honest comparisons

## Introduction

Central Clinic may carry the greatest total waiting burden because it serves more patients, while a smaller service may give each patient a longer wait. Both findings can be true. An honest comparison names the question, numerator, denominator, grain, scale, and group size.

## What you will be able to do

- distinguish total burden, average experience, and percentage affected;
- calculate a combined rate from compatible totals;
- preserve group size, missingness, order, and unrounded ranking values;
- recognise when a scale or aggregation changes the impression.

> **Required path:** 5.2.1–5.2.5. Complete 5.2.6 as the integrated practice.

## 5.2.1 Separate three kinds of question

| Metric | Calculation | Question answered |
|---|---|---|
| total waiting minutes | sum of minutes | where was total burden greatest? |
| average wait per patient | total minutes ÷ patients | where was a typical recorded wait longest? |
| over-60 rate | over-60 patients ÷ patients × 100 | where was a long wait most common? |

Do not describe one as a substitute for another. A staffing decision may need total burden; a targeted process investigation may need the experience by clinic and time slot.

## 5.2.2 Aggregate compatible values before dividing

For six combined weeks, add patient counts and waiting minutes first, then divide. The unweighted mean of six weekly averages gives a small week the same influence as a large week.

```python
service = records.groupby(
    ["clinic_id", "clinic_name", "time_slot"], as_index=False
).agg(
    patients_seen=("patients_seen", "sum"),
    total_wait_minutes=("total_wait_minutes", "sum"),
    over_60_minutes=("over_60_minutes", "sum"),
    source_records=("week", "size"),
)
service["average_wait_minutes"] = (
    service["total_wait_minutes"] / service["patients_seen"]
)
service["over_60_rate"] = (
    service["over_60_minutes"] / service["patients_seen"] * 100
)
```

## 5.2.3 Keep the comparison grain and group size visible

Clinic-level and clinic–time-slot tables answer different questions. Do not place rows of different grain in the same ranking. Show patient count beside an average or percentage; 48 minutes from 210 patients carries different context from 48 minutes from two patients.

## 5.2.4 Use scale and order without exaggeration

A bar chart comparing lengths begins at zero. Starting 46 and 48 minutes at 45 makes a two-minute difference look enormous. A restricted scale may support close reading of a line chart, but it must be disclosed and the absolute change stated.

Rank on unrounded values and round only for display. Finish ties with a stable ID so the output remains reproducible.

```python
ranked = service.sort_values(
    ["average_wait_minutes", "over_60_rate", "clinic_id", "time_slot"],
    ascending=[False, False, True, True],
)
```

## 5.2.5 Preserve missingness and subgroup differences

Missing is not zero. If a week is absent, expose the gap and investigate before joining the surrounding points. An overall value can also hide opposite subgroup patterns. Always ask whether the decision concerns all patients, one clinic, or one time slot, and display the corresponding table.

## 5.2.6 Integrated practice

Create two rankings: total over-60 patients by clinic, and over-60 percentage by clinic and time slot. Display patient counts. Explain in two sentences why the first rows can differ without either calculation being wrong.

## Summary

- Totals, per-person values, and percentages answer different operational questions.
- Compatible counts are combined before division.
- Grain, group size, scale, order, rounding, missingness, and subgroup structure affect a comparison.

## Next lesson

Lesson 5.3 turns a verified comparison into a short statement that communicates what was observed without claiming an unsupported cause.
