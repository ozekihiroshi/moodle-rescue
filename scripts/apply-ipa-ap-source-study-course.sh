#!/bin/sh
set -eu

compose_file=${COMPOSE_FILE:-docker-compose.local.yml}
compose_override=${IPA_AP_COMPOSE_OVERRIDE:-docker-compose.lessonmark.yml}
content_root=sample-content/ap-written-practice-ja
container_root=/tmp/ap-written-practice-ja

compose() {
    docker compose -f "$compose_file" -f "$compose_override" "$@"
}

if [ ! -f "$content_root/course-manifest-v3.json" ]; then
    echo "V3 course source not found: $content_root" >&2
    exit 1
fi

container_id=$(compose ps -q moodle)
if [ -z "$container_id" ]; then
    echo "The Moodle container is not running." >&2
    exit 1
fi

compose cp "$content_root/." "moodle:$container_root"
cleanup() {
    compose exec -T moodle rm -rf "$container_root"
}
trap cleanup EXIT INT TERM

compose exec -T \
    -e IPA_AP_CONTENT_ROOT="$container_root" \
    -e IPA_AP_CREATE_IF_MISSING=1 \
    moodle runuser -u www-data -- php < scripts/update-ipa-ap-source-study-course.php
