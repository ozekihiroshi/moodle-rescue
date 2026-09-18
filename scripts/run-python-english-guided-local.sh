#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
docker exec moodle-rescue-local mkdir -p /tmp/english-guided
docker cp sample-content/introduction-to-python/duallearning-guided/. moodle-rescue-local:/tmp/english-guided/
for script in install-python-english-guided-chapter check-python-english-guided-chapter finalize-python-english-guided-course check-python-path-choice check-python-path-submission-roundtrip check-python-guided-feedback audit-python-path-activities verify-python-english-path; do
    docker cp "scripts/$script.php" moodle-rescue-local:/tmp/
done
for chapter in 0 1 2 3 4 5 6; do
    docker exec --user www-data moodle-rescue-local php /tmp/install-python-english-guided-chapter.php "$chapter"
    docker exec --user www-data moodle-rescue-local php /tmp/check-python-english-guided-chapter.php "$chapter"
done
docker exec --user www-data moodle-rescue-local php /tmp/finalize-python-english-guided-course.php
docker exec --user www-data moodle-rescue-local php /tmp/verify-python-english-path.php --guided
docker exec --user www-data moodle-rescue-local php /tmp/check-python-path-choice.php --english-guided
docker exec --user www-data moodle-rescue-local php /tmp/check-python-path-submission-roundtrip.php --english-guided
docker exec --user www-data moodle-rescue-local php /tmp/check-python-guided-feedback.php --english
python3 scripts/check-python-path-lab-links.py --english-guided
