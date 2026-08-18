# Database backup and restore gate

## Scope

The database path is under development for Secure S3 Storage 0.3. It is
independent from Moodle course `.mbz` backups. The reference deployment uses a
fixed MariaDB producer to create a compressed logical dump, publishes a bounded
manifest last, and gives only the completed files to Moodle Cron through a
read-only mount.

The Moodle web container does not mount `/database-artifacts`. The plugin does
not receive database credentials, execute dump commands, or restore databases.
Both plugin transfer switches remain disabled by default.

The exact payload and manifest grammar is defined by the plugin repository's
[`database-artifact-v1.md`](https://github.com/ozekihiroshi/secure-s3-storage-for-moodle/blob/main/docs/database-artifact-v1.md).

## Create one artifact

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

Compose does not silently choose a backup schedule. Production operators run
the command above from a reviewed systemd timer, Cron entry, or external job
scheduler and monitor its exit status. Do not overlap producer executions.

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

On 2026-08-18 the local producer generated a 469,318-byte MariaDB artifact. The
plugin transferred the payload and manifest to MinIO, the recovery job
downloaded them into a separate volume, verified SHA-256, restored an isolated
database, and a fresh Moodle container read build `2026042002`.

Before release, the same path still needs a clean plugin ZIP gate, deliberate
invalid-manifest and corruption cases, upgrade validation, a non-overlapping
production schedule, and AWS IAM-separated recovery evidence.
