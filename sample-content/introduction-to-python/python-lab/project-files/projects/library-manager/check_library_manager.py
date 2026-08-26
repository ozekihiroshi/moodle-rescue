#!/usr/bin/env python3
"""Check the Project 2.4 library manager without changing its source data."""
from __future__ import annotations

import csv
import importlib.util
import os
import subprocess
import sys
import tempfile
from pathlib import Path

HERE = Path(__file__).resolve().parent
TARGET = Path(os.environ.get("LIBRARY_MANAGER_TARGET", HERE / "library_manager.py")).resolve()
SAMPLE = Path(os.environ.get("LIBRARY_MANAGER_SAMPLE", HERE / "data" / "books.csv")).resolve()
LANGUAGE = os.environ.get("LIBRARY_MANAGER_CHECK_LANGUAGE", "en")
REQUIRED_FUNCTIONS = [
    "parse_read",
    "load_books",
    "find_book",
    "add_book",
    "rename_book",
    "mark_as_read",
    "remove_book",
    "summarise_books",
    "save_books",
    "run_project",
]


def require(condition, message):
    if not condition:
        raise AssertionError(message)


def expect_exception(exception_type, function, *args):
    try:
        function(*args)
    except exception_type:
        return
    except Exception as error:
        raise AssertionError(
            f"expected {exception_type.__name__}, got {type(error).__name__}: {error}"
        ) from error
    raise AssertionError(f"expected {exception_type.__name__}, but no exception was raised")


def write_csv(path, rows, fieldnames=("id", "title", "read")):
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def load_module():
    spec = importlib.util.spec_from_file_location("learner_library_manager", TARGET)
    require(spec is not None and spec.loader is not None, "could not load library_manager.py")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    missing = [name for name in REQUIRED_FUNCTIONS if not callable(getattr(module, name, None))]
    require(not missing, "missing required function(s): " + ", ".join(missing))
    return module


def fresh_books():
    return [
        {"id": "B001", "title": "First", "read": False},
        {"id": "B002", "title": "Second", "read": True},
    ]


def test_sample_load(module, work):
    books = module.load_books(SAMPLE)
    require(len(books) == 4, "sample must contain four books")
    require(books[3]["title"] == "Writing, Presenting, and Learning", "quoted comma was not preserved")
    require([book["read"] for book in books] == [False, True, False, True], "read must be bool")
    require(module.parse_read(" TRUE ") is True, "parse_read must ignore space and case")
    require(module.parse_read(" false ") is False, "parse_read must convert false")


def test_bad_csv(module, work):
    missing = work / "missing.csv"
    write_csv(missing, [{"id": "B001", "title": "Book"}], ("id", "title"))
    expect_exception(ValueError, module.load_books, missing)
    invalid = work / "invalid.csv"
    write_csv(invalid, [{"id": "B001", "title": "Book", "read": "perhaps"}])
    expect_exception(ValueError, module.load_books, invalid)
    blank = work / "blank.csv"
    write_csv(blank, [{"id": " ", "title": "Book", "read": "false"}])
    expect_exception(ValueError, module.load_books, blank)
    empty = work / "empty.csv"
    empty.write_text("", encoding="utf-8")
    expect_exception(ValueError, module.load_books, empty)
    header_only = work / "header-only.csv"
    header_only.write_text("id,title,read\n", encoding="utf-8")
    require(module.load_books(header_only) == [], "header-only CSV must return an empty list")
    extra = work / "extra.csv"
    write_csv(extra, [{"id": "B010", "title": "Extra", "read": "false", "note": "ignored"}], ("id", "title", "read", "note"))
    require(module.load_books(extra) == [{"id": "B010", "title": "Extra", "read": False}], "extra columns must be ignored")


def test_duplicate_csv(module, work):
    duplicate = work / "duplicate.csv"
    write_csv(duplicate, [
        {"id": "B001", "title": "First", "read": "false"},
        {"id": "B001", "title": "Again", "read": "true"},
    ])
    expect_exception(ValueError, module.load_books, duplicate)


def test_add_and_find(module, work):
    books = fresh_books()
    added = module.add_book(books, " B003 ", " Third ")
    require(added == {"id": "B003", "title": "Third", "read": False}, "add must strip and create unread book")
    require(module.find_book(books, "B003") is added, "find must return the stored dictionary")
    require(module.find_book(books, "B999") is None, "absent find must return None")


def test_invalid_add(module, work):
    books = fresh_books()
    expect_exception(ValueError, module.add_book, books, "B001", "Duplicate")
    expect_exception(ValueError, module.add_book, books, "", "No ID")
    expect_exception(ValueError, module.add_book, books, "B003", "  ")
    require(len(books) == 2, "failed additions must not change the list")


def test_rename_and_mark(module, work):
    books = fresh_books()
    renamed = module.rename_book(books, "B001", " Updated ")
    require(renamed["title"] == "Updated", "rename must strip and update title")
    marked = module.mark_as_read(books, "B001")
    require(marked is renamed and marked["read"] is True, "mark must update and return the stored book")
    expect_exception(ValueError, module.rename_book, books, "B001", " ")


def test_missing_ids(module, work):
    books = fresh_books()
    expect_exception(KeyError, module.rename_book, books, "B999", "Missing")
    expect_exception(KeyError, module.mark_as_read, books, "B999")
    expect_exception(KeyError, module.remove_book, books, "B999")


def test_remove(module, work):
    books = fresh_books()
    removed = module.remove_book(books, "B001")
    require(removed["id"] == "B001", "remove must return the removed record")
    require([book["id"] for book in books] == ["B002"], "remove must change the supplied list")


def test_summary_and_round_trip(module, work):
    books = fresh_books()
    require(module.summarise_books(books) == {"total": 2, "read": 1, "unread": 1}, "summary counts are wrong")
    require(module.summarise_books([]) == {"total": 0, "read": 0, "unread": 0}, "empty summary is wrong")
    output = work / "nested" / "books.csv"
    saved = module.save_books(books, output)
    require(saved is None, "save_books must return None")
    require(output.is_file(), "save must create the output file and parent directory")
    require(module.load_books(output) == books, "saved CSV must load back to equivalent records")
    text = output.read_text(encoding="utf-8")
    require("True" not in text and "False" not in text, "CSV Booleans must be lower-case true/false")


def test_complete_project(module, work):
    require(
        "PROGRAM INCOMPLETE" not in TARGET.read_text(encoding="utf-8"),
        "finish every TODO and remove the PROGRAM INCOMPLETE line",
    )
    before = SAMPLE.read_bytes()
    output = work / "result" / "books_updated.csv"
    summary = module.run_project(SAMPLE, output)
    require(summary == {"total": 4, "read": 2, "unread": 2}, "end-to-end summary is wrong")
    result = module.load_books(output)
    require([book["id"] for book in result] == ["B001", "B002", "B003", "B005"], "updated IDs or order are wrong")
    require(result[0]["title"] == "Python Foundations", "B001 was not renamed")
    require(result[2]["read"] is True, "B003 was not marked as read")
    require(SAMPLE.read_bytes() == before, "source books.csv was changed")
    process = subprocess.run([sys.executable, str(TARGET)], text=True, capture_output=True, timeout=10)
    require(process.returncode == 0, f"script stopped with exit code {process.returncode}: {process.stderr.strip()}")
    expected = [
        "LIBRARY UPDATE REPORT",
        "TOTAL BOOKS: 4",
        "READ BOOKS: 2",
        "UNREAD BOOKS: 2",
        "OUTPUT FILE: books_updated.csv",
    ]
    for line in expected:
        require(line in process.stdout.splitlines(), f"missing report line: {line}")


JA_TEST_NAMES = {
    "parse_read / load_books: sample CSV and Boolean conversion": "parse_read / load_books：サンプルCSVとブール値変換",
    "load_books: invalid columns, values, and blanks": "load_books：列不足・不正値・空欄",
    "load_books: duplicate IDs": "load_books：CSV内の重複ID",
    "add_book / find_book": "add_book / find_book：登録と検索",
    "add_book: invalid additions": "add_book：不正な登録",
    "rename_book / mark_as_read": "rename_book / mark_as_read：書名変更と読了変更",
    "rename / mark / remove: missing-ID errors": "rename / mark / remove：存在しないIDのエラー",
    "remove_book": "remove_book：削除",
    "summarise_books / save_books: summary and CSV round trip": "summarise_books / save_books：集計とCSV再読み込み",
    "run_project / main: complete fixed updates and report": "run_project / main：固定更新全体と報告",
}


TESTS = [
    ("parse_read / load_books: sample CSV and Boolean conversion", test_sample_load),
    ("load_books: invalid columns, values, and blanks", test_bad_csv),
    ("load_books: duplicate IDs", test_duplicate_csv),
    ("add_book / find_book", test_add_and_find),
    ("add_book: invalid additions", test_invalid_add),
    ("rename_book / mark_as_read", test_rename_and_mark),
    ("rename / mark / remove: missing-ID errors", test_missing_ids),
    ("remove_book", test_remove),
    ("summarise_books / save_books: summary and CSV round trip", test_summary_and_round_trip),
    ("run_project / main: complete fixed updates and report", test_complete_project),
]


def main():
    japanese = LANGUAGE == "ja"
    print("CSV図書記録管理の自動確認" if japanese else "CSV library record manager automatic check")
    print("確認対象:" if japanese else "Target:", TARGET)
    if not TARGET.is_file():
        print("[NG] library_manager.pyが見つかりません" if japanese else "[NG] library_manager.py was not found")
        return 1
    try:
        module = load_module()
    except Exception as error:
        print(f"[NG] could not load the program: {type(error).__name__}: {error}")
        return 1
    failures = 0
    with tempfile.TemporaryDirectory(prefix="library-manager-check-") as temporary:
        work = Path(temporary)
        for name, test in TESTS:
            display_name = JA_TEST_NAMES.get(name, name) if japanese else name
            try:
                test(module, work / name.replace(" ", "-"))
            except Exception as error:
                failures += 1
                print(f"[NG] {display_name}")
                print(f"     {type(error).__name__}: {error}")
            else:
                print(f"[OK] {display_name}")
    if failures:
        print(f"\n{failures}項目を修正する必要があります。" if japanese else f"\n{failures} check(s) need attention.")
        print("library_manager.pyだけを修正・保存し、もう一度確認してください。" if japanese else "Change only library_manager.py, save it, and run this checker again.")
        return 1
    print("\nALL TESTS PASSED")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
