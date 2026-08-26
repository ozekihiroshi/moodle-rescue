# 第3章・三択中間実践課題の設計

## 位置付け

第3章の中間実践課題は三つの選択肢から一つを完成すれば必須条件を満たす。二つ目と三つ目は任意の転用課題とする。三課題は題材と発見を変えるが、同じ第3章技能、所要時間、提出量、自動確認水準を要求する。

| 選択 | 仮題 | 発見 |
|---|---|---|
| A | 学校給食の追加配送 | 欠損を0扱いした素朴な答えと、監査後の答えが反転する |
| B | 公共バスの改善調査 | 平均遅延と利用者影響で優先路線が変わる |
| C | 地域給水設備の点検 | 低出力に見えるセンサー異常と継続停止を分ける |

まずAを完成させ、その設計からB、Cを作る。既存Moodleへは、Aの学習者経路と確認プログラムが成立するまで反映しない。

# 選択A：学校給食の追加配送

## 学習者に示す状況

6校から直近6日分の記録が届いた。翌日、倉庫の車両は通常配送とは別に一校だけ追加訪問できる。まだ利用してはいけない記録を分けたうえで、最初の配送先を一校示し、判断に使った集計表を残す。元CSVは変更しない。

社会的意義や学習目標は課題本文で説明しない。状況、規則、入力、成果物、完成条件だけを示す。

## 入力

`projects/school-meal-review/data/school-meals-practice.csv`

- 37行、7列、6校、6日分（S003の一日は重複している）
- 列：`date`, `school_id`, `school_name`, `district`, `pupils_present`, `meals_delivered`, `meals_served`
- 架空データであり個人情報を含まない
- 学習者はCSVを編集しない

## 業務規則

1. 必須数値に欠損がある行は確認対象とし、集計から外す。
2. 負数を含む行は確認対象とし、集計から外す。
3. `meals_served`が`pupils_present`または`meals_delivered`を超える行は確認対象とし、集計から外す。
4. `date + school_id`が重複する場合は重複した全行を確認対象とし、集計から外す。
5. 地区名は前後空白を除き、単語先頭を大文字に統一する。これは行を外す理由にしない。
6. 有効行に`unmet_meals = pupils_present - meals_served`を追加する。
7. 学校別に有効日数、出席児童、提供食、未提供食、未提供があった日数を集計する。
8. `meal_coverage_rate = meals_served合計 / pupils_present合計 × 100`を計算する。
9. `average_unmet_meals = unmet_meals合計 / valid_days`を計算する。
10. `average_unmet_meals`降順、`shortage_days`降順、`school_id`昇順で配送優先順位を決める。

## 編集・提出・出力

- 編集・提出：`meal_delivery_review.py`一つ
- 変更しない：入力CSV、`check_meal_delivery_review.py`
- 生成：`output/records_to_verify.csv`
- 生成：`output/school_delivery_summary.csv`

`records_to_verify.csv`には元CSV行番号、日付、学校、理由を残す。`school_delivery_summary.csv`には全校の集計と優先順位を残す。

## 期待される発見

- 元行37
- 欠損行1
- 不可能値行1
- 重複行2
- 確認対象4
- 分析対象33
- 素朴な欠損0扱い：`S002`
- 規則どおりの処理：`S004 — Market Road School`
- S004：有効6日、未提供45、平均7.5、提供率93.3%

素朴な答えと正しい答えが異なることを、この課題の採用条件とする。

## 実装単位

1. `load_records(path)`
2. `add_quality_flags(records)`
3. `build_verification_report(flagged)`
4. `build_analysis_data(flagged)`
5. `summarise_schools(analysis)`
6. `select_first_delivery(summary)`
7. `save_outputs(audit, summary, output_dir)`
8. `run_project(input_path, output_dir)`

個別関数は件数や特定学校を固定しない。`run_project()`だけが配布CSVを既定入力として接続する。

## 自動確認

確認プログラムは公開仕様だけを検査する。

1. CSV読込と元行番号
2. 元DataFrameを変更しない
3. 地区名正規化
4. 四種類の品質フラグ
5. 確認対象4行
6. 分析対象33行
7. 学校別集計値
8. 優先順位とS004
9. 任意の小規模DataFrameでも特定件数へ固定されない
10. 出力、画面表示、元CSV保護

全項目合格時は`ALL TESTS PASSED`と`REVIEW READY`を表示する。

## 第3章本文への逆算項目

プロジェクト完成後、3.1〜3.4に次が十分含まれるか監査する。

- 必須列、shape、型、元データコピー
- 複数列の欠損マスク
- `duplicated(..., keep=False)`
- 複数の不可能値条件
- `.loc`によるフラグ付与
- `str.strip().str.title()`
- `groupby().agg()`のnamed aggregation
- 件数を数える条件付き集計
- 合計から率を作る
- 複数列、異なる昇降順で並べ替える
- CSV保存と再読込照合
