#!/bin/sh
set -eu

course_shortname=${1:?Usage: reconnect-python-lab-production.sh COURSE_SHORTNAME https://lab.example.org}
lab_url=${2:?Usage: reconnect-python-lab-production.sh COURSE_SHORTNAME https://lab.example.org}
env_file=${MOODLE_ENV_FILE:-.env}
compose_file=${COMPOSE_FILE:-docker-compose.yml}
tool_name=${PYTHON_LAB_TOOL_NAME:-Python Lab}

case "$lab_url" in
    https://*) ;;
    *)
        echo "Python Lab production URL must use HTTPS." >&2
        exit 1
        ;;
esac

run_php() {
    script=$1
    docker compose --env-file "$env_file" -f "$compose_file" exec -T \
        -e "PYTHON_COURSE_SHORTNAME=$course_shortname" \
        -e "PYTHON_LAB_PUBLIC_URL=$lab_url" \
        -e "PYTHON_LAB_TOOL_NAME=$tool_name" \
        moodle runuser -u www-data -- php < "$script"
}

echo "Configuring the Moodle LTI 1.3 site tool..."
run_php scripts/configure-python-lab-lti.php

echo "Reconnecting all existing Python Lab activities without changing course structure..."
run_php scripts/reconnect-python-lab-lti.php

echo "Copy the Client ID from the first JSON result into python-lab-rescue/.env.production."
