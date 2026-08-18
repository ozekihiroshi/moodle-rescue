#!/bin/sh
set -eu

repositoryroot="$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

for commandname in docker openssl grep sed sort tail mktemp id; do
    command -v "$commandname" >/dev/null 2>&1 || {
        echo "Required DTL restore-test command is unavailable: $commandname" >&2
        exit 1
    }
done

sourcevolume="${DATABASE_ARTIFACT_VOLUME:?DATABASE_ARTIFACT_VOLUME is required}"
moodlecontainer="${MOODLE_DTL_RESTORE_CONTAINER:?MOODLE_DTL_RESTORE_CONTAINER is required}"
moodleimage="${MOODLE_RESTORE_TEST_IMAGE:?MOODLE_RESTORE_TEST_IMAGE is required}"

docker volume inspect "$sourcevolume" >/dev/null
docker container inspect "$moodlecontainer" >/dev/null
docker image inspect "$moodleimage" >/dev/null

runid="$(openssl rand -hex 8)"
case "$runid" in
    [0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f]) ;;
    *) echo 'Unable to generate a safe DTL restore-test identifier.' >&2; exit 1 ;;
esac

networkname="moodle_dtl_restore_$runid"
dbvolume="moodle_dtl_db_$runid"
moodledata="moodle_dtl_data_$runid"
dbcontainer="moodle-dtl-db-$runid"
restoredatabase="moodle_dtl_restore_$runid"
restoreuser="moodle_dtl_restore_$runid"
restorepassword="$(openssl rand -hex 24)"
rootpassword="$(openssl rand -hex 24)"
hosttemporary="$(mktemp -d)"
containerdirectory="/var/moodledata/temp/secure-s3-dtl-$runid"

cleanup() {
    status=$?
    trap - EXIT HUP INT TERM
    if [ "$status" -ne 0 ]; then
        echo 'DTL database restore test failed; collecting isolated database logs.' >&2
        docker logs "$dbcontainer" >&2 || true
    fi
    docker exec "$moodlecontainer" sh -c \
        "find '$containerdirectory' -maxdepth 1 -type f -delete 2>/dev/null || true; rmdir '$containerdirectory' 2>/dev/null || true" \
        >/dev/null 2>&1 || true
    docker network disconnect "$networkname" "$moodlecontainer" >/dev/null 2>&1 || true
    docker rm -f "$dbcontainer" >/dev/null 2>&1 || true
    docker volume rm "$dbvolume" "$moodledata" >/dev/null 2>&1 || true
    docker network rm "$networkname" >/dev/null 2>&1 || true
    find "$hosttemporary" -maxdepth 1 -type f -delete 2>/dev/null || true
    rmdir "$hosttemporary" 2>/dev/null || true
    exit "$status"
}
trap cleanup EXIT HUP INT TERM

manifestpath="$(docker run --rm --volume "$sourcevolume:/source:ro" alpine:3.22 \
    sh -c 'find /source -maxdepth 1 -type f -name "moodle-db-*.xml.gz.manifest.json" | sort | tail -n 1')"
manifestname="${manifestpath##*/}"
case "$manifestname" in
    moodle-db-????????T??????Z-????????????????????????????????.xml.gz.manifest.json) ;;
    *) echo "No valid downloaded v2 manifest was found: $manifestname" >&2; exit 1 ;;
esac
payloadname="${manifestname%.manifest.json}"

docker run --rm --volume "$sourcevolume:/source:ro" --volume "$hosttemporary:/out" alpine:3.22 \
    sh -c "
        cp '/source/$manifestname' '/out/$manifestname'
        cp '/source/$payloadname' '/out/$payloadname'
        chown '$(id -u):$(id -g)' '/out/$manifestname' '/out/$payloadname'
        chmod 0600 '/out/$manifestname' '/out/$payloadname'
    "

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
    --health-interval 2s --health-timeout 5s --health-retries 60 \
    mariadb:11.8 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci >/dev/null

attempt=0
until [ "$(docker inspect --format '{{.State.Health.Status}}' "$dbcontainer")" = healthy ]; do
    attempt=$((attempt + 1))
    [ "$attempt" -lt 90 ] || { echo 'Timed out waiting for isolated DTL database.' >&2; exit 1; }
    sleep 2
done

docker network connect "$networkname" "$moodlecontainer"
docker exec "$moodlecontainer" install -d -m 0700 -o www-data -g www-data "$containerdirectory"
docker cp "$hosttemporary/$manifestname" "$moodlecontainer:$containerdirectory/$manifestname"
docker cp "$hosttemporary/$payloadname" "$moodlecontainer:$containerdirectory/$payloadname"
docker exec "$moodlecontainer" chown www-data:www-data \
    "$containerdirectory/$manifestname" "$containerdirectory/$payloadname"
docker exec "$moodlecontainer" chmod 0600 \
    "$containerdirectory/$manifestname" "$containerdirectory/$payloadname"

echo 'Restoring downloaded v2 DTL artifact into an isolated empty database.'
docker exec \
    --env SECURE_S3_RESTORE_DBHOST=restore-db \
    --env SECURE_S3_RESTORE_DBPORT=3306 \
    --env "SECURE_S3_RESTORE_DBNAME=$restoredatabase" \
    --env "SECURE_S3_RESTORE_DBUSER=$restoreuser" \
    --env "SECURE_S3_RESTORE_DBPASSWORD=$restorepassword" \
    --env SECURE_S3_RESTORE_DBTYPE=mariadb \
    --env SECURE_S3_RESTORE_DBPREFIX=mdl_ \
    "$moodlecontainer" runuser -u www-data -- php \
    /var/www/html/public/admin/tool/secure_s3_storage/cli/restore_database.php \
    --manifest="$containerdirectory/$manifestname"

restoredversion="$(docker run --rm \
    --network "$networkname" \
    --env MOODLE_DB_HOST=restore-db \
    --env "MOODLE_DB_NAME=$restoredatabase" \
    --env "MOODLE_DB_USER=$restoreuser" \
    --env "MOODLE_DB_PASSWORD=$restorepassword" \
    --env MOODLE_WWWROOT=http://restore.invalid \
    --env MOODLE_REVERSE_PROXY=false \
    --env MOODLE_SSL_PROXY=false \
    --volume "$moodledata:/var/moodledata" \
    "$moodleimage" runuser -u www-data -- php admin/cli/cfg.php --name=version)"
if ! printf '%s\n' "$restoredversion" | grep -Eq '^[0-9]+([.][0-9]+)*$'; then
    echo "Fresh Moodle could not read the DTL-restored version: $restoredversion" >&2
    exit 1
fi

printf '{"databaseDtlRestoreGate":true,"moodleVersion":"%s","manifest":"%s"}\n' \
    "$restoredversion" "$manifestname"
