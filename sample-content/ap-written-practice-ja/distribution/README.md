# IPA AP written-practice course alpha release 0.1.0-alpha.1

This directory contains a ready-to-restore Moodle backup of the Japanese
course **応用情報技術者試験 過去問題学習 — 令和7年度春期 午後・原文解答解説版**
(`IPA-AP-WRITTEN-JA-V3`).

## Files

- `ipa-ap-written-practice-ja-2025-spring-0.1.0-alpha.1.mbz` — Moodle course backup
- `manifest.json` — release, compatibility, inventory, and verification metadata
- `SHA256SUMS` — integrity checksum
- `LICENSE.txt` — portable licence and third-party-material notice

The backup was produced on Moodle 5.2.2 with users and user-dependent data
excluded. It contains 12 LessonMark activities and 55 official source-page
images covering all 11 questions in the Spring 2025 afternoon examination.

## Verify

From the repository root in WSL or Linux:

```sh
sh scripts/verify-ipa-ap-distribution.sh
```

## Restore from the Moodle interface

1. Install a compatible LessonMark 0.2-series plugin first.
2. Open **Site administration > Courses > Restore course**.
3. Upload the versioned `.mbz` and restore it as a new course.
4. Review visibility, enrolment, completion, and access settings.
5. Test the restored course with a non-teacher account.

## Restore from the CLI in the local Docker environment

The first argument is the destination category ID; it defaults to `1`.

```sh
sh scripts/restore-ipa-ap-distribution.sh 1
```

For a different Compose deployment, set `COMPOSE_FILE` and
`IPA_AP_COMPOSE_OVERRIDE`, or invoke Moodle's standard command directly:

```sh
php admin/cli/restore_backup.php \
  --file=/path/to/ipa-ap-written-practice-ja-2025-spring-0.1.0-alpha.1.mbz \
  --categoryid=1
```

## Alpha limitations

This is a controlled-pilot release. Human review of explanations,
accessibility, learner trials, and editorial refinement is ongoing. Working
answers in RESPONSE and CHOICE blocks are browser-local, ungraded, and are not
included in Moodle submissions or teacher reports.

Official exam pages, answer examples, and examiner commentary remain IPA
third-party material. See `LICENSE.txt` and the parent directory's
`THIRD-PARTY-NOTICES.md` before redistributing or adapting the package.
