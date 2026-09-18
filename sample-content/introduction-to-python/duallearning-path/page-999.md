## Introduction

**Keep one source for one meaning**

At the end of Lesson 1, the morning count 18 was written directly in several expressions. Changing it to 20 required several edits, and a missed edit could make the outputs contradict each other. Give the value one name instead.

## Learning outcomes

At the end of this lesson, you can:

- Explain assignment as evaluating the right side before updating the name on the left.
- Trace the value associated with each name after reassignment.
- Distinguish assignment with = from comparison with ==.
- Choose valid, meaningful names and diagnose spelling or execution-order errors.

**Learning route:** Required: 1.2.1–1.2.3 \| Integration: 1.2.4

## 1.2.1 Create and update program state with assignment

```
capacity = 40
morning = 18
afternoon = 12

print("Total:", morning + afternoon)
print("Unused seats:", capacity - (morning + afternoon))
```

When `morning = 18` runs, Python first evaluates 18 on the right and makes the name `morning` refer to that value. This operation is assignment. Later expressions read `morning` instead of repeating 18. Changing the morning count now requires one edit, and both outputs use the same information.

### Assignment is not mathematical equality

One `=` in Python does not assert that both sides are equal. It evaluates the right-hand side, then assigns the result to the name on the left. Two signs, `==`, ask whether values are equal.

```
registered = 40
print(registered == 40)
print(registered == 35)
```

The first line creates state; the other lines compare without changing it. They display `True` and `False`. Booleans and decisions are developed later, but distinguishing `=` from `==` matters now.

### Read the right side before updating the left

A name can later be assigned another value. This is reassignment. Trace the value of `total` after every line.

```
total = 12
amount = 5
total = total + amount
print(total)
```

On the third line, `total` on the right is still 12. Python adds 12 and 5 to make 17, then updates the name on the left. Read the line as “add the amount to the current total, then make that result the new total”, not as an algebraic equation.

The same update can be written `total += amount`. The shorter spelling still reads the current values before updating the left name. This course uses the long form until the state change is clear.

### A calculated value does not update itself

Assignment connects a name to the result produced when that line runs. It is not a spreadsheet formula that recalculates automatically whenever an input changes.

```
planned = 15
cancelled = 2
delivered = planned - cancelled

cancelled = 4
print("Cancelled:", cancelled)
print("Delivered:", delivered)
```

`delivered` became 13 on the third line. Changing `cancelled` to 4 does not change the already calculated value, so `delivered` remains 13. Run `delivered = planned - cancelled` again to update it to 11. Understanding which calculations must run after a state change is a foundation for reliable programs.

**Quick check:** When total = total + amount runs, which value of total is read first?

## 1.2.2 Choose meaningful names and diagnose NameError

### A name follows rules and communicates meaning

Names explain code to its readers. `completed_learners` communicates more than `x`, but an identifier cannot be arbitrary prose.

| Name                 | Valid? | Reason                                        |
|----------------------|--------|-----------------------------------------------|
| `completed_learners` | Yes    | Letters and an underscore communicate meaning |
| `group2`             | Yes    | A digit is allowed after the first character  |
| `2nd_group`          | No     | An identifier cannot start with a digit       |
| `completed learners` | No     | An identifier cannot contain a space          |
| `class`              | No     | It is a Python keyword                        |

Names are case-sensitive, so `Score` and `score` are different. Ordinary variables normally use lower-case words and underscores. All capitals, as in `MAX_SEATS`, signal a convention that the value should not change; Python does not enforce that convention.

### Use NameError to inspect execution order

Reading a name that has not yet been assigned, is misspelled, or differs in case normally raises `NameError`. Read the exception name and message on the final traceback line, then compare the names character by character.

```
completed_learners = 29
print(completed_learner)
```

The second name is missing its final `s`. In a Notebook, the same error can occur when the correct assignment cell is visible but has not run. Check both spelling and whether the required assignment executed first.

## 1.2.3 Make Notebook state reproducible

### Make Notebook state reproducible

The kernel remembers names created by earlier cell execution. A Notebook may therefore work on your screen after cells were run out of order but fail for a reader who starts at the top. Before saving, restart the kernel and run every cell from the top. This proves that each required assignment appears before use and that the Notebook has no hidden state dependency.

**Quick check:** Why can a Notebook work before restart but fail when another learner runs it from the top?

## Integrated practice: a lighter rehearsal for the chapter project

**Connection to the chapter project:**

Use event tickets to practise keeping one source for each value and updating derived state.

### What to build

An event planned 80 tickets and cancelled 7. Store the values, calculate active tickets, and display all three quantities.

### Completion criteria

- Use meaningful names.

- Calculate active tickets from the two source values.

- After changing a source, rerun the derived assignment.

### Step-by-step guide

1.  Assign the two source values first.

2.  The right side is evaluated before the left name changes.

3.  Print labels with all three variables.

**Build and run your own version before opening the model answer.**

> [!ANSWER]
> **Model answer and explanation**
>
> ```
> planned = 80
> cancelled = 7
> active = planned - cancelled
> print("PLANNED:", planned)
> print("CANCELLED:", cancelled)
> print("ACTIVE:", active)
> ```
>
> **Representative check**
>
> ```
> PLANNED: 80
> CANCELLED: 7
> ACTIVE: 73
> ```
>
> A derived variable stores the result produced when its assignment runs; it does not update like a spreadsheet formula.

### Change and recheck

Change cancellations to 12. Inspect the old active value, then rerun the calculation and confirm 68.

## Summary

After completing this lesson and its integrated practice, you can:

- Used names as a single source for values that have one meaning.
- Traced reassignment and calculations that do not update automatically.
- Restarted and ran a Notebook from the top to expose hidden state dependencies.

## Next

Lesson 1.3 examines the kinds of scalar values stored by those names and the arithmetic and conversions permitted for each kind.

**Estimated learning time:** about 2 hours
