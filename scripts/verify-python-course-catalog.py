#!/usr/bin/env python3
"""Verify the canonical catalogue and an optional adaptation segment file."""

from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("canonical", type=Path)
    parser.add_argument("--adaptation", type=Path)
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    raw = args.canonical.read_bytes()
    canonical = json.loads(raw)
    activities = list(canonical["activities"].values())
    quizzes = [item for item in activities if item["selector"]["modname"] == "quiz"]
    question_count = sum(len(quiz["questions"]) for quiz in quizzes)

    expected = {
        "sections": 23,
        "activities": 80,
        "announcements": 4,
        "quizzes": 12,
        "questions": 120,
    }
    actual = {
        "sections": len(canonical["sections"]),
        "activities": len(activities),
        "announcements": len(canonical["announcements"]),
        "quizzes": len(quizzes),
        "questions": question_count,
    }
    if actual != expected:
        raise SystemExit(f"Canonical inventory mismatch: expected {expected}, got {actual}")

    for key, section in canonical["sections"].items():
        if section["selector"].get("component") == "mod_subsection" and "itemid" in section["selector"]:
            raise SystemExit(f"Volatile Moodle item ID found in {key}")

    report = {"canonical": "ok", **actual}
    if args.adaptation:
        adaptation = json.loads(args.adaptation.read_text(encoding="utf-8"))
        digest = hashlib.sha256(raw).hexdigest()
        if adaptation["catalogue_sha256"] != digest:
            raise SystemExit("Adaptation was prepared from a different canonical catalogue")
        segments = adaptation["segments"]
        ids = [segment["id"] for segment in segments]
        if len(ids) != len(set(ids)):
            raise SystemExit("Adaptation contains duplicate segment IDs")
        pending = sum(segment["status"] == "pending" for segment in segments)
        translated = sum(segment["status"] in {"adapted", "reviewed"} for segment in segments)
        invalid = [segment["id"] for segment in segments if segment["status"] not in {"pending", "adapted", "reviewed"}]
        if invalid:
            raise SystemExit(f"Unknown adaptation status for {invalid[0]}")
        report.update({"segments": len(segments), "pending": pending, "adapted_or_reviewed": translated})

    print(json.dumps(report, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
