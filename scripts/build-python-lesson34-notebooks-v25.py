#!/usr/bin/env python3
"""Build bilingual Lesson 3.4 notebooks and the concept contract."""
from __future__ import annotations
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"

def md(i, s): return {"cell_type":"markdown","id":i,"metadata":{},"source":s.splitlines(keepends=True)}
def code(i, s): return {"cell_type":"code","execution_count":None,"id":i,"metadata":{},"outputs":[],"source":s.splitlines(keepends=True)}

SETUP = '''from pathlib import Path
import pandas as pd

def find_course_data(filename):
    roots = [Path.cwd(), *Path.cwd().parents, Path.home() / "work", Path("/opt/python-lab/course-materials")]
    checked = []
    for root in roots:
        for candidate in (root / "data" / filename, root / filename):
            if candidate in checked:
                continue
            checked.append(candidate)
            if candidate.is_file():
                return candidate
    raise FileNotFoundError("Course data was not found:\\n" + "\\n".join(map(str, checked)))

raw = pd.read_csv(find_course_data("learning-centres-practice.csv"))
clean = raw.copy()
clean["district"] = clean["district"].astype("string").str.strip().str.title()
business_key = ["centre_id", "month", "course"]
quality_problem = (
    clean["attended"].isna()
    | (clean["completed"] > clean["attended"])
    | clean.duplicated(subset=business_key, keep=False)
)
analysis = clean.loc[~quality_problem].copy()
analysis["completion_rate"] = analysis["completed"] / analysis["registered"] * 100
print("Source:", len(raw), "analysis-ready:", len(analysis), "flagged:", int(quality_problem.sum()))
'''

def notebook(lang):
    if lang == "ja":
        cells = [
            md("l34-ja-title", "# 3.4 — グループ化と要約統計\n\n明細行を意思決定に使える表へ集約し、集計単位、件数、分母、欠損、分布を明示して結果を検証します。"),
            md("l34-ja-grain", "## 集計を始める前に、一行と一グループの意味を決める\n\n元表の一行はセンター・月・コースの記録です。地区別にまとめるなら結果の一行は一地区、地区・コース別なら一地区・一コースになります。この粒度を決めずに`groupby()`を書くと、同じ数値でも何を数えたか説明できません。"),
            code("l34-ja-setup", SETUP),
            md("l34-ja-split", "## groupbyは分割・計算・結合を行う\n\n`groupby(キー)`は同じキーの行を分け、各組へ同じ集計を適用し、結果を結合します。名前付き集計を使うと、出力列の意味をコードに残せます。`reset_index()`はグループキーを通常の列へ戻し、後の表やグラフで扱いやすくします。"),
            code("l34-ja-split-code", '''district_summary = (
    analysis.groupby("district", dropna=False)
    .agg(
        centre_months=("centre_id", "size"),
        centres=("centre_id", "nunique"),
        registered_total=("registered", "sum"),
        completed_total=("completed", "sum"),
    )
    .reset_index()
)
district_summary
'''),
            md("l34-ja-count", "## size・count・nuniqueは数えている対象が違う\n\n`size`は欠損を含む行数、`count`は指定列の欠損でない値の数、`nunique`は異なる値の数です。「センター月数」「報告済み出席者数」「センター数」は別の指標なので、質問に合うものを選びます。"),
            code("l34-ja-count-code", '''count_check = analysis.groupby("course").agg(
    rows=("centre_id", "size"),
    reported_attendance=("attended", "count"),
    distinct_centres=("centre_id", "nunique"),
)
count_check
'''),
            md("l34-ja-stats", "## 合計と中心、ばらつきは別の問いに答える\n\n`sum`は総量、`mean`は算術平均、`median`は並べた中央、`min`と`max`は範囲の端、`std`は平均からの散らばりを表します。平均は極端な値の影響を受けやすいため、件数、中央値、最小・最大と一緒に読みます。標準偏差は単位を保ちますが、原因までは説明しません。"),
            code("l34-ja-stats-code", '''distribution = analysis.groupby("course")["registered"].agg(
    ["size", "sum", "mean", "median", "min", "max", "std"]
)
distribution.round(2)
'''),
            md("l34-ja-ratio", "## 割合は分子と分母を同じ単位で合計してから求める\n\nコース全体の修了率は、修了者合計を登録者合計で割ります。各センター月の率を単純平均すると、小規模行と大規模行へ同じ重みを与えるため、全参加者の率とは一致しません。どちらが正しいかではなく、問いが「典型的なセンター月」か「全登録者」かで決まります。"),
            code("l34-ja-ratio-code", '''course_summary = analysis.groupby("course").agg(
    records=("centre_id", "size"),
    registered_total=("registered", "sum"),
    completed_total=("completed", "sum"),
    mean_row_completion_rate=("completion_rate", "mean"),
    median_row_completion_rate=("completion_rate", "median"),
    material_cost_total=("material_cost", "sum"),
)
course_summary["overall_completion_rate"] = (
    course_summary["completed_total"] / course_summary["registered_total"] * 100
)
course_summary["cost_per_completion"] = (
    course_summary["material_cost_total"] / course_summary["completed_total"]
)
course_summary.round(2)
'''),
            md("l34-ja-multikey", "## 複数キーでは比較の階層を保つ\n\n地区だけの集計と地区・コースの集計は粒度が違います。複数キーでまとめ、`sort_values()`で順序を明示します。結果を結合するときは、粒度の違う表を無条件に足したり平均したりしません。"),
            code("l34-ja-multikey-code", '''district_course = (
    analysis.groupby(["district", "course"], dropna=False)
    .agg(records=("centre_id", "size"), registered=("registered", "sum"), completed=("completed", "sum"))
    .reset_index()
)
district_course["completion_rate"] = district_course["completed"] / district_course["registered"] * 100
district_course.sort_values(["district", "course"]).round(2)
'''),
            md("l34-ja-denominator", "## 構成比では分母と合計100%を確認する\n\n地区内の登録者構成比なら、各地区・コースの登録者数をその地区の登録者合計で割ります。全体合計を分母にすれば別の問いになります。`transform('sum')`は元の各行へ所属グループの合計を対応させ、地区ごとの構成比合計を検証できます。"),
            code("l34-ja-denominator-code", '''district_course["district_registered_total"] = district_course.groupby("district")["registered"].transform("sum")
district_course["share_within_district"] = district_course["registered"] / district_course["district_registered_total"] * 100
print(district_course.groupby("district")["share_within_district"].sum().round(6))
'''),
            md("l34-ja-validate", "## 部分集計を全体合計と照合する\n\nグループ別合計をもう一度合計すると、分析対象全体の合計と一致するはずです。行数、登録者数、修了者数、教材費を照合します。小さなグループの平均には件数を添え、差が原因や優劣を証明するとは解釈しません。"),
            code("l34-ja-validate-code", '''assert int(course_summary["records"].sum()) == len(analysis)
assert course_summary["registered_total"].sum() == analysis["registered"].sum()
assert course_summary["completed_total"].sum() == analysis["completed"].sum()
assert abs(course_summary["material_cost_total"].sum() - analysis["material_cost"].sum()) < 1e-9
print("Reconciliation passed")
'''),
            md("l34-ja-transfer", "## 応用練習\n\n月別・コース別に、記録数、異なるセンター数、登録者合計、出席者合計、修了者合計、全体修了率、教材費合計、一人修了当たり教材費を求めてください。各率の分母を文章で書き、単純な行別率平均とも比較し、全体合計との照合を追加します。件数が小さい比較を一つ指摘してください。"),
            code("l34-ja-work", "# ここに応用練習の解答を書きます。\n"),
            md("l34-ja-complete", "## このレッスンで到達したこと\n\n集計粒度を定義し、`groupby()`と名前付き`agg()`を使い、`size`・`count`・`nunique`、合計、平均、中央値、最小・最大、標準偏差を使い分けられるようになりました。比率の分子と分母を先に合計し、複数キーと構成比を扱い、部分合計と全体を照合できれば理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l34-en-title", "# 3.4 — Grouping and summary statistics\n\nCompress detail into decision-sized tables while making grain, counts, denominators, missingness, and distribution explicit."),
            md("l34-en-grain", "## Define what one source row and one result row mean\n\nOne source row is a centre-month-course record. A district summary has one result row per district; a district-course summary has one per pair. Define this grain before writing `groupby()` or a correct number may still be impossible to explain."),
            code("l34-en-setup", SETUP),
            md("l34-en-split", "## groupby splits, applies, and combines\n\n`groupby(key)` splits rows by matching keys, applies the same aggregations, and combines results. Named aggregation preserves output meaning. `reset_index()` returns group keys to ordinary columns for later tables and charts."),
            code("l34-en-split-code", '''district_summary = (analysis.groupby("district", dropna=False).agg(
    centre_months=("centre_id", "size"), centres=("centre_id", "nunique"),
    registered_total=("registered", "sum"), completed_total=("completed", "sum"),
).reset_index())
district_summary
'''),
            md("l34-en-count", "## size, count, and nunique count different things\n\n`size` counts rows including missing values, `count` counts non-missing values in one column, and `nunique` counts distinct values. Centre-month records, reported attendance values, and distinct centres are different measures."),
            code("l34-en-count-code", '''analysis.groupby("course").agg(
    rows=("centre_id", "size"), reported_attendance=("attended", "count"), distinct_centres=("centre_id", "nunique")
)
'''),
            md("l34-en-stats", "## Totals, centre, and spread answer different questions\n\n`sum` measures volume; `mean` the arithmetic average; `median` the middle ordered value; `min` and `max` the endpoints; and `std` spread around the mean. Extremes can pull the mean, so interpret it with count, median, and range. Standard deviation retains the variable's unit but does not explain causes."),
            code("l34-en-stats-code", '''analysis.groupby("course")["registered"].agg(
    ["size", "sum", "mean", "median", "min", "max", "std"]
).round(2)
'''),
            md("l34-en-ratio", "## Aggregate compatible numerators and denominators before a rate\n\nOverall course completion is total completed divided by total registered. Averaging row rates gives every centre-month equal weight, so it does not generally equal the rate for all learners. One answers about a typical record; the other about all registered learners."),
            code("l34-en-ratio-code", '''course_summary = analysis.groupby("course").agg(
    records=("centre_id", "size"), registered_total=("registered", "sum"), completed_total=("completed", "sum"),
    mean_row_completion_rate=("completion_rate", "mean"), median_row_completion_rate=("completion_rate", "median"),
    material_cost_total=("material_cost", "sum"),
)
course_summary["overall_completion_rate"] = course_summary["completed_total"] / course_summary["registered_total"] * 100
course_summary["cost_per_completion"] = course_summary["material_cost_total"] / course_summary["completed_total"]
course_summary.round(2)
'''),
            md("l34-en-multikey", "## Multiple keys preserve comparison hierarchy\n\nDistrict and district-course summaries have different grains. Group by both keys and sort explicitly. Never add or average results from incompatible grains without defining the intended relationship."),
            code("l34-en-multikey-code", '''district_course = analysis.groupby(["district", "course"], dropna=False).agg(
    records=("centre_id", "size"), registered=("registered", "sum"), completed=("completed", "sum")
).reset_index()
district_course["completion_rate"] = district_course["completed"] / district_course["registered"] * 100
district_course.sort_values(["district", "course"]).round(2)
'''),
            md("l34-en-denominator", "## State the denominator and verify proportions sum to 100%\n\nFor course share within a district, divide each district-course registration count by that district's total. Dividing by the grand total answers another question. `transform('sum')` aligns each group total back to its rows so shares can be checked."),
            code("l34-en-denominator-code", '''district_course["district_registered_total"] = district_course.groupby("district")["registered"].transform("sum")
district_course["share_within_district"] = district_course["registered"] / district_course["district_registered_total"] * 100
print(district_course.groupby("district")["share_within_district"].sum().round(6))
'''),
            md("l34-en-validate", "## Reconcile grouped totals with the detail\n\nSumming grouped totals should reproduce the analysis-detail total. Reconcile row count, registered, completed, and material cost. Attach counts to group averages, and do not treat a difference from a small group as proof of cause or superiority."),
            code("l34-en-validate-code", '''assert int(course_summary["records"].sum()) == len(analysis)
assert course_summary["registered_total"].sum() == analysis["registered"].sum()
assert course_summary["completed_total"].sum() == analysis["completed"].sum()
assert abs(course_summary["material_cost_total"].sum() - analysis["material_cost"].sum()) < 1e-9
print("Reconciliation passed")
'''),
            md("l34-en-transfer", "## Transfer exercise\n\nBy month and course, calculate record count, distinct centres, total registered, attended and completed, overall completion rate, material cost, and cost per completion. State every rate denominator, compare with the mean row rate, reconcile grand totals, and identify one comparison based on a small count."),
            code("l34-en-work", "# Write the transfer solution here.\n"),
            md("l34-en-complete", "## Completion check\n\nYou can define grain; use `groupby()` and named `agg()`; distinguish `size`, `count`, and `nunique`; choose totals, mean, median, min, max, and standard deviation; aggregate rate numerators and denominators; group by multiple keys; calculate within-group shares; and reconcile grouped totals. Save and continue to the learning check."),
        ]
    return {"cells":cells,"metadata":{"kernelspec":{"display_name":"Python 3 (ipykernel)","language":"python","name":"python3"},"language_info":{"name":"python","version":"3"},"pyai":{"lesson":"3.4","language":lang,"concepts":[f"D{i:02d}" for i in range(1,11)],"revision":25}},"nbformat":4,"nbformat_minor":5}

def main():
    for lang, path in {"en":TEMPLATES/"10_grouping_statistics.ipynb","ja":TEMPLATES/"ja/10_grouping_statistics.ipynb"}.items():
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(notebook(lang),ensure_ascii=False,indent=1)+"\n",encoding="utf-8")
        print("wrote",path.relative_to(ROOT))
    desc = [
        "define source and result grain before aggregation", "use groupby named aggregation and reset_index",
        "distinguish size count and nunique", "choose totals centre and spread statistics",
        "interpret mean median range and standard deviation with group count", "compute rates from aggregated compatible numerators and denominators",
        "distinguish an overall weighted rate from a mean row rate", "group by multiple keys without mixing grains",
        "calculate within-group proportions with an explicit denominator", "reconcile grouped counts and totals with detail and qualify small groups",
    ]
    m={"schema_version":1,"lesson":"3.4 Grouping and summary statistics","canonical_language":"en","adaptations":["ja"],"concepts":[{"id":f"D{i:02d}","description":d,"lesson":True,"notebook":True,"question":f"L34R-{i:02d}","teacher":False} for i,d in enumerate(desc,1)],"notebooks":{"en":"sample-content/introduction-to-python/python-lab/templates/10_grouping_statistics.ipynb","ja":"sample-content/introduction-to-python/python-lab/templates/ja/10_grouping_statistics.ipynb"},"implementation":"scripts/upgrade-python-lesson34-v25.php"}
    p=ROOT/"sample-content/introduction-to-python/localization/lesson-3-4-concept-map-v1.json"
    p.write_text(json.dumps(m,ensure_ascii=False,indent=2)+"\n",encoding="utf-8")
    print("wrote",p.relative_to(ROOT))

if __name__ == "__main__": main()
