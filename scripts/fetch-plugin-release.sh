#!/bin/sh
set -eu

repositoryroot="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

version="0.2.1"
expectedsha256="6b7b0bc78934698ae87013c1a77131bb5030e416cde955819802886a9a69c0bb"
releaseurl="https://github.com/ozekihiroshi/secure-s3-storage-for-moodle/releases/download/v${version}/tool_secure_s3_storage.zip"
output="${1:-release/tool_secure_s3_storage.zip}"

case "$output" in
    /*) ;;
    *) output="$repositoryroot/$output" ;;
esac

for command in curl sha256sum unzip awk grep; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required release fetch command is unavailable: $command" >&2
        exit 1
    fi
done

outputdirectory="$(dirname "$output")"
mkdir -p "$outputdirectory"
outputdirectory="$(cd "$outputdirectory" && pwd)"
output="$outputdirectory/$(basename "$output")"

downloadroot="$(mktemp -d)"
downloadedzip="$downloadroot/tool_secure_s3_storage.zip"
trap 'rm -rf "$downloadroot"' EXIT HUP INT TERM

curl --fail --location --show-error --silent \
    --retry 3 --retry-all-errors \
    "$releaseurl" \
    --output "$downloadedzip"

printf '%s  %s\n' "$expectedsha256" "$downloadedzip" | sha256sum -c -

unzip -Z1 "$downloadedzip" | awk '
    $0 !~ /^secure_s3_storage\// { invalid = 1 }
    $0 == "secure_s3_storage/version.php" { version = 1 }
    $0 == "secure_s3_storage/settings.php" { settings = 1 }
    $0 == "secure_s3_storage/thirdpartylibs.xml" { thirdpartylibs = 1 }
    $0 == "secure_s3_storage/vendor/autoload.php" { autoload = 1 }
    $0 == "secure_s3_storage/vendor/aws/aws-sdk-php/LICENSE" { awslicense = 1 }
    END {
        exit invalid || !version || !settings || !thirdpartylibs ||
            !autoload || !awslicense
    }
'

unzip -p "$downloadedzip" secure_s3_storage/version.php |
    grep -Fq "\$plugin->release = '$version';"

mv -f "$downloadedzip" "$output"
trap - EXIT HUP INT TERM
rm -rf "$downloadroot"

echo "Fetched Secure S3 Storage ${version} from its GitHub prerelease."
sha256sum "$output"
