#!/bin/sh
set -eu

repositoryroot="$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

for commandname in docker openssl grep mktemp find; do
    command -v "$commandname" >/dev/null 2>&1 || {
        echo "Required AWS recovery-test command is unavailable: $commandname" >&2
        exit 1
    }
done

recoverysetid="${1:-}"
envfile="${2:-.env}"
case "$recoverysetid" in
    [0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]T[0-9][0-9][0-9][0-9][0-9][0-9]Z-[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f]) ;;
    *)
        echo 'Usage: sh scripts/run-aws-recovery-test.sh YYYYMMDDTHHMMSSZ-<32 lowercase hex> [.env]' >&2
        exit 1
        ;;
esac
if [ ! -f "$envfile" ]; then
    echo "Environment file is unavailable: $envfile" >&2
    exit 1
fi

compose() {
    docker compose --env-file "$envfile" "$@"
}

compose config --quiet
moodlecontainer="$(compose ps -q moodle)"
croncontainer="$(compose ps -q moodle-cron)"
if [ -z "$moodlecontainer" ] || [ -z "$croncontainer" ]; then
    echo 'The production Moodle and Cron containers must already be running.' >&2
    exit 1
fi

moodleimage="$(docker inspect --format '{{.Image}}' "$moodlecontainer")"
awsnetwork="$(docker inspect --format '{{range $name, $details := .NetworkSettings.Networks}}{{println $name}}{{end}}' \
    "$croncontainer" | grep '_aws_access$' || true)"
awsnetworkcount="$(printf '%s\n' "$awsnetwork" | grep -c . || true)"
[ "$awsnetworkcount" -eq 1 ] || {
    echo 'Unable to identify exactly one Cron AWS-access network.' >&2
    exit 1
}
docker network inspect "$awsnetwork" >/dev/null
docker image inspect "$moodleimage" >/dev/null

read_plugin_setting() {
    compose exec -T moodle runuser -u www-data -- php admin/cli/cfg.php \
        --component=tool_secure_s3_storage --name="$1" | tr -d '\r\n'
}

region="$(read_plugin_setting region)"
bucket="$(read_plugin_setting bucket)"
prefix="$(read_plugin_setting prefix)"
case "$region" in ''|*[!a-z0-9-]*) echo 'Configured AWS region is invalid.' >&2; exit 1 ;; esac
case "$bucket" in ''|*[!a-z0-9.-]*) echo 'Configured S3 bucket is invalid.' >&2; exit 1 ;; esac
case "$prefix" in ''|/*|*..*|*//*|*\\*) echo 'Configured S3 prefix is invalid.' >&2; exit 1 ;; esac

runid="$(openssl rand -hex 8)"
case "$runid" in
    [0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f]) ;;
    *) echo 'Unable to generate a safe AWS recovery-test identifier.' >&2; exit 1 ;;
esac

hosttemporary="$(mktemp -d)"
chmod 0700 "$hosttemporary"
mkdir -m 0750 "$hosttemporary/database" "$hosttemporary/content"
databasevolume="moodle_aws_recovery_database_$runid"
contentvolume="moodle_aws_recovery_content_$runid"
helpervolume="moodle_aws_recovery_helper_$runid"
helpercontainer="moodle-aws-recovery-helper-$runid"
bootstrapnetwork="moodle_aws_recovery_bootstrap_$runid"

cleanup() {
    status=$?
    trap - EXIT HUP INT TERM
    if [ "$status" -ne 0 ]; then
        echo 'AWS isolated recovery rehearsal failed; production data was not modified.' >&2
        docker logs "$helpercontainer" >&2 2>/dev/null || true
    fi
    docker rm -f "$helpercontainer" >/dev/null 2>&1 || true
    docker network rm "$bootstrapnetwork" >/dev/null 2>&1 || true
    docker volume rm "$databasevolume" "$contentvolume" "$helpervolume" >/dev/null 2>&1 || true
    find "$hosttemporary" -depth -mindepth 1 -delete 2>/dev/null || true
    rmdir "$hosttemporary" 2>/dev/null || true
    exit "$status"
}
trap cleanup EXIT HUP INT TERM

docker volume create "$databasevolume" >/dev/null
docker volume create "$contentvolume" >/dev/null
docker volume create "$helpervolume" >/dev/null
docker network create --internal "$bootstrapnetwork" >/dev/null

echo "Downloading and verifying AWS recovery set $recoverysetid."
docker run --rm \
    --network "$awsnetwork" \
    --read-only \
    --tmpfs /tmp:rw,noexec,nosuid,nodev,mode=1777 \
    --security-opt no-new-privileges:true \
    --cap-drop ALL \
    --user "$(id -u):$(id -g)" \
    --env "AWS_REGION=$region" \
    --env AWS_EC2_METADATA_DISABLED=false \
    --env "S3_BUCKET=$bucket" \
    --env "S3_PREFIX=$prefix" \
    --env "AWS_RECOVERY_SET_ID=$recoverysetid" \
    --mount "type=bind,src=$repositoryroot/moodle/aws-recovery-fetch.php,dst=/usr/local/bin/aws-recovery-fetch.php,readonly" \
    --mount "type=bind,src=$hosttemporary/database,dst=/database-artifacts" \
    --mount "type=bind,src=$hosttemporary/content,dst=/content-recovery" \
    --entrypoint php \
    "$moodleimage" /usr/local/bin/aws-recovery-fetch.php

docker run --rm \
    --mount "type=bind,src=$hosttemporary/database,dst=/source,readonly" \
    --volume "$databasevolume:/target" \
    alpine:3.22 sh -eu -c 'cp -a /source/. /target/; chown -R 0:33 /target; chmod 0750 /target; find /target -type d -exec chmod 0750 {} +; find /target -type f -exec chmod 0640 {} +'
docker run --rm \
    --mount "type=bind,src=$hosttemporary/content,dst=/source,readonly" \
    --volume "$contentvolume:/target" \
    alpine:3.22 sh -eu -c 'cp -a /source/. /target/; chown -R 0:33 /target; chmod 0750 /target; find /target -type d -exec chmod 0750 {} +; find /target -type f -exec chmod 0640 {} +'

docker run -d \
    --name "$helpercontainer" \
    --network "$bootstrapnetwork" \
    --env MOODLE_DB_HOST=restore-db \
    --env MOODLE_DB_NAME=unused_restore_database \
    --env MOODLE_DB_USER=unused_restore_user \
    --env MOODLE_DB_PASSWORD=unused_restore_password \
    --env MOODLE_WWWROOT=http://restore.invalid \
    --env MOODLE_REVERSE_PROXY=false \
    --env MOODLE_SSL_PROXY=false \
    --volume "$helpervolume:/var/moodledata" \
    "$moodleimage" tail -f /dev/null >/dev/null

echo 'Starting the disposable database and content restore gate.'
DATABASE_ARTIFACT_VOLUME="$databasevolume" \
CONTENT_RECOVERY_VOLUME="$contentvolume" \
CONTENT_VERIFICATION_MODE=generic \
MOODLE_DTL_RESTORE_CONTAINER="$helpercontainer" \
MOODLE_RESTORE_TEST_IMAGE="$moodleimage" \
    sh scripts/run-dtl-database-restore-test.sh

echo "AWS recovery set $recoverysetid passed the isolated restore rehearsal."
