#!/bin/sh
# Run AFTER the administrator has completed the RC2 ZIP upgrade on localhost:8096.
set -eu
umask 077
mode=${1:-verify}
case "$mode" in
    verify|--check-only) ;;
    *) echo 'Usage: verify-managed-ui-lab.sh [--check-only]' >&2; exit 2 ;;
esac
stage=preflight
trap 'result=$?; if [ "$result" -ne 0 ]; then printf "FAIL: stage=%s exit=%s; verification not completed.\n" "$stage" "$result" >&2; fi' EXIT
root=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
work="$root/build/managed-ui-lab"
if [ ! -s "$work/before.json" ]; then
    echo "Missing baseline: $work/before.json" >&2; exit 1
fi
compose() {
    (
        unset MANAGED_MOODLE_IMAGE MANAGED_LAB_DB_PASSWORD MANAGED_LAB_ROOT_PASSWORD MANAGED_LAB_ADMIN_PASSWORD
        docker compose --env-file "$work/runtime.env" -p lessonmark-managed-ui \
            -f "$root/docker-compose.managed-lab.yml" \
            -f "$root/docker-compose.managed-modules.yml" "$@"
    )
}
verify() {
    stage="$1-versions"
    mismatch=0
    for container in lessonmark-managed-ui lessonmark-managed-ui-cron; do
        stage="$1-$container-inspect"
        printf 'Checking container: %s\n' "$container"
        project=$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$container")
        if [ "$project" != lessonmark-managed-ui ]; then
            echo "Unexpected project for $container: $project" >&2; exit 1
        fi
        stage="$1-$container-code-version"
        # version.php references MATURITY_RC from Moodle core; requiring it
        # standalone produces an undefined-constant fatal error. Read as text.
        if codesource=$(docker exec "$container" cat /var/www/html/public/mod/lessonmark/version.php); then
            codeversion=$(printf '%s\n' "$codesource" | sh "$root/scripts/read-lessonmark-build.sh")
        else
            result=$?
            echo "Cannot read LessonMark version.php in $container" >&2
            exit "$result"
        fi
        printf '  Code version: %s\n' "$codeversion"
        stage="$1-$container-database-version"
        printf '  Reading installed version through Moodle CLI...\n'
        if dbversion=$(docker exec -u www-data "$container" php -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 -d error_reporting=-1 /var/www/html/admin/cli/cfg.php --component=mod_lessonmark --name=version 2>&1); then
            :
        else
            result=$?
            printf 'Moodle CLI failed (exit %s):\n%s\n' "$result" "$dbversion" >&2
            exit "$result"
        fi
        printf '%s: database=%s code=%s expected=2026090802\n' "$container" "$dbversion" "$codeversion"
        if [ "$dbversion" != 2026090802 ] || [ "$codeversion" != 2026090802 ]; then
            mismatch=1
        fi
    done
    if [ "$mismatch" -ne 0 ]; then
        echo 'Version mismatch. No recreation will be attempted; inspect the actual 8096 upgrade state.' >&2
        exit 1
    fi
    stage="$1-fixture"
    docker cp "$root/scripts/managed-ui-fixture.php" lessonmark-managed-ui:/tmp/managed-ui-fixture.php
    docker exec -u www-data lessonmark-managed-ui php /tmp/managed-ui-fixture.php check > "$work/$1.json"
    cmp "$work/before.json" "$work/$1.json"
}
verify after-ui-upgrade
if [ "$mode" = --check-only ]; then
    echo 'PASS: current code/DB versions and fixture match. Check-only: containers were not recreated.'
    exit 0
fi
# Recreate ONLY the dedicated 8096 Web and idle Cron, not DB or other projects.
stage=recreate-lab-web-and-idle-cron
compose up -d --no-deps --no-build --pull never --force-recreate moodle moodle-cron
verify after-recreation
echo 'PASS: RC2 code/DB versions match in Web and Cron; fixture IDs, records, Markdown and image bytes survived UI upgrade and container recreation.'
