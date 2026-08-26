#!/usr/bin/env python3
"""Reference solution for Project 2.4: CSV library record manager."""
import csv
from pathlib import Path

HERE = Path(__file__).resolve().parent
DEFAULT_INPUT_PATH = HERE / "data" / "books.csv"
DEFAULT_OUTPUT_PATH = HERE / "output" / "books_updated.csv"
REQUIRED_FIELDS = {"id", "title", "read"}


def parse_read(value):
    normalised = str(value).strip().lower()
    if normalised == "true":
        return True
    if normalised == "false":
        return False
    raise ValueError(f"invalid read value: {value!r}")


def load_books(path):
    books = []
    seen_ids = set()
    with Path(path).open("r", encoding="utf-8", newline="") as file:
        reader = csv.DictReader(file)
        missing = REQUIRED_FIELDS - set(reader.fieldnames or [])
        if missing:
            raise ValueError("missing columns: " + ", ".join(sorted(missing)))
        for row_number, row in enumerate(reader, start=2):
            book_id = (row.get("id") or "").strip()
            title = (row.get("title") or "").strip()
            if not book_id or not title:
                raise ValueError(f"blank required value on row {row_number}")
            if book_id in seen_ids:
                raise ValueError(f"duplicate id on row {row_number}: {book_id}")
            books.append({"id": book_id, "title": title, "read": parse_read(row.get("read", ""))})
            seen_ids.add(book_id)
    return books


def find_book(books, book_id):
    target = str(book_id).strip()
    for book in books:
        if book["id"] == target:
            return book
    return None


def add_book(books, book_id, title):
    clean_id = str(book_id).strip()
    clean_title = str(title).strip()
    if not clean_id or not clean_title:
        raise ValueError("book id and title are required")
    if find_book(books, clean_id) is not None:
        raise ValueError(f"duplicate id: {clean_id}")
    book = {"id": clean_id, "title": clean_title, "read": False}
    books.append(book)
    return book


def rename_book(books, book_id, new_title):
    clean_title = str(new_title).strip()
    if not clean_title:
        raise ValueError("title is required")
    book = find_book(books, book_id)
    if book is None:
        raise KeyError(str(book_id).strip())
    book["title"] = clean_title
    return book


def mark_as_read(books, book_id):
    book = find_book(books, book_id)
    if book is None:
        raise KeyError(str(book_id).strip())
    book["read"] = True
    return book


def remove_book(books, book_id):
    target = str(book_id).strip()
    for index, book in enumerate(books):
        if book["id"] == target:
            return books.pop(index)
    raise KeyError(target)


def summarise_books(books):
    read_count = 0
    for book in books:
        if book["read"]:
            read_count += 1
    return {"total": len(books), "read": read_count, "unread": len(books) - read_count}


def save_books(books, path):
    destination = Path(path)
    destination.parent.mkdir(parents=True, exist_ok=True)
    with destination.open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=["id", "title", "read"])
        writer.writeheader()
        for book in books:
            writer.writerow({"id": book["id"], "title": book["title"], "read": "true" if book["read"] else "false"})


def run_project(input_path=DEFAULT_INPUT_PATH, output_path=DEFAULT_OUTPUT_PATH):
    books = load_books(input_path)
    add_book(books, "B005", "Algorithms Made Clear")
    mark_as_read(books, "B003")
    rename_book(books, "B001", "Python Foundations")
    remove_book(books, "B004")
    summary = summarise_books(books)
    save_books(books, output_path)
    return summary


def main():
    summary = run_project()
    print("LIBRARY UPDATE REPORT")
    print(f"TOTAL BOOKS: {summary['total']}")
    print(f"READ BOOKS: {summary['read']}")
    print(f"UNREAD BOOKS: {summary['unread']}")
    print(f"OUTPUT FILE: {DEFAULT_OUTPUT_PATH.name}")


if __name__ == "__main__":
    main()
