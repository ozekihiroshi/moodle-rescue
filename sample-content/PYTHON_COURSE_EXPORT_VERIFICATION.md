# Python course export verification

Verification date: 2026-08-27

## Result

The canonical English course and the independent Japanese adaptation were
cleaned, backed up without users, and restored successfully into a newly
created isolated Moodle 5.2.2 environment.

| Check | English | Japanese |
|---|---:|---:|
| Restored sections | 38 | 38 |
| Restored quizzes | 23 | 23 |
| Restored question references | 230 | 230 |
| Questions in the wrong context | 0 | 0 |
| Restored enrolments | 0 | 0 |
| Restored quiz attempts | 0 | 0 |
| Restored assignment submissions | 0 | 0 |

The first clean restores were course ID 2 (`PYAI-INTRO`) and course ID 3
(`PYAI-INTRO-JA`) in the disposable Compose project
`moodle-rescue-python-course-restore-20260827`.

## Released artifacts

### English canonical course

- File: `introduction-to-python/distribution/python-for-data-foundations-ai-era-0.1.0-alpha.1.mbz`
- Size: 317514 bytes
- SHA-256: `f602e2b05c32dcdcccfe443e02ed538060d3b42a3a1a03b4c12800dbe8703e00`

### Japanese adaptation

- File: `introduction-to-python-ja/distribution/python-for-data-foundations-ai-era-ja-0.1.0-alpha.1.mbz`
- Size: 342731 bytes
- SHA-256: `cd7b1eb4cd96c135a134f329480c699639dd3eb38d2f5b853208f61ee171a051`

## Cleanup performed on the authoring courses

- Removed 7 historical English quizzes and 8 historical Japanese quizzes.
- Removed their attempt history, in accordance with the authoring decision not
  to preserve development attempts.
- Deleted 430 unreferenced English question-bank entries.
- Deleted 480 unreferenced Japanese question-bank entries.
- Moved the remaining 230 entries in each course into the module context of
  the visible quiz that references them.
- Retained Moodle's hidden system question-bank activity in each course.

The pre-cleanup recovery point remains documented in
`PYTHON_COURSE_EXPORT_SCOPE_AUDIT.md`.
