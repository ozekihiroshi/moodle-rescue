#!/usr/bin/env python3
"""Align Project 1.7 learner instructions, paths, and verification wording."""
from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def replace(path: Path, old: str, new: str) -> None:
    text = path.read_text(encoding="utf-8")
    if old not in text:
        raise RuntimeError(f"Expected text not found in {path}: {old[:100]!r}")
    path.write_text(text.replace(old, new), encoding="utf-8")


page = ROOT / "scripts/upgrade-python-project1-page-v30.php"
replace(
    page,
    '<p><strong>日報を見る → プログラムを起動する → 数字を順番に入力する → 週間報告を確認する</strong>、という作業を自動化します。</p>',
    '<p>担当者は日報の数字を順番に入力します。プログラムは、その後の集計、解決率の計算、対応状況の判定、最繁忙日の特定を自動化します。</p>',
)
replace(
    page,
    '<p>5日分を一つの<code>for</code>ループで入力し、次の項目を計算して表示します。</p>\n<ul><li>問い合わせ合計</li><li>解決合計</li><li>未解決件数</li><li>解決率</li><li>対応状況</li><li>最も問い合わせが多かった曜日</li></ul>',
    '<p>5日分の入力を受け取り、次の項目を計算して表示します。</p>\n<ul><li>問い合わせ合計</li><li>解決合計</li><li>未解決件数</li><li>解決率</li><li>対応状況</li><li>最も問い合わせが多かった曜日</li></ul>\n<p><strong>実装上の約束：</strong>この課題では繰り返し処理を練習するため、月曜日から金曜日までの入力を一つの<code>for</code>ループで処理します。同じ入力コードを5回繰り返して書かないでください。</p>',
)
replace(
    page,
    '<p><code>weekly_support.py</code>を実行し、サンプル日報の10個の値を入力します。「期待される出力」と一致することを確認します。</p>\n<h4>2. 確認プログラムを実行する</h4>\n<pre><code>python check_weekly_support.py</code></pre>',
    '<p><code>weekly_support.py</code>を実行し、サンプル日報の10個の値を入力します。「期待される出力」と一致することを確認します。</p>\n<p>Python Labのターミナルから実行する場合：</p>\n<pre><code>python projects/weekly-support/weekly_support.py</code></pre>\n<h4>2. 確認プログラムを実行する</h4>\n<pre><code>python projects/weekly-support/check_weekly_support.py</code></pre>',
)
replace(
    page,
    '<h3>Program requirements</h3><p>Use one <code>for</code> loop for five days. Display totals received and resolved, unresolved count, resolution rate to one decimal place, status, and busiest day. At least 90% is <code>ON TRACK</code>; at least 80% is <code>REVIEW</code>; below 80% is <code>PRIORITY SUPPORT</code>. On a busiest-day tie, keep the first day.</p>',
    '<h3>Program requirements</h3><p>The staff member enters the figures from the daily records. The program automates the totals, resolution-rate calculation, status decision, and identification of the busiest day. Display totals received and resolved, unresolved count, resolution rate to one decimal place, status, and busiest day. At least 90% is <code>ON TRACK</code>; at least 80% is <code>REVIEW</code>; below 80% is <code>PRIORITY SUPPORT</code>. On a busiest-day tie, keep the first day.</p><p><strong>Implementation contract:</strong> This project practises repetition, so process the Monday-to-Friday inputs with one <code>for</code> loop. Do not write the same input code five times.</p>',
)
replace(
    page,
    '<h3>Checking</h3><h4>1. Check it yourself</h4><p>Run the script, enter the ten sample values, and compare with the expected output.</p><h4>2. Run the supplied checker</h4><pre><code>python check_weekly_support.py</code></pre>',
    '<h3>Checking</h3><h4>1. Check it yourself</h4><p>Run the script, enter the ten sample values, and compare with the expected output.</p><p>From the Python Lab terminal:</p><pre><code>python projects/weekly-support/weekly_support.py</code></pre><h4>2. Run the supplied checker</h4><pre><code>python projects/weekly-support/check_weekly_support.py</code></pre>',
)

submission = ROOT / "scripts/upgrade-python-project1-submission-v33.php"
replace(submission, "python check_weekly_support.py", "python projects/weekly-support/check_weekly_support.py")

readmes = {
    ROOT / "sample-content/introduction-to-python/python-lab/project-files/projects/weekly-support/README.md": """# Weekly support mini-project

Edit `projects/weekly-support/weekly_support.py`; do not edit the checker. From the course-materials directory, run the program yourself first:

```text
python projects/weekly-support/weekly_support.py
```

Then run:

```text
python projects/weekly-support/check_weekly_support.py
```

Submit only `weekly_support.py`.
""",
    ROOT / "sample-content/introduction-to-python/python-lab/project-files/ja/projects/weekly-support/README.md": """# 週間サポート報告・小プロジェクト

`projects/weekly-support/weekly_support.py`を編集し、確認プログラムは変更しません。教材のルートから、最初に自分でプログラムを実行します。

```text
python projects/weekly-support/weekly_support.py
```

次に確認プログラムを実行します。

```text
python projects/weekly-support/check_weekly_support.py
```

提出するのは`weekly_support.py`だけです。
""",
}
for path, content in readmes.items():
    path.write_text(content, encoding="utf-8")

notebooks = {
    ROOT / "sample-content/introduction-to-python/python-lab/templates/P1_weekly_support_report.ipynb": {
        "output": "Display each label, colon, space, and value exactly as in this example, one item per line. For invalid data, output only `RESULT: INVALID`.",
        "rules": "1. This project practises repetition: process the Monday-to-Friday inputs with one `for` loop rather than writing the same input code five times.\n",
    },
    ROOT / "sample-content/introduction-to-python/python-lab/templates/ja/P1_weekly_support_report.ipynb": {
        "output": "各項目名、コロン、空白、値をこの出力例と同じ形式で、一行に一項目ずつ表示します。不正データの場合は`RESULT: INVALID`だけを表示します。",
        "rules": "1. この課題では繰り返し処理を練習するため、月曜日から金曜日までの入力を一つの`for`ループで処理します。同じ入力コードを5回繰り返して書かないでください。\n",
    },
}
for path, replacements in notebooks.items():
    document = json.loads(path.read_text(encoding="utf-8"))
    cells = {cell.get("id"): cell for cell in document["cells"]}
    output_cell = cells["p1-output"]
    output_cell["source"][-1] = replacements["output"]
    rules_cell = cells["p1-rules"]
    rules_cell["source"][2] = replacements["rules"]
    document["metadata"]["pyai"]["revision"] = 34
    path.write_text(json.dumps(document, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")

print("Project 1.7 audit alignment written")
