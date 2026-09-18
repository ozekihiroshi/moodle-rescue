## 導入

**集計する前に、一行の意味を決める**

3.3では品質規則を満たす分析用データを作りました。次は24件の明細を、会議や報告で読める少数の行へまとめます。しかし、集計は情報を圧縮するため、何を一行とするかを先に定義しなければなりません。元表の一行はセンター・月・コース、地区別集計の一行は一地区、地区・コース別集計の一行はその組合せです。この単位を粒度と呼びます。

## このレッスンの到達目標

このレッスンを終えると、次のことができます。

- 明細と集計結果について、一行が表す粒度を定義できる。
- groupbyと名前付きaggで、件数・合計・統計量・条件付き件数を作れる。
- 率の分子と分母、構成比の合計、順位の比較順を説明できる。
- 明細との照合、CSV保存、再読込により集計結果を検証できる。

**学習経路:** 必須：3.4.1〜3.4.5　／　統合練習：3.4.6

## 3.4.1 明細と集計結果の粒度を決める

**確認:** 元明細と地区別集計では、一行が表すものはどのように変わりますか。

## 3.4.2 groupbyで分け、数え、まとめる

`groupby("district")`は同じ地区の行を分け、各地区へ同じ集計を適用し、結果を結合します。名前付き`agg()`なら出力列の意味がコードに残ります。`reset_index()`はグループキーを通常の列へ戻します。

    district = analysis.groupby("district", dropna=False).agg(
        centre_months=("centre_id", "size"),
        centres=("centre_id", "nunique"),
        registered_total=("registered", "sum"),
    ).reset_index()

### size・count・nuniqueの対象を区別する

`size`は欠損を含む行数、`count`は指定列の欠損でない値の数、`nunique`は異なる値の数です。センター月数、出席報告済み件数、異なるセンター数は同じ「件数」ではありません。列名も質問に合う具体名にします。

### 条件付き件数を名前付き集計へ入れる

まず明細へ条件のブール列を作り、グループ内でTrueを合計します。判定式と件数を別々に確認できます。

    operational = analysis.assign(low_completion=analysis['completion_rate'] < 75)
    summary = operational.groupby('course').agg(low_records=('low_completion','sum'))

## 3.4.3 合計・統計量・率を計算する

`sum`は総量、`mean`は算術平均、`median`は並べた中央、`min`と`max`は範囲の端、`std`は平均からの散らばりです。極端な値は平均を動かすため、件数、中央値、最小・最大と一緒に読みます。標準偏差はばらつきの大きさを示しますが、原因を説明するものではありません。

    analysis.groupby("course")["registered"].agg(["size", "sum", "mean", "median", "min", "max", "std"])

### 率は対応する分子と分母の合計から求める

コース全体の修了率は、コースの修了者合計を登録者合計で割ります。各センター月の修了率を単純平均すると、小規模な行と大規模な行へ同じ重みを与えます。これは「典型的なセンター月」を尋ねる値であり、「全登録者のうち何人が修了したか」とは違います。問いに合う分母を文章でも明記します。

    course = analysis.groupby("course").agg(
        records=("centre_id", "size"),
        registered=("registered", "sum"),
        completed=("completed", "sum"),
        mean_row_rate=("completion_rate", "mean"),
        material_cost=("material_cost", "sum"),
    )
    course["overall_completion_rate"] = course["completed"] / course["registered"] * 100
    course["cost_per_completion"] = course["material_cost"] / course["completed"]

練習データのPython Foundationsでは、行別修了率の単純平均は約71.9%、合計修了者290人を合計登録者400人で割った全体修了率は72.5%です。似た値でも定義が違うため、名前と分母を省略してはいけません。

### 複数キーで比較の階層を保つ

`groupby(["district", "course"])`は一地区・一コースを一行にします。地区だけの表とは粒度が違います。粒度の異なる表を、そのまま足したり平均したりしてはいけません。必要なら`sort_values()`で表示順も明示します。

## 3.4.4 判断に使う指標と順位を作る

### 複数条件で再現可能な順位を作る

主要指標を降順、次の指標も降順、最後のIDを昇順にします。最後の安定したキーが同順位を再現可能にし、丸める前の値で順位を決めます。

    ranked = summary.sort_values(['average','days','id'], ascending=[False,False,True])

### 構成比の分母と100%を確認する

地区内のコース別登録者構成比なら、各地区・コースの登録者数をその地区の登録者合計で割ります。全体合計を使えば別の問いです。`transform("sum")`で各行へ地区合計を対応させ、地区ごとの構成比合計が100%になることを確認します。

    dc["district_total"] = dc.groupby("district")["registered"].transform("sum")
    dc["share"] = dc["registered"] / dc["district_total"] * 100
    print(dc.groupby("district")["share"].sum())

**確認:** 順位を丸める前の値で決め、最後に安定したIDを使う理由を説明してください。

## 3.4.5 集計結果を照合し、保存後に再確認する

グループ別の記録数、登録者数、修了者数、教材費を再合計すると、分析用明細の全体と一致するはずです。`assert`で照合すれば、欠落や二重集計をデータ更新時に検出できます。平均の比較には必ず件数を添え、少数グループの差を原因や優劣の証明として扱いません。

    assert int(course["records"].sum()) == len(analysis)
    assert course["registered"].sum() == analysis["registered"].sum()
    assert course["completed"].sum() == analysis["completed"].sum()

### 用途別CSVを保存し、再読込して検証する

確認対象表と順位表を別々に保存し、再読込した列順、件数、先頭の優先対象を照合します。保存前の変数だけで成果物を検証したことにはなりません。

## 統合練習：章末課題へつながる軽い予行演習

**章末課題との接続：**

自転車修理を題材に、グループ化、導出指標、決定的な順位、照合を練習します。

### 作るもの

有効な6件を整備担当者別にまとめ、件数、合計分、平均分を求めます。平均の降順、同率は担当者ID昇順で並べてください。

### 完成条件

- 名前付き集計を使います。

- 順位は丸め前の平均で決め、表示時だけ丸めます。

- 件数と合計分を原明細と照合します。

### 解答への手引き

1.  `groupby('mechanic_id', as_index=False).agg(...)`を使います。

2.  平均降順、ID昇順で並べます。

3.  集計表の合計と原表の合計を比較します。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> **模範解答と解説**
>
> ```
> import pandas as pd
> jobs = pd.DataFrame({
>     "job_id": ["J1", "J2", "J3", "J4", "J5", "J6"],
>     "mechanic_id": ["M01", "M02", "M01", "M03", "M02", "M03"],
>     "minutes": [30, 45, 50, 20, 35, 40],
> })
> summary = jobs.groupby("mechanic_id", as_index=False).agg(
>     job_count=("job_id", "size"),
>     total_minutes=("minutes", "sum"),
>     mean_minutes=("minutes", "mean"),
> )
> summary = summary.sort_values(["mean_minutes", "mechanic_id"], ascending=[False, True])
> assert summary["job_count"].sum() == len(jobs)
> assert summary["total_minutes"].sum() == jobs["minutes"].sum()
> print(summary.round({"mean_minutes": 1}).to_string(index=False))
> ```
>
> **代表的な確認結果**
>
>     FIRST: M01
>
> 一行の意味が一修理から一担当者へ変わります。照合により、集計が原明細を説明できることを確かめます。

### 値を変えて再確認する

M02へ60分の修理を一件追加します。実行前に新しい1位を予想します。

## まとめ

このレッスンと統合練習を終えると、次のことができます。

- 先に結果表の粒度を決めてからgroupbyを行いました。
- 件数、統計量、率、構成比、順位を、それぞれの定義と分母に結び付けました。
- 明細との合計照合と再読込により、保存された成果物まで検証しました。

## 3.5A 中間実践課題へ

3.5Aでは、原資料を確認する小プログラムと、品質判定・集計・順位付けを行う本番プログラムを完成させます。3.1〜3.4の処理を初めて一つの意思決定へ接続します。

**学習時間の目安:** 約3時間
