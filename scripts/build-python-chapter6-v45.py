from __future__ import annotations

import json
from pathlib import Path
import shutil


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "sample-content" / "introduction-to-python"
TEMPLATES = CONTENT / "python-lab" / "templates"
PROJECT = CONTENT / "python-lab" / "project-files" / "projects" / "clinic-stock-scaleup"
LAB = Path("/mnt/d/workspace/python-lab-rescue/course-materials")


def md(text: str) -> dict:
    return {"cell_type": "markdown", "metadata": {}, "source": [text.strip() + "\n"]}


def code(text: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "metadata": {}, "outputs": [], "source": [text.strip() + "\n"]}


def notebook(cells: list[dict], prefix: str) -> dict:
    for number, cell in enumerate(cells, 1):
        cell["id"] = f"{prefix}-{number:02d}"
    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def setup(ja: bool) -> str:
    return """
from pathlib import Path
import pandas as pd

project = Path.cwd() / "projects" / "clinic-stock-scaleup"
fixture = project / "data" / "clinic-stock-fixture.csv"
print("Project found:", project.is_dir())
print("Fixture found:", fixture.is_file())
"""


def lesson61(ja: bool) -> dict:
    cells = [
        md("# 6.1 — 読み込む前に調べる" if ja else "# 6.1 — Inspect before loading"),
        md("ファイルの大きさ、列、一行の意味を確認し、必要な列だけを読む計画を作ります。" if ja else "Inspect size, columns, and record meaning, then make a selective reading plan."),
        code(setup(ja)),
        md("## 6.1.1 原資料を標本で確認する" if ja else "## 6.1.1 Inspect a source sample"),
        code("""
print("Bytes:", fixture.stat().st_size)
sample = pd.read_csv(fixture, nrows=5)
print("Columns:", sample.columns.tolist())
display(sample)
"""),
        md("## 6.1.2 必要列とメモリを比較する" if ja else "## 6.1.2 Compare selected columns and memory"),
        code("""
needed = ["district", "medicine", "stockout_hours", "patients_turned_away"]
all_columns = pd.read_csv(fixture)
selected = pd.read_csv(fixture, usecols=needed)
print("All columns bytes:", all_columns.memory_usage(deep=True).sum())
print("Selected bytes:", selected.memory_usage(deep=True).sum())
display(selected.head())
"""),
        md("## 6.1.3 チャンクの行数を照合する" if ja else "## 6.1.3 Reconcile chunk lengths"),
        code("""
lengths = []
for chunk in pd.read_csv(fixture, usecols=needed, chunksize=10):
    lengths.append(len(chunk))
print("Chunk lengths:", lengths)
print("Processed:", sum(lengths), "Source:", len(all_columns))
assert sum(lengths) == len(all_columns)
"""),
        md("## 確認\n`nrows`、`usecols`、`dtype`、`chunksize`がそれぞれ何を制御するか説明してください。" if ja else "## Check\nExplain separately what `nrows`, `usecols`, `dtype`, and `chunksize` control."),
    ]
    return notebook(cells, "ja-61" if ja else "en-61")


def lesson62(ja: bool) -> dict:
    cells = [
        md("# 6.2 — チャンクを越えて正しく集計する" if ja else "# 6.2 — Aggregate correctly across chunks"),
        md("グループの合計と件数を小さな辞書へ保持し、チャンク境界に依存しない集計を作ります。" if ja else "Retain group totals and counts in small state that does not depend on chunk boundaries."),
        code(setup(ja)),
        code("""
def aggregate(path, chunksize):
    totals = {}
    processed = 0
    for chunk in pd.read_csv(path, chunksize=chunksize):
        processed += len(chunk)
        chunk["stockout_day"] = chunk["stockout_hours"].gt(0).astype(int)
        part = chunk.groupby(["district", "medicine"], as_index=False).agg(
            clinic_days=("date", "size"),
            stockout_days=("stockout_day", "sum"),
            patients_turned_away=("patients_turned_away", "sum"),
        )
        for row in part.itertuples(index=False):
            key = (row.district, row.medicine)
            current = totals.setdefault(key, {"clinic_days": 0, "stockout_days": 0, "patients_turned_away": 0})
            current["clinic_days"] += row.clinic_days
            current["stockout_days"] += row.stockout_days
            current["patients_turned_away"] += row.patients_turned_away
    return processed, totals
"""),
        md("## 6.2.1 異なるチャンクサイズを比較する" if ja else "## 6.2.1 Compare different chunk sizes"),
        code("""
processed7, totals7 = aggregate(fixture, 7)
processed13, totals13 = aggregate(fixture, 13)
print("Rows:", processed7, processed13)
print("Same totals:", totals7 == totals13)
assert totals7 == totals13
"""),
        md("## 6.2.2 分子と分母から率を作る" if ja else "## 6.2.2 Build rates from numerators and denominators"),
        code("""
rows = []
for (district, medicine), values in totals7.items():
    rows.append({
        "district": district,
        "medicine": medicine,
        **values,
        "stockout_rate": values["stockout_days"] / values["clinic_days"] * 100,
    })
summary = pd.DataFrame(rows).sort_values("patients_turned_away", ascending=False)
display(summary)
"""),
        md("## 確認\nチャンクごとの`stockout_rate`を単純平均してはいけない理由を、件数の違いを使って説明してください。" if ja else "## Check\nUse unequal group sizes to explain why chunk-level `stockout_rate` values must not simply be averaged."),
    ]
    return notebook(cells, "ja-62" if ja else "en-62")


def lesson63(ja: bool) -> dict:
    cells = [
        md("# 6.3 — 照合して再現可能にする" if ja else "# 6.3 — Reconcile and reproduce"),
        md("小さなfixture、全行照合、保存後の再読込を、一つの独立した確認手順にします。" if ja else "Combine a small fixture, whole-file reconciliation, and reopening saved output into an independent check."),
        code(setup(ja)),
        md("## 6.3.1 原本を人間が確認できる形で見る" if ja else "## 6.3.1 Display the source for human inspection"),
        code("""
records = pd.read_csv(fixture)
print("Rows:", len(records), "Columns:", len(records.columns))
print(records.to_string(index=False))
"""),
        md("## 6.3.2 件数を照合する" if ja else "## 6.3.2 Reconcile row counts"),
        code("""
required_numeric = ["opening_units", "received_units", "dispensed_units", "closing_units", "stockout_hours", "patients_turned_away"]
work = records.copy(deep=True)
for column in required_numeric:
    work[column] = pd.to_numeric(work[column], errors="coerce")
invalid = work[required_numeric].isna().any(axis=1) | work[required_numeric].lt(0).any(axis=1)
analysis = work.loc[~invalid].copy()
review = work.loc[invalid].copy()
print("Source:", len(work), "Analysis:", len(analysis), "Review:", len(review))
assert len(work) == len(analysis) + len(review)
"""),
        md("## 6.3.3 保存結果を再読込する" if ja else "## 6.3.3 Reopen saved output"),
        code("""
output = project / "output" / "lesson63_check.csv"
output.parent.mkdir(exist_ok=True)
check_table = analysis.groupby(["district", "medicine"], as_index=False).agg(records=("date", "size"))
check_table.to_csv(output, index=False)
saved = pd.read_csv(output)
assert list(saved.columns) == list(check_table.columns)
assert len(saved) == len(check_table)
display(saved)
"""),
        md("## 確認\nこの確認が保証することと、まだ保証しないことを一つずつ挙げてください。" if ja else "## Check\nState one thing this workflow establishes and one thing it still does not prove."),
    ]
    return notebook(cells, "ja-63" if ja else "en-63")


def project_notebook(ja: bool) -> dict:
    cells = [
        md("# 6.4 — 診療所医薬品在庫切れ対応" if ja else "# 6.4 — Clinic medicine stock-out response"),
        md("Notebookは提出しません。fixtureで理解した後、12万件を生成し、スターターを完成させます。" if ja else "The Notebook is not submitted. Understand the fixture, generate 120,000 records, then complete the starter."),
        code(setup(ja)),
        md("## 課題仕様を読む" if ja else "## Read the project contract"),
        code("print((project / 'PROJECT_BRIEF.md').read_text(encoding='utf-8'))"),
        md("## 48件のfixtureを確認する" if ja else "## Inspect the 48-row fixture"),
        code("records = pd.read_csv(fixture)\nprint(records.to_string(index=False))"),
        md("## 12万件を生成する" if ja else "## Generate 120,000 records"),
        code("""
import subprocess
import sys

source = project / "data" / "clinic-stock-120000.csv"
if not source.is_file():
    subprocess.run([sys.executable, str(project / "generate_clinic_stock_data.py"), str(source), "--rows", "120000"], check=True)
print("Source:", source)
print("Bytes:", source.stat().st_size)
"""),
        md("## プログラムを実行する" if ja else "## Run the program"),
        code("!python projects/clinic-stock-scaleup/clinic_stock_scaleup.py"),
        md("## 自動確認を実行する" if ja else "## Run the checker"),
        code("!python projects/clinic-stock-scaleup/check_clinic_stock_scaleup.py"),
        md("## 成果物を確認する" if ja else "## Inspect the deliverables"),
        code("""
from IPython.display import Image, display
summary = project / "output" / "clinic_stock_summary.csv"
figure = project / "output" / "clinic_stock_evidence.png"
if summary.is_file() and figure.is_file():
    display(pd.read_csv(summary))
    display(Image(filename=figure))
else:
    print("Complete and run the program before inspecting outputs.")
"""),
    ]
    return notebook(cells, "ja-p6" if ja else "en-p6")


def write_notebook(name: str, document: dict, ja: bool) -> None:
    target = TEMPLATES / ("ja" if ja else "") / name
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(document, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    lab = LAB / ("ja" if ja else "") / name
    lab.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(target, lab)


def copy_projects() -> None:
    for root in [CONTENT / "python-lab" / "project-files", LAB]:
        destination = root / "projects" / "clinic-stock-scaleup"
        if destination.resolve() != PROJECT.resolve():
            shutil.copytree(PROJECT, destination, dirs_exist_ok=True)
        ja = root / "ja" / "projects" / "clinic-stock-scaleup"
        shutil.copytree(PROJECT, ja, dirs_exist_ok=True)
        ja_brief = (CONTENT / "project-6-brief-ja.md").read_text(encoding="utf-8")
        (ja / "PROJECT_BRIEF.md").write_text(ja_brief, encoding="utf-8")
        (ja / "README.md").write_text(
            "# 診療所医薬品在庫切れ対応\n\n編集するのは`clinic_stock_scaleup.py`だけです。"
            "READMEの関数順に実装し、fixtureで確認してから12万件へ進みます。"
            "入力DataFrameと原本CSVを変更せず、率は全チャンクの分子と分母を統合してから計算してください。\n",
            encoding="utf-8",
        )


def build() -> None:
    for ja in (False, True):
        write_notebook("20_inspect_before_loading.ipynb", lesson61(ja), ja)
        write_notebook("21_chunked_aggregation.ipynb", lesson62(ja), ja)
        write_notebook("22_reconcile_reproduce.ipynb", lesson63(ja), ja)
        write_notebook("P6_clinic_stock_scaleup.ipynb", project_notebook(ja), ja)
    copy_projects()
    print("built Chapter 6 notebooks and project in both repositories")


if __name__ == "__main__":
    build()
