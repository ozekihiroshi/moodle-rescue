#!/bin/sh
# Read version.php from stdin as text, never execute its Moodle-dependent PHP.
set -eu
build=$(sed -n 's/^[[:space:]]*\$plugin->version[[:space:]]*=[[:space:]]*\([0-9][0-9]*\);[[:space:]]*$/\1/p')
case "$build" in
    ''|*[!0-9]*)
        echo 'Expected exactly one numeric LessonMark version assignment.' >&2
        exit 1
        ;;
esac
printf '%s\n' "$build"
