#!/usr/bin/env python3
"""Close the four Chapter 3 gaps required by midterm project A."""
from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
LAB = ROOT / "sample-content/introduction-to-python/python-lab"
TEMPLATES = LAB / "templates"


def md(cell_id: str, source: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": source.splitlines(keepends=True)}


def code(cell_id: str, source: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": source.splitlines(keepends=True)}


def load(path: Path) -> dict:
    return json.loads(path.read_text(encoding="utf-8"))


def save(path: Path, document: dict) -> None:
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        json.dump(document, stream, ensure_ascii=False, indent=1)
        stream.write("\n")


def insert_after(document: dict, anchor: str, additions: list[dict]) -> None:
    if any(cell.get("id") == additions[0]["id"] for cell in document["cells"]):
        return
    for index, cell in enumerate(document["cells"]):
        if cell.get("id") == anchor:
            document["cells"][index + 1:index + 1] = additions
            return
    raise RuntimeError(f"Notebook anchor not found: {anchor}")


def add_project_notebook_ids(path: Path, language: str) -> None:
    document = load(path)
    for index, cell in enumerate(document["cells"], start=1):
        cell["id"] = f"p3a-{language}-{index:02d}"
    save(path, document)


def update_lesson33(path: Path, language: str) -> None:
    document = load(path)
    if language == "ja":
        explanation = """## 個別フラグから、確認対象レコード表を作る

規則ごとの件数だけでは、原資料のどの行を確認すべきか分かりません。個別フラグは消さず、一定の順序で理由を連結し、元の識別列とともに確認対象表へ取り出します。一行が複数規則へ違反した場合も、最初の理由だけにせず該当理由をすべて残します。これにより、分析から外した件数と、確認担当者へ渡すレコードを同じ判定から作れます。
"""
        source = '''issue_rules = [
    (missing_attended, "missing attended"),
    (completion_over_attendance, "completion above attendance"),
    (negative_cost, "negative material cost"),
    (duplicate_key, "duplicate business key"),
]
clean["issue"] = ""
for mask, label in issue_rules:
    clean.loc[mask, "issue"] = clean.loc[mask, "issue"] + label + "; "
clean["issue"] = clean["issue"].str.rstrip("; ")

verification_columns = ["month", "centre_id", "course", "issue"]
records_to_verify = (
    clean.loc[~clean["analysis_ready"], verification_columns]
    .sort_values(["month", "centre_id", "course"])
    .reset_index(drop=True)
)
records_to_verify
'''
        completion = "\n\n個別フラグから、複数理由を保持した行単位の確認対象表も作成できることを確認してください。"
    else:
        explanation = """## Turn individual flags into a records-to-verify table

Rule counts alone do not tell a reviewer which source rows need attention. Keep every individual flag, join its reason in one documented order, and select the original identifying columns into a verification table. If one row breaks several rules, retain every matching reason rather than only the first. The analysis exclusion count and the reviewer hand-off then come from the same decisions.
"""
        source = '''issue_rules = [
    (missing_attended, "missing attended"),
    (completion_over_attendance, "completion above attendance"),
    (negative_cost, "negative material cost"),
    (duplicate_key, "duplicate business key"),
]
clean["issue"] = ""
for mask, label in issue_rules:
    clean.loc[mask, "issue"] = clean.loc[mask, "issue"] + label + "; "
clean["issue"] = clean["issue"].str.rstrip("; ")

verification_columns = ["month", "centre_id", "course", "issue"]
records_to_verify = (
    clean.loc[~clean["analysis_ready"], verification_columns]
    .sort_values(["month", "centre_id", "course"])
    .reset_index(drop=True)
)
records_to_verify
'''
        completion = "\n\nConfirm that you can also derive a row-level verification table that retains every matching reason from the individual flags."
    insert_after(document, f"l33-{language}-actions-code", [
        md(f"l33-{language}-verification", explanation),
        code(f"l33-{language}-verification-code", source),
    ])
    complete_id = f"l33-{language}-complete"
    for cell in document["cells"]:
        if cell.get("id") == complete_id and completion.strip() not in "".join(cell["source"]):
            cell["source"] = ("".join(cell["source"]) + completion).splitlines(keepends=True)
    save(path, document)


def update_lesson34(path: Path, language: str) -> None:
    document = load(path)
    if language == "ja":
        conditional_text = """## 条件付き件数は、判定列を作ってから集計する

「修了率75%未満だった記録数」は単なる行数ではありません。まず各明細が条件に当てはまるかをブール列にし、グループ内でTrueを合計します。判定と集計を分けると、条件そのものと件数を別々に確認できます。
"""
        ranking_text = """## 優先順位は、昇順と降順を列ごとに指定する

優先順位には複数の規則が必要です。ここでは一人修了当たり教材費が高い順、同じなら記録数が多い順、さらに同じならコース名順とします。最後の安定した識別列まで指定すると、同じデータから毎回同じ先頭行を得られます。丸める値がある場合は、丸める前の値で順位を決めます。
"""
        output_text = """## 保存は終点ではなく、再読込して境界を確認する

提出物になるCSVは、Notebook内のDataFrameとは別の境界です。二つの用途別CSVを保存し、再読込後の列、件数、先頭の優先対象を照合します。直前の変数が正しくても、保存列や並びを誤れば成果物は正しくありません。
"""
        complete = "\n\n条件付き件数、昇降順を混在させた決定的な順位、二つのCSVの保存後再照合まで確認してください。"
    else:
        conditional_text = """## Count a condition by creating its Boolean column before aggregation

“Records below 75% completion” is not an ordinary row count. First make the row-level condition a Boolean column, then sum True values within each group. Separating the decision from the aggregation lets you inspect both the rule and its count.
"""
        ranking_text = """## Specify ascending and descending direction for each ranking key

A priority order usually needs more than one rule. Here, rank higher cost per completion first, then more records, then course name. The final stable identifier makes ties reproducible. When values will be rounded for display, rank with the unrounded values.
"""
        output_text = """## Saving is not the end: re-read and validate the output boundary

A submitted CSV is a different boundary from an in-memory DataFrame. Save two purpose-specific files, re-read them, and reconcile columns, row counts, and the first priority. Correct variables are not enough when the saved schema or order is wrong.
"""
        complete = "\n\nConfirm conditional counts, deterministic mixed-direction ranking, and post-save reconciliation of two CSV outputs."

    conditional_code = '''operational = analysis.assign(low_completion=analysis["completion_rate"] < 75)
condition_summary = operational.groupby("course", as_index=False).agg(
    records=("centre_id", "size"),
    low_completion_records=("low_completion", "sum"),
)
condition_summary
'''
    ranking_code = '''ranked_course = (
    course_summary.reset_index()
    .sort_values(
        ["cost_per_completion", "records", "course"],
        ascending=[False, False, True],
    )
    .reset_index(drop=True)
)
ranked_course.insert(0, "priority", range(1, len(ranked_course) + 1))
ranked_course[["priority", "course", "cost_per_completion", "records"]].round(2)
'''
    output_code = '''review_columns = ["month", "centre_id", "course", "registered", "completed", "completion_rate"]
review_output = (
    operational.loc[operational["low_completion"], review_columns]
    .sort_values(["month", "centre_id", "course"])
    .reset_index(drop=True)
)
summary_output = ranked_course.copy()

output_dir = Path.cwd() / "output" / "lesson34"
output_dir.mkdir(parents=True, exist_ok=True)
review_path = output_dir / "records_to_review.csv"
summary_path = output_dir / "course_priority_summary.csv"
review_output.to_csv(review_path, index=False)
summary_output.to_csv(summary_path, index=False)

saved_review = pd.read_csv(review_path)
saved_summary = pd.read_csv(summary_path)
assert list(saved_review.columns) == review_columns
assert len(saved_review) == len(review_output)
assert list(saved_summary.columns) == list(summary_output.columns)
assert len(saved_summary) == len(summary_output)
assert saved_summary.iloc[0]["course"] == summary_output.iloc[0]["course"]
print("Saved-output reconciliation passed:", review_path, summary_path)
'''
    insert_after(document, f"l34-{language}-count-code", [
        md(f"l34-{language}-conditional", conditional_text),
        code(f"l34-{language}-conditional-code", conditional_code),
    ])
    insert_after(document, f"l34-{language}-ratio-code", [
        md(f"l34-{language}-ranking", ranking_text),
        code(f"l34-{language}-ranking-code", ranking_code),
    ])
    insert_after(document, f"l34-{language}-validate-code", [
        md(f"l34-{language}-outputs", output_text),
        code(f"l34-{language}-outputs-code", output_code),
    ])
    complete_id = f"l34-{language}-complete"
    for cell in document["cells"]:
        if cell.get("id") == complete_id and complete.strip() not in "".join(cell["source"]):
            cell["source"] = ("".join(cell["source"]) + complete).splitlines(keepends=True)
    save(path, document)


def update_generator() -> None:
    path = LAB / "generate-notebooks.py"
    text = path.read_text(encoding="utf-8")
    if "Expected 43 reviewed Notebook templates" in text:
        return
    old = 'if len(notebooks) != 41:\n        raise ValueError(f"Expected 41 reviewed Notebook templates, found {len(notebooks)}")'
    new = 'if len(notebooks) != 43:\n        raise ValueError(f"Expected 43 reviewed Notebook templates, found {len(notebooks)}")'
    if text.count(old) != 1:
        raise RuntimeError("Notebook count guard was not found exactly once")
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        stream.write(text.replace(old, new))


def main() -> None:
    subprocess.run([sys.executable, str(ROOT / "scripts/finalize-python-project3a-v4.py")], check=True)
    add_project_notebook_ids(TEMPLATES / "P3A_school_meal_delivery_review.ipynb", "en")
    add_project_notebook_ids(TEMPLATES / "ja/P3A_school_meal_delivery_review.ipynb", "ja")
    update_lesson33(TEMPLATES / "09_cleaning_audit_trail.ipynb", "en")
    update_lesson33(TEMPLATES / "ja/09_cleaning_audit_trail.ipynb", "ja")
    update_lesson34(TEMPLATES / "10_grouping_statistics.ipynb", "en")
    update_lesson34(TEMPLATES / "ja/10_grouping_statistics.ipynb", "ja")
    update_generator()
    print(json.dumps({"project_notebooks": 2, "lesson33": 2, "lesson34": 2, "template_count": 43}))


if __name__ == "__main__":
    main()
