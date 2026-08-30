# Python course alpha release 0.1.0-alpha.1

This directory contains the ready-to-restore Moodle backup for the canonical
English course **Python for Data: Foundations in the AI Era** (`PYAI-INTRO`).

## Files

- `python-for-data-foundations-ai-era-0.1.0-alpha.1.mbz` — Moodle course backup
- `manifest.json` — release, compatibility, and inventory metadata
- `SHA256SUMS` — integrity checksum
- `LICENSE.txt` — portable CC BY 4.0 notice

The backup was produced on Moodle 5.2.2 without learner accounts, attempts,
submissions, completion records, or other user-dependent data.

## Verify

From the repository root in WSL or Linux:

```sh
sh scripts/verify-python-sample-distribution.sh
```

Expected SHA-256:

```text
f602e2b05c32dcdcccfe443e02ed538060d3b42a3a1a03b4c12800dbe8703e00
```

## Restore

1. Open **Site administration > Courses > Restore course**.
2. Upload the versioned `.mbz` and restore it as a new course.
3. Review course visibility, enrolment, completion, grade, and assignment
   settings before admitting learners.
4. Test the restored course using a non-teacher account.

Python Lab launch activities require a separately deployed Python Lab and a
site-specific LTI 1.3 registration. The backup deliberately contains no
signing key, client secret, deployment credential, or learner workspace.
After restoring the course, follow
[`docs/python-lab-production-reconnect.md`](../../../docs/python-lab-production-reconnect.md)
to reconnect every existing Lab activity without rebuilding the course
structure.

## Alpha limitations

The full Chapters 0–6 learning path is present, but editorial review, learner
trials, accessibility review, and final assessment calibration are ongoing.
Use this release for evaluation or a controlled pilot, not as an unattended
production curriculum.

This artifact is verified for the first restore into a fresh Moodle 5.2.2
site. Do not restore another copy of the same English edition into a site that
already contains it during alpha. A later duplicate restore may reuse Moodle
question-category stamps and produce question-context mismatches. Use a fresh
disposable site when repeating restore evaluation.
