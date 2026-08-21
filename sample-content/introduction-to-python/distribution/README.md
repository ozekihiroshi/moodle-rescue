# Python for Data sample-course release 1.0.0

This directory contains the ready-to-restore Moodle course backup for
**Python for Data: Foundations in the AI Era** (`PYAI-INTRO`). It is the first
fixed sample-content release from Moodle Rescue.

## Files

- `python-for-data-foundations-ai-era-1.0.0.mbz` — Moodle course backup
- `manifest.json` — machine-readable release and compatibility metadata
- `SHA256SUMS` — integrity checksum
- `LICENSE.txt` — portable CC BY 4.0 notice and recommended attribution

The backup was produced on Moodle 5.2.2 and contains no learner accounts or
attempt history. Moodle can normally restore backups from supported earlier
versions, but receiving sites should test the restore on their exact Moodle
version before assigning the course.

## Verify before restoring

From the repository root in WSL:

```sh
sh scripts/verify-python-sample-distribution.sh
```

The expected SHA-256 is:

```text
23898d1c887dea94355c18dd8179305905b79665161dee56be2225e2882f99ec
```

## Restore in Moodle

1. Sign in as a site administrator or a user allowed to restore courses.
2. Open **Site administration > Courses > Restore course**.
3. Upload `python-for-data-foundations-ai-era-1.0.0.mbz`.
4. Restore it as a new course, review every setting, and keep user data
   excluded.
5. Confirm that hidden teacher resources remain hidden and test the course with
   a non-teacher account before enrolment.

The source and regeneration procedure are in the
[parent directory](../README.md).

## Python Lab and LTI 1.3

The Moodle pages, assignments, and quizzes are present in this backup. The
Python Lab notebook service is deliberately not embedded in it. External-tool
activities retain course-level links, but a receiving Moodle site must deploy
its own Python Lab and register the LTI 1.3 tool before those links will open.
Until then, hide or disable the Python Lab activities; the remaining course can
still be reviewed.

Never copy signing keys, client secrets, deployment identifiers, private URLs,
or learner notebook volumes from the authoring environment into a release.

## Release status and licensing

This is a fixed, checksummed course artifact rather than an automatically
updated `latest` file. Changes to course content require a new versioned
backup, manifest, checksum, and verification run.

The original educational content in this backup is licensed under Creative
Commons Attribution 4.0 International (CC BY 4.0), copyright © 2026 Hiroshi
Ozeki. The exact scope and recommended attribution are defined in
[`LICENSE-CONTENT.md`](../../LICENSE-CONTENT.md). Moodle, plugins, and other
third-party components retain their own licenses and notices.
