#!/bin/sh
# Fresh 8096 site only. Never connect to existing 8095 or AWS data.
set -eu
umask 077
mode=${1:-fresh}
case "$mode" in
    fresh|--resume-before-start) ;;
    *) echo "Usage: prepare-managed-ui-lab.sh [--resume-before-start]" >&2; exit 2 ;;
esac
root=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
work="$root/build/managed-ui-lab"
zip="$(dirname "$root")/LessonMark/build/mod_lessonmark-0.2.0-alpha2.zip"
test "$(sha256sum "$zip" | cut -d ' ' -f 1)" = 1811fecd80eaf1eee3008009460ab6ef32a6675e45cc542e6afc27688eca6f41
for name in lessonmark-managed-ui lessonmark-managed-ui-cron lessonmark-managed-ui-db; do
    if docker inspect "$name" >/dev/null 2>&1; then
        echo "Existing lab container; refusing to recreate or reset: $name" >&2; exit 1
    fi
done
# Also catch Compose-generated initializer containers, including stopped ones.
if [ -n "$(docker ps -aq --filter label=com.docker.compose.project=lessonmark-managed-ui)" ]; then
    echo "Existing lab project containers; inspect before retrying." >&2; exit 1
fi
for suffix in managed_modules moodle_data moodle_backups moodle_db; do
    if docker volume inspect "lessonmark-managed-ui_$suffix" >/dev/null 2>&1; then
        echo "Existing lab volume; refusing to overwrite: $suffix" >&2; exit 1
    fi
done
if [ "$mode" = --resume-before-start ]; then
    # Resume ONLY before any lab containers or volumes exist. Never reset a DB,
    # regenerate credentials, or rebuild the already selected image.
    test -s "$work/runtime.env"
    test ! -e "$work/before.json"
    image=$(sed -n 's/^MANAGED_MOODLE_IMAGE=//p' "$work/runtime.env")
    if ! printf '%s\n' "$image" | grep -Eq '^sha256:[0-9a-f]{64}$'; then
        echo "Invalid pinned image in existing runtime.env" >&2; exit 1
    fi
    test "$(docker image inspect --format '{{.Id}}' "$image")" = "$image"
    echo "Resuming before first start; retaining image and existing credentials."
else
    if [ -e "$work" ]; then
        echo "Existing lab work directory; inspect before retrying: $work" >&2; exit 1
    fi
mkdir -p "$work/seed"
unzip -q "$zip" -d "$work/seed"
base=$(docker image inspect --format '{{.Id}}' moodle-rescue-ui-test-moodle)
docker build --network none --pull=false --build-arg BASE_IMAGE=moodle-rescue-ui-test-moodle \
    -t lessonmark-managed-ui-alpha2 -f "$root/moodle/managed-lab.Dockerfile" "$work/seed"
test "$(docker image inspect --format '{{.Id}}' moodle-rescue-ui-test-moodle)" = "$base"
image=$(docker image inspect --format '{{.Id}}' lessonmark-managed-ui-alpha2)
{
    printf 'MANAGED_MOODLE_IMAGE=%s\n' "$image"
    printf 'MANAGED_LAB_DB_PASSWORD=%s\n' "$(openssl rand -hex 24)"
    printf 'MANAGED_LAB_ROOT_PASSWORD=%s\n' "$(openssl rand -hex 24)"
    printf 'MANAGED_LAB_ADMIN_PASSWORD=Lab-%s!\n' "$(openssl rand -hex 24)"
} > "$work/runtime.env"
fi
compose() {
    # Avoid ambient environment overriding the generated lab-only settings.
    (
        unset MANAGED_MOODLE_IMAGE MANAGED_LAB_DB_PASSWORD MANAGED_LAB_ROOT_PASSWORD MANAGED_LAB_ADMIN_PASSWORD
        docker compose --env-file "$work/runtime.env" -p lessonmark-managed-ui \
            -f "$root/docker-compose.managed-lab.yml" \
            -f "$root/docker-compose.managed-modules.yml" "$@"
    )
}
compose config --quiet
compose up -d --no-build --pull never moodle moodle-cron
docker exec lessonmark-managed-ui /usr/local/bin/moodle-ui-test-install
test "$(docker exec -u www-data lessonmark-managed-ui php /var/www/html/admin/cli/cfg.php --component=mod_lessonmark --name=version)" = 2026083001
docker cp "$root/scripts/managed-ui-fixture.php" lessonmark-managed-ui:/tmp/managed-ui-fixture.php
docker exec -u www-data lessonmark-managed-ui php /tmp/managed-ui-fixture.php create > "$work/before.json"
if ! curl --noproxy '*' --fail --silent --show-error --max-time 10 \
        --output /dev/null http://127.0.0.1:8096/; then
    echo 'Database and fixture initialized, but HTTP access failed; inspect networking without reinstalling.' >&2
    exit 1
fi
printf 'Ready: http://localhost:8096/ (admin)\nPassword is in %s/runtime.env (MANAGED_LAB_ADMIN_PASSWORD).\n' "$work"
echo 'Alpha2 and a synthetic image lesson are ready. Upload the verified RC2 ZIP through Site administration > Plugins > Install plugins.'
echo 'Do not edit the fixture before preservation comparison. Existing sites and AWS were not modified.'
