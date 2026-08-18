#!/bin/sh

set -eu

destination="${DATABASE_ARTIFACT_DESTINATION:-/database-artifacts}"
reader_gid="${DATABASE_BACKUP_READER_GID:-33}"

case "$destination" in
    /*) ;;
    *)
        echo "DATABASE_ARTIFACT_DESTINATION must be absolute." >&2
        exit 1
        ;;
esac

case "$reader_gid" in
    ''|*[!0-9]*)
        echo "DATABASE_BACKUP_READER_GID must be numeric." >&2
        exit 1
        ;;
esac

for commandname in gzip sha256sum stat cut; do
    if ! command -v "$commandname" >/dev/null 2>&1; then
        echo "Required negative-fixture command is unavailable: $commandname" >&2
        exit 1
    fi
done

umask 0027
mkdir -p "$destination"
chmod 0750 "$destination"
chgrp "$reader_gid" "$destination"

malformed_timestamp="20000101T000001Z"
malformed_createdat="2000-01-01T00:00:01Z"
malformed_artifactid="11111111111111111111111111111111"
malformed_shortid="1111111111111111"
malformed_payload="moodle-db-${malformed_timestamp}-${malformed_shortid}.sql.gz"
malformed_manifest="${malformed_payload}.manifest.json"

printf '%s\n' 'malformed manifest rejection fixture' |
    gzip -9 > "$destination/$malformed_payload"
malformed_bytes="$(stat -c '%s' "$destination/$malformed_payload")"
malformed_sha256="$(sha256sum "$destination/$malformed_payload" | cut -d ' ' -f 1)"

cat > "$destination/$malformed_manifest" <<EOF
{
  "schema": "tool_secure_s3_storage.artifact/v1",
  "artifactid": "$malformed_artifactid",
  "type": "database",
  "createdat": "$malformed_createdat",
  "payload": "$malformed_payload",
  "bytes": $malformed_bytes,
  "sha256": "$malformed_sha256",
  "format": "mariadb-sql",
  "compression": "gzip",
  "encryption": "none",
  "recoverysetid": "${malformed_timestamp}-${malformed_shortid}",
  "unexpected": true
}
EOF

corrupt_timestamp="20000101T000002Z"
corrupt_createdat="2000-01-01T00:00:02Z"
corrupt_artifactid="22222222222222222222222222222222"
corrupt_shortid="2222222222222222"
corrupt_payload="moodle-db-${corrupt_timestamp}-${corrupt_shortid}.sql.gz"
corrupt_manifest="${corrupt_payload}.manifest.json"

printf '%s\n' 'corrupt payload rejection fixture' |
    gzip -9 > "$destination/$corrupt_payload"
corrupt_bytes="$(stat -c '%s' "$destination/$corrupt_payload")"

cat > "$destination/$corrupt_manifest" <<EOF
{
  "schema": "tool_secure_s3_storage.artifact/v1",
  "artifactid": "$corrupt_artifactid",
  "type": "database",
  "createdat": "$corrupt_createdat",
  "payload": "$corrupt_payload",
  "bytes": $corrupt_bytes,
  "sha256": "0000000000000000000000000000000000000000000000000000000000000000",
  "format": "mariadb-sql",
  "compression": "gzip",
  "encryption": "none",
  "recoverysetid": "${corrupt_timestamp}-${corrupt_shortid}"
}
EOF

chgrp "$reader_gid" \
    "$destination/$malformed_payload" \
    "$destination/$malformed_manifest" \
    "$destination/$corrupt_payload" \
    "$destination/$corrupt_manifest"
chmod 0640 \
    "$destination/$malformed_payload" \
    "$destination/$malformed_manifest" \
    "$destination/$corrupt_payload" \
    "$destination/$corrupt_manifest"

printf '{"malformedManifest":"%s","corruptPayload":"%s"}\n' \
    "$malformed_manifest" "$corrupt_payload"
