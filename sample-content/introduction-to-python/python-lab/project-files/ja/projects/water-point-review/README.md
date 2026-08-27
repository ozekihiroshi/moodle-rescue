# 第3章 中間実践課題 — 地域給水設備の点検

## 課題の状況

保守チームは最初に一施設だけ現地点検できます。故障センサーや矛盾した記録を分け、停止の継続と低出力日から点検順位を作ります。

## 二つのプログラム

空のファイルから作りません。Python Labの`projects/water-point-review/`にあるスターターを完成させます。

1. `inspect_water_points.py`（20点）：原資料31件を読み、全件、facility/date順、型、欠損、原文のカテゴリ値を表示します。
2. `water_point_review.py`（80点）：公開された品質規則、確認対象の分離、集計、順位付け、CSV保存を行います。

編集・提出するのはこの2ファイルです。入力CSV、確認プログラム、Notebookは変更・提出しません。

## 入力と作業順

入力は`data/water-points-practice.csv`です。一行は一対象の一日分の記録です。原本を変更せず、最初に第1段階を完成させて自分の目で全件を確認してから、第2段階へ進みます。

```text
原資料を読む → 全件と並べ替え表示を確認 → 第1段階のテスト
→ 品質フラグ → 確認対象と分析対象を分離 → 集計と順位
→ CSVを確認 → 第2段階のテスト → 2ファイルを提出
```

## 公開チェックポイント

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: 5
ANALYSIS RECORDS: 26
FIRST INSPECTION: W004 — East Market Water Point
```

原資料の最小値には故障センサーが含まれ、信頼できる記録からの優先施設はW004です。

第2段階では、`load_records`、`add_quality_flags`、`build_verification_report`、`build_analysis_data`、集計、優先対象選択、保存、`run_project`の8関数を完成させます。関数名、引数、定数、完成済み`main()`は変更しません。

## 確認と完成条件

```text
python projects/water-point-review/inspect_water_points.py
python projects/water-point-review/check_inspect_water_points.py
python projects/water-point-review/water_point_review.py
python projects/water-point-review/check_water_point_review.py
```

両方の確認プログラムへ合格し、最後に`ALL TESTS PASSED`と`REVIEW READY`が表示されたら完成です。
