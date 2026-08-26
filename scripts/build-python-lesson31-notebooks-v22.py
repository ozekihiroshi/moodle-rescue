#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for tabular data, CSV, and pandas."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id: str, text: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code(cell_id: str, text: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


LOCATOR = '''from pathlib import Path
import pandas as pd


def find_course_data(filename):
    """Find course data without depending on the Notebook start directory."""
    roots = [
        Path.cwd(),
        *Path.cwd().parents,
        Path.home() / "work",
        Path("/opt/python-lab/course-materials"),
    ]
    checked = []
    for root in roots:
        for candidate in (root / "data" / filename, root / filename):
            candidate = candidate.expanduser()
            if candidate in checked:
                continue
            checked.append(candidate)
            if candidate.is_file():
                return candidate
    locations = "\\n".join(f"- {path}" for path in checked)
    raise FileNotFoundError(
        f"Course data file {filename!r} was not found. Checked:\\n{locations}"
    )


data_file = find_course_data("learning-centres-practice.csv")
print("Working directory:", Path.cwd())
print("Loading:", data_file.resolve())
'''


def notebook(language: str) -> dict:
    if language == "ja":
        cells = [
            md("l31-ja-title", "# 3.1 — 表形式データ・CSV・pandas\n\n2.3で扱った辞書のリストを表へ移し、CSVを読み込み、列の意味と型を確認してから計算へ進みます。"),
            md("l31-ja-table", "## 表は行・列・セルで観測をそろえる\n\nこの教材では、一行を一つのセンター・月の観測、一列を同じ意味を持つ変数、一つのセルを一観測の一変数の値として扱います。最初の行の列名と、各列に期待する型・単位・欠損規則を合わせてスキーマと考えます。"),
            code("l31-ja-records", "import pandas as pd\n\nrecords = [\n    {\"month\": \"2026-01\", \"centre_id\": \"C001\", \"registered\": 32, \"completed\": 24},\n    {\"month\": \"2026-01\", \"centre_id\": \"C002\", \"registered\": 27, \"completed\": 18},\n]\n\nrecords_df = pd.DataFrame(records)\nrecords_df\n"),
            md("l31-ja-csv", "## CSVは表計算ファイルではなくテキスト形式\n\nCSVでは通常、一行目がヘッダー、後続行がレコード、カンマがフィールドの区切りです。値にカンマや改行を含む場合は引用符が必要です。文字コード、区切り文字、欠損を表す空欄、先頭0を保持したい識別子を意識して読み込みます。"),
            md("l31-ja-path", "## 相対パスは現在の作業フォルダを基準にする\n\n`data/file.csv`はNotebookファイルの場所ではなく、カーネルの現在の作業フォルダから解釈されます。Python Labを別の入口から開いても教材CSVを読めるよう、次の補助関数は現在位置、その親、サーバー上の学習領域、配布元を順に調べます。表示される`Working directory`と`Loading`を必ず確認してください。"),
            code("l31-ja-locate", LOCATOR),
            md("l31-ja-load", "## read_csvで読み込み方を明示する\n\n`pandas`は慣例的に`pd`という名前で読み込みます。`read_csv()`はCSVから`DataFrame`を作ります。ここではUTF-8を明記し、コードや年月を計算対象ではない文字列として保持します。他のデータでは区切り文字や文字コードが異なることがあります。"),
            code("l31-ja-load-code", "df = pd.read_csv(\n    data_file,\n    encoding=\"utf-8\",\n    dtype={\"centre_id\": \"string\", \"month\": \"string\"},\n)\nprint(df.head(3))\n"),
            md("l31-ja-inspect", "## 計算の前に形・列名・型・欠損を確認する\n\n`head()`で値の並び、`shape`で行数と列数、`columns`で列名、`dtypes`と`info()`で推定型、`isna().sum()`で欠損数を確認します。想定と違う場合は、計算を始めず読み込み方か入力データを調べます。"),
            code("l31-ja-inspect-code", "print(\"Shape:\", df.shape)\nprint(\"Columns:\", df.columns.tolist())\nprint(\"Dtypes:\")\nprint(df.dtypes)\nprint(\"Missing values:\")\nprint(df.isna().sum())\nprint(\"Info:\")\ndf.info()\n"),
            md("l31-ja-series", "## 一列はSeries、複数列の表はDataFrame\n\n`df[\"registered\"]`は一次元の`Series`を返します。`df[[\"registered\"]]`は一列を持つ二次元の`DataFrame`です。列名は文字列として正確に指定し、複数列では外側と内側の二組の角括弧を使います。"),
            code("l31-ja-series-code", "registered_series = df[\"registered\"]\nregistered_table = df[[\"registered\"]]\nprint(type(registered_series).__name__, registered_series.shape)\nprint(type(registered_table).__name__, registered_table.shape)\n"),
            md("l31-ja-derived", "## 列どうしの計算は全行へ適用される\n\nSeriesどうしの演算は行の対応を保って全行へ適用されます。`assign()`を使うと元の`df`を直接変更せず、計算列を追加した新しいDataFrameを作れます。分母0や不正値の扱いは3.3で詳しく検討します。"),
            code("l31-ja-derived-code", "report = df.assign(\n    completion_rate=df[\"completed\"] / df[\"registered\"] * 100\n)\nprint(report[[\"month\", \"centre_name\", \"registered\", \"completed\", \"completion_rate\"]].head())\n"),
            md("l31-ja-index", "## DataFrameのindexと業務上の識別子を区別する\n\n左端のindexはpandasが各行へ付けるラベルで、`centre_id`の代わりではありません。CSVへ保存するときに`index=False`を指定すれば、不要なindex列を書き出しません。保存後はパスと先頭行を確認します。"),
            code("l31-ja-save", "preview_file = Path.cwd() / \"monthly-centres-preview.csv\"\nreport.head(5).to_csv(preview_file, index=False, encoding=\"utf-8\")\nprint(\"Saved:\", preview_file.resolve())\nprint(preview_file.read_text(encoding=\"utf-8\").splitlines()[0])\n"),
            md("l31-ja-errors", "## 読み込み時の代表的な問題を切り分ける\n\n`FileNotFoundError`なら表示された現在位置と探索先、列が一列だけなら区切り文字、文字化けや`UnicodeDecodeError`なら文字コード、数値列が文字列なら単位記号・空白・不正値を確認します。読み込めたことと、正しく読めたことは同じではありません。"),
            md("l31-ja-transfer", "## 応用練習\n\n2.3のセンターレコードからDataFrameを作り、CSVへ保存して再度読み込んでください。読み込み前後で`shape`、列名、識別子、人数合計が一致することを確認し、`attendance_rate`と`completion_rate`を追加します。次の3.2では、この表から条件に合う行と必要な列を選びます。"),
            code("l31-ja-work", "# ここに応用練習の解答を書きます。\n"),
            md("l31-ja-complete", "## 完了確認\n\n行・列・セル・スキーマ、CSVのヘッダー・区切り・文字コード、作業フォルダとパス、DataFrameとSeries、`read_csv()`、`head()`・`shape`・`columns`・`dtypes`・`info()`・欠損確認、列計算、`to_csv(index=False)`を説明できたら保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l31-en-title", "# 3.1 — Tabular data, CSV, and pandas\n\nMove the list of dictionaries from 2.3 into a table, load CSV data, and inspect column meaning and types before calculating."),
            md("l31-en-table", "## A table aligns observations in rows, columns, and cells\n\nIn this course, one row is one centre-month observation, one column is a variable with consistent meaning, and one cell is one variable value for one observation. Treat the header plus each column's expected type, unit, and missing-value rule as its schema."),
            code("l31-en-records", "import pandas as pd\n\nrecords = [\n    {\"month\": \"2026-01\", \"centre_id\": \"C001\", \"registered\": 32, \"completed\": 24},\n    {\"month\": \"2026-01\", \"centre_id\": \"C002\", \"registered\": 27, \"completed\": 18},\n]\nrecords_df = pd.DataFrame(records)\nrecords_df\n"),
            md("l31-en-csv", "## CSV is a text format, not a spreadsheet workbook\n\nA CSV commonly uses its first line as a header, later lines as records, and commas as field delimiters. A value containing a comma or newline needs quoting. Consider encoding, delimiter, empty fields that represent missing data, and identifiers whose leading zero must be retained."),
            md("l31-en-path", "## A relative path starts from the current working directory\n\n`data/file.csv` is interpreted from the kernel's current working directory, not necessarily the Notebook file's directory. To work from different Python Lab entry points, the helper below checks the current location, its parents, the server work area, and the distributed materials. Always inspect the printed `Working directory` and `Loading` paths."),
            code("l31-en-locate", LOCATOR),
            md("l31-en-load", "## Make CSV loading assumptions explicit\n\nImport `pandas` conventionally as `pd`. `read_csv()` creates a `DataFrame`. Here UTF-8 is explicit, and centre codes and months remain strings rather than quantities. Other files may require a different delimiter or encoding."),
            code("l31-en-load-code", "df = pd.read_csv(\n    data_file,\n    encoding=\"utf-8\",\n    dtype={\"centre_id\": \"string\", \"month\": \"string\"},\n)\nprint(df.head(3))\n"),
            md("l31-en-inspect", "## Inspect shape, names, types, and missingness before calculation\n\nUse `head()` for representative layout, `shape` for row and column counts, `columns` for exact names, `dtypes` and `info()` for inferred types, and `isna().sum()` for missing counts. If they differ from the expected schema, investigate the input or loading options before calculation."),
            code("l31-en-inspect-code", "print(\"Shape:\", df.shape)\nprint(\"Columns:\", df.columns.tolist())\nprint(\"Dtypes:\")\nprint(df.dtypes)\nprint(\"Missing values:\")\nprint(df.isna().sum())\nprint(\"Info:\")\ndf.info()\n"),
            md("l31-en-series", "## One column is a Series; a multi-column table is a DataFrame\n\n`df[\"registered\"]` returns a one-dimensional `Series`. `df[[\"registered\"]]` returns a two-dimensional `DataFrame` with one column. Specify column names exactly and use two bracket pairs for a list of selected columns."),
            code("l31-en-series-code", "registered_series = df[\"registered\"]\nregistered_table = df[[\"registered\"]]\nprint(type(registered_series).__name__, registered_series.shape)\nprint(type(registered_table).__name__, registered_table.shape)\n"),
            md("l31-en-derived", "## Operations between columns apply to every aligned row\n\nSeries arithmetic applies across corresponding rows. `assign()` can create a new DataFrame containing a derived column without directly changing the source `df`. Lesson 3.3 examines zero denominators and invalid values in detail."),
            code("l31-en-derived-code", "report = df.assign(\n    completion_rate=df[\"completed\"] / df[\"registered\"] * 100\n)\nprint(report[[\"month\", \"centre_name\", \"registered\", \"completed\", \"completion_rate\"]].head())\n"),
            md("l31-en-index", "## Distinguish the DataFrame index from an operational identifier\n\nThe index shown at the left is a pandas row label, not a replacement for `centre_id`. Use `index=False` when exporting if that extra index column is not part of the data. Check the saved path and header afterward."),
            code("l31-en-save", "preview_file = Path.cwd() / \"monthly-centres-preview.csv\"\nreport.head(5).to_csv(preview_file, index=False, encoding=\"utf-8\")\nprint(\"Saved:\", preview_file.resolve())\nprint(preview_file.read_text(encoding=\"utf-8\").splitlines()[0])\n"),
            md("l31-en-errors", "## Separate common loading failures\n\nFor `FileNotFoundError`, inspect the working directory and checked paths. If every field becomes one column, inspect the delimiter. For mojibake or `UnicodeDecodeError`, inspect encoding. If a numeric column becomes text, inspect units, spaces, and invalid values. Successfully loading a file is not proof that it was loaded correctly."),
            md("l31-en-transfer", "## Transfer exercise\n\nCreate a DataFrame from the centre records in 2.3, save it to CSV, and load it again. Confirm that shape, columns, identifiers, and count totals agree before and after. Add `attendance_rate` and `completion_rate`. Lesson 3.2 then selects rows and columns that answer a question."),
            code("l31-en-work", "# Write the transfer solution here.\n"),
            md("l31-en-complete", "## Completion check\n\nWhen you can explain rows, columns, cells, schema, CSV headers, delimiters and encoding, working directories and paths, DataFrames and Series, `read_csv()`, `head()`, `shape`, `columns`, `dtypes`, `info()`, missingness, column arithmetic, and `to_csv(index=False)`, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "3.1", "language": language, "concepts": [f"T{i:02d}" for i in range(1, 11)], "revision": 22},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "07_tables_csv_pandas.ipynb", "ja": TEMPLATES / "ja/07_tables_csv_pandas.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "interpret rows columns cells observations variables and schema",
        "recognise CSV headers delimiters quoting encoding and missing fields",
        "resolve course data independently of the Notebook start directory",
        "load CSV with explicit encoding and identifier dtypes",
        "inspect head shape columns dtypes info and missing counts before analysis",
        "distinguish a pandas Series from a DataFrame",
        "calculate a derived column with aligned vectorised arithmetic",
        "distinguish the DataFrame index from an operational identifier",
        "export CSV without an unintended index and verify the saved file",
        "diagnose path delimiter encoding and dtype loading problems",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "3.1 Tabular data, CSV, and pandas",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"T{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L31R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/07_tables_csv_pandas.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/07_tables_csv_pandas.ipynb",
        },
        "implementation": "scripts/upgrade-python-lesson31-v22.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-3-1-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
