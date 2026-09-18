#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
mkdir -p build/python-dual-raw
docker cp scripts/export-python-dual-distributions.php moodle-rescue-local:/tmp/
for variant in ja-path ja-guided en-path en-guided; do
    docker exec --user www-data moodle-rescue-local php /tmp/export-python-dual-distributions.php "$variant"
    docker cp "moodle-rescue-local:/var/moodledata/temp/python-dual-distribution/$variant.mbz" "build/python-dual-raw/$variant.mbz"
done
