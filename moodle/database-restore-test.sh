#!/bin/sh

set -eu

artifactdirectory="${DATABASE_ARTIFACT_DIRECTORY:-/database-artifacts}"
restorehost="${RESTORE_DB_HOST:?RESTORE_DB_HOST is required}"
restoreport="${RESTORE_DB_PORT:-3306}"
restoredatabase="${RESTORE_DB_NAME:?RESTORE_DB_NAME is required}"
restoreuser="${RESTORE_DB_USER:?RESTORE_DB_USER is required}"
restorepassword="${RESTORE_DB_PASSWORD:?RESTORE_DB_PASSWORD is required}"
manifestname="${DATABASE_BACKUP_MANIFEST:-}"

if ! printf '%s\n' "$restoredatabase" | grep -Eq '^moodle_restore_[0-9a-f]{16}$'; then
    echo "Refusing to restore into an unexpected database name: $restoredatabase" >&2
    exit 1
fi

for commandname in mariadb gzip sha256sum stat sed find sort tail mktemp grep cut; do
    if ! command -v "$commandname" >/dev/null 2>&1; then
        echo "Required restore-test command is unavailable: $commandname" >&2
        exit 1
    fi
done

if [ -z "$manifestname" ]; then
    manifestpath="$(find "$artifactdirectory" -maxdepth 1 -type f -name 'moodle-db-*.sql.gz.manifest.json' -print | sort | tail -n 1)"
    if [ -z "$manifestpath" ]; then
        echo "No database backup manifest was found in $artifactdirectory" >&2
        exit 1
    fi
    manifestname="${manifestpath##*/}"
else
    case "$manifestname" in
        */*|*..*)
            echo "DATABASE_BACKUP_MANIFEST must be a file name, not a path" >&2
            exit 1
            ;;
    esac
    manifestpath="$artifactdirectory/$manifestname"
fi

case "$manifestname" in
    moodle-db-[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]T[0-9][0-9][0-9][0-9][0-9][0-9]Z-[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f].sql.gz.manifest.json) ;;
    *)
        echo "Unexpected database backup manifest name: $manifestname" >&2
        exit 1
        ;;
esac

if [ ! -f "$manifestpath" ] || [ -L "$manifestpath" ]; then
    echo "Manifest is not a regular non-symlink file: $manifestpath" >&2
    exit 1
fi

extract_string() {
    fieldname="$1"
    sed -n "s/^[[:space:]]*\"$fieldname\":[[:space:]]*\"\([^\"]*\)\"[,]*$/\1/p" "$manifestpath"
}

extract_number() {
    fieldname="$1"
    sed -n "s/^[[:space:]]*\"$fieldname\":[[:space:]]*\([0-9][0-9]*\)[,]*$/\1/p" "$manifestpath"
}

schema="$(extract_string schema)"
artifacttype="$(extract_string type)"
payloadname="$(extract_string payload)"
expectedbytes="$(extract_number bytes)"
expectedsha256="$(extract_string sha256)"

if [ "$schema" != "tool_secure_s3_storage.artifact/v1" ] || [ "$artifacttype" != "database" ]; then
    echo "Unsupported database artifact manifest" >&2
    exit 1
fi

case "$payloadname" in
    moodle-db-[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]T[0-9][0-9][0-9][0-9][0-9][0-9]Z-[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f].sql.gz) ;;
    *)
        echo "Unexpected database backup payload name: $payloadname" >&2
        exit 1
        ;;
esac

case "$expectedbytes" in
    ''|*[!0-9]*)
        echo "Manifest bytes field is invalid" >&2
        exit 1
        ;;
esac

if ! printf '%s\n' "$expectedsha256" | grep -Eq '^[0-9a-f]{64}$'; then
    echo "Manifest sha256 field is invalid" >&2
    exit 1
fi

payloadpath="$artifactdirectory/$payloadname"
if [ ! -f "$payloadpath" ] || [ -L "$payloadpath" ]; then
    echo "Payload is not a regular non-symlink file: $payloadpath" >&2
    exit 1
fi

actualbytes="$(stat -c %s "$payloadpath")"
actualsha256="$(sha256sum "$payloadpath" | cut -d ' ' -f 1)"
if [ "$actualbytes" != "$expectedbytes" ] || [ "$actualsha256" != "$expectedsha256" ]; then
    echo "Database artifact integrity verification failed" >&2
    exit 1
fi

optionfile="$(mktemp /tmp/moodle-db-restore-options.XXXXXX)"
cleanup() {
    rm -f "$optionfile"
}
trap cleanup EXIT HUP INT TERM
chmod 0600 "$optionfile"

cat > "$optionfile" <<EOF
[client]
host=$restorehost
port=$restoreport
user=$restoreuser
password=$restorepassword
protocol=TCP
EOF

gzip -t "$payloadpath"
gzip -dc "$payloadpath" |
    mariadb --defaults-extra-file="$optionfile" "$restoredatabase"

tablecount="$(mariadb --defaults-extra-file="$optionfile" --batch --skip-column-names "$restoredatabase" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'mdl_config';")"
if [ "$tablecount" != "1" ]; then
    echo "Restored database does not contain mdl_config" >&2
    exit 1
fi

moodleversion="$(mariadb --defaults-extra-file="$optionfile" --batch --skip-column-names "$restoredatabase" -e "SELECT value FROM mdl_config WHERE name = 'version' LIMIT 1;")"
if [ -z "$moodleversion" ]; then
    echo "Restored database does not contain a Moodle version" >&2
    exit 1
fi

printf '{"restoreverified":true,"database":"%s","payload":"%s","bytes":%s,"sha256":"%s","moodleversion":"%s"}\n' \
    "$restoredatabase" "$payloadname" "$actualbytes" "$actualsha256" "$moodleversion"
