#!/usr/bin/env bash
set -euo pipefail
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
python3 scripts/verify-python-project1-page-checker-v30.py
python3 scripts/verify-python-project1-contract-v29.py
docker compose -f "${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}" exec -T moodle runuser -u www-data -- php < scripts/verify-python-project1-page-v30.php
