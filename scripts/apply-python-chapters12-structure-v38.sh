#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root_dir"
for shortname in PYAI-INTRO PYAI-INTRO-JA; do
  docker compose -f docker-compose.local.yml exec -T -e PYTHON_COURSE_SHORTNAME="$shortname" moodle runuser -u www-data -- php < scripts/upgrade-python-chapters12-structure-v38.php
done
docker compose -f docker-compose.local.yml exec -T moodle runuser -u www-data -- php < scripts/verify-python-chapters12-structure-v38.php
