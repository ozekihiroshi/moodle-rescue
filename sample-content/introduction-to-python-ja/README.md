# Pythonによるデータ活用：AI時代の基礎

This directory is the source and release workspace for `PYAI-INTRO-JA`, the
official Japanese adaptation of the canonical English course `PYAI-INTRO`.

It is a separate Moodle course and release artifact. It is not a literal or
automatically synchronised translation. Explanations and teaching language are
rewritten for Japanese learners while the learning outcomes, assessment
intent, mastery policy, and data-analysis progression remain aligned with the
canonical edition.

Release 1.0.0 contains all 1,607 adapted segments; none remain pending. The
course guide, responsible-AI policy, all four announcements, the complete
learner path, projects, Python Lab links, learning checks, reference material,
model answers, and teacher guidance are included. The authoring Course ID 12
remains intentionally hidden; the versioned, user-free `.mbz` is published in
[`distribution/`](distribution/README.md).

## Version 2 prototype

The versioned 1.0.0 `.mbz` remains an immutable release artifact. The local
authoring Course ID 12 now contains the Chapter 0 and Chapter 1 Lesson 1 v2
prototype for review. Its Moodle pages, mastery check, and `ja/` Python Lab
Notebooks are Japanese adaptations of the new canonical English design. It is
not yet a Japanese version 2 release.

## Adaptation workflow

The English catalogue is the source inventory. A segment file records Japanese
work without changing the canonical course:

```sh
python3 scripts/prepare-python-course-adaptation.py \
  sample-content/introduction-to-python/localization/canonical-en-1.0.0.json \
  sample-content/introduction-to-python-ja/adaptation/segments-ja-1.0.0.json

python3 scripts/apply-python-adaptation-memory.py \
  sample-content/introduction-to-python-ja/adaptation/segments-ja-1.0.0.json \
  sample-content/introduction-to-python-ja/adaptation/translation-memory-ja.json

python3 scripts/verify-python-course-catalog.py \
  sample-content/introduction-to-python/localization/canonical-en-1.0.0.json \
  --adaptation sample-content/introduction-to-python-ja/adaptation/segments-ja-1.0.0.json
```

Do not regenerate an active segment file without first preserving reviewed
targets. Translation memories are exact-match, reviewed inputs; they do not
perform automatic translation.

## Version relationship

The first release is adaptation version `1.0.0`, based on canonical English
version `1.0.0`. The English edition remains the normative source. The exact
relationship is recorded in
[`adaptation.json`](adaptation.json).

## License

Original Japanese educational content is licensed under CC BY 4.0. Code
and generation or verification tooling remain GPL-3.0-or-later. See the
repository's [`LICENSE-CONTENT.md`](../../LICENSE-CONTENT.md).
