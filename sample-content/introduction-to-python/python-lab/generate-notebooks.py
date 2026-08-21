#!/usr/bin/env python3
"""Build learner-facing notebooks for the Python sample course.

The notebooks intentionally contain runnable examples and incomplete transfer
tasks, but not teacher model answers. Teacher answers remain hidden in Moodle.
"""

from __future__ import annotations

import argparse
import json
import shutil
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
DATASETS = ROOT / "datasets"


def markdown(text: str) -> dict:
    return {"cell_type": "markdown", "metadata": {}, "source": text.splitlines(keepends=True)}


def code(text: str) -> dict:
    return {
        "cell_type": "code",
        "execution_count": None,
        "metadata": {},
        "outputs": [],
        "source": text.splitlines(keepends=True),
    }


def notebook(title: str, section: str, cells: list[dict]) -> dict:
    opening = markdown(
        f"# {title}\n\n"
        f"**Moodle section:** {section}\n\n"
        "Use the course cycle: **Predict → Run → Check → Change → Explain**. "
        "Save before closing Python Lab; your notebook remains in your server workspace.\n"
    )
    closing = markdown(
        "## Learning record\n\n"
        "Before returning to Moodle, write down: (1) one result you predicted correctly, "
        "(2) one change you tested, and (3) one question or error you still have. "
        "Then complete the Moodle learning check.\n"
    )
    allcells = [opening, *cells, closing]
    for index, cell in enumerate(allcells, start=1):
        cell["id"] = f"cell-{index:03d}"
    return {
        "cells": allcells,
        "metadata": {
            "kernelspec": {
                "display_name": "Python 3 (ipykernel)",
                "language": "python",
                "name": "python3",
            },
            "language_info": {"name": "python", "version": "3"},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


LESSONS: list[tuple[str, str, str, list[dict]]] = [
    (
        "01_programs_values_output.ipynb",
        "Lesson 1 — Programs, values, and output",
        "1. Programs, values, and output",
        [
            markdown("## Goals\n\nRun statements, distinguish numbers from text, and label useful output."),
            markdown("## Predict\n\nBefore running, predict all three lines. Which line calculates?"),
            code('print("Registered learners:", 40)\nprint(34 + 6)\nprint("34 + 6")'),
            markdown("## Worked example\n\nNaledi begins a monthly learning-centre report. Labels preserve meaning when results are copied elsewhere."),
            code('registered = 40\nattended = 34\nprint("Registered:", registered)\nprint("Attended:", attended)'),
            markdown("## Transfer challenge\n\nA course scheduled 24 training hours and delivered 20. Display both values with clear labels, then add the difference."),
            code('# Write and run your transfer solution here.\nscheduled = 24\ndelivered = 20\n'),
            markdown("**Check:** Your output should clearly distinguish scheduled, delivered, and remaining hours. Change one value and explain every changed output."),
        ],
    ),
    (
        "02_variables_types_calculations.ipynb",
        "Lesson 2 — Variables, types, input, and calculations",
        "2. Variables, types, input, and calculations",
        [
            markdown("## Goals\n\nStore meaningful values, convert text input, calculate a rate, and retain units."),
            markdown("## Predict\n\nWhich value is the numerator? Which is the denominator? What should happen if registered is zero?"),
            code('registered = 40\nattended = 34\nattendance_rate = attended / registered * 100\nprint(f"Attendance rate: {attendance_rate:.1f}%")'),
            markdown("## Try input safely"),
            code('hours_text = "7.5"\ndays_text = "3"\nhours = float(hours_text)\ndays = int(days_text)\nprint("Hours per day:", hours / days)'),
            markdown("## Transfer challenge\n\nFor 36 registered and 29 attending, calculate the rate. Then calculate material cost per attendee from a cost of 420.50."),
            code('# Add named variables and both calculations here.\nregistered = 36\nattended = 29\nmaterial_cost = 420.50\n'),
            markdown("**Check:** Attendance rate is about 80.6%. State why the denominator is registered, not attended."),
        ],
    ),
    (
        "03_conditions_boundaries.ipynb",
        "Lesson 3 — Decisions with conditions",
        "3. Decisions with conditions",
        [
            markdown("## Goals\n\nWrite ordered conditions and test values below, at, and above every boundary."),
            code('def attendance_status(rate):\n    if rate < 75:\n        return "support"\n    elif rate < 85:\n        return "watch"\n    return "on track"\n\nfor rate in [74.9, 75, 84.9, 85]:\n    print(rate, attendance_status(rate))'),
            markdown("## Common mistake\n\nChanging `< 75` to `<= 75` changes the rule at exactly 75. Test boundaries rather than trusting a plausible-looking answer."),
            markdown("## Transfer challenge\n\nMaterials below 10 require reorder; 10–19 require monitoring; 20 or more are sufficient. Test 9, 10, 19, and 20."),
            code('def stock_status(stock):\n    # Replace this placeholder with if / elif / else.\n    return "not implemented"\n\nfor stock in [9, 10, 19, 20]:\n    print(stock, stock_status(stock))'),
            markdown("**Check:** Expected statuses are reorder, monitor, monitor, sufficient."),
        ],
    ),
    (
        "04_loops_accumulators.ipynb",
        "Lesson 4 — Repetition with loops",
        "4. Repetition with loops",
        [
            markdown("## Goals\n\nProcess repeated values, trace an accumulator, count matching records, and avoid off-by-one errors."),
            code('weekly_attendance = [28, 31, 32, 34]\ntotal = 0\nfor attendance in weekly_attendance:\n    total += attendance\n    print("Running total:", total)\nprint("Mean:", total / len(weekly_attendance))'),
            markdown("## Transfer challenge\n\nFor weekly material costs `[82.5, 74.0, 91.5, 80.0]`, calculate total, maximum, and the number of weeks above 80."),
            code('weekly_costs = [82.5, 74.0, 91.5, 80.0]\ntotal = 0\nmaximum = None\nweeks_above_80 = 0\n# Complete one loop that updates all three results.\n'),
            markdown("**Check:** Total is 328.0 and maximum is 91.5. Decide whether exactly 80 counts as “above 80”."),
        ],
    ),
    (
        "05_lists_dictionaries_records.ipynb",
        "Lesson 5 — Lists and dictionaries",
        "5. Lists and dictionaries",
        [
            markdown("## Goals\n\nRepresent labelled records, loop through them, calculate a field, and inspect unique categories."),
            code('centres = [\n    {"name": "Gaborone", "registered": 40, "attended": 34},\n    {"name": "Maun", "registered": 35, "attended": 29},\n]\nfor centre in centres:\n    rate = centre["attended"] / centre["registered"] * 100\n    print(centre["name"], round(rate, 1))'),
            markdown("## Sets as a preview of data inspection"),
            code('courses = ["Python", "Data", "Python", "Office"]\nprint(set(courses))\nprint("Unique course count:", len(set(courses)))'),
            markdown("## Transfer challenge\n\nAdd a centre record with course, scheduled hours, and delivered hours. Print its delivery percentage with a label."),
            code('# Add your third record, then calculate delivered / scheduled * 100.\n'),
            markdown("**Check:** Every number should remain attached to a meaningful key or output label."),
        ],
    ),
    (
        "06_functions_errors_testing.ipynb",
        "Lesson 6 — Functions, errors, and testing",
        "6. Functions, errors, and testing",
        [
            markdown("## Goals\n\nPackage a rule in a function, handle an invalid boundary, read an error, and test normal and edge cases."),
            code('def cost_per_completion(cost, completed):\n    if completed <= 0:\n        return None\n    return cost / completed\n\nfor completed in [30, 1, 0]:\n    print(completed, cost_per_completion(450, completed))'),
            markdown("## Debugging laboratory\n\nRun the next cell, read the final traceback line, fix only the cause, then rerun."),
            code('def attendance_rate(attended, registered):\n    return attended / registerd * 100  # Intentional spelling error.\n\n# attendance_rate(34, 40)'),
            markdown("## Transfer challenge\n\nWrite `delivery_rate(delivered, scheduled)`. Return `None` when scheduled is zero. Test 20/24, 0/24, and 10/0."),
            code('def delivery_rate(delivered, scheduled):\n    # Complete and test the function.\n    pass\n'),
            markdown("**Check:** A good test set contains a normal value, a boundary, and an invalid denominator."),
        ],
    ),
    (
        "07_tables_csv_pandas.ipynb",
        "Lesson 7 — Tables, CSV, and pandas",
        "7. Tables, CSV, and pandas",
        [
            markdown("## Goals\n\nLoad the shared CSV, inspect its schema, and calculate a derived column only after inspection."),
            code('import pandas as pd\n\ndf = pd.read_csv("data/learning-centres-practice.csv")\nprint(df.head())\nprint("Shape:", df.shape)\nprint("Columns:", df.columns.tolist())\nprint(df.dtypes)'),
            markdown("## Worked calculation"),
            code('df["completion_rate"] = df["completed"] / df["registered"] * 100\ndf[["month", "centre_name", "completion_rate"]].head()'),
            markdown("## Transfer challenge\n\nSelect centre name, registered, attended, and completed. Add attendance rate and identify the row count before interpreting it."),
            code('# Build a focused DataFrame named report.\n'),
            markdown("**Check:** The source has 24 rows and 10 columns. Do not silently treat the blank attendance value as zero."),
        ],
    ),
    (
        "08_filtering_boolean_logic.ipynb",
        "Lesson 8 — Inspecting, selecting, and Boolean logic",
        "8. Inspecting and selecting data",
        [
            markdown("## Goals\n\nSelect columns, combine Boolean conditions correctly, and compare AND with OR."),
            code('import pandas as pd\ndf = pd.read_csv("data/learning-centres-practice.csv")\ndf["attendance_rate"] = df["attended"] / df["registered"] * 100\npriority = df[(df["registered"] >= 30) & (df["attendance_rate"] < 80)]\npriority[["month", "centre_name", "attendance_rate"]]'),
            markdown("## AND versus OR"),
            code('large = df["registered"] >= 30\nlow_rate = df["attendance_rate"] < 80\nprint("AND count:", (large & low_rate).sum())\nprint("OR count:", (large | low_rate).sum())'),
            markdown("## Transfer challenge\n\nFind centre-months with at least 24 scheduled training hours but fewer than 20 delivered hours. The current file contains only `training_hours`; state the missing field instead of inventing it."),
            code('required = {"training_hours", "delivered_hours"}\nmissing = required - set(df.columns)\nprint("Missing required columns:", missing)\n'),
            markdown("**Check:** Parenthesise each pandas condition. Validate required columns before analysis."),
        ],
    ),
    (
        "09_cleaning_audit_trail.ipynb",
        "Lesson 9 — Cleaning data with an audit trail",
        "9. Cleaning data",
        [
            markdown("## Goals\n\nExpose invalid values, normalise categories, count quality problems, and keep a cleaning log."),
            code('import pandas as pd\ndf = pd.read_csv("data/learning-centres-practice.csv")\nprint("Missing before cleaning:")\nprint(df.isna().sum())\nprint("District labels:", sorted(df["district"].unique()))'),
            code('clean = df.copy()\nclean["district"] = clean["district"].str.strip().str.title()\nfor column in ["registered", "attended", "completed"]:\n    clean[column] = pd.to_numeric(clean[column], errors="coerce")\ninvalid_completion = clean[clean["completed"] > clean["attended"]]\nprint("Missing after conversion:", clean.isna().sum().to_dict())\nprint("Impossible completion rows:", len(invalid_completion))'),
            markdown("## Transfer challenge\n\nFlag negative material cost and duplicate centre-month-course keys. Report counts before deciding what to exclude."),
            code('negative_cost = clean["material_cost"] < 0\nduplicate_key = clean.duplicated(\n    subset=["centre_id", "month", "course"], keep=False\n)\nprint("Negative cost:", negative_cost.sum())\nprint("Duplicate key rows:", duplicate_key.sum())'),
            markdown("**Check:** Cleaning is a documented decision, not a command to delete inconvenient rows."),
        ],
    ),
    (
        "10_grouping_statistics.ipynb",
        "Lesson 10 — Grouping and summary statistics",
        "10. Grouping and summary statistics",
        [
            markdown("## Goals\n\nAggregate compatible totals, compare mean with median, and report group size beside summaries."),
            code('import pandas as pd\ndf = pd.read_csv("data/learning-centres-practice.csv")\nsummary = df.groupby("course").agg(\n    rows=("centre_id", "size"),\n    registered=("registered", "sum"),\n    completed=("completed", "sum"),\n    material_cost=("material_cost", "sum"),\n)\nsummary["completion_rate"] = summary["completed"] / summary["registered"] * 100\nsummary["cost_per_completion"] = summary["material_cost"] / summary["completed"]\nsummary'),
            markdown("## Mean and median under an extreme value"),
            code('values = pd.Series([20, 21, 22, 100])\nprint("Mean:", values.mean())\nprint("Median:", values.median())'),
            markdown("## Transfer challenge\n\nCompare attendance rate by district. Aggregate attended and registered totals first; also report record count."),
            code('# Build district_summary here.\n'),
            markdown("**Check:** Do not average group percentages when group sizes differ; combine numerators and denominators."),
        ],
    ),
    (
        "11_visualisation_evidence.ipynb",
        "Lesson 11 — Visualisation and evidence",
        "11. Visualisation and evidence",
        [
            markdown("## Goals\n\nChoose a chart that fits the question, label it, and distinguish observation from causal claim."),
            code('import pandas as pd\nimport matplotlib.pyplot as plt\n\ndf = pd.read_csv("data/learning-centres-practice.csv")\nmonthly = df.groupby("month").agg(\n    attended=("attended", "sum"), registered=("registered", "sum")\n)\nmonthly["attendance_rate"] = monthly["attended"] / monthly["registered"] * 100\nax = monthly["attendance_rate"].plot(kind="line", marker="o")\nax.set(title="Monthly attendance rate", xlabel="Month", ylabel="Attendance rate (%)")\nplt.ylim(0, 100)\nplt.tight_layout()'),
            markdown("## Evidence statement\n\nWrite one observation supported by numbers, one limitation, and one next question. A time pattern alone does not prove a cause."),
            markdown("## Transfer challenge\n\nCreate a bar chart of completion rate by course. Aggregate totals before calculating rates."),
            code('# Create course_summary and a labelled bar chart here.\n'),
            markdown("**Check:** The title names the measure, both axes are labelled, and the written claim does not exceed the evidence."),
        ],
    ),
    (
        "12_scaling_chunks_validation.ipynb",
        "Lesson 12 — Scaling up safely",
        "12. Scaling up: larger CSV datasets",
        [
            markdown("## Goals\n\nGenerate deterministic data, select schema, process chunks, merge group totals, and reconcile row counts."),
            code('from pathlib import Path\nimport subprocess\nimport sys\n\nlarge_file = Path("data/learning-centres-10000.csv")\nif not large_file.exists():\n    subprocess.run([\n        sys.executable, "data/generate-learning-centre-data.py",\n        "--rows", "10000", "--output", str(large_file)\n    ], check=True)\nlarge_file'),
            code('import pandas as pd\n\ntotals = {}\nrow_count = 0\nfor chunk in pd.read_csv(\n    large_file,\n    usecols=["district", "material_cost"],\n    dtype={"district": "string", "material_cost": "float64"},\n    chunksize=2_000,\n):\n    row_count += len(chunk)\n    part = chunk.groupby("district")["material_cost"].sum()\n    for district, amount in part.items():\n        totals[district] = totals.get(district, 0) + amount\n\nprint("Rows processed:", row_count)\nprint(totals)'),
            markdown("## Transfer challenge\n\nProcess registered and attended totals by district in chunks. Reconcile the processed row count, then calculate district attendance rates."),
            code('# Test with 10,000 rows before increasing the generated size.\n'),
            markdown("**Check:** Never average unweighted chunk means. Merge sums and counts, then calculate the final statistic."),
        ],
    ),
]


PROJECTS: list[tuple[str, str, str, list[dict]]] = [
    (
        "P1_weekly_support_report.ipynb",
        "Milestone project — Weekly learning-centre support report",
        "After Lesson 4",
        [
            markdown("## Brief\n\nProcess four weekly attendance counts. Calculate total and mean attendance, then classify support (<75%), watch (75–84.9%), or on track (≥85%). Test below, at, and above both boundaries."),
            code('weekly_attended = [28, 31, 30, 33]\nweekly_registered = [40, 40, 40, 40]\n\n# 1. Accumulate totals.\n# 2. Calculate one combined attendance rate.\n# 3. Classify it.\n# 4. Print a useful recommendation.\n'),
            markdown("## Required evidence\n\nRunnable code, boundary tests, an operational recommendation, and the AI-use declaration below."),
            markdown("## AI-use declaration\n\n1. Did you use AI? 2. What help did you request? 3. What did you test/change/verify? 4. What did you learn?"),
        ],
    ),
    (
        "P2_monthly_centre_report.ipynb",
        "Foundation project — Monthly learning-centre performance report",
        "Foundation project",
        [
            markdown("## Brief\n\nRepresent at least three learning centres, calculate attendance percentage, completion percentage, and cost per completion with reusable functions, then flag centres needing support."),
            code('centres = [\n    {"name": "Gaborone", "registered": 40, "attended": 34, "completed": 30, "material_cost": 450},\n    {"name": "Maun", "registered": 35, "attended": 29, "completed": 25, "material_cost": 390},\n]\n\ndef safe_rate(part, whole):\n    # Return None for an invalid denominator; otherwise return a percentage.\n    pass\n\n# Add cost_per_completion, validation, a loop, and a support flag.'),
            markdown("## Tests\n\nInclude normal data, zero denominators, an impossible count such as completed greater than attended, and values exactly at each support boundary."),
            markdown("## Submit\n\nThe saved notebook, test evidence, a short explanation of one design decision, and your AI-use declaration."),
        ],
    ),
    (
        "P3_learning_centres_analysis.ipynb",
        "Data-analysis project — Learning centres",
        "Data analysis project",
        [
            markdown("## Question\n\nChoose one: Which centres need attendance support? How do completion rates differ by course? Where are data-quality problems concentrated?"),
            code('import pandas as pd\nimport matplotlib.pyplot as plt\n\nsource = "data/learning-centres-practice.csv"\ndf = pd.read_csv(source)\nprint(df.shape)\nprint(df.columns.tolist())'),
            markdown("## Workflow\n\n1. Define the question and measures. 2. Inspect. 3. Clean with counts. 4. Aggregate. 5. Validate. 6. Chart. 7. Explain finding, evidence, and limitation."),
            code('# Keep each stage in a separate cell and retain your cleaning log.\n'),
            markdown("## Submit\n\nNotebook, cleaning log, one labelled chart, 150–250 words, and AI-use declaration."),
        ],
    ),
    (
        "P4_final_question_to_evidence.ipynb",
        "Final project — From question to evidence",
        "Final project and reflection",
        [
            markdown("## Planning record\n\nWrite your question, data source and licence, data dictionary, expected measures, privacy decision, and validation plan before writing analysis code."),
            code('# Imports and source loading\nimport pandas as pd\nimport matplotlib.pyplot as plt\n'),
            markdown("## Analysis sections\n\nAdd separate cells for inspection, cleaning log, calculation, validation, table, chart, and interpretation."),
            code('# Analysis scaffold: replace this comment one tested stage at a time.\n'),
            markdown("## Final report\n\n250–400 words containing one finding, numerical evidence, one limitation, a next question, and the AI-use declaration."),
        ],
    ),
    (
        "P5_scaleup_capstone.ipynb",
        "Scale-up capstone — Operations evidence",
        "Scale-up capstone project",
        [
            markdown("## Brief\n\nUse a teacher-approved public, organisational, or generated dataset with at least 10,000 rows. The goal is a reproducible workflow, not a size contest."),
            code('from pathlib import Path\nimport pandas as pd\n\nsource = Path("data/learning-centres-10000.csv")\n# Record file name, size, expected row count, required columns, and dtypes.\n'),
            markdown("## Required controls\n\nValidate on a small fixture; select needed columns and types; reconcile processed, rejected, and missing rows; aggregate to decision size; retain provenance and privacy decisions."),
            code('# Implement chunk processing and reconciliation here.\n'),
            markdown("## Submit\n\nRunnable notebook, source/licence record, reconciliation evidence, decision-sized table, one chart, recommendation, limitation, and AI-use declaration."),
        ],
    ),
]


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    output = args.output.resolve()
    output.mkdir(parents=True, exist_ok=True)
    (output / "P2_foundation_score_report.ipynb").unlink(missing_ok=True)
    data = output / "data"
    data.mkdir(exist_ok=True)

    for filename, title, section, cells in [*LESSONS, *PROJECTS]:
        path = output / filename
        path.write_text(
            json.dumps(notebook(title, section, cells), ensure_ascii=False, indent=2) + "\n",
            encoding="utf-8",
        )

    shutil.copy2(DATASETS / "learning-centres-practice.csv", data / "learning-centres-practice.csv")
    shutil.copy2(DATASETS / "generate-learning-centre-data.py", data / "generate-learning-centre-data.py")
    for path in output.glob("*.ipynb"):
        document = json.loads(path.read_text(encoding="utf-8"))
        for index, cell in enumerate(document["cells"], start=1):
            cell.setdefault("id", f"cell-{index:03d}")
        path.write_text(json.dumps(document, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

    print(json.dumps({"notebooks": len(LESSONS) + len(PROJECTS), "output": str(output)}))


if __name__ == "__main__":
    main()
