#!/usr/bin/env python3
"""Turn Project 3A into a small inspection program followed by the full review."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"
PROJECTS = BASE / "python-lab/project-files"
REFERENCE = BASE / "reference-solutions/project-3a"


def write(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(text.rstrip() + "\n", encoding="utf-8", newline="\n")


def replace_between(path: Path, start: str, end: str, replacement: str) -> None:
    text = path.read_text(encoding="utf-8")
    start_at = text.find(start)
    end_at = text.find(end, start_at + len(start))
    if start_at < 0 or end_at < 0:
        raise RuntimeError(f"Section markers not found in {path}: {start!r}, {end!r}")
    write(path, text[:start_at] + replacement.rstrip() + "\n\n" + text[end_at:])


def replace_to_end(path: Path, start: str, replacement: str) -> None:
    text = path.read_text(encoding="utf-8")
    start_at = text.find(start)
    if start_at < 0:
        raise RuntimeError(f"Final marker not found in {path}: {start!r}")
    write(path, text[:start_at] + replacement)


EN_PROGRAMS = """## Two programs to complete

Do not start from empty files. Python Lab supplies two starters in `projects/school-meal-review/`.

1. Complete `inspect_school_meals.py`, a small program that reads and displays the source data. This is Stage 1 and is worth 20 points.
2. Then complete the existing eight functions in `meal_delivery_review.py`. This is Stage 2 and is worth 80 points.

The Project Notebook is a work guide used to run both programs and view their output. It is not submitted. Neither program may edit or overwrite the source CSV.
"""


JA_PROGRAMS = """## 完成させる二つのプログラム

空のファイルから作り始めません。Python Labの`projects/school-meal-review/`に、次の二つのスターターがあります。

1. 原資料を読み込んで表示する小さな`inspect_school_meals.py`を完成させます。これが第1段階で、20点です。
2. 続いて、既存の`meal_delivery_review.py`にある8関数を完成させます。これが第2段階で、80点です。

Project Notebookは二つのプログラムを実行して結果を見るための作業案内であり、提出しません。どちらのプログラムも原本CSVを編集・上書きしてはいけません。
"""


EN_STAGE1 = """## Stage 1 — Complete the source inspection program

Data work begins by checking the shape and content of the source before applying quality decisions. Open `inspect_school_meals.py` and complete its three small functions. The finished program must:

1. read the supplied CSV with pandas;
2. display the row and column counts;
3. display the column names and pandas-inferred dtypes;
4. display all 37 records without truncating rows;
5. display a separate view sorted by `school_id` and then `date`;
6. display missing-value counts for every column; and
7. display each raw `district` value and its count.

Use the raw district strings exactly as recorded at this stage. Do not add quality flags, remove records, correct values, aggregate schools, or decide delivery priority. `load_records(path)` must work with another CSV of the same columns rather than containing the 37 sample rows in source code. `build_school_date_view(records)` returns a sorted copy without changing `records`. `count_district_values(records)` counts raw values, including inconsistent spelling or whitespace.

Run the program and its small checker before Stage 2:

```text
python projects/school-meal-review/inspect_school_meals.py
python projects/school-meal-review/check_inspect_school_meals.py
```

The full table and sorted table are intentionally verbose: their purpose is to let a person see the supplied source before the more difficult processing begins.
"""


JA_STAGE1 = """## 第1段階 — 原資料を確認する小課題

データを使う仕事では、品質判定を始める前に原資料の形と内容を確認します。`inspect_school_meals.py`を開き、三つの小さな関数を完成させてください。完成したプログラムは、次を行います。

1. 配布CSVをpandasで読み込む。
2. 行数と列数を表示する。
3. 列名とpandasが推定したデータ型を表示する。
4. 37件すべてを、行を省略せずに表示する。
5. `school_id`、次に`date`の順で並べた別の確認用表を表示する。
6. 全列の欠損数を表示する。
7. 原資料の`district`に記録された値と件数を表示する。

この段階では地区名を原資料の文字列のまま数えます。品質フラグの追加、行の削除、値の補正、学校別集計、配送順位の決定は行いません。`load_records(path)`は37件をコードへ書き写さず、同じ列を持つ別のCSVも読み込めるようにします。`build_school_date_view(records)`は`records`を変更せず、並べ替えたコピーを返します。`count_district_values(records)`は空白や大文字・小文字の違いを直さず、記録された値を数えます。

第2段階へ進む前に、プログラムと簡単な確認プログラムを実行します。

```text
python projects/school-meal-review/inspect_school_meals.py
python projects/school-meal-review/check_inspect_school_meals.py
```

全件表と並べ替え表は長く表示されます。難しい処理を始める前に、人間が原資料を確認できる状態をコードで作ることが、この小課題の目的です。
"""


EN_FINAL = """## Work order, checking, and submission

Complete the project in this order:

```text
read the CSV
→ display and inspect the source
→ check inspect_school_meals.py
→ read the quality rules
→ implement the eight production functions
→ inspect the generated CSVs
→ check meal_delivery_review.py
→ submit both programs
```

The Notebook only guides these operations. No observation essay or final report is required.

### Assessment

| Submitted program | Points | What is checked |
|---|---:|---|
| `inspect_school_meals.py` | 20 | reads the supplied and alternate CSVs; preserves the source; shows shape, names, dtypes, every row, a school/date view, missing counts, and raw district counts |
| `meal_delivery_review.py` | 80 | the existing eight functions implement the published quality, separation, aggregation, ranking, and saving contract; all ten production checks pass |

Run both checkers after inspecting the program output:

```text
python projects/school-meal-review/check_inspect_school_meals.py
python projects/school-meal-review/check_meal_delivery_review.py
```

Submit exactly these two files to Moodle:

1. `inspect_school_meals.py`
2. `meal_delivery_review.py`

Generated CSVs and the Project Notebook remain in Python Lab as working material and are not submitted.
"""


JA_FINAL = """## 作業順、確認、提出

次の順序で進めます。

```text
CSVを読み込む
→ 見やすく表示して原資料を確認する
→ inspect_school_meals.pyを確認する
→ 品質規則を確認する
→ 本番の8関数を実装する
→ 生成CSVを確認する
→ meal_delivery_review.pyを確認する
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
"""


STARTER = '''from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
SOURCE_FILE = HERE / "data" / "school-meals-practice.csv"


def load_records(path):
    """Read and return the CSV without changing the source file."""
    # TODO 1: use pandas to read the path supplied to this function
    raise NotImplementedError


def build_school_date_view(records):
    """Return a new table sorted by school_id and date."""
    # TODO 2: do not change records itself
    raise NotImplementedError


def count_district_values(records):
    """Return counts for the raw district values as recorded."""
    # TODO 3: do not strip, title-case, or otherwise normalise the values
    raise NotImplementedError


def main():
    records = load_records(SOURCE_FILE)
    school_date_view = build_school_date_view(records)
    district_counts = count_district_values(records)

    print("SCHOOL MEAL SOURCE INSPECTION")
    print(f"ROWS: {len(records)}")
    print(f"COLUMNS: {len(records.columns)}")
    print("COLUMN NAMES:")
    print(records.columns.tolist())
    print("INFERRED DTYPES:")
    print(records.dtypes.to_string())
    print("ALL RECORDS:")
    print(records.to_string(index=False))
    print("SCHOOL/DATE VIEW:")
    print(school_date_view.to_string(index=False))
    print("MISSING VALUES:")
    print(records.isna().sum().to_string())
    print("DISTRICT VALUES:")
    print(district_counts.to_string())


if __name__ == "__main__":
    main()
'''


SOLUTION = '''from pathlib import Path

import pandas as pd


HERE = Path(__file__).resolve().parent
SOURCE_FILE = HERE / "data" / "school-meals-practice.csv"


def load_records(path):
    """Read and return the CSV without changing the source file."""
    return pd.read_csv(path)


def build_school_date_view(records):
    """Return a new table sorted by school_id and date."""
    return records.sort_values(["school_id", "date"], kind="stable").reset_index(drop=True)


def count_district_values(records):
    """Return counts for the raw district values as recorded."""
    return records["district"].value_counts(dropna=False, sort=False)


def main():
    records = load_records(SOURCE_FILE)
    school_date_view = build_school_date_view(records)
    district_counts = count_district_values(records)

    print("SCHOOL MEAL SOURCE INSPECTION")
    print(f"ROWS: {len(records)}")
    print(f"COLUMNS: {len(records.columns)}")
    print("COLUMN NAMES:")
    print(records.columns.tolist())
    print("INFERRED DTYPES:")
    print(records.dtypes.to_string())
    print("ALL RECORDS:")
    print(records.to_string(index=False))
    print("SCHOOL/DATE VIEW:")
    print(school_date_view.to_string(index=False))
    print("MISSING VALUES:")
    print(records.isna().sum().to_string())
    print("DISTRICT VALUES:")
    print(district_counts.to_string())


if __name__ == "__main__":
    main()
'''


CHECKER = r'''#!/usr/bin/env python3
from __future__ import annotations

import hashlib
import importlib.util
import subprocess
import sys
import tempfile
from pathlib import Path

import pandas as pd
from pandas.testing import assert_frame_equal, assert_series_equal


HERE = Path(__file__).resolve().parent
TARGET = HERE / "inspect_school_meals.py"
SOURCE = HERE / "data" / "school-meals-practice.csv"


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def load_module():
    spec = importlib.util.spec_from_file_location("learner_inspection", TARGET)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def check(name, action):
    try:
        action()
    except Exception as error:
        print(f"[NG] {name}: {type(error).__name__}: {error}")
        return False
    print(f"[OK] {name}")
    return True


def main():
    print("School meal source inspection — automatic check")
    if not TARGET.is_file():
        raise SystemExit(f"Missing: {TARGET}")
    module = load_module()
    raw = pd.read_csv(SOURCE)
    before = digest(SOURCE)

    def source_loading():
        actual = module.load_records(SOURCE)
        assert_frame_equal(actual, raw)
        assert digest(SOURCE) == before

    def alternate_loading():
        alternate = raw.iloc[:3].copy()
        alternate.loc[:, "school_id"] = ["T101", "T102", "T103"]
        with tempfile.TemporaryDirectory() as temporary:
            path = Path(temporary) / "alternate.csv"
            alternate.to_csv(path, index=False)
            actual = module.load_records(path)
            assert_frame_equal(actual, pd.read_csv(path))

    def sorted_view():
        original = raw.copy(deep=True)
        actual = module.build_school_date_view(raw)
        expected = raw.sort_values(["school_id", "date"], kind="stable").reset_index(drop=True)
        assert_frame_equal(actual.reset_index(drop=True), expected)
        assert_frame_equal(raw, original)
        assert actual is not raw

    def district_counts():
        actual = module.count_district_values(raw)
        expected = raw["district"].value_counts(dropna=False, sort=False)
        assert_series_equal(actual, expected)

    def command_output():
        result = subprocess.run([sys.executable, str(TARGET)], cwd=HERE, text=True, capture_output=True, timeout=30)
        assert result.returncode == 0, result.stderr
        for token in ["ROWS: 37", "COLUMNS: 7", "COLUMN NAMES:", "INFERRED DTYPES:", "ALL RECORDS:", "SCHOOL/DATE VIEW:", "MISSING VALUES:", "DISTRICT VALUES:"]:
            assert token in result.stdout, f"missing output label: {token}"
        for school_id in raw["school_id"].unique():
            assert school_id in result.stdout
        assert digest(SOURCE) == before

    checks = [
        ("CSV loading and source protection", source_loading),
        ("not fixed to the 37 sample rows", alternate_loading),
        ("school/date inspection view", sorted_view),
        ("raw district value counts", district_counts),
        ("complete command-line inspection", command_output),
    ]
    passed = sum(check(name, action) for name, action in checks)
    if passed != len(checks):
        raise SystemExit(f"{passed}/{len(checks)} checks passed")
    print("\nALL INSPECTION CHECKS PASSED")
    print("STAGE 1 COMPLETE")


if __name__ == "__main__":
    main()
'''


README_EN = """# Midterm choice A — School meal delivery review

Read `PROJECT_BRIEF.md` for the public contract. Complete the supplied starters; do not begin from empty files.

Stage 1 (20 points): edit `inspect_school_meals.py`, save it, view its complete output, then run `check_inspect_school_meals.py`.

Stage 2 (80 points): edit the eight TODO functions in `meal_delivery_review.py`, inspect its two generated CSV files, then run `check_meal_delivery_review.py`.

```text
python projects/school-meal-review/inspect_school_meals.py
python projects/school-meal-review/check_inspect_school_meals.py
python projects/school-meal-review/meal_delivery_review.py
python projects/school-meal-review/check_meal_delivery_review.py
```

Submit `inspect_school_meals.py` and `meal_delivery_review.py`. Do not submit the Notebook or generated CSVs. Do not edit the source CSV or either checker.
"""


README_JA = """# 第3章 中間実践課題A — 学校給食の追加配送

公開仕様は`PROJECT_BRIEF.md`にあります。空のファイルから作らず、配布されたスターターを完成させます。

第1段階（20点）：`inspect_school_meals.py`を編集・保存し、全出力を自分で見てから`check_inspect_school_meals.py`を実行します。

第2段階（80点）：`meal_delivery_review.py`の8個のTODO関数を編集し、生成された二つのCSVを見てから`check_meal_delivery_review.py`を実行します。

```text
python projects/school-meal-review/inspect_school_meals.py
python projects/school-meal-review/check_inspect_school_meals.py
python projects/school-meal-review/meal_delivery_review.py
python projects/school-meal-review/check_meal_delivery_review.py
```

提出するのは`inspect_school_meals.py`と`meal_delivery_review.py`です。Notebookと生成CSVは提出しません。原本CSVと二つの確認プログラムは編集しません。
"""


def markdown(cell_id: str, source: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": source.splitlines(keepends=True)}


def code(cell_id: str, source: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": source.splitlines(keepends=True)}


def update_notebook(path: Path, ja: bool) -> None:
    old = json.loads(path.read_text(encoding="utf-8"))
    prefix = "p3a-ja" if ja else "p3a-en"
    title = ("# 第3章 中間実践課題A — 明日の追加配送先を決める\n\nこのNotebookは二つのプログラムを実行して出力を確認する作業案内です。Notebook自体は提出しません。" if ja else "# Chapter 3 midterm practical project A — Decide tomorrow's additional delivery\n\nThis Notebook guides you through running two programs and checking their output. The Notebook itself is not submitted.")
    stage1 = ("## 第1段階 — 原資料を確認する\n\nファイルブラウザで`projects/school-meal-review/inspect_school_meals.py`を開き、三つのTODOを完成させてCtrl+Sで保存します。最初の未完成エラーは正常です。実行すると原資料37件、学校・日付順の表、型、欠損数、原地区名の件数が表示されます。" if ja else "## Stage 1 — Inspect the source\n\nOpen `projects/school-meal-review/inspect_school_meals.py`, complete its three TODOs, and save with Ctrl+S. An initial incomplete error is normal. The finished output shows all 37 records, a school/date view, dtypes, missing counts, and raw district counts.")
    inspect_result = ("第1段階の終了コードが0でない場合は、表示されたTODOまたはエラーを直して再実行します。表を上から下まで確認してから小課題の確認へ進みます。" if ja else "If Stage 1 does not exit with code 0, fix the displayed TODO or error and run it again. Review the complete tables before running the small checker.")
    stage2 = ("## 第2段階 — 本番のデータ処理\n\n`PROJECT_BRIEF.md`の品質規則と8関数の契約を読み、`meal_delivery_review.py`を完成させます。第1段階で見た原資料に、要確認行の分離、集計、優先順位、CSV保存を再現可能な規則として加えます。" if ja else "## Stage 2 — Production data processing\n\nRead the quality rules and eight-function contract in `PROJECT_BRIEF.md`, then complete `meal_delivery_review.py`. Add reproducible verification, separation, aggregation, ranking, and CSV saving to the source you inspected in Stage 1.")
    output_text = ("## 本番の生成CSVを確認する\n\n直前の本番プログラムが成功した場合だけ、要確認記録と学校別順位を表示します。" if ja else "## Inspect the generated production CSVs\n\nDisplay the verification records and school ranking only when the immediately preceding production run succeeded.")
    expected = ("## 代表値を照合する\n\n原本37行、要確認4行、分析対象33行、学校別6グループです。最初の配送先は`S004 — Market Road School`、1位の平均未提供食数は7.5、未提供日は6日です。違う場合は本番確認へ進まず、中間処理を見直します。" if ja else "## Reconcile the checkpoints\n\nExpect 37 source rows, 4 verification rows, 33 analysis rows, and 6 school groups. The first delivery is `S004 — Market Road School`; its average unmet meals is 7.5 across 6 shortage days. If these differ, inspect the production stages before running its checker.")
    submit = ("## 二つのPythonプログラムを提出する\n\n両方をCtrl+Sで保存し、第1段階で`ALL INSPECTION CHECKS PASSED`、第2段階で`ALL TESTS PASSED`を確認します。Moodleへ`inspect_school_meals.py`（20点）と`meal_delivery_review.py`（80点）を提出します。Notebookと生成CSVは提出しません。" if ja else "## Submit two Python programs\n\nSave both files with Ctrl+S. Confirm `ALL INSPECTION CHECKS PASSED` for Stage 1 and `ALL TESTS PASSED` for Stage 2. Submit `inspect_school_meals.py` (20 points) and `meal_delivery_review.py` (80 points) to Moodle. Do not submit the Notebook or generated CSVs.")
    cells = [
        markdown(f"{prefix}-01", title),
        markdown(f"{prefix}-02", stage1),
        code(f"{prefix}-03", '''import subprocess\nimport sys\nfrom pathlib import Path\n\nproject = Path.cwd() / "projects" / "school-meal-review"\ninspect_result = subprocess.run([sys.executable, str(project / "inspect_school_meals.py")])\nprint("Stage 1 exit code:", inspect_result.returncode)\n'''),
        markdown(f"{prefix}-04", inspect_result),
        code(f"{prefix}-05", '!python projects/school-meal-review/check_inspect_school_meals.py\n'),
        markdown(f"{prefix}-06", stage2),
        code(f"{prefix}-07", '''production_result = subprocess.run([sys.executable, str(project / "meal_delivery_review.py")])\nproduction_succeeded = production_result.returncode == 0\nprint("Stage 2 exit code:", production_result.returncode)\n'''),
        markdown(f"{prefix}-08", output_text),
        code(f"{prefix}-09", '''import pandas as pd\n\nif not globals().get("production_succeeded", False):\n    print("Output is hidden because the immediately preceding Stage 2 run failed. Older files may still exist.")\nelse:\n    for filename in ["records_to_verify.csv", "school_delivery_summary.csv"]:\n        output_file = project / "output" / filename\n        print(f"\\n--- {filename} ---")\n        if output_file.is_file():\n            display(pd.read_csv(output_file))\n        else:\n            print("Missing output. Review save_outputs() and run_project().")\n'''),
        markdown(f"{prefix}-10", expected),
        code(f"{prefix}-11", '!python projects/school-meal-review/check_meal_delivery_review.py\n'),
        markdown(f"{prefix}-12", submit),
    ]
    old["cells"] = cells
    path.write_text(json.dumps(old, ensure_ascii=False, indent=1) + "\n", encoding="utf-8", newline="\n")


def update_briefs() -> None:
    settings = [
        (BASE / "project-3a-brief-en.md", "## Program to complete\n", "## Input data\n", EN_PROGRAMS, "## Observe the source before reading the rules\n", "## Processing order and verification rules\n", EN_STAGE1, "## Work record, checking, and submission\n", EN_FINAL),
        (PROJECTS / "projects/school-meal-review/PROJECT_BRIEF.md", "## Program to complete\n", "## Input data\n", EN_PROGRAMS, "## Observe the source before reading the rules\n", "## Processing order and verification rules\n", EN_STAGE1, "## Work record, checking, and submission\n", EN_FINAL),
        (BASE / "project-3a-brief-ja.md", "## 今回完成させるプログラム\n", "## 入力データ\n", JA_PROGRAMS, "## 品質規則を読む前に原資料を観察する\n", "## 判定する順序と品質規則\n", JA_STAGE1, "## 作業記録、確認、提出\n", JA_FINAL),
        (PROJECTS / "ja/projects/school-meal-review/PROJECT_BRIEF.md", "## 今回完成させるプログラム\n", "## 入力データ\n", JA_PROGRAMS, "## 品質規則を読む前に原資料を観察する\n", "## 判定する順序と品質規則\n", JA_STAGE1, "## 作業記録、確認、提出\n", JA_FINAL),
    ]
    for path, program_start, input_start, programs, observation_start, rules_start, stage1, final_start, final in settings:
        replace_between(path, program_start, input_start, programs)
        replace_between(path, observation_start, rules_start, stage1)
        replace_to_end(path, final_start, final)


def update_moodle_builder() -> None:
    path = ROOT / "scripts/build-python-chapter3-moodle-v35.py"
    text = path.read_text(encoding="utf-8")
    replacements = [
        ("$ltiintro = $ja ? '<p>課題案内Notebookを開き、配布CSVを確認して<code>meal_delivery_review.py</code>の8関数を完成させます。</p>' : '<p>Open the project Notebook, inspect the supplied CSV, and complete the eight functions in <code>meal_delivery_review.py</code>.</p>';", "$ltiintro = $ja ? '<p>作業案内Notebookから、原資料確認用<code>inspect_school_meals.py</code>、次に本番用<code>meal_delivery_review.py</code>を完成させます。Notebook自体は提出しません。</p>' : '<p>Use the work-guide Notebook to complete <code>inspect_school_meals.py</code> and then <code>meal_delivery_review.py</code>. The Notebook itself is not submitted.</p>';"),
        ("v35_plugin_config($assign->id, 'file', 'allowedfiletypes', '.py,.ipynb');", "v35_plugin_config($assign->id, 'file', 'allowedfiletypes', '.py');"),
        ("'file:maxfilesubmissions' => '2', 'file:allowedfiletypes' => '.py,.ipynb'", "'file:maxfilesubmissions' => '2', 'file:allowedfiletypes' => '.py'"),
        ("'assignment' => ['files' => 2, 'types' => ['.py', '.ipynb'], 'online_text' => false]", "'assignment' => ['files' => 2, 'types' => ['.py'], 'online_text' => false]"),
        ("'P3A_school_meal_delivery_review.ipynb'] as $token", "'inspect_school_meals.py', 'check_inspect_school_meals.py'] as $token"),
    ]
    for old, new in replacements:
        count = text.count(old)
        if count != 1:
            raise RuntimeError(f"Moodle builder expected one fragment, found {count}: {old[:80]}")
        text = text.replace(old, new)
    path.write_text(text, encoding="utf-8", newline="\n")


def main() -> None:
    update_briefs()
    write(PROJECTS / "projects/school-meal-review/inspect_school_meals.py", STARTER)
    write(PROJECTS / "ja/projects/school-meal-review/inspect_school_meals.py", STARTER)
    write(PROJECTS / "projects/school-meal-review/check_inspect_school_meals.py", CHECKER)
    write(PROJECTS / "ja/projects/school-meal-review/check_inspect_school_meals.py", CHECKER)
    write(PROJECTS / "projects/school-meal-review/README.md", README_EN)
    write(PROJECTS / "ja/projects/school-meal-review/README.md", README_JA)
    write(REFERENCE / "inspect_school_meals.py", SOLUTION)
    update_notebook(BASE / "python-lab/templates/P3A_school_meal_delivery_review.ipynb", False)
    update_notebook(BASE / "python-lab/templates/ja/P3A_school_meal_delivery_review.ipynb", True)
    update_moodle_builder()
    print({"stages": 2, "submitted_programs": 2, "inspection_points": 20, "production_points": 80})


if __name__ == "__main__":
    main()
