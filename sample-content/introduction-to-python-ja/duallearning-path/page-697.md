# 5.4 応用プロジェクト — 診療所の待ち時間を根拠として伝える

## 課題の状況

地区の保健担当者は、来週一つだけ臨時支援チームを配置できます。三つの診療所では6週間にわたり、午前・夕方別に、診療した人数、待ち時間の合計、60分を超えて待った人数を記録しています。

担当者が知りたいことは二つあります。待ち時間の総負担が最も大きかった診療所はどこか。そして、一人の患者が最も厳しい待ち時間を経験した診療所・時間帯はどこか。一つの合計値または一つの平均値だけでは、両方の問いには答えられません。

## 作成するプログラム

空のファイルから作り始めません。次のスターターを開いて完成させます。

```text
projects/clinic-wait-evidence/clinic_wait_evidence.py
```

プログラムは、配布CSVの読込と検証、二種類の集計、二つの対象選定、集計CSVと二面構成のPNG保存、短い根拠文の表示を行います。編集するのはこのPythonファイルだけです。

## 入力データ

原資料は次にあります。

```text
projects/clinic-wait-evidence/data/clinic-waits-practice.csv
```

架空の36件です。三診療所×二時間帯×6週間で、一行は一診療所の一週間における午前または夕方の記録です。原資料は編集・上書きしません。

| 列 | 意味 |
|---|---|
| `week` | 報告週 |
| `clinic_id`, `clinic_name` | 診療所を安定して識別する値 |
| `time_slot` | `Morning`または`Evening` |
| `patients_seen` | その記録に含まれる患者数 |
| `total_wait_minutes` | 患者全員の待ち時間合計 |
| `over_60_minutes` | 60分を超えて待った患者数 |

## 実装する処理

用意された九つの関数を、次の順番で完成させます。

1. `load_records(path)` — 必須列を読み、三つの数値列を数値へ変換する。
2. `validate_records(records)` — 空データ、必須値欠損、負数、患者数を超える60分超人数、週・診療所・時間帯の重複、未知の時間帯を拒否する。
3. `build_burden_summary(records)` — 診療所ごとに互換性のある値を合計し、平均待ち時間と60分超割合を計算する。待ち時間合計の降順、診療所IDの順に並べる。
4. `build_service_summary(records)` — 診療所・時間帯ごとに同じ指標を作る。丸め前の平均待ち時間、60分超割合の降順、診療所ID、時間帯の順に並べる。
5. `choose_targets(...)` — 二つの集計表の先頭行から、公開されたキーを持つ対象辞書を返す。
6. `create_evidence_figure(...)` — 診療所別の総負担と、診療所・時間帯別の平均待ち時間を一つのPNGへ保存する。棒の軸は0から始め、表題、単位、期間、支援対象を見えるようにする。
7. `build_evidence_note(...)` — 総負担の観察、二つの数値を含む支援対象の観察、原因までは分からないという限界の、ちょうど三文を返す。
8. `save_summary(...)` — 保存用コピーだけを小数第1位へ丸め、indexなしで時間帯別集計を保存する。
9. `run_project(...)` — 全工程を接続し、原資料件数、二つの集計表、対象辞書、根拠文を返す。

完成済みの`main()`が用意されています。関数名、引数、定数、出力項目名は変更しません。

## 手動確認値

配布データを正しく処理すると、次の値になります。

```text
SOURCE RECORDS: 36
TOTAL BURDEN CLINIC: C001 — Central Clinic
SUPPORT TARGET: C002 — Riverside Clinic — Evening
TARGET AVERAGE WAIT: 48.1 MINUTES
TARGET OVER-60 RATE: 32.4%
```

総負担が最大の診療所と、一人当たりの待ち時間が最も厳しい診療所・時間帯が一致しないのは、意図された結果です。

## 実行と確認

`Ctrl+S`で保存し、次を実行します。

```text
python projects/clinic-wait-evidence/clinic_wait_evidence.py
python projects/clinic-wait-evidence/check_clinic_wait_evidence.py
```

`output/clinic_wait_summary.csv`と`output/clinic_wait_evidence.png`を開き、図の分類と値が保存表と一致することを確認します。確認プログラムの最後が`ALL TESTS PASSED`、`EVIDENCE READY`となるまで修正します。

## 提出物

次の二つを提出します。

1. `clinic_wait_evidence.py`
2. `clinic_wait_evidence.png`

生成した集計CSVはPython Labに作業根拠として残します。原資料、確認プログラム、Notebook、出力CSVは提出しません。
