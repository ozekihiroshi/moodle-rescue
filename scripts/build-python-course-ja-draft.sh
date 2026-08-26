#!/bin/sh
set -eu

compose_file=${COMPOSE_FILE:-docker-compose.local.yml}
course_shortname=PYAI-INTRO-JA
course_fullname='Pythonによるデータ活用：AI時代の基礎'

run_php() {
    script=$1
    docker compose -f "$compose_file" exec -T moodle \
        env PYTHON_COURSE_SHORTNAME="$course_shortname" PYTHON_COURSE_FULLNAME="$course_fullname" \
        runuser -u www-data -- php < "$script"
}

if [ "${PYTHON_COURSE_RESUME:-0}" != 1 ]; then
    run_php scripts/create-python-sample-course.php
fi
run_php scripts/upgrade-python-sample-course-v2.php
run_php scripts/upgrade-python-sample-course-v3.php
run_php scripts/upgrade-python-sample-course-v4.php
run_php scripts/upgrade-python-sample-course-v5.php
run_php scripts/configure-python-lab-lti.php
run_php scripts/configure-python-lab-notebooks.php
run_php scripts/group-python-course-chapters.php
run_php scripts/upgrade-python-sample-course-v7.php

canonical=sample-content/introduction-to-python/localization/canonical-en-1.0.0.json
adaptation=sample-content/introduction-to-python-ja/adaptation/segments-ja-1.0.0.json

docker compose -f "$compose_file" cp "$canonical" moodle:/tmp/python-canonical.json
docker compose -f "$compose_file" cp "$adaptation" moodle:/tmp/python-adaptation.json
docker compose -f "$compose_file" exec -T moodle \
    env PYTHON_CANONICAL_CATALOGUE=/tmp/python-canonical.json \
        PYTHON_ADAPTATION_SEGMENTS=/tmp/python-adaptation.json \
        PYTHON_ADAPTATION_ALLOW_PENDING=1 \
    runuser -u www-data -- php < scripts/apply-python-course-adaptation.php
