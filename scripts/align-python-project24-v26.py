from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
finalizer = ROOT / "scripts" / "finalize-python-project24-learner-brief-v25.py"
text = finalizer.read_text(encoding="utf-8")

replacements = {
    """The program must work from the supplied data rather than assuming that every
input file always contains exactly four books.""": """Functions such as `load_books()` process any valid number of records rather than
fixing the input at four books. However, this project's `run_project()` is specific
to the supplied `books.csv` and applies the four update requests shown below.""",
    """プログラムは、入力が常に4冊だと決めつけず、配布されたデータを処理できるようにします。""": """`load_books()`などの個別関数は、データ件数を4冊に固定せず処理します。ただし、
今回の`run_project()`は、配布された`books.csv`へ指定の4件を適用する専用処理です。""",
    """The starter also contains a completed `main()` in addition to the ten functions
below. `main()` calls `run_project()` with the default paths and displays the
returned summary. Do not rename or change `main()`.""": """The starter also contains a completed `main()` in addition to the ten functions
below. `main()` calls `run_project()` with the default paths and displays the
returned summary. Do not rename or change `main()`. IDs and titles are stripped
of surrounding whitespace before validation, search, or storage.""",
    """スターターコードには、次の10関数とは別に完成済みの`main()`があります。`main()`は
既定パスを使って`run_project()`を呼び出し、返された集計結果を画面に表示します。
`main()`の名前や処理は変更しません。""": """スターターコードには、次の10関数とは別に完成済みの`main()`があります。`main()`は
既定パスを使って`run_project()`を呼び出し、返された集計結果を画面に表示します。
`main()`の名前や処理は変更しません。IDと書名は、検証・検索・保存の前に前後の空白を
取り除きます。""",
    """| `run_project(input_path, output_path)` | connect load, four fixed updates, save, summary | summary dictionary; completed `main()` prints it |""": """| `run_project(input_path, output_path)` | load the input, apply four fixed updates, summarise, save, return | summary dictionary; completed `main()` prints it |""",
    """| `run_project(input_path, output_path)` | 読込、固定更新4件、保存、集計を接続 | 集計辞書を返す。完成済みの`main()`が表示する |""": """| `run_project(input_path, output_path)` | 読込、固定更新4件、集計、保存、返却を接続 | 集計辞書を返す。完成済みの`main()`が表示する |""",
    """6. Connect loading, the fixed updates, saving, and summary in `run_project()`.""": """6. In `run_project()`, connect load, updates, summary, save, and return in that order.""",
    """6. `run_project()`で読込、固定更新4件、保存、集計を順番につなぐ。""": """6. `run_project()`で読込、固定更新4件、集計、保存、返却を順番につなぐ。""",
}

for old, new in replacements.items():
    if new in text:
        continue
    if old not in text:
        raise SystemExit("Finalizer alignment anchor missing: " + old[:80])
    text = text.replace(old, new, 1)
finalizer.write_text(text, encoding="utf-8")

starter_paths = [
    ROOT / "sample-content" / "introduction-to-python" / "python-lab" / "project-files" / "projects" / "library-manager" / "library_manager.py",
    ROOT / "sample-content" / "introduction-to-python" / "python-lab" / "project-files" / "ja" / "projects" / "library-manager" / "library_manager.py",
]
old_doc = '    """Load, call the four fixed update functions in order, save, and return summary."""'
new_doc = '    """Load, apply fixed updates, summarise, save, and return the summary."""'
for path in starter_paths:
    source = path.read_text(encoding="utf-8")
    if old_doc in source:
        path.write_text(source.replace(old_doc, new_doc, 1), encoding="utf-8")
    elif "読込、固定更新4関数の順次呼出し、保存を行い、集計辞書を返します。" in source:
        path.write_text(source.replace(
            "読込、固定更新4関数の順次呼出し、保存を行い、集計辞書を返します。",
            "読込、固定更新、集計、保存の順に処理し、集計辞書を返します。",
            1,
        ), encoding="utf-8")
    elif new_doc not in source and "読込、固定更新、集計、保存の順に処理し、集計辞書を返します。" not in source:
        raise SystemExit(f"Starter run_project docstring missing: {path}")

reference = ROOT / "sample-content" / "introduction-to-python" / "reference-solutions" / "project-2-4" / "library_manager.py"
source = reference.read_text(encoding="utf-8")
old_order = '''    remove_book(books, "B004")
    save_books(books, output_path)
    return summarise_books(books)
'''
new_order = '''    remove_book(books, "B004")
    summary = summarise_books(books)
    save_books(books, output_path)
    return summary
'''
if old_order in source:
    reference.write_text(source.replace(old_order, new_order, 1), encoding="utf-8")
elif new_order not in source:
    raise SystemExit("Reference run_project order anchor missing")

checker = ROOT / "sample-content" / "introduction-to-python" / "python-lab" / "project-files" / "projects" / "library-manager" / "check_library_manager.py"
source = checker.read_text(encoding="utf-8")
source = source.replace('"complete update sheet and report": "更新票全体と報告"', '"complete fixed updates and report": "固定更新全体と報告"')
source = source.replace('("complete update sheet and report", test_complete_project)', '("complete fixed updates and report", test_complete_project)')
checker.write_text(source, encoding="utf-8")

verifier = ROOT / "scripts" / "verify-python-project24-v24.php"
source = verifier.read_text(encoding="utf-8")
anchor = '    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI path");\n'
table_check = '''    $beforeHeader = $ja ? '<th>ID</th><th>更新前</th><th>操作</th><th>更新後</th>' : '<th>ID</th><th>Before</th><th>Operation</th><th>After</th>';
    $functionHeader = $ja ? '<th>関数</th><th>引数と役割</th><th>戻り値・状態変化・例外</th>' : '<th>Function</th><th>Inputs and responsibility</th><th>Return, mutation, and exceptions</th>';
    if (!str_contains($page->content, $beforeHeader) || !str_contains($page->content, $functionHeader)) throw new RuntimeException("$shortname table columns are not separated");
'''
if table_check not in source:
    if anchor not in source:
        raise SystemExit("Moodle table verifier anchor missing")
    verifier.write_text(source.replace(anchor, table_check + anchor, 1), encoding="utf-8")

print("Project 2.4 v26 specification and implementation order aligned")
