# 3.5B 中間実践課題 — 公共バスの改善調査

## 課題の状況

交通担当部署は、最初に一路線だけ改善調査できます。日報には予定便数、完了便数、乗客数、遅延分数合計があります。一便平均の遅延が最長の路線と、多くの乗客へ大きな遅延を与えている路線は同じとは限りません。確認が必要な記録を分け、路線別集計と調査順位を作ります。原本CSVは変更しません。

## 完成させる二つのプログラム

空のファイルから作りません。`projects/bus-service-review/`にある二つのスターターを完成させます。

1. `inspect_bus_service.py`（20点）：原資料を読み、全件と路線・日付順の表を表示します。
2. `bus_service_review.py`（80点）：既存の8関数へ監査、集計、順位、保存を実装します。

Notebookは作業案内です。提出するのはこの二つの`.py`だけです。入力CSVと確認プログラムは変更しません。

## 入力データ

`data/bus-service-practice.csv`には架空の31件があり、一行は一路線の一日分です。列は`date`、路線ID・名称、`district`、予定便数、完了便数、乗客数、完了便全体の遅延分数合計です。ヘッダーを1行目とし、最初のデータへ`source_row=2`を付けます。

## 第1段階 — 原資料を確認する

| 関数 | 公開仕様 |
|---|---|
| `load_records(path)` | 行順・列順を変えずCSVをDataFrameとして返す |
| `build_key_date_view(records)` | 原本を変えず、路線ID・日付昇順の新しい表を返す |
| `count_raw_values(records, column)` | 補正前の値を初出順に数え、`value, records`表を返す |

完成済み`main()`がshape、列名、推定型、全31件、路線・日付順、列ごとの欠損数、原文の地区名と件数を表示します。この段階では補正、除外、集計、順位付けをしません。

## 第2段階 — 品質確認と順位付け

原本のdeep copyへ次を行います。

1. 日付と路線IDの前後空白を除く。
2. 地区原文を`district_raw`へ残し、作業用地区をstrip＋title形式にする。
3. 4数値列を`pd.to_numeric(..., errors="coerce")`で変換する。
4. 必須数値の欠損、負数、完了便数が予定便数を超える、完了便0で乗客がいる、日付＋路線ID重複の5フラグを作る。
5. 重複キーは`keep=False`相当で全行を確認対象にする。

issue文は順に`missing required number`、`negative number`、`completed trips exceeds scheduled trips`、`passengers recorded with zero completed trips`、`duplicate route/date`とし、複数なら`; `で連結します。

有効行では次を計算します。

```text
cancelled_trips = scheduled_trips - completed_trips
passenger_delay_minutes = delay_minutes / completed_trips * passengers
```

完了便も乗客も0なら乗客遅延分は0.0です。路線ID・名称別に有効日数、予定・完了・欠便、乗客、遅延、乗客遅延分、一便平均遅延、欠便率を集計します。丸める前の乗客遅延分降順、欠便率降順、路線ID昇順で順位を付け、計算した小数列を`.round(1)`にします。

## 自分で照合する値

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: 4
ANALYSIS RECORDS: 27
FIRST REVIEW: R002 — Market Loop
```

欠損1件、不可能便数1件、重複2件です。R002は有効6日、推定乗客遅延25,920.0分、一便平均6.0分です。一便平均が最大のR003は15.0分ですが、乗客影響を含めると最優先ではありません。

第2段階では、読込、フラグ、確認票、分析表、路線集計、優先対象、保存、`run_project()`の既存8関数を完成させます。定数、関数名、引数、完成済み`main()`は変更しません。

## 出力・確認・提出

確認対象を`output/records_to_verify.csv`、路線順位を`output/route_review_summary.csv`へindexなしで保存します。

```text
python projects/bus-service-review/inspect_bus_service.py
python projects/bus-service-review/check_inspect_bus_service.py
python projects/bus-service-review/bus_service_review.py
python projects/bus-service-review/check_bus_service_review.py
```

二つの確認に合格し、`ALL TESTS PASSED`と`REVIEW READY`が表示されたら、`inspect_bus_service.py`と`bus_service_review.py`を提出します。
