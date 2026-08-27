# Clinic stock scale-up

Edit only `clinic_stock_scaleup.py`. Keep the generator, fixture, and checker unchanged.

Implement in this order:

1. `required_columns()` — return the 11 required names in source order.
2. `validate_schema(columns)` — raise `ValueError` listing missing columns.
3. `prepare_chunk(chunk)` — deep-copy, trim text, convert numbers, and return `(valid, review)`.
4. `update_totals(totals, valid)` — add one chunk's grouped sums to the running dictionary.
5. `build_summary(totals)` — create and sort the decision-sized DataFrame.
6. `select_priority(summary)` — return the first row; reject an empty summary.
7. `process_file(path, chunksize)` — iterate through `pd.read_csv(..., chunksize=...)` and reconcile counts.
8. `save_outputs(summary, priority, output_dir)` — save the required CSV and labelled PNG.
9. `run_project(source, output_dir, chunksize)` — connect the workflow and return counts, summary, and priority.

`prepare_chunk` must not modify its input. `process_file` must not concatenate all chunks into one large DataFrame. Calculate `stockout_rate` from merged counts, not by averaging chunk percentages.
