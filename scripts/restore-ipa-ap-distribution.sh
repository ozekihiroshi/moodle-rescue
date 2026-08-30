#!/bin/sh
set -eu

repo_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
compose_file=${COMPOSE_FILE:-docker-compose.local.yml}
compose_override=${IPA_AP_COMPOSE_OVERRIDE:-docker-compose.lessonmark.yml}
category_id=${1:-1}
artifact=${2:-$repo_dir/sample-content/ap-written-practice-ja/distribution/ipa-ap-written-practice-ja-2025-spring-0.1.0-alpha.1.mbz}
container_file=/var/moodlebackups/ipa-ap-written-practice-ja-restore.mbz

if [ ! -f "$artifact" ]; then
    echo "Distribution artifact not found: $artifact" >&2
    exit 1
fi

python3 "$repo_dir/scripts/verify-ipa-ap-distribution.py" "$artifact"

compose() {
    docker compose -f "$compose_file" -f "$compose_override" "$@"
}

if [ -z "$(compose ps -q moodle)" ]; then
    echo 'The Moodle container is not running.' >&2
    exit 1
fi

compose cp "$artifact" "moodle:$container_file"
cleanup() {
    compose exec -T moodle rm -f "$container_file" >/dev/null 2>&1 || true
}
trap cleanup EXIT INT TERM

compose exec -T moodle runuser -u www-data -- php admin/cli/restore_backup.php \
    --file="$container_file" --categoryid="$category_id"

echo 'Restore completed. Review visibility, enrolment, and access settings before admitting learners.'
