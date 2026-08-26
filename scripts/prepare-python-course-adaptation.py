#!/usr/bin/env python3
"""Create a stable, reviewable translation-segment file from a course catalogue."""

from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path
from typing import Any


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("canonical", type=Path)
    parser.add_argument("output", type=Path)
    parser.add_argument("--target-language", default="ja")
    parser.add_argument("--adaptation-course", default="PYAI-INTRO-JA")
    parser.add_argument("--adaptation-version", default="0.1.0-draft")
    return parser.parse_args()


def pointer(*parts: str) -> str:
    def escape(value: str) -> str:
        return value.replace("~", "~0").replace("/", "~1")

    return "/" + "/".join(escape(part) for part in parts)


def main() -> None:
    args = parse_args()
    raw = args.canonical.read_bytes()
    canonical: dict[str, Any] = json.loads(raw)
    segments: list[dict[str, Any]] = []

    def add(path: tuple[str, ...], value: Any, kind: str, context: str) -> None:
        if not isinstance(value, str) or value == "":
            return
        segments.append(
            {
                "id": pointer(*path),
                "kind": kind,
                "context": context,
                "source": value,
                "target": "",
                "status": "pending",
            }
        )

    add(("course", "fullname"), canonical["course"]["fullname"], "plain", "course fullname")
    add(("course", "summary", "text"), canonical["course"]["summary"]["text"], "html", "course summary")

    for key, section in canonical["sections"].items():
        add(("sections", key, "name"), section["name"], "plain", f"section {key} name")
        add(("sections", key, "summary", "text"), section["summary"]["text"], "html", f"section {key} summary")

    for key, activity in canonical["activities"].items():
        label = f"{activity['selector']['modname']} activity {activity['selector']['name']}"
        add(("activities", key, "name"), activity["name"], "plain", label + " name")
        add(("activities", key, "intro", "text"), activity["intro"]["text"], "html", label + " intro")
        if "content" in activity:
            add(("activities", key, "content", "text"), activity["content"]["text"], "html", label + " content")
        for index, band in enumerate(activity.get("feedback_bands", [])):
            add(
                ("activities", key, "feedback_bands", str(index), "text", "text"),
                band["text"]["text"],
                "html",
                label + f" feedback band {index + 1}",
            )
        for questionkey, question in activity.get("questions", {}).items():
            qlabel = label + f" {questionkey}"
            add(("activities", key, "questions", questionkey, "name"), question["name"], "plain", qlabel + " bank name")
            add(
                ("activities", key, "questions", questionkey, "questiontext", "text"),
                question["questiontext"]["text"],
                "html",
                qlabel + " prompt",
            )
            add(
                ("activities", key, "questions", questionkey, "generalfeedback", "text"),
                question["generalfeedback"]["text"],
                "html",
                qlabel + " explanation",
            )
            for answerindex, answer in enumerate(question["answers"]):
                add(
                    ("activities", key, "questions", questionkey, "answers", str(answerindex), "text", "text"),
                    answer["text"]["text"],
                    "html" if answer["text"]["format"] else "plain",
                    qlabel + f" answer {answerindex + 1}",
                )
                add(
                    ("activities", key, "questions", questionkey, "answers", str(answerindex), "feedback", "text"),
                    answer["feedback"]["text"],
                    "html",
                    qlabel + f" answer {answerindex + 1} feedback",
                )

    for key, announcement in canonical["announcements"].items():
        add(("announcements", key, "subject"), announcement["subject"], "plain", key + " subject")
        add(("announcements", key, "message", "text"), announcement["message"]["text"], "html", key + " message")

    ids = [segment["id"] for segment in segments]
    if len(ids) != len(set(ids)):
        raise RuntimeError("Duplicate adaptation segment IDs")

    result = {
        "schema_version": 1,
        "catalogue_sha256": hashlib.sha256(raw).hexdigest(),
        "canonical_course": canonical["canonical"]["shortname"],
        "canonical_version": canonical["canonical"]["version"],
        "source_language": canonical["canonical"]["language"],
        "adaptation_course": args.adaptation_course,
        "adaptation_version": args.adaptation_version,
        "target_language": args.target_language,
        "relationship": "official_adaptation",
        "segments": segments,
    }
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Prepared {len(segments)} adaptation segments in {args.output}")


if __name__ == "__main__":
    main()
