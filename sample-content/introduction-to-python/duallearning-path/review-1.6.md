## Review: Trace each iteration

Write the value of `total` after each iteration. Explain which values `range(1, 4)` produces.

```python
total = 0
for number in range(1, 4):
    total = total + number
print(total)
```

> [!ANSWER]
> `number` takes 1, 2 and 3. `total` changes from 0 to 1, 3 and 6, so the final output is `6`. The stop value 4 is excluded. Moving the initialization inside the loop would discard the accumulated total each time.

Check and save your work. Project 1.7 uses the same idea to update weekly totals after each day.
