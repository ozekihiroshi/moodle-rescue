from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
finalizer = ROOT / "scripts" / "finalize-python-project24-learner-brief-v25.py"
text = finalizer.read_text(encoding="utf-8")

replacements = {
    """## 2. What you will do

1. Load the supplied `books.csv` (the sample contains four books).""": """## 2. What you will do

You do not create the program from an empty file. In Python Lab, open the
supplied starter at `projects/library-manager/library_manager.py` and complete
its ten unfinished functions. This Python program will read and process the CSV.
Edit only `library_manager.py`.

1. Make the Python program load the supplied `books.csv` (the sample contains four books).""",
    """## 2. この課題で行うこと

1. 配布された`books.csv`を読み込む（今回のサンプルは4冊）。""": """## 2. この課題で行うこと

この課題では、Pythonプログラムを一から作成しません。Python Labに用意された
`projects/library-manager/library_manager.py`を開き、スターターコードの未完成の
10関数を実装してプログラムを完成させます。このPythonプログラムがCSVを読み込み、
次の処理を行います。編集するファイルは`library_manager.py`一つだけです。

1. Pythonプログラムで配布済みの`books.csv`を読み込む（今回のサンプルは4冊）。""",
    """0: \"# Project 2.4 — CSV library record manager\\n\\nUse the file browser to open `projects/library-manager/README.md`. Edit only `library_manager.py`; this Notebook guides the work and is not the submission.\\n\",""": """0: \"# Project 2.4 — CSV library record manager\\n\\nYou do not start from an empty file. In the file browser, open `projects/library-manager/library_manager.py` and complete its ten unfinished functions. Edit only that supplied starter. This Notebook guides the work and is not the submission.\\n\",""",
    """0: \"# 2.4 実践プロジェクト — CSV図書記録管理\\n\\n左のファイル一覧から`projects/library-manager/README.md`を開きます。編集するのは`library_manager.py`だけです。このNotebookは作業案内であり、提出物ではありません。\\n\",""": """0: \"# 2.4 実践プロジェクト — CSV図書記録管理\\n\\nプログラムを一から作る必要はありません。左のファイル一覧から`projects/library-manager/library_manager.py`を開き、用意されたスターターコードの未完成の10関数を実装します。編集するのはこのファイルだけです。このNotebookは作業案内であり、提出物ではありません。\\n\",""",
}

for old, new in replacements.items():
    if old in text:
        text = text.replace(old, new, 1)
    elif new not in text:
        raise SystemExit("Starter clarification anchor missing: " + old[:100])

finalizer.write_text(text, encoding="utf-8")

verifier = ROOT / "scripts" / "verify-python-project24-v24.php"
source = verifier.read_text(encoding="utf-8")
anchor = '    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI path");\n'
check = '''    foreach ([$ja ? 'Pythonプログラムを一から作成しません' : 'do not create the program from an empty file', 'projects/library-manager/library_manager.py', $ja ? '編集するファイルは' : 'Edit only'] as $starterToken) {
        if (!str_contains($page->content, $starterToken) || !str_contains($assign->intro, $starterToken)) throw new RuntimeException("$shortname missing starter instruction $starterToken");
    }
'''
if check not in source:
    if anchor not in source:
        raise SystemExit("Moodle verifier anchor missing")
    verifier.write_text(source.replace(anchor, check + anchor, 1), encoding="utf-8")

print("Project 2.4 starter location clarified in section 2 and Notebook")
