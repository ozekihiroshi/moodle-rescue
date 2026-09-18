"""Check every visible LTI Notebook against source files and the test Lab volume."""
import json
from pathlib import Path
import subprocess
import sys
from urllib.parse import unquote, urlsplit

root = Path(__file__).resolve().parents[1]
english = '--english' in sys.argv or '--english-guided' in sys.argv
language_mode = '--english-guided' if '--english-guided' in sys.argv else '--english'
rows = json.loads(subprocess.check_output(['docker', 'exec', '--user', 'www-data',
    'moodle-rescue-local', 'php', '/tmp/audit-python-path-activities.php'] + ([language_mode] if english else []), text=True))
paths = []
for row in rows:
    if row['module'] != 'lti':
        continue
    path = unquote(urlsplit(row['toolurl']).path).split('/lab/tree/', 1)[1]
    assert path.startswith('ja/') != english and '..' not in Path(path).parts, path
    source = root.parent / 'python-lab-rescue/course-materials' / path
    book = json.loads(source.read_text(encoding='utf-8'))
    assert book['nbformat'] == 4 and book['cells'], source
    assert 'submit_weekly_support.py' not in json.dumps(book), path
    paths.append('/home/jovyan/work/' + path)
code = 'import pathlib,sys; missing=[p for p in sys.argv[1:] if not pathlib.Path(p).is_file()]; print(missing); sys.exit(bool(missing))'
subprocess.run(['docker', 'exec', 'python-lab-5', 'python', '-c', code, *paths], check=True)
print(json.dumps({'notebook_links': len(paths), 'source_and_learner_files': 'PASS',
    'no_legacy_direct_submission': True}))
