#!/usr/bin/env bash
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

compose_file="${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}"
if (($#)); then
  courses=("$@")
else
  courses=(PYAI-INTRO PYAI-INTRO-JA)
fi

for shortname in "${courses[@]}"; do
  docker compose -f "$compose_file" exec -T -u www-data \
    -e PYTHON_COURSE_SHORTNAME="$shortname" moodle php \
    < scripts/upgrade-python-lab-submit-v32.php
done
