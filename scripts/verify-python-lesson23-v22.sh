#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}"
cd "$root_dir"
docker compose -f "$compose_file" exec -T moodle runuser -u www-data -- php \
  < scripts/verify-python-lesson23-v22.php
