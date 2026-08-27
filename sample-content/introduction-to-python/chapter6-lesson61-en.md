# Lesson 6.1: Inspect before loading

Chapter 5 turned summary tables into charts and evidence statements. When a source grows, however, loading the entire file into one DataFrame may itself be the wrong first step. This lesson moves from checks before opening a file to a selective, typed reading plan.

## By the end of this lesson, you can

- inspect file size and record meaning before a full read;
- sample a few rows to understand columns and value shapes;
- limit input with `usecols` and explicit types;
- compare DataFrame memory use.

## 6.1.1 Establish the file and analysis boundary

“Large” is not a fixed row count. Available memory, column count, and string length all matter. Start with file bytes, headers, and a few rows, then state what one row represents.

```python
from pathlib import Path
import pandas as pd

source = Path("data/clinic-stock-fixture.csv")
print("Bytes:", source.stat().st_size)
sample = pd.read_csv(source, nrows=5)
print(sample.columns.tolist())
print(sample)
```

`nrows=5` supports a reading plan. It cannot establish final missing counts or rankings.

## 6.1.2 Select only required columns

Reading irrelevant columns consumes memory and obscures the boundary of the question. A district-medicine shortage analysis does not need personal identifiers.

```python
needed = ["district", "medicine", "stockout_hours", "patients_turned_away"]
records = pd.read_csv(source, usecols=needed)
```

`usecols` is not merely a speed trick. It records which data the decision actually requires.

## 6.1.3 Do not leave every type to inference

CSV stores no data types. pandas infers them from values, and a later invalid value may change an inferred type. Choose identifiers as strings and convert measured values deliberately.

```python
typed = pd.read_csv(
    source,
    usecols=needed,
    dtype={"district": "string", "medicine": "string"},
)
typed["stockout_hours"] = pd.to_numeric(typed["stockout_hours"], errors="coerce")
```

A failed conversion becomes missing and can be counted for review instead of silently remaining text.

## 6.1.4 Measure memory use

```python
all_columns = pd.read_csv(source)
selected = pd.read_csv(source, usecols=needed)
print(all_columns.memory_usage(deep=True).sum())
print(selected.memory_usage(deep=True).sum())
```

The difference is modest on a fixture, but the same choice scales to hundreds of thousands of records. Compare measurements rather than relying on guesses.

## 6.1.5 Read without retaining every record

```python
for chunk in pd.read_csv(source, usecols=needed, chunksize=10_000):
    print(len(chunk))
```

With `chunksize`, pandas returns successive DataFrames. Process each one and retain only the small state required for the final decision.

## Integrated practice

Compare memory for all columns and the four required columns in the fixture. Then use `chunksize=10`, print each chunk length, and reconcile their sum with the source row count.

## Summary

- Start large-file work with size, columns, and record meaning.
- A small sample plans the read; it does not prove a whole-file conclusion.
- `usecols` and explicit types limit the analysis boundary.
- Chunk reading retains decision state rather than all records.

## Next lesson

Dividing input does not automatically create a correct whole-file statistic. Next, merge counts and totals across chunks without losing meaning.
