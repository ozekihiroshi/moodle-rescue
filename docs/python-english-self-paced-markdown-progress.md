# English self-paced Markdown edition

The completed four-course distribution is documented in the [release guide](../sample-content/python-duallearning/README.md). The notes below record local implementation and verification at the time of development.

## Status — 2026-09-19

Chapters 0–6 are available for local review:

- Course: `PYAI-INTRO-EN-DUAL-PATH`, local ID 44
- URL: http://localhost:8083/course/view.php?id=44
- Canonical content: the existing English course (local ID 10), not a back-translation of Japanese.
- Format: `duallearning`, self-paced (`path`); lesson content: LessonMark Markdown.
- Existing English/Japanese published courses and Japanese path/guided copies remain separate.

## Scope

37 visible LessonMark pages include 23 lessons, orientation, project briefs and optional references. The course retains 23 knowledge checks (230 questions), 32 English Notebook links and eight assignment destinations. Nine short review exercises for 1.1–1.6 and 2.1–2.3 supplement the canonical content with the established self-paced flow.

Learners read, predict, run and save in Python Lab, compare with expandable model answers, then retry the knowledge check. Chapter 3 requires one of A/B/C; unchosen projects are not outstanding requirements. After submitting a selected project, the learner marks the shared Chapter 3 confirmation complete. This is a learner confirmation, not an automatic grade-based OR rule.

The weekly-support Notebook uses the new English `P1_weekly_support_report_dual_path.ipynb`. It directs learners to this course's assignment rather than the original course's direct-submission bridge. Other project programs, datasets and checker contracts are unchanged.

## Verification

- Chapter-by-chapter Markdown rendering and preservation of 213 original code blocks passed. The obsolete weekly-support direct-submission command is intentionally excluded.
- All 23 lesson positions, 230 English question texts/feedback, retry settings, and 68 internal activity links passed automated checks.
- All 32 Notebook targets exist in source and the local learner workspace; JSON is valid and no linked Notebook contains the legacy weekly-support submission command.
- Chapter 3 choice/continuation tests passed with simulated completion state rolled back.
- Eight local test submissions reached their intended assignments. File hashes, submitted status and teacher permissions matched. These are test-user records, not genuine learner submissions; exclude users from distribution backups.
- Browser: English Lesson 1.1 headings, table, code, expanded model answer and navigation rendered correctly. Moodle LTI opened the dedicated English weekly-support Notebook successfully, with an idle Python kernel.
- Single-user image rebuilt; running learner servers were not restarted. Startup provisioning adds the new guide only when absent and does not overwrite learner work.
- No new plugin defect was found in these checks. This is not a claim that every Notebook cell or every quiz response was manually executed this turn.

## Implementation and reruns

Source directory: `sample-content/introduction-to-python/duallearning-path/`.

1. `prepare-python-english-path.php` copies the local canonical course without users and writes its manifest. It deliberately guards against creating duplicate copies.
2. `build-python-english-path-materials.py` creates chapter manifests, Markdown and the dedicated guide. Existing page Markdown is retained.
3. Copy the source directory to `/tmp/python-english-path` in the local Moodle container; run `install-python-english-path-chapter.php 0` through `6` as `www-data`.
4. Run `finalize-python-english-path.php` to resolve course-local links and the choice activity, then run `verify-python-english-path.php`.
5. `check-python-english-path-chapter.php` verifies each chapter. Existing path checks accept `--english`: `check-python-path-choice.php`, `check-python-path-submission-roundtrip.php`, and `check-python-path-lab-links.py` (with `audit-python-path-activities.php` installed in container `/tmp`).

These scripts and numeric IDs target this local development instance, not an arbitrary production installation. Run the finalizer after reimporting chapters. Do not overwrite a learner's existing project programs or guide.

## Publication boundary

No commit, push, production deployment or replacement public backup was performed for this work. Before distribution, create a user-free backup, restore-test it with required plugins, and validate environment-specific LTI configuration. The English teacher-guided edition is a separate next task.
