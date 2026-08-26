#!/usr/bin/env python3
"""Structure Lessons 3.1-3.4 into visible, numbered learning topics."""

from __future__ import annotations

import html
import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"
UPGRADE = ROOT / "scripts/upgrade-python-chapter3-lessons-v36.php"
VERIFY = ROOT / "scripts/verify-python-chapter3-lessons-v36.php"
APPLY = ROOT / "scripts/apply-python-chapter3-lessons-v36.sh"
AUDIT_MD = ROOT / "sample-content/introduction-to-python/chapter3-project-readiness-audit-v2.md"
AUDIT_JSON = ROOT / "sample-content/introduction-to-python/chapter3-project-readiness-audit-v2.json"


STYLE = "margin-top:2em;padding:.55em .75em;border-left:4px solid #0f6cbf;background:#f3f7fb"


def h3(label: str) -> str:
    return f'<h3 style="{STYLE}">{label}</h3>'


def h4(label: str) -> str:
    return f"<h4>{label}</h4>"


def code_html(code: str) -> str:
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' + html.escape(code) + "</code></pre>"


def page_specs() -> dict[str, dict[str, dict[str, str]]]:
    ja31_extra = (
        h4("全件表示とカテゴリ値の件数を確認する")
        + '<p><code>head()</code>は先頭確認に向きますが、37件程度の原資料を人が全件確認するときは<code>to_string(index=False)</code>で中間行の省略を防げます。カテゴリ列は、補正する前に原資料の値と件数を確認します。<code>value_counts(dropna=False, sort=False)</code>は欠損も含め、値が最初に現れた順で数えます。2列の表が必要ならSeriesの名前を列へ戻します。</p>'
        + code_html('print(df.to_string(index=False, line_width=200))\n\ndistrict_counts = (\n    df["district"]\n    .value_counts(dropna=False, sort=False)\n    .rename_axis("district")\n    .reset_index(name="records")\n)\nprint(district_counts.to_string(index=False, formatters={"district": repr}))')
    )
    en31_extra = (
        h4("Display every row and count raw category values")
        + '<p><code>head()</code> is useful for a first sample, but a person inspecting a 37-row source can use <code>to_string(index=False)</code> to prevent middle rows from being replaced by an ellipsis. Before correcting a category column, inspect its raw values and counts. <code>value_counts(dropna=False, sort=False)</code> includes missingness and retains first-appearance order. Convert the Series to a two-column table when the result needs named fields.</p>'
        + code_html('print(df.to_string(index=False, line_width=200))\n\ndistrict_counts = (\n    df["district"]\n    .value_counts(dropna=False, sort=False)\n    .rename_axis("district")\n    .reset_index(name="records")\n)\nprint(district_counts.to_string(index=False, formatters={"district": repr}))')
    )
    return {
        "ja": {
            "レッスン3.1：表形式データ・CSV・pandas": {
                '<h2>レコードの集まりを、確認可能な表として扱う</h2>': '<h2>レコードの集まりを、確認可能な表として扱う</h2>' + h3("3.1.1 表形式データ — 行・列・セルの意味"),
                '<h3>一行を一観測、一列を一変数としてそろえる</h3>': h4("一行を一観測、一列を一変数としてそろえる"),
                '<h3>CSVは表を記録したテキスト形式である</h3>': h3("3.1.2 CSV — 表を交換するテキスト形式"),
                '<h3>相対パスは現在の作業フォルダから解釈される</h3>': h4("相対パスと実際に読み込むファイル"),
                '<h3>read_csvの前提をコードへ明示する</h3>': h4("read_csvの前提をコードへ明示する"),
                '<h3>計算の前に形、列名、型、欠損を確認する</h3>': h3("3.1.3 pandas — DataFrameを読み、確かめる"),
                '<h3>一列はSeries、複数列の表はDataFrameになる</h3>': ja31_extra + h4("一列はSeries、複数列の表はDataFrame"),
                '<h3>計算列は確認後に追加する</h3>': h3("3.1.4 pandas — 列計算とCSV出力"),
                '<h3>indexと業務上の識別子を区別する</h3>': h4("indexと業務上の識別子を区別する"),
                '<h3>読込失敗を原因別に調べる</h3>': h3("3.1.5 読込問題を原因別に切り分ける"),
                '<h3>例題から応用へ</h3>': h3("3.1.6 例題を別の表へ応用する"),
            },
            "レッスン3.2：データの選択・抽出とブール論理": {
                '<h2>分析の問いを、表示列と行条件へ翻訳する</h2>': '<h2>分析の問いを、表示列と行条件へ翻訳する</h2>' + h3("3.2.1 問いを表示列と行条件へ分ける"),
                '<h3>問いを表示列と部分条件へ分ける</h3>': h4("必要列を検査し、部分条件へ分ける"),
                '<h3>列名のリストで必要な項目だけを選ぶ</h3>': h4("列名で必要な項目を選ぶ"),
                '<h3>locはラベル、ilocは位置で選ぶ</h3>': h3("3.2.2 locとilocで行・列を選ぶ"),
                '<h3>比較式は各行のTrue・Falseを持つマスクを作る</h3>': h3("3.2.3 比較式をブールマスクにする"),
                '<h3>pandasの条件には&・|・~と括弧を使う</h3>': h3("3.2.4 ブール論理で複数条件を組み立てる"),
                '<h3>AND、OR、NOTの意味を件数で確かめる</h3>': h4("AND・OR・NOTを件数で確かめる"),
                '<h3>isinで所属、betweenで範囲を表す</h3>': h3("3.2.5 所属・範囲・欠損を条件に表す"),
                '<h3>欠損を偶然条件外へ落とさず、明示する</h3>': h4("欠損を条件から偶然落とさない"),
                '<h3>名前を付けたマスクをlocで組み合わせる</h3>': h3("3.2.6 マスクで抽出し、件数を検証する"),
                '<h3>ブールマスクはindexラベルで行へ対応する</h3>': h4("マスクと行をindexで対応させる"),
                '<h3>例題から応用へ</h3>': h3("3.2.7 条件を別の問いへ応用する"),
            },
            "レッスン3.3：データのクリーニングと監査記録": {
                '<h2>データを直す前に、何を問題とするか決める</h2>': '<h2>データを直す前に、何を問題とするか決める</h2>' + h3("3.3.1 原本を保ち、品質問題を定義する"),
                '<h3>修正前に型、欠損、カテゴリ、範囲を観察する</h3>': h4("修正前に型・欠損・カテゴリ・範囲を確認する"),
                '<h3>元データ、作業用データ、分析用データを分ける</h3>': h3("3.3.2 原本・作業用・分析用データを分ける"),
                '<h3>型変換失敗を、元からある欠損と区別する</h3>': h3("3.3.3 型変換と欠損を区別する"),
                '<h3>欠損は0ではない</h3>': h4("欠損と0を混同しない"),
                '<h3>表記を統一しても元の文字列を残す</h3>': h3("3.3.4 表記を整え、元の値を残す"),
                '<h3>単独の範囲と、項目間の関係を別々に検査する</h3>': h3("3.3.5 範囲・項目間制約・重複を検査する"),
                '<h3>重複は業務キーを決めてから調べる</h3>': h4("業務キーで重複グループ全体を調べる"),
                '<h3>検出と処置を分ける</h3>': h3("3.3.6 品質フラグから確認対象表を作る"),
                '<h3>個別フラグから、行単位の確認対象表を作る</h3>': h4("問題理由を行単位の確認対象表へ残す"),
                '<h3>監査記録とassertで再現可能にする</h3>': h3("3.3.7 監査記録と再検証を残す"),
                '<h3>課題へ進む前の境界規則</h3>': h4("課題へ進む前に境界値を検証する"),
                '<h3>例題から応用へ</h3>': h3("3.3.8 品質規則を別のデータへ応用する"),
            },
            "レッスン3.4：グループ化と要約統計": {
                '<h2>集計する前に、一行の意味を決める</h2>': '<h2>集計する前に、一行の意味を決める</h2>' + h3("3.4.1 明細と集計結果の粒度を決める"),
                '<h3>groupbyは分割・計算・結合を行う</h3>': h3("3.4.2 groupbyで分け、数え、まとめる"),
                '<h3>size・count・nuniqueを使い分ける</h3>': h4("size・count・nuniqueの対象を区別する"),
                '<h3>条件付き件数を名前付き集計へ入れる</h3>': h4("条件付き件数を名前付き集計へ入れる"),
                '<h3>合計、中心、ばらつきは別の問いに答える</h3>': h3("3.4.3 要約統計と割合を計算する"),
                '<h3>率は分子と分母を合計してから求める</h3>': h4("率は対応する分子と分母の合計から求める"),
                '<h3>複数方向の並べ替えで順位を確定する</h3>': h3("3.4.4 複数条件で再現可能な順位を作る"),
                '<h3>複数キーでは比較の階層を保つ</h3>': h4("複数キーで比較の階層を保つ"),
                '<h3>構成比は分母と100%を確認する</h3>': h3("3.4.5 構成比の分母と合計を確認する"),
                '<h3>部分集計を明細の全体合計と照合する</h3>': h3("3.4.6 集計・保存・再読込を照合する"),
                '<h3>二つのCSVを保存後に再読込する</h3>': h4("用途別CSVを保存し、再読込して検証する"),
                '<h3>例題から応用へ</h3>': h3("3.4.7 集計と順位を別の判断へ応用する"),
            },
        },
        "en": {
            "Lesson 3.1: Tabular data, CSV, and pandas": {
                '<h2>Treat a collection of records as an inspectable table</h2>': '<h2>Treat a collection of records as an inspectable table</h2>' + h3("3.1.1 Tabular data — rows, columns, and cells"),
                '<h3>Align one observation per row and one variable per column</h3>': h4("Align one observation per row and one variable per column"),
                '<h3>CSV is a text representation of a table</h3>': h3("3.1.2 CSV — a text format for exchanging tables"),
                '<h3>A relative path is interpreted from the current working directory</h3>': h4("Relative paths and the file actually loaded"),
                '<h3>Express read_csv assumptions in code</h3>': h4("Express read_csv assumptions in code"),
                '<h3>Inspect shape, names, types, and missingness before calculation</h3>': h3("3.1.3 pandas — load and inspect a DataFrame"),
                '<h3>One column is a Series; a multi-column table is a DataFrame</h3>': en31_extra + h4("One column is a Series; multiple columns form a DataFrame"),
                '<h3>Add derived columns only after inspection</h3>': h3("3.1.4 pandas — column calculations and CSV output"),
                '<h3>Distinguish the index from an operational identifier</h3>': h4("Distinguish the index from an operational identifier"),
                '<h3>Diagnose loading failures by cause</h3>': h3("3.1.5 Diagnose loading problems by cause"),
                '<h3>From guided example to transfer</h3>': h3("3.1.6 Transfer the example to another table"),
            },
            "Lesson 3.2: Data selection, filtering, and Boolean logic": {
                '<h2>Translate an analysis question into displayed columns and row conditions</h2>': '<h2>Translate an analysis question into displayed columns and row conditions</h2>' + h3("3.2.1 Separate a question into displayed columns and row conditions"),
                '<h3>Separate displayed columns from partial conditions</h3>': h4("Validate required columns and separate partial conditions"),
                '<h3>Select needed fields with a list of column names</h3>': h4("Select the fields needed for the result"),
                '<h3>loc uses labels; iloc uses positions</h3>': h3("3.2.2 Select rows and columns with loc and iloc"),
                '<h3>A comparison creates one True/False value per row</h3>': h3("3.2.3 Turn comparisons into Boolean masks"),
                '<h3>Use & | ~ and parentheses for pandas conditions</h3>': h3("3.2.4 Build compound conditions with Boolean logic"),
                '<h3>Verify AND, OR, and NOT through counts</h3>': h4("Verify AND, OR, and NOT through counts"),
                '<h3>Use isin for membership and between for a range</h3>': h3("3.2.5 Express membership, ranges, and missingness"),
                '<h3>Make missingness explicit instead of losing rows accidentally</h3>': h4("Do not lose missing rows accidentally"),
                '<h3>Combine named masks in loc</h3>': h3("3.2.6 Filter with masks and verify row counts"),
                '<h3>A Boolean mask aligns by index label</h3>': h4("Align masks and rows by index label"),
                '<h3>From guided example to transfer</h3>': h3("3.2.7 Transfer the conditions to another question"),
            },
            "Lesson 3.3: Data cleaning and audit records": {
                '<h2>Define the problem before changing the data</h2>': '<h2>Define the problem before changing the data</h2>' + h3("3.3.1 Preserve the source and define quality problems"),
                '<h3>Profile types, missingness, categories, and ranges before correction</h3>': h4("Inspect types, missingness, categories, and ranges first"),
                '<h3>Separate source, working, and analysis-ready data</h3>': h3("3.3.2 Separate source, working, and analysis-ready data"),
                '<h3>Distinguish conversion failure from source missingness</h3>': h3("3.3.3 Separate type conversion from missingness"),
                '<h3>Missing does not mean zero</h3>': h4("Do not confuse missingness with zero"),
                '<h3>Normalise labels without discarding source text</h3>': h3("3.3.4 Normalise labels while retaining source values"),
                '<h3>Test individual ranges and cross-field relationships separately</h3>': h3("3.3.5 Test ranges, cross-field constraints, and duplicates"),
                '<h3>Define a business key before checking duplicates</h3>': h4("Use a business key to flag every duplicate row"),
                '<h3>Separate detection from action</h3>': h3("3.3.6 Build a verification table from quality flags"),
                '<h3>Build a row-level verification report from individual flags</h3>': h4("Preserve row-level reasons in the verification report"),
                '<h3>Make the workflow reproducible with an audit record and assertions</h3>': h3("3.3.7 Record the audit and validate again"),
                '<h3>Boundary rules before the project</h3>': h4("Validate boundary cases before the project"),
                '<h3>From worked example to transfer</h3>': h3("3.3.8 Transfer quality rules to another dataset"),
            },
            "Lesson 3.4: Grouping and summary statistics": {
                '<h2>Define what one result row means before aggregation</h2>': '<h2>Define what one result row means before aggregation</h2>' + h3("3.4.1 Define the grain of detail and summary rows"),
                '<h3>groupby splits, applies, and combines</h3>': h3("3.4.2 Split, count, and combine with groupby"),
                '<h3>Distinguish size, count, and nunique</h3>': h4("Distinguish what size, count, and nunique count"),
                '<h3>Put a conditional count into named aggregation</h3>': h4("Put a conditional count into named aggregation"),
                '<h3>Totals, centre, and spread answer different questions</h3>': h3("3.4.3 Calculate summary statistics and rates"),
                '<h3>Aggregate compatible numerators and denominators before a rate</h3>': h4("Calculate rates from compatible aggregate totals"),
                '<h3>Make ranking deterministic with mixed sort directions</h3>': h3("3.4.4 Build reproducible rankings from multiple conditions"),
                '<h3>Multiple keys preserve hierarchy</h3>': h4("Preserve comparison hierarchy with multiple keys"),
                '<h3>State the proportion denominator and check 100%</h3>': h3("3.4.5 Check the denominator and 100% total of proportions"),
                '<h3>Reconcile grouped totals with detail</h3>': h3("3.4.6 Reconcile aggregation, saving, and re-reading"),
                '<h3>Re-read two CSV products after saving</h3>': h4("Save purpose-specific CSVs and validate them after re-reading"),
                '<h3>From worked example to transfer</h3>': h3("3.4.7 Transfer aggregation and ranking to another decision"),
            },
        },
    }


NOTEBOOK_HEADINGS = {
    "07_tables_csv_pandas.ipynb": {
        "l31-en-table": "## 3.1.1 Tabular data — rows, columns, and cells",
        "l31-en-csv": "## 3.1.2 CSV — a text format for exchanging tables",
        "l31-en-path": "### Relative paths and the file actually loaded",
        "l31-en-load": "### Make CSV loading assumptions explicit",
        "l31-en-inspect": "## 3.1.3 pandas — load and inspect a DataFrame",
        "l31-en-series": "### One column is a Series; multiple columns form a DataFrame",
        "l31-en-derived": "## 3.1.4 pandas — column calculations and CSV output",
        "l31-en-index": "### Distinguish the DataFrame index from an operational identifier",
        "l31-en-errors": "## 3.1.5 Diagnose loading problems by cause",
        "l31-en-transfer": "## 3.1.6 Transfer the example to another table",
    },
    "ja/07_tables_csv_pandas.ipynb": {
        "l31-ja-table": "## 3.1.1 表形式データ — 行・列・セルの意味",
        "l31-ja-csv": "## 3.1.2 CSV — 表を交換するテキスト形式",
        "l31-ja-path": "### 相対パスと実際に読み込むファイル",
        "l31-ja-load": "### read_csvで読み込み方を明示する",
        "l31-ja-inspect": "## 3.1.3 pandas — DataFrameを読み、確かめる",
        "l31-ja-series": "### 一列はSeries、複数列はDataFrame",
        "l31-ja-derived": "## 3.1.4 pandas — 列計算とCSV出力",
        "l31-ja-index": "### DataFrameのindexと業務上の識別子を区別する",
        "l31-ja-errors": "## 3.1.5 読込問題を原因別に切り分ける",
        "l31-ja-transfer": "## 3.1.6 例題を別の表へ応用する",
    },
    "08_filtering_boolean_logic.ipynb": {
        "l32-en-question": "## 3.2.1 Separate a question into displayed columns and row conditions",
        "l32-en-columns": "### Select the fields needed for the result",
        "l32-en-loc": "## 3.2.2 Select rows and columns with loc and iloc",
        "l32-en-mask": "## 3.2.3 Turn comparisons into Boolean masks",
        "l32-en-bool": "## 3.2.4 Build compound conditions with Boolean logic",
        "l32-en-combine": "### Verify AND, OR, and NOT through counts",
        "l32-en-membership": "## 3.2.5 Express membership, ranges, and missingness",
        "l32-en-missing": "### Do not lose missing rows accidentally",
        "l32-en-result": "## 3.2.6 Filter with masks and verify row counts",
        "l32-en-index": "### Align masks and rows by index label",
        "l32-en-transfer": "## 3.2.7 Transfer the conditions to another question",
    },
    "ja/08_filtering_boolean_logic.ipynb": {
        "l32-ja-question": "## 3.2.1 問いを表示列と行条件へ分ける",
        "l32-ja-columns": "### 列名で必要な項目を選ぶ",
        "l32-ja-loc": "## 3.2.2 locとilocで行・列を選ぶ",
        "l32-ja-mask": "## 3.2.3 比較式をブールマスクにする",
        "l32-ja-bool": "## 3.2.4 ブール論理で複数条件を組み立てる",
        "l32-ja-combine": "### AND・OR・NOTを件数で確かめる",
        "l32-ja-membership": "## 3.2.5 所属・範囲・欠損を条件に表す",
        "l32-ja-missing": "### 欠損を条件から偶然落とさない",
        "l32-ja-result": "## 3.2.6 マスクで抽出し、件数を検証する",
        "l32-ja-index": "### マスクと行をindexで対応させる",
        "l32-ja-transfer": "## 3.2.7 条件を別の問いへ応用する",
    },
    "09_cleaning_audit_trail.ipynb": {
        "l33-en-flow": "## 3.3.1 Preserve the source and define quality problems",
        "l33-en-profile": "## 3.3.2 Separate source, working, and analysis data",
        "l33-en-types": "## 3.3.3 Separate type conversion from missingness",
        "l33-en-missing": "### Do not confuse missingness with zero",
        "l33-en-text": "## 3.3.4 Normalise labels while retaining source values",
        "l33-en-rules": "## 3.3.5 Test ranges, cross-field constraints, and duplicates",
        "l33-en-duplicates": "### Use a business key to flag every duplicate row",
        "l33-en-actions": "## 3.3.6 Build a verification table from quality flags",
        "l33-en-verification": "### Preserve row-level reasons in the verification report",
        "l33-en-audit": "## 3.3.7 Record the audit and validate again",
        "l33-en-validate": "### Reapply constraints and reconcile counts",
        "l33-en-transfer": "## 3.3.8 Transfer quality rules to another dataset",
    },
    "ja/09_cleaning_audit_trail.ipynb": {
        "l33-ja-flow": "## 3.3.1 原本を保ち、品質問題を定義する",
        "l33-ja-profile": "## 3.3.2 原本・作業用・分析用データを分ける",
        "l33-ja-types": "## 3.3.3 型変換と欠損を区別する",
        "l33-ja-missing": "### 欠損と0を混同しない",
        "l33-ja-text": "## 3.3.4 表記を整え、元の値を残す",
        "l33-ja-rules": "## 3.3.5 範囲・項目間制約・重複を検査する",
        "l33-ja-duplicates": "### 業務キーで重複グループ全体を調べる",
        "l33-ja-actions": "## 3.3.6 品質フラグから確認対象表を作る",
        "l33-ja-verification": "### 問題理由を行単位の確認対象表へ残す",
        "l33-ja-audit": "## 3.3.7 監査記録と再検証を残す",
        "l33-ja-validate": "### 同じ制約と件数で再検証する",
        "l33-ja-transfer": "## 3.3.8 品質規則を別のデータへ応用する",
    },
    "10_grouping_statistics.ipynb": {
        "l34-en-grain": "## 3.4.1 Define the grain of detail and summary rows",
        "l34-en-split": "## 3.4.2 Split, count, and combine with groupby",
        "l34-en-count": "### Distinguish what size, count, and nunique count",
        "l34-en-conditional": "### Put a conditional count into named aggregation",
        "l34-en-stats": "## 3.4.3 Calculate summary statistics and rates",
        "l34-en-ratio": "### Calculate rates from compatible aggregate totals",
        "l34-en-ranking": "## 3.4.4 Build reproducible rankings from multiple conditions",
        "l34-en-multikey": "### Preserve comparison hierarchy with multiple keys",
        "l34-en-denominator": "## 3.4.5 Check the denominator and 100% total of proportions",
        "l34-en-validate": "## 3.4.6 Reconcile aggregation, saving, and re-reading",
        "l34-en-outputs": "### Save purpose-specific CSVs and validate them after re-reading",
        "l34-en-transfer": "## 3.4.7 Transfer aggregation and ranking to another decision",
    },
    "ja/10_grouping_statistics.ipynb": {
        "l34-ja-grain": "## 3.4.1 明細と集計結果の粒度を決める",
        "l34-ja-split": "## 3.4.2 groupbyで分け、数え、まとめる",
        "l34-ja-count": "### size・count・nuniqueの対象を区別する",
        "l34-ja-conditional": "### 条件付き件数を名前付き集計へ入れる",
        "l34-ja-stats": "## 3.4.3 要約統計と割合を計算する",
        "l34-ja-ratio": "### 対応する分子と分母の合計から率を求める",
        "l34-ja-ranking": "## 3.4.4 複数条件で再現可能な順位を作る",
        "l34-ja-multikey": "### 複数キーで比較の階層を保つ",
        "l34-ja-denominator": "## 3.4.5 構成比の分母と合計を確認する",
        "l34-ja-validate": "## 3.4.6 集計・保存・再読込を照合する",
        "l34-ja-outputs": "### 用途別CSVを保存し、再読込して検証する",
        "l34-ja-transfer": "## 3.4.7 集計と順位を別の判断へ応用する",
    },
}


def replace_heading(cell: dict, heading: str) -> None:
    source = "".join(cell["source"])
    lines = source.splitlines(keepends=True)
    if not lines or not lines[0].startswith("#"):
        raise RuntimeError(f"No heading in cell {cell.get('id')}")
    ending = "\n" if lines[0].endswith("\n") else ""
    lines[0] = heading + ending
    cell["source"] = lines


def update_notebooks() -> None:
    for relative, headings in NOTEBOOK_HEADINGS.items():
        path = TEMPLATES / relative
        document = json.loads(path.read_text(encoding="utf-8"))
        cells = document["cells"]
        by_id = {cell.get("id"): cell for cell in cells}
        for cell_id, heading in headings.items():
            if cell_id not in by_id:
                raise RuntimeError(f"Missing {cell_id} in {path}")
            replace_heading(by_id[cell_id], heading)
        if relative.endswith("07_tables_csv_pandas.ipynb"):
            ja = relative.startswith("ja/")
            prefix = "l31-ja" if ja else "l31-en"
            cells[:] = [cell for cell in cells if cell.get("id") not in {f"{prefix}-values", f"{prefix}-values-code"}]
            after = next(i for i, cell in enumerate(cells) if cell.get("id") == f"{prefix}-inspect") + 1
            text = ("### 全件表示とカテゴリ値の件数を確認する\n\n`head()`は先頭確認用です。人が確認できる件数なら`to_string(index=False)`で全件を省略せず表示します。補正前のカテゴリ値は`value_counts(dropna=False, sort=False)`で数え、必要なら名前付き2列の表へ変換します。" if ja else "### Display every row and count raw category values\n\n`head()` is for a first sample. For a human-inspectable source, `to_string(index=False)` displays every row without an ellipsis. Count raw category values with `value_counts(dropna=False, sort=False)` and convert the Series to a named two-column table when needed.")
            md = {"cell_type": "markdown", "id": f"{prefix}-values", "metadata": {}, "source": text.splitlines(keepends=True)}
            code = {"cell_type": "code", "execution_count": None, "id": f"{prefix}-values-code", "metadata": {}, "outputs": [], "source": '''print(df.to_string(index=False, line_width=200))

district_counts = (
    df["district"]
    .value_counts(dropna=False, sort=False)
    .rename_axis("district")
    .reset_index(name="records")
)
print(district_counts.to_string(index=False, formatters={"district": repr}))
'''.splitlines(keepends=True)}
            cells[after:after] = [md, code]
        document.setdefault("metadata", {}).setdefault("pyai", {})["structure_revision"] = 36
        path.write_text(json.dumps(document, ensure_ascii=False, indent=1) + "\n", encoding="utf-8", newline="\n")


def write_audit() -> None:
    rows = [
        {"project_need": "read and display complete source", "lesson": "3.1.3", "evidence": "read_csv and to_string(index=False)", "status": "ready"},
        {"project_need": "inspect shape, columns, dtypes, missingness", "lesson": "3.1.3", "evidence": "shape, columns, dtypes, info, isna().sum", "status": "ready"},
        {"project_need": "count raw district labels", "lesson": "3.1.3", "evidence": "value_counts(dropna=False, sort=False)", "status": "ready"},
        {"project_need": "sort by school and date without changing source", "lesson": "3.2.6 and 3.3.2", "evidence": "sort_values plus working copy", "status": "ready"},
        {"project_need": "quality flags and verification table", "lesson": "3.3.3-3.3.7", "evidence": "numeric conversion, missingness, constraints, duplicates, ordered reasons", "status": "ready"},
        {"project_need": "conditional counts and named aggregation", "lesson": "3.4.2", "evidence": "Boolean sum inside named aggregation", "status": "ready"},
        {"project_need": "deterministic mixed-direction ranking", "lesson": "3.4.4", "evidence": "ascending=[False, False, True]", "status": "ready"},
        {"project_need": "save and re-read two outputs", "lesson": "3.4.6", "evidence": "to_csv(index=False), read_csv, schema/count/first-row checks", "status": "ready"},
    ]
    AUDIT_JSON.write_text(json.dumps({"revision": 36, "project": "3.5A", "requirements": rows}, ensure_ascii=False, indent=2) + "\n", encoding="utf-8", newline="\n")
    lines = ["# Chapter 3 → Project 3A readiness audit v2", "", "| Project requirement | Prior lesson | Evidence | Status |", "|---|---|---|---|"]
    lines += [f"| {r['project_need']} | {r['lesson']} | `{r['evidence']}` | {r['status']} |" for r in rows]
    lines += ["", "The lesson pages and Notebooks use numbered topic groups while preserving a continuous reading path. Quizzes remain ten-question learning checks and the project checkers assess the two integrated programs."]
    AUDIT_MD.write_text("\n".join(lines) + "\n", encoding="utf-8", newline="\n")


def write_php(specs: dict) -> None:
    encoded = json.dumps(specs, ensure_ascii=False, separators=(",", ":"))
    upgrade = rf'''<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
$all = json_decode(<<<'JSON'
{encoded}
JSON, true, 512, JSON_THROW_ON_ERROR);
$results = [];
foreach ($all[$language] as $pagename => $replacements) {{
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (!str_contains($page->content, 'PYAI-V36-CHAPTER3-TOPICS')) {{
        foreach ($replacements as $old => $new) {{
            $count = substr_count($page->content, $old);
            if ($count !== 1) throw new RuntimeException("$shortname $pagename expected one heading, found $count: $old");
            $page->content = str_replace($old, $new, $page->content);
        }}
        if (str_contains($pagename, '3.4')) {{
            $rankingText = strpos($page->content, '>3.4.4 ');
            $proportionText = strpos($page->content, '>3.4.5 ');
            $transferText = strpos($page->content, '>3.4.7 ');
            if ($rankingText === false || $proportionText === false || $transferText === false) {{
                throw new RuntimeException("$shortname $pagename cannot locate 3.4 blocks");
            }}
            $rankingStart = strrpos(substr($page->content, 0, $rankingText), '<h3 style=');
            $transferStart = strrpos(substr($page->content, 0, $transferText), '<h3 style=');
            if ($rankingStart > $proportionText) {{
                $rankingBlock = substr($page->content, $rankingStart, $transferStart - $rankingStart);
                $page->content = substr_replace($page->content, '', $rankingStart, $transferStart - $rankingStart);
                $proportionText = strpos($page->content, '>3.4.5 ');
                $proportionStart = strrpos(substr($page->content, 0, $proportionText), '<h3 style=');
                $page->content = substr_replace($page->content, $rankingBlock, $proportionStart, 0);
            }}
        }}
        $count = substr_count($page->content, '</div>');
        if ($count !== 1) throw new RuntimeException("$shortname $pagename outer div count $count");
        $page->content = str_replace('</div>', '<p style="display:none">PYAI-V36-CHAPTER3-TOPICS</p></div>', $page->content);
        $page->timemodified = time();
        $DB->update_record('page', $page);
    }}
    $results[] = ['course' => $shortname, 'page' => $pagename, 'topics' => substr_count($page->content, 'style="{STYLE}')];
}}
rebuild_course_cache($course->id, true);
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
'''
    UPGRADE.write_text(upgrade, encoding="utf-8", newline="\n")
    verify = '''<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
$results = [];
$names = [
 'PYAI-INTRO' => [
  'Lesson 3.1: Tabular data, CSV, and pandas' => ['3.1.1','3.1.2','3.1.3','3.1.4','3.1.5','3.1.6','value_counts(dropna=False, sort=False)','to_string(index=False'],
  'Lesson 3.2: Data selection, filtering, and Boolean logic' => ['3.2.1','3.2.2','3.2.3','3.2.4','3.2.5','3.2.6','3.2.7'],
  'Lesson 3.3: Data cleaning and audit records' => ['3.3.1','3.3.2','3.3.3','3.3.4','3.3.5','3.3.6','3.3.7','3.3.8','records_to_verify'],
  'Lesson 3.4: Grouping and summary statistics' => ['3.4.1','3.4.2','3.4.3','3.4.4','3.4.5','3.4.6','3.4.7','ascending=[False,False,True]'],
 ],
 'PYAI-INTRO-JA' => [
  'レッスン3.1：表形式データ・CSV・pandas' => ['3.1.1','3.1.2','3.1.3','3.1.4','3.1.5','3.1.6','value_counts(dropna=False, sort=False)','to_string(index=False'],
  'レッスン3.2：データの選択・抽出とブール論理' => ['3.2.1','3.2.2','3.2.3','3.2.4','3.2.5','3.2.6','3.2.7'],
  'レッスン3.3：データのクリーニングと監査記録' => ['3.3.1','3.3.2','3.3.3','3.3.4','3.3.5','3.3.6','3.3.7','3.3.8','records_to_verify'],
  'レッスン3.4：グループ化と要約統計' => ['3.4.1','3.4.2','3.4.3','3.4.4','3.4.5','3.4.6','3.4.7','ascending=[False,False,True]'],
 ],
];
foreach ($names as $shortname => $pages) {
 $course=$DB->get_record('course',['shortname'=>$shortname],'*',MUST_EXIST);
 foreach ($pages as $pagename => $tokens) {
  $page=$DB->get_record('page',['course'=>$course->id,'name'=>$pagename],'*',MUST_EXIST);
  if (!str_contains($page->content,'PYAI-V36-CHAPTER3-TOPICS')) throw new RuntimeException("$shortname $pagename marker");
  $last=-1;
  foreach ($tokens as $token) { $pos=strpos($page->content,$token); if ($pos===false) throw new RuntimeException("$shortname $pagename missing $token"); if (preg_match('/^3\\.[1-4]\\.[0-9]+$/',$token)) { if ($pos <= $last) throw new RuntimeException("$shortname $pagename order $token"); $last=$pos; } }
  $results[]=['course'=>$shortname,'page'=>$pagename,'groups'=>substr_count($page->content,'border-left:4px')];
 }
 $quiznames=$shortname==='PYAI-INTRO-JA'
  ? ['理解度チェック：3.1 表形式データ・CSV・pandas','理解度チェック：3.2 データの選択・抽出とブール論理','理解度チェック：3.3 データのクリーニングと監査記録','理解度チェック：3.4 グループ化と要約統計']
  : ['Knowledge check: 3.1 Tabular data, CSV, and pandas','Knowledge check: 3.2 Data selection, filtering, and Boolean logic','Knowledge check: 3.3 Data cleaning and audit records','Knowledge check: 3.4 Grouping and summary statistics'];
 foreach($quiznames as $name){$quiz=$DB->get_record('quiz',['course'=>$course->id,'name'=>$name],'*',MUST_EXIST);if((int)$DB->count_records('quiz_slots',['quizid'=>$quiz->id])!==10)throw new RuntimeException("$shortname $name slots");}
}
echo json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
'''
    VERIFY.write_text(verify, encoding="utf-8", newline="\n")
    apply = '''#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}"
cd "$root_dir"
for shortname in PYAI-INTRO PYAI-INTRO-JA; do
  docker compose -f "$compose_file" exec -T -e PYTHON_COURSE_SHORTNAME="$shortname" moodle runuser -u www-data -- php < scripts/upgrade-python-chapter3-lessons-v36.php
done
docker compose -f "$compose_file" exec -T moodle runuser -u www-data -- php < scripts/verify-python-chapter3-lessons-v36.php
'''
    APPLY.write_text(apply, encoding="utf-8", newline="\n")


def verify_notebooks() -> None:
    for relative, headings in NOTEBOOK_HEADINGS.items():
        document = json.loads((TEMPLATES / relative).read_text(encoding="utf-8"))
        by_id = {cell.get("id"): "".join(cell.get("source", [])) for cell in document["cells"]}
        for cell_id, heading in headings.items():
            if not by_id.get(cell_id, "").startswith(heading):
                raise RuntimeError(f"{relative} missing heading {heading}")
        if relative.endswith("07_tables_csv_pandas.ipynb"):
            text = json.dumps(document, ensure_ascii=False)
            for token in ["value_counts(dropna=False, sort=False)", "to_string(index=False"]:
                if token not in text:
                    raise RuntimeError(f"{relative} missing {token}")


def main() -> None:
    specs = page_specs()
    update_notebooks()
    verify_notebooks()
    write_audit()
    write_php(specs)
    print({"pages": 8, "notebooks": 8, "chapter": 3, "revision": 36})


if __name__ == "__main__":
    main()
