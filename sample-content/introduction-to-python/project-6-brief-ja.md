# 6.4 課題仕様と完成条件

## 課題の状況

地域保健事務所は、複数の診療所から毎日の医薬品在庫記録を受け取っています。在庫切れによって患者を受け付けられなかった地域と医薬品を特定し、翌週の最初の補給先を決めなければなりません。

記録は12万件あるため、全件を一度に読み込むことを前提にしません。小さな確認用データで処理を確かめてから、必要な列だけをチャンク単位で処理します。

## 作成するプログラム

Python Labに用意された次のファイルを編集して完成させます。一から別のファイルを作る課題ではありません。

```text
projects/clinic-stock-scaleup/clinic_stock_scaleup.py
```

完成したプログラムは、入力行を分析対象または要確認へ必ず振り分け、地区・医薬品別に集計し、患者受入不能数が最大の組合せを最初の補給先として示します。

## 配布物

- `data/clinic-stock-fixture.csv`：48件の小さな確認用データ
- `generate_clinic_stock_data.py`：12万件の架空データを生成する完成済みプログラム
- `clinic_stock_scaleup.py`：学習者が完成させるスターター
- `check_clinic_stock_scaleup.py`：自動確認プログラム
- `README.md`：実装順と関数契約

生成データは架空で、個人情報を含みません。生成器と確認プログラムは変更しません。

## 作業の流れ

1. 48件のfixtureを表として確認する。
2. スターターの9関数を順番に実装する。
3. fixtureを処理し、入力行がすべて照合されることを確認する。
4. Notebookから12万件のCSVを生成する。
5. 大規模CSVをチャンク処理する。
6. チャンクサイズを変更しても同じ要約になることを確認する。
7. 自動確認を通し、CSVとPNGを確認する。

## 品質規則

次のいずれかに当てはまる行は要確認とし、集計へ含めません。

- 必須の文字列が空欄
- 数値列が空欄または数値へ変換できない
- 数値列に負数がある
- `stockout_hours`が0～24の範囲外
- `closing_units != opening_units + received_units - dispensed_units`

一行に複数の問題がある場合は、該当する理由を`|`で連結します。入力CSVは変更しません。

## 集計と優先順位

有効行を`district`と`medicine`でまとめ、次を求めます。

- `clinic_days`
- `stockout_days`
- `stockout_hours`
- `patients_turned_away`
- `stockout_rate`

`patients_turned_away`の降順、次に`stockout_hours`の降順、最後に地区名・医薬品名の昇順で並べます。先頭行が最初の補給先です。

## 照合

次が必ず成立しなければなりません。

```text
SOURCE RECORDS = ANALYSIS RECORDS + RECORDS TO REVIEW
```

チャンクサイズは処理方法だけを変える値です。`997`、`2,048`、`10,000`などへ変更しても、最終的な要約と優先順位は変わりません。

## 12万件データの確認値

正しく処理すると次になります。

```text
SOURCE RECORDS: 120000
ANALYSIS RECORDS: 119977
RECORDS TO REVIEW: 23
RECONCILED: True
FIRST RESUPPLY: East — Insulin
PATIENTS TURNED AWAY: 367492
```

これらは自分の結果を確認する代表値です。値をコードへ直接記述してはいけません。

## 提出物

次の3ファイルを提出します。

1. `clinic_stock_scaleup.py`
2. `clinic_stock_summary.csv`
3. `clinic_stock_evidence.png`

生成した12万件の原本CSV、Notebook、確認プログラムは提出しません。

## 完成条件

```text
python projects/clinic-stock-scaleup/check_clinic_stock_scaleup.py
```

を実行し、最後に次が表示されることを確認します。

```text
ALL TESTS PASSED
SCALE-UP VERIFIED
```
