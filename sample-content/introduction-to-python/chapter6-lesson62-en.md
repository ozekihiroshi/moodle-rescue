# Lesson 6.2: Aggregate correctly across chunks

The previous lesson read required columns in manageable chunks. This lesson combines chunk work into one correct whole-file result. The central idea is to retain only the state that must survive after a chunk is released.

## By the end of this lesson, you can

- retain totals and counts across chunks;
- merge a group split across chunk boundaries;
- explain why an average of averages can be wrong;
- test that chunk size does not change the result.

## 6.2.1 Define the state that must survive

You need not retain every record, but must retain the totals and counts required by final metrics. For district-medicine shortages, a dictionary key can be `(district, medicine)`.

```python
totals = {}
key = ("East", "Insulin")
totals.setdefault(key, {"clinic_days": 0, "stockout_hours": 0, "patients_turned_away": 0})
```

This dictionary grows with the number of decision groups, not the number of source rows.

## 6.2.2 Add partial aggregates to the whole

```python
part = chunk.groupby(["district", "medicine"], as_index=False).agg(
    clinic_days=("date", "size"),
    stockout_hours=("stockout_hours", "sum"),
    patients_turned_away=("patients_turned_away", "sum"),
)

for row in part.itertuples(index=False):
    key = (row.district, row.medicine)
    current = totals.setdefault(key, {
        "clinic_days": 0,
        "stockout_hours": 0,
        "patients_turned_away": 0,
    })
    current["clinic_days"] += row.clinic_days
    current["stockout_hours"] += row.stockout_hours
    current["patients_turned_away"] += row.patients_turned_away
```

The same key may appear in later chunks, so add to its state rather than replacing it.

## 6.2.3 Merge numerators and denominators, not averages

Simply averaging two chunk rates loses their different record counts.

```python
# Can be wrong
overall = (chunk1_rate + chunk2_rate) / 2

# Correct structure
overall = (chunk1_events + chunk2_events) / (chunk1_records + chunk2_records) * 100
```

Calculate rates and averages at the end. Across chunks, merge additive numerators, denominators, totals, and counts.

## 6.2.4 Count valid and review records together

```python
source_records += len(chunk)
valid, review = prepare_chunk(chunk)
analysis_records += len(valid)
review_records += len(review)
```

Silently dropping invalid rows loses the ability to explain what happened. Classify them in every chunk and account for the entire source.

## 6.2.5 Test independence from chunk size

```python
small = process_file(source, chunksize=997)
large = process_file(source, chunksize=2048)
pd.testing.assert_frame_equal(small["summary"], large["summary"])
```

Chunk boundaries are a computing convenience. If changing them changes the answer, the merge logic is wrong.

## Integrated practice

Process the 48-row fixture with chunk sizes 7 and 13. Confirm that source, analysis, review, and every district-medicine aggregate agree.

## Summary

- Retain small decision state instead of all source records.
- Add repeated group totals across chunk boundaries.
- Calculate rates only after merging numerators and denominators.
- Change chunk size to test result invariance.

## Next lesson

Equal output alone does not yet prove that processing was complete. Next, combine a known fixture, row reconciliation, and provenance into a reproducible run.
