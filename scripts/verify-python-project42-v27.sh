#!/usr/bin/env bash
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
docker compose -f "${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}" exec -T moodle php < scripts/verify-python-project42-v27.php
