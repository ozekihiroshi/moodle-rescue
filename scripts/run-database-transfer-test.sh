#!/bin/sh

set -eu

repositoryroot="$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

envfile="${DATABASE_TRANSFER_ENV_FILE:-.env}"
composefile="${DATABASE_TRANSFER_COMPOSE_FILE:-docker-compose.local.yml}"

if [ ! -f "$envfile" ]; then
    echo "Database transfer test environment file does not exist: $envfile" >&2
    exit 1
fi

local_compose() {
    docker compose --env-file "$envfile" -f "$composefile" "$@"
}

local_moodle_php() {
    local_compose exec -T moodle runuser -u www-data -- php "$@"
}

local_moodle_php admin/cli/cfg.php \
    --component=tool_secure_s3_storage \
    --name=databaseartifactdirectory \
    --set=/database-artifacts >/dev/null
local_moodle_php admin/cli/cfg.php \
    --component=tool_secure_s3_storage \
    --name=databasetransferenabled \
    --set=1 >/dev/null

execute_database_transfer() {
    local_compose run --rm --no-deps moodle-cron \
        runuser -u www-data -- php admin/cli/scheduled_task.php \
        --execute='\tool_secure_s3_storage\task\transfer_database_backups'
}

execute_database_transfer
sleep 2
execute_database_transfer
