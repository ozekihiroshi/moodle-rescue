#!/bin/sh
set -eu

repositoryroot="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

if [ ! -f .env ]; then
    echo "Production environment file is unavailable: $repositoryroot/.env" >&2
    echo "Copy .env.production.example to .env and replace every CHANGE_ME value." >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is unavailable." >&2
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose v2 is unavailable." >&2
    exit 1
fi

sh scripts/sync-plugins.sh

docker compose --env-file .env config --quiet
docker compose --env-file .env build --pull moodle
docker compose --env-file .env up -d --no-build --force-recreate \
    moodle moodle-cron

if docker compose --env-file .env exec -T moodle \
        runuser -u www-data -- php admin/cli/cfg.php --name=version \
        >/dev/null 2>&1; then
    docker compose --env-file .env exec -T moodle \
        runuser -u www-data -- php admin/cli/upgrade.php --non-interactive
    sh scripts/configure-moodle-backup-storage.sh .env
else
    echo "Moodle has a fresh database."
    echo "Complete the normal web installation, then run this deploy script again."
fi
