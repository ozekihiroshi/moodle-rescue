#!/bin/sh

set -eu

repositoryroot="$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

for commandname in docker openssl tr; do
    if ! command -v "$commandname" >/dev/null 2>&1; then
        echo "Required S3 round-trip command is unavailable: $commandname" >&2
        exit 1
    fi
done

envfile="${DATABASE_TRANSFER_ENV_FILE:-.env}"
composefile="${DATABASE_TRANSFER_COMPOSE_FILE:-docker-compose.local.yml}"
networkname="${DATABASE_TRANSFER_NETWORK:-moodle-rescue-local_internal}"

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

bucket="$(local_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage --name=bucket | tr -d '\r\n')"
prefix="$(local_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage --name=prefix | tr -d '\r\n')"
if [ -z "$bucket" ] || [ -z "$prefix" ]; then
    echo "Plugin bucket and prefix must be configured before the S3 round-trip test." >&2
    exit 1
fi

sh scripts/run-database-transfer-test.sh

runid="$(openssl rand -hex 8 | tr -cd '0-9a-f')"
downloadvolume="moodle_db_s3_roundtrip_$runid"
case "$downloadvolume" in
    moodle_db_s3_roundtrip_[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f]) ;;
    *) echo "Unable to generate a safe S3 round-trip volume name." >&2; exit 1 ;;
esac

cleanup() {
    status=$?
    trap - EXIT HUP INT TERM
    docker volume rm "$downloadvolume" >/dev/null 2>&1 || true
    exit "$status"
}
trap cleanup EXIT HUP INT TERM

docker volume create "$downloadvolume" >/dev/null

docker run --rm \
    --network "$networkname" \
    --env-file "$envfile" \
    --env S3_ENDPOINT=http://minio:9000 \
    --env "S3_BUCKET=$bucket" \
    --env "S3_PREFIX=$prefix" \
    --env DATABASE_ARTIFACT_DESTINATION=/database-artifacts \
    --volume "$downloadvolume:/database-artifacts" \
    --volume "$repositoryroot/moodle/database-artifact-fetch.sh:/usr/local/bin/database-artifact-fetch:ro" \
    --entrypoint /bin/sh \
    quay.io/minio/minio:RELEASE.2025-09-07T16-13-09Z \
    /usr/local/bin/database-artifact-fetch

DATABASE_ARTIFACT_VOLUME="$downloadvolume" \
DATABASE_TRANSFER_ENV_FILE="$envfile" \
MOODLE_RESTORE_COMPOSE_FILE="$composefile" \
MOODLE_RESTORE_ENV_FILE="$envfile" \
    sh scripts/run-database-restore-test.sh
