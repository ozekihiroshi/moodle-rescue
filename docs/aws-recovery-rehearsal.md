# AWS isolated recovery rehearsal

This procedure downloads one exact Secure S3 Storage database/content recovery
set from Amazon S3 through the EC2 instance role and restores it into disposable
Docker resources. It never accepts static AWS access keys.

The rehearsal does not connect its temporary MariaDB to the production Moodle
network, publish a web port, or mount the production database or Moodle data
volumes. The S3 fetch container has only the existing outbound `aws_access`
network and two empty host-temporary destinations. The restore helper starts
on a randomly named internal bootstrap network and is connected only to another
randomly named restore network during verification.

## Prerequisites

- the production `moodle`, `moodle-cron`, and `moodle-db` services are healthy;
- the immutable production Moodle image contains the released plugin ZIP;
- the configured EC2 instance role can read the dedicated S3 prefix;
- the selected v2 database artifact has a completed, matching content recovery
  manifest; and
- the host has enough temporary disk space for one database payload and the
  referenced content objects.

Run the rehearsal with the exact recovery-set identifier printed by the content
transfer task:

```sh
sh scripts/run-aws-recovery-test.sh \
  20260819T080133Z-0926e8941cfda67fe2c282f322d36624
```

The fetcher derives exact object keys from the validated identifier. It validates
both manifests, the database SHA-256, inventory SHA-256, inventory ordering and
totals, S3 content metadata, each file's SHA-256 and Moodle SHA-1, and the shared
recovery-set identifier before restore begins.

The restore gate then creates random database credentials, imports the DTL
database into a new MariaDB container, proves a corrupt inventory is rejected,
reconstructs an empty isolated `filedir`, and reads a representative non-empty
object through Moodle's File API. Temporary containers, networks, volumes, and
download directories are removed on success and failure.

Successful final messages include:

```text
"databaseDtlRestoreGate":true
"contentFileApiRestoreGate":true
AWS recovery set ... passed the isolated restore rehearsal.
```

This is a recovery rehearsal, not a production restore command. Production
recovery remains an explicit administrator-controlled incident procedure.
