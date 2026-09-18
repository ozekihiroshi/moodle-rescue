## Review: Verify saved data

Does the existence of an output CSV prove that saving was correct? Consider a book title containing a comma and revisit writing and rereading CSV files.

> [!ANSWER]
> Existence alone does not prove that columns and values are correct. Save to a separate file with `DictWriter`, reread with `DictReader`, and compare column names, record counts and values. Check that the title is still one value and that the original input was not changed.

Save, reread and compare before treating the task as complete.
