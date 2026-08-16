#!/bin/sh
set -eu

install -m 0640 -o root -g www-data \
    /usr/local/share/moodle/config.php \
    /var/www/html/config.php

mkdir -p /var/moodledata /var/moodlebackups
chown www-data:www-data /var/moodledata /var/moodlebackups
chmod 0770 /var/moodledata /var/moodlebackups

exec docker-php-entrypoint "$@"
