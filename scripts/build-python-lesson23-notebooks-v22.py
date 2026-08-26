#!/usr/bin/env python3
"""Build the canonical and Japanese Lesson 2.3 notebooks."""

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id, text):
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code(cell_id, text):
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


def common_code(language):
    missing = "教材ファイルが見つかりません" if language == "ja" else "Course file not found"
    return f'''from pathlib import Path

def find_course_file(relative_path):
    """Find a supplied course file from a Notebook opened at any course level."""
    start = Path.cwd().resolve()
    for folder in [start, *start.parents]:
        candidate = folder / relative_path
        if candidate.is_file():
            return candidate
    raise FileNotFoundError(f"{missing}: {{relative_path}}; started at {{start}}")

source_path = find_course_file(Path("data") / "library-books-practice.csv")
print("Resolved source:", source_path)
'''


def notebook(language):
    if language == "ja":
        cells = [
            md("l23-ja-title", "# 2.3 — ファイル・CSVの読み書き\n\nこれまでの値はプログラム終了時に失われました。このレッスンでは、外部ファイルに保存された図書記録を読み、検査し、別のCSVへ保存して、もう一度読み直せるところまで進みます。元データは上書きしません。"),
            md("l23-ja-path", "## 最初に、実際に読むファイルの場所を確定する\n\n相対パスは現在の作業フォルダを基準に解釈されます。Notebookを英語版の直下から開く場合と`ja`フォルダから開く場合では現在位置が異なるため、教材ファイルがある親フォルダまで順に探します。エラーになったら、推測でパスを書き換える前に、解決した絶対パスを表示します。"),
            code("l23-ja-path-code", common_code("ja")),
            md("l23-ja-script-path", "### `.py`ファイルでは`__file__`を基準にする\n\nNotebookでは`Path.cwd()`から教材ルートを探しました。独立したスクリプトでは、次の形でスクリプト自身の場所を基準にします。2.4のスタータープログラムでは、この形が最初から用意されています。\n\n```python\nBASE_DIR = Path(__file__).resolve().parent\nINPUT_PATH = BASE_DIR / \"data\" / \"books.csv\"\nOUTPUT_PATH = BASE_DIR / \"output\" / \"books_updated.csv\"\n```"),
            md("l23-ja-text", "## `with`でファイルを開き、文字コードを明示する\n\n`with`ブロックを抜けるとファイルは自動的に閉じられます。`r`は読込、`w`は新規作成または上書き、`a`は末尾への追加です。ここではUTF-8を明示します。CSVでは改行をCSVモジュールへ任せるため`newline=\"\"`も指定します。"),
            code("l23-ja-text-code", '''with source_path.open("r", encoding="utf-8", newline="") as file:
    text = file.read()

print(text)
'''),
            md("l23-ja-csv", "## CSVを文字列の`split(\",\")`で分けない\n\nCSVではフィールド内のコンマを引用符で囲めます。サンプルの2行目の書名にはコンマがありますが、それでも一つの書名です。標準ライブラリの`csv`モジュールなら、この規則を正しく扱えます。"),
            code("l23-ja-reader", '''import csv

with source_path.open("r", encoding="utf-8", newline="") as file:
    reader = csv.DictReader(file)
    print("Header:", reader.fieldnames)
    raw_rows = list(reader)

for row in raw_rows:
    print(row)
'''),
            md("l23-ja-strings", "## CSVから読んだ値は、最初はすべて文字列\n\n`DictReader`はヘッダーを辞書のキーにしますが、`false`は自動的に`False`へ変わりません。`bool(\"false\")`は空でない文字列なので`True`です。意味を確認する変換関数を作り、受け入れない値では`ValueError`を送出します。"),
            code("l23-ja-bool", '''def parse_read(value):
    normalised = value.strip().lower()
    if normalised == "true":
        return True
    if normalised == "false":
        return False
    raise ValueError(f"read must be true or false: {value!r}")

print(parse_read(" TRUE "))
print(parse_read("false"))

try:
    parse_read("yes")
except ValueError as error:
    print(type(error).__name__, error)
'''),
            md("l23-ja-validation", "## ヘッダーと各行を、使う前に検査する\n\n必要列は`id`、`title`、`read`です。列不足、空のIDや書名、重複ID、不正な真偽値を黙って補正すると、後の処理が誤ったデータで進みます。入力境界で原因を示して止めます。余分な列は、この課題では無視できます。"),
            code("l23-ja-load", '''REQUIRED_FIELDS = {"id", "title", "read"}

def validate_header(fieldnames):
    actual = set(fieldnames or [])
    missing = REQUIRED_FIELDS - actual
    if missing:
        raise ValueError(f"Missing CSV columns: {sorted(missing)}")

def load_books(path):
    books = []
    seen_ids = set()
    with path.open("r", encoding="utf-8", newline="") as file:
        reader = csv.DictReader(file)
        validate_header(reader.fieldnames)
        for line_number, row in enumerate(reader, start=2):
            book_id = row["id"].strip()
            title = row["title"].strip()
            if not book_id or not title:
                raise ValueError(f"Blank required value on line {line_number}")
            if book_id in seen_ids:
                raise ValueError(f"Duplicate id on line {line_number}: {book_id}")
            books.append({"id": book_id, "title": title, "read": parse_read(row["read"])})
            seen_ids.add(book_id)
    return books

books = load_books(source_path)
print(books)
'''),
            md("l23-ja-header-test", "### 不足するヘッダーも単独で確認する\n\n入力ファイルを壊して試す必要はありません。検証関数へテスト用の見出しを渡し、期待した例外になることを確認できます。"),
            code("l23-ja-header-test-code", '''try:
    validate_header(["id", "title"])
except ValueError as error:
    print(type(error).__name__, error)
'''),
            md("l23-ja-write", "## 元ファイルとは別の出力先へ保存する\n\n教材として渡された`data`のCSVは入力証拠です。変更後の記録は`output`へ保存します。`DictWriter`には出力する列順を明示し、Pythonの真偽値は小文字の`true`または`false`へ戻します。"),
            code("l23-ja-write-code", '''def save_books(books, path):
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=["id", "title", "read"])
        writer.writeheader()
        for book in books:
            writer.writerow({
                "id": book["id"],
                "title": book["title"],
                "read": "true" if book["read"] else "false",
            })

source_before = source_path.read_bytes()
updated_books = []
for book in books:
    updated_books.append(book.copy())
updated_books[2]["read"] = True

output_path = source_path.parents[1] / "output" / "lesson23-books-updated.csv"
save_books(updated_books, output_path)
print("Saved:", output_path)
'''),
            md("l23-ja-reload", "## 保存成功ではなく、再読込して内容を照合する\n\nファイルが存在するだけでは、列名や型変換が正しいとは限りません。同じ`load_books()`で出力を読み直し、期待したレコードと一致することを確認します。同時に、元CSVのバイト列が変わっていないことも確認します。"),
            code("l23-ja-reload-code", '''reloaded_books = load_books(output_path)
assert reloaded_books == updated_books
assert source_path.read_bytes() == source_before
assert reloaded_books[2]["read"] is True
print("ROUND TRIP OK")
print("SOURCE PRESERVED")
'''),
            md("l23-ja-notfound", "## `FileNotFoundError`では、探した場所を表示する\n\nファイル名だけを見ても、Pythonがどのフォルダを基準にしたか分かりません。候補を`resolve()`して表示し、ファイルが配布されているか、名前と大文字小文字が一致するかを順に確認します。"),
            code("l23-ja-notfound-code", '''missing_path = source_path.parent / "missing.csv"
print("Would read:", missing_path.resolve())
print("Exists:", missing_path.exists())
'''),
            md("l23-ja-transfer", "## 応用練習\n\n`data/library-books-practice.csv`を読み、`L001`の書名だけを`Python Foundations`へ変更したコピーを`output/lesson23-practice.csv`へ保存してください。元CSVは変更しません。保存後に`load_books()`で読み直し、レコード数、ID順、書名、真偽値が期待どおりであることを`assert`で確認します。"),
            code("l23-ja-work", "# ここに応用練習の解答を書きます。\n"),
            md("l23-ja-complete", "## 完了確認\n\nファイルモード、`with`、UTF-8、`newline=\"\"`、CSVの引用符、`DictReader`、明示的な型変換、ヘッダーと値の検証、`DictWriter`、入力と出力の分離、再読込、解決済みパスの確認を説明できたら保存し、理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l23-en-title", "# 2.3 — File and CSV input/output\n\nValues used so far disappeared when a program ended. In this lesson, read book records from an external file, validate them, save a separate CSV, and prove that the result can be loaded again. Never overwrite the supplied source data."),
            md("l23-en-path", "## First establish the exact file to read\n\nA relative path is interpreted from the current working directory. An English Notebook and one inside the `ja` folder may start in different places, so search upward for the supplied course file. When a path fails, display the resolved absolute path before guessing a replacement."),
            code("l23-en-path-code", common_code("en")),
            md("l23-en-script-path", "### In a `.py` file, resolve paths from `__file__`\n\nThe Notebook searched from `Path.cwd()`. An independent script should resolve paths from its own location. The Project 2.4 starter supplies this pattern.\n\n```python\nBASE_DIR = Path(__file__).resolve().parent\nINPUT_PATH = BASE_DIR / \"data\" / \"books.csv\"\nOUTPUT_PATH = BASE_DIR / \"output\" / \"books_updated.csv\"\n```"),
            md("l23-en-text", "## Open a file with `with` and state its encoding\n\nLeaving a `with` block closes the file automatically. Mode `r` reads, `w` creates or replaces, and `a` appends. State UTF-8 explicitly. For CSV, pass `newline=\"\"` so the CSV module controls record endings."),
            code("l23-en-text-code", '''with source_path.open("r", encoding="utf-8", newline="") as file:
    text = file.read()

print(text)
'''),
            md("l23-en-csv", "## Do not parse CSV with `split(\",\")`\n\nCSV can quote a field that contains a comma. The title in the second sample row contains a comma but remains one title. The standard `csv` module handles that rule correctly."),
            code("l23-en-reader", '''import csv

with source_path.open("r", encoding="utf-8", newline="") as file:
    reader = csv.DictReader(file)
    print("Header:", reader.fieldnames)
    raw_rows = list(reader)

for row in raw_rows:
    print(row)
'''),
            md("l23-en-strings", "## Values read from CSV begin as strings\n\n`DictReader` uses the header as dictionary keys, but it does not turn `false` into `False`. `bool(\"false\")` is `True` because the string is non-empty. Write a conversion function that checks meaning and raises `ValueError` for unsupported text."),
            code("l23-en-bool", '''def parse_read(value):
    normalised = value.strip().lower()
    if normalised == "true":
        return True
    if normalised == "false":
        return False
    raise ValueError(f"read must be true or false: {value!r}")

print(parse_read(" TRUE "))
print(parse_read("false"))

try:
    parse_read("yes")
except ValueError as error:
    print(type(error).__name__, error)
'''),
            md("l23-en-validation", "## Validate the header and every row before use\n\nThe required columns are `id`, `title`, and `read`. Silently repairing a missing column, blank ID or title, duplicate ID, or invalid Boolean lets later work continue with false data. Reject it at the input boundary with a useful cause. Extra columns may be ignored here."),
            code("l23-en-load", '''REQUIRED_FIELDS = {"id", "title", "read"}

def validate_header(fieldnames):
    actual = set(fieldnames or [])
    missing = REQUIRED_FIELDS - actual
    if missing:
        raise ValueError(f"Missing CSV columns: {sorted(missing)}")

def load_books(path):
    books = []
    seen_ids = set()
    with path.open("r", encoding="utf-8", newline="") as file:
        reader = csv.DictReader(file)
        validate_header(reader.fieldnames)
        for line_number, row in enumerate(reader, start=2):
            book_id = row["id"].strip()
            title = row["title"].strip()
            if not book_id or not title:
                raise ValueError(f"Blank required value on line {line_number}")
            if book_id in seen_ids:
                raise ValueError(f"Duplicate id on line {line_number}: {book_id}")
            books.append({"id": book_id, "title": title, "read": parse_read(row["read"])})
            seen_ids.add(book_id)
    return books

books = load_books(source_path)
print(books)
'''),
            md("l23-en-header-test", "### Test a missing header independently\n\nThere is no need to damage the supplied file. Pass a test header to the validation function and confirm that the expected exception is raised."),
            code("l23-en-header-test-code", '''try:
    validate_header(["id", "title"])
except ValueError as error:
    print(type(error).__name__, error)
'''),
            md("l23-en-write", "## Save to a separate output path\n\nThe supplied CSV in `data` is input evidence. Save changed records under `output`. Give `DictWriter` a stable field order and convert Python Booleans back to lower-case `true` or `false`."),
            code("l23-en-write-code", '''def save_books(books, path):
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=["id", "title", "read"])
        writer.writeheader()
        for book in books:
            writer.writerow({
                "id": book["id"],
                "title": book["title"],
                "read": "true" if book["read"] else "false",
            })

source_before = source_path.read_bytes()
updated_books = []
for book in books:
    updated_books.append(book.copy())
updated_books[2]["read"] = True

output_path = source_path.parents[1] / "output" / "lesson23-books-updated.csv"
save_books(updated_books, output_path)
print("Saved:", output_path)
'''),
            md("l23-en-reload", "## Reload and compare instead of trusting a successful save\n\nA file can exist while containing the wrong columns or text conversion. Reload it with the same `load_books()` function and compare it with the expected records. Also prove that the supplied CSV bytes are unchanged."),
            code("l23-en-reload-code", '''reloaded_books = load_books(output_path)
assert reloaded_books == updated_books
assert source_path.read_bytes() == source_before
assert reloaded_books[2]["read"] is True
print("ROUND TRIP OK")
print("SOURCE PRESERVED")
'''),
            md("l23-en-notfound", "## For `FileNotFoundError`, display where Python looked\n\nA filename alone does not reveal the directory Python used. Resolve and display the candidate, then check distribution, spelling, and letter case in that order."),
            code("l23-en-notfound-code", '''missing_path = source_path.parent / "missing.csv"
print("Would read:", missing_path.resolve())
print("Exists:", missing_path.exists())
'''),
            md("l23-en-transfer", "## Transfer exercise\n\nLoad `data/library-books-practice.csv`, change only the title of `L001` to `Python Foundations` in a copied record collection, and save `output/lesson23-practice.csv`. Do not change the source CSV. Reload the output with `load_books()` and use `assert` to check record count, ID order, title, and Boolean values."),
            code("l23-en-work", "# Write the transfer solution here.\n"),
            md("l23-en-complete", "## Completion check\n\nWhen you can explain file modes, `with`, UTF-8, `newline=\"\"`, CSV quoting, `DictReader`, explicit conversion, header and value validation, `DictWriter`, separate input and output, round-trip checking, and resolved-path diagnostics, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "2.3", "language": language, "concepts": [f"IO{i:02d}" for i in range(1, 11)], "revision": 22},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main():
    targets = {
        "en": TEMPLATES / "07_files_csv.ipynb",
        "ja": TEMPLATES / "ja/07_files_csv.ipynb",
    }
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "resolve and diagnose course-relative paths in notebooks and scripts",
        "use with, file modes, UTF-8, and newline correctly",
        "parse quoted CSV with the standard csv module",
        "understand DictReader headers and initial string values",
        "convert Boolean text deliberately and reject invalid text",
        "validate required headers, blanks, and duplicate IDs",
        "write stable CSV fields and lower-case Boolean text",
        "preserve source data by separating input and output paths",
        "reload output and compare records for a round trip",
        "transfer the full read-validate-change-write-reload workflow",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "2.3 File and CSV input/output",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"IO{i:02d}", "description": item, "lesson": True, "notebook": True, "question": f"L23R-{i:02d}"}
            for i, item in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/07_files_csv.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/07_files_csv.ipynb",
        },
        "dataset": "sample-content/introduction-to-python/datasets/library-books-practice.csv",
        "implementation": "scripts/upgrade-python-lesson23-v22.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-2-3-concept-map-v1.json"
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
