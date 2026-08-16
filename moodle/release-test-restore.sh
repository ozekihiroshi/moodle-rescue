#!/bin/sh
set -eu

backupfile="${RELEASE_TEST_BACKUP_FILE:-/var/moodlebackups/release-gate-source.mbz}"

verify_restored_course() {
    runuser -u www-data -- env S3_TEST_SOURCE_COURSE_ID=0 \
        php /usr/local/share/moodle-rescue/verify-integration-course.php
}

archive_download() {
    if [ ! -f "$backupfile" ]; then
        return
    fi

    restoreddirectory=/var/moodlebackups/restored
    destination="$restoreddirectory/$(basename "$backupfile")"
    install -d -m 0770 -o www-data -g www-data "$restoreddirectory"
    mv -f "$backupfile" "$destination"
    chown www-data:www-data "$destination"
    echo "Verified backup retained at: $destination"
}

if verify_restored_course >/dev/null 2>&1; then
    echo "Verified restored course already exists."
    archive_download
    exec runuser -u www-data -- env S3_TEST_SOURCE_COURSE_ID=0 \
        php /usr/local/share/moodle-rescue/verify-integration-course.php
fi

if [ ! -f "$backupfile" ]; then
    echo "Release-gate backup file is missing: $backupfile" >&2
    exit 1
fi

runuser -u www-data -- php admin/cli/restore_backup.php \
    --file="$backupfile" --categoryid=1
verification="$(verify_restored_course)"
archive_download
printf '%s\n' "$verification"
