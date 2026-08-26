#!/usr/bin/env python3
"""Add the human observation-to-decision work record to Project 3A."""
from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
PROJECTS = BASE / "python-lab/project-files"


def write_text(path: Path, text: str) -> None:
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        stream.write(text)


def insert_once(path: Path, marker: str, addition: str) -> None:
    text = path.read_text(encoding="utf-8")
    if text.count(marker) != 1:
        raise RuntimeError(f"Expected one marker in {path}: {marker}")
    write_text(path, text.replace(marker, addition + marker))


def replace_once(path: Path, old: str, new: str) -> None:
    text = path.read_text(encoding="utf-8")
    if text.count(old) != 1:
        raise RuntimeError(f"Expected one block in {path}: {old[:60]!r}")
    write_text(path, text.replace(old, new))


EN_OBSERVATION = """## Observe the source before reading the rules

Open the Project Notebook and display all 37 records as a table before implementing a function. At this stage, do not delete, correct, colour, or automatically label any record. The purpose is to understand the source and form hypotheses that the later program will test reproducibly.

Look for the following without trying to prove the final answer yet:

- what one row represents and how many dates appear for each school
- which columns should be numeric and which are text
- blanks or values that do not look numeric
- negative values or relationships that appear impossible
- repeated school/date combinations
- whitespace or capitalisation differences in district labels
- the totals and comparisons needed to decide a delivery priority

Then display the column names, inferred dtypes, and missing-value counts. A numeric-looking column inferred as `object` is useful evidence: ask what prevented pandas from treating the whole column as numeric. Visual inspection is not the official quality decision. It is the human observation that helps you design explicit rules before Python applies them to every row.

The Notebook contains an observation memo. Complete it in your own words before opening the expected checkpoints.

### From source to decision

| Stage | Human question | Python work |
|---|---|---|
| Inspect source | What do rows, columns, and values mean? | Display the complete table |
| Form hypotheses | What may be blank, contradictory, duplicated, or inconsistent? | Inspect shape, columns, dtypes, and missingness |
| Confirm rules | What may be normalised, and what must be reviewed? | Reproduce the published rules as flags |
| Define analysis data | Which records can support the decision? | Separate verification and valid records |
| Compare schools | What evidence should drive priority? | Aggregate the published measures |
| Make the decision | Which school should receive the extra visit? | Rank deterministically and select the first row |
| Check the result | Can another person review the evidence? | Save CSVs, re-read them, and run the checker |

"""


JA_OBSERVATION = """## 品質規則を読む前に原資料を観察する

関数の実装を始める前にProject Notebookを開き、37件すべてを表として表示します。この段階では、値を削除、修正、色付け、自動判定しません。原資料の意味をつかみ、後でプログラムによって再現する仮説を作ることが目的です。

最終的な正解を決めようとせず、次の点を観察してください。

- 一行が何を表し、一校について何日分の記録があるか
- どの列を数値として、どの列を文字列として扱うか
- 空欄や数値ではないように見える値がないか
- 負数や、項目間で矛盾して見える値がないか
- 同じ学校・同じ日付らしい行が複数ないか
- 地区名の空白や大文字・小文字が揃っているか
- 配送優先順位を判断するには何を集計・比較すべきか

続いて、列名、pandasが推定したデータ型、列ごとの欠損数を表示します。本来は数値に見える列が`object`になっていれば、それ自体が重要な観察です。「何が数値列としての読込を妨げたのか」と考えてください。目視は正式な品質判定ではありません。すべての行へ同じ規則を適用する前に、人間が処理方法を設計するための仮説形成です。

Notebookには原資料の観察メモがあります。代表値を開く前に、自分の言葉で記入してください。

### 原資料から意思決定まで

| 工程 | 人間が考えること | Pythonで行うこと |
|---|---|---|
| 原資料の確認 | 行・列・値は何を意味するか | 全件を表として表示する |
| 問題の予想 | 空欄、矛盾、重複、表記ゆれはどこにありそうか | 形、列名、推定型、欠損数を確認する |
| 品質規則の確定 | 何を正規化し、何を要確認とするか | 公開規則をフラグとして再現する |
| 分析対象の確定 | 判断に使える記録はどれか | 要確認行と有効行を分離する |
| 学校別比較 | 優先順位の根拠は何か | 公開された指標を集計する |
| 意思決定 | 追加訪問先をどこにするか | 再現可能に順位付けして1位を得る |
| 結果の確認 | 他者が根拠を確認できるか | CSV保存・再読込・自動確認を行う |

"""


EN_FINAL = """## Work record, checking, and submission

Follow this order: observe the complete source; write your initial memo; read the quality rules; sketch the pipeline; implement the eight functions; inspect the two result tables; write the decision memo; compare the published checkpoints; then run the checker.

The Project Notebook is not a scratch file to discard. It is the human-readable analysis record. Save it after completing the observation, processing-plan, and final-decision sections and after running the cells that display the complete source, structural checks, and latest output tables. Do not paste the expected answer into the observation memo; record what you actually noticed before implementation.

### Assessment

| Deliverable | Points | Evidence |
|---|---:|---|
| `P3A_school_meal_delivery_review.ipynb` | 50 | complete source and structure shown; initial observation and plan completed; current result tables shown; final decision supported by measures and a limitation |
| `meal_delivery_review.py` | 50 | eight functions implement the public contract; source is protected; outputs are reproducible; all ten automatic checks pass |

This gives substantial credit for turning source data into a state a person can inspect and use for a decision, even while the program still needs correction. The Notebook and program must nevertheless describe the same latest run.

The program combines APIs already practised in 3.3 and 3.4, including `pd.to_numeric(..., errors="coerce")`, `copy(deep=True)`, `duplicated(..., keep=False)`, Boolean masks, `groupby().agg()`, mixed-direction `sort_values()`, `reset_index()`, CSV saving, and output re-reading.

Run the checker after your own inspection:

```text
python projects/school-meal-review/check_meal_delivery_review.py
```

The checker tests only the public processing contract. Submit these two files after saving the executed Notebook and obtaining `ALL TESTS PASSED` and `REVIEW READY`:

1. `P3A_school_meal_delivery_review.ipynb`
2. `meal_delivery_review.py`
"""


JA_FINAL = """## 作業記録、確認、提出

全原資料の観察、最初の観察メモ、品質規則の確認、処理計画、8関数の実装、二つの結果表の確認、最終判断メモ、代表値との照合、自動確認の順に進めます。

Project Notebookは作業後に捨てるメモではなく、人間が読める分析記録です。全原資料、構造確認、最新結果表を表示した状態で、観察、処理計画、最終判断の三つの記入欄を完成させ、Ctrl+Sで保存します。観察メモへ期待結果を書き写さず、実装前に自分が気付いたことを残してください。

### 評価

| 提出物 | 配点 | 確認する根拠 |
|---|---:|---|
| `P3A_school_meal_delivery_review.ipynb` | 50点 | 全原資料と構造の表示、観察と計画、最新結果表、数値根拠と限界を含む最終判断 |
| `meal_delivery_review.py` | 50点 | 8関数が公開仕様を実装し、原本を保護し、結果を再現でき、10項目の自動確認に合格 |

この配点では、プログラムに修正が残っていても、原資料を人間が確認し、判断に使える表へ加工しようとした過程へ相応の点数が与えられます。ただし、Notebookとプログラムは同じ最新実行を説明する必要があります。

この課題で使う`pd.to_numeric(..., errors="coerce")`、`copy(deep=True)`、`duplicated(..., keep=False)`、ブールマスク、`groupby().agg()`、方向の異なる`sort_values()`、`reset_index()`、CSV保存と再読込は3.3・3.4までに練習した内容です。

自分で結果を確認した後、次を実行します。

```text
python projects/school-meal-review/check_meal_delivery_review.py
```

確認プログラムは公開された処理仕様だけを検査します。Notebookを実行済みの状態で保存し、`ALL TESTS PASSED`と`REVIEW READY`を確認したら、次の二つを提出します。

1. `P3A_school_meal_delivery_review.ipynb`
2. `meal_delivery_review.py`
"""


def update_briefs() -> None:
    paths = [
        (BASE / "project-3a-brief-en.md", EN_OBSERVATION, "## Processing order and verification rules\n", EN_FINAL),
        (PROJECTS / "projects/school-meal-review/PROJECT_BRIEF.md", EN_OBSERVATION, "## Processing order and verification rules\n", EN_FINAL),
        (BASE / "project-3a-brief-ja.md", JA_OBSERVATION, "## 判定する順序と品質規則\n", JA_FINAL),
        (PROJECTS / "ja/projects/school-meal-review/PROJECT_BRIEF.md", JA_OBSERVATION, "## 判定する順序と品質規則\n", JA_FINAL),
    ]
    for path, observation, marker, final in paths:
        insert_once(path, marker, observation)
        text = path.read_text(encoding="utf-8")
        heading = "## Work in stages, check, and submit\n" if "project-3a-brief-en" in str(path) or "/projects/school-meal-review/" in str(path).replace("\\", "/") and "/ja/" not in str(path).replace("\\", "/") else "## 段階的に実装し、確認して提出する\n"
        start = text.find(heading)
        if start < 0:
            raise RuntimeError(f"Final-section heading not found in {path}")
        write_text(path, text[:start] + final)


def update_readmes() -> None:
    en = PROJECTS / "projects/school-meal-review/README.md"
    ja = PROJECTS / "ja/projects/school-meal-review/README.md"
    replace_once(en, "Before the checker, inspect both generated CSVs and compare the published screen values, flag counts, group count, and first-row checks. Completion requires all ten checks, `ALL TESTS PASSED`, and `REVIEW READY`.", "Before implementation, use the Project Notebook to display all 37 rows, inspect structure, and complete the observation and plan. After implementation, inspect the generated tables and complete the decision memo. Save and submit both `P3A_school_meal_delivery_review.ipynb` and `meal_delivery_review.py`. The Notebook and program are worth 50 points each.")
    replace_once(ja, "自動確認の前に2つのCSVを開き、公開された画面表示、フラグ件数、集計数、1位の代表値と照合します。10項目すべてと`ALL TESTS PASSED`、`REVIEW READY`が完成条件です。", "実装前にProject Notebookで37行すべてと構造を確認し、観察メモと計画を記入します。実装後は結果表を確認し、最終判断メモを完成させます。`P3A_school_meal_delivery_review.ipynb`と`meal_delivery_review.py`の二つを保存して提出します。配点は各50点です。")


def markdown_cell(cell_id: str, text: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code_cell(cell_id: str, text: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


def update_notebook(path: Path, ja: bool) -> None:
    document = json.loads(path.read_text(encoding="utf-8"))
    prefix = "p3a-ja" if ja else "p3a-en"
    additions = {f"{prefix}-03b", f"{prefix}-03c", f"{prefix}-04b", f"{prefix}-08c"}
    document["cells"] = [cell for cell in document["cells"] if cell.get("id") not in additions]
    by_id = {cell.get("id"): cell for cell in document["cells"]}
    by_id[f"{prefix}-02"]["source"] = (("## 原資料37件をすべて表として見る\n\nこの段階では修正、削除、色付け、自動判定をしません。列を揃えた表として全体を眺め、一行の意味、繰り返し、空欄、不自然に見える値、表記の違いを探します。" if ja else "## Display all 37 source records as a table\n\nDo not correct, delete, colour, or automatically label records yet. View the complete aligned table and look for row meaning, repetition, blanks, surprising values, and inconsistent labels." ).splitlines(keepends=True))
    by_id[f"{prefix}-03"]["source"] = '''from pathlib import Path
import pandas as pd

project = Path.cwd() / "projects" / "school-meal-review"
source_file = project / "data" / "school-meals-practice.csv"
records = pd.read_csv(source_file)

print("Project found:", project.is_dir())
print("Source found:", source_file.is_file())
print("Rows:", len(records))
print("Columns:", len(records.columns))
display(records)
'''.splitlines(keepends=True)
    structure = '''print("Columns:")
print(records.columns.tolist())

print("\\nInferred dtypes:")
print(records.dtypes)

print("\\nMissing values by column:")
print(records.isna().sum())
'''
    observation = ("""## 原資料を見て気付いたこと — ここを自分の言葉で編集する

- 一行が表しているもの：
- 数値として扱う必要がある列：
- 空欄または不自然に見えた値：
- 重複している可能性がある記録：
- 表記が揃っていないように見える列：
- 優先順位を決めるために必要だと思う集計：

これは正式な異常判定ではありません。品質規則を読む前の観察と仮説です。
""" if ja else """## What I noticed in the source — edit this in your own words

- What one row represents:
- Columns that should be numeric:
- Blank or surprising-looking values:
- Records that may be duplicated:
- Columns whose labels appear inconsistent:
- Aggregations that may support the priority decision:

This is not the official quality decision. It is your observation and hypothesis before reading the rules.
""")
    plan = ("""## 実装前の処理計画 — ここを編集する

- 原本を残したまま、どの順序で作業表を作るか：
- 要確認行と分析対象行をどう分けるか：
- 学校別に何を合計・計数するか：
- 同順位をどう再現可能に解消するか：
- 保存後に何を照合するか：
""" if ja else """## Processing plan before implementation — edit this section

- Order for creating a working table while preserving the source:
- How verification and analysis rows will be separated:
- What will be totalled or counted for each school:
- How ties will be resolved reproducibly:
- What will be reconciled after saving:
""")
    decision = ("""## 最終判断メモ — 最新の結果表を見て編集する

- 明日の追加配送先：
- その学校を選ぶ数値根拠（少なくとも二つ）：
- 要確認記録を判断から分けた理由：
- この結果だけでは分からないこと、または次に確認すること：

コードが出した学校名だけを書かず、人間が結果表を読み、判断根拠と限界を説明してください。
""" if ja else """## Final decision memo — edit after viewing the latest result tables

- School for tomorrow's additional delivery:
- At least two measures supporting that choice:
- Why verification records were kept outside the decision:
- One limitation or next question not answered by this result:

Do not copy only the school name printed by the program. Read the result table and explain the evidence and its limit.
""")
    cells = document["cells"]
    def insert_after(marker: str, newcells: list[dict]) -> None:
        index = next(i for i, cell in enumerate(cells) if cell.get("id") == marker) + 1
        cells[index:index] = newcells
    insert_after(f"{prefix}-03", [code_cell(f"{prefix}-03b", structure), markdown_cell(f"{prefix}-03c", observation)])
    insert_after(f"{prefix}-04", [markdown_cell(f"{prefix}-04b", plan)])
    insert_after(f"{prefix}-08b", [markdown_cell(f"{prefix}-08c", decision)])
    by_id = {cell.get("id"): cell for cell in cells}
    by_id[f"{prefix}-11"]["source"] = (("## 二つの成果物を提出する\n\nすべてのセルを最新状態で実行し、観察・計画・判断メモを記入してCtrl+SでNotebookを保存します。Moodleへ`P3A_school_meal_delivery_review.ipynb`と`meal_delivery_review.py`を提出します。Notebookとプログラムは各50点です。生成CSVはPython Labに作業根拠として残します。" if ja else "## Submit two deliverables\n\nRun all cells with the latest work, complete the observation, plan, and decision memos, and save the Notebook with Ctrl+S. Submit `P3A_school_meal_delivery_review.ipynb` and `meal_delivery_review.py` to Moodle. The Notebook and program are worth 50 points each. Keep the generated CSV files in Python Lab as working evidence.").splitlines(keepends=True))
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        json.dump(document, stream, ensure_ascii=False, indent=1)
        stream.write("\n")


def main() -> None:
    # v5 is the prerequisite for this script. It is intentionally not rerun here,
    # because its older lesson-copy migration is not idempotent once applied.
    update_briefs()
    update_readmes()
    update_notebook(BASE / "python-lab/templates/P3A_school_meal_delivery_review.ipynb", False)
    update_notebook(BASE / "python-lab/templates/ja/P3A_school_meal_delivery_review.ipynb", True)
    print(json.dumps({"briefs": 4, "readmes": 2, "notebooks": 2, "deliverables": 2, "points_each": 50}))


if __name__ == "__main__":
    main()
