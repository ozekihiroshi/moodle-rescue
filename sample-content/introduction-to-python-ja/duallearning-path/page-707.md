# レッスン6.2：チャンクを越えて正しく集計する

前のレッスンでは、必要な列を小さなチャンクとして読み込みました。今回は、チャンクごとの処理を全体の正しい集計へ統合します。中心となる考え方は、チャンクを読み終えた後にも必要な「状態」だけを残すことです。

## このレッスンを終えると、次のことができます

- チャンクを越えて合計・件数を保持できる
- グループが複数チャンクへ分かれても正しく統合できる
- 平均の平均が誤る理由を説明できる
- チャンクサイズを変えて結果の不変性を確認できる

## 6.2.1 チャンク処理に必要な状態を決める

全件を残さなくても、最終的な指標を計算するための合計と件数は残す必要があります。地区・医薬品別の不足を求めるなら、辞書のキーを`(district, medicine)`とし、値に必要な合計を保持できます。

```python
totals = {}
key = ("East", "Insulin")
totals.setdefault(key, {"clinic_days": 0, "stockout_hours": 0, "patients_turned_away": 0})
```

この辞書は原明細よりはるかに小さく、ファイルが増えても地区と医薬品の組合せ数に応じて増えるだけです。

## 6.2.2 部分集計を全体へ加える

```python
part = chunk.groupby(["district", "medicine"], as_index=False).agg(
    clinic_days=("date", "size"),
    stockout_hours=("stockout_hours", "sum"),
    patients_turned_away=("patients_turned_away", "sum"),
)

for row in part.itertuples(index=False):
    key = (row.district, row.medicine)
    current = totals.setdefault(key, {
        "clinic_days": 0,
        "stockout_hours": 0,
        "patients_turned_away": 0,
    })
    current["clinic_days"] += row.clinic_days
    current["stockout_hours"] += row.stockout_hours
    current["patients_turned_away"] += row.patients_turned_away
```

同じキーが次のチャンクにも現れるため、辞書の値を置き換えず加算します。

## 6.2.3 平均ではなく分子と分母を統合する

二つのチャンクの平均を単純に平均すると、件数の違いが失われます。

```python
# 誤りになり得る
overall = (chunk1_rate + chunk2_rate) / 2

# 正しい考え方
overall = (chunk1_events + chunk2_events) / (chunk1_records + chunk2_records) * 100
```

率や平均は最後に計算します。チャンク間で統合するのは、足し算可能な分子・分母・合計・件数です。

## 6.2.4 有効行と要確認行を同時に数える

```python
source_records += len(chunk)
valid, review = prepare_chunk(chunk)
analysis_records += len(valid)
review_records += len(review)
```

処理の途中で不正行を単に削除すると、何件を捨てたか分からなくなります。各チャンクで振り分け、最後に全件が説明できる状態を作ります。

## 6.2.5 チャンクサイズに依存しないことを確認する

```python
small = process_file(source, chunksize=997)
large = process_file(source, chunksize=2048)

pd.testing.assert_frame_equal(small["summary"], large["summary"])
```

チャンク境界は計算機上の都合です。境界が変わると結果も変わるなら、状態の統合方法に問題があります。

## 統合練習：章末課題へつながる軽い予行演習

> **章末課題との接続：**倉庫からの発送を題材に、チャンク境界を越えて加算可能な状態を保持します。

### 作るもの

12行CSVを4行ずつ処理し、配送先別の発送件数と荷物合計を累積します。全チャンク後に一発送当たり荷物数を求めてください。

### 完成条件

- チャンク間ではグループ別合計だけを保持します。
- チャンク平均を平均しません。
- 原本行数と累積発送件数を照合します。

### 解答への手引き

1. 各チャンクを配送先別に集計します。
2. 部分件数と荷物合計を辞書へ加算します。
3. 最後に荷物合計÷発送件数を計算します。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> 模範解答と解説
>
> ```python
> import pandas as pd
> pd.DataFrame({
>     "dispatch_id": [f"D{i:02d}" for i in range(1, 13)],
>     "destination": ["North", "East", "South"] * 4,
>     "packages": [8, 5, 7, 9, 6, 4, 10, 8, 5, 7, 9, 6],
> }).to_csv("warehouse_dispatches.csv", index=False)
>
> totals = {}
> source_rows = 0
> for chunk in pd.read_csv("warehouse_dispatches.csv", chunksize=4):
>     source_rows += len(chunk)
>     part = chunk.groupby("destination", as_index=False).agg(
>         dispatches=("dispatch_id", "size"), packages=("packages", "sum"))
>     for row in part.itertuples(index=False):
>         state = totals.setdefault(row.destination, {"dispatches": 0, "packages": 0})
>         state["dispatches"] += row.dispatches
>         state["packages"] += row.packages
> summary = pd.DataFrame([{"destination": key, **value} for key, value in totals.items()])
> summary["packages_per_dispatch"] = summary["packages"] / summary["dispatches"]
> assert source_rows == summary["dispatches"].sum()
> print(summary.sort_values("destination").to_string(index=False))
> ```
>
> **代表的な確認結果**
>
> ```text
> Chunk size does not change the final summary
> ```
>
> 件数と合計は加算可能です。平均は分子と分母を統合してから導出します。


### 値を変えて再確認する

チャンクサイズ3と5でも実行し、最終表と照合行数が同一になることを確認します。
## まとめ




このレッスンと統合練習を終えると、次のことができます。

- 全件の代わりに、最終指標へ必要な小さな状態を保持する
- 同じグループの部分集計をチャンク間で加算する
- 平均や率は分子と分母を統合してから計算する
- チャンクサイズを変えて結果が不変であることを試す

## 次のレッスンへ

同じ値が出ただけでは、処理の完全性はまだ説明できません。次は、小さなfixture、件数照合、来歴を組み合わせ、再現可能な実行へ仕上げます。
