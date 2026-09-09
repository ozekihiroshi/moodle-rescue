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

# Do not silently replace UI-managed code with the immutable image seed.
# The volume check also protects a stopped managed deployment (base project name).
managedvolumes=$(docker volume ls -q \
    --filter label=com.docker.compose.project=moodle-rescue \
    --filter label=com.docker.compose.volume=managed_modules)
if [ -n "$managedvolumes" ] || docker compose --env-file .env exec -T moodle \
        test -f /var/www/html/public/mod/.moodle-core-version.sha256 2>/dev/null; then
    echo "UI-managed modules detected. Follow docs/managed-modules.md; immutable deploy refused." >&2
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
