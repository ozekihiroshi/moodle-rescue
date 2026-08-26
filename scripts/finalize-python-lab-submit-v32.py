from pathlib import Path


MOODLE_ROOT = Path(__file__).resolve().parents[1]
LAB_ROOT = MOODLE_ROOT.parent / "python-lab-rescue"


def helper(course: str, japanese: bool) -> str:
    if japanese:
        messages = {
            "doc": "weekly_support.pyを確認し、合格したファイルをMoodleへ提出します。",
            "failed_check": "提出していません。すべての項目がOKになるまでweekly_support.pyを修正してください。",
            "not_lab": "提出していません。MoodleからPython Labを開いてください。",
            "failed": "提出に失敗しました",
            "unavailable": "提出サービスへ接続できません",
            "complete": "提出が完了しました",
            "file": "ファイル",
            "id": "Moodle提出ID",
        }
    else:
        messages = {
            "doc": "Check weekly_support.py and submit the passing file to Moodle.",
            "failed_check": "Not submitted: fix weekly_support.py until all checks pass.",
            "not_lab": "Not submitted: open this project from Moodle through Python Lab.",
            "failed": "Submission failed",
            "unavailable": "submission service unavailable",
            "complete": "SUBMISSION COMPLETE",
            "file": "File",
            "id": "Moodle submission ID",
        }
    return f'''"""{messages["doc"]}"""
import base64
import hashlib
import json
import os
import subprocess
import sys
import urllib.error
import urllib.request
from pathlib import Path

COURSE_SHORTNAME = "{course}"
HERE = Path(__file__).resolve().parent
ARTIFACT = HERE / "weekly_support.py"
CHECKER = HERE / "check_weekly_support.py"


def main():
    check = subprocess.run(
        [sys.executable, str(CHECKER)],
        cwd=HERE,
        text=True,
        capture_output=True,
        timeout=30,
    )
    print(check.stdout, end="")
    if check.stderr:
        print(check.stderr, file=sys.stderr, end="")
    if check.returncode or "ALL TESTS PASSED" not in check.stdout:
        print("{messages["failed_check"]}")
        return 1

    content = ARTIFACT.read_bytes()
    payload = json.dumps({{
        "course_shortname": COURSE_SHORTNAME,
        "project": "weekly-support",
        "filename": ARTIFACT.name,
        "content_base64": base64.b64encode(content).decode("ascii"),
        "sha256": hashlib.sha256(content).hexdigest(),
    }}, separators=(",", ":")).encode()
    url = os.environ.get("PYTHON_LAB_SUBMIT_URL", "").strip()
    token = os.environ.get("JUPYTERHUB_API_TOKEN", "").strip()
    if not url or not token:
        print("{messages["not_lab"]}")
        return 1

    request = urllib.request.Request(url, data=payload, method="POST", headers={{
        "Authorization": f"token {{token}}",
        "Content-Type": "application/json",
    }})
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            result = json.load(response)
    except urllib.error.HTTPError as error:
        try:
            result = json.load(error)
        except (json.JSONDecodeError, UnicodeDecodeError):
            result = {{"error": f"HTTP {{error.code}}"}}
        print(f"{messages["failed"]}: {{result.get('error', 'unknown_error')}}")
        return 1
    except (urllib.error.URLError, TimeoutError) as error:
        reason = getattr(error, "reason", str(error))
        print(f"{messages["failed"]}: {messages["unavailable"]} ({{reason}})")
        return 1

    if not isinstance(result, dict) or result.get("ok") is not True:
        error = result.get("error", "invalid_response") if isinstance(result, dict) else "invalid_response"
        print(f"{messages["failed"]}: {{error}}")
        return 1

    print("{messages["complete"]}")
    print(f"{messages["file"]}: {{result['filename']}}")
    print(f"{messages["id"]}: {{result['submission_id']}}")
    print(f"SHA-256: {{result['sha256']}}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
'''


targets = [
    (MOODLE_ROOT / "sample-content/introduction-to-python/python-lab/project-files/projects/weekly-support/submit_weekly_support.py", "PYAI-INTRO", False),
    (MOODLE_ROOT / "sample-content/introduction-to-python/python-lab/project-files/ja/projects/weekly-support/submit_weekly_support.py", "PYAI-INTRO-JA", True),
    (LAB_ROOT / "course-materials/projects/weekly-support/submit_weekly_support.py", "PYAI-INTRO", False),
    (LAB_ROOT / "course-materials/ja/projects/weekly-support/submit_weekly_support.py", "PYAI-INTRO-JA", True),
]

for path, course, japanese in targets:
    path.write_text(helper(course, japanese), encoding="utf-8", newline="\n")
    print(path)


doc = '''# Python Lab direct submission

Python Lab is the learner workspace. Moodle Assignment is the system of record for submitted work and teacher review. Teachers can review the submitted `weekly_support.py` without access to learner workspace volumes.

For Project 1.7, `submit_weekly_support.py` reruns the supplied checker and sends `weekly_support.py` only when all checks pass. The service identifies the learner with the JupyterHub API token. Moodle accepts only a signed, recent, non-replayed request for the fixed Assignment idnumber `pyai-project-1-weekly-support`, then checks enrolment and `mod/assign:submit` before using the standard Assignment API.

## Local setup

Generate one shared secret into both ignored `.env` files:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/configure-python-lab-submit-secret-v2.ps1
```

Build Moodle:

```bash
docker compose -f docker-compose.local.yml up -d --build
```

Build Python Lab with the direct-submission overlay:

```bash
docker compose -f docker-compose.local.yml -f docker-compose.lti.yml -f docker-compose.submit-v4.yml up -d --build
```

Apply the file-only Assignment setting to both language courses from WSL:

```bash
bash scripts/apply-python-lab-submit-v32.sh PYAI-INTRO PYAI-INTRO-JA
```

Never expose port 8090 publicly. It is an internal learner-to-Hub endpoint. Rotate the shared secret by rerunning the setup script and recreating both Moodle and JupyterHub containers.
'''
(MOODLE_ROOT / "docs/python-lab-direct-submission.md").write_text(doc, encoding="utf-8", newline="\n")


shell = '''#!/usr/bin/env bash
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

compose_file="${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}"
if (($#)); then
  courses=("$@")
else
  courses=(PYAI-INTRO PYAI-INTRO-JA)
fi

for shortname in "${courses[@]}"; do
  docker compose -f "$compose_file" exec -T -u www-data \\
    -e PYTHON_COURSE_SHORTNAME="$shortname" moodle php \\
    < scripts/upgrade-python-lab-submit-v32.php
done
'''
(MOODLE_ROOT / "scripts/apply-python-lab-submit-v32.sh").write_text(shell, encoding="utf-8", newline="\n")
