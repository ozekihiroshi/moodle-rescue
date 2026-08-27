# Lesson 6.3: Reconcile and make the run reproducible

A chunked program can finish without being correct. The final lesson of Chapter 6 checks logic on known small data, reconciles every row in the large run, and leaves enough provenance for another person to reproduce the result.

## By the end of this lesson, you can

- check expected behaviour with a small fixture;
- reconcile source, analysis, and review counts;
- record input and processing provenance;
- reopen and validate a saved result.

## 6.3.1 Verify small before scaling up

A 120,000-row result cannot be checked row by row. First display the 48-row fixture and explain why a known record is valid or requires review. Use the same functions on the large source so that tested logic is scaled rather than replaced.

```python
fixture = pd.read_csv("data/clinic-stock-fixture.csv")
print(fixture.to_string(index=False))
valid, review = prepare_chunk(fixture)
print("Valid:", len(valid), "Review:", len(review))
```

Do not hard-code fixture answers. Apply the same schema and quality rules to both files.

## 6.3.2 Reconcile every row

```python
reconciled = source_records == analysis_records + review_records
if not reconciled:
    raise ValueError("Source records were not reconciled")
```

This control does not prove every calculation, but it establishes that rows did not silently disappear or become double-counted.

## 6.3.3 Retain processing conditions and provenance

A reproducible result needs more than code. Be able to identify:

- input filename and generation method;
- row count and required columns;
- chunk size used;
- quality rules and review count;
- output filenames.

Even with fictional non-personal data, keep the result tied to its source. With real data, avoid reading columns that the stated purpose does not require.

## 6.3.4 Reopen the saved CSV

```python
summary.to_csv("output/clinic_stock_summary.csv", index=False)
saved = pd.read_csv("output/clinic_stock_summary.csv")
assert list(saved.columns) == list(summary.columns)
assert len(saved) == len(summary)
```

A correct in-memory DataFrame can change through column names, index saving, or rounding. Inspect the file that will actually be shared or submitted.

## 6.3.5 Check the run independently

```python
first = process_file(source, chunksize=997)
second = process_file(source, chunksize=2048)
assert first["reconciled"]
assert second["reconciled"]
pd.testing.assert_frame_equal(first["summary"], second["summary"])
```

The checker does not create the answer for you. It independently tests boundaries and omissions after you have run and inspected your own program.

## Integrated practice

Process the fixture with two chunk sizes. Check row reconciliation, summary equality, source protection, and the columns and row count of the reopened CSV.

## Summary

- Verify rules on small known data before scaling.
- Account for every input row as analysis or review.
- Retain source, conditions, quality rules, and output provenance.
- Reopen the saved deliverable, not only the in-memory object.

## Chapter project

Next, combine reading plans, validation, chunk aggregation, reconciliation, saving, and visualisation to choose the first medicine resupply for the clinic network.
