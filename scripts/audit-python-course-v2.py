#!/usr/bin/env python3
"""Map every canonical v1 quiz question to the approved v2 curriculum."""

from __future__ import annotations

import argparse
import html
import json
import re
from pathlib import Path


QUIZ_MAP = {
    "Knowledge check: Lesson 1: Your first Python program": ("Chapter 1", "1.1 Programs, values, expressions, and output"),
    "Knowledge check: Lesson 2: Variables, types, input, and calculations": ("Chapter 1", "Split across 1.2 variables/state, 1.3 scalar types/arithmetic, and 1.4 strings/input"),
    "Knowledge check: Lesson 3: Decisions with conditions": ("Chapter 2", "2.1 Selection and Boolean conditions"),
    "Knowledge check: Lesson 4: Repetition with loops": ("Chapter 2", "2.2 Repetition, tracing, and accumulators"),
    "Knowledge check: Lesson 5: Lists and dictionaries": ("Chapter 3", "3.1 Collections and records"),
    "Knowledge check: Lesson 6: Functions, errors, and testing": ("Chapter 4/5", "Split between functions/testing and errors/exceptions"),
    "Knowledge check: Lesson 7: Tables, CSV, and pandas": ("Chapter 7", "7.1 Tables, DataFrames, and CSV loading"),
    "Knowledge check: Lesson 8: Inspecting and selecting data": ("Chapter 7", "7.2 Inspection, selection, and Boolean filtering"),
    "Knowledge check: Lesson 9: Cleaning data": ("Chapter 7", "7.3 Cleaning with an audit trail"),
    "Knowledge check: Lesson 10: Grouping and summary statistics": ("Chapter 7", "7.4 Grouping and summary statistics"),
    "Knowledge check: Lesson 11: Visualisation and evidence": ("Chapter 7", "7.5 Visualisation and evidence"),
    "Applied check: Scaling up safely": ("Chapter 7/8", "Large-file validation and final evidence workflow"),
}


def plain(value: str) -> str:
    return re.sub(r"\s+", " ", re.sub(r"<[^>]+>", " ", html.unescape(value))).strip()


def combined_question_text(question: dict) -> str:
    parts = [question.get("name", ""), question.get("questiontext", {}).get("text", ""),
             question.get("generalfeedback", {}).get("text", "")]
    for answer in question.get("answers", []):
        parts.append(answer.get("text", {}).get("text", ""))
        parts.append(answer.get("feedback", {}).get("text", ""))
    return plain(" ".join(parts))


def classify(quiz_name: str, text: str) -> tuple[str, str]:
    lowered = text.lower()
    if re.search(r"\bai\b|artificial intelligence|ai-generated|ai wrote", lowered):
        return "replace", "AI-policy assessment is outside the Python learning outcomes."
    if "naledi" in lowered:
        return "rewrite_context", "Remove the named character and state the work task directly."
    if quiz_name == "Knowledge check: Lesson 1: Your first Python program":
        return "replace_in_v2_prototype", "Replace with the taught v2 instruction order, values, simple expressions, and output check."
    if quiz_name == "Knowledge check: Lesson 2: Variables, types, input, and calculations":
        return "split_and_review", "Assign the item to 1.2, 1.3, or 1.4 and teach its prerequisite before assessment."
    if quiz_name == "Knowledge check: Lesson 6: Functions, errors, and testing":
        return "split_and_review", "Move the item to Chapter 4 or 5 and verify that its prerequisite is taught first."
    return "retain_after_alignment_review", "Retain only if the mapped v2 lesson explicitly teaches the tested idea first."


def build(catalogue: dict) -> dict:
    rows = []
    for activity_key, activity in catalogue["activities"].items():
        if activity.get("selector", {}).get("modname") != "quiz":
            continue
        quiz_name = activity["name"]
        if quiz_name not in QUIZ_MAP:
            raise ValueError(f"Unmapped quiz: {quiz_name}")
        chapter, lesson = QUIZ_MAP[quiz_name]
        for question_key, question in activity.get("questions", {}).items():
            text = combined_question_text(question)
            action, reason = classify(quiz_name, text)
            rows.append({
                "source_activity": activity_key,
                "source_quiz": quiz_name,
                "source_question": question_key,
                "source_slot": question.get("selector", {}).get("slot"),
                "source_name": question.get("name"),
                "prompt": plain(question.get("questiontext", {}).get("text", "")),
                "v2_chapter": chapter,
                "v2_lesson": lesson,
                "action": action,
                "reason": reason,
            })
    if len(rows) != 120:
        raise ValueError(f"Expected 120 questions, found {len(rows)}")

    counts = {}
    for row in rows:
        counts[row["action"]] = counts.get(row["action"], 0) + 1
    return {
        "schema_version": 1,
        "source": "canonical-en-1.0.0",
        "target": "curriculum-v2",
        "question_count": len(rows),
        "action_counts": counts,
        "questions": rows,
    }


def markdown_report(audit: dict) -> str:
    lines = [
        "# Python v2 question audit",
        "",
        "Generated from the canonical English 1.0.0 catalogue. Every one of the 120",
        "questions has a target curriculum location and migration action.",
        "",
        "## Action summary",
        "",
        "| Action | Questions |",
        "|---|---:|",
    ]
    for action, count in sorted(audit["action_counts"].items()):
        lines.append(f"| `{action}` | {count} |")
    lines.extend(["", "## Question mapping", "", "| Quiz / slot | Current question | v2 destination | Action |", "|---|---|---|---|"])
    for row in audit["questions"]:
        current = row["source_name"].replace("|", "\\|")
        destination = f"{row['v2_chapter']} — {row['v2_lesson']}".replace("|", "\\|")
        lines.append(f"| {row['source_quiz']} / {row['source_slot']} | {current} | {destination} | `{row['action']}` |")
    return "\n".join(lines) + "\n"


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("catalogue", type=Path)
    parser.add_argument("--json-output", type=Path, required=True)
    parser.add_argument("--markdown-output", type=Path, required=True)
    args = parser.parse_args()

    catalogue = json.loads(args.catalogue.read_text(encoding="utf-8"))
    audit = build(catalogue)
    args.json_output.parent.mkdir(parents=True, exist_ok=True)
    args.json_output.write_text(json.dumps(audit, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    args.markdown_output.write_text(markdown_report(audit), encoding="utf-8")
    print(json.dumps({"questions": audit["question_count"], "actions": audit["action_counts"]}, indent=2))


if __name__ == "__main__":
    main()
