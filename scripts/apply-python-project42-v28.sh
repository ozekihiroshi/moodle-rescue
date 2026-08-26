#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root_dir"
data_b64="$(base64 -w0 sample-content/introduction-to-python/datasets/learning-centres-practice.csv)"
docker compose -f "${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}" exec -T \
  -e PYTHON_COURSE_SHORTNAME="${1:-PYAI-INTRO}" -e PYAI_DATA_B64="$data_b64" \
  moodle runuser -u www-data -- php < scripts/upgrade-python-project42-v28.php
