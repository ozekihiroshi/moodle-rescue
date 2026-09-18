## 導入

**プログラムの外にある記録を、安全に受け渡す**

これまで扱った値は、プログラムが終了すると失われました。実務では、前回の記録を読み、処理結果を次回も使える形で保存する必要があります。ここでは、小さな図書一覧をCSVから読み、Pythonのレコードへ変換し、変更後の一覧を別のCSVへ保存します。

## このレッスンの到達目標

このレッスンを終えると、次のことができます。

- 現在位置を仮定せず、定義した基準位置からパスを作れる。
- モードとUTF-8を明示し、安全にテキストファイルを開閉できる。
- DictReaderでCSVを読み、宣言したスキーマに従って値を変換できる。
- 使用前にヘッダーと各行を検証できる。
- DictWriterで別のCSVへ保存し、再読込した成果物を確認できる。

**学習経路:** 必須：2.3.1～2.3.5　｜　統合練習：2.3.6

## 2.3.1 読み込むファイルの場所を確定する

### 読む前に、ファイルの場所を確定する

相対パスは現在の作業フォルダを基準にします。Notebookでは`Path.cwd()`を表示し、候補を`resolve()`して確認します。独立したスクリプトでは`Path(__file__).resolve().parent`を基準にすると、起動位置が変わっても同じ付属ファイルを参照できます。

    from pathlib import Path

    BASE_DIR = Path(__file__).resolve().parent
    INPUT_PATH = BASE_DIR / "data" / "books.csv"
    OUTPUT_PATH = BASE_DIR / "output" / "books_updated.csv"

`FileNotFoundError`が出たら、ファイル名だけでなく、解決した絶対パスと`exists()`の結果を表示します。Notebookでは`__file__`が通常ないため、教材Notebookには現在位置から親フォルダを探す補助関数を用意しています。

**確認:** この項目の中心となる値・処理・結果を、自分の言葉で区別して説明できますか。

## 2.3.2 モードと文字コードを明示してファイルを開く

### with、モード、文字コード

`with`で開くと、正常終了でも例外でもファイルが閉じられます。`r`は読込、`w`は新規作成または上書き、`a`は末尾追加です。文字コードは`encoding="utf-8"`、CSVでは`newline=""`を明示します。入力元を`w`で開くと内容を失うため、入力と出力のパスを分けます。

## 2.3.3 構造を壊さずにCSVレコードを読む

### CSVはコンマでsplitしない

CSVでは、コンマを含む一つの値を引用符で囲めます。`Data, Decisions, and Evidence`という書名は一項目です。標準`csv`モジュールは引用符、区切り、改行の規則を扱います。

    import csv

    with INPUT_PATH.open("r", encoding="utf-8", newline="") as file:
        reader = csv.DictReader(file)
        print(reader.fieldnames)
        for row in reader:
            print(row)

### DictReaderの値は最初は文字列

見出しは辞書のキーになりますが、CSVの`false`はPythonの`False`へ自動変換されません。さらに`bool("false")`は空でない文字列なので`True`です。受け入れる表記を決めた関数で明示的に変換します。

    def parse_read(value):
        text = value.strip().lower()
        if text == "true":
            return True
        if text == "false":
            return False
        raise ValueError(f"read must be true or false: {value!r}")

## 2.3.4 ヘッダー・各行・変換した値を検証する

### 使う前にヘッダーと各行を検証する

必要列`id`、`title`、`read`の不足、空のIDや書名、重複ID、不正な真偽値を入力境界で拒否します。黙って0や空文字へ置き換えると、後の処理は動いても結果が誤ります。行番号を例外メッセージに含めると、元データを修正できます。

## 2.3.5 別ファイルへ保存し、再読込で確認する

### 別のCSVへ書き、もう一度読む

`csv.DictWriter`へ列順を指定し、`writeheader()`の後に各レコードを書きます。真偽値は小文字の`true`または`false`へ戻します。出力フォルダは必要な時だけ作り、教材の入力CSVは変更しません。

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    with OUTPUT_PATH.open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=["id", "title", "read"])
        writer.writeheader()
        for book in books:
            writer.writerow({"id": book["id"], "title": book["title"], "read": "true" if book["read"] else "false"})

保存処理が例外なく終わっただけでは十分ではありません。同じ読込関数で出力CSVを再読込し、レコード数、ID順、書名、真偽値を期待値と比較します。さらに、処理前後の入力ファイルのバイト列が同じであることを確認します。

**確認:** 境界や例外的な入力を一つ選び、期待する結果を説明できますか。

## 統合練習：章末課題へつながる軽い予行演習

**章末課題との接続：**

本ではなく部屋予約を使い、プロジェクトの読込・変換・保存を予行演習します。

### 作るもの

小さな`bookings.csv`を作り、`csv.DictReader`で読み、`people`を`int`へ変換します。R02へ2人追加し、別の`bookings_updated.csv`へ`csv.DictWriter`で保存してください。

### 完成条件

- 入力ファイルは変更しません。

- 見出しは`id,room,people`です。

- 出力を再読込し、R02が14人であることを`assert`します。

### 解答への手引き

1.  練習コード内で小さな入力を作ります。

2.  CSVの文字列を算術前に変換します。

3.  `newline=''`を指定し、見出しは一度だけ書きます。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> **模範解答と解説**
>
> ```
> import csv
> from pathlib import Path
>
> source = Path("bookings.csv")
> output = Path("bookings_updated.csv")
> source.write_text("id,room,people\nR01,Hall A,8\nR02,Room B,12\n", encoding="utf-8")
> with source.open(encoding="utf-8", newline="") as file:
>     rows = list(csv.DictReader(file))
> for row in rows:
>     row["people"] = int(row["people"])
>     if row["id"] == "R02":
>         row["people"] += 2
> with output.open("w", encoding="utf-8", newline="") as file:
>     writer = csv.DictWriter(file, fieldnames=["id", "room", "people"])
>     writer.writeheader()
>     writer.writerows(rows)
> with output.open(encoding="utf-8", newline="") as file:
>     saved = list(csv.DictReader(file))
> assert saved[1]["people"] == "14"
> print("ROUND TRIP OK")
> ```
>
> **代表的な確認結果**
>
>     ROUND TRIP OK
>
> 読込、型付き変換、別名保存、再読込が、信頼できるファイル処理の一巡です。

### 値を変えて再確認する

カンマを含む部屋名を追加し、手作業の分割なしで`csv`モジュールが保持することを確認します。

## まとめ

このレッスンと統合練習を終えると、次のことができます。

- パス解決、ファイルを開く処理、CSV解析、型変換、検証を分けました。
- カンマを含む引用符付きフィールドを壊さずに読みました。
- 原本を保護し、保存後の再読込まで含む往復処理を検証しました。

## 次のレッスンへ

2.4では、レコード構造、テスト済み関数、入力検証、CSV入出力を組み合わせ、図書台帳を更新するプログラムを作ります。

**学習時間の目安:** 約3時間
