## Review: Connect input to output

An item costs 120 units of currency. The user enters `"3"` through `input()`. How can you display `TOTAL: 360`? Assume the input represents an integer.

> [!ANSWER]
> ```python
> count = int(input("Count: "))
> total = count * 120
> print(f"TOTAL: {total}")
> ```
>
> Input is initially text. Convert it to an integer before calculating and use an f-string to display the result.

Try inputs 3 and 5, then save. Run cells that wait for input one at a time.
