# Database backup and restore gate

## Scope

Secure S3 Storage 0.4 supports two database producer modes independently from
Moodle course `.mbz` backups. The default built-in mode uses Moodle's existing
database connection and DTL exporter to create a private v2 artifact below
`moodledata`. The advanced external mode uses this deployment's fixed MariaDB
producer to create a native v1 dump and gives only completed files to Moodle
Cron through a read-only mount.

The Moodle web container does not mount `/database-artifacts`. In external
mode, the plugin receives no dump credentials and executes no dump command.
Neither mode permits a web-triggered restore, and database transfer remains
disabled by default.

The exact payload and manifest grammar is defined by the plugin repository's
[`database-artifact-v1.md`](https://github.com/ozekihiroshi/secure-s3-storage-for-moodle/blob/main/docs/database-artifact-v1.md).

## Built-in mode

Select **Built-in Moodle DTL producer** in the plugin settings, confirm the
private directory shown by Moodle, and then enable database transfer. The
scheduled task creates and transfers a v2 artifact without an additional host
scheduler. Restore remains an explicit CLI-only operation into an empty,
isolated database.

## Create one external artifact

Start the normal environment first. Then invoke the producer as an explicit
Compose tools job:

```sh
docker compose --env-file .env --profile tools run --rm moodle-db-backup
```

The job uses `mariadb-dump --single-transaction`, writes a private temporary SQL
file, compresses it, calculates the final SHA-256, publishes the payload, and
atomically publishes the manifest last. The producer has no AWS credentials or
external network. A failed run removes incomplete output.

The current reference job receives the existing Moodle application database
credential from the deployment environment. It runs a fixed script and joins
only the internal network, but that credential is not database read-only. Sites
requiring stronger database-side separation should provision a dedicated dump
identity with only the grants required by the tested MariaDB dump method before
calling this production-ready.

Compose does not silently choose an external backup schedule. Production
operators can run the locked wrapper manually:

```sh
sh scripts/database-backup-now.sh
```

On a reviewed systemd host, install the optional daily timer and inspect it:

```sh
sudo sh scripts/install-database-backup-timer.sh
sh scripts/database-backup-status.sh
```

The installer defaults to `02:17` with a randomized delay. Pass a reviewed
systemd calendar expression as its first argument to change the schedule. To
remove only the units while preserving artifacts and Docker volumes:

```sh
sudo sh scripts/uninstall-database-backup-timer.sh
```

The wrapper uses `flock` to reject overlapping producer executions. A Cron
entry or external scheduler may invoke the same wrapper instead of installing
the supplied timer.

## Enable transfer

In **Site administration > Plugins > Admin tools > Secure S3 Storage**, set:

- Database artifact directory: `/database-artifacts`
- Enable scheduled database artifact transfer: enabled only after testing

The Cron container sees the artifact volume read-only. The scheduled task
uploads the payload below `database/v1/YYYY/MM/DD/<artifactid>/`, verifies it by
reading it back, then uploads and verifies `manifest.json` as the completion
marker. Local artifacts are preserved.

For the local MinIO environment, the repeatable transfer-only check is:

```sh
sh scripts/run-database-transfer-test.sh
```

## Recovery acceptance

The full local gate deliberately downloads from MinIO into a new, unmonitored
volume before restoring:

```sh
sh scripts/run-database-s3-roundtrip-test.sh
```

It performs these checks:

1. Run the plugin database transfer task.
2. Download the latest remote payload and manifest into a unique volume.
3. Verify manifest grammar, byte count, gzip integrity, and SHA-256.
4. Initialize a unique empty MariaDB database and import the dump.
5. Verify the Moodle `mdl_config` table and build number.
6. Start a fresh matching Moodle container and read that restored build number.
7. Remove only the uniquely named test database, network, and volumes.

The standalone local-artifact restore check is:

```sh
sh scripts/run-database-restore-test.sh
```

Neither test can address the running Moodle database. The generated target name
must match `moodle_restore_<16 lowercase hex>` before any import occurs.

## Current evidence and remaining gates

On 2026-08-18 the clean-plugin-ZIP, empty-Moodle gate verified both producer
modes. It rejected malformed v1/v2 manifests and checksum-corrupt payloads,
published no rejected objects, restored a real v1 native dump and a real v2 DTL
artifact into separate empty MariaDB instances, and read Moodle build
`2026042002` through fresh release images.

The remaining production evidence is a reviewed non-overlapping schedule and
an AWS IAM-separated v2 download and isolated recovery rehearsal. Do not use a
successful transfer alone as evidence that a backup is recoverable.
