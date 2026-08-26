#!/bin/sh
set -eu

repo_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
dist_dir="$repo_dir/sample-content/introduction-to-python-ja/distribution"
artifact="$dist_dir/python-for-data-foundations-ai-era-ja-1.0.0.mbz"

test -f "$artifact"

(
    cd "$dist_dir"
    sha256sum -c SHA256SUMS
)

archive_index=$(tar -tf "$artifact")
printf '%s\n' "$archive_index" | grep -qx 'moodle_backup.xml'
printf '%s\n' "$archive_index" | grep -q '^activities/quiz_'
printf '%s\n' "$archive_index" | grep -q '^activities/assign_'
printf '%s\n' "$archive_index" | grep -q '^activities/lti_'

metadata=$(tar -xOf "$artifact" moodle_backup.xml)
compact_metadata=$(printf '%s' "$metadata" | tr -d '\r\n ')
printf '%s' "$metadata" | grep -q '<original_course_shortname>PYAI-INTRO-JA</original_course_shortname>'
printf '%s' "$metadata" | grep -q '<moodle_release>5.2.2 (Build: 20260810)</moodle_release>'
printf '%s' "$compact_metadata" | grep -q '<name>users</name><value>0</value>'
printf '%s' "$compact_metadata" | grep -q '<name>role_assignments</name><value>0</value>'
printf '%s' "$compact_metadata" | grep -q '<name>userscompletion</name><value>0</value>'

echo 'Japanese Python sample distribution verified.'
