#!/usr/bin/env python3
"""Add the Project 2.4 prerequisite examples to Lessons 2.1 and 2.2."""

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id, text):
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code(cell_id, text):
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


def update_notebook(path, language, lesson):
    document = json.loads(path.read_text(encoding="utf-8"))
    document["cells"] = [cell for cell in document["cells"] if not cell.get("id", "").startswith(f"l{lesson.replace('.', '')}-v23-")]
    cells = document["cells"]
    transfer_id = f"l{lesson.replace('.', '')}-{'ja' if language == 'ja' else 'en'}-transfer"
    transfer_index = next(i for i, cell in enumerate(cells) if cell.get("id") == transfer_id)

    if lesson == "2.1" and language == "ja":
        additions = [
            md("l21-v23-ja-search", "## IDで一件を探し、見つからない場合を区別する\n\nレコードの位置ではなく、一意なIDを照合して探します。見つかった辞書を変数へ入れて`break`し、最後まで見つからなければ`None`のままにします。`None`は異常そのものではなく、「該当なし」という通常の検索結果です。"),
            code("l21-v23-ja-search-code", '''books = [
    {"id": "B001", "title": "Python Basics", "read": False},
    {"id": "B002", "title": "Working with Data", "read": True},
    {"id": "B003", "title": "Useful Functions", "read": False},
]

target_id = "B002"
found = None
for book in books:
    if book["id"] == target_id:
        found = book
        break

print(found)
'''),
            md("l21-v23-ja-crud", "## 同じレコード集合を追加・参照・更新・削除する\n\n追加前にはID集合で重複を確認します。検索で返された辞書はリスト内の辞書そのものなので、その項目を変更すると保存されたレコードも変わります。削除では`enumerate()`で位置とレコードを同時に受け取り、その位置を`pop()`します。先頭から探すことで、残るレコードの順序を壊しません。これらをCreate、Read、Update、Deleteの頭文字からCRUDと呼びます。"),
            code("l21-v23-ja-crud-code", '''existing_ids = set()
for book in books:
    existing_ids.add(book["id"])

new_book = {"id": "B004", "title": "Files in Practice", "read": False}
if new_book["id"] not in existing_ids:
    books.append(new_book)

found["read"] = False

remove_index = None
for index, book in enumerate(books):
    if book["id"] == "B003":
        remove_index = index
        break
if remove_index is not None:
    removed = books.pop(remove_index)
    print("削除:", removed)

print(books)
'''),
        ]
        cells[transfer_index:transfer_index] = additions
        cells[transfer_index]["source"] = """## 応用練習：備品台帳を更新する

`asset_id`、`name`、`available`を持つ三件の備品を辞書のリストで表してください。IDで一件を探し、見つからない場合は`None`とします。重複しない`A004`を追加し、`A002`を利用可能へ変更し、`A003`を削除します。最後にID順と件数を表示し、元の三件だけを保持した浅いコピーとの違いも確認してください。
""".splitlines(keepends=True)
    elif lesson == "2.1":
        additions = [
            md("l21-v23-en-search", "## Find one record by ID and distinguish absence\n\nSearch by a unique ID rather than a position. Store the matching dictionary and `break`; if no match is found, leave the result as `None`. Here `None` is not automatically an error. It is the normal result “no such record”."),
            code("l21-v23-en-search-code", '''books = [
    {"id": "B001", "title": "Python Basics", "read": False},
    {"id": "B002", "title": "Working with Data", "read": True},
    {"id": "B003", "title": "Useful Functions", "read": False},
]

target_id = "B002"
found = None
for book in books:
    if book["id"] == target_id:
        found = book
        break

print(found)
'''),
            md("l21-v23-en-crud", "## Add, read, update, and remove records in one collection\n\nBuild an ID set before adding so duplicates can be rejected. The dictionary returned by the search is the stored dictionary, so changing one field changes that record in the list. To remove, use `enumerate()` to obtain both position and record, then `pop()` that position. A first-to-last search preserves the order of remaining records. These four operations are often called CRUD: Create, Read, Update, and Delete."),
            code("l21-v23-en-crud-code", '''existing_ids = set()
for book in books:
    existing_ids.add(book["id"])

new_book = {"id": "B004", "title": "Files in Practice", "read": False}
if new_book["id"] not in existing_ids:
    books.append(new_book)

found["read"] = False

remove_index = None
for index, book in enumerate(books):
    if book["id"] == "B003":
        remove_index = index
        break
if remove_index is not None:
    removed = books.pop(remove_index)
    print("Removed:", removed)

print(books)
'''),
        ]
        cells[transfer_index:transfer_index] = additions
        cells[transfer_index]["source"] = """## Transfer exercise: update an equipment register

Represent three assets as a list of dictionaries containing `asset_id`, `name`, and `available`. Find one by ID and use `None` when absent. Add non-duplicate `A004`, mark `A002` available, and remove `A003`. Display final ID order and count, and compare with a shallow copy containing only the original three records.
""".splitlines(keepends=True)
    elif language == "ja":
        additions = [
            md("l22-v23-ja-contract", "## 検索関数は`None`を返し、変更関数は保存中のレコードを返す\n\n`find_book()`は該当なしを`None`で返します。`mark_as_read()`は検索結果を確認し、該当なしなら`KeyError`を送出します。見つかった辞書を変更して返すため、呼び出し側は変更対象を確認できます。関数が渡されたリストを変更することは、契約に明記します。"),
            code("l22-v23-ja-contract-code", '''def find_book(books, book_id):
    for book in books:
        if book["id"] == book_id:
            return book
    return None

def mark_as_read(books, book_id):
    book = find_book(books, book_id)
    if book is None:
        raise KeyError(book_id)
    book["read"] = True
    return book

books = [{"id": "B001", "title": "Python Basics", "read": False}]
changed = mark_as_read(books, "B001")
print(changed)
print(books)
'''),
            md("l22-v23-ja-errors", "## 値の規則違反と、更新対象の欠落を分ける\n\n空のIDや重複IDは、新しく渡された値が追加規則に反するため`ValueError`です。一方、更新や削除を依頼されたIDが存在しない場合は、対象キーがないため`KeyError`とします。例外名を分けると、確認プログラムは失敗理由まで検査できます。"),
            code("l22-v23-ja-errors-code", '''def add_book(books, book_id, title):
    clean_id = book_id.strip()
    clean_title = title.strip()
    if not clean_id or not clean_title:
        raise ValueError("id and title are required")
    if find_book(books, clean_id) is not None:
        raise ValueError(f"duplicate id: {clean_id}")
    book = {"id": clean_id, "title": clean_title, "read": False}
    books.append(book)
    return book
'''),
            md("l22-v23-ja-state", "## 戻り値だけでなく、呼び出し前後の状態をテストする\n\nリストを変更する関数では、返された辞書だけを確認しても不十分です。件数が一つ増えたか、返された辞書が実際にリストへ格納されたか、既存レコードの順序が保たれたかを確認します。計算だけを行う関数なら、逆に入力を変更していないことを確認します。"),
            code("l22-v23-ja-state-code", '''before_count = len(books)
added = add_book(books, " B002 ", " Working with Data ")
assert len(books) == before_count + 1
assert added is books[-1]
assert added == {"id": "B002", "title": "Working with Data", "read": False}

try:
    add_book(books, "B002", "Duplicate")
except ValueError:
    pass
else:
    raise AssertionError("ValueErrorを期待しました")

print("STATE TESTS PASSED")
'''),
            md("l22-v23-ja-checker", "## 確認プログラムは関数契約を利用する別のプログラム\n\n学習者がこの段階で確認プログラムの内部を作れる必要はありません。確認プログラムは`library_manager.py`を読み込み、決められた関数を通常値・境界値・異常値で呼び出します。学習者はファイル名、関数名、引数、戻り値、例外を契約どおりに保ち、`NG`なら自分のプログラムだけを修正します。"),
        ]
        cells[transfer_index:transfer_index] = additions
        cells[transfer_index]["source"] = """## 応用練習：備品台帳の関数を作る

2.1の備品台帳を使い、`find_asset(assets, asset_id)`、`add_asset(...)`、`mark_available(...)`、`remove_asset(...)`へ分けてください。検索の該当なしは`None`、空欄・重複追加は`ValueError`、存在しない更新・削除は`KeyError`とします。正常値と異常値に加え、件数と順序の変更前後も`assert`で確認します。
""".splitlines(keepends=True)
    else:
        additions = [
            md("l22-v23-en-contract", "## A search returns `None`; a mutation returns the stored record\n\n`find_book()` returns `None` when no ID matches. `mark_as_read()` checks that result and raises `KeyError` for an absent update target. It changes and returns the found dictionary so the caller can inspect the actual record. State clearly in the function contract that the supplied list is mutated."),
            code("l22-v23-en-contract-code", '''def find_book(books, book_id):
    for book in books:
        if book["id"] == book_id:
            return book
    return None

def mark_as_read(books, book_id):
    book = find_book(books, book_id)
    if book is None:
        raise KeyError(book_id)
    book["read"] = True
    return book

books = [{"id": "B001", "title": "Python Basics", "read": False}]
changed = mark_as_read(books, "B001")
print(changed)
print(books)
'''),
            md("l22-v23-en-errors", "## Separate an invalid new value from an absent update target\n\nA blank or duplicate ID violates the rule for a newly supplied value, so raise `ValueError`. An update or removal requested for an ID that is not stored has a missing target, so raise `KeyError`. Specific exceptions let an automatic checker verify the cause, not only that something failed."),
            code("l22-v23-en-errors-code", '''def add_book(books, book_id, title):
    clean_id = book_id.strip()
    clean_title = title.strip()
    if not clean_id or not clean_title:
        raise ValueError("id and title are required")
    if find_book(books, clean_id) is not None:
        raise ValueError(f"duplicate id: {clean_id}")
    book = {"id": clean_id, "title": clean_title, "read": False}
    books.append(book)
    return book
'''),
            md("l22-v23-en-state", "## Test state before and after a call, not only its return value\n\nFor a mutating function, checking only the returned dictionary is insufficient. Check that count increased once, the returned dictionary is the stored object, and existing order was preserved. For a calculation-only function, test the opposite contract: its input remains unchanged."),
            code("l22-v23-en-state-code", '''before_count = len(books)
added = add_book(books, " B002 ", " Working with Data ")
assert len(books) == before_count + 1
assert added is books[-1]
assert added == {"id": "B002", "title": "Working with Data", "read": False}

try:
    add_book(books, "B002", "Duplicate")
except ValueError:
    pass
else:
    raise AssertionError("Expected ValueError")

print("STATE TESTS PASSED")
'''),
            md("l22-v23-en-checker", "## A supplied checker is another program that consumes the contract\n\nYou do not need to author or fully understand the checker yet. It imports `library_manager.py` and calls the named functions with normal, boundary, and invalid cases. Keep filename, function names, parameters, returns, mutations, and exceptions compatible. When a check reports `NG`, change only your program."),
        ]
        cells[transfer_index:transfer_index] = additions
        cells[transfer_index]["source"] = """## Transfer exercise: functions for an equipment register

Use the 2.1 equipment register and implement `find_asset(assets, asset_id)`, `add_asset(...)`, `mark_available(...)`, and `remove_asset(...)`. Absence from search returns `None`; blank or duplicate additions raise `ValueError`; absent update or removal targets raise `KeyError`. Test normal and invalid cases plus count and order before and after each mutation.
""".splitlines(keepends=True)

    document["metadata"].setdefault("pyai", {})["revision"] = 23
    path.write_text(json.dumps(document, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
    print(f"updated {path.relative_to(ROOT)}")


def main():
    targets = [
        (TEMPLATES / "05_lists_dictionaries_records.ipynb", "en", "2.1"),
        (TEMPLATES / "ja/05_lists_dictionaries_records.ipynb", "ja", "2.1"),
        (TEMPLATES / "06_functions_errors_testing.ipynb", "en", "2.2"),
        (TEMPLATES / "ja/06_functions_errors_testing.ipynb", "ja", "2.2"),
    ]
    for target in targets:
        update_notebook(*target)

    mapping = {
        "schema_version": 1,
        "purpose": "Project 2.4 prerequisite closure",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "requirements": [
            {"id": "P24-R01", "requirement": "linear search returns stored record or None", "lesson": "2.1", "notebook": True, "questions": ["L21P-05", "L21P-06"]},
            {"id": "P24-R02", "requirement": "unique ID check before add", "lesson": "2.1", "notebook": True, "questions": ["L21P-08"]},
            {"id": "P24-R03", "requirement": "mutate found record and preserve collection order", "lesson": "2.1", "notebook": True, "questions": ["L21P-07", "L21P-09"]},
            {"id": "P24-R04", "requirement": "CRUD transfer exercise", "lesson": "2.1", "notebook": True, "questions": ["L21P-10"]},
            {"id": "P24-R05", "requirement": "mutation contract returns stored record", "lesson": "2.2", "notebook": True, "questions": ["L22P-03", "L22P-04"]},
            {"id": "P24-R06", "requirement": "ValueError versus KeyError", "lesson": "2.2", "notebook": True, "questions": ["L22P-05", "L22P-06"]},
            {"id": "P24-R07", "requirement": "test state before and after mutation", "lesson": "2.2", "notebook": True, "questions": ["L22P-07", "L22P-08"]},
            {"id": "P24-R08", "requirement": "supplied checker consumes public contract", "lesson": "2.2", "notebook": True, "questions": ["L22P-09", "L22P-10"]},
        ],
    }
    target = ROOT / "sample-content/introduction-to-python/localization/chapter-2-project24-readiness-v1.json"
    target.write_text(json.dumps(mapping, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
