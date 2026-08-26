# 日本語版の公開仕様

英語版を正とし、ファイル名、関数シグネチャ、データ規則、確認項目を同一にします。

# 2.4 実践プロジェクト — CSV図書記録管理

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
