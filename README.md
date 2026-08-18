# Moodle Rescue

Docker environments for developing and validating Secure S3 Storage for
Moodle.

## Production plugin policy

A conventional Moodle installation may install and uninstall plugin ZIP files
through the administration interface; port 8085 exists to test that workflow.
The production-shaped Compose environment deliberately prevents the web process
from changing application code. All additional plugins are instead declared in
the tracked `plugins.lock`, downloaded over HTTPS, verified against a pinned
SHA-256, inspected, and copied into one immutable Moodle image shared by the web
and Cron services.

`plugins.lock` is the complete desired set of additional plugins, not a change
log. Secure S3 Storage is the initial entry. Add one line below it for every
additional plugin, then run:

```sh
sh scripts/deploy.sh
```

The deploy script synchronizes and validates the declared ZIP files before
Docker Compose rebuilds or recreates any production-shaped service.

The development order and the reason for each step are maintained in the
[`Secure S3 Storage roadmap`](https://github.com/ozekihiroshi/secure-s3-storage-for-moodle/blob/main/docs/roadmap.md).
This repository's Docker responsibilities are documented in
[`docs/backup-architecture.md`](docs/backup-architecture.md), with the detailed
product boundary in the
[`Secure S3 Storage architecture`](https://github.com/ozekihiroshi/secure-s3-storage-for-moodle/blob/main/docs/backup-architecture.md).
Database, content backup, and native S3 primary storage remain design targets;
the current Compose services implement only the course-archive path.

## Separation rules

- `docker-compose.local.yml` exposes Moodle on `127.0.0.1:8083`, includes
  MinIO, and read-only bind mounts the plugin source.
- `docker-compose.yml` is the production-shaped Traefik configuration. It
  never bind mounts plugin source and never contains static AWS credentials.
- `docker-compose.release-test.yml` installs only a generated plugin ZIP. It
  has independent containers and volumes and never inherits the local source
  bind.
- `docker-compose.ui-test.yml` exposes an empty conventional Moodle on
  `127.0.0.1:8085` for web ZIP installation and uninstall lifecycle testing.
- Every environment has its own Compose project, container names, and volumes.

## Environment templates

Choose the template for the environment being started. The generated files
contain secrets and remain ignored by Git.

| Purpose | Template | Runtime file |
| --- | --- | --- |
| Local Moodle and MinIO development | `.env.example` | `.env` |
| ZIP installation and restore gate | `.env.release-test.example` | `.env.release-test` |
| Web ZIP install and uninstall test | `.env.ui-test.example` | `.env.ui-test` |
| EC2, Traefik, and AWS S3 | `.env.production.example` | `.env` |

The release-test file contains only release-specific overrides and is loaded
after the local `.env`. The production template deliberately contains no
MinIO or static AWS credential variables. The AWS SDK uses the EC2 instance
role credential provider instead.

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
  moodle runuser -u www-data -- php < scripts/create-integration-course.php
```

The corresponding verification script locates a restored course containing
the marker while excluding the source course. The complete tested workflow and
latest local result are documented in
[`docs/integration-test.md`](docs/integration-test.md).

## ZIP release gate

`docker-compose.release-test.yml` builds a plugin ZIP from the clean plugin
repository `HEAD`, installs it into an image, and initializes an independent
Moodle environment on <http://localhost:8084>. It never bind mounts the plugin
source. The plugin-owned release builder installs the dependencies pinned by its
`composer.lock` and includes them in the ZIP. The release image requires the
bundled `vendor/autoload.php` and does not receive the source environment's
external AWS SDK override. The Docker build context is allowlisted so ignored
secrets such as `.env` are not sent to the builder.

The release environment reuses only the running local MinIO service and its
least-privilege identity through the existing internal Docker network. Build
and test commands plus the latest result are documented in
[`docs/release-gate.md`](docs/release-gate.md).

## Web installer lifecycle test

`docker-compose.ui-test.yml` provides a third, independent local Moodle on
<http://localhost:8085>. It contains no plugin source bind mount and no
preinstalled Secure S3 Storage ZIP. Unlike the immutable release and production
images, its administration-tool directory is writable by the web process so a
Moodle administrator can test the conventional ZIP upload, installation, and
uninstall workflow.

The 8083 development environment and 8084 release gate are not reused or
modified. Preparation, start, stop, restart, removal, and reset commands are in
[`docs/ui-lifecycle-test.md`](docs/ui-lifecycle-test.md).

## EC2 production-shaped environment

The EC2 host requires Docker, `curl`, `unzip`, and `sha256sum`. On
Ubuntu, `sha256sum` is supplied by `coreutils`; install missing fetch tools
with `sudo apt-get install curl unzip`.

Copy the production template, replace every `CHANGE_ME` value, and set
`MOODLE_HOST` to the public hostname and `TRAEFIK_NETWORK` to the existing
external Traefik Docker network:

```sh
cp .env.production.example .env
chmod 600 .env
sh scripts/deploy.sh
```

`scripts/deploy.sh` performs the production plugin workflow in order:

1. `scripts/sync-plugins.sh` reads `plugins.lock`.
2. Each HTTPS ZIP is downloaded and its pinned SHA-256 is verified.
3. ZIP paths, the single root directory, duplicate entries, symbolic links,
   Moodle component, and approved destination are checked.
4. Verified ZIPs and their generated install manifest are staged below
   `build/plugin-zips/` and `build/plugins.install`.
5. Docker Compose validates its configuration and rebuilds the immutable image.
6. The web and Cron services are recreated from the same image.
7. Moodle's non-interactive CLI upgrade runs when the database is installed.

To add another plugin, append one complete line to `plugins.lock` using the
commented field order:

```text
component|version|destination below public/|HTTPS ZIP URL|SHA-256
```

Do not remove existing lines when adding a plugin. To update a plugin, replace
that component's version, URL, and SHA-256 on its existing line, then run the
same deploy command.

Removing a plugin requires the reverse lifecycle. Uninstall it from the Moodle
database first, remove its line from `plugins.lock`, and then redeploy:

```sh
docker compose --env-file .env exec -T moodle \
  runuser -u www-data -- php admin/cli/uninstall_plugins.php \
  --plugins=component_name --run
sh scripts/deploy.sh
```

Never remove the manifest line or rebuild the image before the database
uninstall. Doing so leaves Moodle reporting the plugin as missing from disk.

Do not add AWS access keys to `.env` or Moodle settings. Attach the
least-privilege S3 policy to the EC2 instance role. The production Compose
configuration derives Moodle's canonical HTTPS URL from `MOODLE_HOST`.

The web and Cron services use the same root-owned image, so plugins survive
container replacement and scheduled tasks see the identical code. Cron uses a
dedicated outbound network for EC2 instance metadata and S3 access; it does not
join the shared Traefik network. Do not make Moodle's plugin directories
writable or use the web installer for this deployment.

For a fresh database, the deploy script starts the services and skips the CLI
upgrade. Complete Moodle's normal web installation, then run the deploy script
again. `scripts/build-plugin-zip.sh` remains available only for local plugin
development and CI.

The validated EC2 instance-role policy, Docker network boundary, Amazon S3
round-trip result, and evidence-retention policy are recorded in
[`docs/aws-ec2-validation.md`](docs/aws-ec2-validation.md).

`scripts/run-release-gate-ci.sh` automates the complete source-backup-transfer
and empty-environment restore path. It creates random ephemeral credentials and
unique Compose projects, then removes only its CI-specific containers and
volumes. Existing 8083 and 8084 development environments are not reused.
