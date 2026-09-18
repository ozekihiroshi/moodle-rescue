# Python Foundations — Dual Learning edition

Four complete alpha courses, covering Chapters 0–6. English is the canonical
curriculum; Japanese is its translated/adapted edition. Self-paced and
teacher-guided courses are independent courses. The guided edition adds
preparation, review and chapter help forums without changing project contracts.

## Downloads

Version **0.1.0-alpha.1**:

| Language | Learning mode | Moodle backup |
|---|---|---|
| English | Self-paced | [en-path](distribution/python-foundations-dual-en-path-0.1.0-alpha.1.mbz) |
| English | Teacher-guided | [en-guided](distribution/python-foundations-dual-en-guided-0.1.0-alpha.1.mbz) |
| Japanese | Self-paced | [ja-path](distribution/python-foundations-dual-ja-path-0.1.0-alpha.1.mbz) |
| Japanese | Teacher-guided | [ja-guided](distribution/python-foundations-dual-ja-guided-0.1.0-alpha.1.mbz) |

[Manifest](distribution/manifest.json) · [SHA-256 checksums](distribution/SHA256SUMS)

All four archives passed [actual Moodle restore verification](VERIFICATION.md).

Each course contains 23 ten-question learning checks, 8 assignments and 32
Python Lab links. Self-paced courses have 37 LessonMark activities; guided
courses have 83, including 23 preparation and 23 review activities.
Optional activities are not required for the main learning path.

## Requirements

Tested with Moodle **5.2.2**, plus:

- [LessonMark v0.3.0-alpha4](https://github.com/ozekihiroshi/moodle-mod_lessonmark/releases/tag/v0.3.0-alpha4), build 2026091800.
- [Dual Learning v0.1.0-alpha5](https://github.com/ozekihiroshi/moodle-format_duallearning/releases/tag/v0.1.0-alpha5), build 2026091803.
- [Python Lab Rescue (tested revision c6b6b2b)](https://github.com/ozekihiroshi/python-lab-rescue/tree/c6b6b2b), including the English and Japanese `P1_weekly_support_report_dual_path.ipynb` guides. Until merged, use branch `codex/python-course-midpoint` at this revision rather than assuming `main` includes these changes.

The platform's existing `plugins.lock` may pin older plugins. Installing
moodle-rescue alone does **not** guarantee these requirements are installed.
Install the listed plugin versions before restoring. A special theme is not
required. Python Lab needs its own running server and Moodle LTI 1.3 setup;
restoring a course does not provision it. Guests cannot use individual Lab
workspaces or submit assignments.

## Install as a new course

1. Download a `.mbz` and verify its SHA-256 against the manifest.
2. In Moodle, choose **Restore**, not **Upload courses**. Restore to a **new
   course**, with activities/resources and files enabled, without users.
3. The restored course is hidden. Check its learning mode and activities as an
   administrator before making it visible.
4. Follow [Python Lab production reconnection](../../docs/python-lab-production-reconnect.md).
   Lab links deliberately use `https://python-lab.example.invalid/`; replace
   deployment configuration and associate the destination site's LTI tool.
   These editions use the Moodle Assignment in the course being studied for
   submission. Do not reuse an older direct-submission command without verifying
   its destination mapping: several editions can have similarly named assignments.
5. Enrol teachers and learners, choose enrolment methods and any local dates,
   test one Lab launch and assignment, then make the course visible.

Manual enrolment configuration is included, but no enrolments, passwords,
learner attempts, submissions, grades or teacher feedback are distributed.
Guest/self-enrolment methods and local LTI secrets are not transferred.
Course-internal activity links are remapped by Moodle during restore.

CLI administrators can use Moodle's standard `admin/cli/restore_backup.php`
with `--file=/absolute/path/to/course.mbz --categoryid=<destination-category>`;
run it as the web-service user and check `--help` on the installed Moodle.
Complete the same post-restore configuration above.

## Sources and verification

The four authoring sources remain in:

- `sample-content/introduction-to-python/duallearning-path/`
- `sample-content/introduction-to-python/duallearning-guided/`
- `sample-content/introduction-to-python-ja/duallearning-path/`
- `sample-content/introduction-to-python-ja/duallearning-guided/`

These directories include authoring/provenance metadata; use the `.mbz`
archives, not a source directory, to install a complete course. Local course
IDs in development scripts are not destination-site installation instructions.
Previously published non-Dual Learning courses remain available separately.

Release tooling:

```sh
python3 scripts/package-python-dual-distributions.py --verify
# Maintainers only: restore all four into hidden local acceptance courses.
sh scripts/test-python-dual-distributions.sh
```

Exports omit hidden legacy activities. Packaging removes local deployment
credentials, namespaces question stamps for coexistence, and preserves
lesson content, questions, code examples and assignment contracts.

## Alpha limitations and licensing

This is a complete course package, not a claim of finished editorial review.
Further classroom feedback, accessibility review and copy editing are welcome.
The required plugins are also alpha releases. Test on a staging site first.
No production-server update is performed by these files.

Course materials: [CC BY 4.0](distribution/LICENSE.txt). Bundled code and
third-party dependencies retain their applicable repository licences.
