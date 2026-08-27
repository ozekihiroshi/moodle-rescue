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

## Alpha scope

The courses contain Chapters 0 through 6, lesson pages, learning checks,
integrated practice, projects, Moodle assignments, and Python Lab launch
activities. They are suitable for evaluation and pilot teaching, but still
require editorial review and learner trials before a stable release.

Python Lab activities require a separately deployed
[`python-lab-rescue`](https://github.com/ozekihiroshi/python-lab-rescue) service
and a site-specific LTI 1.3 registration. No credentials, learner accounts,
attempt history, or learner workspace data are distributed in the backups.

## Licensing

Original educational content is CC BY 4.0. Supporting software and repository
automation are GPL-3.0-or-later. See [`LICENSE-CONTENT.md`](../LICENSE-CONTENT.md)
and the portable `LICENSE.txt` beside each artifact.
