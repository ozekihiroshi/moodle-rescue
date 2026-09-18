## 導入

**レコードの集まりを、確認可能な表として扱う**

2.3では一件のセンターを辞書、複数件をリストとして処理しました。件数と項目が増えると、すべてのレコードで同じ列をそろえ、列単位で確認・計算できる表形式が有効になります。pandasはこの表を`DataFrame`として扱います。

## このレッスンの到達目標

このレッスンを終えると、次のことができます。

- 表形式データの一行・一列・セルが何を表すか説明できる。
- CSVの読込条件と実際に読み込んだファイルを確認できる。
- DataFrameの形、列名、型、欠損、カテゴリ値を調べられる。
- 計算列を追加し、indexを混入させずCSVへ保存できる。

**学習経路:** 必須：3.1.1〜3.1.4　／　補足：3.1.5　／　統合練習：3.1.6

## 3.1.1 行・列・セルとスキーマを理解する

### 一行を一観測、一列を一変数としてそろえる

この教材では一行を一つのセンター・月、一列を同じ意味の変数、一つのセルを一観測の一変数の値とします。列名だけでなく、各列の期待型、単位、欠損規則までをスキーマとして確認します。同じ列へ人数と文字を混ぜると、後の計算が不安定になります。

**確認:** この表で一行が何を表すか、識別子と測定値を分けて説明してください。

## 3.1.2 CSVと読込条件を理解する

CSVでは通常、一行目がヘッダー、後続行がレコード、カンマがフィールドの区切りです。値にカンマや改行を含む場合は引用符が必要です。CSV自体はセルの色、数式、複数シートを保持しません。文字コード、区切り文字、空欄、識別子の先頭0をどのように読むかを決める必要があります。

    month,centre_id,centre_name,registered,completed
    2026-01,C001,Gaborone Learning Centre,32,24
    2026-01,C002,"North, Main Centre",27,18

### 相対パスと実際に読み込むファイル

`data/learning-centres-practice.csv`はNotebookファイルの位置ではなく、カーネルの現在の作業フォルダを基準にします。そのためPython Labを別の入口から開くと、同じ相対パスでも見つからないことがあります。教材Notebookは現在位置と親フォルダ、サーバー上の学習領域、配布元を調べ、実際に読み込んだ絶対パスを表示します。`FileNotFoundError`では、まず現在位置と確認済みパスを読みます。

    from pathlib import Path
    print("Working directory:", Path.cwd())
    data_file = find_course_data("learning-centres-practice.csv")
    print("Loading:", data_file.resolve())

### read_csvの前提をコードへ明示する

`import pandas as pd`は一般的な読み込み方です。`pd.read_csv()`はCSVからDataFrameを作ります。ここではUTF-8を指定し、`centre_id`と`month`を計算対象ではない文字列として保持します。別のファイルでは`sep`や`encoding`が異なる可能性があります。

    df = pd.read_csv(
        data_file,
        encoding="utf-8",
        dtype={"centre_id": "string", "month": "string"},
    )

## 3.1.3 DataFrameを読み込み、内容を確認する

`head()`で値の並び、`shape`で行数と列数、`columns`で正確な列名、`dtypes`と`info()`で推定型、`isna().sum()`で欠損数を確認します。練習CSVは24行10列で、品質問題を意図的に含みます。読み込めたことと、正しく読めたことは同じではありません。

    print(df.head(3))
    print("Shape:", df.shape)
    print(df.columns.tolist())
    print(df.dtypes)
    print(df.isna().sum())
    df.info()

### 全件表示とカテゴリ値の件数を確認する

`head()`は先頭確認に向きますが、37件程度の原資料を人が全件確認するときは`to_string(index=False)`で中間行の省略を防げます。カテゴリ列は、補正する前に原資料の値と件数を確認します。`value_counts(dropna=False, sort=False)`は欠損も含め、値が最初に現れた順で数えます。2列の表が必要ならSeriesの名前を列へ戻します。

    print(df.to_string(index=False, line_width=200))

    district_counts = (
        df["district"]
        .value_counts(dropna=False, sort=False)
        .rename_axis("district")
        .reset_index(name="records")
    )
    print(district_counts.to_string(index=False, formatters={"district": repr}))

### 一列はSeries、複数列の表はDataFrame

`df[\"registered\"]`は一次元のSeries、`df[[\"registered\"]]`は一列を持つ二次元のDataFrameです。列名は完全一致で指定します。Seriesどうしの演算は、対応する行へまとめて適用されます。

**確認:** 『CSVを読み込めた』だけでは不十分です。次に確認する四つの情報を挙げてください。

## 3.1.4 計算列を作り、表を保存する

`assign()`を使うと、元の`df`を直接変更せず、計算列を持つ新しいDataFrameを作れます。分母0、欠損、不正値をどう扱うか決めないまま最終結果として解釈してはいけません。これらの品質判断は3.3で詳しく扱います。

    report = df.assign(
        completion_rate=df["completed"] / df["registered"] * 100
    )
    print(report[["centre_name", "completion_rate"]].head())

### indexと業務上の識別子を区別する

DataFrame左端のindexはpandasが行へ付けるラベルで、`centre_id`ではありません。CSVへ保存するときに`index=False`を指定すると、業務データに不要なindex列を書き出しません。保存後はパスとヘッダーを確認します。

## 3.1.5 読込問題を原因別に切り分ける

`FileNotFoundError`では作業フォルダと探索先、列が一列だけなら区切り文字、文字化けや`UnicodeDecodeError`なら文字コード、数値列が文字列なら空白・単位記号・不正値を確認します。推測で値を直す前に、入力と読込条件を記録します。

## 統合練習：章末課題へつながる軽い予行演習

**章末課題との接続：**

4行の地域講習会表を使い、中間課題の最初の段階である『加工前の確認』を練習します。

### 作るもの

CSV文字列をDataFrameへ読み、形、列名、型、全行、`workshop_id,date`順の表示用表を出します。元の順序は変えません。

### 完成条件

- 一行は一つの講習会の一日分です。

- 原表と並べ替え表はどちらも4行です。

- 元のindex順は`[0, 1, 2, 3]`のままです。

### 解答への手引き

1.  外部ファイルを不要にするため`io.StringIO`を使います。

2.  表示用表は`sort_values`と`reset_index(drop=True)`で作ります。

3.  並べ替え結果を`records`へ代入しません。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> **模範解答と解説**
>
> ```
> from io import StringIO
> import pandas as pd
>
> csv_text = """workshop_id,date,district,registered,attended
> W02,2026-09-02,east,18,15
> W01,2026-09-02,North,20,18
> W02,2026-09-01, east ,16,14
> W01,2026-09-01,North,19,17
> """
> records = pd.read_csv(StringIO(csv_text))
> print("SHAPE:", records.shape)
> print("COLUMNS:", records.columns.tolist())
> print(records.dtypes)
> print(records.to_string(index=False))
> view = records.sort_values(["workshop_id", "date"]).reset_index(drop=True)
> print(view.to_string(index=False))
> assert records.index.tolist() == [0, 1, 2, 3]
> ```
>
> **代表的な確認結果**
>
>     SHAPE: (4, 5)
>
> 確認により、分析前に一行の意味、列、型、順序を把握します。派生表示のために原表を変更する必要はありません。

### 値を変えて再確認する

CSVの行順を逆にします。原表の表示は変わりますが、並べ替え表は同じ講習会・日付順になることを確認します。

## まとめ

このレッスンと統合練習を終えると、次のことができます。

- 一行の観測単位と列の意味を決めてから表を扱いました。
- CSVの場所と読込条件を確認し、DataFrame全体を観察しました。
- 計算結果を別の表として作り、保存後の形と列を確かめました。

## 次のレッスンへ

確認できる表ができました。3.2では、分析の問いを列と行条件へ分け、必要なレコードだけを再現可能な方法で選びます。

**学習時間の目安:** 約4時間
