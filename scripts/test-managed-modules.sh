#!/bin/sh
# Local storage/permission contract test; no production DB, ports or networks.
set -eu
root=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
image=$(docker image inspect --format '{{.Id}}' "${1:-moodle-rescue-ui-test-moodle}")
prefix="lessonmark-managed-check-$(date -u +%Y%m%dT%H%M%SZ)-$$"
volume="$prefix-code"
partial="$prefix-partial"
script="$root/moodle/managed-modules.sh"
owner="lessonmark-managed-check"

cleanup() {
    for container in "$prefix-web" "$prefix-cron"; do
        if [ "$(docker inspect --format '{{index .Config.Labels "test.owner"}}' "$container" 2>/dev/null || true)" = "$owner" ]; then
            docker stop "$container" >/dev/null || true
            docker rm "$container" >/dev/null || true
        fi
    done
    echo "Test-only volumes retained: $volume $partial"
}
trap cleanup EXIT
docker volume create --label "test.owner=$owner" "$volume" >/dev/null
docker volume create --label "test.owner=$owner" "$partial" >/dev/null

initialize() {
    docker run --rm --network none --entrypoint sh \
        --mount "type=bind,src=$script,dst=/opt/managed-modules.sh,readonly" \
        --mount "type=volume,src=$volume,dst=/managed-modules,volume-nocopy" \
        "$image" /opt/managed-modules.sh init
}
start_reader() {
    name=$1
    access=$2
    docker run -d --name "$name" --label "test.owner=$owner" --network none \
        --entrypoint sh \
        --mount "type=bind,src=$script,dst=/opt/managed-modules.sh,readonly" \
        --mount "type=volume,src=$volume,dst=/var/www/html/public/mod,volume-nocopy$access" \
        "$image" -c 'sh /opt/managed-modules.sh check && exec sleep infinity' >/dev/null
}

initialize
start_reader "$prefix-web" ''
start_reader "$prefix-cron" ',readonly'
docker exec -u www-data "$prefix-web" sh -c '
    mkdir /var/www/html/public/mod/managedtestfixture
    printf "updated-code\n" > /var/www/html/public/mod/managedtestfixture/proof
    mv /var/www/html/public/mod/managedtestfixture /var/www/html/public/mod/managedtestfixture-renamed
'
docker exec "$prefix-cron" sh -c 'test "$(cat /var/www/html/public/mod/managedtestfixture-renamed/proof)" = updated-code'
if docker exec -u www-data "$prefix-cron" touch /var/www/html/public/mod/cron-must-not-write; then
    echo "FAIL: Cron could write managed code" >&2
    exit 1
fi
docker stop "$prefix-web" "$prefix-cron" >/dev/null
docker rm "$prefix-web" "$prefix-cron" >/dev/null
initialize
start_reader "$prefix-web" ''
start_reader "$prefix-cron" ',readonly'
for container in "$prefix-web" "$prefix-cron"; do
    docker exec "$container" sh -c 'test "$(cat /var/www/html/public/mod/managedtestfixture-renamed/proof)" = updated-code'
done
# Alter only this disposable container's core-version file, never shared code.
docker exec "$prefix-web" sh -c 'printf "\n// different core\n" >> /var/www/html/public/version.php'
if docker exec "$prefix-web" sh /opt/managed-modules.sh check; then
    echo "FAIL: different core accepted" >&2
    exit 1
fi
docker run --rm --network none --entrypoint sh \
    --mount "type=volume,src=$partial,dst=/managed-modules,volume-nocopy" \
    "$image" -c 'touch /managed-modules/interrupted-seed'
if docker run --rm --network none --entrypoint sh \
    --mount "type=bind,src=$script,dst=/opt/managed-modules.sh,readonly" \
    --mount "type=volume,src=$partial,dst=/managed-modules,volume-nocopy" \
    "$image" /opt/managed-modules.sh init; then
    echo "FAIL: partial volume accepted" >&2
    exit 1
fi
echo "PASS: seed, permissions, shared code, recreation persistence, core mismatch and partial-seed rejection."
