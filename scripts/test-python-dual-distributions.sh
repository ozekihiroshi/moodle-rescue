#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
docker exec moodle-rescue-local mkdir -p /tmp/python-dual-dist
docker cp sample-content/python-duallearning/distribution/. moodle-rescue-local:/tmp/python-dual-dist/
docker cp scripts/test-python-dual-distribution-restore.php moodle-rescue-local:/tmp/
for variant in en-path en-guided ja-path ja-guided; do
    docker exec --user www-data moodle-rescue-local php /tmp/test-python-dual-distribution-restore.php "$variant"
    docker cp "moodle-rescue-local:/var/moodledata/temp/python-dual-distribution/restore-$variant.json" "sample-content/python-duallearning/distribution/restore-$variant.json"
done
