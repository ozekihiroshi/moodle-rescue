# Moodle Rescue sample content

Moodle Rescue publishes ready-to-restore teaching content separately from the
Docker platform. Release `0.1.0-alpha.1` contains two editions of the same
Python learning path:

| Edition | Course shortname | Relationship | Download and restore |
|---|---|---|---|
| English | `PYAI-INTRO` | Canonical edition | [`introduction-to-python/distribution/`](introduction-to-python/distribution/README.md) |
| Japanese | `PYAI-INTRO-JA` | Official Japanese adaptation | [`introduction-to-python-ja/distribution/`](introduction-to-python-ja/distribution/README.md) |

The Japanese course is a separate Moodle course, not a runtime language switch
or a literal translation. The English edition is normative. Both editions
share the curriculum, mastery approach, project progression, and Python Lab
workflow, while explanations are adapted for their learners.

## Public showcase packages

Two packages have different outreach roles:

| Package | Intended public use | Distribution |
|---|---|---|
| English Python course | A ready-to-restore foundation for JICA volunteers and other international education support, with a separate Python Lab service for practical work | [English Python distribution](introduction-to-python/distribution/README.md) |
| IPA AP Question 1–2 preview | A guest-accessible demonstration of LessonMark, Markdown authoring, inline working answers, official-source images, and answer disclosures | [IPA AP public preview distribution](ap-written-practice-ja-preview/distribution/README.md) |

The Python course is a complete learning path and needs site-specific Python
Lab LTI configuration. The IPA AP preview is deliberately small, labels itself
as a public preview, and contains only Question 1 and Question 2. The complete
IPA AP course remains a separate login-controlled course with all 11 questions.

## IPA AP written-practice pilot

[`ap-written-practice-ja/`](ap-written-practice-ja/README.md) is a separate
Japanese pilot course built as a same-page LessonMark study activity. It shows
the cited official question pages, places an ungraded working-answer control
under each question, and keeps the official answer and commentary in a closed
disclosure immediately below it. Working answers stay only in the learner's
current browser; this pilot does not use Moodle Quiz or Assignment.

[ap-written-practice-ja-preview/](ap-written-practice-ja-preview/README.md)
is the guest-ready derivative containing only the guide, Question 1, and
Question 2. It reuses the complete course sources rather than forking them.

## Alpha scope

The courses contain Chapters 0 through 6, lesson pages, learning checks,
integrated practice, projects, Moodle assignments, and Python Lab launch
activities. They are suitable for evaluation and pilot teaching, but still
require editorial review and learner trials before a stable release.

Python Lab activities require a separately deployed
[`python-lab-rescue`](https://github.com/ozekihiroshi/python-lab-rescue) service
and a site-specific LTI 1.3 registration. No credentials, learner accounts,
attempt history, or learner workspace data are distributed in the backups.

Each alpha artifact is verified for its first restore into a fresh Moodle
site. Restoring another copy of the same edition into a site that already
contains it is not supported in `0.1.0-alpha.1`: Moodle may reuse question
category stamps and create question-context mismatches. Use a fresh disposable
site for repeated evaluation until this limitation is resolved.

## Licensing

Original educational content is CC BY 4.0. Supporting software and repository
automation are GPL-3.0-or-later. See [`LICENSE-CONTENT.md`](../LICENSE-CONTENT.md)
and the portable `LICENSE.txt` beside each artifact.
