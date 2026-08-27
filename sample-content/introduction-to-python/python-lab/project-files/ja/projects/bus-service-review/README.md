# 第3章 中間実践課題 — 公共バスの改善調査

## 課題の状況

交通担当部署は最初に一路線だけ改善調査できます。信頼できない日次記録を分け、遅延時間だけでなく乗客への遅延影響から調査順位を作ります。

## 二つのプログラム

空のファイルから作りません。Python Labの`projects/bus-service-review/`にあるスターターを完成させます。

1. `inspect_bus_service.py`（20点）：原資料31件を読み、全件、route/date順、型、欠損、原文のカテゴリ値を表示します。
2. `bus_service_review.py`（80点）：公開された品質規則、確認対象の分離、集計、順位付け、CSV保存を行います。

編集・提出するのはこの2ファイルです。入力CSV、確認プログラム、Notebookは変更・提出しません。

## 入力と作業順

入力は`data/bus-service-practice.csv`です。一行は一対象の一日分の記録です。原本を変更せず、最初に第1段階を完成させて自分の目で全件を確認してから、第2段階へ進みます。

```text
原資料を読む → 全件と並べ替え表示を確認 → 第1段階のテスト
→ 品質フラグ → 確認対象と分析対象を分離 → 集計と順位
→ CSVを確認 → 第2段階のテスト → 2ファイルを提出
```

## 公開チェックポイント

```text
SOURCE RECORDS: 31
RECORDS TO VERIFY: 4
ANALYSIS RECORDS: 27
FIRST REVIEW: R002 — Market Loop
```

一便平均遅延が最長なのはR003ですが、乗客への遅延影響が最大なのはR002です。

第2段階では、`load_records`、`add_quality_flags`、`build_verification_report`、`build_analysis_data`、集計、優先対象選択、保存、`run_project`の8関数を完成させます。関数名、引数、定数、完成済み`main()`は変更しません。

## 確認と完成条件

```text
python projects/bus-service-review/inspect_bus_service.py
python projects/bus-service-review/check_inspect_bus_service.py
python projects/bus-service-review/bus_service_review.py
python projects/bus-service-review/check_bus_service_review.py
```

両方の確認プログラムへ合格し、最後に`ALL TESTS PASSED`と`REVIEW READY`が表示されたら完成です。
