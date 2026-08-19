#!/bin/sh
set -eu

endpoint="${S3_ENDPOINT:?S3_ENDPOINT is required}"
accesskey="${S3_ACCESS_KEY_ID:?S3_ACCESS_KEY_ID is required}"
secretkey="${S3_SECRET_ACCESS_KEY:?S3_SECRET_ACCESS_KEY is required}"
bucket="${S3_BUCKET:?S3_BUCKET is required}"
prefix="${S3_PREFIX:?S3_PREFIX is required}"
destination="${CONTENT_RECOVERY_DESTINATION:-/content-recovery}"
readergid="${CONTENT_RECOVERY_READER_GID:-33}"

case "$readergid" in
    ''|*[!0-9]*) echo "CONTENT_RECOVERY_READER_GID is invalid." >&2; exit 1 ;;
esac
case "$prefix" in
    /*|*..*|*//*|*\\*) echo "S3_PREFIX is invalid." >&2; exit 1 ;;
esac
prefix="${prefix%/}"

mkdir -p "$destination" "$destination/objects"
chmod 0750 "$destination" "$destination/objects"
mc alias set source "$endpoint" "$accesskey" "$secretkey" >/dev/null

searchroot="source/$bucket/$prefix/content/v1/recovery-sets"
remotemanifest=""
recoverysetid=""
for candidate in $(mc find "$searchroot" --name manifest.json --print '{}'); do
    candidatejson="$(mc cat "$candidate")"
    candidateid="${candidatejson#*'"recoverysetid":"'}"
    if [ "$candidateid" = "$candidatejson" ]; then
        candidateid="${candidatejson#*'"recoverysetid": "'}"
    fi
    candidateid="${candidateid%%'"'*}"
    case "$candidateid" in
        ????????T??????Z-????????????????????????????????) ;;
        *) continue ;;
    esac
    if [ -z "$recoverysetid" ] || expr "$candidateid" \> "$recoverysetid" >/dev/null; then
        recoverysetid="$candidateid"
        remotemanifest="$candidate"
    fi
done

case "$remotemanifest" in
    "$searchroot"/*/manifest.json) ;;
    *) echo "No completed remote content recovery manifest was found." >&2; exit 1 ;;
esac

inventory="moodle-content-$recoverysetid.jsonl.gz"
temporarymanifest="$destination/.manifest.partial"
temporaryinventory="$destination/.inventory.partial"
remoteparent="${remotemanifest%/manifest.json}"
mc cp --quiet "$remotemanifest" "$temporarymanifest"
mc cp --quiet "$remoteparent/inventory.jsonl.gz" "$temporaryinventory"

objectroot="source/$bucket/$prefix/content/v1/objects"
mc cp --quiet --recursive "$objectroot/" "$destination/objects/"

chmod 0640 "$temporarymanifest" "$temporaryinventory"
mv "$temporaryinventory" "$destination/$inventory"
mv "$temporarymanifest" "$destination/$inventory.manifest.json"
chmod 0750 "$destination" "$destination/objects"
chmod 0640 "$destination/$inventory" "$destination/$inventory.manifest.json"
for firstlevel in "$destination/objects"/*; do
    [ -d "$firstlevel" ] || continue
    chmod 0750 "$firstlevel"
    for secondlevel in "$firstlevel"/*; do
        [ -d "$secondlevel" ] || continue
        chmod 0750 "$secondlevel"
        for object in "$secondlevel"/*; do
            [ -f "$object" ] || continue
            chmod 0640 "$object"
        done
    done
done
chown -R "0:$readergid" "$destination"

printf '{"contentDownloaded":true,"recoverysetid":"%s","inventory":"%s"}\n' \
    "$recoverysetid" "$inventory"
