# Python course export scope audit

Audit date: 2026-08-27

This report defines what belongs in the distributable Moodle course backups.
It is based on read-only inspection of the current local Moodle database.

## Canonical relationship

- `PYAI-INTRO` is the canonical English source course.
- `PYAI-INTRO-JA` is an independent Japanese adaptation derived from the English source.
- Activities visible on the current course page form the learner-facing distribution scope.
- Intentionally hidden Moodle infrastructure may remain when Moodle requires it.
- Historical quizzes, attempts, and unreferenced question-bank entries are not distribution content.

## Learner-facing structure

Both courses currently have the same learner-facing totals:

- 38 visible sections
- 132 visible activities
- 23 visible quizzes
- 230 question references in visible quizzes

The visible English and Japanese course structures therefore agree at the activity-count level.

## Hidden infrastructure to retain

Each course has one hidden `qbank` activity named `システム共有問題バンク`.
This is Moodle question-bank infrastructure and is not classified as an obsolete teaching activity.

## Historical quizzes outside the distribution scope

### English (`PYAI-INTRO`, course ID 10)

- CM 36 — archived Lesson 1 knowledge check
- CM 55 — previous 5.1 visualisation and evidence check
- CM 66 — previous scaling-up check
- CM 307 — previous 4.1 objects and classes check
- CM 311 — previous 4.2 state and validation check
- CM 315 — previous 4.3 composition and responsibility check
- CM 319 — previous 4.4 persistence and testing check

These seven quizzes reference 70 historical question-bank entries.

### Japanese (`PYAI-INTRO-JA`, course ID 12)

- CM 182 — archived Lesson 1 knowledge check
- CM 192 — archived 2.2 functions, errors, and testing check
- CM 201 — previous 5.1 visualisation and evidence check
- CM 212 — previous scaling-up check
- CM 327 — previous 4.1 objects and classes check
- CM 331 — previous 4.2 state and validation check
- CM 335 — previous 4.3 composition and responsibility check
- CM 339 — previous 4.4 persistence and testing check

These eight quizzes reference 80 historical question-bank entries.

CM 55 and 66 in English, and CM 201 and 212 in Japanese, are no longer present
in the current course-page sequence even though their database records and question
references remain.

## Question-bank classification

| Course | Course-context entries | Visible quiz entries | Historical quiz entries | Unreferenced entries |
|---|---:|---:|---:|---:|
| English | 660 | 230 | 70 | 360 |
| Japanese | 710 | 230 | 80 | 400 |

No entry is referenced by more than one quiz context, and no reference points to a
missing or foreign context. This makes a deterministic cleanup possible.

## Required cleanup before release

1. Keep all 132 visible activities in each course.
2. Keep the hidden Moodle question-bank infrastructure activity.
3. Remove only the explicitly listed historical quizzes.
4. Remove their attempts and questions; attempt history is not a release requirement.
5. Remove question-bank entries that have no remaining references.
6. Move the remaining 230 entries per course from the legacy course context into
   the module context of the one visible quiz that references each entry.
7. Generate fresh user-free English and Japanese backups.
8. Restore both backups into an isolated Moodle instance and verify activities,
   quizzes, questions, and absence of user data.

## Recovery point

The database backup made before cleanup is:

- Artifact ID: `7bbd2c8cd1bcdf4e65d8b080d3278965`
- File: `moodle-db-20260827T025027Z-7bbd2c8cd1bcdf4e.sql.gz`
- SHA-256: `29804d4f980ed8a212c254268238a6c9a35d434ca6741c4060518834fdb8a6c1`

No database cleanup described in this report had been executed when the audit was written.
