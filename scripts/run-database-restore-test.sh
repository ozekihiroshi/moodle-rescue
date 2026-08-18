#!/bin/sh

set -eu

repositoryroot="$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

for commandname in docker openssl grep tr tail; do
    if ! command -v "$commandname" >/dev/null 2>&1; then
        echo "Required database restore-test command is unavailable: $commandname" >&2
        exit 1
    fi
done

sourcevolume="${DATABASE_ARTIFACT_VOLUME:-moodle-rescue-local_moodle_database_artifacts}"
composefile="${MOODLE_RESTORE_COMPOSE_FILE:-docker-compose.local.yml}"
envfile="${MOODLE_RESTORE_ENV_FILE:-.env}"
moodleimage="${MOODLE_RESTORE_TEST_IMAGE:-}"

if ! docker volume inspect "$sourcevolume" >/dev/null 2>&1; then
    echo "Database artifact volume does not exist: $sourcevolume" >&2
    exit 1
fi

if [ -z "$moodleimage" ]; then
    if [ ! -f "$envfile" ]; then
        echo "Moodle environment file does not exist: $envfile" >&2
        echo "Set MOODLE_RESTORE_TEST_IMAGE to use an explicit built image." >&2
        exit 1
    fi
    moodleimage="$(docker compose --env-file "$envfile" -f "$composefile" images -q moodle | tail -n 1)"
fi

if [ -z "$moodleimage" ] || ! docker image inspect "$moodleimage" >/dev/null 2>&1; then
    echo "A built Moodle image is required for the restored-database read test." >&2
    exit 1
fi

runid="$(openssl rand -hex 8 | tr -cd '0-9a-f')"
networkname="moodle_db_restore_$runid"
dbvolume="moodle_db_restore_$runid"
moodledata="moodle_data_restore_$runid"
dbcontainer="moodle-db-restore-$runid"
restoredatabase="moodle_restore_$runid"
restoreuser="moodle_restore_$runid"
restorepassword="$(openssl rand -hex 24)"
rootpassword="$(openssl rand -hex 24)"

case "$runid" in
    [0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f]) ;;
    *) echo "Unable to generate a safe restore-test identifier." >&2; exit 1 ;;
esac

cleanup() {
    status=$?
    trap - EXIT HUP INT TERM

    if [ "$status" -ne 0 ]; then
        echo "Database restore test failed; collecting isolated database logs." >&2
        docker logs "$dbcontainer" >&2 || true
    fi

    docker rm -f "$dbcontainer" >/dev/null 2>&1 || true
    docker volume rm "$dbvolume" "$moodledata" >/dev/null 2>&1 || true
    docker network rm "$networkname" >/dev/null 2>&1 || true
    exit "$status"
}
trap cleanup EXIT HUP INT TERM

docker network create "$networkname" >/dev/null
docker volume create "$dbvolume" >/dev/null
docker volume create "$moodledata" >/dev/null

docker run -d \
    --name "$dbcontainer" \
    --network "$networkname" \
    --network-alias restore-db \
    --env "MARIADB_DATABASE=$restoredatabase" \
    --env "MARIADB_USER=$restoreuser" \
    --env "MARIADB_PASSWORD=$restorepassword" \
    --env "MARIADB_ROOT_PASSWORD=$rootpassword" \
    --volume "$dbvolume:/var/lib/mysql" \
    --health-cmd 'healthcheck.sh --connect --innodb_initialized' \
    --health-interval 2s \
    --health-timeout 5s \
    --health-retries 60 \
    mariadb:11.8 \
    --character-set-server=utf8mb4 \
    --collation-server=utf8mb4_unicode_ci >/dev/null

attempt=0
while :; do
    health="$(docker inspect --format '{{.State.Health.Status}}' "$dbcontainer")"
    if [ "$health" = "healthy" ]; then
        break
    fi
    if [ "$health" = "unhealthy" ]; then
        echo "Isolated restore database became unhealthy." >&2
        exit 1
    fi
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 90 ]; then
        echo "Timed out waiting for the isolated restore database." >&2
        exit 1
    fi
    sleep 2
done

echo "Restoring the latest verified database artifact into an isolated database."
docker run --rm \
    --network "$networkname" \
    --read-only \
    --tmpfs /tmp:rw,noexec,nosuid,size=16m \
    --security-opt no-new-privileges:true \
    --cap-drop ALL \
    --env RESTORE_DB_HOST=restore-db \
    --env RESTORE_DB_PORT=3306 \
    --env "RESTORE_DB_NAME=$restoredatabase" \
    --env "RESTORE_DB_USER=$restoreuser" \
    --env "RESTORE_DB_PASSWORD=$restorepassword" \
    --volume "$sourcevolume:/database-artifacts:ro" \
    --volume "$repositoryroot/moodle/database-restore-test.sh:/usr/local/bin/moodle-database-restore-test:ro" \
    mariadb:11.8 \
    sh /usr/local/bin/moodle-database-restore-test

echo "Reading the restored database through a fresh Moodle container."
restoredversion="$(
    docker run --rm \
        --network "$networkname" \
        --env MOODLE_DB_HOST=restore-db \
        --env "MOODLE_DB_NAME=$restoredatabase" \
        --env "MOODLE_DB_USER=$restoreuser" \
        --env "MOODLE_DB_PASSWORD=$restorepassword" \
        --env MOODLE_WWWROOT=http://restore.invalid \
        --env MOODLE_REVERSE_PROXY=false \
        --env MOODLE_SSL_PROXY=false \
        --volume "$moodledata:/var/moodledata" \
        "$moodleimage" \
        runuser -u www-data -- php admin/cli/cfg.php --name=version
)"

if ! printf '%s\n' "$restoredversion" | grep -Eq '^[0-9]+([.][0-9]+)*$'; then
    echo "Fresh Moodle could not read a valid version from the restored database: $restoredversion" >&2
    exit 1
fi

printf '{"databaseRestoreGate":true,"moodleVersion":"%s","sourceVolume":"%s"}\n' \
    "$restoredversion" "$sourcevolume"
