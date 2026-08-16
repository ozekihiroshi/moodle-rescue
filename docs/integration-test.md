# Secure S3 integration test

## Scope

This test exercises a Moodle-generated course backup through the local
S3-compatible path:

1. Create a minimal course and Page activity through Moodle APIs.
2. Generate a real `.mbz` with Moodle's `admin/cli/backup.php`.
3. Transfer it with the Secure S3 Storage scheduled task.
4. Download it from MinIO using the dedicated least-privilege identity.
5. Compare the original and downloaded SHA-256 digests.
6. Restore the downloaded archive with Moodle's
   `admin/cli/restore_backup.php`.
7. Verify the restored Page content through Moodle's database API.

The source and verification utilities are:

- `scripts/create-integration-course.php`
- `scripts/verify-integration-course.php`

## Result on 2026-08-16

- Source course ID: `3`
- Source shortname: `S3INT-CLI`
- Source Page course-module ID: `4`
- Backup file:
  `backup-moodle2-course-3-S3INT-CLI-20260816-0615.mbz`
- Size: `5427` bytes
- SHA-256:
  `0d32f1b45e35cb67d362b65f7cd29b12c6ee08fc915e1d9e3cc79d5b525f8549`
- Transfer status: `success`
- Object key:
  `moodle/v1/0d/0d32f1b45e35cb67d362b65f7cd29b12c6ee08fc915e1d9e3cc79d5b525f8549.mbz`
- Downloaded SHA-256: identical to the source
- Restored course ID: `4`
- Restored shortname: `S3INT-CLI_1`
- Restored Page ID: `2`
- Verification marker: present

The plugin stability interval was temporarily reduced to one second for the
test and restored to 60 seconds immediately after transfer.

The downloaded copy was removed from `/var/moodlebackups` after restoration.
Recovery downloads should use a directory outside the monitored source so they
are not observed as new transfer candidates.

## Remaining release gates

This result proves the local Moodle-to-MinIO round trip and restoration into a
new course in the existing local Moodle instance. It does not yet prove:

- restoration into a completely empty Moodle environment;
- operation against real Amazon S3 with workload credentials;
- all automated source-boundary, partial-I/O, concurrency, retention, upgrade,
  disable/re-enable, and uninstall cases.
