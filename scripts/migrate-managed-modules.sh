#!/bin/sh
# One-time immutable -> managed activity code migration, preserving installed code.
# No plugin upgrade or uninstall. Run after the documented local release gates.
set -eu
umask 077
root=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
cd "$root"
web=moodle-rescue
cron=moodle-rescue-cron
db=moodle-rescue-db
stage=preflight
trap 'result=$?; if [ "$result" -ne 0 ]; then printf "FAIL stage=%s exit=%s. Preserve all backups/volumes and inspect service state before retry.\n" "$stage" "$result" >&2; fi' EXIT
test -f .env
test -z "$(git status --porcelain --untracked-files=no)"
for container in "$web" "$cron" "$db"; do
    test "$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$container")" = moodle-rescue
    test "$(docker inspect --format '{{.State.Running}}' "$container")" = true
done
test -z "$(docker volume ls -q --filter label=com.docker.compose.project=moodle-rescue --filter label=com.docker.compose.volume=managed_modules)"
if grep -Eq '^[[:space:]]*(COMPOSE_FILE|MANAGED_MOODLE_IMAGE)=' .env; then
    echo 'Existing Compose/image override; inspect instead of overwriting.' >&2; exit 1
fi
image=$(docker inspect --format '{{.Image}}' "$web")
test "$image" = "$(docker inspect --format '{{.Image}}' "$cron")"
dbimage=$(docker inspect --format '{{.Image}}' "$db")
installed=$(docker exec -u www-data "$web" php /var/www/html/admin/cli/cfg.php --component=mod_lessonmark --name=version)
test "$installed" = 2026083001
if [ "$(docker exec -u www-data "$web" php /var/www/html/admin/cli/cfg.php --name=maintenance_enabled 2>/dev/null || true)" = 1 ]; then
    echo 'Site already in maintenance; refusing to change its existing state.' >&2; exit 1
fi
for container in "$web" "$cron"; do
    changes=$(docker diff "$container")
    if printf '%s\n' "$changes" | grep -Eq '^[ACD] /var/www/html/public/mod(/|$)'; then
        echo "Live module changes in $container; image seeding would lose them. Refusing." >&2; exit 1
    fi
    mounts=$(docker inspect --format '{{range .Mounts}}{{println .Destination}}{{end}}' "$container")
    if printf '%s\n' "$mounts" | grep -Eq '^/var/www/html(/|$)'; then
        echo 'Existing application mount; manual migration review required.' >&2; exit 1
    fi
done
available=$(df -Pk "$root" | awk 'NR==2 {print $4}')
test "$available" -ge 6291456
destination="$root/backups/pre-managed-$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$root/backups"
mkdir "$destination"
cp .env "$destination/runtime.env"
cp docker-compose.yml docker-compose.managed-modules.yml "$destination/"
cp moodle/managed-modules.sh "$destination/"
git rev-parse HEAD > "$destination/repository-head.txt"
printf '%s\n' "$image" > "$destination/image-id.txt"
printf '%s\n' "$dbimage" > "$destination/db-image-id.txt"
printf '%s\n' "$installed" > "$destination/plugin-build.txt"
stage=preserve-images
echo "Preserving exact Web and DB images before downtime: $destination"
docker image save -o "$destination/images.tar" "$image" "$dbimage"
tar -tf "$destination/images.tar" >/dev/null
stage=quiesce-site
echo 'Pausing Moodle Cron and enabling maintenance for a consistent snapshot...'
docker stop "$cron" >/dev/null
docker exec -u www-data "$web" php /var/www/html/admin/cli/maintenance.php --enable
docker cp scripts/managed-site-inventory.php "$web:/tmp/managed-site-inventory.php" >/dev/null
docker exec -u www-data "$web" php /tmp/managed-site-inventory.php > "$destination/before.json"
docker stop "$web" >/dev/null
stage=snapshot
echo 'Snapshotting database, live application/config and teaching files...'
docker exec "$db" sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-dump -uroot --single-transaction --routines --events --triggers --databases "$MARIADB_DATABASE"' > "$destination/database.sql"
test -s "$destination/database.sql"
docker cp "$web:/var/www/html/." - > "$destination/application.tar"
gzip "$destination/application.tar"
docker run --rm --network none --entrypoint tar --volumes-from "$web:ro" \
    "$image" -czf - -C /var moodledata moodlebackups > "$destination/data.tgz"
tar -tzf "$destination/application.tar.gz" >/dev/null
tar -tzf "$destination/data.tgz" >/dev/null
docker run --rm --network none --entrypoint sh "$image" \
    -c 'cd /var/www/html/public/mod && find . -type f -print0 | sort -z | xargs -0 sha256sum' > "$destination/image-module-files.sha256"
(cd "$destination" && sha256sum images.tar database.sql application.tar.gz data.tgz \
    runtime.env docker-compose.yml docker-compose.managed-modules.yml managed-modules.sh \
    repository-head.txt image-id.txt db-image-id.txt plugin-build.txt before.json image-module-files.sha256 > SHA256SUMS)
(cd "$destination" && sha256sum -c SHA256SUMS >/dev/null)
echo "Snapshot integrity verified: $destination"
stage=enable-managed-compose
# Site-local settings only; all executable source is committed in this repository.
# The default Compose command will retain the overlay on subsequent recreations.
printf '\n# Persistent UI-managed activities (see docs/managed-modules.md)\nCOMPOSE_FILE=docker-compose.yml:docker-compose.managed-modules.yml\nMANAGED_MOODLE_IMAGE=%s\n' "$image" >> .env
compose() {
    MANAGED_MOODLE_IMAGE="$image" docker compose --env-file .env -p moodle-rescue \
        -f docker-compose.yml -f docker-compose.managed-modules.yml "$@"
}
compose config --quiet
stage=seed-modules
echo 'Seeding an empty managed module volume from the original alpha2 image...'
compose run --rm --no-deps moodle-modules-init
stage=recreate-web
compose up -d --no-deps --no-build --pull never --force-recreate moodle
attempt=0
until docker exec "$web" sh -c 'test "$(cat /proc/1/comm)" = apache2 && runuser -u www-data -- test -r /var/www/html/config.php' >/dev/null 2>&1; do
    attempt=$((attempt + 1)); test "$attempt" -lt 60; sleep 1
done
stage=verify-preservation
docker cp scripts/managed-site-inventory.php "$web:/tmp/managed-site-inventory.php" >/dev/null
docker exec -u www-data "$web" php /tmp/managed-site-inventory.php > "$destination/after.json"
cmp "$destination/before.json" "$destination/after.json"
docker exec "$web" sh -c 'cd /var/www/html/public/mod && find . -type f ! -name .moodle-core-version.sha256 -print0 | sort -z | xargs -0 sha256sum' > "$destination/managed-module-files.sha256"
cmp "$destination/image-module-files.sha256" "$destination/managed-module-files.sha256"
docker exec -u www-data "$web" test -w /var/www/html/public/mod/lessonmark
docker exec -u www-data "$web" test -w /var/www/html/public/mod
test "$(docker exec -u www-data "$web" php /var/www/html/admin/cli/cfg.php --component=mod_lessonmark --name=version)" = "$installed"
stage=resume-site
docker exec -u www-data "$web" php /var/www/html/admin/cli/maintenance.php --disable
compose up -d --no-deps --no-build --pull never --force-recreate moodle-cron
test "$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html/public/mod"}}{{.RW}}{{end}}{{end}}' "$cron")" = false
printf 'PASS: shared-code deployment enabled; alpha2, all LessonMark records/images and course counts unchanged. Backup: %s\n' "$destination"
echo 'The administrator UI can now upgrade LessonMark. Pause Cron during ZIP replacement/database upgrade, then restart it.'
