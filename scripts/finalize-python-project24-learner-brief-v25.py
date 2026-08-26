from __future__ import annotations

import html
import json
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content" / "introduction-to-python"
LAB = BASE / "python-lab"
EN_README = LAB / "project-files" / "projects" / "library-manager" / "README.md"
JA_README = LAB / "project-files" / "ja" / "projects" / "library-manager" / "README.md"
UPDATER = ROOT / "scripts" / "upgrade-python-project24-v24.php"
VERIFIER = ROOT / "scripts" / "verify-python-project24-v24.php"


EN = r'''# Project 2.4 — CSV library record manager

## 1. Situation

A small learning centre stores its book catalogue in `data/books.csv`. A staff
member has received four requests to update the catalogue. Build a program that
loads the source CSV, applies those requests, counts the updated records, displays
a report, and saves the result as a separate CSV.

## 2. What you will do

You do not create the program from an empty file. In Python Lab, open the
supplied starter at `projects/library-manager/library_manager.py` and complete
its ten unfinished functions. This Python program will read and process the CSV.
Edit only `library_manager.py`.

1. Make the Python program load the supplied `books.csv` (the sample contains four books).
2. Apply four specified updates in order.
3. Count the books after the updates.
4. Save the updated records to `books_updated.csv`.
5. Display the summary on screen.

Functions such as `load_books()` process any valid number of records rather than
fixing the input at four books. However, this project's `run_project()` is specific
to the supplied `books.csv` and applies the four update requests shown below.

## 3. Input CSV and source protection

The supplied `projects/library-manager/data/books.csv` contains:

```csv
id,title,read
B001,Python Basics,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,false
B004,"Writing, Presenting, and Learning",true
```

This listing is provided so you can understand the input before opening the
file. Do not copy it into the Python source. Read the supplied file with
`csv.DictReader`.

The first row is the header. `DictReader` initially produces string values with
the keys `id`, `title`, and `read`; `parse_read()` converts the `read` text to a
bool. B004 shows that a field containing commas is quoted. Do not split lines
yourself or remove the quotes yourself—the `csv` module handles them.

`data/books.csv` is the unchanged source record. Do not edit or overwrite it.
Always save the result to `output/books_updated.csv`.

## 4. How to apply the four update requests

In a larger system, update requests might come from another file or a user
interface. This project focuses on writing and combining update functions, so
the four requests are not another file and are not keyboard input. Implement
these fixed function calls directly inside `run_project()`, in this order:

```python
add_book(books, "B005", "Algorithms Made Clear")
mark_as_read(books, "B003")
rename_book(books, "B001", "Python Foundations")
remove_book(books, "B004")
```

## 5. Before and after

| ID | Before | Operation | After |
|---|---|---|---|
| B001 | Python Basics / unread | rename | Python Foundations / unread |
| B002 | Data Skills for Beginners / read | none | unchanged |
| B003 | Networks in Practice / unread | mark read | Networks in Practice / read |
| B004 | Writing, Presenting, and Learning / read | remove | not written |
| B005 | absent | add unread | Algorithms Made Clear / unread |

Because B004 is later removed, the checker tests `load_books()` independently
to confirm that its comma-containing title was read correctly.

## 6. Public contract for all ten functions

The starter also contains a completed `main()` in addition to the ten functions
below. `main()` calls `run_project()` with the default paths and displays the
returned summary. Do not rename or change `main()`. IDs and titles are stripped
of surrounding whitespace before validation, search, or storage.

| Function | Inputs and responsibility | Return, mutation, and exceptions |
|---|---|---|
| `parse_read(value)` | convert CSV Boolean text | ignore surrounding space and case; return bool; otherwise `ValueError` |
| `load_books(path)` | read UTF-8 CSV | dictionaries in input order; invalid columns, blanks, duplicates, or Booleans raise `ValueError` |
| `find_book(books, book_id)` | linear ID search | stored dictionary or `None` |
| `add_book(books, book_id, title)` | append an unread record | stored new dictionary; blank ID/title or duplicate ID raises `ValueError` |
| `rename_book(books, book_id, new_title)` | mutate stored title | stored changed dictionary; blank title `ValueError`; absent ID `KeyError` |
| `mark_as_read(books, book_id)` | mutate stored read state | stored changed dictionary; absent ID `KeyError` |
| `remove_book(books, book_id)` | remove one while preserving remaining order | removed dictionary; absent ID `KeyError` |
| `summarise_books(books)` | count total/read/unread | `{"total": n, "read": n, "unread": n}` |
| `save_books(books, path)` | create parent and write UTF-8 CSV | `None`; current list order, `id,title,read`, lower-case Booleans |
| `run_project(input_path, output_path)` | load the input, apply four fixed updates, summarise, save, return | summary dictionary; completed `main()` prints it |

Ignore extra CSV columns. A completely empty file raises `ValueError` for
missing columns; a correct header with no data rows returns an empty list. Do
not sort during saving; preserve the current list order.

## 7. Path basis

The starter already constructs the input and output paths from the script's own
folder. The program therefore finds its files regardless of the terminal's
current directory. Do not change the constant names or default paths.

## 8. Implementation stages

1. Complete `parse_read()` and `load_books()`; confirm four records and bool values.
2. Complete `find_book()`; check a present and an absent ID.
3. Complete add, rename, mark-read, and remove.
4. Complete `summarise_books()`.
5. Complete `save_books()` and reload its output.
6. In `run_project()`, connect load, updates, summary, save, and return in that order.
7. Finish every TODO and delete the final `print("PROGRAM INCOMPLETE")` line.

## 9. Manual check

Save with **Ctrl+S**, then run:

```text
python projects/library-manager/library_manager.py
```

The report must be:

```text
LIBRARY UPDATE REPORT
TOTAL BOOKS: 4
READ BOOKS: 2
UNREAD BOOKS: 2
OUTPUT FILE: books_updated.csv
```

The generated CSV must be:

```csv
id,title,read
B001,Python Foundations,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,true
B005,Algorithms Made Clear,false
```

Create this CSV from the record list with `csv.DictWriter`; do not write the
shown CSV as one fixed string.

## 10. Automatic check and submission

Run `python projects/library-manager/check_library_manager.py`. Change only
`library_manager.py` until all ten areas show `[OK]` and the last line is
`ALL TESTS PASSED`. Confirm again that the source CSV is unchanged.

Right-click `library_manager.py` in the Python Lab file browser, download it,
and upload that one file to the Moodle assignment.
'''


JA = r'''# 2.4 実践プロジェクト — CSV図書記録管理

## 1. 課題の状況

小規模な学習センターでは、図書台帳を`data/books.csv`に保存しています。担当者は
台帳に対する4件の更新依頼を受け取りました。元のCSVを読み込み、更新を適用し、
更新後の冊数を集計して画面へ表示し、結果を別のCSVへ保存するプログラムを作成します。

## 2. この課題で行うこと

この課題では、Pythonプログラムを一から作成しません。Python Labに用意された
`projects/library-manager/library_manager.py`を開き、スターターコードの未完成の
10関数を実装してプログラムを完成させます。このPythonプログラムがCSVを読み込み、
次の処理を行います。編集するファイルは`library_manager.py`一つだけです。

1. Pythonプログラムで配布済みの`books.csv`を読み込む（今回のサンプルは4冊）。
2. 指定された4件の更新を順番に適用する。
3. 更新後の冊数を集計する。
4. 更新結果を`books_updated.csv`へ保存する。
5. 集計結果を画面に表示する。

`load_books()`などの個別関数は、データ件数を4冊に固定せず処理します。ただし、
今回の`run_project()`は、配布された`books.csv`へ指定の4件を適用する専用処理です。

## 3. 入力CSVと原本の保護

配布済みの`projects/library-manager/data/books.csv`には次の内容があります。

```csv
id,title,read
B001,Python Basics,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,false
B004,"Writing, Presenting, and Learning",true
```

上記は、ファイルを開く前にも入力内容を理解できるように示したものです。Python
ソースへ書き写さず、配布済みのCSVを`csv.DictReader`で読み込みます。

1行目はヘッダーです。`DictReader`で読み込んだ直後は、`id`、`title`、`read`を
キーとする文字列の辞書になります。`read`の文字列は`parse_read()`でboolへ変換します。
B004のようにカンマを含む項目は引用符で囲まれます。自分で行を分割したり引用符を
取り除いたりせず、`csv`モジュールに処理させてください。

`data/books.csv`は更新前の原本です。このファイルを編集または上書きしてはいけません。
更新結果は必ず`output/books_updated.csv`へ保存します。

## 4. 4件の更新依頼を適用する方法

実務では、更新依頼を別ファイルや入力画面から受け取ることもあります。今回は更新を
行う関数の作り方と組み合わせ方を学ぶため、更新依頼を別ファイルやキーボードからは
読み込みません。次の4件を`run_project()`内へ、記載された順番の関数呼出しとして
直接実装します。

```python
add_book(books, "B005", "Algorithms Made Clear")
mark_as_read(books, "B003")
rename_book(books, "B001", "Python Foundations")
remove_book(books, "B004")
```

## 5. 更新前と更新後

| ID | 更新前 | 操作 | 更新後 |
|---|---|---|---|
| B001 | Python Basics／未読 | 書名変更 | Python Foundations／未読 |
| B002 | Data Skills for Beginners／読了 | 変更なし | そのまま |
| B003 | Networks in Practice／未読 | 読了へ変更 | Networks in Practice／読了 |
| B004 | Writing, Presenting, and Learning／読了 | 削除 | 出力しない |
| B005 | 存在しない | 未読で追加 | Algorithms Made Clear／未読 |

B004は最後に削除されるため、確認プログラムは`load_books()`単体でも、カンマを含む
書名を正しく読み込めたか検査します。

## 6. 10関数の公開仕様

スターターコードには、次の10関数とは別に完成済みの`main()`があります。`main()`は
既定パスを使って`run_project()`を呼び出し、返された集計結果を画面に表示します。
`main()`の名前や処理は変更しません。IDと書名は、検証・検索・保存の前に前後の空白を
取り除きます。

| 関数 | 引数と役割 | 戻り値・状態変化・例外 |
|---|---|---|
| `parse_read(value)` | CSVの真偽値文字列を変換 | 前後空白と大文字小文字を無視して`True`/`False`。それ以外は`ValueError` |
| `load_books(path)` | UTF-8 CSVを読む | 入力順を保った本の辞書リスト。必須列不足、空欄、重複ID、不正な真偽値は`ValueError` |
| `find_book(books, book_id)` | IDで線形検索 | リストに保存された辞書そのもの。該当なしは`None` |
| `add_book(books, book_id, title)` | 未読の本を末尾へ追加 | 追加した保存中の辞書。IDまたは書名の空欄、ID重複は`ValueError` |
| `rename_book(books, book_id, new_title)` | 保存中の書名を変更 | 変更した辞書。空の新書名は`ValueError`、対象なしは`KeyError` |
| `mark_as_read(books, book_id)` | 保存中の本を読了済みに変更 | 変更した辞書。対象なしは`KeyError` |
| `remove_book(books, book_id)` | 一件を削除し、残りの順序を維持 | 削除した辞書。対象なしは`KeyError` |
| `summarise_books(books)` | 合計、読了、未読を数える | `{"total": n, "read": n, "unread": n}`形式の辞書 |
| `save_books(books, path)` | 親フォルダを作りUTF-8 CSVを保存 | 戻り値は`None`。現在のリスト順、列順`id,title,read`、小文字`true`/`false`で書く |
| `run_project(input_path, output_path)` | 読込、固定更新4件、集計、保存、返却を接続 | 集計辞書を返す。完成済みの`main()`が表示する |

CSVの余分な列は無視します。完全に空のファイルは必須列不足として`ValueError`、
正しいヘッダーだけでデータ行がないCSVは空リストとして扱います。保存時にID順へ
並べ替えず、読み込みと更新で生じた現在のリスト順を維持します。

## 7. パスの基準

入出力パスを作るコードはスターターに用意されています。ターミナルの現在位置に
かかわらず、スクリプト自身の場所を基準にファイルを見つけます。定数名と既定パスは
変更しません。

## 8. 段階的な実装順

1. `parse_read()`と`load_books()`を完成させ、4件とbool型を確認する。
2. `find_book()`を完成させ、存在するIDと存在しないIDを確認する。
3. 追加、書名変更、読了変更、削除の4関数を完成させる。
4. `summarise_books()`で件数を計算する。
5. `save_books()`で別の出力CSVを作り、再読込する。
6. `run_project()`で読込、固定更新4件、集計、保存、返却を順番につなぐ。
7. すべてのTODOを完成させ、最後の`print("PROGRAM INCOMPLETE")`行を削除する。

## 9. 手動確認

**Ctrl+S**で保存してから実行します。

```text
python projects/library-manager/library_manager.py
```

画面表示は次です。

```text
LIBRARY UPDATE REPORT
TOTAL BOOKS: 4
READ BOOKS: 2
UNREAD BOOKS: 2
OUTPUT FILE: books_updated.csv
```

生成CSVは次の内容になります。

```csv
id,title,read
B001,Python Foundations,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,true
B005,Algorithms Made Clear,false
```

この文字列を直接書くのではなく、本の辞書リストから`csv.DictWriter`で作成します。

## 10. 自動確認と提出

`python projects/library-manager/check_library_manager.py`を実行します。変更するのは
`library_manager.py`だけです。全10項目が`[OK]`となり、最後に`ALL TESTS PASSED`が
表示されるまで修正します。元CSVが変更されていないことも、もう一度確認します。

Python Labのファイル一覧で`library_manager.py`を右クリックしてダウンロードし、
Moodleの提出課題へその一つだけをアップロードします。
'''


def inline(text: str) -> str:
    escaped = html.escape(text, quote=False)
    escaped = re.sub(r"`([^`]+)`", r"<code>\1</code>", escaped)
    escaped = re.sub(r"\*\*([^*]+)\*\*", r"<strong>\1</strong>", escaped)
    return escaped


def markdown_to_html(source: str) -> str:
    lines = source.splitlines()
    out = ['<div class="python-project-brief">']
    i = 0
    while i < len(lines):
        line = lines[i]
        if not line.strip():
            i += 1
            continue
        if line.startswith("```"):
            i += 1
            code = []
            while i < len(lines) and not lines[i].startswith("```"):
                code.append(lines[i])
                i += 1
            i += 1
            out.append("<pre><code>" + html.escape("\n".join(code), quote=False) + "</code></pre>")
            continue
        heading = re.match(r"^(#{1,6})\s+(.*)$", line)
        if heading:
            level = 2 if len(heading.group(1)) == 1 else min(6, len(heading.group(1)) + 1)
            out.append(f"<h{level}>{inline(heading.group(2))}</h{level}>")
            i += 1
            continue
        if line.startswith("|") and i + 1 < len(lines) and re.match(r"^\|(?:\s*:?-+:?\s*\|)+$", lines[i + 1]):
            rows = [[cell.strip() for cell in line.strip().strip("|").split("|")]]
            i += 2
            while i < len(lines) and lines[i].startswith("|"):
                rows.append([cell.strip() for cell in lines[i].strip().strip("|").split("|")])
                i += 1
            out.append('<table class="generaltable"><thead><tr>' + "".join(f"<th>{inline(cell)}</th>" for cell in rows[0]) + "</tr></thead><tbody>")
            for row in rows[1:]:
                out.append("<tr>" + "".join(f"<td>{inline(cell)}</td>" for cell in row) + "</tr>")
            out.append("</tbody></table>")
            continue
        if re.match(r"^\d+\.\s+", line):
            out.append("<ol>")
            while i < len(lines) and re.match(r"^\d+\.\s+", lines[i]):
                out.append("<li>" + inline(re.sub(r"^\d+\.\s+", "", lines[i])) + "</li>")
                i += 1
            out.append("</ol>")
            continue
        paragraph = [line.strip()]
        i += 1
        while i < len(lines) and lines[i].strip():
            next_line = lines[i]
            if next_line.startswith(("#", "```", "|")) or re.match(r"^\d+\.\s+", next_line):
                break
            paragraph.append(next_line.strip())
            i += 1
        out.append("<p>" + inline(" ".join(paragraph)) + "</p>")
    out.append('<p style="display:none">PYAI-V25-PROJECT24-LEARNER-BRIEF</p>')
    out.append('<p style="display:none">PYAI-V24-PROJECT24-LIBRARY</p>')
    out.append("</div>")
    return "\n".join(out)


EN_README.write_text(EN.strip() + "\n", encoding="utf-8")
JA_README.write_text(JA.strip() + "\n", encoding="utf-8")

(BASE / "project-2-4-library-manager-spec.md").write_text(
    "Canonical English specification. Filenames, signatures, data rules, and checker cases are normative.\n\n" + EN.strip() + "\n",
    encoding="utf-8",
)
(BASE / "project-2-4-library-manager-spec-ja.md").write_text(
    "# 日本語版の公開仕様\n\n英語版を正とし、ファイル名、関数シグネチャ、データ規則、確認項目を同一にします。\n\n" + JA.strip() + "\n",
    encoding="utf-8",
)

text = UPDATER.read_text(encoding="utf-8")

# Replace the later English branch first, then the earlier Japanese branch.
# This keeps each PHP branch boundary independent of the newly inserted HTML.
en_branch = text.index("\n} else {")
en_start = text.index("    $body =", en_branch)
en_end = text.index("\n}\n\n$subsection", en_start)
en_declaration = "    $body = <<<'HTML'\n" + markdown_to_html(EN) + "\nHTML;"
text = text[:en_start] + en_declaration + text[en_end:]

ja_branch = text.index("if ($ja) {")
ja_start = text.index("    $body =", ja_branch)
ja_end = text.index("\n} else {", ja_start)
ja_declaration = "    $body = <<<'HTML'\n" + markdown_to_html(JA) + "\nHTML;"
text = text[:ja_start] + ja_declaration + text[ja_end:]
UPDATER.write_text(text, encoding="utf-8")

notebook_updates = {
    LAB / "templates" / "P2_csv_library_manager.ipynb": {
        0: "# Project 2.4 — CSV library record manager\n\nYou do not start from an empty file. In the file browser, open `projects/library-manager/library_manager.py` and complete its ten unfinished functions. Edit only that supplied starter. This Notebook guides the work and is not the submission.\n",
        1: "## See the whole job first\n\n1. Load the supplied CSV (this sample has four books).\n2. Apply four fixed updates in order.\n3. Count the updated records.\n4. Save a separate output CSV.\n5. Display the summary.\n\nThe next cell displays the actual source CSV. It is reference output, not data to copy into your program.\n",
        3: "## Read the contract before implementation\n\nThe exact signatures, returns, mutations, and exceptions for all ten functions are in `projects/library-manager/README.md`. The starter separately contains a completed `main()` that calls `run_project()` with the default paths and prints its returned summary; do not change `main()`.\n\nThe source CSV must remain unchanged. The starter builds paths from the script location, so do not change the constant names or defaults. Implement in the staged order shown in the README, then delete `print(\"PROGRAM INCOMPLETE\")`.\n",
        7: "## Before submission\n\nConfirm `ALL TESTS PASSED`, the four output records and their order, and that `data/books.csv` is unchanged. Submit only `library_manager.py` to Moodle.\n",
    },
    LAB / "templates" / "ja" / "P2_csv_library_manager.ipynb": {
        0: "# 2.4 実践プロジェクト — CSV図書記録管理\n\nプログラムを一から作る必要はありません。左のファイル一覧から`projects/library-manager/library_manager.py`を開き、用意されたスターターコードの未完成の10関数を実装します。編集するのはこのファイルだけです。このNotebookは作業案内であり、提出物ではありません。\n",
        1: "## 最初に仕事の全体像を確認する\n\n1. 配布CSVを読み込む（今回のサンプルは4冊）。\n2. 固定された4件の更新を順番に適用する。\n3. 更新後の件数を集計する。\n4. 結果を別のCSVへ保存する。\n5. 集計結果を画面に表示する。\n\n次のセルには原本CSVの実物が表示されます。内容確認用であり、プログラムへ書き写すデータではありません。\n",
        3: "## 実装前に契約を確認する\n\n10関数の正確な引数、戻り値、状態変化、例外は`projects/library-manager/README.md`にあります。スターターには別に完成済みの`main()`があり、既定パスで`run_project()`を呼び出して返された集計を表示します。`main()`は変更しません。\n\n原本CSVは変更してはいけません。パスはスクリプト自身の場所を基準に作るコードが用意されているので、定数名と既定パスも変更しません。READMEの段階的な順序で実装し、最後に`print(\"PROGRAM INCOMPLETE\")`を削除します。\n",
        7: "## 提出前チェック\n\n`ALL TESTS PASSED`、出力CSVの4件と順序、`data/books.csv`が変更されていないことを確認します。Moodleへ提出するのは`library_manager.py`だけです。\n",
    },
}

for path, updates in notebook_updates.items():
    notebook = json.loads(path.read_text(encoding="utf-8"))
    for index, source in updates.items():
        notebook["cells"][index]["source"] = source.splitlines(keepends=True)
    path.write_text(json.dumps(notebook, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")

vtext = VERIFIER.read_text(encoding="utf-8")
anchor = "    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException(\"$shortname LTI path\");\n"
check = """    foreach (['PYAI-V25-PROJECT24-LEARNER-BRIEF', 'id,title,read', 'B004,\"Writing, Presenting, and Learning\",true', $ja ? 'この課題で行うこと' : 'What you will do', $ja ? '完成済みの' : 'completed', $ja ? '編集または上書きしてはいけません' : 'Do not edit or overwrite it'] as $briefToken) {
        if (!str_contains($page->content, $briefToken) || !str_contains($assign->intro, $briefToken)) throw new RuntimeException("$shortname missing learner-brief token $briefToken");
    }
"""
if check not in vtext:
    if anchor not in vtext:
        raise SystemExit("Moodle verifier anchor missing")
    VERIFIER.write_text(vtext.replace(anchor, check + anchor, 1), encoding="utf-8")

print("Project 2.4 learner brief v25 finalized")
