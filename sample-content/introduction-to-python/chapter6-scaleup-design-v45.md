# Chapter 6 design — Scaling reliable analysis

## Chapter purpose

Chapter 6 closes the course by showing that a correct small-data analysis is not automatically a reliable large-data workflow. Learners first inspect file size and schema, then process repeated chunks without losing the meaning of totals, and finally prove that the result is complete and reproducible.

## Learning path

1. **6.1 Inspect before loading** — estimate size, sample safely, select columns, and set data types.
2. **6.2 Aggregate across chunks** — carry state across chunks, merge compatible totals, and avoid averages of averages.
3. **6.3 Reconcile and reproduce** — test on a small fixture, account for every source row, repeat with another chunk size, and retain provenance.
4. **6.4 Capstone project** — process a generated clinic-medicine stock dataset and identify the district-medicine pair needing first resupply.

## Capstone situation

A regional health office receives daily medicine-stock records from multiple clinics. The full file is too large to treat as a classroom-sized table. The office must identify where medicine shortages turned away the most patients, while excluding invalid records and proving that every input row was either analysed or sent for review.

The supplied generator creates a deterministic 120,000-row fictional CSV. A small fixture is also supplied so learners can understand and manually verify the workflow before scaling up.

## Required discovery

The project deliberately separates three concerns:

- the source may be large, but the final decision table is small;
- chunk boundaries must not change the result;
- `source = analysed + review` is a required control, not an optional comment.

The checker tests the learner functions with another compatible file and different chunk sizes. It does not reward hard-coded results from the supplied 120,000 rows.

## Submission

- `clinic_stock_scaleup.py`
- `clinic_stock_summary.csv`
- `clinic_stock_evidence.png`

The source CSV is generated locally and is not submitted.
