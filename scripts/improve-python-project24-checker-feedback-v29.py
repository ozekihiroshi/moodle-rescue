from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
checker = (
    ROOT
    / "sample-content"
    / "introduction-to-python"
    / "python-lab"
    / "project-files"
    / "projects"
    / "library-manager"
    / "check_library_manager.py"
)
text = checker.read_text(encoding="utf-8")

old_marker = '''    if "PROGRAM INCOMPLETE" in TARGET.read_text(encoding="utf-8"):
        print("[NG] スタータープログラムは未完成です" if japanese else "[NG] starter program is not complete")
        print("     TODOを完成させ、PROGRAM INCOMPLETEの行を削除してください。" if japanese else "     Complete the TODOs and remove the PROGRAM INCOMPLETE line.")
        return 1
'''
if old_marker in text:
    text = text.replace(old_marker, "", 1)

test_anchor = '''def test_complete_project(module, work):
    before = SAMPLE.read_bytes()
'''
test_replacement = '''def test_complete_project(module, work):
    require(
        "PROGRAM INCOMPLETE" not in TARGET.read_text(encoding="utf-8"),
        "finish every TODO and remove the PROGRAM INCOMPLETE line",
    )
    before = SAMPLE.read_bytes()
'''
if test_anchor in text:
    text = text.replace(test_anchor, test_replacement, 1)
elif test_replacement not in text:
    raise SystemExit("Completion-marker test anchor missing")

start = text.index("JA_TEST_NAMES = {")
end = text.index("\n\n\ndef main():", start)
replacement = '''JA_TEST_NAMES = {
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
]'''
text = text[:start] + replacement + text[end:]
checker.write_text(text, encoding="utf-8")

print("Project 2.4 checker now reports function names and supports staged checking")
