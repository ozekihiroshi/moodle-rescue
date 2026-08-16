#!/bin/sh
set -eu

repositoryroot="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

pluginrepo="${PLUGIN_REPOSITORY:-../secure-s3-storage-for-moodle}"
output="${1:-release/tool_secure_s3_storage.zip}"
pluginrepo="$(cd "$pluginrepo" && pwd)"

case "$output" in
    /*) ;;
    *) output="$repositoryroot/$output" ;;
esac

if [ ! -f "$pluginrepo/scripts/build-release.sh" ]; then
    echo "Plugin release builder is unavailable." >&2
    exit 1
fi

exec sh "$pluginrepo/scripts/build-release.sh" "$output"
