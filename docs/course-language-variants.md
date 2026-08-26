# Course language variants

## Canonical edition

`PYAI-INTRO`, **Python for Data: Foundations in the AI Era**, is the canonical
English edition. Learning goals, assessment intent, mastery requirements, and
the overall progression are governed from that edition.

Language variants are separate Moodle courses and separate release artifacts.
They are not byte-for-byte translations and are not maintained by placing
multiple languages in the same Moodle fields.

## Official adaptations

`PYAI-INTRO-JA`, **Pythonによるデータ活用：AI時代の基礎**, is the official
Japanese adaptation. It may rewrite explanations, examples, feedback, and
teaching language so they work naturally for Japanese learners. It must retain
the canonical learning outcomes and assessment blueprint.

An adaptation manifest records both its own version and the exact canonical
version on which it is based. A canonical update does not silently overwrite an
adaptation. The update is reviewed, adapted, verified, and released separately.

## Invariants

The following must remain aligned unless a documented pedagogical decision
explicitly supersedes one of them:

- chapter and topic correspondence;
- concepts and Python capabilities taught;
- number and intent of learning checks;
- correct answers, weights, and misconception being diagnosed;
- pass grade, retry policy, and course-completion requirements;
- project learning outcomes and rubric totals;
- responsible-AI policy; and
- the progression from programming foundations to data analysis and evidence.

The following may be adapted:

- explanation order and sentence structure;
- examples needed to make an idea natural in the target language;
- terminology notes and first-use English terms;
- culturally dependent names or classroom instructions; and
- feedback wording, provided it diagnoses the same misconception.

Python code, CSV column names, and shared data semantics normally remain the
same so learners can use upstream documentation and reproduce results.

## Release relationship

A variant manifest uses independent adaptation versions and an explicit source
relationship, for example:

```json
{
  "course": "PYAI-INTRO-JA",
  "adaptation_version": "1.0.0",
  "canonical_course": "PYAI-INTRO",
  "based_on_canonical_version": "1.0.0",
  "language": "ja",
  "relationship": "official_adaptation"
}
```

## Quality gate

Before release, tooling compares the canonical catalogue and adaptation for
keys, activity types, question counts, answer fractions, grade weights,
visibility, and completion policy. A human review then checks terminology,
naturalness, code/output consistency, accessibility, and teacher guidance.

The canonical catalogue is an inventory and change-detection input. It is not
sent through an automatic translator and is not itself a learner-facing
course.
