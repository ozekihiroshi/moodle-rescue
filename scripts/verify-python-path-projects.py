"""Run published checkers against reference solutions in disposable copies."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

base = Path(__file__).resolve().parents[1] / 'sample-content/introduction-to-python'
projects = [('school-meal-review', 'project-3a'), ('bus-service-review', 'project-3b'),
            ('water-point-review', 'project-3c'), ('equipment-lending', 'project-4-5'),
            ('clinic-wait-evidence', 'project-5'), ('clinic-stock-scaleup', 'project-6')]
results = []
for name, reference in projects:
    with tempfile.TemporaryDirectory(prefix='python-path-') as folder:
        work = Path(folder) / name
        shutil.copytree(base / 'python-lab/project-files/projects' / name, work)
        for source in (base / 'reference-solutions' / reference).glob('*.py'):
            shutil.copy2(source, work / source.name)
        hashes = {p.relative_to(work): hashlib.sha256(p.read_bytes()).hexdigest()
                  for p in (work / 'data').glob('*.csv')}
        for checker in sorted(work.glob('check_*.py')):
            result = subprocess.run([sys.executable, str(checker)], cwd=work,
                env=dict(os.environ, MPLBACKEND='Agg'), capture_output=True, text=True, timeout=180)
            if result.returncode:
                raise RuntimeError(name + '/' + checker.name + '\n' + result.stdout + result.stderr)
            results.append({'project': name, 'checker': checker.name, 'result': 'PASS'})
        for path, digest in hashes.items():
            assert hashlib.sha256((work / path).read_bytes()).hexdigest() == digest, path
print(json.dumps(results, ensure_ascii=False, indent=2))
