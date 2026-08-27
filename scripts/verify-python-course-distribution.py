#!/usr/bin/env python3
"""Verify a public, user-free Moodle course backup."""

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
    parser.add_argument("--shortname", required=True)
    parser.add_argument("--language", choices=("en", "ja"), required=True)
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
            raise SystemExit("user data file found in distribution backup")

        metadata = read_xml(archive, "moodle_backup.xml")
        shortname = required_text(metadata, "original_course_shortname")
        if shortname != args.shortname:
            raise SystemExit(f"unexpected shortname: {shortname}")

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

        activity_types: dict[str, int] = {}
        activity_pattern = re.compile(r"^activities/([^_/]+)_\d+/?$")
        for name in names:
            match = activity_pattern.match(name)
            if match:
                activity_types[match.group(1)] = activity_types.get(match.group(1), 0) + 1

        for required in ("page", "quiz", "assign", "lti"):
            if activity_types.get(required, 0) == 0:
                raise SystemExit(f"required activity type is missing: {required}")

        result = {
            "status": "ok",
            "artifact": artifact.name,
            "bytes": artifact.stat().st_size,
            "sha256": digest,
            "course": {
                "fullname": required_text(metadata, "original_course_fullname"),
                "shortname": shortname,
                "language": args.language,
            },
            "created_with": {
                "moodle_release": required_text(metadata, "moodle_release"),
                "moodle_version": int(required_text(metadata, "moodle_version")),
                "backup_version": int(required_text(metadata, "backup_version")),
            },
            "sections": sum(1 for name in names if re.match(r"^sections/section_\d+/?$", name)),
            "activities": dict(sorted(activity_types.items())),
            "includes_user_data": False,
        }
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
