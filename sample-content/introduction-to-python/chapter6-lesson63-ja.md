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

## 統合練習

fixtureを二つの異なるチャンクサイズで処理し、件数照合、要約一致、原本ファイルの未変更、保存CSVの列と行数を確認してください。

## まとめ

- 小さな既知データで処理規則を確認してから拡大する
- 全入力行を分析または要確認として照合する
- 入力、条件、品質規則、出力の来歴を残す
- メモリ上の値だけでなく、保存した成果物を再確認する

## 章末プロジェクトへ

次は、ここまでの読み込み計画、品質判定、チャンク集計、照合、保存、可視化を一つのプログラムへ統合し、診療所の最初の医薬品補給先を決定します。
