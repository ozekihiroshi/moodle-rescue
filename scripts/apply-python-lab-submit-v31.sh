#!/usr/bin/env bash
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
shortname="${1:-PYAI-INTRO}"
docker compose -f "${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}" exec -T \
  -e PYTHON_COURSE_SHORTNAME="${shortname}" moodle php < scripts/upgrade-python-lab-submit-v31.php
