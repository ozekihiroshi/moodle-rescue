#!/bin/sh
set -eu

backupfile="${RELEASE_TEST_BACKUP_FILE:-/var/moodlebackups/release-gate-source.mbz}"

if runuser -u www-data -- env S3_TEST_SOURCE_COURSE_ID=0 php /usr/local/share/moodle-rescue/verify-integration-course.php >/dev/null 2>&1; then
    echo "Verified restored course already exists."
    exec runuser -u www-data -- env S3_TEST_SOURCE_COURSE_ID=0 php /usr/local/share/moodle-rescue/verify-integration-course.php
fi

if [ ! -f "$backupfile" ]; then
    echo "Release-gate backup file is missing: $backupfile" >&2
    exit 1
fi

runuser -u www-data -- php admin/cli/restore_backup.php --file="$backupfile" --categoryid=1
exec runuser -u www-data -- env S3_TEST_SOURCE_COURSE_ID=0 php /usr/local/share/moodle-rescue/verify-integration-course.php
