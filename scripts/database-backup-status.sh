#!/bin/sh

set -eu

repositoryroot="$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

envfile="${DATABASE_BACKUP_ENV_FILE:-.env}"
unitname="moodle-rescue-database-backup.timer"

if [ ! -f "$envfile" ]; then
    echo "Database backup environment file does not exist: $envfile" >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose v2 is unavailable." >&2
    exit 1
fi

echo "Database backup automation"
if command -v systemctl >/dev/null 2>&1; then
    enabled="$(systemctl is-enabled "$unitname" 2>/dev/null || true)"
    active="$(systemctl is-active "$unitname" 2>/dev/null || true)"
    printf '  timer enabled: %s\n' "${enabled:-unknown}"
    printf '  timer active:  %s\n' "${active:-unknown}"
    systemctl list-timers "$unitname" --no-pager 2>/dev/null || true
else
    echo "  systemd: unavailable"
fi

echo "Plugin database transfer"
artifactdirectory="$(docker compose --env-file "$envfile" exec -T moodle \
    runuser -u www-data -- php admin/cli/cfg.php \
    --component=tool_secure_s3_storage --name=databaseartifactdirectory 2>/dev/null || true)"
transferenabled="$(docker compose --env-file "$envfile" exec -T moodle \
    runuser -u www-data -- php admin/cli/cfg.php \
    --component=tool_secure_s3_storage --name=databasetransferenabled 2>/dev/null || true)"
printf '  artifact directory: %s\n' "${artifactdirectory:-unavailable}"
printf '  transfer enabled:   %s\n' "${transferenabled:-unavailable}"

docker compose --env-file "$envfile" exec -T moodle \
    runuser -u www-data -- php admin/cli/scheduled_task.php --list 2>/dev/null |
    grep 'tool_secure_s3_storage\\task\\transfer_database_backups' || true

echo "Latest local database artifacts"
docker compose --env-file "$envfile" exec -T moodle-cron \
    find /database-artifacts -maxdepth 1 -type f \
    -name 'moodle-db-*.sql.gz*' -printf '%T@ %TY-%Tm-%TdT%TH:%TM:%TSZ %s %f\n' 2>/dev/null |
    sort -n |
    tail -n 4 |
    cut -d ' ' -f 2- || true
