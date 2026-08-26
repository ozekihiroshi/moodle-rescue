#!/usr/bin/env python3
"""Add a direct Moodle submission step to Project 1.7 notebooks."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TARGETS = [
    (ROOT / "sample-content/introduction-to-python/python-lab/templates/P1_weekly_support_report.ipynb", False),
    (ROOT / "sample-content/introduction-to-python/python-lab/templates/ja/P1_weekly_support_report.ipynb", True),
    (Path("D:/workspace/python-lab-rescue/course-materials/P1_weekly_support_report.ipynb"), False),
    (Path("D:/workspace/python-lab-rescue/course-materials/ja/P1_weekly_support_report.ipynb"), True),
]

def cell(cell_type, cell_id, source):
    value = {"cell_type": cell_type, "id": cell_id, "metadata": {}, "source": source.splitlines(True)}
    if cell_type == "code":
        value.update({"execution_count": None, "outputs": []})
    return value

for path, ja in TARGETS:
    data = json.loads(path.read_text(encoding="utf-8"))
    ids = {item.get("id") for item in data["cells"]}
    if "p1-submit-moodle" in ids:
        continue
    if ja:
        heading = "## Moodleへ提出する\n\n`weekly_support.py`を保存し、自動確認がすべて通った後に次のセルを実行します。確認プログラムがもう一度動き、合格したファイルだけがMoodle課題へ送られます。`提出が完了しました`とMoodle提出IDが表示されたら完了です。再実行すると現在のファイルで再提出します。\n"
        command = "!python /home/jovyan/work/ja/projects/weekly-support/submit_weekly_support.py\n"
    else:
        heading = "## Submit to Moodle\n\nSave `weekly_support.py` and run this cell after every automatic check passes. The checker runs once more and only a passing file is sent to the Moodle Assignment. `SUBMISSION COMPLETE` and a Moodle submission ID confirm success. Run it again to resubmit the current file.\n"
        command = "!python /home/jovyan/work/projects/weekly-support/submit_weekly_support.py\n"
    data["cells"].extend([
        cell("markdown", "p1-submit-moodle", heading),
        cell("code", "p1-submit-moodle-command", command),
    ])
    path.write_text(json.dumps(data, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
    print(path)
