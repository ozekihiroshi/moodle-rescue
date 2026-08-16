# ZIP release gate

## Purpose

The release-test environment proves that the committed plugin can be packaged,
installed, and used without a source bind mount. It has an independent Compose
project, database, Moodle data directory, backup volume, containers, and port.

The local MinIO service from `docker-compose.local.yml` must be running. The
release containers join its internal Docker network and authenticate with the
same dedicated least-privilege S3 identity.

## Run

Populate the `RELEASE_TEST_*` values in the ignored `.env`, including the
object key of a verified Moodle-generated backup, then run:

```sh
scripts/build-plugin-zip.sh
docker compose -f docker-compose.release-test.yml config
docker compose -f docker-compose.release-test.yml build moodle-release
docker compose -f docker-compose.release-test.yml up -d --no-build
docker compose -f docker-compose.release-test.yml --profile tools \
  run --rm --no-deps release-fetch
docker compose -f docker-compose.release-test.yml --profile tools \
  run --rm --no-deps release-restore
```

The installer and restore helpers are idempotent. To deliberately repeat the
test from empty release-only volumes, first run the following destructive
command. It does not target the local development volumes:

```sh
docker compose -f docker-compose.release-test.yml down --volumes
```

## Result on 2026-08-16

- Plugin source commit:
  `8f7cc6a2ca6e10e7c9b696c4ad2a70217c57f836`
- Release ZIP SHA-256:
  `691f5f6287311a21624500fef43d798d835d2e869e52d8b4d73be89202892691`
- Installed plugin version: `2026081601`
- Installed `version.php`: identical to the committed source
- Moodle installation: fresh database and named volumes
- Web container bind mounts: none
- Moodle URL: `http://localhost:8084`
- Initial plugin state: disabled
- Re-enabled scan: completed with no eligible top-level archives
- MinIO object SHA-256:
  `0d32f1b45e35cb67d362b65f7cd29b12c6ee08fc915e1d9e3cc79d5b525f8549`
- Restored course ID: `2`
- Restored course shortname: `S3INT-CLI`
- Restored Page: `Secure S3 verification page`
- Verification marker: present
- Repeated restore: detected the existing verified course and made no duplicate
- Moodle upgrade: no upgrade required
- Final HTTP result: `200`

The downloaded backup is retained below
`/var/moodlebackups/restored/`, outside the plugin's top-level scan.
