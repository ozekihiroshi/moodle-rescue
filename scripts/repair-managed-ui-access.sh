#!/bin/sh
# Repair only the existing 8096 lab's Web attachment; never reset data or plugins.
set -eu
umask 077
root=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
work="$root/build/managed-ui-lab"
web=lessonmark-managed-ui
test -s "$work/runtime.env"
test -s "$work/before.json"
test "$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$web")" = lessonmark-managed-ui
test "$(docker inspect --format '{{index .Config.Labels "com.docker.compose.service"}}' "$web")" = moodle
compose() {
    (
        unset MANAGED_MOODLE_IMAGE MANAGED_LAB_DB_PASSWORD MANAGED_LAB_ROOT_PASSWORD MANAGED_LAB_ADMIN_PASSWORD
        docker compose --env-file "$work/runtime.env" -p lessonmark-managed-ui \
            -f "$root/docker-compose.managed-lab.yml" \
            -f "$root/docker-compose.managed-modules.yml" "$@"
    )
}
inventory() {
    docker cp "$root/scripts/managed-ui-fixture.php" "$web:/tmp/managed-ui-fixture.php" >/dev/null
    docker exec -u www-data "$web" php /tmp/managed-ui-fixture.php check > "$work/$1.json"
}
compose config --quiet
inventory before-access-repair
compose up -d --no-deps --no-build --pull never --force-recreate moodle
inventory after-access-repair
cmp "$work/before-access-repair.json" "$work/after-access-repair.json"
echo 'PASS: fixture records, Markdown and image bytes preserved.'
docker port "$web" 80/tcp
attempt=0
until curl --noproxy '*' --fail --silent --show-error --max-time 5 \
        --output /dev/null http://127.0.0.1:8096/; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 10 ]; then
        echo 'HTTP check failed. Inspect this lab only; do not reinstall or delete volumes.' >&2
        exit 1
    fi
    sleep 1
done
echo 'Ready: http://localhost:8096/ (HTTP access verified from WSL).'
