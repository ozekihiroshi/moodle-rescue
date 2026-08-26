#!/usr/bin/env python3
"""プロジェクト2.4スターター：公開された関数契約を変えずTODOを完成させます。"""
import csv
from pathlib import Path

HERE = Path(__file__).resolve().parent
DEFAULT_INPUT_PATH = HERE / "data" / "books.csv"
DEFAULT_OUTPUT_PATH = HERE / "output" / "books_updated.csv"
REQUIRED_FIELDS = {"id", "title", "read"}


def parse_read(value):
    """CSV文字列true/falseをboolへ変換し、それ以外はValueErrorにします。"""
    raise NotImplementedError("TODO: implement parse_read")


def load_books(path):
    """検証済みの本をid、title、bool型readを持つ辞書のリストで返します。"""
    raise NotImplementedError("TODO: implement load_books")


def find_book(books, book_id):
    """一致する保存中の辞書を返し、該当IDがなければNoneを返します。"""
    raise NotImplementedError("TODO: implement find_book")


def add_book(books, book_id, title):
    """未読の本を追加して返し、空欄または重複データを拒否します。"""
    raise NotImplementedError("TODO: implement add_book")


def rename_book(books, book_id, new_title):
    """保存中の本の書名を変えて返し、IDがなければKeyErrorにします。"""
    raise NotImplementedError("TODO: implement rename_book")


def mark_as_read(books, book_id):
    """保存中の本を読了済みにして返し、IDがなければKeyErrorにします。"""
    raise NotImplementedError("TODO: implement mark_as_read")


def remove_book(books, book_id):
    """保存中の本を削除して返し、IDがなければKeyErrorにします。"""
    raise NotImplementedError("TODO: implement remove_book")


def summarise_books(books):
    """合計、読了、未読の件数を辞書で返します。"""
    raise NotImplementedError("TODO: implement summarise_books")


def save_books(books, path):
    """親を作り、現在順・小文字真偽値のUTF-8 CSVを書き、Noneを返します。"""
    raise NotImplementedError("TODO: implement save_books")


def run_project(input_path=DEFAULT_INPUT_PATH, output_path=DEFAULT_OUTPUT_PATH):
    """読込、固定更新、集計、保存の順に処理し、集計辞書を返します。"""
    raise NotImplementedError("TODO: implement run_project")


def main():
    summary = run_project()
    print("LIBRARY UPDATE REPORT")
    print(f"TOTAL BOOKS: {summary['total']}")
    print(f"READ BOOKS: {summary['read']}")
    print(f"UNREAD BOOKS: {summary['unread']}")
    print(f"OUTPUT FILE: {DEFAULT_OUTPUT_PATH.name}")
    # すべてのTODOを完成させたら、次の行を削除します。
    print("PROGRAM INCOMPLETE")


if __name__ == "__main__":
    main()
