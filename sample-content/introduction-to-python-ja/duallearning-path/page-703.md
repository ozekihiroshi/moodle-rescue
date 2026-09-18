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

## 統合練習：章末課題へつながる軽い予行演習

> **章末課題との接続：**章末では診療所在庫データを拡大処理します。この軽い練習では太陽光設備保守ログの選択読込を計画します。

### 作るもの

6列12行のCSVを作り、バイト数と2行を確認します。次に`site_id,date,downtime_hours`だけ読み、全列DataFrameとのdeep memoryを比較してください。

### 完成条件

- 結論は2行標本ではなく12行全件に基づきます。
- `nrows`、`usecols`、`memory_usage(deep=True)`を使います。
- 選択列が指定の3列だけか確認します。

### 解答への手引き

1. 小さなCSVはコード内で生成します。
2. 標本は列名と値の形の確認だけに使います。
3. 二つのDataFrameを同じ方法で測ります。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> 模範解答と解説
>
> ```python
> from pathlib import Path
> import pandas as pd
> source = Path("solar_maintenance.csv")
> pd.DataFrame({
>     "site_id": [f"S{i % 3 + 1}" for i in range(12)],
>     "date": [f"2026-09-{i + 1:02d}" for i in range(12)],
>     "downtime_hours": [i % 5 for i in range(12)],
>     "technician": ["T1", "T2"] * 6,
>     "weather": ["clear", "rain", "cloud"] * 4,
>     "note": ["routine check"] * 12,
> }).to_csv(source, index=False)
> print("BYTES:", source.stat().st_size)
> print(pd.read_csv(source, nrows=2))
> full = pd.read_csv(source)
> selected = pd.read_csv(source, usecols=["site_id", "date", "downtime_hours"])
> print("FULL MEMORY:", full.memory_usage(deep=True).sum())
> print("SELECTED MEMORY:", selected.memory_usage(deep=True).sum())
> assert selected.columns.tolist() == ["site_id", "date", "downtime_hours"]
> ```
>
> **代表的な確認結果**
>
> ```text
> Selected memory is lower than full memory
> ```
>
> 標本は読込計画を立て、選択読込はデータ境界を表します。どちらも必要全行の処理の代わりではありません。


### 値を変えて再確認する

長い自由記述列を追加し、どちらの読込のメモリが大きく増えるか予想して測ります。
## まとめ




このレッスンと統合練習を終えると、次のことができます。

このレッスンでは、次を確認しました。

- 大規模処理は、ファイルの大きさ・列・一行の意味の確認から始める
- `nrows`は読み込み計画用の標本であり、全件の結論ではない
- `usecols`と明示的な型で処理範囲を限定する
- チャンク読み込みでは、全件ではなく必要な状態だけを保持する

## 次のレッスンへ

読み込みを分割しただけでは、正しい全体集計にはなりません。次は、複数チャンクの合計と件数を一つの結果へ安全に統合します。
