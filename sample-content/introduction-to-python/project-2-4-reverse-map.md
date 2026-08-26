# Project 2.4 reverse curriculum map

Status: design audit; Moodle content has not yet been changed.

Target chapter title:

- Japanese: `第2章 — データ構造・関数・ファイル処理`
- English: `Chapter 2 — Data Structures, Functions, and File Processing`

Target sequence:

1. 2.1 Lists, dictionaries, and records
2. 2.2 Functions, errors, and tests
3. 2.3 File and CSV input/output
4. 2.4 Applied project: CSV library record manager

## Reverse mapping

| Project requirement | Prior teaching location | Required treatment before 2.4 |
|---|---|---|
| Text, Boolean values, conversion, labelled output | Chapter 1 scalar values and strings | Retain; practise `strip()`, `lower()`, Boolean decisions, and f-strings |
| Ordered update sheet and repeated record processing | Chapter 1 conditions and loops | Retain; transfer from numeric accumulation to record iteration |
| Several book records | 2.1 | Master a list of dictionaries and explain row/field meaning |
| Find by ID | 2.1 | Teach linear search, early return, and absent result `None` |
| Add, rename, mark, and remove | 2.1 | Teach list/dictionary mutation, `append()`, `pop()`, and identity of stored records |
| Unique IDs | 2.1 | Teach set membership and why uniqueness is a data rule |
| Removal by position | 2.1 | Introduce `enumerate()` or teach an equivalent already-covered pattern |
| One responsibility per operation | 2.2 | Separate loading, validation, lookup, mutation, summary, saving, and orchestration |
| Inputs and returned records | 2.2 | Master parameters, `return`, `None`, and `print()` versus return values |
| Functions intentionally mutate the supplied collection | 2.2 | State mutation contracts and contrast them with returning a new value |
| Blank/duplicate/invalid CSV values | 2.2 | Raise specific `ValueError` with a useful cause |
| Missing update target | 2.2 | Distinguish an absent search result from a `KeyError` operation contract |
| Supplied automatic checker | 2.2 | Teach normal, boundary, empty, and invalid cases; learner need not author this checker |
| Portable project-relative paths | 2.3 | Teach `Path(__file__).resolve().parent`; do not depend on Notebook working directory |
| UTF-8 text and CSV conventions | 2.3 | Teach `with open`, modes, `encoding="utf-8"`, and `newline=""` |
| Header-aware CSV records | 2.3 | Teach `csv.DictReader` and that CSV values initially arrive as strings |
| Re-readable output CSV | 2.3 | Teach `csv.DictWriter`, stable field order, and lower-case Boolean text |
| Preserve supplied source data | 2.3 | Separate `data/` input from `output/`; never overwrite the course fixture |
| Create an output folder safely | 2.3 | Introduce `Path.parent.mkdir(parents=True, exist_ok=True)` with one bounded example |
| Script runs only when executed directly | 2.3 | Introduce the supplied `if __name__ == "__main__":` entry-point pattern |

## Required repairs to existing lessons

### 2.1

The current lesson already covers list/dictionary records, tuple/set choice,
mutation, aliasing, slicing, dictionary iteration, and set operations. Before
2.4 it must also make these project-facing operations explicit:

- linear search returning a found dictionary or `None`;
- unique-ID checking;
- updating the stored dictionary returned by search;
- removing one record without corrupting order;
- `enumerate()` if the reference removal pattern is retained;
- a short CRUD transfer exercise unrelated to learning-centre metrics.

### 2.2

The current lesson covers functions, parameters, returns, scope, defaults,
contracts, validation, exceptions, and tests. It needs clearer project-facing
coverage of:

- `return` versus printing;
- functions that mutate a supplied list versus pure calculations;
- returning a stored record after mutation;
- `None` as a normal search result;
- when to raise `ValueError` and when to raise `KeyError`;
- testing state before and after a function call;
- a provided checker as an independent consumer of the function contract.

### 2.3

This is a new lesson. It must be completed before publishing 2.4 and must not
introduce pandas. Required learner evidence is:

- locate a supplied CSV from both Notebook and script contexts;
- read a quoted-comma row correctly with `csv.DictReader`;
- convert `true`/`false` text deliberately;
- reject a missing header and invalid value without silently substituting data;
- write a separate CSV with `csv.DictWriter`;
- reload the output and compare records;
- diagnose `FileNotFoundError` by printing the resolved path.

## Explicitly deferred

The project must not require pandas, DataFrames, classes, databases, JSON,
custom exception classes, comprehensions, generators, optimisation solvers,
or external packages. A later pandas chapter can repeat the same
read–inspect–filter–summarise workflow on a larger table and compare the amount
and shape of code.

## Publication gate

Do not publish 2.4 to Moodle until all of the following are true:

- the reference solution passes all ten checker areas;
- both starters fail clearly while `PROGRAM INCOMPLETE` remains;
- the checker runs from a directory other than the project directory;
- the source CSV hash is unchanged after every test;
- output reloads to equivalent Boolean records;
- every learner-visible construct maps to Chapter 1 or 2.1–2.3;
- English and Japanese filenames, function contracts, and expected results are identical;
- a fresh Python Lab user receives the files without overwriting existing work.
