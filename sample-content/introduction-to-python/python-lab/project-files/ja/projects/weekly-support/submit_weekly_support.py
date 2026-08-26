"""weekly_support.pyを確認し、合格したファイルをMoodleへ提出します。"""
import base64
import hashlib
import json
import os
import subprocess
import sys
import urllib.error
import urllib.request
from pathlib import Path

COURSE_SHORTNAME = "PYAI-INTRO-JA"
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
        print("提出していません。すべての項目がOKになるまでweekly_support.pyを修正してください。")
        return 1

    content = ARTIFACT.read_bytes()
    payload = json.dumps({
        "course_shortname": COURSE_SHORTNAME,
        "project": "weekly-support",
        "filename": ARTIFACT.name,
        "content_base64": base64.b64encode(content).decode("ascii"),
        "sha256": hashlib.sha256(content).hexdigest(),
    }, separators=(",", ":")).encode()
    url = os.environ.get("PYTHON_LAB_SUBMIT_URL", "").strip()
    token = os.environ.get("JUPYTERHUB_API_TOKEN", "").strip()
    if not url or not token:
        print("提出していません。MoodleからPython Labを開いてください。")
        return 1

    request = urllib.request.Request(url, data=payload, method="POST", headers={
        "Authorization": f"token {token}",
        "Content-Type": "application/json",
    })
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            result = json.load(response)
    except urllib.error.HTTPError as error:
        try:
            result = json.load(error)
        except (json.JSONDecodeError, UnicodeDecodeError):
            result = {"error": f"HTTP {error.code}"}
        print(f"提出に失敗しました: {result.get('error', 'unknown_error')}")
        return 1
    except (urllib.error.URLError, TimeoutError) as error:
        reason = getattr(error, "reason", str(error))
        print(f"提出に失敗しました: 提出サービスへ接続できません ({reason})")
        return 1

    if not isinstance(result, dict) or result.get("ok") is not True:
        error = result.get("error", "invalid_response") if isinstance(result, dict) else "invalid_response"
        print(f"提出に失敗しました: {error}")
        return 1

    print("提出が完了しました")
    print(f"ファイル: {result['filename']}")
    print(f"Moodle提出ID: {result['submission_id']}")
    print(f"SHA-256: {result['sha256']}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
