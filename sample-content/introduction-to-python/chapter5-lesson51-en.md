# Lesson 5.1: From a question to a chart

## Introduction

Chapter 3 produced verified summary tables. A chart is the next representation of such a table, not a decoration added after analysis. Begin with the decision question and the meaning of one plotted mark. Only then choose a chart type.

## What you will be able to do

- define the question and plotted grain before drawing;
- choose bars, lines, scatter, or a histogram for the relationship being examined;
- display and reconcile the plot table before the chart;
- label title, axes, units, period, and relevant count.

> **Required path:** 5.1.1–5.1.5. Complete 5.1.6 as the integrated practice.

## 5.1.1 Define the question and plotted grain

“Show the clinic data” is not yet a question. “Which clinic carried the largest total waiting burden over six weeks?” identifies a comparison, measure, period, and output grain. The source has one clinic–time-slot–week per row; the plot table must have one clinic per row.

```python
clinic = records.groupby(["clinic_id", "clinic_name"], as_index=False).agg(
    patients_seen=("patients_seen", "sum"),
    total_wait_minutes=("total_wait_minutes", "sum"),
)
display(clinic)
```

The displayed table is part of the evidence path. It makes the exact values behind each bar inspectable.

## 5.1.2 Compare category magnitudes with bars

Bars compare a small number of discrete categories. Their length encodes magnitude, so the length axis normally begins at zero. Sort deliberately when rank is part of the question.

```python
plot_data = clinic.sort_values("total_wait_minutes")
ax = plot_data.plot.barh(
    x="clinic_name", y="total_wait_minutes", legend=False
)
ax.set(
    title="Total waiting burden by clinic",
    xlabel="Total waiting time (minutes)",
    ylabel="",
)
ax.set_xlim(left=0)
```

A table of percentages can use a 0–100% axis. A count or duration uses its own unit; “rate” without a denominator is not a sufficient label.

## 5.1.3 Follow ordered change with a line

A line joins ordered observations. Parse and sort dates or periods and expose missing periods rather than silently treating them as zero. A line through unordered clinic names invents a path that does not exist.

```python
trend = records.query(
    "clinic_id == 'C002' and time_slot == 'Evening'"
).copy()
trend["average_wait_minutes"] = (
    trend["total_wait_minutes"] / trend["patients_seen"]
)
trend = trend.sort_values("week")
```

## 5.1.4 Examine relationships and distributions

A scatter plot links two quantitative values from the same record. It can expose clusters and unusual records but does not establish that one variable caused the other. A histogram groups one quantitative variable into adjacent intervals. Bin width changes the visible shape, so report the record count and inspect more than one reasonable width.

| Question | Suitable chart | One mark represents |
|---|---|---|
| compare clinics | bar | one clinic total or rate |
| change by week | line | one ordered weekly value |
| patient volume and average wait | scatter | one clinic-time-week record |
| spread of average waits | histogram | count of records in a numeric interval |

## 5.1.5 Verify and label the plotted evidence

Before drawing, reconcile the plot table with the analysis data.

```python
assert clinic["patients_seen"].sum() == records["patients_seen"].sum()
assert clinic["total_wait_minutes"].sum() == records["total_wait_minutes"].sum()
```

The finished chart needs a specific title, axis labels, units, covered period, and useful sample count. A legend is needed only when it identifies multiple series. Do not rely on colour alone; direct labels, contrast, markers, and line styles can carry the same distinction.

## 5.1.6 Integrated practice

Create a clinic-level bar chart of patients seen. Display the plot table first. State what one bar represents, its unit, the six-week period, and the reconciliation performed.

## Summary

- The question determines plot grain and chart type.
- Bars compare categories, lines follow order, scatter examines paired quantities, and histograms show a distribution.
- A chart remains traceable when its plot table, checks, labels, units, period, and count are visible.

## Next lesson

One dataset can support several correct charts that answer different questions. Lesson 5.2 examines how totals, averages, percentages, scales, and subgroup grain can produce different rankings.
