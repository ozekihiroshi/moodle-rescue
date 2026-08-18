#!/bin/sh
set -eu

if runuser -u www-data -- php admin/cli/cfg.php --name=version >/dev/null 2>&1; then
    echo "Moodle UI test database is already installed."
    exit 0
fi

: "${UI_TEST_ADMIN_PASSWORD:?UI_TEST_ADMIN_PASSWORD is required}"

exec runuser -u www-data -- php admin/cli/install_database.php \
    --lang=en \
    --adminuser=admin \
    --adminpass="$UI_TEST_ADMIN_PASSWORD" \
    --adminemail=admin@example.invalid \
    --fullname="Moodle Plugin UI Test" \
    --shortname="PluginUITest" \
    --agree-license
