#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
course_shortname="${1:-PYAI-INTRO}"
compose_file="${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}"
cd "$root_dir"
PYTHON_COURSE_SHORTNAME="$course_shortname" docker compose -f "$compose_file" exec -T \
  -e PYTHON_COURSE_SHORTNAME="$course_shortname" moodle runuser -u www-data -- php \
  < scripts/upgrade-python-lesson3-v14.php
