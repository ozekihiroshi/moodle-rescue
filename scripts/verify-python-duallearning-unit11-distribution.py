#!/usr/bin/env python3
"""Audit the single-unit Dual Learning backup before public distribution."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import tarfile
import xml.etree.ElementTree as ET
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "sample-content/introduction-to-python-ja/duallearning-1-1"
USER_SETTINGS = (
    "users",
    "role_assignments",
    "comments",
    "badges",
    "userscompletion",
    "logs",
    "grade_histories",
)


def xml(archive: tarfile.TarFile, name: str) -> ET.Element:
    member = archive.extractfile(name)
    if member is None:
        raise ValueError(f"Missing member: {name}")
    return ET.fromstring(member.read())


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("artifact", type=Path)
    args = parser.parse_args()
    artifact = args.artifact.resolve()
    if not artifact.is_file():
        raise SystemExit("Backup not found")

    with tarfile.open(artifact, "r:gz") as archive:
        names = archive.getnames()
        if any(name == "users.xml" or name.endswith("/users.xml") for name in names):
            raise SystemExit("User data found in backup")

        metadata = xml(archive, "moodle_backup.xml")
        if metadata.findtext(".//original_course_shortname") != "DUAL-PY-11-PATH-JA":
            raise SystemExit("Wrong course shortname")
        settings = {
            setting.findtext("name"): setting.findtext("value")
            for setting in metadata.findall(".//setting")
        }
        for name in USER_SETTINGS:
            if name in settings and settings[name] != "0":
                raise SystemExit(f"User-dependent setting enabled: {name}")
        if settings.get("users") != "0":
            raise SystemExit("User exclusion is not explicit")

        course = xml(archive, "course/course.xml")
        if course.findtext("format") != "duallearning":
            raise SystemExit("Wrong course format")
        options = {
            option.findtext("name"): option.findtext("value")
            for option in course.findall(".//courseformatoption")
        }
        if options.get("learningmode") != "path":
            raise SystemExit("Self-paced mode was not preserved")

        activity_dirs = [
            name for name in names if re.fullmatch(r"activities/[^/]+_\d+/?", name)
        ]
        lesson_dirs = sorted(name.rstrip("/") + "/" for name in activity_dirs
                             if name.startswith("activities/lessonmark_"))
        lti_dirs = [name.rstrip("/") + "/" for name in activity_dirs
                    if name.startswith("activities/lti_")]
        if len(lesson_dirs) != 3 or lti_dirs or len(activity_dirs) != 3:
            raise SystemExit("Expected exactly three Markdown lessons and no site-specific LTI activity")

        markdown_names = set()
        for directory in lesson_dirs:
            lesson = xml(archive, directory + "lessonmark.xml")
            name = lesson.findtext(".//name") or ""
            filename = {
                "01": "01-prepare.md",
                "02": "02-lesson.md",
                "03": "03-review.md",
            }.get(name[:2])
            if not filename or filename in markdown_names:
                raise SystemExit(f"Unexpected LessonMark activity: {name}")
            markdown_names.add(filename)
            actual = lesson.findtext(".//markdownsource")
            expected = (SOURCE / filename).read_text(encoding="utf-8")
            if actual != expected:
                raise SystemExit(f"Markdown differs from source: {filename}")

        for name in names:
            if not name.endswith(".xml"):
                continue
            root = xml(archive, name)
            for node in root.iter():
                tag = node.tag.lower().rsplit("}", 1)[-1]
                # Moodle serialises a database NULL with this literal sentinel.
                # It is not a credential; any other non-empty value is rejected.
                value = (node.text or "").strip()
                if tag in ("password", "resourcekey", "secret", "token") and value not in ("", "$@NULL@$"):
                    raise SystemExit(f"Non-empty secret-like field in {name}: {tag}")

    print(json.dumps({
        "status": "verified",
        "artifact": artifact.name,
        "bytes": artifact.stat().st_size,
        "sha256": hashlib.sha256(artifact.read_bytes()).hexdigest(),
        "course": "DUAL-PY-11-PATH-JA",
        "mode": "self-paced",
        "lessonmark_activities": 3,
        "lti_activities": 0,
        "lab_registration_required_after_restore": True,
        "includes_user_data": False,
    }, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
