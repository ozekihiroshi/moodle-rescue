#!/bin/sh
set -eu

interval="${MOODLE_CRON_INTERVAL_SECONDS:-60}"

case "$interval" in
    ''|*[!0-9]*)
        echo "MOODLE_CRON_INTERVAL_SECONDS must be a positive integer." >&2
        exit 1
        ;;
esac

while true; do
    if php -r '
        mysqli_report(MYSQLI_REPORT_OFF);
        $db = @new mysqli(
            getenv("MOODLE_DB_HOST"),
            getenv("MOODLE_DB_USER"),
            getenv("MOODLE_DB_PASSWORD"),
            getenv("MOODLE_DB_NAME")
        );
        if ($db->connect_errno) {
            exit(1);
        }
        $result = $db->query("SHOW TABLES LIKE \"mdl_config\"");
        exit($result && $result->num_rows === 1 ? 0 : 1);
    '; then
        su -s /bin/sh www-data -c \
            'php /var/www/html/admin/cli/cron.php' || true
    fi

    sleep "$interval"
done
