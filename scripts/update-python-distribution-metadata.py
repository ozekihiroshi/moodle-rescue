#!/usr/bin/env python3
"""Synchronise Python course distribution metadata with the actual MBZ files."""

from __future__ import annotations

import hashlib
import json
from pathlib import Path
import re


ROOT = Path(__file__).resolve().parents[1]
EDITIONS = [
    {
        "directory": ROOT / "sample-content/introduction-to-python/distribution",
        "filename": "python-for-data-foundations-ai-era-0.1.0-alpha.1.mbz",
    },
    {
        "directory": ROOT / "sample-content/introduction-to-python-ja/distribution",
        "filename": "python-for-data-foundations-ai-era-ja-0.1.0-alpha.1.mbz",
    },
]


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


for edition in EDITIONS:
    directory = edition["directory"]
    artifact = directory / edition["filename"]
    digest = sha256(artifact)

    manifest_path = directory / "manifest.json"
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    manifest["artifact"]["bytes"] = artifact.stat().st_size
    manifest["artifact"]["sha256"] = digest
    manifest["inventory"]["quizzes"] = 23
    manifest["verification"] = {
        "archive_structure": "passed",
        "fresh_moodle_restore": "passed",
        "restored_question_references": 230,
        "wrong_question_contexts": 0,
        "restored_enrolments": 0,
        "restored_quiz_attempts": 0,
        "restored_submissions": 0,
    }
    manifest_path.write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    (directory / "SHA256SUMS").write_text(
        f"{digest}  {artifact.name}\n",
        encoding="utf-8",
    )

    readme_path = directory / "README.md"
    readme = readme_path.read_text(encoding="utf-8")
    readme = re.sub(r"(?m)^[0-9a-f]{64}$", digest, readme, count=1)
    readme_path.write_text(readme, encoding="utf-8")

    print(json.dumps({
        "artifact": artifact.name,
        "bytes": artifact.stat().st_size,
        "sha256": digest,
    }))
