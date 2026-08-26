#!/usr/bin/env python3
"""Build the bilingual Lesson 3.3 notebooks and its concept contract."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id: str, source: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": source.splitlines(keepends=True)}


def code(cell_id: str, source: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": source.splitlines(keepends=True)}


SETUP = '''from pathlib import Path
import pandas as pd


def find_course_data(filename):
    """Find a course data file without depending on the notebook start folder."""
    roots = [Path.cwd(), *Path.cwd().parents, Path.home() / "work", Path("/opt/python-lab/course-materials")]
    checked = []
    for root in roots:
        for candidate in (root / "data" / filename, root / filename):
            candidate = candidate.expanduser()
            if candidate in checked:
                continue
            checked.append(candidate)
            if candidate.is_file():
                return candidate
    locations = "\\n".join(f"- {candidate}" for candidate in checked)
    raise FileNotFoundError(f"Course data file {filename!r} was not found. Checked:\\n{locations}")


data_file = find_course_data("learning-centres-practice.csv")
raw = pd.read_csv(data_file)
print("Loading:", data_file.resolve())
print("Rows:", len(raw), "Columns:", len(raw.columns))
'''


def notebook(language: str) -> dict:
    if language == "ja":
        cells = [
            md("l33-ja-title", "# 3.3 — データのクリーニングと監査記録\n\n元データを保持したまま品質問題を検出し、規則・件数・処置・検証結果を記録できる分析用データを作ります。"),
            md("l33-ja-flow", "## クリーニングは、都合よく値を消す作業ではない\n\n3.2では、条件に合う行を選びました。しかし、欠損や表記ゆれ、不可能な値を含むままでは、同じ抽出式でも意味のある結果になりません。そこで、まず元データを保存し、問題を数え、判定規則を決め、その規則に従って処置し、最後に件数と制約を再確認します。この順序が監査可能なクリーニングです。"),
            code("l33-ja-setup", SETUP),
            md("l33-ja-profile", "## 修正する前に、元データを観察する\n\n欠損件数、データ型、カテゴリ値、数値範囲を先に表示します。`raw`は読み込んだ状態のまま残し、処理は`clean = raw.copy()`から始めます。元データと処理後データを区別できなければ、何を変えたか検証できません。"),
            code("l33-ja-profile-code", '''print(raw.dtypes)
print("Missing values:\\n", raw.isna().sum())
print("District labels:", sorted(raw["district"].dropna().unique()))
print(raw[["registered", "attended", "completed", "material_cost"]].describe())

clean = raw.copy()
'''),
            md("l33-ja-types", "## 型変換は、変換できなかった値を明らかにする\n\nCSVの数値列に文字が混じると、列全体が文字列として読まれることがあります。`pd.to_numeric(..., errors=\"coerce\")`は変換不能値を欠損値へ変えます。ただし、変換前から空だった値と、新たに変換できなかった値は別の問題です。変換前後のマスクを比較し、発生件数を記録します。"),
            code("l33-ja-types-code", '''numeric_columns = ["registered", "attended", "completed", "training_hours", "material_cost"]
conversion_failures = {}
for column in numeric_columns:
    before_missing = clean[column].isna()
    converted = pd.to_numeric(clean[column], errors="coerce")
    failed = converted.isna() & ~before_missing
    conversion_failures[column] = int(failed.sum())
    clean[column] = converted

print("Conversion failures:", conversion_failures)
'''),
            md("l33-ja-missing", "## 欠損値は0ではなく、意味を確認する必要がある\n\n出席者数の空欄を0で埋めると、「未報告」を「出席者なし」に変えてしまいます。平均や割合も変わります。根拠がなければ推測で補完せず、`isna()`でフラグを作り、集計対象から除外するか、確認待ちとして隔離するかを記録します。"),
            code("l33-ja-missing-code", '''missing_attended = clean["attended"].isna()
print("Missing attended:", int(missing_attended.sum()))
print(clean.loc[missing_attended, ["month", "centre_id", "attended", "completed"]])
'''),
            md("l33-ja-text", "## 表記の正規化では、元の文字列を残す\n\n前後の空白や大文字・小文字の違いで、同じ地区が別カテゴリになることがあります。新しい列へ`strip()`と`title()`を適用し、変化した行を数えます。ただし、似ている語を自動的に同一視してはいけません。表記規則や対応表で同じ意味だと確認できる場合だけ統一します。"),
            code("l33-ja-text-code", '''clean["district_raw"] = clean["district"]
clean["district"] = clean["district"].astype("string").str.strip().str.title()
changed_district = clean["district_raw"].astype("string") != clean["district"]
print("Changed district labels:", int(changed_district.sum()))
print(clean.loc[changed_district, ["district_raw", "district"]].drop_duplicates())
'''),
            md("l33-ja-rules", "## 業務上成立する範囲を条件式で表す\n\nこのデータでは、人数は0以上で、登録者数以上に出席者は存在せず、出席者数以上に修了者は存在しないと定義します。各規則を別のブールマスクにすると、どの規則に何件違反したか説明できます。欠損は不正値と同一視せず、別に数えます。"),
            code("l33-ja-rules-code", '''negative_count = (clean[["registered", "attended", "completed"]] < 0).any(axis=1)
attendance_over_registered = clean["attended"].notna() & (clean["attended"] > clean["registered"])
completion_over_attendance = clean["completed"].notna() & clean["attended"].notna() & (clean["completed"] > clean["attended"])
negative_cost = clean["material_cost"].notna() & (clean["material_cost"] < 0)

print("Negative learner counts:", int(negative_count.sum()))
print("Attendance above registration:", int(attendance_over_registered.sum()))
print("Completion above attendance:", int(completion_over_attendance.sum()))
print("Negative material cost:", int(negative_cost.sum()))
print(clean.loc[completion_over_attendance, ["month", "centre_id", "registered", "attended", "completed"]])
'''),
            md("l33-ja-duplicates", "## 重複は、業務上の一意キーを決めて調べる\n\n全列が同じかどうかだけでは、二重登録を見つけられないことがあります。この表では「センター・月・コース」を1記録と定義し、`duplicated(..., keep=False)`で重複グループの全行を表示します。同じセンターが別月に現れることは正当なので、キーの定義が先です。"),
            code("l33-ja-duplicates-code", '''business_key = ["centre_id", "month", "course"]
duplicate_key = clean.duplicated(subset=business_key, keep=False)
print("Rows with duplicate business keys:", int(duplicate_key.sum()))
print(clean.loc[duplicate_key].sort_values(business_key))
'''),
            md("l33-ja-actions", "## 検出と処置を分け、分析用データを作る\n\n問題を見つけた直後に元の行を削除してはいけません。信頼できる原資料から修正できるなら修正し、表記規則が明確なら正規化し、根拠が足りなければ欠損または確認待ちとして扱います。ここでは不可能な修了者数を推測で直さず、分析対象フラグをFalseにします。`raw`と`clean`の行数は保持します。"),
            code("l33-ja-actions-code", '''clean["analysis_ready"] = ~(
    missing_attended
    | negative_count
    | attendance_over_registered
    | completion_over_attendance
    | negative_cost
    | duplicate_key
)
analysis = clean.loc[clean["analysis_ready"]].copy()
print("Raw rows:", len(raw))
print("Retained clean rows:", len(clean))
print("Analysis-ready rows:", len(analysis))
print("Flagged rows:", int((~clean["analysis_ready"]).sum()))
'''),
            md("l33-ja-audit", "## 監査記録には、規則・件数・処置・残件を残す\n\n監査記録はコードの代わりではなく、コードが行った判断の要約です。問題名、検出規則、影響件数、処置、処置後の残件を記録します。0件だった検査も、確認した証拠として残します。"),
            code("l33-ja-audit-code", '''audit = pd.DataFrame([
    {"issue": "missing attended", "rule": "attended is missing", "affected": int(missing_attended.sum()), "action": "exclude from rate analysis; request review"},
    {"issue": "district spelling", "rule": "strip whitespace and title case", "affected": int(changed_district.sum()), "action": "normalise; preserve district_raw"},
    {"issue": "completion above attendance", "rule": "completed <= attended", "affected": int(completion_over_attendance.sum()), "action": "flag; do not guess replacement"},
    {"issue": "duplicate business key", "rule": "unique centre_id + month + course", "affected": int(duplicate_key.sum()), "action": "review duplicate group"},
])
audit
'''),
            md("l33-ja-validate", "## 最後に、同じ規則と件数で検証する\n\n処置後のデータへ同じ制約を適用し、違反が残っていないことを確認します。さらに、元件数が「分析可能件数＋フラグ件数」と一致するか照合します。`assert`は期待が崩れた場所で処理を止め、翌月のデータ更新で規則が破られたことを知らせます。"),
            code("l33-ja-validate-code", '''assert len(raw) == len(clean)
assert len(clean) == int(clean["analysis_ready"].sum()) + int((~clean["analysis_ready"]).sum())
assert not (analysis["completed"] > analysis["attended"]).any()
assert not analysis.duplicated(subset=business_key).any()
print("Validation passed")
'''),
            md("l33-ja-transfer", "## 応用練習\n\n練習CSVについて、欠損した修了者数、登録者数を上回る出席者数、0以下の研修時間、負の教材費、業務キーの重複を検査してください。各マスクの件数を表示し、処置を推測で決めず、規則・件数・提案する処置を監査表にまとめます。最後に元件数との照合式を追加してください。"),
            code("l33-ja-work", "# ここに応用練習の解答を書きます。\n"),
            md("l33-ja-complete", "## このレッスンで到達したこと\n\n元データを保持し、修正前に観察し、型変換失敗・欠損・表記ゆれ・範囲違反・項目間制約・業務キー重複を別々に検出できるようになりました。検出と処置を分け、件数を照合し、監査記録と`assert`で再現可能に検証できれば、Notebookを保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l33-en-title", "# 3.3 — Data cleaning with an audit trail\n\nPreserve the source, detect quality problems, and create analysis-ready data with documented rules, counts, actions, and validation."),
            md("l33-en-flow", "## Cleaning is not deleting inconvenient values\n\nLesson 3.2 selected rows. Selection cannot produce reliable evidence if missing values, inconsistent labels, or impossible relationships remain unexplained. Preserve the source, profile problems, define rules, flag affected rows, choose an action, and validate counts and constraints. That order makes cleaning auditable."),
            code("l33-en-setup", SETUP),
            md("l33-en-profile", "## Profile the source before changing it\n\nDisplay missing counts, data types, category values, and numeric ranges first. Keep `raw` unchanged and begin processing with `clean = raw.copy()`. Without separate source and working data, you cannot verify what changed."),
            code("l33-en-profile-code", '''print(raw.dtypes)
print("Missing values:\\n", raw.isna().sum())
print("District labels:", sorted(raw["district"].dropna().unique()))
print(raw[["registered", "attended", "completed", "material_cost"]].describe())

clean = raw.copy()
'''),
            md("l33-en-types", "## Type conversion must expose values that failed conversion\n\nA numeric CSV column containing text may be read as text. `pd.to_numeric(..., errors=\"coerce\")` converts invalid text to missing, but a source blank and a new conversion failure are different problems. Compare masks before and after conversion and record the count."),
            code("l33-en-types-code", '''numeric_columns = ["registered", "attended", "completed", "training_hours", "material_cost"]
conversion_failures = {}
for column in numeric_columns:
    before_missing = clean[column].isna()
    converted = pd.to_numeric(clean[column], errors="coerce")
    failed = converted.isna() & ~before_missing
    conversion_failures[column] = int(failed.sum())
    clean[column] = converted
print("Conversion failures:", conversion_failures)
'''),
            md("l33-en-missing", "## Missing does not mean zero\n\nFilling a blank attendance count with zero changes “not reported” into “nobody attended” and changes rates and averages. Do not impute without evidence. Flag it with `isna()` and document whether it is excluded from a calculation or quarantined for review."),
            code("l33-en-missing-code", '''missing_attended = clean["attended"].isna()
print("Missing attended:", int(missing_attended.sum()))
print(clean.loc[missing_attended, ["month", "centre_id", "attended", "completed"]])
'''),
            md("l33-en-text", "## Normalise labels while preserving the source text\n\nWhitespace and case variations can split one district into several groups. Apply `strip()` and `title()` to a new working value and count changed rows while preserving `district_raw`. Never merge merely similar words unless an agreed rule or mapping confirms that they mean the same category."),
            code("l33-en-text-code", '''clean["district_raw"] = clean["district"]
clean["district"] = clean["district"].astype("string").str.strip().str.title()
changed_district = clean["district_raw"].astype("string") != clean["district"]
print("Changed district labels:", int(changed_district.sum()))
print(clean.loc[changed_district, ["district_raw", "district"]].drop_duplicates())
'''),
            md("l33-en-rules", "## Express valid operational relationships as conditions\n\nFor this dataset, counts must be non-negative, attendance cannot exceed registration, and completion cannot exceed attendance. Separate Boolean masks explain how many rows violate each rule. Count missingness separately from invalidity."),
            code("l33-en-rules-code", '''negative_count = (clean[["registered", "attended", "completed"]] < 0).any(axis=1)
attendance_over_registered = clean["attended"].notna() & (clean["attended"] > clean["registered"])
completion_over_attendance = clean["completed"].notna() & clean["attended"].notna() & (clean["completed"] > clean["attended"])
negative_cost = clean["material_cost"].notna() & (clean["material_cost"] < 0)
print("Negative learner counts:", int(negative_count.sum()))
print("Attendance above registration:", int(attendance_over_registered.sum()))
print("Completion above attendance:", int(completion_over_attendance.sum()))
print("Negative material cost:", int(negative_cost.sum()))
print(clean.loc[completion_over_attendance, ["month", "centre_id", "registered", "attended", "completed"]])
'''),
            md("l33-en-duplicates", "## Detect duplicates using a defined business key\n\nExact duplicate rows are not the only possible double entry. Define one record as centre, month, and course, then use `duplicated(..., keep=False)` to display every row in a duplicate group. The same centre in another month is legitimate, so the key definition comes first."),
            code("l33-en-duplicates-code", '''business_key = ["centre_id", "month", "course"]
duplicate_key = clean.duplicated(subset=business_key, keep=False)
print("Rows with duplicate business keys:", int(duplicate_key.sum()))
print(clean.loc[duplicate_key].sort_values(business_key))
'''),
            md("l33-en-actions", "## Separate detection from action\n\nDo not delete a row immediately after detecting a problem. Correct it only from an authoritative source, normalise it only under an explicit rule, or mark it missing or pending review when evidence is insufficient. Here an impossible completion is not guessed; it is flagged as not analysis-ready. Both `raw` and `clean` retain their row counts."),
            code("l33-en-actions-code", '''clean["analysis_ready"] = ~(
    missing_attended | negative_count | attendance_over_registered
    | completion_over_attendance | negative_cost | duplicate_key
)
analysis = clean.loc[clean["analysis_ready"]].copy()
print("Raw rows:", len(raw))
print("Retained clean rows:", len(clean))
print("Analysis-ready rows:", len(analysis))
print("Flagged rows:", int((~clean["analysis_ready"]).sum()))
'''),
            md("l33-en-audit", "## Record rule, count, action, and remaining issue\n\nAn audit log summarises the decisions performed by code. Record the issue, detection rule, affected count, action, and remaining unresolved count. A zero count still documents that a check was performed."),
            code("l33-en-audit-code", '''audit = pd.DataFrame([
    {"issue": "missing attended", "rule": "attended is missing", "affected": int(missing_attended.sum()), "action": "exclude from rate analysis; request review"},
    {"issue": "district spelling", "rule": "strip whitespace and title case", "affected": int(changed_district.sum()), "action": "normalise; preserve district_raw"},
    {"issue": "completion above attendance", "rule": "completed <= attended", "affected": int(completion_over_attendance.sum()), "action": "flag; do not guess replacement"},
    {"issue": "duplicate business key", "rule": "unique centre_id + month + course", "affected": int(duplicate_key.sum()), "action": "review duplicate group"},
])
audit
'''),
            md("l33-en-validate", "## Reapply constraints and reconcile counts\n\nApply the same constraints after the action and confirm that source count equals analysis-ready count plus flagged count. `assert` stops the workflow where an expectation fails, making a new monthly data problem visible."),
            code("l33-en-validate-code", '''assert len(raw) == len(clean)
assert len(clean) == int(clean["analysis_ready"].sum()) + int((~clean["analysis_ready"]).sum())
assert not (analysis["completed"] > analysis["attended"]).any()
assert not analysis.duplicated(subset=business_key).any()
print("Validation passed")
'''),
            md("l33-en-transfer", "## Transfer exercise\n\nCheck the practice CSV for missing completion, attendance above registration, non-positive training hours, negative material cost, and duplicate business keys. Display each mask count. Do not guess corrections; create an audit table containing the rule, count, and proposed action, then add a reconciliation assertion."),
            code("l33-en-work", "# Write the transfer solution here.\n"),
            md("l33-en-complete", "## Completion check\n\nYou can now preserve the source, profile before changing, distinguish conversion failure from source missingness, normalise labels without losing raw text, test ranges and cross-field constraints, define duplicate keys, separate detection from action, reconcile counts, and document decisions with an audit table and assertions. Save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "3.3", "language": language, "concepts": [f"C{i:02d}" for i in range(1, 11)], "revision": 24},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "09_cleaning_audit_trail.ipynb", "ja": TEMPLATES / "ja/09_cleaning_audit_trail.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "preserve source data and profile it before changes",
        "detect conversion failures separately from source missingness",
        "distinguish missing values from zero and avoid unsupported imputation",
        "normalise category text while retaining original values",
        "test ranges and cross-field operational constraints separately",
        "define a business key before detecting duplicate records",
        "separate detection from correction exclusion or review action",
        "retain source and working row counts while flagging analysis-ready rows",
        "record rules affected counts actions and unresolved issues in an audit log",
        "reapply constraints and reconcile counts with assertions",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "3.3 Data cleaning with an audit trail",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"C{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L33R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/09_cleaning_audit_trail.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/09_cleaning_audit_trail.ipynb",
        },
        "implementation": "scripts/upgrade-python-lesson33-v24.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-3-3-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
