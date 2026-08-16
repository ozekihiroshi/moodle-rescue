#!/bin/sh
set -eu

pluginrepo="${PLUGIN_REPOSITORY:-../secure-s3-storage-for-moodle}"
output="${1:-release/tool_secure_s3_storage.zip}"

pluginrepo="$(cd "$pluginrepo" && pwd)"

if [ -n "$(git -C "$pluginrepo" status --porcelain)" ]; then
    echo "Plugin repository must be clean before building a release ZIP." >&2
    exit 1
fi

outputname="$(basename "$output")"
mkdir -p "$(dirname "$output")"
output="$(cd "$(dirname "$output")" && pwd)/$outputname"
temporary="$(mktemp "${output}.tmp.XXXXXX")"
trap 'rm -f "$temporary"' EXIT HUP INT TERM

git -C "$pluginrepo" archive \
    --format=zip \
    --prefix=secure_s3_storage/ \
    --output="$temporary" \
    HEAD \
    -- . ':(exclude).gitattributes' ':(exclude).gitignore'

unzip -Z1 "$temporary" | awk '
    $0 !~ /^secure_s3_storage\// { invalid = 1 }
    $0 == "secure_s3_storage/version.php" { version = 1 }
    $0 == "secure_s3_storage/settings.php" { settings = 1 }
    END { exit invalid || !version || !settings }
'

mv "$temporary" "$output"
trap - EXIT HUP INT TERM

echo "Plugin commit: $(git -C "$pluginrepo" rev-parse HEAD)"
sha256sum "$output"
