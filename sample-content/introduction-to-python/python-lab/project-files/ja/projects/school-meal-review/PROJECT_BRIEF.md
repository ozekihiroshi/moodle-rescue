# 第3章 中間実践課題A — 明日の追加配送先を決める

## 課題の状況

業務終了時刻になり、6校から6日分の給食提供記録が届きました。明日の通常配送は決まっていますが、車両一台だけが追加で一校を訪問できます。朝の打合せまでに、担当者は「原資料を確認すべき記録」と「学校別の配送優先順位」を用意しなければなりません。

提出記録には、空欄、項目間の矛盾、二重登録、地区名の表記ゆれがあります。根拠のない値を推測で直してはいけません。判断に使えない記録を分け、残った記録から優先順位を作ってください。

## 完成させる二つのプログラム

空のファイルから作り始めません。Python Labの`projects/school-meal-review/`に、次の二つのスターターがあります。

1. 原資料を読み込んで表示する小さな`inspect_school_meals.py`を完成させます。これが第1段階で、20点です。
2. 続いて、既存の`meal_delivery_review.py`にある8関数を完成させます。これが第2段階で、80点です。

Project Notebookは二つのプログラムを実行して結果を見るための作業案内であり、提出しません。どちらのプログラムも原本CSVを編集・上書きしてはいけません。

## 入力データ

使用するファイルは`projects/school-meal-review/data/school-meals-practice.csv`です。すべて架空の業務記録で、個人情報は含みません。一行は「一校の一日分の記録」を表します。

| 列 | 内容 |
|---|---|
| `date` | 給食を提供した日 |
| `school_id` | 学校ID |
| `school_name` | 学校名 |
| `district` | 提出時の地区名 |
| `pupils_present` | 当日の出席児童数 |
| `meals_delivered` | 当日の配送食数 |
| `meals_served` | 当日に実際に提供できた食数 |

CSVの見出しを原本の1行目と数え、最初のデータ行には`source_row`として2を付けます。原本には37件のデータ行があります。

## 第1段階 — 原資料を確認する小課題

データを使う仕事では、品質判定を始める前に原資料の形と内容を確認します。`inspect_school_meals.py`を開き、三つの小さな関数を完成させてください。完成したプログラムは、次を行います。

1. 配布CSVをpandasで読み込む。
2. 行数と列数を表示する。
3. 列名とpandasが推定したデータ型を表示する。
4. 37件すべてを、行を省略せずに表示する。
5. `school_id`、次に`date`の順で並べた別の確認用表を表示する。
6. 全列の欠損数を表示する。
7. 原資料の`district`に記録された値と件数を表示する。

この段階では地区名を原資料の文字列のまま数えます。品質フラグの追加、行の削除、値の補正、学校別集計、配送順位の決定は行いません。`load_records(path)`は37件をコードへ書き写さず、同じ列を持つ別のCSVも読み込めるようにします。`build_school_date_view(records)`は`records`を変更せず、並べ替えたコピーを返します。`count_district_values(records)`は空白や大文字・小文字の違いを直さず、記録された値を数えます。

### 第1段階の3関数の契約

| 関数 | 返す値 |
|---|---|
| `load_records(path)` | `path`のCSVを読み込んだpandas DataFrame。CSVの行順と列順を保つ |
| `build_school_date_view(records)` | `school_id`の昇順、次に`date`の昇順で並べ、indexを0から振り直した新しいDataFrame。`records`自体は変更しない |
| `count_district_values(records)` | `district, records`の2列を持つDataFrame。原資料の地区文字列を区別し、各値が最初に現れた順で並べる |

完成済みの`main()`は、二つの37行表を`to_string(index=False)`で表示するため、pandasが中間行を省略記号へ置き換えることはありません。地区名はPythonの引用符付きで表示し、前後空白も目で判別できるようにします。

第2段階へ進む前に、プログラムと簡単な確認プログラムを実行します。

```text
python projects/school-meal-review/inspect_school_meals.py
python projects/school-meal-review/check_inspect_school_meals.py
```

全件表と並べ替え表は長く表示されます。難しい処理を始める前に、人間が原資料を確認できる状態をコードで作ることが、この小課題の目的です。

## 第2段階 — 品質を判定し、追加配送先を決める

原資料確認プログラムを完成させたら、`meal_delivery_review.py`を開きます。 既存の8関数を完成させ、確認した原資料から要確認記録、分析対象、学校別順位、 二つの出力CSVを作ります。以下は第2段階の公開仕様です。

### 判定する順序と品質規則

同じ原本から同じフラグが得られるよう、次の順序で処理します。

1. `date`と`school_id`の前後空白を除きます。日付形式の変換や表記統一はしません。重複判定には空白除去後の文字列を使います。
2. `pupils_present`、`meals_delivered`、`meals_served`を`pd.to_numeric(..., errors="coerce")`で変換します。空セル、空白だけの値、数値に変換できない値は欠損値になります。
3. 変換後の必須数値に欠損が一つでもあれば`missing_number`をTrueにします。
4. 数値へ変換できた値のいずれかが負なら`negative_number`をTrueにします。欠損値だけを理由にこのフラグをTrueにはしません。
5. `meals_served > pupils_present`または`meals_served > meals_delivered`なら`impossible_service`をTrueにします。それぞれの比較は、比較する二つの値が揃った場合だけ行います。欠損値だけを理由にTrueにはしません。
6. 空白除去後の`date`と`school_id`の組が二行以上に現れた場合、重複グループの全行で`duplicate_school_date`をTrueにします。`duplicated(..., keep=False)`に相当する判定です。

提出時の地区名を`district_raw`へ残します。処理用の`district`は`.astype("string").str.strip().str.title()`と同じ方法で整えます。配布CSVには地区名、日付、学校ID、学校名の空欄はありません。この課題ではこれらの項目へ別の品質フラグを追加せず、学校名の表記ゆれも補正しません。

Trueになったすべてのフラグから、次の順序で`issue`を作り、複数ある場合は`; `で連結します。

| フラグ | `issue`へ入れる文字列 |
|---|---|
| `missing_number` | `missing required number` |
| `negative_number` | `negative number` |
| `impossible_service` | `meals served exceeds limit` |
| `duplicate_school_date` | `duplicate school/date` |

一行に複数の問題が入る場合があります。4フラグがすべてFalseの場合だけ`is_valid`をTrueにします。`records_to_verify`は問題文字列の総数ではなく、無効と判定された行数です。

### 集計と順位の規則

有効行へ`unmet_meals = pupils_present - meals_served`を追加します。`school_id`と`school_name`の両方でまとめます。同じ`school_id`でも`school_name`が異なる行は別グループとし、この課題では学校名の表記ゆれを直しません。

次の順序で10列を作ります。

```text
priority, school_id, school_name, valid_days, pupils_present,
meals_served, unmet_meals, shortage_days,
meal_coverage_rate, average_unmet_meals
```

- `valid_days`：有効な異なる日付の数
- `shortage_days`：`unmet_meals > 0`だった有効行数
- `meal_coverage_rate`：提供食数合計 ÷ 出席児童数合計 × 100
- `average_unmet_meals`：未提供食数合計 ÷ 有効日数

あるグループの出席児童数合計が0なら、`meal_coverage_rate`を`0.0`とします。丸める前の`average_unmet_meals`を降順、次に`shortage_days`を降順、最後に`school_id`を昇順として順位を決めます。学校IDは同順位を再現可能に解消するキーです。先頭から`priority`を付けた後、率と平均をpandasの`.round(1)`で小数第1位へ丸めます。

### 配布CSVで確認できる代表値

どの行が異常かという答えは示しませんが、自分の処理を確認できる値は次のとおりです。

```text
SOURCE RECORDS: 37
RECORDS TO VERIFY: 4
ANALYSIS RECORDS: 33
FIRST DELIVERY: S004 — Market Road School
```

4フラグの件数は、欠損数値1行、負数0行、提供数の矛盾1行、重複キー2行です。学校別集計は6グループになり、1位の`average_unmet_meals`は7.5、`shortage_days`は6です。値が違う場合は、自動確認を実行する前に中間フラグと集計表を見直してください。

### 作成するファイル

pandasのindexを含めず、次の2ファイルを保存します。

1. `output/records_to_verify.csv`：`source_row,date,school_id,school_name,issue`
2. `output/school_delivery_summary.csv`：上記の学校別集計10列

完成済みの`main()`は、`SCHOOL MEAL DELIVERY REVIEW`という見出しに続けて、上記4行の代表値を表示します。

### 8関数の契約

| 関数 | 入力と完成時の動作 |
|---|---|
| `load_records(path)` | 必須列を検証・選択し、原本行番号を追加する |
| `add_quality_flags(records)` | ディープコピーへキーの空白除去、数値変換、地区列、4フラグ、`issue`、`is_valid`を追加し、`records`自体は変更しない |
| `build_verification_report(flagged)` | 無効行を原本順に並べ、指定5列で返す |
| `build_analysis_data(flagged)` | 有効行の必須列と`unmet_meals`を新しいindexで返す |
| `summarise_schools(analysis)` | 6校や既知IDへ固定せず、指定10列の順位表を返す |
| `select_first_delivery(summary)` | 1位を`{"school_id": ..., "school_name": ...}`で返し、空なら`ValueError` |
| `save_outputs(audit, summary, output_dir)` | 出力フォルダを作り、2つのCSVを保存する |
| `run_project(input_path, output_dir)` | 全工程を接続し、下記5項目を返す |

| `run_project()`のキー | 値の定義 |
|---|---|
| `source_records` | 入力CSVのデータ行数 |
| `records_to_verify` | 無効行数。一行に複数問題があっても一行と数える |
| `analysis_records` | 集計に使用した有効行数 |
| `first_delivery_id` | 優先順位1位の学校ID |
| `first_delivery_name` | 優先順位1位の学校名 |

定数、関数名、引数、完成済み`main()`は変更しません。

## 作業順、確認、提出

次の順序で進めます。

```text
CSVを読み込む
→ 見やすく表示して原資料を確認する
→ inspect_school_meals.pyを完成させ、自動確認に合格する
→ 品質規則を確認する
→ 本番の8関数を実装する
→ 生成CSVを確認する
→ meal_delivery_review.pyを完成させ、自動確認に合格する
→ 二つのプログラムを提出する
```

Notebookはこの作業を案内するためだけに使用します。観察についての長い文章や最終レポートは要求しません。

### 評価

| 提出するプログラム | 配点 | 確認する内容 |
|---|---:|---|
| `inspect_school_meals.py` | 20点 | 配布CSVと別CSVを読み込める、原本を保護する、形・列名・型・全行・学校日付順の表・欠損数・原地区名の件数を確認できる |
| `meal_delivery_review.py` | 80点 | 既存の8関数が公開された品質判定・分離・集計・順位・保存の仕様を実装し、本番の10項目すべてに合格する |

出力を自分で確認してから、二つの確認プログラムを実行します。

```text
python projects/school-meal-review/check_inspect_school_meals.py
python projects/school-meal-review/check_meal_delivery_review.py
```

Moodleへ提出するのは、次の二つだけです。

1. `inspect_school_meals.py`
2. `meal_delivery_review.py`

生成CSVとProject NotebookはPython Labに作業用として残し、提出しません。
