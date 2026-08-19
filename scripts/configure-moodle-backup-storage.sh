#!/bin/sh
set -eu

repositoryroot="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

envfile="${1:-.env}"
backupdirectory='/var/moodlebackups'

compose() {
    docker compose --env-file "$envfile" "$@"
}

for service in moodle moodle-cron; do
    compose exec -T "$service" runuser -u www-data -- php -r '
        $path = "/var/moodlebackups";
        if (!is_dir($path) || !is_writable($path)) {
            fwrite(STDERR, $path . " is unavailable or not writable.\n");
            exit(1);
        }
    '
done

# Keep local-path editing disabled in config.php. The deployment process is the
# trusted authority that pins Moodle automated backups to the shared volume.
compose exec -T moodle runuser -u www-data -- php admin/cli/cfg.php \
    --component=backup \
    --name=backup_auto_destination \
    --set="$backupdirectory"

storage="$(compose exec -T moodle runuser -u www-data -- php admin/cli/cfg.php \
    --component=backup \
    --name=backup_auto_storage 2>/dev/null | tr -d '\r\n')"

case "$storage" in
    1|2)
        ;;
    *)
        compose exec -T moodle runuser -u www-data -- php admin/cli/cfg.php \
            --component=backup \
            --name=backup_auto_storage \
            --set=1
        storage=1
        ;;
esac

configured="$(compose exec -T moodle runuser -u www-data -- php admin/cli/cfg.php \
    --component=backup \
    --name=backup_auto_destination | tr -d '\r\n')"

if [ "$configured" != "$backupdirectory" ]; then
    echo "Moodle automated backup destination was not pinned correctly." >&2
    exit 1
fi

echo "Pinned Moodle automated backup storage mode $storage to $backupdirectory."
echo "The site administrator still controls activation, schedule, and retention."
