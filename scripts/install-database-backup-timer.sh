#!/bin/sh

set -eu

repositoryroot="$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)"
serviceuser="${DATABASE_BACKUP_SERVICE_USER:-${SUDO_USER:-}}"
oncalendar="${1:-*-*-* 02:17:00}"
unitdirectory="/etc/systemd/system"
serviceunit="$unitdirectory/moodle-rescue-database-backup.service"
timerunit="$unitdirectory/moodle-rescue-database-backup.timer"

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this installer through sudo." >&2
    exit 1
fi

case "$serviceuser" in
    ''|root|*[!A-Za-z0-9_.-]*)
        echo "A non-root DATABASE_BACKUP_SERVICE_USER or SUDO_USER is required." >&2
        exit 1
        ;;
esac

if ! id "$serviceuser" >/dev/null 2>&1; then
    echo "Database backup service user does not exist: $serviceuser" >&2
    exit 1
fi

case "$repositoryroot" in
    *[!A-Za-z0-9_./-]*)
        echo "Repository path contains characters unsupported by the systemd installer." >&2
        exit 1
        ;;
esac

case "$oncalendar" in
    ''|*[!0-9A-Za-z*,:/_.~+@\ -]*)
        echo "The systemd calendar expression contains unsupported characters." >&2
        exit 1
        ;;
esac

for commandname in docker install sed systemctl; do
    if ! command -v "$commandname" >/dev/null 2>&1; then
        echo "Required timer installation command is unavailable: $commandname" >&2
        exit 1
    fi
done

if [ ! -f "$repositoryroot/.env" ]; then
    echo "Production environment file is unavailable: $repositoryroot/.env" >&2
    exit 1
fi

if ! runuser -u "$serviceuser" -- docker info >/dev/null 2>&1; then
    echo "The service user cannot access Docker: $serviceuser" >&2
    exit 1
fi

temporarydirectory="$(mktemp -d)"
cleanup() {
    rm -rf "$temporarydirectory"
}
trap cleanup EXIT HUP INT TERM

sed \
    -e "s|@SERVICE_USER@|$serviceuser|g" \
    -e "s|@REPOSITORY_ROOT@|$repositoryroot|g" \
    "$repositoryroot/systemd/moodle-rescue-database-backup.service.in" \
    > "$temporarydirectory/moodle-rescue-database-backup.service"
sed \
    -e "s|@ON_CALENDAR@|$oncalendar|g" \
    "$repositoryroot/systemd/moodle-rescue-database-backup.timer.in" \
    > "$temporarydirectory/moodle-rescue-database-backup.timer"

install -o root -g root -m 0644 \
    "$temporarydirectory/moodle-rescue-database-backup.service" "$serviceunit"
install -o root -g root -m 0644 \
    "$temporarydirectory/moodle-rescue-database-backup.timer" "$timerunit"

systemctl daemon-reload
systemctl enable --now moodle-rescue-database-backup.timer
systemctl list-timers moodle-rescue-database-backup.timer --no-pager
