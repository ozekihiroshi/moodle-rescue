from pathlib import Path


root = Path(__file__).resolve().parents[1]
learner_sources = [
    root / "sample-content/introduction-to-python/python-lab/project-files/projects/library-manager/library_manager.py",
    root / "sample-content/introduction-to-python/python-lab/project-files/ja/projects/library-manager/library_manager.py",
    root / "sample-content/introduction-to-python/reference-solutions/project-2-4/library_manager.py",
]
for path in learner_sources:
    text = path.read_text(encoding="utf-8")
    text = text.replace("from __future__ import annotations\n\n", "", 1)
    path.write_text(text, encoding="utf-8", newline="\n")
    print(path)

checker = root / "sample-content/introduction-to-python/python-lab/project-files/projects/library-manager/check_library_manager.py"
text = checker.read_text(encoding="utf-8")
text = text.replace(
    'SAMPLE = HERE / "data" / "books.csv"\n',
    'SAMPLE = HERE / "data" / "books.csv"\nLANGUAGE = os.environ.get("LIBRARY_MANAGER_CHECK_LANGUAGE", "en")\n',
    1,
)
text = text.replace(
    'TESTS = [\n',
    '''JA_TEST_NAMES = {
    "sample CSV and Boolean conversion": "サンプルCSVとブール値変換",
    "invalid columns, values, and blanks": "列不足・不正値・空欄",
    "duplicate IDs in CSV": "CSV内の重複ID",
    "add and find": "登録と検索",
    "invalid additions": "不正な登録",
    "rename and mark as read": "書名変更と読了変更",
    "missing-ID errors": "存在しないIDのエラー",
    "remove": "削除",
    "summary and CSV round trip": "集計とCSV再読み込み",
    "complete update sheet and report": "更新票全体と報告",
}


TESTS = [
''',
    1,
)
old_main = '''def main():
    print("CSV library record manager automatic check")
    print("Target:", TARGET)
    if not TARGET.is_file():
        print("[NG] library_manager.py was not found")
        return 1
    if "PROGRAM INCOMPLETE" in TARGET.read_text(encoding="utf-8"):
        print("[NG] starter program is not complete")
        print("     Complete the TODOs and remove the PROGRAM INCOMPLETE line.")
        return 1
'''
new_main = '''def main():
    japanese = LANGUAGE == "ja"
    print("CSV図書記録管理の自動確認" if japanese else "CSV library record manager automatic check")
    print("確認対象:" if japanese else "Target:", TARGET)
    if not TARGET.is_file():
        print("[NG] library_manager.pyが見つかりません" if japanese else "[NG] library_manager.py was not found")
        return 1
    if "PROGRAM INCOMPLETE" in TARGET.read_text(encoding="utf-8"):
        print("[NG] スタータープログラムは未完成です" if japanese else "[NG] starter program is not complete")
        print("     TODOを完成させ、PROGRAM INCOMPLETEの行を削除してください。" if japanese else "     Complete the TODOs and remove the PROGRAM INCOMPLETE line.")
        return 1
'''
if old_main not in text:
    raise SystemExit("checker main block was not found")
text = text.replace(old_main, new_main, 1)
text = text.replace(
    '''        for name, test in TESTS:
            try:
                test(module, work / name.replace(" ", "-"))
            except Exception as error:
                failures += 1
                print(f"[NG] {name}")
                print(f"     {type(error).__name__}: {error}")
            else:
                print(f"[OK] {name}")
    if failures:
        print(f"\\n{failures} check(s) need attention.")
        print("Change only library_manager.py, save it, and run this checker again.")
        return 1
''',
    '''        for name, test in TESTS:
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
        print(f"\\n{failures}項目を修正する必要があります。" if japanese else f"\\n{failures} check(s) need attention.")
        print("library_manager.pyだけを修正・保存し、もう一度確認してください。" if japanese else "Change only library_manager.py, save it, and run this checker again.")
        return 1
''',
    1,
)
checker.write_text(text, encoding="utf-8", newline="\n")
print(checker)

wrapper = root / "sample-content/introduction-to-python/python-lab/project-files/ja/projects/library-manager/check_library_manager.py"
wrapper_text = wrapper.read_text(encoding="utf-8")
wrapper_text = wrapper_text.replace(
    'os.environ["LIBRARY_MANAGER_TARGET"] = str(Path(__file__).with_name("library_manager.py"))\n',
    'os.environ["LIBRARY_MANAGER_TARGET"] = str(Path(__file__).with_name("library_manager.py"))\nos.environ["LIBRARY_MANAGER_CHECK_LANGUAGE"] = "ja"\n',
    1,
)
wrapper.write_text(wrapper_text, encoding="utf-8", newline="\n")
print(wrapper)
