#!/bin/sh

set -eu

repositoryroot="$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

envfile="${DATABASE_BACKUP_ENV_FILE:-.env}"
lockfile="${DATABASE_BACKUP_LOCK_FILE:-$repositoryroot/build/database-backup.lock}"

if [ ! -f "$envfile" ]; then
    echo "Database backup environment file does not exist: $envfile" >&2
    exit 1
fi

for commandname in docker flock; do
    if ! command -v "$commandname" >/dev/null 2>&1; then
        echo "Required database backup command is unavailable: $commandname" >&2
        exit 1
    fi
done

lockdirectory="$(dirname "$lockfile")"
if [ -L "$lockdirectory" ] || [ -L "$lockfile" ]; then
    echo "Database backup lock path must not contain a symbolic link." >&2
    exit 1
fi
mkdir -p "$lockdirectory"
chmod 0750 "$lockdirectory"

exec 9>"$lockfile"
if ! flock -n 9; then
    echo "Another database backup producer is already running." >&2
    exit 1
fi

docker compose --env-file "$envfile" config --quiet
docker compose --env-file "$envfile" --profile tools run --rm moodle-db-backup
