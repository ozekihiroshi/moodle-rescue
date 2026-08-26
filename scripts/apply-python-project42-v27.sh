#!/usr/bin/env bash
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
docker compose -f "${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}" exec -T -e PYTHON_COURSE_SHORTNAME="${1:-PYAI-INTRO}" moodle runuser -u www-data -- php < scripts/upgrade-python-project42-v27.php
