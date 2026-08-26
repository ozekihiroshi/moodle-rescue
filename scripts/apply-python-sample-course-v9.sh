#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
course_shortname="${1:-PYAI-INTRO}"
compose_file="${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}"

cd "$root_dir"
{
  cat scripts/upgrade-python-sample-course-v8.php
  tail -n +4 scripts/upgrade-python-sample-course-v9.php
} | PYTHON_COURSE_SHORTNAME="$course_shortname" docker compose -f "$compose_file" exec -T \
    -e PYTHON_COURSE_SHORTNAME="$course_shortname" moodle runuser -u www-data -- php
