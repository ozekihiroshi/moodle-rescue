#!/usr/bin/env python3
"""Verify the public, guest-ready IPA AP preview Moodle course backup."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import tarfile
import xml.etree.ElementTree as ET
from pathlib import Path


USER_SETTINGS = (
    "users",
    "role_assignments",
    "comments",
    "badges",
    "userscompletion",
    "logs",
    "grade_histories",
)


def read_xml(archive: tarfile.TarFile, name: str) -> ET.Element:
    member = archive.extractfile(name)
    if member is None:
        raise ValueError(f"missing archive member: {name}")
    return ET.fromstring(member.read())


def required_text(root: ET.Element, name: str) -> str:
    node = root.find(f".//{name}")
    if node is None or node.text is None:
        raise ValueError(f"missing metadata element: {name}")
    return node.text


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("artifact", type=Path)
    args = parser.parse_args()

    artifact = args.artifact.resolve()
    if not artifact.is_file():
        raise SystemExit(f"artifact not found: {artifact}")

    digest = hashlib.sha256(artifact.read_bytes()).hexdigest()
    with tarfile.open(artifact, "r:gz") as archive:
        names = archive.getnames()
        if "moodle_backup.xml" not in names:
            raise SystemExit("moodle_backup.xml is missing")
        if any(name == "users.xml" or name.endswith("/users.xml") for name in names):
            raise SystemExit("user data file found in preview backup")

        metadata = read_xml(archive, "moodle_backup.xml")
        shortname = required_text(metadata, "original_course_shortname")
        fullname = required_text(metadata, "original_course_fullname")
        if shortname != "IPA-AP-WRITTEN-JA-PREVIEW":
            raise SystemExit(f"unexpected shortname: {shortname}")
        if "公開体験版（問1・問2）" not in fullname:
            raise SystemExit(f"preview label is missing from fullname: {fullname}")

        settings = {}
        for setting in metadata.findall(".//setting"):
            name_node = setting.find("name")
            value_node = setting.find("value")
            if name_node is not None and value_node is not None:
                settings[name_node.text or ""] = value_node.text or ""
        for name in USER_SETTINGS:
            if name in settings and settings[name] != "0":
                raise SystemExit(f"backup setting {name} is not disabled")
        if settings.get("users") != "0":
            raise SystemExit("backup does not explicitly disable users")

        activities = {
            match.group(1)
            for name in names
            if (match := re.match(r"^activities/(lessonmark_\d+)/?$", name))
        }
        if len(activities) != 3:
            raise SystemExit(
                f"expected 3 preview LessonMark activities; found {len(activities)}"
            )

        section_count = sum(
            1 for name in names if re.match(r"^sections/section_\d+/?$", name)
        )
        if section_count != 3:
            raise SystemExit(f"expected 3 preview sections; found {section_count}")

        enrolments = read_xml(archive, "course/enrolments.xml")
        guest = [
            enrol
            for enrol in enrolments.findall(".//enrol")
            if enrol.findtext("enrol") == "guest"
        ]
        if len(guest) != 1:
            raise SystemExit("expected exactly one guest enrolment method")
        if guest[0].findtext("status") != "0":
            raise SystemExit("guest enrolment method is disabled")
        if (guest[0].findtext("password") or "") != "":
            raise SystemExit("guest enrolment method requires a password")
        if guest[0].findall(".//user_enrolment"):
            raise SystemExit("guest enrolment method contains user enrolments")

        result = {
            "status": "ok",
            "artifact": artifact.name,
            "bytes": artifact.stat().st_size,
            "sha256": digest,
            "course": {
                "fullname": fullname,
                "shortname": shortname,
                "language": "ja",
                "edition": "public-preview",
            },
            "created_with": {
                "moodle_release": required_text(metadata, "moodle_release"),
                "moodle_version": int(required_text(metadata, "moodle_version")),
                "backup_version": int(required_text(metadata, "backup_version")),
            },
            "sections": section_count,
            "lessonmark_activities": len(activities),
            "questions": 2,
            "guest_access": {
                "enabled": True,
                "password_required": False,
            },
            "includes_user_data": False,
        }
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
