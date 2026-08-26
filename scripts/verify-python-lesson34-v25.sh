#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root_dir"
docker compose -f "${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}" exec -T moodle php < scripts/verify-python-lesson34-v25.php
