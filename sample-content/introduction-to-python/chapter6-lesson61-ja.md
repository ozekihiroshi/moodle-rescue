# レッスン6.1：読み込む前に調べる

第5章では、集計表から判断に使える図と根拠文を作りました。しかし、原本が大きくなると、最初に全件をDataFrameへ読み込む方法そのものを見直す必要があります。このレッスンでは、ファイルを開く前の確認から、必要な列と型を選んだ読み込みまでを扱います。

## このレッスンを終えると、次のことができます

- ファイルの大きさと一行の意味を読み込み前に確認できる
- 少数行から列名と値の形を調べられる
- `usecols`と`dtype`で読み込み範囲を限定できる
- DataFrameのメモリ使用量を比較できる

## 6.1.1 ファイルと分析の境界を確認する

「大きいデータ」は固定された件数ではありません。同じCSVでも、利用できるメモリ、列数、文字列の長さによって扱いやすさが変わります。まずファイルのバイト数、列名、少数行を確認し、一行が何を表すかを説明できる状態にします。

```python
from pathlib import Path
import pandas as pd

source = Path("data/clinic-stock-fixture.csv")
print("Bytes:", source.stat().st_size)
sample = pd.read_csv(source, nrows=5)
print(sample.columns.tolist())
print(sample)
```

`nrows=5`は全件分析ではなく、読み込み計画を立てるための標本です。標本だけで欠損件数や最終順位を決めてはいけません。

## 6.1.2 必要な列だけを選ぶ

問いに不要な列まで読み込むと、メモリを使うだけでなく、処理の境界も曖昧になります。例えば地区・医薬品別の在庫切れ負担を調べるなら、個人を識別する列は必要ありません。

```python
needed = [
    "district", "medicine", "stockout_hours", "patients_turned_away"
]
records = pd.read_csv(source, usecols=needed)
```

`usecols`は高速化の小技ではなく、「この判断に必要なデータは何か」をコードで示す契約です。

## 6.1.3 型を推測任せにしない

CSVには型情報が保存されていません。pandasは値から型を推測しますが、大きなファイルでは途中の不正値によって推測結果が変わる場合があります。識別子は文字列、集計量は数値というように、用途から型を決めます。

```python
typed = pd.read_csv(
    source,
    usecols=needed,
    dtype={"district": "string", "medicine": "string"},
)
typed["stockout_hours"] = pd.to_numeric(typed["stockout_hours"], errors="coerce")
```

変換できない値を`NaN`にすることで、黙って文字列のまま集計するのではなく、後で要確認として数えられます。

## 6.1.4 メモリ使用量を比較する

```python
all_columns = pd.read_csv(source)
selected = pd.read_csv(source, usecols=needed)

print(all_columns.memory_usage(deep=True).sum())
print(selected.memory_usage(deep=True).sum())
```

小さなfixtureでは差が小さくても、同じ選択を12万件、100万件へ拡大すると効果が現れます。推測だけでなく、実測値を比較します。

## 6.1.5 全件を保持しない読み込み

```python
for chunk in pd.read_csv(source, usecols=needed, chunksize=10_000):
    print(len(chunk))
```

`chunksize`を指定すると、結果は一つのDataFrameではなく、DataFrameを順に返す反復可能なオブジェクトになります。各チャンクで必要な処理を行い、最終判断に必要な小さな状態だけを残します。

## 統合練習

fixtureについて、全列読み込みと必要4列読み込みのメモリ使用量を比較してください。その後、`chunksize=10`で各チャンクの行数を表示し、合計が原本行数と一致することを確認します。

## まとめ

このレッスンでは、次を確認しました。

- 大規模処理は、ファイルの大きさ・列・一行の意味の確認から始める
- `nrows`は読み込み計画用の標本であり、全件の結論ではない
- `usecols`と明示的な型で処理範囲を限定する
- チャンク読み込みでは、全件ではなく必要な状態だけを保持する

## 次のレッスンへ

読み込みを分割しただけでは、正しい全体集計にはなりません。次は、複数チャンクの合計と件数を一つの結果へ安全に統合します。
