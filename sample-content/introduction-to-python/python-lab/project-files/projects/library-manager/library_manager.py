#!/usr/bin/env python3
"""Project 2.4 starter: complete the TODOs without changing the public contract."""
import csv
from pathlib import Path

HERE = Path(__file__).resolve().parent
DEFAULT_INPUT_PATH = HERE / "data" / "books.csv"
DEFAULT_OUTPUT_PATH = HERE / "output" / "books_updated.csv"
REQUIRED_FIELDS = {"id", "title", "read"}


def parse_read(value):
    """Return bool for CSV text true/false; raise ValueError otherwise."""
    raise NotImplementedError("TODO: implement parse_read")


def load_books(path):
    """Return validated books as dictionaries with id, title, and Boolean read."""
    raise NotImplementedError("TODO: implement load_books")


def find_book(books, book_id):
    """Return the stored matching dictionary, or None when no ID matches."""
    raise NotImplementedError("TODO: implement find_book")


def add_book(books, book_id, title):
    """Append one unread book and return it; reject blank or duplicate data."""
    raise NotImplementedError("TODO: implement add_book")


def rename_book(books, book_id, new_title):
    """Rename and return a stored book; raise KeyError if the ID is absent."""
    raise NotImplementedError("TODO: implement rename_book")


def mark_as_read(books, book_id):
    """Mark and return a stored book; raise KeyError if the ID is absent."""
    raise NotImplementedError("TODO: implement mark_as_read")


def remove_book(books, book_id):
    """Remove and return a stored book; raise KeyError if the ID is absent."""
    raise NotImplementedError("TODO: implement remove_book")


def summarise_books(books):
    """Return total, read, and unread counts in a dictionary."""
    raise NotImplementedError("TODO: implement summarise_books")


def save_books(books, path):
    """Write UTF-8 CSV in current list order; create its parent; return None."""
    raise NotImplementedError("TODO: implement save_books")


def run_project(input_path=DEFAULT_INPUT_PATH, output_path=DEFAULT_OUTPUT_PATH):
    """Load, apply fixed updates, summarise, save, and return the summary."""
    raise NotImplementedError("TODO: implement run_project")


def main():
    summary = run_project()
    print("LIBRARY UPDATE REPORT")
    print(f"TOTAL BOOKS: {summary['total']}")
    print(f"READ BOOKS: {summary['read']}")
    print(f"UNREAD BOOKS: {summary['unread']}")
    print(f"OUTPUT FILE: {DEFAULT_OUTPUT_PATH.name}")
    # Delete the next line after every TODO is complete.
    print("PROGRAM INCOMPLETE")


if __name__ == "__main__":
    main()
