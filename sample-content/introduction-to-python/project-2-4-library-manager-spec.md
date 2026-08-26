Canonical English specification. Filenames, signatures, data rules, and checker cases are normative.

# Project 2.4 — CSV library record manager

## 1. Situation

A small learning centre stores its book catalogue in `data/books.csv`. A staff
member has received four requests to update the catalogue. Build a program that
loads the source CSV, applies those requests, counts the updated records, displays
a report, and saves the result as a separate CSV.

## 2. What you will do

You do not create the program from an empty file. In Python Lab, open the
supplied starter at `projects/library-manager/library_manager.py` and complete
its ten unfinished functions. This Python program will read and process the CSV.
Edit only `library_manager.py`.

1. Make the Python program load the supplied `books.csv` (the sample contains four books).
2. Apply four specified updates in order.
3. Count the books after the updates.
4. Save the updated records to `books_updated.csv`.
5. Display the summary on screen.

Functions such as `load_books()` process any valid number of records rather than
fixing the input at four books. However, this project's `run_project()` is specific
to the supplied `books.csv` and applies the four update requests shown below.

## 3. Input CSV and source protection

The supplied `projects/library-manager/data/books.csv` contains:

```csv
id,title,read
B001,Python Basics,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,false
B004,"Writing, Presenting, and Learning",true
```

This listing is provided so you can understand the input before opening the
file. Do not copy it into the Python source. Read the supplied file with
`csv.DictReader`.

The first row is the header. `DictReader` initially produces string values with
the keys `id`, `title`, and `read`; `parse_read()` converts the `read` text to a
bool. B004 shows that a field containing commas is quoted. Do not split lines
yourself or remove the quotes yourself—the `csv` module handles them.

`data/books.csv` is the unchanged source record. Do not edit or overwrite it.
Always save the result to `output/books_updated.csv`.

## 4. How to apply the four update requests

In a larger system, update requests might come from another file or a user
interface. This project focuses on writing and combining update functions, so
the four requests are not another file and are not keyboard input. Implement
these fixed function calls directly inside `run_project()`, in this order:

```python
add_book(books, "B005", "Algorithms Made Clear")
mark_as_read(books, "B003")
rename_book(books, "B001", "Python Foundations")
remove_book(books, "B004")
```

## 5. Before and after

| ID | Before | Operation | After |
|---|---|---|---|
| B001 | Python Basics / unread | rename | Python Foundations / unread |
| B002 | Data Skills for Beginners / read | none | unchanged |
| B003 | Networks in Practice / unread | mark read | Networks in Practice / read |
| B004 | Writing, Presenting, and Learning / read | remove | not written |
| B005 | absent | add unread | Algorithms Made Clear / unread |

Because B004 is later removed, the checker tests `load_books()` independently
to confirm that its comma-containing title was read correctly.

## 6. Public contract for all ten functions

The starter also contains a completed `main()` in addition to the ten functions
below. `main()` calls `run_project()` with the default paths and displays the
returned summary. Do not rename or change `main()`. IDs and titles are stripped
of surrounding whitespace before validation, search, or storage.

| Function | Inputs and responsibility | Return, mutation, and exceptions |
|---|---|---|
| `parse_read(value)` | convert CSV Boolean text | ignore surrounding space and case; return bool; otherwise `ValueError` |
| `load_books(path)` | read UTF-8 CSV | dictionaries in input order; invalid columns, blanks, duplicates, or Booleans raise `ValueError` |
| `find_book(books, book_id)` | linear ID search | stored dictionary or `None` |
| `add_book(books, book_id, title)` | append an unread record | stored new dictionary; blank ID/title or duplicate ID raises `ValueError` |
| `rename_book(books, book_id, new_title)` | mutate stored title | stored changed dictionary; blank title `ValueError`; absent ID `KeyError` |
| `mark_as_read(books, book_id)` | mutate stored read state | stored changed dictionary; absent ID `KeyError` |
| `remove_book(books, book_id)` | remove one while preserving remaining order | removed dictionary; absent ID `KeyError` |
| `summarise_books(books)` | count total/read/unread | `{"total": n, "read": n, "unread": n}` |
| `save_books(books, path)` | create parent and write UTF-8 CSV | `None`; current list order, `id,title,read`, lower-case Booleans |
| `run_project(input_path, output_path)` | load the input, apply four fixed updates, summarise, save, return | summary dictionary; completed `main()` prints it |

Ignore extra CSV columns. A completely empty file raises `ValueError` for
missing columns; a correct header with no data rows returns an empty list. Do
not sort during saving; preserve the current list order.

## 7. Path basis

The starter already constructs the input and output paths from the script's own
folder. The program therefore finds its files regardless of the terminal's
current directory. Do not change the constant names or default paths.

## 8. Implementation stages

1. Complete `parse_read()` and `load_books()`; confirm four records and bool values.
2. Complete `find_book()`; check a present and an absent ID.
3. Complete add, rename, mark-read, and remove.
4. Complete `summarise_books()`.
5. Complete `save_books()` and reload its output.
6. In `run_project()`, connect load, updates, summary, save, and return in that order.
7. Finish every TODO and delete the final `print("PROGRAM INCOMPLETE")` line.

## 9. Manual check

Save with **Ctrl+S**, then run:

```text
python projects/library-manager/library_manager.py
```

The report must be:

```text
LIBRARY UPDATE REPORT
TOTAL BOOKS: 4
READ BOOKS: 2
UNREAD BOOKS: 2
OUTPUT FILE: books_updated.csv
```

The generated CSV must be:

```csv
id,title,read
B001,Python Foundations,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,true
B005,Algorithms Made Clear,false
```

Create this CSV from the record list with `csv.DictWriter`; do not write the
shown CSV as one fixed string.

## 10. Automatic check and submission

Run `python projects/library-manager/check_library_manager.py`. Change only
`library_manager.py` until all ten areas show `[OK]` and the last line is
`ALL TESTS PASSED`. Confirm again that the source CSV is unchanged.

Right-click `library_manager.py` in the Python Lab file browser, download it,
and upload that one file to the Moodle assignment.
