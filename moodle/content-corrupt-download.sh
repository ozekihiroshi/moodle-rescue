#!/bin/sh
set -eu

root="${CONTENT_RECOVERY_ROOT:-/content-recovery}"
case "$root" in
    /content-recovery) ;;
    *) echo "Unexpected content recovery root." >&2; exit 1 ;;
esac

set -- "$root"/moodle-content-*.jsonl.gz
if [ "$#" -ne 1 ] || [ ! -f "$1" ] || [ -L "$1" ]; then
    echo "Exactly one regular content inventory is required." >&2
    exit 1
fi
printf 'X' >> "$1"
echo "Corrupted content inventory fixture: ${1##*/}"