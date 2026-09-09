#!/bin/sh
# Opt-in UI-managed activity code. Never overwrite an initialized volume.
set -eu

mode=${1:-check}
source=/var/www/html/public/mod
target=/managed-modules
marker=.moodle-core-version.sha256
fingerprint=$(sha256sum /var/www/html/public/version.php | cut -d ' ' -f 1)

check() {
    directory=$1
    if [ ! -f "$directory/$marker" ] || [ "$(cat "$directory/$marker")" != "$fingerprint" ]; then
        echo "Managed modules are uninitialized or belong to a different Moodle core. Refusing startup." >&2
        exit 1
    fi
}

case "$mode" in
    init)
        if [ -e "$target/$marker" ]; then
            check "$target"
            echo "Existing managed modules retained; no image code copied."
            exit 0
        fi
        if [ -n "$(find "$target" -mindepth 1 -maxdepth 1 -print -quit)" ]; then
            echo "Refusing to seed a nonempty uninitialized module volume." >&2
            exit 1
        fi
        cp -a "$source/." "$target/"
        # The type directory must be writable for Moodle ZIP deployment/rename.
        # Only LessonMark's existing tree is made writable; core trees retain ownership.
        chown www-data:www-data "$target"
        chmod 0755 "$target"
        if [ -d "$target/lessonmark" ]; then
            chown -R www-data:www-data "$target/lessonmark"
        fi
        printf '%s\n' "$fingerprint" > "$target/$marker"
        chmod 0444 "$target/$marker"
        echo "Seeded managed modules from the selected image."
        ;;
    start)
        shift
        check "$source"
        exec /usr/local/bin/moodle-entrypoint "$@"
        ;;
    check)
        check "$source"
        ;;
    *) echo "Usage: managed-modules.sh init|check|start [command ...]" >&2; exit 2 ;;
esac
