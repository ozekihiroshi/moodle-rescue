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

## 統合練習

48行fixtureを`chunksize=7`と`chunksize=13`で処理し、原本件数、分析件数、要確認件数、地区・医薬品別集計が一致することを確認してください。

## まとめ

- 全件の代わりに、最終指標へ必要な小さな状態を保持する
- 同じグループの部分集計をチャンク間で加算する
- 平均や率は分子と分母を統合してから計算する
- チャンクサイズを変えて結果が不変であることを試す

## 次のレッスンへ

同じ値が出ただけでは、処理の完全性はまだ説明できません。次は、小さなfixture、件数照合、来歴を組み合わせ、再現可能な実行へ仕上げます。
