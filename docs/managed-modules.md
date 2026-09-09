# UI-managed activity modules (implementation candidate)

Local storage, administrator-upgrade, recreation and full recovery gates have
passed. The original immutable image mode remains the default. AWS migration
requires a production snapshot and a reviewed deployment change. The first AWS
migration completed on 2026-09-09 as recorded below; plugin ZIP upgrade is a
separate step.

## Boundary

Use `docker-compose.yml` together with `docker-compose.managed-modules.yml`.
Set the site-local `MANAGED_MOODLE_IMAGE` to the exact tested image ID/digest.
The same repository files are used locally and on AWS; credentials, domains,
and the selected image are deployment configuration, not plugin source changes.

A one-shot, network-isolated initializer copies the image's entire `public/mod`
directory into a new, empty named volume. It never overwrites initialized code.
Web mounts the volume read-write; Cron mounts it read-only. The activity-type
parent directory and LessonMark tree are writable by `www-data`. Moodle core,
configuration, and `admin/tool` are not made writable by this feature.

The writable parent is required by Moodle's replace/rename installer. It also
means a compromised Web process can rename activity directories, even when
their contents retain root ownership. This is an explicit security tradeoff
against immutable deployment, not an equivalent security boundary. Do not
expose installer rights to ordinary teachers. Do not use a plugin-directory
bind mount to a Git checkout or mount only the LessonMark directory: uninstall
and rename operations must not act on a repository or mount point.

The volume contains standard activity modules too. The SHA-256 of the image's
core `public/version.php` is recorded; startup refuses a different core version.
This is a version guard, not a complete integrity signature. Moodle core image
upgrades require a separate reviewed migration of standard modules, with a
backup of managed plugins. Recreating containers with the same pinned image
does not reset UI-installed LessonMark. Image `plugins.lock` entries become
initial seeds for managed activity modules, not their current installed state.
Record each uploaded ZIP's release, build, source commit and checksum separately.

## Required release gates

Run the storage contract test in local WSL with the existing UI-test image:

```sh
sh scripts/test-managed-modules.sh
```

It starts only uniquely named `lessonmark-managed-check-*` containers without
ports, networks or a database. Those containers are removed afterwards; its two
labelled, test-only volumes remain for inspection. Existing Moodle containers
and course data are not mounted or changed. This is not a replacement for the
actual administrator UI upgrade gate.

1. Empty-volume initialization; repeat initialization preserves changed code.
2. Web update/rename permissions; Cron reads the same code but cannot write.
3. Both containers recreated: updated code is retained.
4. Nonempty partial initialization and mismatched core version fail closed.
5. Actual Moodle administrator ZIP upgrade with existing content and images.
6. Consistent backup/restore of DB, moodledata, managed code, image and config.

## Migration procedure (do not run before local gates pass)

### Storage contract evidence — 2026-09-09

The operator ran `scripts/test-managed-modules.sh` in local WSL. It reported
PASS for initialization, Web rename/write permissions, shared code, read-only
Cron, persistence across recreation, core-version mismatch rejection and
nonempty partial-seed rejection. The rejection messages are expected negative
test results, not failures. Retained evidence volumes:

- `lessonmark-managed-check-20260909T012808Z-1683244-code`
- `lessonmark-managed-check-20260909T012808Z-1683244-partial`

This storage-only test did not establish an administrator ZIP upgrade. That
gate subsequently passed as recorded below. Full-site backup/recovery with the
new managed code volume remains a separate gate. AWS remains unchanged.

### Administrator upgrade and recreation evidence — 2026-09-09

The operator uploaded RC2 in the localhost:8096 administration interface and
confirmed `0.2.0-rc2 / 2026090802` in Plugins overview. The corrected
`scripts/verify-managed-ui-lab.sh --check-only` passed; the full script then
recreated Web and the idle Cron container and passed again. Both containers'
code metadata and installed DB version were `2026090802` before and after
recreation. Synthetic fixture IDs, records, Markdown and actual image bytes
were retained. This does not claim that scheduled Cron tasks ran.

The three local evidence files `before.json`, `after-ui-upgrade.json`, and
`after-recreation.json` under ignored `build/managed-ui-lab/` were independently
hashed from Windows and all had SHA-256:

`7f5e8af83561089ec2a41cf7f3e2a23c0f6d7e1f41c4dc6a16abb73c6a5d2194`

The verifier initially failed by requiring plugin `version.php` outside Moodle,
where `MATURITY_RC` was undefined. It now reads the numeric build assignment as
text and reports failure stages explicitly; this was a verifier defect, not
evidence of an unsuccessful plugin upgrade.

### Production migration

### Full recovery evidence — 2026-09-09

Snapshot: `build/managed-ui-lab/recovery-20260909T024745Z-1945433` (private,
ignored). All 14 snapshot-manifest checksums were verified independently from
Windows. The first run completed the snapshot but raced the Web entrypoint
while disabling maintenance: `config.php` temporarily could not be read.
The error handler successfully resumed 8096. Startup now waits for Apache PID 1
and readable configuration before invoking the maintenance CLI.

The operator resumed restoration from that verified snapshot without stopping
8096 again. Recovery containers:

- `lessonmark-managed-recovery-20260909T030052Z-1989063-web`
- `lessonmark-managed-recovery-20260909T030052Z-1989063-db`

The script reported PASS for database, application/config, managed-module code
and the image lesson. The recorded before/restored fixture JSON and the full
managed-code file-hash lists also matched independently from Windows. The
recovery copy has no published ports, Web server, scheduled Cron or external
network. The original 8096 remains running. AWS remains unchanged.

### Backup and migration procedure

### AWS deployment evidence — 2026-09-09

The operator-authorized migration ran on the existing `moodle-rescue` project
using the same local/AWS source commit `4cc0d1798aa2d238bda854f3c0102b7a76e64ab4`
on `codex/ui-managed-modules`. It completed with PASS. No unrelated service was
changed and no plugin was uninstalled or upgraded by this migration.

Private rollback bundle on the host:
`/home/ubuntu/docker/moodle-rescue/backups/pre-managed-20260909T031712Z`.
It contains exact Web/DB images, database SQL, full live application/config,
teaching files/backups, old deployment settings, inventory and SHA256SUMS.
Archive integrity and manifest checksums passed; this AWS snapshot has not been
restored on AWS or copied off-host by this operation. Full recovery of the same
snapshot design was previously tested locally.

The two services share `moodle-rescue_managed_modules`: Web read-write, Cron
read-only. Both are running; maintenance is disabled. The ordinary
`docker compose config --services` includes `moodle-modules-init`, confirming
the `.env` overlay selection survives standard Compose invocations.
LessonMark remains alpha2 / `2026083001`, ready for an administrator UI upgrade.

- Before/after all-LessonMark inventory SHA-256:
  `7d290d28fa967197b5db41767a3a6474d3236c8075d9937f2ecfd2e4e5153563`.
- Image/managed module file-list SHA-256:
  `f6f5d94e84cfa922cb02973ef25d03f0a1886634eeac348fbdd4e5cc9da8b200`.

The in-app browser reported `ERR_BLOCKED_BY_CLIENT` for the AWS site; do not
bypass that browser restriction or infer an application failure from it. The
administrator will use their normal browser for the ZIP update. Pause Cron
just before the update and resume it promptly after the DB/code checks pass.

### Operator commands

### AWS RC2 administrator upgrade completed — 2026-09-09

After the administrator logged in, only `moodle-rescue-cron` was temporarily
stopped. The administrator performed the ZIP upgrade and reported normal page
display. Independent CLI checks confirmed `2026090802` in both the database
and actual LessonMark code. The complete LessonMark inventory after RC2
(`backups/pre-managed-20260909T031712Z/after-rc2.json`) matched the pre-migration
inventory, including record/source hashes, IDs and image bytes. Both have
SHA-256 `7d290d28fa967197b5db41767a3a6474d3236c8075d9937f2ecfd2e4e5153563`.

Cron was restarted and independently checked against RC2. Web and Cron are
running, sharing `moodle-rescue_managed_modules` (Web writable, Cron read-only),
and `maintenance_enabled` is `0`. No plugin uninstall occurred. Container
recreation persistence and full recovery were validated locally; this final
AWS UI upgrade did not repeat a destructive recovery or container recreation.

The release candidate used is LessonMark `0.2.0-rc2 / 2026090802`, ZIP SHA-256
`d9d09b9ae35c07fcdccbf35dc2169cbfdfbda3a67a34389b78fae49175294a5f`.
This does not declare RC2 a stable/public release. The image still contains the
alpha2 seed; the existing managed volume is authoritative and must be included
in future backups and retained during deployment. Do not remove it or revert
to the immutable deployment command.

### Operator workflow reference

For the reviewed production project `moodle-rescue`, the one-time operator
command is `sh scripts/migrate-managed-modules.sh`. It requires a clean tracked
checkout, running Web/Cron/DB, alpha2, no existing application mounts and no
live module changes relative to the image. It saves the exact Web/DB images,
full application/config, DB, teaching-file volumes and an all-LessonMark
inventory, verifies their checksums, then enables the tested overlay. Only the
site `.env` gets a pinned `MANAGED_MOODLE_IMAGE` and a persistent `COMPOSE_FILE`.
It compares all module file hashes and all LessonMark records/file bytes before
resuming normal access and Cron. It does NOT upgrade the plugin. Any failure
must be inspected by the operator; do not retry blindly or delete volumes.

Do not use the immutable `scripts/deploy.sh` after conversion: its guard refuses
the managed volume. Routine container recreation uses the Compose files already
selected by `.env`, with `--no-build --pull never`. Core image upgrades remain a
separate operator procedure because of the recorded core-version guard.

Before production migration, run `sh scripts/test-managed-ui-recovery.sh` in
local WSL. This temporarily stops ONLY the original 8096 Web and idle Cron,
enables maintenance and snapshots the DB, full live application/config,
`moodledata`, backup storage and the explicit managed-module volume. It saves
Compose files, the initializer, pinned Web/DB image IDs and private runtime
settings under ignored `build/managed-ui-lab/recovery-<timestamp>-<pid>/`, with
SHA-256 checksums. The original site is resumed before restoration begins.

The script restores into new labelled containers/volumes on an internal-only
`10.203.98.0/24` network (override `MANAGED_RECOVERY_SUBNET` if necessary). The
recovery Web container runs only `sleep infinity`: no published ports, Apache,
Cron, external network or original data mounts. It checks RC2 in code and DB,
the fixture's IDs/source/image bytes and hashes of every managed code file.
The isolated recovery copy and private backup remain for inspection; repeated
runs must use a nonconflicting subnet rather than deleting existing evidence.

This test requires the recorded image IDs to remain available locally. It does
not export images. A portable/AWS rollback bundle must additionally preserve
the exact Web and DB images (registry digests or private `docker image save`
archives), deployment environment and the full-site snapshot. Never attempt a
rollback using an old image against a database already upgraded by newer code.

For the next local gate, `sh scripts/prepare-managed-ui-lab.sh` prepares a fresh
`http://localhost:8096/` site, using the same managed-modules overlay. It checks
the previously tested alpha2 ZIP hash and seeds a synthetic Japanese image
lesson. It refuses existing lab containers, volumes or working directories.
Its generated credentials and evidence are ignored under `build/managed-ui-lab/`.
The `admin` password is the `MANAGED_LAB_ADMIN_PASSWORD` value in `runtime.env`.
The image is based on the existing local UI-test image, with activity/admin-tool
parent ownership restored to match the production permission boundary.

The local-only base uses the explicit subnet `10.203.96.0/24`, overridable with
`MANAGED_LAB_SUBNET`. It was selected against the operator's Docker network and
WSL route inventory on 2026-09-09: the automatic 172.17–31 and 192.168 pools
were exhausted; no conflicting 10.203.96 route was listed. Recheck on other
hosts or when VPN routes change. This is not an AWS network setting. Do not
prune existing networks or change daemon-wide address pools to start this lab.

Web also joins a non-internal access bridge (`10.203.97.0/24`, overridable with
`MANAGED_LAB_ACCESS_SUBNET`) so Docker can publish `127.0.0.1:8096:80`.
DB and idle Cron remain internal-only. An internal-only Web container was
observed to serve HTTP internally but have no active host port publication on
the local Docker installation. For an already initialized lab in that state,
run `sh scripts/repair-managed-ui-access.sh`: it recreates only Web, compares
fixture records and image bytes before/after, and verifies the host HTTP port.
No database, code volume, initializer or existing unrelated site is recreated.
Preparation now checks HTTP before printing Ready. This topology also permits
Web outbound access; it is consistent with the existing UI-test environment,
not a guarantee of network egress isolation.

If the image and `runtime.env` were created but network allocation failed before
containers/volumes were created, use:

```sh
sh scripts/prepare-managed-ui-lab.sh --resume-before-start
```

This retains the pinned image and generated credentials. It refuses any lab
containers (including stopped initializers), known lab volumes, or a previously
recorded fixture rather than resetting an existing installation. For later-stage
failures, inspect the state instead of using this pre-start resume option.

Upload the verified RC2 ZIP using the Moodle administrator UI, without uninstall.
Do not edit the synthetic fixture before comparing it. After the UI upgrade,
run `sh scripts/verify-managed-ui-lab.sh`. This compares the preserved fixture,
recreates only this lab's Web and idle Cron services, and compares again. It
does not perform an upgrade itself. Existing 8085/8095 sites and AWS are not
modified. This gate does not claim to test scheduled Cron jobs; Cron is idle
to avoid running tasks concurrently with the UI code/database upgrade.

Record the current image IDs, Compose configuration paths, installed plugin
versions and active module code hashes. Check `docker diff` and compare image
code against running code; do not seed from an image if live changes would be
lost. Keep original image and complete backup privately available.

Enter maintenance mode, stop Cron, quiesce Web, and take a consistent backup of
DB, moodledata, backup storage, live application code and deployment settings.
Do not uninstall LessonMark. Start only the initializer using the pinned image,
then recreate Web with the overlay (`--no-build --pull never`). Verify unchanged
content before uploading the locally tested ZIP in the Moodle administrator UI.
Keep Cron stopped throughout file replacement and database upgrade. After the
upgrade finishes, restart Web to clear opcode caches, then start Cron. Verify
plugin versions match in Web, Cron and the database, and check content/images.
Recreate both services with the same overlay and recheck persistence.

Always use the overlay for subsequent Compose operations. Never run `down -v`,
delete the managed volume, or revert to the base Compose file as an upgrade or
rollback technique. After a database upgrade, rollback requires a matched
database/data/code backup, not just an older plugin ZIP. Include the managed
code volume in future full-site backups; course backups alone do not replace it.
