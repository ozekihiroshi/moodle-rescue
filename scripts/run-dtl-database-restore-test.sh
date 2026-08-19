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
contentsource="${CONTENT_RECOVERY_VOLUME:-}"
contentmarker="${S3_TEST_FILE_MARKER:-secure-s3-content-recovery-marker-v1}"

docker volume inspect "$sourcevolume" >/dev/null
if [ -n "$contentsource" ]; then
    docker volume inspect "$contentsource" >/dev/null
fi
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
contentdata="moodle_dtl_content_$runid"
contentcorrupt="moodle_dtl_content_corrupt_$runid"
contentnegative="moodle_dtl_content_negative_$runid"
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
    if [ -n "$contentsource" ]; then
        docker volume rm "$contentdata" "$contentcorrupt" "$contentnegative" >/dev/null 2>&1 || true
    fi
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
if [ -n "$contentsource" ]; then
    docker volume create "$contentdata" >/dev/null
    docker volume create "$contentcorrupt" >/dev/null
    docker volume create "$contentnegative" >/dev/null
    docker run --rm --volume "$contentdata:/target" alpine:3.22 chown 33:33 /target
    docker run --rm --volume "$contentnegative:/target" alpine:3.22 chown 33:33 /target
    docker run --rm \
        --volume "$contentsource:/source:ro" \
        --volume "$contentcorrupt:/content-recovery" \
        alpine:3.22 cp -a /source/. /content-recovery/
    docker run --rm \
        --volume "$contentcorrupt:/content-recovery" \
        --volume "$repositoryroot/moodle/content-corrupt-download.sh:/usr/local/bin/content-corrupt-download:ro" \
        alpine:3.22 sh /usr/local/bin/content-corrupt-download
fi
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

verificationdata="$moodledata"
contentverified=false
if [ -n "$contentsource" ]; then
    contentmanifest="$(docker run --rm --volume "$contentsource:/source:ro" alpine:3.22 \
        sh -c 'find /source -maxdepth 1 -type f -name "moodle-content-*.jsonl.gz.manifest.json" | sort | tail -n 1')"
    contentmanifestname="${contentmanifest##*/}"
    case "$contentmanifestname" in
        moodle-content-????????T??????Z-????????????????????????????????.jsonl.gz.manifest.json) ;;
        *) echo "No valid downloaded content manifest was found: $contentmanifestname" >&2; exit 1 ;;
    esac
    contentrecoveryset="${contentmanifestname#moodle-content-}"
    contentrecoveryset="${contentrecoveryset%.jsonl.gz.manifest.json}"
    databaserecoveryset="$(sed -n 's/.*"recoverysetid":"\([^"]*\)".*/\1/p' \
        "$hosttemporary/$manifestname")"
    if [ "$contentrecoveryset" != "$databaserecoveryset" ]; then
        echo "Database and content recovery-set identifiers do not match." >&2
        exit 1
    fi

    echo 'Proving that a checksum-corrupt content inventory is rejected.'
    if docker run --rm \
        --network "$networkname" \
        --env MOODLE_DB_HOST=restore-db \
        --env "MOODLE_DB_NAME=$restoredatabase" \
        --env "MOODLE_DB_USER=$restoreuser" \
        --env "MOODLE_DB_PASSWORD=$restorepassword" \
        --env MOODLE_WWWROOT=http://restore.invalid \
        --env MOODLE_REVERSE_PROXY=false \
        --env MOODLE_SSL_PROXY=false \
        --volume "$moodledata:/var/moodledata" \
        --volume "$sourcevolume:/database-source:ro" \
        --volume "$contentcorrupt:/content-source:ro" \
        --volume "$contentnegative:/content-target" \
        "$moodleimage" runuser -u www-data -- php \
        /var/www/html/public/admin/tool/secure_s3_storage/cli/restore_content.php \
        --manifest="/content-source/$contentmanifestname" \
        --database-manifest="/database-source/$manifestname" \
        --objects=/content-source/objects \
        --target=/content-target/filedir
    then
        echo 'Checksum-corrupt content inventory was unexpectedly accepted.' >&2
        exit 1
    fi
    echo 'Checksum-corrupt content inventory was rejected.'

    echo 'Restoring the matched content inventory into an isolated filedir.'
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
        --volume "$sourcevolume:/database-source:ro" \
        --volume "$contentsource:/content-source:ro" \
        --volume "$contentdata:/content-target" \
        "$moodleimage" runuser -u www-data -- php \
        /var/www/html/public/admin/tool/secure_s3_storage/cli/restore_content.php \
        --manifest="/content-source/$contentmanifestname" \
        --database-manifest="/database-source/$manifestname" \
        --objects=/content-source/objects \
        --target=/content-target/filedir
    verificationdata="$contentdata"
fi

restoredversion="$(docker run --rm \
    --network "$networkname" \
    --env MOODLE_DB_HOST=restore-db \
    --env "MOODLE_DB_NAME=$restoredatabase" \
    --env "MOODLE_DB_USER=$restoreuser" \
    --env "MOODLE_DB_PASSWORD=$restorepassword" \
    --env MOODLE_WWWROOT=http://restore.invalid \
    --env MOODLE_REVERSE_PROXY=false \
    --env MOODLE_SSL_PROXY=false \
    --volume "$verificationdata:/var/moodledata" \
    "$moodleimage" runuser -u www-data -- php admin/cli/cfg.php --name=version)"
if ! printf '%s\n' "$restoredversion" | grep -Eq '^[0-9]+([.][0-9]+)*$'; then
    echo "Fresh Moodle could not read the DTL-restored version: $restoredversion" >&2
    exit 1
fi

if [ -n "$contentsource" ]; then
    docker run --rm --interactive \
        --network "$networkname" \
        --env MOODLE_DB_HOST=restore-db \
        --env "MOODLE_DB_NAME=$restoredatabase" \
        --env "MOODLE_DB_USER=$restoreuser" \
        --env "MOODLE_DB_PASSWORD=$restorepassword" \
        --env MOODLE_WWWROOT=http://restore.invalid \
        --env MOODLE_REVERSE_PROXY=false \
        --env MOODLE_SSL_PROXY=false \
        --env "S3_TEST_FILE_MARKER=$contentmarker" \
        --volume "$verificationdata:/var/moodledata" \
        "$moodleimage" runuser -u www-data -- php \
        < scripts/verify-content-recovery-fixture.php
    contentverified=true
fi

printf '{"databaseDtlRestoreGate":true,"contentFileApiRestoreGate":%s,"moodleVersion":"%s","manifest":"%s"}\n' \
    "$contentverified" "$restoredversion" "$manifestname"
