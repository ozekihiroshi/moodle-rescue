# Backup architecture reference deployment

## Status

This document maps the `moodle-rescue` Docker environments to the architectural
contract owned by Secure S3 Storage:

- [Backup and recovery architecture](https://github.com/ozekihiroshi/secure-s3-storage-for-moodle/blob/main/docs/backup-architecture.md)

The linked document is authoritative for product roles, artifact semantics, and
security boundaries. This document is authoritative only for the companion
Docker reference implementation. The database producer and isolated restore gate
are implemented on the development branch; content production remains planned.

## Current implementation

The current environment implements the course-archive path:

```text
Moodle automated backup
  -> moodle_backups named volume
  -> /var/moodlebackups in web and Cron
  -> Secure S3 Storage Cron task
  -> MinIO in local tests or Amazon S3 on EC2
```

The plugin scans stable top-level `.mbz` files. Web and Cron use the same
Moodle database, `moodledata`, immutable application image, and backup volume.
Cron performs the transfer because only Cron has the required scheduled task
and production outbound AWS network.

The complete course archive round trip is covered by
[`integration-test.md`](integration-test.md),
[`release-gate.md`](release-gate.md), and
[`aws-ec2-validation.md`](aws-ec2-validation.md).

## Database reference topology

The current development extension adds a MariaDB producer without giving the
Moodle web or
Cron process a database-owner credential:

```text
                         internal network
                     +----------------------+
                     |                      |
MariaDB <---- database-backup producer      |
                     |                      |
                     +--> artifact_handoff volume --read-only--> Moodle Cron
                                                                  |
                                                                  | instance role
                                                                  v
                                                         Amazon S3 database/
```

The implemented development services and storage boundaries are:

| Component | Network access | Mounted data | Credential | Responsibility |
| --- | --- | --- | --- | --- |
| `moodle` | MariaDB and Traefik | Moodle data and course-backup volume | Moodle application DB credential; no static AWS key | Web application and administrator UI |
| `moodle-cron` | MariaDB and restricted outbound AWS path | Moodle data, course backups, read-only artifact hand-off | Moodle application DB credential and workload AWS identity | Scheduled backup observation, upload, verification, and audit |
| `moodle-db` | Internal only | Database volume | Database service credentials | Live Moodle database |
| `moodle-db-backup` tools job | MariaDB only; no Traefik or AWS path | Write-only operational ownership of artifact hand-off | Current reference: Moodle application DB credential; dedicated dump identity recommended | Consistent dump, gzip compression, manifest publication |
| Isolated restore verifier | Isolated test network | Read-only source artifact and disposable target volumes | Ephemeral target database credential; read-only test storage identity | Verify an empty-database restore without production access |
| MinIO test services | Local test networks only | Test-only object volume | Random test credentials | S3-compatible integration testing |

"Write-only operational ownership" means the producer owns its output directory
and does not require access to payloads from other producers. Unix ownership and
mode checks, rather than the volume declaration alone, enforce this inside the
shared volume.

## Current volume layout

Existing course compatibility remains unchanged:

```text
moodle_backups
  /var/moodlebackups/*.mbz
```

The MariaDB producer uses a separate named volume so database production is not
coupled to the Moodle-generated course directory:

```text
moodle_database_artifacts
  /database-artifacts/
    moodle-db-<UTC timestamp>-<16 hex>.sql.gz
    moodle-db-<UTC timestamp>-<16 hex>.sql.gz.manifest.json
```

The producer mounts `moodle_database_artifacts` read-write. Moodle Cron mounts it
read-only. Moodle web does not mount it unless a future administrator download
feature has a reviewed need for access. Restore downloads use a third,
non-monitored workspace so they cannot be rediscovered as outbound backups.

The exact format is fixed by the plugin repository's
[`database-artifact-v1.md`](https://github.com/ozekihiroshi/secure-s3-storage-for-moodle/blob/main/docs/database-artifact-v1.md).

## Identity and secret boundaries

### Database producer

The current reference producer receives the existing Moodle application DB
credential through Compose runtime environment and writes it to a private
temporary client option file. The fixed producer has no Traefik or AWS network,
but the database credential itself is not read-only. A dedicated dump identity
with only the tested MariaDB grants is the preferred production hardening and
remains a release gate.

No database credential is read from Moodle plugin settings, baked into an
image, placed in a manifest, or passed as a command-line password. The producer
contains no restore or database-write command; Compose supplies the expected
source database.

### Transfer controller

Production Moodle Cron uses the AWS SDK default credential provider chain and
the EC2 instance role. Static AWS keys remain absent from `.env`, Compose, and
Moodle settings. Its IAM policy is restricted to the enabled recovery-domain
prefixes and the object operations required for upload and verification.

The database producer has no AWS identity. This prevents compromise of the dump
process from also granting bucket access. Conversely, Moodle Cron can read a
finished dump but cannot connect with the dump producer's database credential.

### Restore verifier

A restore job uses a disposable target database and must be unable to address
the production database endpoint. Production restore remains an explicit
operator procedure, never an automatic scheduled task or Moodle web action.

### Configuration and encryption keys

`.env`, `config.php`, Traefik state, database service credentials, and
client-side encryption private keys belong to deployment operations. They are
not placed in the artifact manifest, plugin tables, release ZIP, Docker build
context, or Git.

Public templates document variable structure only. A disaster-recovery runbook
must identify the approved encrypted system from which an operator retrieves
the real configuration and keys.

## Database artifact flow

The implemented MariaDB producer follows this sequence:

1. Confirm that the target is the configured Moodle database.
2. Create a consistent logical dump with the selected MariaDB method.
3. Write only to a temporary ineligible path.
4. Compress the dump.
5. Encrypt it when client-side encryption is configured.
6. Close the payload and calculate its ciphertext size and SHA-256.
7. Atomically publish the payload.
8. Atomically publish the secret-free manifest last.
9. Exit non-zero when any step fails and leave no eligible manifest.

Secure S3 Storage then validates the manifest and paths, verifies the local
digest, streams the opaque payload to the database prefix, reads it back for
verification, and records the result. Local removal and remote retention remain
disabled until separate policy and failure-mode tests exist.

## Database restore gate

A database feature is not releasable merely because its object reached MinIO or
S3. The CI gate must:

1. Build or install the released plugin ZIP without a source bind mount.
2. Create representative Moodle state.
3. Generate a database artifact with the reference producer.
4. Transfer and verify it through the plugin.
5. Download it into an unmonitored recovery workspace.
6. Verify the manifest and ciphertext digest.
7. Decrypt and decompress when configured.
8. Import into a new empty MariaDB instance.
9. Start a matching clean Moodle application against the restored database.
10. Verify selected site, plugin, course, and file metadata.
11. Remove only uniquely named CI resources.

The gate must reject wrong checksums, incomplete manifests, unsafe paths,
unsupported versions, a non-empty target database, and any production-like
endpoint.

## Content phase

The first content reference implementation will back up the existing
`moodle_data` file pool; it will not make S3 the live filesystem. Database and
content producers must coordinate a documented consistency point and publish
the same recovery-set identifier.

A later profile will validate the S3 primary-content filesystem implemented by
Secure S3 Storage itself. It will not install or require an external ObjectFS
plugin. The profile will test Moodle File API compatibility, migration,
rollback, cache behavior, primary-bucket failure, database and object
consistency, independent replication, and recovery. The production Compose
profile must not mount S3 as a generic POSIX replacement for all of
`moodledata`.

## Implementation acceptance checklist

Before releasing the MariaDB artifact feature:

- the canonical manifest schema and threat model are stable;
- Compose validation and shell checks pass;
- secrets are absent from images, logs, manifests, and Git;
- producer, Cron, restore, and database networks are demonstrably separated;
- partial and concurrent output cannot become eligible;
- local and remote corruption tests fail closed;
- a released plugin ZIP completes the isolated restore gate;
- production containers and volumes are not reused or cleaned by CI; and
- operations and recovery documentation includes key custody, retention,
  monitoring, and failure procedures.
