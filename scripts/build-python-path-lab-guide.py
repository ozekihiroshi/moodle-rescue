#!/usr/bin/env python3
"""Generate a separate guide; never overwrite the original or learner notebook."""
import json
from pathlib import Path

root = Path(__file__).resolve().parents[1]
source = root.parent / 'python-lab-rescue/course-materials/ja/P1_weekly_support_report.ipynb'
target = root / 'sample-content/introduction-to-python-ja/duallearning-path/P1_weekly_support_report_dual_path.ipynb'
book = json.loads(source.read_text(encoding='utf-8'))
book['cells'] = [cell for cell in book['cells'] if cell.get('id') != 'p1-submit-moodle-command']
for cell in book['cells']:
    if cell.get('id') == 'p1-submit-moodle':
        cell['source'] = [
            '## 対応版コースへ提出する\n', '\n',
            'Ctrl+Sで保存し、手動確認と自動確認を終えたら、左のファイル一覧で'
            '`projects/weekly-support/weekly_support.py`を右クリックしてダウンロードします。\n',
            'Moodleへ戻り、**今学習しているMarkdown対応版コースの1.7提出課題**を開きます。'
            '「提出をアップロード・入力する」から保存したファイルをアップロードし、提出を確定してください。'
            '提出ステータスと添付ファイル名を確認して終了します。\n',
            '従来版のコースへ送らないよう、直接提出スクリプトは使用しません。'
            'Notebookと確認プログラムは提出不要です。\n',
        ]
    if cell['cell_type'] == 'code':
        cell['outputs'] = []
        cell['execution_count'] = None
book['metadata']['pyai']['variant'] = 'duallearning-self-paced'
target.write_text(json.dumps(book, ensure_ascii=False, indent=1) + '\n', encoding='utf-8')
print(target)
labtarget = root.parent / 'python-lab-rescue/course-materials/ja' / target.name
labtarget.write_bytes(target.read_bytes())
print(labtarget)
