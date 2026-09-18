## Review: Check boundaries and branch order

Trace this code for scores 79, 80 and 90. At 90, does it also print `REVIEW`?

```python
if score >= 90:
    print("ON TRACK")
elif score >= 80:
    print("REVIEW")
else:
    print("SUPPORT")
```

> [!ANSWER]
> 79 prints `SUPPORT`, 80 prints `REVIEW`, and 90 prints only `ON TRACK`. An `if / elif / else` chain runs only the first branch whose condition is true.

Set `score = 79` before running the example. Predict what changes if the conditions are reordered.
