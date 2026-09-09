ARG BASE_IMAGE
FROM ${BASE_IMAGE}
# Reproduce production parent permissions before the managed initializer runs.
RUN test ! -e /var/www/html/public/mod/lessonmark \
    && chown root:www-data /var/www/html/public/mod /var/www/html/public/admin/tool
COPY --chown=root:www-data lessonmark/ /var/www/html/public/mod/lessonmark/
