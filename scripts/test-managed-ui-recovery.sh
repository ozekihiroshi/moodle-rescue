#!/bin/sh
# Full snapshot/recovery gate for localhost:8096 only. Never targets AWS.
set -eu
umask 077
root=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
work="$root/build/managed-ui-lab"
web=lessonmark-managed-ui
cron=lessonmark-managed-ui-cron
db=lessonmark-managed-ui-db
stamp="$(date -u +%Y%m%dT%H%M%SZ)-$$"
destination="$work/recovery-$stamp"
saved=${1:-}
if [ -n "$saved" ]; then
    case "$saved" in
        recovery-*[!0-9TZ-]*|*[!a-zA-Z0-9-]*|*/*) echo 'Invalid recovery directory name.' >&2; exit 2 ;;
        recovery-*) destination="$work/$saved" ;;
        *) echo 'Usage: test-managed-ui-recovery.sh [recovery-directory-name]' >&2; exit 2 ;;
    esac
fi
recovery="lessonmark-managed-recovery-$stamp"
stage=preflight
resume=0
resume_original() {
    docker start "$web" >/dev/null || return 1
    # docker start returns before entrypoint installs config.php and sets its
    # ownership. PID 1 becomes Apache only AFTER that entrypoint completes.
    readyattempt=0
    until docker exec "$web" sh -c 'test "$(cat /proc/1/comm)" = apache2 && runuser -u www-data -- test -r /var/www/html/config.php' >/dev/null 2>&1; do
        readyattempt=$((readyattempt + 1))
        if [ "$readyattempt" -ge 30 ]; then
            echo 'Timed out waiting for Apache and readable config.php.' >&2
            return 1
        fi
        sleep 1
    done
    docker exec -u www-data "$web" php /var/www/html/admin/cli/maintenance.php --disable || return 1
    docker start "$cron" >/dev/null || return 1
}
finish() {
    result=$?
    if [ "$resume" = 1 ]; then
        echo 'Resuming only the original 8096 lab after snapshot...'
        if resume_original; then
            :
        else
            echo '8096 needs attention; no data was deleted. Inspect Web/maintenance state.' >&2
            result=1
        fi
    fi
    if [ "$result" -ne 0 ]; then
        printf 'FAIL: stage=%s exit=%s. Backups/recovery resources retained for inspection.\n' "$stage" "$result" >&2
    fi
    trap - EXIT
    exit "$result"
}
trap finish EXIT
test -s "$work/runtime.env"
test -s "$work/before.json"
for container in "$web" "$cron" "$db"; do
    test "$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$container")" = lessonmark-managed-ui
    test "$(docker inspect --format '{{.State.Running}}' "$container")" = true
done
test "$(docker inspect --format '{{.Image}}' "$web")" = "$(docker inspect --format '{{.Image}}' "$cron")"
if [ "$(docker exec -u www-data "$web" php /var/www/html/admin/cli/cfg.php --name=maintenance_enabled 2>/dev/null || true)" = 1 ]; then
    echo '8096 is already in maintenance; refusing to change its existing state.' >&2; exit 1
fi
# All credentials remain in ignored, private artifacts; never echo them.
. "$work/runtime.env"
image=$(docker inspect --format '{{.Image}}' "$web")
dbimage=$(docker inspect --format '{{.Image}}' "$db")
if [ -z "$saved" ]; then
mkdir "$destination"
printf '%s\n' "$image" > "$destination/image-id.txt"
printf '%s\n' "$dbimage" > "$destination/db-image-id.txt"
cp "$work/runtime.env" "$destination/runtime.env"
cp "$root/docker-compose.managed-lab.yml" "$root/docker-compose.managed-modules.yml" "$destination/"
cp "$root/moodle/managed-modules.sh" "$destination/"
cp "$root/scripts/managed-ui-fixture.php" "$destination/"
git -C "$root" rev-parse HEAD > "$destination/repository-head.txt"
stage=quiesce-lab
docker stop "$cron" >/dev/null
resume=1
docker exec -u www-data "$web" php /var/www/html/admin/cli/maintenance.php --enable
docker cp "$root/scripts/managed-ui-fixture.php" "$web:/tmp/managed-ui-fixture.php" >/dev/null
docker exec -u www-data "$web" php /tmp/managed-ui-fixture.php check > "$destination/before.json"
cmp "$work/before.json" "$destination/before.json"
docker stop "$web" >/dev/null
stage=snapshot
docker exec "$db" sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-dump -uroot --single-transaction --routines --events --triggers --databases "$MARIADB_DATABASE"' > "$destination/database.sql"
# docker cp works on stopped containers, preserving their writable layer too.
docker cp "$web:/var/www/html/." - > "$destination/application.tar"
gzip "$destination/application.tar"
docker run --rm --network none --entrypoint tar --volumes-from "$web:ro" \
    "$image" -czf - -C /var moodledata moodlebackups > "$destination/data.tgz"
docker run --rm --network none --entrypoint tar --volumes-from "$web:ro" \
    "$image" -czf - -C /var/www/html/public/mod . > "$destination/managed-modules.tgz"
docker run --rm --network none --entrypoint sh --volumes-from "$web:ro" "$image" \
    -c 'cd /var/www/html/public/mod && find . -type f -print0 | sort -z | xargs -0 sha256sum' > "$destination/source-code-files.sha256"
test -s "$destination/database.sql"
tar -tzf "$destination/application.tar.gz" >/dev/null
tar -tzf "$destination/data.tgz" >/dev/null
tar -tzf "$destination/managed-modules.tgz" >/dev/null
(cd "$destination" && sha256sum database.sql application.tar.gz data.tgz managed-modules.tgz \
    runtime.env image-id.txt db-image-id.txt docker-compose.managed-lab.yml \
    docker-compose.managed-modules.yml managed-modules.sh managed-ui-fixture.php \
    repository-head.txt before.json source-code-files.sha256 > SHA256SUMS)
stage=resume-original-lab
resume_original
resume=0
echo "Consistent snapshot created: $destination"
else
    stage=validate-saved-snapshot
    test -s "$destination/SHA256SUMS"
    (cd "$destination" && sha256sum -c SHA256SUMS >/dev/null)
    image=$(cat "$destination/image-id.txt")
    dbimage=$(cat "$destination/db-image-id.txt")
    test "$(docker image inspect --format '{{.Id}}' "$image")" = "$image"
    test "$(docker image inspect --format '{{.Id}}' "$dbimage")" = "$dbimage"
    . "$destination/runtime.env"
    echo "Resuming restoration from verified snapshot: $destination"
    echo 'Original 8096 is not stopped or modified in this resume mode.'
fi
stage=restore-isolated-copy
(cd "$destination" && sha256sum -c SHA256SUMS >/dev/null)
# Explicit local subnet avoids exhausted automatic Docker pools. Docker refuses
# overlap rather than changing or deleting existing networks.
docker network create --internal --subnet "${MANAGED_RECOVERY_SUBNET:-10.203.98.0/24}" \
    --label test.owner=lessonmark-managed-recovery "$recovery" >/dev/null
for suffix in db data backups modules; do
    docker volume create --label test.owner=lessonmark-managed-recovery "$recovery-$suffix" >/dev/null
done
docker run -d --name "$recovery-db" --label test.owner=lessonmark-managed-recovery \
    --network "$recovery" \
    -e MARIADB_ROOT_PASSWORD="$MANAGED_LAB_ROOT_PASSWORD" \
    -e MARIADB_DATABASE=managed_ui -e MARIADB_USER=managed_ui \
    -e MARIADB_PASSWORD="$MANAGED_LAB_DB_PASSWORD" \
    --mount "type=volume,src=$recovery-db,dst=/var/lib/mysql" "$dbimage" >/dev/null
attempt=0
until docker exec "$recovery-db" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; do
    attempt=$((attempt + 1)); test "$attempt" -lt 120; sleep 1
done
docker exec -i "$recovery-db" sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -uroot' < "$destination/database.sql"
docker run -d --name "$recovery-web" --label test.owner=lessonmark-managed-recovery \
    --network "$recovery" --entrypoint sh \
    -e MOODLE_DB_HOST="$recovery-db" -e MOODLE_DB_NAME=managed_ui \
    -e MOODLE_DB_USER=managed_ui -e MOODLE_DB_PASSWORD="$MANAGED_LAB_DB_PASSWORD" \
    -e MOODLE_WWWROOT=http://localhost:8096 -e MOODLE_REVERSE_PROXY=false -e MOODLE_SSL_PROXY=false \
    --mount "type=volume,src=$recovery-data,dst=/var/moodledata,volume-nocopy" \
    --mount "type=volume,src=$recovery-backups,dst=/var/moodlebackups,volume-nocopy" \
    --mount "type=volume,src=$recovery-modules,dst=/var/www/html/public/mod,volume-nocopy" \
    "$image" -c 'exec sleep infinity' >/dev/null
docker exec -i "$recovery-web" tar -xzf - -C /var/www/html < "$destination/application.tar.gz"
docker exec -i "$recovery-web" tar -xzf - -C /var < "$destination/data.tgz"
docker exec -i "$recovery-web" tar -xzf - -C /var/www/html/public/mod < "$destination/managed-modules.tgz"
docker cp "$destination/managed-modules.sh" "$recovery-web:/tmp/managed-modules.sh" >/dev/null
docker exec "$recovery-web" sh /tmp/managed-modules.sh check
test "$(docker exec -u www-data "$recovery-web" php /var/www/html/admin/cli/cfg.php --component=mod_lessonmark --name=version)" = 2026090802
code=$(docker exec "$recovery-web" cat /var/www/html/public/mod/lessonmark/version.php)
test "$(printf '%s\n' "$code" | sh "$root/scripts/read-lessonmark-build.sh")" = 2026090802
docker cp "$destination/managed-ui-fixture.php" "$recovery-web:/tmp/managed-ui-fixture.php" >/dev/null
docker exec -u www-data "$recovery-web" php /tmp/managed-ui-fixture.php check > "$destination/restored.json"
cmp "$destination/before.json" "$destination/restored.json"
# Compare every managed code file, not merely version.php or DB metadata.
docker exec "$recovery-web" sh -c 'cd /var/www/html/public/mod && find . -type f -print0 | sort -z | xargs -0 sha256sum' > "$destination/restored-code-files.sha256"
cmp "$destination/source-code-files.sha256" "$destination/restored-code-files.sha256"
echo "PASS: full recovery of DB, application/config, managed module code and image lesson. Evidence: $destination"
echo "Recovery copy retained without published ports, Web server, Cron or external network: $recovery"
echo 'Original 8096 is running again. AWS and other sites were not modified.'
