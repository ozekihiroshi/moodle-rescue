#!/bin/sh
set -eu

endpoint="${S3_ENDPOINT:?S3_ENDPOINT is required}"
accesskey="${S3_ACCESS_KEY_ID:?S3_ACCESS_KEY_ID is required}"
secretkey="${S3_SECRET_ACCESS_KEY:?S3_SECRET_ACCESS_KEY is required}"
bucket="${S3_BUCKET:?S3_BUCKET is required}"
prefix="${S3_PREFIX:?S3_PREFIX is required}"
destination="${DATABASE_ARTIFACT_DESTINATION:-/database-artifacts}"

case "$prefix" in
    /*|*..*|*//*|*\\*) echo "S3_PREFIX is invalid." >&2; exit 1 ;;
esac
prefix="${prefix%/}"

mkdir -p "$destination"
chmod 0750 "$destination"
mc alias set source "$endpoint" "$accesskey" "$secretkey" >/dev/null

searchroot="source/$bucket/$prefix/database/v2"
remotemanifest=""
payload=""
for candidate in $(mc find "$searchroot" --name manifest.json --print '{}'); do
    candidatejson="$(mc cat "$candidate")"
    candidatepayload="${candidatejson#*'"payload":"'}"
    if [ "$candidatepayload" = "$candidatejson" ]; then
        candidatepayload="${candidatejson#*'"payload": "'}"
    fi
    candidatepayload="${candidatepayload%%'"'*}"

    case "$candidatepayload" in
        *[!A-Za-z0-9.-]*|*..*) continue ;;
        moodle-db-????????T??????Z-????????????????????????????????.xml.gz) ;;
        *) continue ;;
    esac
    if [ -z "$payload" ] || expr "$candidatepayload" \> "$payload" >/dev/null; then
        payload="$candidatepayload"
        remotemanifest="$candidate"
    fi
done

case "$remotemanifest" in
    "$searchroot"/*/manifest.json) ;;
    *) echo "No completed remote v2 database artifact manifest was found." >&2; exit 1 ;;
esac

temporarymanifest="$destination/.manifest.partial"
temporarypayload="$destination/.$payload.partial"
mc cp --quiet "$remotemanifest" "$temporarymanifest"
remoteparent="${remotemanifest%/manifest.json}"
mc cp --quiet "$remoteparent/$payload" "$temporarypayload"
chmod 0640 "$temporarypayload" "$temporarymanifest"
mv "$temporarypayload" "$destination/$payload"
mv "$temporarymanifest" "$destination/$payload.manifest.json"

printf '{"downloaded":true,"version":2,"payload":"%s","manifest":"%s"}\n' \
    "$payload" "$payload.manifest.json"
