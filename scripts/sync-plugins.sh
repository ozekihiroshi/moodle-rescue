#!/bin/sh
set -eu

repositoryroot="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
manifest="${PLUGIN_MANIFEST:-$repositoryroot/plugins.lock}"
output="$repositoryroot/build/plugin-zips"
installmanifest="$repositoryroot/build/plugins.install"
resolvedmanifest="$repositoryroot/build/plugins.lock.resolved"

case "$manifest" in
    /*) ;;
    *) manifest="$repositoryroot/$manifest" ;;
esac

if [ ! -f "$manifest" ]; then
    echo "Plugin manifest is unavailable: $manifest" >&2
    exit 1
fi

for command in curl sha256sum unzip awk grep sort uniq find tr; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required plugin synchronization command is unavailable: $command" >&2
        exit 1
    fi
done

workroot="$(mktemp -d)"
staging="$workroot/plugin-zips"
stagedinstallmanifest="$workroot/plugins.install"
downloads="$workroot/downloads"
seen="$workroot/components"
trap 'rm -rf "$workroot"' EXIT HUP INT TERM

mkdir -p "$staging" "$downloads"
: > "$stagedinstallmanifest"
: > "$seen"
plugincount=0
linenumber=0

while IFS='|' read -r component version destination url expectedsha256 extra; do
    linenumber=$((linenumber + 1))

    case "$component" in
        ""|"#"*) continue ;;
    esac

    if [ -n "$extra" ] ||
            [ -z "$version" ] ||
            [ -z "$destination" ] ||
            [ -z "$url" ] ||
            [ -z "$expectedsha256" ]; then
        echo "Invalid plugins.lock field count at line $linenumber." >&2
        exit 1
    fi

    if ! printf '%s\n' "$component" |
            grep -Eq '^[a-z][a-z0-9]*_[a-z][a-z0-9_]*$'; then
        echo "Invalid Moodle component at line $linenumber: $component" >&2
        exit 1
    fi

    if ! printf '%s\n' "$version" |
            grep -Eq '^[0-9A-Za-z][0-9A-Za-z._+-]*$'; then
        echo "Invalid plugin version at line $linenumber: $version" >&2
        exit 1
    fi

    case "$destination" in
        admin/tool|admin/report|auth|availability/condition|blocks|cache/stores|\
        contentbank/contenttype|course/format|customfield/field|enrol|files/converter|\
        filter|grade/export|grade/import|grade/report|lib/editor/atto/plugins|\
        lib/editor/tiny/plugins|local|media/player|message/output|mod|\
        mod/assign/feedback|mod/assign/submission|mod/quiz/accessrule|\
        payment/gateway|plagiarism|portfolio|question/behaviour|question/format|\
        question/type|repository|report|search/engine|theme|webservice)
            ;;
        *)
            echo "Unsupported Moodle plugin destination at line $linenumber: $destination" >&2
            exit 1
            ;;
    esac

    case "$url" in
        https://*) ;;
        *)
            echo "Plugin URL must use HTTPS at line $linenumber: $url" >&2
            exit 1
            ;;
    esac

    if ! printf '%s\n' "$expectedsha256" |
            grep -Eq '^[0-9a-f]{64}$'; then
        echo "Invalid SHA-256 at line $linenumber for $component." >&2
        exit 1
    fi

    if grep -Fxq "$component" "$seen"; then
        echo "Duplicate plugin component at line $linenumber: $component" >&2
        exit 1
    fi
    printf '%s\n' "$component" >> "$seen"

    plugindirectory="${component#*_}"
    downloadedzip="$downloads/$component.zip"
    entries="$workroot/$component.entries"
    extractroot="$workroot/extracted-$component"

    curl --fail --location --show-error --silent \
        --retry 3 --retry-all-errors \
        "$url" \
        --output "$downloadedzip"

    printf '%s  %s\n' "$expectedsha256" "$downloadedzip" |
        sha256sum -c - >/dev/null

    unzip -Z1 "$downloadedzip" > "$entries"
    if [ ! -s "$entries" ]; then
        echo "Plugin ZIP is empty: $component" >&2
        exit 1
    fi

    if ! awk -v root="$plugindirectory/" '
        {
            if (index($0, root) != 1 || index($0, "\\") != 0) {
                invalid = 1
            }
            count = split($0, parts, "/")
            for (indexpart = 1; indexpart <= count; indexpart++) {
                if (parts[indexpart] == "." || parts[indexpart] == "..") {
                    invalid = 1
                }
            }
        }
        END { exit invalid }
    ' "$entries"; then
        echo "Plugin ZIP has an invalid path or root directory: $component" >&2
        exit 1
    fi

    if LC_ALL=C sort "$entries" | uniq -d | grep -q .; then
        echo "Plugin ZIP contains duplicate entries: $component" >&2
        exit 1
    fi

    mkdir -p "$extractroot"
    unzip -q "$downloadedzip" -d "$extractroot"

    pluginroot="$extractroot/$plugindirectory"
    if [ ! -f "$pluginroot/version.php" ]; then
        echo "Plugin version.php is unavailable: $component" >&2
        exit 1
    fi

    if find "$pluginroot" -type l -print -quit | grep -q .; then
        echo "Plugin ZIP contains a symbolic link: $component" >&2
        exit 1
    fi

    normalizedversionfile="$workroot/$component.version.normalized"
    tr -d '[:space:]' < "$pluginroot/version.php" > "$normalizedversionfile"
    if ! grep -Fq "\$plugin->component='$component';" "$normalizedversionfile" &&
            ! grep -Fq "\$plugin->component=\"$component\";" "$normalizedversionfile"; then
        echo "Plugin component does not match plugins.lock: $component" >&2
        exit 1
    fi

    stagedzip="$staging/$component.zip"
    mv "$downloadedzip" "$stagedzip"
    printf '%s|%s|%s|%s|%s\n' \
        "$component" "$destination" "$plugindirectory" \
        "$component.zip" "$expectedsha256" >> "$stagedinstallmanifest"

    plugincount=$((plugincount + 1))
    echo "Verified $component $version -> public/$destination/$plugindirectory"
done < "$manifest"

if [ "$plugincount" -eq 0 ]; then
    echo "plugins.lock does not contain any plugins." >&2
    exit 1
fi

mkdir -p "$repositoryroot/build"
rm -rf "$output"
mv "$staging" "$output"
rm -f "$installmanifest"
mv "$stagedinstallmanifest" "$installmanifest"
cp "$manifest" "$resolvedmanifest"

echo "Prepared $plugincount verified plugin ZIP(s) in $output."
