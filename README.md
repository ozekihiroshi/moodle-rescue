# Moodle Rescue

Docker environments for developing and validating Secure S3 Storage for
Moodle.

## Separation rules

- `docker-compose.local.yml` exposes Moodle on `127.0.0.1:8083`, includes
  MinIO, and read-only bind mounts the plugin source.
- `docker-compose.yml` is the production-shaped Traefik configuration. It
  never bind mounts plugin source and never contains static AWS credentials.
- `docker-compose.release-test.yml` installs only a generated plugin ZIP. It
  has independent containers and volumes and never inherits the local source
  bind.
- Every environment has its own Compose project, container names, and volumes.

## Local prerequisites

Copy `.env.example` to `.env` and replace every `CHANGE_ME` value. Keep `.env`
out of version control. The MinIO credentials are local test credentials only;
do not reuse real AWS credentials.

```sh
cp .env.example .env
docker compose -f docker-compose.local.yml config
docker compose -f docker-compose.local.yml up -d --build
```

Open Moodle at <http://localhost:8083> and MinIO at
<http://localhost:9003>. Moodle uses its standard installation flow against
the preconfigured MariaDB connection.

Moodle 5.2 stores web-exposed plugin code below `public/`. The local source is
therefore mounted at:

```text
/var/www/html/public/admin/tool/secure_s3_storage
```

The local environment builds the AWS SDK into the Moodle image, provisions a
dedicated MinIO bucket and least-privilege writer identity, shares
`/var/moodlebackups` between the web and Cron containers, and connects the
plugin scheduled task to MinIO. Credentials and the MinIO endpoint are supplied
only at container runtime; they are not Moodle plugin settings.

The integration path verifies streamed upload, remote read-back, SHA-256 and
size equality, transfer history, and duplicate suppression. A successful
transfer deliberately leaves the source `.mbz` in `/var/moodlebackups`.

## Reproducible course fixture

The CLI fixture creates a minimal course and Page activity through Moodle APIs.
Run it from the repository root while the local environment is running:

```sh
docker compose -f docker-compose.local.yml exec -T \
  -e S3_TEST_COURSE_SHORTNAME=S3INT-CLI \
  -e 'S3_TEST_COURSE_FULLNAME=Secure S3 Integration Test (CLI)' \
  -e S3_TEST_CONTENT_MARKER=secure-s3-integration-marker-v1 \
  moodle php /dev/stdin < scripts/create-integration-course.php
```

The corresponding verification script locates a restored course containing
the marker while excluding the source course. The complete tested workflow and
latest local result are documented in
[`docs/integration-test.md`](docs/integration-test.md).

## ZIP release gate

`docker-compose.release-test.yml` builds a plugin ZIP from the clean plugin
repository `HEAD`, installs it into an image, and initializes an independent
Moodle environment on <http://localhost:8084>. It never bind mounts the plugin
source. The Docker build context is allowlisted so ignored secrets such as
`.env` are not sent to the builder.

The release environment reuses only the running local MinIO service and its
least-privilege identity through the existing internal Docker network. Build
and test commands plus the latest result are documented in
[`docs/release-gate.md`](docs/release-gate.md).

`scripts/run-release-gate-ci.sh` automates the complete source-backup-transfer
and empty-environment restore path. It creates random ephemeral credentials and
unique Compose projects, then removes only its CI-specific containers and
volumes. Existing 8083 and 8084 development environments are not reused.
