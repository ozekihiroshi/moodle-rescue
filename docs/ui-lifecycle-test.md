# Moodle plugin UI lifecycle test

## Purpose

This environment reproduces the conventional Moodle administrator workflow:
upload a plugin ZIP in the web interface, install it, inspect its settings,
and uninstall it again. It contains no Secure S3 Storage source mount or
preinstalled plugin ZIP.

The environment is independent of the source-bound development site on port
8083 and the immutable ZIP release gate on port 8084. Its database, Moodle data,
backup directory, containers, network, image, and port are dedicated to this
test.

The web process owns only `public/admin/tool`, which is required by Moodle's
web plugin installer. The rest of the Moodle application remains root-owned.
This permission is a test of the conventional web installation model; it is
not used by the production-shaped immutable deployment.

## Prepare

From the repository root, create the ignored runtime environment file and
replace every `CHANGE_ME` value with a unique local test secret:

```sh
cp .env.ui-test.example .env.ui-test
chmod 600 .env.ui-test
```

The Moodle administrator username is `admin`. Its password is the value of
`UI_TEST_ADMIN_PASSWORD` in `.env.ui-test`.

## Build and start

```sh
docker compose --env-file .env.ui-test \
  -f docker-compose.ui-test.yml config --quiet
docker compose --env-file .env.ui-test \
  -f docker-compose.ui-test.yml up -d --build
docker compose --env-file .env.ui-test \
  -f docker-compose.ui-test.yml ps
```

Open <http://localhost:8085>, sign in as `admin`, and upload the release ZIP
through **Site administration > Plugins > Install plugins**.

## Stop, restart, and remove

Stop the containers while preserving the test database and volumes:

```sh
docker compose --env-file .env.ui-test \
  -f docker-compose.ui-test.yml stop
```

Restart the stopped containers without rebuilding:

```sh
docker compose --env-file .env.ui-test \
  -f docker-compose.ui-test.yml start
```

Remove the containers and network while preserving the volumes:

```sh
docker compose --env-file .env.ui-test \
  -f docker-compose.ui-test.yml down
```

Delete only the 8085 test database and volumes to repeat the lifecycle from an
empty Moodle installation:

```sh
docker compose --env-file .env.ui-test \
  -f docker-compose.ui-test.yml down --volumes
```

The final command is destructive only to the `moodle-rescue-ui-test` Compose
project. It does not target the 8083 or 8084 projects.

## Required checks

1. Upload the official `tool_secure_s3_storage.zip` release artifact.
2. Confirm that Moodle identifies `tool_secure_s3_storage` and version 0.2.3.
3. Complete installation and open the plugin settings page.
4. Confirm that S3 transfer is disabled by default.
5. Uninstall the plugin from the Plugins overview page.
6. Confirm that the plugin directory is removed and Moodle no longer lists it.
7. Repeat the installation once to prove that no stale database state blocks it.
