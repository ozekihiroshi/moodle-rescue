#!/bin/sh
set -eu

repositoryroot="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$repositoryroot"

pluginrepo="${PLUGIN_REPOSITORY:-../secure-s3-storage-for-moodle}"
pluginrepo="$(cd "$pluginrepo" && pwd)"

for command in docker git unzip awk sed grep sha256sum openssl curl; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required CI command is unavailable: $command" >&2
        exit 1
    fi
done

if [ -n "$(git -C "$pluginrepo" status --porcelain)" ]; then
    echo "Plugin repository must be clean for the release gate." >&2
    exit 1
fi

runid="${GITHUB_RUN_ID:-local-$$}"
runid="$(printf '%s' "$runid" | tr -cd 'a-zA-Z0-9-')"
sourceproject="moodle-rescue-ci-source-$runid"
releaseproject="moodle-rescue-ci-release-$runid"
sourceprefix="moodle-rescue-ci-source-$runid"
releaseprefix="moodle-rescue-ci-release-$runid"
sourceimage="$sourceproject-moodle"
releaseimage="$releaseproject-moodle"
localport="${CI_LOCAL_MOODLE_PORT:-18083}"
releaseport="${CI_RELEASE_MOODLE_PORT:-18084}"
envfile="$(mktemp)"

dbpassword="$(openssl rand -hex 24)"
dbrootpassword="$(openssl rand -hex 24)"
miniopassword="$(openssl rand -hex 24)"
s3password="$(openssl rand -hex 24)"
releasedbpassword="$(openssl rand -hex 24)"
releasedbrootpassword="$(openssl rand -hex 24)"
adminpassword="$(openssl rand -hex 24)Aa1!"

cat > "$envfile" <<EOF
LOCAL_COMPOSE_PROJECT_NAME=$sourceproject
LOCAL_CONTAINER_PREFIX=$sourceprefix
LOCAL_MOODLE_PORT=$localport
LOCAL_MINIO_API_PORT=19002
LOCAL_MINIO_CONSOLE_PORT=19003
PLUGIN_SOURCE_PATH=$pluginrepo
MOODLE_WWWROOT=http://localhost:$localport
MOODLE_DB_NAME=moodle_ci
MOODLE_DB_USER=moodle_ci
MOODLE_DB_PASSWORD=$dbpassword
MOODLE_DB_ROOT_PASSWORD=$dbrootpassword
MINIO_ROOT_USER=moodle-ci-root
MINIO_ROOT_PASSWORD=$miniopassword
MINIO_BUCKET=moodle-backups
S3_ACCESS_KEY_ID=moodle-ci-writer
S3_SECRET_ACCESS_KEY=$s3password
RELEASE_COMPOSE_PROJECT_NAME=$releaseproject
RELEASE_CONTAINER_PREFIX=$releaseprefix
RELEASE_MOODLE_PORT=$releaseport
RELEASE_MOODLE_IMAGE=$releaseimage
MOODLE_BASE_IMAGE=$sourceimage
SOURCE_MINIO_NETWORK=${sourceproject}_internal
RELEASE_TEST_DATABASE_ARTIFACT_VOLUME=${sourceproject}_moodle_database_artifacts
RELEASE_TEST_DB_NAME=moodle_release_ci
RELEASE_TEST_DB_USER=moodle_release_ci
RELEASE_TEST_DB_PASSWORD=$releasedbpassword
RELEASE_TEST_DB_ROOT_PASSWORD=$releasedbrootpassword
RELEASE_TEST_ADMIN_PASSWORD=$adminpassword
RELEASE_TEST_WWWROOT=http://localhost:$releaseport
RELEASE_TEST_OBJECT_KEY=pending
EOF
chmod 0600 "$envfile"

source_compose() {
    docker compose --env-file "$envfile" -f docker-compose.local.yml "$@"
}

release_compose() {
    docker compose --env-file "$envfile" -f docker-compose.release-test.yml "$@"
}

cleanup() {
    status=$?
    trap - EXIT HUP INT TERM

    if [ "$status" -ne 0 ]; then
        echo "Release gate failed; collecting container state." >&2
        release_compose ps >&2 || true
        release_compose logs --no-color --tail=120 >&2 || true
        source_compose ps >&2 || true
        source_compose logs --no-color --tail=120 >&2 || true
    fi

    release_compose --profile tools down --volumes --remove-orphans >/dev/null 2>&1 || true
    source_compose --profile tools down --volumes --remove-orphans >/dev/null 2>&1 || true
    rm -f "$envfile"
    exit "$status"
}
trap cleanup EXIT HUP INT TERM

set_plugin_config() {
    source_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
        --name="$1" --set="$2" >/dev/null
}

source_moodle_php() {
    source_compose exec -T moodle \
        runuser -u www-data -- php "$@"
}

release_moodle_php() {
    release_compose exec -T moodle-release \
        runuser -u www-data -- php "$@"
}

wait_for_source_moodle() {
    attempt=0
    until source_compose exec -T moodle \
        runuser -u www-data -- php -r \
        'exit(is_readable("/var/www/html/config.php") ? 0 : 1);' \
        >/dev/null 2>&1; do
        attempt=$((attempt + 1))
        if [ "$attempt" -ge 30 ]; then
            echo "Source Moodle entrypoint did not become ready." >&2
            exit 1
        fi
        sleep 2
    done
}

wait_for_release_moodle() {
    attempt=0
    until release_compose exec -T moodle-release \
        runuser -u www-data -- php -r \
        'exit(is_readable("/var/www/html/config.php") ? 0 : 1);' \
        >/dev/null 2>&1; do
        attempt=$((attempt + 1))
        if [ "$attempt" -ge 30 ]; then
            echo "Release Moodle entrypoint did not become ready." >&2
            exit 1
        fi
        sleep 2
    done
}

echo "Building and installing the source-bound integration environment."
source_compose config --quiet
source_compose up -d --build
echo "Waiting for the source Moodle entrypoint."
wait_for_source_moodle
echo "Installing the source Moodle database."
source_moodle_php admin/cli/install_database.php \
    --lang=en \
    --adminuser=admin \
    --adminpass="$adminpassword" \
    --adminemail=admin@example.invalid \
    --fullname="Moodle CI Source" \
    --shortname="CISource" \
    --agree-license

coursejson="$(
    source_compose exec -T \
        -e S3_TEST_COURSE_SHORTNAME=S3INT-CI \
        -e 'S3_TEST_COURSE_FULLNAME=Secure S3 Integration Test (CI)' \
        -e S3_TEST_CONTENT_MARKER=secure-s3-integration-marker-v1 \
        moodle runuser -u www-data -- php \
        < scripts/create-integration-course.php
)"
courseid="$(
    printf '%s\n' "$coursejson" |
        awk 'match($0, /"courseid":"?[0-9]+"?/) {
            value = substr($0, RSTART, RLENGTH)
            gsub(/[^0-9]/, "", value)
            print value
            exit
        }'
)"
case "$courseid" in
    ''|*[!0-9]*) echo "Unable to determine the fixture course ID." >&2; exit 1 ;;
esac

source_moodle_php admin/cli/backup.php \
    --courseid="$courseid" --destination=/var/moodlebackups

set_plugin_config region ap-northeast-1
set_plugin_config bucket moodle-backups
set_plugin_config prefix moodle/
set_plugin_config sourcedirectory /var/moodlebackups
set_plugin_config stabilityseconds 1
set_plugin_config transferenabled 1

source_moodle_php admin/cli/scheduled_task.php \
    --execute='\tool_secure_s3_storage\task\transfer_course_backups'
sleep 2
source_moodle_php admin/cli/scheduled_task.php \
    --execute='\tool_secure_s3_storage\task\transfer_course_backups'

backuphash="$(
    source_compose exec -T moodle sh -c \
        'find /var/moodlebackups -maxdepth 1 -type f -name "*.mbz" -exec sha256sum {} \;' |
        awk 'NR == 1 { print $1 }'
)"
if ! printf '%s\n' "$backuphash" | grep -Eq '^[0-9a-f]{64}$'; then
    echo "Unable to determine the Moodle backup SHA-256." >&2
    exit 1
fi
objectkey="moodle/v1/$(printf '%.2s' "$backuphash")/$backuphash.mbz"
sed -i "s|^RELEASE_TEST_OBJECT_KEY=.*|RELEASE_TEST_OBJECT_KEY=$objectkey|" "$envfile"

echo "Building the clean plugin ZIP and isolated release image."
PLUGIN_REPOSITORY="$pluginrepo" sh scripts/build-plugin-zip.sh
release_compose config --quiet
release_compose build --no-cache moodle-release
release_compose up -d --no-build
echo "Waiting for the release Moodle entrypoint."
wait_for_release_moodle

echo "Verifying initial disabled state, MinIO retrieval, and restoration."
release_moodle_php admin/cli/scheduled_task.php \
    --execute='\tool_secure_s3_storage\task\transfer_course_backups'

release_compose stop moodle-release-cron >/dev/null

release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=region --set=ap-northeast-1 >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=bucket --set=moodle-backups >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=prefix --set=moodle/ >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=databaseartifactdirectory --set=/database-artifacts >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=databasetransferenabled --set=1 >/dev/null

echo "Verifying fail-closed handling for malformed manifests and corrupt payloads."
release_compose --profile tools run --rm --no-deps \
    release-db-negative-fixtures

negativefirst="$(
    release_compose --profile tools run --rm --no-deps \
        moodle-release-negative-db-test \
        runuser -u www-data -- php admin/cli/scheduled_task.php \
        --execute='\tool_secure_s3_storage\task\transfer_database_backups' 2>&1
)"
printf '%s\n' "$negativefirst"
if ! printf '%s\n' "$negativefirst" |
        grep -F 'Rejected invalid database artifact manifest' >/dev/null; then
    echo "Malformed database manifest was not explicitly rejected." >&2
    exit 1
fi
if ! printf '%s\n' "$negativefirst" |
        grep -F 'database artifact(s) were observed and will be retried.' >/dev/null; then
    echo "Checksum-corrupt database payload was not safely observed before transfer." >&2
    exit 1
fi

negativesecond="$(
    release_compose --profile tools run --rm --no-deps \
        moodle-release-negative-db-test \
        runuser -u www-data -- php admin/cli/scheduled_task.php \
        --execute='\tool_secure_s3_storage\task\transfer_database_backups' 2>&1
)"
printf '%s\n' "$negativesecond"
if ! printf '%s\n' "$negativesecond" |
        grep -F 'Database artifact transfer failed for' >/dev/null; then
    echo "Checksum-corrupt database payload did not produce the expected failure." >&2
    exit 1
fi
if ! printf '%s\n' "$negativesecond" |
        grep -F 'local artifacts were preserved' >/dev/null; then
    echo "Checksum rejection did not confirm local artifact preservation." >&2
    exit 1
fi

negativeaudit="$(
    release_moodle_php -r '
define("CLI_SCRIPT", true);
require "/var/www/html/config.php";

$malformed = $DB->count_records(
    "tool_secure_s3_storage_xfer",
    ["filename" => "moodle-db-20000101T000001Z-1111111111111111.sql.gz.manifest.json"]
);
$corrupt = $DB->get_record(
    "tool_secure_s3_storage_xfer",
    ["filename" => "moodle-db-20000101T000002Z-2222222222222222.sql.gz.manifest.json"],
    "status, errormessage",
    IGNORE_MISSING
);
echo $malformed, ":",
    ($corrupt->status ?? ""), ":",
    ($corrupt->errormessage ?? ""), PHP_EOL;
' | tr -d '\r\n'
)"
if [ "$negativeaudit" != "0:failed:RuntimeException" ]; then
    echo "Database rejection audit state is invalid: $negativeaudit" >&2
    exit 1
fi

release_compose --profile tools run --rm --no-deps \
    --entrypoint /bin/sh moodle-release-negative-db-test \
    -c 'test -f /database-artifacts/moodle-db-20000101T000001Z-1111111111111111.sql.gz.manifest.json &&
        test -f /database-artifacts/moodle-db-20000101T000002Z-2222222222222222.sql.gz'
if release_compose --profile tools run --rm --no-deps release-db-fetch; then
    echo "A rejected database artifact unexpectedly published a completion manifest." >&2
    exit 1
fi

echo "Producing a real source database artifact for the ZIP-install release gate."
source_compose --profile tools run --rm moodle-db-backup

release_compose --profile tools run --rm --no-deps \
    moodle-release-cron \
    runuser -u www-data -- php admin/cli/scheduled_task.php \
    --execute='\tool_secure_s3_storage\task\transfer_database_backups'
release_compose --profile tools run --rm --no-deps \
    moodle-release-cron \
    runuser -u www-data -- php admin/cli/scheduled_task.php \
    --execute='\tool_secure_s3_storage\task\transfer_database_backups'

echo "Fetching and restoring the database artifact into a separate empty database."
release_compose --profile tools run --rm --no-deps release-db-fetch
DATABASE_ARTIFACT_VOLUME="${releaseproject}_release_database_downloads" \
MOODLE_RESTORE_TEST_IMAGE="$releaseimage" \
    sh scripts/run-database-restore-test.sh

release_compose --profile tools run --rm --no-deps release-fetch

downloadhash="$(
    release_compose exec -T moodle-release \
        sha256sum /var/moodlebackups/release-gate-source.mbz |
        awk 'NR == 1 { print $1 }'
)"
if [ "$downloadhash" != "$backuphash" ]; then
    echo "Downloaded backup SHA-256 does not match the Moodle source." >&2
    exit 1
fi

release_compose --profile tools run --rm --no-deps release-restore
release_compose --profile tools run --rm --no-deps release-restore

toparchive="$(
    release_compose exec -T moodle-release sh -c \
        'find /var/moodlebackups -maxdepth 1 -type f -name "*.mbz" -print -quit'
)"
if [ -n "$toparchive" ]; then
    echo "A restored backup remained in the monitored top-level directory." >&2
    exit 1
fi

release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=region --set=ap-northeast-1 >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=bucket --set=moodle-backups >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=prefix --set=moodle/ >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=sourcedirectory --set=/var/moodlebackups >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=stabilityseconds --set=1 >/dev/null
release_moodle_php admin/cli/cfg.php --component=tool_secure_s3_storage \
    --name=transferenabled --set=1 >/dev/null
release_moodle_php admin/cli/scheduled_task.php \
    --execute='\tool_secure_s3_storage\task\transfer_course_backups'

release_moodle_php admin/cli/upgrade.php --non-interactive

sourceversionhash="$(sha256sum "$pluginrepo/version.php" | awk '{ print $1 }')"
installedversionhash="$(
    release_compose exec -T moodle-release \
        sha256sum /var/www/html/public/admin/tool/secure_s3_storage/version.php |
        awk 'NR == 1 { print $1 }'
)"
if [ "$installedversionhash" != "$sourceversionhash" ]; then
    echo "Installed plugin version.php differs from the committed source." >&2
    exit 1
fi

bindmounts="$(
    docker inspect "$releaseprefix" --format \
        '{{range .Mounts}}{{if eq .Type "bind"}}{{println .Source}}{{end}}{{end}}'
)"
if [ -n "$bindmounts" ]; then
    echo "Release Moodle unexpectedly contains a bind mount: $bindmounts" >&2
    exit 1
fi

cronbindmounts="$(
    docker inspect "$releaseprefix-cron" --format \
        '{{range .Mounts}}{{if eq .Type "bind"}}{{println .Source}}{{end}}{{end}}'
)"
if [ -n "$cronbindmounts" ]; then
    echo "Release Cron unexpectedly contains a bind mount: $cronbindmounts" >&2
    exit 1
fi
databaseartifactmount="$(
    docker inspect "$releaseprefix-cron" --format \
        '{{range .Mounts}}{{if eq .Destination "/database-artifacts"}}{{.Type}}:{{.RW}}{{end}}{{end}}'
)"
if [ "$databaseartifactmount" != "volume:false" ]; then
    echo "Release Cron database artifact hand-off is not a read-only volume." >&2
    exit 1
fi

attempt=0
until curl --fail --silent --output /dev/null \
    "http://127.0.0.1:$releaseport/login/index.php"; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "Release Moodle did not become ready." >&2
        exit 1
    fi
    sleep 2
done

echo "Release gate passed for plugin commit $(git -C "$pluginrepo" rev-parse HEAD)."
echo "Verified object: $objectkey"
