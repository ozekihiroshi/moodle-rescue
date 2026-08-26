#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}"
shortname="${1:-PYAI-INTRO}"
cd "$root_dir"
docker compose -f "$compose_file" exec -T \
  -e PYTHON_COURSE_SHORTNAME="$shortname" \
  moodle runuser -u www-data -- php \
  < scripts/upgrade-python-project1-v18.php
