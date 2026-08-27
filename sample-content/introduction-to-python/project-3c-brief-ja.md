# 3.5C 中間実践課題 — 地域給水設備の点検

## 課題の状況

保守チームは翌日、一施設だけ追加で現地点検できます。遠隔記録には故障センサーと実際の停止が混在します。原資料の最小給水量だけを選ぶと、訪問先を誤る可能性があります。確認対象を分け、施設別集計と点検順位を作ります。原本CSVは変更しません。

## 完成させる二つのプログラム

`projects/water-point-review/`にあるスターターを完成させます。

1. `inspect_water_points.py`（20点）：原資料を読み、全件と施設・日付順の表を表示します。
2. `water_point_review.py`（80点）：既存8関数へ品質フラグ、分離、集計、順位、CSV保存を実装します。

Notebookは作業案内です。提出は二つの`.py`だけです。入力CSVと確認プログラムは変更しません。

## 入力データ

`data/water-points-practice.csv`には架空の31件があり、一行は一施設の一日分です。列は日付、施設ID・名称、地区、定格毎時給水量、運転時間、実給水量、利用世帯数、センサー状態です。ヘッダーを1行目とし、最初のデータへ`source_row=2`を付けます。

## 第1段階 — 原資料を確認する

| 関数 | 公開仕様 |
|---|---|
| `load_records(path)` | 行順・列順を変えずCSVを返す |
| `build_key_date_view(records)` | 原本を変えず、施設ID・日付昇順の新しい表を返す |
| `count_raw_values(records, column)` | 補正前の値を初出順に数え、`value, records`表を返す |

完成済み`main()`がshape、列名、推定型、全31件、施設・日付順、欠損数、原文の地区名、原文のセンサー状態を表示します。この段階では補正、品質判定、除外、集計、順位付けをしません。

## 第2段階 — 品質確認と順位付け

原本のdeep copyへ次を行います。

1. 日付と施設IDの前後空白を除く。
2. 地区原文を残し、作業用地区をstrip＋title形式にする。
3. センサー状態の原文を残し、作業用状態をstrip＋小文字にする。
4. 4数値列を`pd.to_numeric(..., errors="coerce")`で変換する。
5. 必須数値欠損、負数、定格能力の105%を超える給水、センサー状態が`ok`でない、日付＋施設ID重複の5フラグを作る。
6. 重複キーは`keep=False`相当で全行を確認対象にする。

issue文は順に`missing required number`、`negative number`、`delivery exceeds rated capacity`、`sensor status is not ok`、`duplicate facility/date`とし、複数なら`; `で連結します。

有効行では次を計算します。

```text
rated_capacity_litres = rated_litres_per_hour * operating_hours
stopped_day = operating_hours == 0 and water_delivered_litres == 0
low_output_day = operating_hours > 0 and
                 water_delivered_litres < rated_capacity_litres * 0.70
```

施設ID・名称別に有効日数、停止日、低出力日、運転時間、定格能力、実給水量、利用世帯、出力率を集計します。停止日数降順、低出力日数降順、利用世帯数降順、施設ID昇順で順位を付け、出力率を`.round(1)`にします。

## 自分で照合する値

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: 5
ANALYSIS RECORDS: 26
FIRST INSPECTION: W004 — East Market Water Point
```

欠損1件、不可能出力1件、故障センサー1件、重複2件です。W004は有効6日、停止2日、低出力2日、出力率81.2%です。W002の原資料にある0は故障センサーの値で、実際の停止根拠として扱いません。

第2段階では、読込、フラグ、確認票、分析表、施設集計、優先対象、保存、`run_project()`の既存8関数を完成させます。定数、関数名、引数、完成済み`main()`は変更しません。

## 出力・確認・提出

確認対象を`output/records_to_verify.csv`、施設順位を`output/facility_inspection_summary.csv`へindexなしで保存します。

```text
python projects/water-point-review/inspect_water_points.py
python projects/water-point-review/check_inspect_water_points.py
python projects/water-point-review/water_point_review.py
python projects/water-point-review/check_water_point_review.py
```

二つの確認に合格し、`ALL TESTS PASSED`と`REVIEW READY`が表示されたら、`inspect_water_points.py`と`water_point_review.py`を提出します。
