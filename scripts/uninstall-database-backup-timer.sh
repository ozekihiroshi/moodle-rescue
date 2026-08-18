#!/bin/sh

set -eu

unitdirectory="/etc/systemd/system"
serviceunit="$unitdirectory/moodle-rescue-database-backup.service"
timerunit="$unitdirectory/moodle-rescue-database-backup.timer"

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this uninstaller through sudo." >&2
    exit 1
fi

if ! command -v systemctl >/dev/null 2>&1; then
    echo "systemctl is unavailable." >&2
    exit 1
fi

systemctl disable --now moodle-rescue-database-backup.timer 2>/dev/null || true
rm -f "$serviceunit" "$timerunit"
systemctl daemon-reload
systemctl reset-failed moodle-rescue-database-backup.service 2>/dev/null || true

echo "Removed the Moodle Rescue database backup timer."
echo "Database artifacts and Docker volumes were preserved."
