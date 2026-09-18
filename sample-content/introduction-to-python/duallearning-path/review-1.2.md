## Review: Trace assignment and state

Predict `total` and `price` after these lines, then check in Python Lab.

```python
price = 120
total = price * 3
price = 150
```

> [!ANSWER]
> `total` is `360` and `price` is `150`. Assignment stores the result at that point. Changing `price` later does not automatically recalculate `total`.

If this is unclear, write the value of each variable after every line.
