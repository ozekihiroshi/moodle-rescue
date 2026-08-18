#!/bin/sh
set -eu

manifest="/tmp/plugins.install"
archives="/tmp/plugin-zips"
publicroot="/var/www/html/public"

if [ ! -f "$manifest" ]; then
    echo "Plugin install manifest is unavailable: $manifest" >&2
    exit 1
fi

workroot="$(mktemp -d)"
trap 'rm -rf "$workroot"' EXIT HUP INT TERM

plugincount=0
linenumber=0

while IFS='|' read -r component destination plugindirectory archive expectedsha256 extra; do
    linenumber=$((linenumber + 1))

    case "$component" in
        ""|"#"*) continue ;;
    esac

    if [ -n "$extra" ] ||
            [ -z "$destination" ] ||
            [ -z "$plugindirectory" ] ||
            [ -z "$archive" ] ||
            [ -z "$expectedsha256" ]; then
        echo "Invalid plugin install manifest at line $linenumber." >&2
        exit 1
    fi

    if ! printf '%s\n' "$component" |
            grep -Eq '^[a-z][a-z0-9]*_[a-z][a-z0-9_]*$'; then
        echo "Invalid Moodle component at line $linenumber: $component" >&2
        exit 1
    fi

    if [ "$plugindirectory" != "${component#*_}" ] ||
            [ "$archive" != "$component.zip" ]; then
        echo "Plugin identity mismatch at line $linenumber: $component" >&2
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
            echo "Unsupported Moodle plugin destination: $destination" >&2
            exit 1
            ;;
    esac

    if ! printf '%s\n' "$expectedsha256" |
            grep -Eq '^[0-9a-f]{64}$'; then
        echo "Invalid SHA-256 for $component." >&2
        exit 1
    fi

    zipfile="$archives/$archive"
    if [ ! -f "$zipfile" ]; then
        echo "Verified plugin ZIP is unavailable: $zipfile" >&2
        exit 1
    fi

    printf '%s  %s\n' "$expectedsha256" "$zipfile" |
        sha256sum -c - >/dev/null

    entries="$workroot/$component.entries"
    unzip -Z1 "$zipfile" > "$entries"
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

    destinationroot="$publicroot/$destination"
    pluginroot="$destinationroot/$plugindirectory"
    if [ -e "$pluginroot" ]; then
        echo "Plugin destination already exists: $destination/$plugindirectory" >&2
        exit 1
    fi

    mkdir -p "$destinationroot"
    unzip -q "$zipfile" -d "$destinationroot"

    if [ ! -f "$pluginroot/version.php" ]; then
        echo "Installed plugin version.php is unavailable: $component" >&2
        exit 1
    fi

    if find "$pluginroot" -type l -print -quit | grep -q .; then
        echo "Installed plugin contains a symbolic link: $component" >&2
        exit 1
    fi

    normalizedversionfile="$workroot/$component.version.normalized"
    tr -d '[:space:]' < "$pluginroot/version.php" > "$normalizedversionfile"
    if ! grep -Fq "\$plugin->component='$component';" "$normalizedversionfile" &&
            ! grep -Fq "\$plugin->component=\"$component\";" "$normalizedversionfile"; then
        echo "Installed plugin component does not match: $component" >&2
        exit 1
    fi

    chown -R root:www-data "$pluginroot"
    find "$pluginroot" -type d -exec chmod 0755 {} +
    find "$pluginroot" -type f -exec chmod 0644 {} +
    plugincount=$((plugincount + 1))
    echo "Installed $component -> public/$destination/$plugindirectory"
done < "$manifest"

if [ "$plugincount" -eq 0 ]; then
    echo "Plugin install manifest does not contain any plugins." >&2
    exit 1
fi

echo "Installed $plugincount verified plugin(s)."
