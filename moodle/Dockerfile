FROM composer:2.8.12 AS aws-sdk-builder

WORKDIR /app
COPY moodle/aws-sdk/composer.json moodle/aws-sdk/composer.lock ./
RUN composer install --no-dev --classmap-authoritative --no-interaction --no-progress --no-scripts

FROM php:8.3-apache-bookworm AS moodle-runtime

ARG MOODLE_VERSION=5.2.2
ARG MOODLE_SHA256=72be209e7c0f5341b87de0bc993b2430087fda2769d8c3cc2f32736d1513e88c

COPY --from=aws-sdk-builder /app/vendor /opt/moodle-aws-sdk/vendor

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libsodium-dev \
        libxml2-dev \
        libzip-dev \
        unzip; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        mysqli \
        opcache \
        soap \
        zip; \
    if ! php -m | grep -qi '^sodium$'; then \
        docker-php-ext-install -j"$(nproc)" sodium; \
    fi; \
    curl -fsSL \
        "https://download.moodle.org/download.php/direct/stable502/moodle-${MOODLE_VERSION}.tgz" \
        -o /tmp/moodle.tgz; \
    echo "${MOODLE_SHA256}  /tmp/moodle.tgz" | sha256sum -c -; \
    tar -xzf /tmp/moodle.tgz --strip-components=1 -C /var/www/html; \
    rm /tmp/moodle.tgz; \
    sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf; \
    a2enmod headers rewrite; \
    chown -R root:www-data /var/www/html; \
    find /var/www/html -type d -exec chmod 0755 {} +; \
    find /var/www/html -type f -exec chmod 0644 {} +; \
    rm -rf /var/lib/apt/lists/*

COPY moodle/php.ini /usr/local/etc/php/conf.d/zz-moodle.ini
COPY moodle/config.php /usr/local/share/moodle/config.php
COPY moodle/entrypoint.sh /usr/local/bin/moodle-entrypoint
COPY moodle/cron-loop.sh /usr/local/bin/moodle-cron-loop
COPY moodle/local/pythonlabsubmit/ /var/www/html/public/local/pythonlabsubmit/

RUN chmod 0755 \
    /usr/local/bin/moodle-entrypoint \
    /usr/local/bin/moodle-cron-loop

WORKDIR /var/www/html

ENTRYPOINT ["moodle-entrypoint"]
CMD ["apache2-foreground"]

FROM moodle-runtime AS moodle-ui-test

COPY moodle/ui-test-install.sh /usr/local/bin/moodle-ui-test-install

RUN set -eux; \
    chown www-data:www-data /var/www/html/public/admin/tool; \
    chmod 0755 /var/www/html/public/admin/tool; \
    chmod 0755 /usr/local/bin/moodle-ui-test-install

FROM moodle-runtime AS moodle-production

COPY moodle/install-plugins.sh /usr/local/bin/moodle-install-plugins
COPY build/plugins.install /tmp/plugins.install
COPY build/plugin-zips/ /tmp/plugin-zips/

RUN set -eux; \
    chmod 0755 /usr/local/bin/moodle-install-plugins; \
    /usr/local/bin/moodle-install-plugins; \
    rm -rf \
        /tmp/plugin-zips \
        /tmp/plugins.install
