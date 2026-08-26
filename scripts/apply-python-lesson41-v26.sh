#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"; cd "$root_dir"
docker compose -f "${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}" exec -T -e PYTHON_COURSE_SHORTNAME="${1:-PYAI-INTRO}" moodle runuser -u www-data -- php < scripts/upgrade-python-lesson41-v26.php
