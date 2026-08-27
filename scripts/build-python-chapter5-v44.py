from __future__ import annotations

import json
from pathlib import Path
import shutil


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "sample-content" / "introduction-to-python"
TEMPLATES = CONTENT / "python-lab" / "templates"
LAB = Path("/mnt/d/workspace/python-lab-rescue/course-materials")


def md(text: str) -> dict:
    return {"cell_type": "markdown", "metadata": {}, "source": [text.strip() + "\n"]}


def code(text: str) -> dict:
    return {
        "cell_type": "code",
        "execution_count": None,
        "metadata": {},
        "outputs": [],
        "source": [text.strip() + "\n"],
    }


def notebook(cells: list[dict], prefix: str) -> dict:
    for number, cell in enumerate(cells, start=1):
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


def lesson_51(ja: bool) -> dict:
    cells = [
        md("# 5.1 — 問いから図へ" if ja else "# 5.1 — From a question to a chart"),
        md((
            "図の種類を先に決めません。まず、何を比較・追跡・検討したいのかと、図の一点または一本が何を表すかを決めます。"
            if ja else
            "Do not choose a chart type first. Define the comparison and what one mark in the chart represents."
        )),
        code("""
from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt

project = Path.cwd() / "projects" / "clinic-wait-evidence"
records = pd.read_csv(project / "data" / "clinic-waits-practice.csv")
print("Shape:", records.shape)
display(records.head())
"""),
        md("## 5.1.1 カテゴリの大きさを棒で比べる" if ja else "## 5.1.1 Compare category magnitude with bars"),
        code("""
clinic = records.groupby("clinic_name", as_index=False).agg(
    patients_seen=("patients_seen", "sum"),
    total_wait_minutes=("total_wait_minutes", "sum"),
)
clinic = clinic.sort_values("total_wait_minutes")
display(clinic)
ax = clinic.plot.barh(x="clinic_name", y="total_wait_minutes", legend=False)
ax.set(title="Total waiting burden by clinic", xlabel="Total waiting time (minutes)", ylabel="")
ax.set_xlim(left=0)
plt.tight_layout()
plt.show()
"""),
        md("## 5.1.2 時間順の変化を線で追う" if ja else "## 5.1.2 Follow ordered change with a line"),
        code("""
trend = records[(records["clinic_id"] == "C002") & (records["time_slot"] == "Evening")].copy()
trend["average_wait_minutes"] = trend["total_wait_minutes"] / trend["patients_seen"]
trend = trend.sort_values("week")
display(trend[["week", "patients_seen", "average_wait_minutes"]])
ax = trend.plot(x="week", y="average_wait_minutes", marker="o", legend=False)
ax.set(title="Riverside evening average wait", xlabel="Week", ylabel="Average wait (minutes)")
plt.tight_layout()
plt.show()
"""),
        md("## 5.1.3 関係と分布を散布図・ヒストグラムで見る" if ja else "## 5.1.3 Examine relationship and distribution"),
        code("""
view = records.assign(average_wait_minutes=records["total_wait_minutes"] / records["patients_seen"])
fig, axes = plt.subplots(1, 2, figsize=(11, 4))
axes[0].scatter(view["patients_seen"], view["average_wait_minutes"], alpha=.75)
axes[0].set(title="Volume and average wait", xlabel="Patients seen", ylabel="Average wait (minutes)")
axes[1].hist(view["average_wait_minutes"], bins=8, edgecolor="white")
axes[1].set(title="Distribution of record-level waits", xlabel="Average wait (minutes)", ylabel="Records")
fig.tight_layout()
plt.show()
"""),
        md((
            "## 統合練習\n患者数を診療所別に比較する図を作り、図の一つの棒が何を表すか、単位、対象期間を説明してください。"
            if ja else
            "## Integrated practice\nCreate a clinic comparison of patients seen. State what one bar represents, its unit, and the period covered."
        )),
    ]
    return notebook(cells, "ja-51" if ja else "en-51")


def lesson_52(ja: bool) -> dict:
    cells = [
        md("# 5.2 — 誤解を生まない比較" if ja else "# 5.2 — Honest comparisons"),
        md((
            "同じ原資料でも、総量・一人当たり・割合は別の問いへ答えます。先に指標と粒度を確定し、比較表を表示してから描画します。"
            if ja else
            "Totals, per-person values, and percentages answer different questions. Define metric and grain, then inspect the plot table before drawing."
        )),
        code("""
from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt

project = Path.cwd() / "projects" / "clinic-wait-evidence"
records = pd.read_csv(project / "data" / "clinic-waits-practice.csv")
"""),
        md("## 5.2.1 互換性のある値を先に合計する" if ja else "## 5.2.1 Aggregate compatible values first"),
        code("""
service = records.groupby(["clinic_id", "clinic_name", "time_slot"], as_index=False).agg(
    records=("week", "size"),
    patients_seen=("patients_seen", "sum"),
    total_wait_minutes=("total_wait_minutes", "sum"),
    over_60_minutes=("over_60_minutes", "sum"),
)
service["average_wait_minutes"] = service["total_wait_minutes"] / service["patients_seen"]
service["over_60_rate"] = service["over_60_minutes"] / service["patients_seen"] * 100
display(service)
assert service["patients_seen"].sum() == records["patients_seen"].sum()
"""),
        md("## 5.2.2 総負担と個人の経験を分ける" if ja else "## 5.2.2 Separate total burden from individual experience"),
        code("""
burden = records.groupby(["clinic_id", "clinic_name"], as_index=False).agg(
    total_wait_minutes=("total_wait_minutes", "sum"),
    patients_seen=("patients_seen", "sum"),
)
burden["average_wait_minutes"] = burden["total_wait_minutes"] / burden["patients_seen"]
print("Largest total burden")
display(burden.sort_values("total_wait_minutes", ascending=False).head(1))
print("Longest average wait by clinic and time slot")
display(service.sort_values("average_wait_minutes", ascending=False).head(1))
"""),
        md("## 5.2.3 軸・順序・件数を比較条件として示す" if ja else "## 5.2.3 Make scale, order, and counts visible"),
        code("""
plot_data = service.assign(service=service["clinic_name"] + " — " + service["time_slot"]).sort_values("average_wait_minutes")
ax = plot_data.plot.barh(x="service", y="average_wait_minutes", legend=False)
ax.set(title="Average wait by clinic and time slot", xlabel="Average wait per patient (minutes)", ylabel="")
ax.set_xlim(left=0)
for index, row in enumerate(plot_data.itertuples()):
    ax.text(row.average_wait_minutes + .5, index, f"n={row.patients_seen}", va="center")
plt.tight_layout()
plt.show()
"""),
        md((
            "## 統合練習\n診療所別の60分超人数合計と、診療所・時間帯別の60分超割合を作り、二つの順位が違う理由を説明してください。"
            if ja else
            "## Integrated practice\nBuild total over-60 patients by clinic and over-60 percentage by clinic/time slot. Explain why the rankings can differ."
        )),
    ]
    return notebook(cells, "ja-52" if ja else "en-52")


def lesson_53(ja: bool) -> dict:
    cells = [
        md("# 5.3 — 図から根拠文へ" if ja else "# 5.3 — From chart to evidence statement"),
        md((
            "強い根拠文は、観察した値、比較範囲、対象期間、重要な限界を短く結びます。図から原因までは推測しません。"
            if ja else
            "A strong evidence statement connects observed values, comparison scope, period, and one material limitation. It does not infer cause from a chart."
        )),
        code("""
from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt

project = Path.cwd() / "projects" / "clinic-wait-evidence"
records = pd.read_csv(project / "data" / "clinic-waits-practice.csv")
service = records.groupby(["clinic_id", "clinic_name", "time_slot"], as_index=False).agg(
    patients_seen=("patients_seen", "sum"),
    total_wait_minutes=("total_wait_minutes", "sum"),
    over_60_minutes=("over_60_minutes", "sum"),
)
service["average_wait_minutes"] = service["total_wait_minutes"] / service["patients_seen"]
service["over_60_rate"] = service["over_60_minutes"] / service["patients_seen"] * 100
target = service.sort_values(["average_wait_minutes", "over_60_rate"], ascending=False).iloc[0]
display(target)
"""),
        md("## 5.3.1 観察・解釈・因果を区別する" if ja else "## 5.3.1 Separate observation, interpretation, and cause"),
        code("""
observation = f"{target['clinic_name']} — {target['time_slot']} had an average wait of {target['average_wait_minutes']:.1f} minutes."
scope = f"The comparison covers {len(records)} clinic-time-week records from {records['week'].min()} to {records['week'].max()}."
limitation = "The records show where waiting was concentrated, but they do not identify the cause."
print(observation)
print(scope)
print(limitation)
"""),
        md("## 5.3.2 判断に使う値を直接示す" if ja else "## 5.3.2 Directly identify the decision value"),
        code("""
plot_data = service.assign(service=service["clinic_name"] + " — " + service["time_slot"]).sort_values("average_wait_minutes")
colours = ["#E45756" if value == target["clinic_name"] + " — " + target["time_slot"] else "#72B7B2" for value in plot_data["service"]]
fig, ax = plt.subplots(figsize=(8, 4.5))
ax.barh(plot_data["service"], plot_data["average_wait_minutes"], color=colours)
ax.set(title="Average wait by clinic and time slot", xlabel="Average wait per patient (minutes)", ylabel="")
ax.set_xlim(left=0)
fig.tight_layout()
output = project / "output" / "lesson53_evidence.png"
output.parent.mkdir(exist_ok=True)
fig.savefig(output, dpi=150, bbox_inches="tight")
plt.show()
print("Saved:", output)
"""),
        md((
            "## 統合練習\n上の図について、数値を含む観察、対象期間、原因を断定しない限界の三文を書いてください。保存したPNGを開き、表題・軸・単位が残っていることも確認します。"
            if ja else
            "## Integrated practice\nWrite three sentences for the chart: a numerical observation, the period, and a non-causal limitation. Open the saved PNG and confirm title, axes, and units remain visible."
        )),
    ]
    return notebook(cells, "ja-53" if ja else "en-53")


def project_notebook(ja: bool) -> dict:
    title = "# 5.4 — 診療所待ち時間プロジェクト" if ja else "# 5.4 — Clinic waiting-time evidence project"
    intro = (
        "Notebookは提出しません。`PROJECT_BRIEF.md`を読み、スターターを保存してから実行します。"
        if ja else
        "The Notebook is not submitted. Read `PROJECT_BRIEF.md`, edit the supplied starter, and save before each run."
    )
    cells = [
        md(title), md(intro),
        code(f"""
from pathlib import Path
project = Path.cwd() / "projects" / "clinic-wait-evidence"
print("Project found:", project.is_dir())
print((project / "PROJECT_BRIEF.md").read_text(encoding="utf-8")[:1600])
"""),
        md("## プログラムを実行する" if ja else "## Run the program"),
        code("!python projects/clinic-wait-evidence/clinic_wait_evidence.py"),
        md("## 自動確認を実行する" if ja else "## Run the checker"),
        code("!python projects/clinic-wait-evidence/check_clinic_wait_evidence.py"),
        md("## 保存された表と図を確認する" if ja else "## Inspect the saved table and figure"),
        code(f"""
import pandas as pd
from IPython.display import Image, display

summary = project / "output" / "clinic_wait_summary.csv"
figure = project / "output" / "clinic_wait_evidence.png"
if summary.is_file() and figure.is_file():
    display(pd.read_csv(summary))
    display(Image(filename=figure))
else:
    print("Run the completed program successfully before inspecting outputs.")
"""),
    ]
    return notebook(cells, "ja-p5" if ja else "en-p5")


def write(name: str, content: dict, ja: bool = False) -> None:
    destination = TEMPLATES / ("ja" if ja else "") / name
    destination.parent.mkdir(parents=True, exist_ok=True)
    destination.write_text(json.dumps(content, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    lab_destination = LAB / ("ja" if ja else "") / name
    lab_destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(destination, lab_destination)


def build() -> None:
    for ja in (False, True):
        write("17_question_to_chart.ipynb", lesson_51(ja), ja)
        write("18_honest_comparisons.ipynb", lesson_52(ja), ja)
        write("19_evidence_statements.ipynb", lesson_53(ja), ja)
        write("P5_clinic_wait_evidence.ipynb", project_notebook(ja), ja)
    print("built Chapter 5 notebooks in both repositories")


if __name__ == "__main__":
    build()
