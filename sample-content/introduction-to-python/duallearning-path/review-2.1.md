## Review: Change a record deliberately

Why does changing a dictionary assigned to `found` also change the record in the original list? Revisit assignment, copying and searching in 2.1.

> [!ANSWER]
> Both refer to the same dictionary; assignment has not created a new one. Changing the dictionary found by ID changes that record in the list. Check that the search result is not `None` before changing it.

Explain the difference between finding a record and making an independent copy.
