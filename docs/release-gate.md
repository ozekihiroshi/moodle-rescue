# ZIP release gate

## Purpose

The release-test environment proves that the committed plugin can be packaged,
installed, and used without a source bind mount. It has an independent Compose
project, database, Moodle data directory, backup volume, containers, and port.

The local MinIO service from `docker-compose.local.yml` must be running. The
release containers join its internal Docker network and authenticate with the
same dedicated least-privilege S3 identity.

## Run

Keep the local MinIO settings in the ignored `.env`. Copy the release-specific
template, populate every `CHANGE_ME` value including the object key of a
verified Moodle-generated backup, then load both files in order:

```sh
cp .env.release-test.example .env.release-test
scripts/build-plugin-zip.sh
docker compose --env-file .env --env-file .env.release-test \
  -f docker-compose.release-test.yml config
docker compose --env-file .env --env-file .env.release-test \
  -f docker-compose.release-test.yml build moodle-release
docker compose --env-file .env --env-file .env.release-test \
  -f docker-compose.release-test.yml up -d --no-build
docker compose --env-file .env --env-file .env.release-test \
  -f docker-compose.release-test.yml --profile tools \
  run --rm --no-deps release-fetch
docker compose --env-file .env --env-file .env.release-test \
  -f docker-compose.release-test.yml --profile tools \
  run --rm --no-deps release-restore
```

The installer and restore helpers are idempotent. To deliberately repeat the
test from empty release-only volumes, first run the following destructive
command. It does not target the local development volumes:

```sh
docker compose --env-file .env --env-file .env.release-test \
  -f docker-compose.release-test.yml down --volumes
```

## Automated CI

Workflows in both repositories call the complete gate for pushes to `main`,
pull requests, and manual dispatches. Each workflow checks out the other
repository alongside the exact commit under test and runs:

```sh
PLUGIN_REPOSITORY=/path/to/plugin scripts/run-release-gate-ci.sh
```

The script creates random credentials in a mode-0600 temporary environment
file, uses unique source and release Compose projects, and always removes their
containers and volumes. It does not read the developer `.env`, reuse the local
8083/8084 environments, or require repository secrets.

The automated path creates a course and real Moodle backup, verifies the
Moodle-to-MinIO transfer, builds a clean-HEAD ZIP, and installs it into an empty
Moodle database. Before the database happy path, it presents an unknown-field
manifest and a checksum-corrupt payload from an isolated volume. The gate
requires explicit rejection, preservation of both local artifacts, and absence
of a remotely published completion manifest.

It then generates a real external MariaDB dump from the source environment,
exposes the artifact volume read-only to the ZIP-installed release Cron
container, transfers and downloads the completed v1 pair, restores it to an
empty MariaDB instance, and reads it through a fresh release image.

The same ZIP is then switched to the built-in producer. It creates and
transfers a v2 Moodle DTL XML artifact in one scheduled run, rejects an
unknown-field v2 manifest and a checksum-corrupt v2 payload while preserving
the local pairs and publishing neither their payloads nor completion manifests, downloads a valid v2 pair from MinIO, and invokes
the plugin's CLI-only restore command against another empty isolated database.
A fresh release image must read the DTL-restored Moodle version.

The course restore is also repeated to prove idempotency. Finally, the gate checks
disabled and re-enabled transfer states, runs the Moodle upgrade no-op, rejects
bind mounts on both release Moodle runtime containers, verifies the database
hand-off is a read-only named volume, and verifies the final HTTP response.

## Result on 2026-08-16

- Plugin source commit:
  `f62a4547f5de1579d32554a427a2494408a28776`
- Release ZIP SHA-256:
  `ca6f7b93843dc4113119dcbbce79dd6c9df0ef249b22a2fa18c1679e4708b040`
- Installed plugin version: `2026081602`
- Installed `version.php`: identical to the committed source
- Bundled AWS SDK: loaded from the release ZIP without the image SDK override
- Moodle installation: fresh database and named volumes
- Web container bind mounts: none
- Moodle URL: `http://localhost:8084`
- Initial plugin state: disabled
- Re-enabled scan: completed with no eligible top-level archives
- MinIO object SHA-256:
  `71d373e97f501e2ddc228dac3831fda817ce6244640622b61680fc40ddb652ce`
- Restored course ID: `2`
- Restored course shortname: `S3INT-CI`
- Restored Page: `Secure S3 verification page`
- Verification marker: present
- Repeated restore: detected the existing verified course and made no duplicate
- Moodle upgrade: no upgrade required
- Final HTTP result: `200`

The downloaded backup is retained below
`/var/moodlebackups/restored/`, outside the plugin's top-level scan.
