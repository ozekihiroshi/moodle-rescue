## Introduction

**Read the first program**

Python Lab is ready, so begin with a short program. This example represents a course that runs three times a day for five days, with sixteen places in each session. Before running it, predict the three displayed lines.

## Learning outcomes

At the end of this lesson, you can:

- Identify instructions, values, expressions, and output in a short program.
- Predict execution from top to bottom and distinguish evaluation from display.
- Use parentheses to make the intended order of a calculation visible.
- Change a small program and check whether all displayed results remain consistent.

**Learning route:** Required: 1.1.1–1.1.3 \| Integration: 1.1.4

## 1.1.1 Read values, expressions, and visible output

```
print("Course delivery plan")
print(3 * 5)
print(3 * 5 * 16)
```

The Python interpreter normally executes these instructions from top to bottom. The first line displays a heading, the second displays 15 sessions, and the third displays 240 available places. Reading a program means tracing the instructions that produced a result, not merely looking at the result.

| Term        | In this example                      | Role                                                 |
|-------------|--------------------------------------|------------------------------------------------------|
| Instruction | `print(...)`                         | States an operation for the computer to perform      |
| Value       | `3`, `5`, `16`, and the heading text | Data handled by the program                          |
| Expression  | `3 * 5 * 16`                         | Evaluates to one value                               |
| Output      | The three displayed lines            | Makes a processed result visible outside the program |

### An expression makes a value; print() makes it visible

The expression `3 * 5` evaluates to the value 15. Passing that value to `print()` makes 15 appear on screen. Evaluation and output are two separate operations.

A Notebook helpfully displays `3 * 5` when it is the final expression in a cell. A normal `.py` script does not display that expression by itself. Use `print()` for an intended program output so that the intention remains clear in a Notebook, Console, or script.

### The kind of value changes what an operator means

These two lines look similar but do not produce the same result.

```
print(3 + 4)
print("3 + 4")
```

On the first line, `3` and `4` are numeric values, so `+` adds them and produces 7. On the second, everything inside the quotes is a text value, so the characters `3 + 4` are displayed unchanged. Every value has a kind that determines sensible operations. Later lessons develop type names and conversion systematically; for now, distinguish numbers from quoted text.

**Quick check:** Which part of the program calculates a value, and which part makes that value visible?

## 1.1.2 Express the intended calculation order

### Express the intended order of calculation

This lesson uses only addition `+`, subtraction `-`, and multiplication `*`. Multiplication is evaluated before addition or subtraction, while parentheses are evaluated first.

```
print(2 + 3 * 4)
print((2 + 3) * 4)
```

The first expression is 14 because `3 * 4` is evaluated first. The second is 20 because the parentheses produce 5 before multiplication. Even when you know precedence, parentheses can make work meaning explicit. To subtract morning 18 and afternoon 12 from capacity 40, `40 - (18 + 12)` clearly says to combine attendance before finding unused places.

## 1.1.3 Change a program and verify the result

### Changing a value exposes a weakness

The following program displays morning and afternoon places, their total, and unused capacity.

```
print("Workshop places")
print("Morning:", 18)
print("Afternoon:", 12)
print("Total:", 18 + 12)
print("Unused seats:", 40 - (18 + 12))
```

Predict all five lines, then run the cell from the top. Now change the morning places to 20. Because the value 18 is written directly in three locations, all three must be edited or the outputs contradict each other. Python cannot infer that those three values have the same meaning. Lesson 2 gives the value one name and makes this kind of change safer.

If the code will not run, check for a missing closing quote or parenthesis. If it runs, check that total and unused seats can both be true. The Chapter 0 distinction between syntax, execution, and a result that meets the purpose applies here.

**Quick check:** If one business value appears three times, what can go wrong when only one occurrence is changed?

## Integrated practice: a lighter rehearsal for the chapter project

**Connection to the chapter project:**

A small rehearsal for producing a labelled report, using book packing rather than the chapter project.

### What to build

A box holds 12 books and 4 full boxes are ready. Display a heading, books per box, boxes, and calculated total on four lines.

### Completion criteria

- Use only values, `*`, parentheses, and `print()`.

- Show four clear labels and calculate the total.

- Predict the output before running.

### Step-by-step guide

1.  Put text labels in quotes.

2.  Let Python calculate `12 * 4`; do not type 48 as the answer.

3.  Use one `print()` for each line.

**Build and run your own version before opening the model answer.**

> [!ANSWER]
> **Model answer and explanation**
>
> ```
> print("BOOK PACKING")
> print("BOOKS PER BOX:", 12)
> print("BOXES:", 4)
> print("TOTAL BOOKS:", 12 * 4)
> ```
>
> **Representative check**
>
> ```
> BOOK PACKING
> BOOKS PER BOX: 12
> BOXES: 4
> TOTAL BOOKS: 48
> ```
>
> The expression calculates the value; `print()` makes it visible. The repeated box count prepares the next lesson.

### Change and recheck

Change 4 boxes to 5. Which two places must change? Lesson 1.2 removes that duplication.

## Summary

After completing this lesson and its integrated practice, you can:

- Read a short program as a sequence of instructions rather than only its final answer.
- Distinguished numbers, quoted text, expressions, and intentional output.
- Used prediction, execution, and explanation to check a program.

## Next

Lesson 1.2 gives repeated values meaningful names, so one change can update every dependent calculation consistently.

**Estimated learning time:** about 2 hours
