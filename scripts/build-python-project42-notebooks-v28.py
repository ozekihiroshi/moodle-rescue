#!/usr/bin/env python3
"""Make Project 4.2 concrete: data location, exact tasks, outputs, and report prompts."""
from __future__ import annotations
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"

def markdown(cell_id: str, source: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": source.splitlines(keepends=True)}

def code(cell_id: str, source: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": source.splitlines(keepends=True)}

def replace_cell(cells: list[dict], cell_id: str, source: str) -> None:
    for cell in cells:
        if cell.get("id") == cell_id:
            cell["source"] = source.splitlines(keepends=True)
            return
    raise KeyError(cell_id)

def build(language: str, path: Path) -> None:
    doc = json.loads(path.read_text(encoding="utf-8"))
    cells = doc["cells"]
    ja = language == "ja"
    replace_cell(cells, "p42-title", (
        "# 4.2 — ガイド付きプロジェクト：学習センター分析\n\n"
        "**具体的な問い：** 2026年1〜6月について、Python FoundationsとDigital Skillsのどちらが全体修了率が高く、どちらが一人修了当たり教材費が低いでしょうか。差を数値で示し、この24件だけでコースの優劣を決められるか判断し、次に必要なデータを一つ提案します。"
        if ja else
        "# 4.2 — Guided project: Learning-centre analysis\n\n"
        "**Concrete question:** For January–June 2026, which course has the higher overall completion rate, and which has the lower material cost per completion: Python Foundations or Digital Skills? Quantify both differences, decide whether these 24 records justify ranking the courses, and propose one item of data needed next."
    ))
    replace_cell(cells, "p42-scope", (
        "## 1. 使用するファイルと完成条件\n\n"
        "ファイル名は`learning-centres-practice.csv`です。Moodleの「4.2 データセットとプロジェクト手順」から直接ダウンロードできます。Python Labでは`/home/jovyan/work/data/learning-centres-practice.csv`にあり、このNotebookの次のセルが開始フォルダに依存せず探します。\n\n"
        "読み込み直後は**24行×10列**です。対象コースは`Python Foundations`と`Digital Skills`、対象月は`2026-01`〜`2026-06`です。最終的に、品質監査表、コース別集計表、照合結果、主図1点、300〜500字の回答をこのNotebookへ残します。"
        if ja else
        "## 1. File location and definition of done\n\n"
        "The file is `learning-centres-practice.csv`. Download it directly from the Moodle page “4.2 Dataset and project brief.” In Python Lab it is available at `/home/jovyan/work/data/learning-centres-practice.csv`; the next cell locates it without depending on the start folder.\n\n"
        "Immediately after loading, it has **24 rows and 10 columns**. Courses are `Python Foundations` and `Digital Skills`, covering `2026-01` through `2026-06`. Your final notebook must contain a quality audit, course summary, reconciliation, one primary chart, and a 150–250 word answer."
    ))
    load_index = next(i for i, cell in enumerate(cells) if cell.get("id") == "p42-load")
    cells[load_index + 1:load_index + 1] = [
        markdown("p42-location", "## ファイルを開けたことを確認する\n\n次の出力で、実際に読んだ絶対パス、`(24, 10)`、10列の名前、先頭5行を確認します。異なる形なら、そのまま進まずファイルを確認します。" if ja else "## Confirm the exact file that opened\n\nThe next output must show the absolute path, `(24, 10)`, ten column names, and the first five rows. Stop and check the file if the shape differs."),
        code("p42-preview", "print(data_file.resolve())\nprint(\"Shape:\", raw.shape)\nprint(\"Columns:\", raw.columns.tolist())\ndisplay(raw.head())\nassert raw.shape == (24, 10)\n"),
    ]
    replace_cell(cells, "p42-inspect", (
        "## 2. 次の10列を検査する\n\n"
        "`month`, `centre_id`, `centre_name`, `district`, `course`, `registered`, `attended`, `completed`, `training_hours`, `material_cost`が存在することを確認します。次に型、列別欠損件数、地区名一覧、人数・時間・費用の最小値と最大値を表示します。ここではまだ修正しません。"
        if ja else
        "## 2. Inspect these ten columns\n\n"
        "Confirm `month`, `centre_id`, `centre_name`, `district`, `course`, `registered`, `attended`, `completed`, `training_hours`, and `material_cost`. Then display types, missing count per column, district labels, and minimum and maximum for counts, hours, and cost. Do not change values yet."
    ))
    replace_cell(cells, "p42-clean", (
        "## 3. 三つの品質検査を別々に実行する\n\n"
        "次の規則を、名前付きブールマスクとして別々に数えます。\n\n"
        "1. `attended`が欠損している。\n"
        "2. `completed > attended`である（両方の値がある行だけ）。\n"
        "3. `centre_id + month + course`が重複している。\n\n"
        "地区名は元の`district_raw`を残して前後空白と大文字・小文字を正規化します。人数を推測で書き換えず、問題行を`analysis_ready=False`にします。監査表には問題名、規則、影響件数、処置を記録します。"
        if ja else
        "## 3. Run three separate quality checks\n\n"
        "Count three named Boolean masks separately: (1) missing `attended`; (2) `completed > attended` where both are known; and (3) duplicate `centre_id + month + course`. Preserve `district_raw` while normalising whitespace and case. Do not guess learner counts; set problem rows to `analysis_ready=False`. The audit table must record issue, rule, affected count, and action."
    ))
    replace_cell(cells, "p42-analyse", (
        "## 4. この7列のコース別集計表を完成させる\n\n"
        "出力を`summary`とし、`course`, `records`, `centres`, `registered`, `completed`, `material_cost`, `completion_rate`, `cost_per_completion`を含めます。\n\n"
        "- `completion_rate = completed合計 / registered合計 × 100`\n"
        "- `cost_per_completion = material_cost合計 / completed合計`\n\n"
        "各行の率や費用を先に計算して単純平均してはいけません。二つのコースの修了率差は**パーセントポイント**、費用差は教材費と同じ単位で計算します。"
        if ja else
        "## 4. Complete this course summary\n\n"
        "`summary` must contain `course`, `records`, `centres`, `registered`, `completed`, `material_cost`, `completion_rate`, and `cost_per_completion`. Calculate completion as total completed / total registered × 100 and cost per completion as total cost / total completed. Do not average row rates or row costs. Calculate the course completion difference in percentage points and the cost difference in the cost unit."
    ))
    replace_cell(cells, "p42-report", (
        "## 7. 次の5項目へ順番に答える\n\n"
        "300〜500字程度で、次を明記します。\n\n"
        "1. 各コースの全体修了率と、その差（パーセントポイント）。\n"
        "2. 各コースの一人修了当たり教材費と、その差。\n"
        "3. どちらが高い／低いか。ただし『優れている』とは書かない。\n"
        "4. 分析対象行数、対象期間、少なくとも一つの限界。\n"
        "5. 優劣や原因を検討するため、次に必要なデータを一つ。"
        if ja else
        "## 7. Answer these five prompts in order\n\n"
        "In 150–250 words state: (1) both overall completion rates and the percentage-point difference; (2) both costs per completion and their difference; (3) which is higher/lower without saying one course is superior; (4) analysis-ready row count, period, and at least one limitation; and (5) one additional item of data needed to investigate performance or causes."
    ))
    replace_cell(cells, "p42-submit", (
        "## 提出前チェックリスト\n\n"
        "- 最初から最後まで「すべて実行」でエラーがない。\n"
        "- 読み込んだパスと`(24, 10)`が表示される。\n"
        "- 三つの品質問題が別々に数えられ、監査表がある。\n"
        "- `summary`に指定した8列と2コースがある。\n"
        "- 行数・登録者・修了者・教材費の照合が通る。\n"
        "- 主図は0〜100%軸、タイトル、軸名、単位、分析対象件数を持つ。\n"
        "- 報告文が上の5項目すべてへ回答する。"
        if ja else
        "## Pre-submission checklist\n\n"
        "- Run All completes without errors.\n"
        "- Loaded path and `(24, 10)` are shown.\n"
        "- Three quality issues are counted separately and an audit table is present.\n"
        "- `summary` has the specified eight columns and two courses.\n"
        "- Row, registration, completion, and cost reconciliations pass.\n"
        "- The primary chart has a 0–100% scale, title, axis name, unit, and analysis-ready n.\n"
        "- The report answers all five prompts."
    ))
    doc["metadata"]["pyai"]["revision"] = 28
    path.write_text(json.dumps(doc, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
    print("wrote", path.relative_to(ROOT))

build("en", TEMPLATES / "P3_learning_centres_analysis.ipynb")
build("ja", TEMPLATES / "ja/P3_learning_centres_analysis.ipynb")
