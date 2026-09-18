# Distribution verification — 2026-09-19

The actual four packaged `.mbz` files were restored together on the local Moodle
5.2.2 site, using LessonMark 2026091800 and Dual Learning 2026091803.
Original courses 10, 12 and the four authored courses 42–45 were not replaced.

| Package | Restore | LessonMark | Quizzes / questions | Lab links | Assignments | Internal links checked |
|---|---|---:|---:|---:|---:|---:|
| EN self-paced | PASS | 37 | 23 / 230 | 32 | 8 | 60 |
| EN guided | PASS | 83 | 23 / 230 | 32 | 8 | 298 |
| JA self-paced | PASS | 37 | 23 / 230 | 32 | 8 | 52 |
| JA guided | PASS | 83 | 23 / 230 | 32 | 8 | 290 |

Checks cover hidden initial course state, learning mode, no hidden legacy
activities, question references owned by each restored quiz, same-course link
remapping, unlimited quiz attempts / highest grade, placeholder LTI destinations,
and absence of enrolments, submissions and grades. Archive inspection additionally
checks user-free settings, removed secrets and SHA-256 hashes. Per-course results
are in `distribution/restore-*.json`; IDs there belong only to local test courses.

This is a real Moodle restore test, not merely an archive extraction test.
It is not a production-site deployment or a fresh browser walkthrough of every
restored lesson. Prior learner/teacher UI and chapter verification is recorded in
the four development progress documents linked from the authoring directories.

Python Lab revision `c6b6b2b` passed isolated-container tests for writable learner
copies, preservation of edited files on restart, unique OAuth state-cookie naming,
dependency consistency, CSV/Excel/plot operations and authenticated JupyterLab
startup. The cookie test is not itself an end-to-end browser authentication test.

Packaging fixes found during acceptance testing were confined to XML declaration,
copying archive contents, and Moodle-specific empty credential representation
(Quiz empty string versus LTI null). No course question, code contract or plugin
implementation was changed for publication.
