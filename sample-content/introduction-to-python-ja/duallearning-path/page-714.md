# レッスン6.3：照合して再現可能にする

チャンク処理が最後まで動いても、正しいとは限りません。第6章の最後のレッスンでは、小さな既知データでロジックを確かめ、大規模実行の全行を照合し、別の人が同じ入力から同じ結果を作れる形へ整えます。

## このレッスンを終えると、次のことができます

- 小さなfixtureで期待結果を確認できる
- 原本・分析・要確認件数を照合できる
- 入力と処理条件の来歴を記録できる
- 保存結果を再読込して構造を検証できる

## 6.3.1 小さく確認してから拡大する

12万件の結果を目で検算することは困難です。まず48件のfixtureを表として見て、既知の一行が有効または要確認になる理由を説明します。同じ関数を大規模CSVにも使うことで、確認済みのロジックを拡大します。

```python
fixture = pd.read_csv("data/clinic-stock-fixture.csv")
print(fixture.to_string(index=False))
valid, review = prepare_chunk(fixture)
print("Valid:", len(valid), "Review:", len(review))
```

fixture専用の答えをコードへ書くのではなく、同じ列契約と品質規則を使います。

## 6.3.2 全行を照合する

```python
reconciled = source_records == analysis_records + review_records
if not reconciled:
    raise ValueError("Source records were not reconciled")
```

この式は、結果が正しいことのすべてを保証するものではありません。しかし、少なくとも行が途中で消えたり二重計上されたりしていないことを確認する基本統制になります。

## 6.3.3 処理条件と来歴を残す

再現に必要なのはコードだけではありません。少なくとも次を説明できる必要があります。

- 入力ファイル名と生成方法
- 行数と必須列
- 使用したチャンクサイズ
- 品質規則と要確認件数
- 出力ファイル名

個人情報を含まない架空データでも、どの原本から作られた結果かを曖昧にしない習慣は変わりません。実データでは、利用目的に不要な列を読み込まないことも重要です。

## 6.3.4 保存したCSVを再読込する

```python
summary.to_csv("output/clinic_stock_summary.csv", index=False)
saved = pd.read_csv("output/clinic_stock_summary.csv")

assert list(saved.columns) == list(summary.columns)
assert len(saved) == len(summary)
```

メモリ上のDataFrameが正しくても、保存時の列名、インデックス、丸めによって成果物が変わることがあります。実際に提出・共有するファイルを開いて確認します。

## 6.3.5 実行を独立した検査で確かめる

```python
first = process_file(source, chunksize=997)
second = process_file(source, chunksize=2048)

assert first["reconciled"]
assert second["reconciled"]
pd.testing.assert_frame_equal(first["summary"], second["summary"])
```

自動確認は答えを代わりに作るものではありません。自分で実行結果を確認した後、見落としや境界条件を別のコードで検査する仕組みです。

## 統合練習：章末課題へつながる軽い予行演習

> **章末課題との接続：**食品倉庫のfixtureを使い、大規模実行を説明可能にする統制を練習します。

### 作るもの

8行を、数量が0以上という規則で有効と要確認へ分けます。商品別要約を保存して再読込し、件数照合、列、再実行一致を確認してください。

### 完成条件

- 全原本行が必ずどちらか一方へ入ります。
- 要約は有効行だけから作ります。
- 二つのチャンクサイズで同じ並べ替え済み要約になります。

### 解答への手引き

1. 原本、有効、要確認件数を記録します。
2. 比較前に並べ替え、indexを振り直します。
3. メモリだけでなく実際のCSVを再読込します。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> 模範解答と解説
>
> ```python
> import pandas as pd
> records = pd.DataFrame({"product": ["Rice", "Beans", "Rice", "Oil"],
>                         "quantity": [20, 15, -2, 8]})
> working = records.copy()
> working["quantity"] = pd.to_numeric(working["quantity"], errors="coerce")
> review_mask = working["quantity"].isna() | working["quantity"].lt(0)
> valid = working.loc[~review_mask].copy()
> review = working.loc[review_mask].copy()
> assert len(records) == len(valid) + len(review)
> summary = valid.groupby("product", as_index=False).agg(total_quantity=("quantity", "sum"))
> summary = summary.sort_values("product").reset_index(drop=True)
> summary.to_csv("food_summary.csv", index=False)
> saved = pd.read_csv("food_summary.csv")
> assert saved.columns.tolist() == summary.columns.tolist()
> assert len(saved) == len(summary)
> print("RECONCILED:", len(records), len(valid), len(review))
> ```
>
> **代表的な確認結果**
>
> ```text
> RECONCILED: 4 3 1
> ```
>
> fixture確認、件数照合、決定的順序、出力再読込は異なる失敗点を検査し、一つで他を代替できません。


### 値を変えて再確認する

数量一つを文字列で壊し、要確認へ移ること、照合が保たれること、要約が予想どおり変わることを確認します。
## まとめ




このレッスンと統合練習を終えると、次のことができます。

- 小さな既知データで処理規則を確認してから拡大する
- 全入力行を分析または要確認として照合する
- 入力、条件、品質規則、出力の来歴を残す
- メモリ上の値だけでなく、保存した成果物を再確認する

## 章末プロジェクトへ

次は、ここまでの読み込み計画、品質判定、チャンク集計、照合、保存、可視化を一つのプログラムへ統合し、診療所の最初の医薬品補給先を決定します。
