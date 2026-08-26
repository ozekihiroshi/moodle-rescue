#!/usr/bin/env python3
"""Verify the machine-readable Chapter 0 and Lesson 1 evidence map."""

from __future__ import annotations

import json
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MAP_PATH = (
    ROOT
    / "sample-content"
    / "introduction-to-python"
    / "localization"
    / "chapter-0-1-concept-map-v2.json"
)
COVERAGE_PATH = (
    ROOT / "sample-content" / "introduction-to-python" / "concept-coverage-v2.md"
)
UPGRADE_PATH = ROOT / "scripts" / "upgrade-python-sample-course-v8.php"

EXPECTED_CONCEPTS = {
    *(f"E{number:02d}" for number in range(1, 11)),
    *(f"F{number:02d}" for number in range(1, 7)),
}
MASTER_EVIDENCE = {
    "teach",
    "demonstrate",
    "practise",
    "diagnose",
    "test",
    "assess",
    "support",
}
INTRODUCE_EVIDENCE = {"teach", "demonstrate"}
QUESTION_IDS = {f"L1R-{number:02d}" for number in range(1, 11)}


def fail(message: str) -> None:
    raise SystemExit(message)


def load_coverage_levels() -> dict[str, str]:
    pattern = re.compile(r"^\| ([A-Z]\d+) \| (M|I|D|X) \|")
    levels: dict[str, str] = {}
    for line in COVERAGE_PATH.read_text(encoding="utf-8").splitlines():
        if match := pattern.match(line):
            concept_id, level = match.groups()
            if concept_id in levels:
                fail(f"Duplicate concept in coverage audit: {concept_id}")
            levels[concept_id] = level
    return levels


def split_reference(reference: str, resources: dict[str, object]) -> tuple[str, str | None]:
    resource_id, separator, member = reference.partition("#")
    if resource_id not in resources:
        fail(f"Unknown resource reference: {reference}")
    return resource_id, member if separator else None


def validate_notebook(resource_id: str, resource: dict[str, object]) -> None:
    expected_ids = set(resource["cell_ids"])
    if len(expected_ids) != len(resource["cell_ids"]):
        fail(f"Duplicate declared cell ID in {resource_id}")
    for language in ("en", "ja"):
        path = ROOT / str(resource[language])
        document = json.loads(path.read_text(encoding="utf-8"))
        actual_ids = [cell.get("id") for cell in document["cells"]]
        if len(actual_ids) != len(set(actual_ids)):
            fail(f"Duplicate Notebook cell ID: {path}")
        if set(actual_ids) != expected_ids:
            missing = sorted(expected_ids - set(actual_ids))
            unexpected = sorted(set(actual_ids) - expected_ids)
            fail(
                f"Notebook map mismatch: {path}; "
                f"missing={missing}, unexpected={unexpected}"
            )


def main() -> None:
    mapping = json.loads(MAP_PATH.read_text(encoding="utf-8"))
    if mapping.get("schema_version") != 1:
        fail("Unsupported concept-map schema")

    resources = mapping["resources"]
    for resource_id, resource in resources.items():
        if resource["kind"] == "notebook":
            validate_notebook(resource_id, resource)

    concepts = mapping["concepts"]
    concept_ids = [concept["id"] for concept in concepts]
    if len(concept_ids) != len(set(concept_ids)):
        fail("Duplicate concept ID in machine-readable map")
    if set(concept_ids) != EXPECTED_CONCEPTS:
        fail(
            "Concept scope mismatch: "
            f"missing={sorted(EXPECTED_CONCEPTS - set(concept_ids))}, "
            f"unexpected={sorted(set(concept_ids) - EXPECTED_CONCEPTS)}"
        )

    coverage_levels = load_coverage_levels()
    for concept in concepts:
        concept_id = concept["id"]
        level = concept["level"]
        if coverage_levels.get(concept_id) != level:
            fail(
                f"Coverage level mismatch for {concept_id}: "
                f"{level} != {coverage_levels.get(concept_id)}"
            )
        evidence = concept["evidence"]
        required = MASTER_EVIDENCE if level == "M" else INTRODUCE_EVIDENCE
        missing_evidence = sorted(
            key for key in required if not evidence.get(key)
        )
        if missing_evidence:
            fail(f"{concept_id} lacks evidence: {missing_evidence}")
        for references in evidence.values():
            for reference in references:
                resource_id, member = split_reference(reference, resources)
                resource = resources[resource_id]
                if member and resource["kind"] == "notebook":
                    if member not in resource["cell_ids"]:
                        fail(f"Unknown Notebook cell reference: {reference}")
                if member and resource["kind"] == "moodle_quiz":
                    if member not in resource["question_ids"]:
                        fail(f"Unknown quiz question reference: {reference}")

    question_map = mapping["quiz_question_map"]
    if set(question_map) != QUESTION_IDS:
        fail("Quiz mapping must contain exactly L1R-01 through L1R-10")
    for question_id, mapped_concepts in question_map.items():
        if not mapped_concepts:
            fail(f"Question has no concepts: {question_id}")
        unknown = set(mapped_concepts) - EXPECTED_CONCEPTS
        if unknown:
            fail(f"Question {question_id} uses unknown concepts: {sorted(unknown)}")

    upgrade_source = UPGRADE_PATH.read_text(encoding="utf-8")
    for resource in resources.values():
        marker = resource.get("marker")
        if marker and marker not in upgrade_source:
            fail(f"Missing Moodle source marker: {marker}")
    for question_id in sorted(QUESTION_IDS):
        count = upgrade_source.count(f"v8_question('{question_id}'")
        if count != 2:
            fail(
                f"{question_id} must have one English and one Japanese "
                f"definition; found {count}"
            )
    if re.search(r"v8_question\('L1-\d\d'", upgrade_source):
        fail("Overwritten broad L1 question definitions still remain in v8")

    print(
        json.dumps(
            {
                "verified": True,
                "concepts": len(concepts),
                "master": sum(concept["level"] == "M" for concept in concepts),
                "introduce": sum(concept["level"] == "I" for concept in concepts),
                "questions": len(question_map),
                "notebooks": 4,
            },
            indent=2,
        )
    )


if __name__ == "__main__":
    main()
