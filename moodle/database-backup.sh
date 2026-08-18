#!/bin/sh
set -eu

outputdirectory="${DATABASE_BACKUP_OUTPUT_DIRECTORY:-/database-artifacts}"
reader_gid="${DATABASE_BACKUP_READER_GID:-33}"
database_host="${MOODLE_DB_HOST:?MOODLE_DB_HOST is required}"
database_port="${MOODLE_DB_PORT:-3306}"
database_name="${MOODLE_DB_NAME:?MOODLE_DB_NAME is required}"
database_user="${MOODLE_DB_USER:?MOODLE_DB_USER is required}"
database_password="${MOODLE_DB_PASSWORD:?MOODLE_DB_PASSWORD is required}"

case "$outputdirectory" in
    /*) ;;
    *)
        echo "DATABASE_BACKUP_OUTPUT_DIRECTORY must be absolute." >&2
        exit 1
        ;;
esac

case "$reader_gid" in
    ''|*[!0-9]*)
        echo "DATABASE_BACKUP_READER_GID must be numeric." >&2
        exit 1
        ;;
esac

case "$database_port" in
    ''|*[!0-9]*)
        echo "MOODLE_DB_PORT must be numeric." >&2
        exit 1
        ;;
esac

if ! command -v grep >/dev/null 2>&1; then
    echo "Required database backup command is unavailable: grep" >&2
    exit 1
fi

for value in "$database_host" "$database_name" "$database_user" "$database_password"; do
    if printf '%s' "$value" | grep -q '[[:cntrl:]]'; then
        echo "Database connection values must not contain control characters." >&2
        exit 1
    fi
done

for command in mariadb-dump gzip sha256sum stat od tr sed date mktemp grep cut; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required database backup command is unavailable: $command" >&2
        exit 1
    fi
done

umask 0077
mkdir -p "$outputdirectory"
chmod 0750 "$outputdirectory"
chgrp "$reader_gid" "$outputdirectory"

timestamp="$(date -u '+%Y%m%dT%H%M%SZ')"
createdat="$(date -u '+%Y-%m-%dT%H:%M:%SZ')"
artifactid="$(od -An -N16 -tx1 /dev/urandom | tr -d ' \n')"

if ! printf '%s\n' "$artifactid" | grep -Eq '^[0-9a-f]{32}$'; then
    echo "Unable to generate a database artifact identifier." >&2
    exit 1
fi

shortid="$(printf '%s' "$artifactid" | cut -c1-16)"
recoverysetid="${timestamp}-${shortid}"
payloadname="moodle-db-${timestamp}-${shortid}.sql.gz"
manifestname="${payloadname}.manifest.json"

optionfile="$(mktemp /tmp/moodle-db-client.XXXXXXXX)"
sqltemporary="$outputdirectory/.${payloadname}.sql.partial"
payloadtemporary="$outputdirectory/.${payloadname}.partial"
manifesttemporary="$outputdirectory/.${manifestname}.partial"
payloadfinal="$outputdirectory/$payloadname"
manifestfinal="$outputdirectory/$manifestname"
published=0

cleanup() {
    rm -f \
        "$optionfile" \
        "$sqltemporary" \
        "$payloadtemporary" \
        "$manifesttemporary"

    if [ "$published" -ne 1 ]; then
        rm -f "$payloadfinal" "$manifestfinal"
    fi
}
trap cleanup EXIT HUP INT TERM

escape_option_value() {
    printf '%s' "$1" |
        sed -e 's/\\/\\\\/g' -e 's/"/\\"/g'
}

{
    printf '%s\n' '[client]'
    printf 'host="%s"\n' "$(escape_option_value "$database_host")"
    printf 'port=%s\n' "$database_port"
    printf 'user="%s"\n' "$(escape_option_value "$database_user")"
    printf 'password="%s"\n' "$(escape_option_value "$database_password")"
    printf '%s\n' 'default-character-set=utf8mb4'
} > "$optionfile"
chmod 0600 "$optionfile"

mariadb-dump \
    "--defaults-extra-file=$optionfile" \
    --single-transaction \
    --quick \
    --skip-lock-tables \
    --no-tablespaces \
    --hex-blob \
    --default-character-set=utf8mb4 \
    "$database_name" \
    > "$sqltemporary"

test -s "$sqltemporary"

gzip -9 -c "$sqltemporary" > "$payloadtemporary"
rm -f "$sqltemporary"
test -s "$payloadtemporary"

bytes="$(stat -c '%s' "$payloadtemporary")"
sha256="$(sha256sum "$payloadtemporary" | cut -d ' ' -f 1)"

if ! printf '%s\n' "$bytes" | grep -Eq '^[1-9][0-9]*$' ||
        ! printf '%s\n' "$sha256" | grep -Eq '^[0-9a-f]{64}$'; then
    echo "Generated database artifact metadata is invalid." >&2
    exit 1
fi

chgrp "$reader_gid" "$payloadtemporary"
chmod 0640 "$payloadtemporary"
mv "$payloadtemporary" "$payloadfinal"

cat > "$manifesttemporary" <<EOF
{
  "schema": "tool_secure_s3_storage.artifact/v1",
  "artifactid": "$artifactid",
  "type": "database",
  "createdat": "$createdat",
  "payload": "$payloadname",
  "bytes": $bytes,
  "sha256": "$sha256",
  "format": "mariadb-sql",
  "compression": "gzip",
  "encryption": "none",
  "recoverysetid": "$recoverysetid"
}
EOF

chgrp "$reader_gid" "$manifesttemporary"
chmod 0640 "$manifesttemporary"
mv "$manifesttemporary" "$manifestfinal"
published=1

printf '{"artifactid":"%s","payload":"%s","manifest":"%s","bytes":%s,"sha256":"%s"}\n' \
    "$artifactid" \
    "$payloadname" \
    "$manifestname" \
    "$bytes" \
    "$sha256"
