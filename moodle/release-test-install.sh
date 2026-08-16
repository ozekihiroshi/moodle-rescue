#!/bin/sh
set -eu

if runuser -u www-data -- php admin/cli/cfg.php --name=version >/dev/null 2>&1; then
    echo "Moodle database is already installed."
    exit 0
fi

: "${RELEASE_TEST_ADMIN_PASSWORD:?RELEASE_TEST_ADMIN_PASSWORD is required}"

exec runuser -u www-data -- php admin/cli/install_database.php \
    --lang=en \
    --adminuser=admin \
    --adminpass="$RELEASE_TEST_ADMIN_PASSWORD" \
    --adminemail=admin@example.invalid \
    --fullname="Moodle Release Test" \
    --shortname="ReleaseTest" \
    --agree-license
