# Reconnect a restored Python course to production Python Lab

The distributed Moodle course retains its activity structure and notebook
paths, but a restored course must be connected to the production LTI 1.3 site
tool. Do not recreate the activities from an older lesson list.

## Access boundary

The course may allow password-free guest reading. Python Lab does not allow
anonymous execution: each Lab learner signs in to Moodle and launches through
LTI 1.3. Confirm in a private browser that a guest can read the course but
cannot launch Python Lab.

## 1. Create or update the Moodle site tool

Run this from the production `moodle-rescue` checkout after restoring the
course. Replace both values:

```sh
sh scripts/reconnect-python-lab-production.sh \
  PYAI-INTRO \
  https://lab.example.org
```

The first JSON object prints Moodle's LTI Client ID. The second reports every
existing Lab activity whose URL was moved to the production host. The script
does not change sections, names, completion settings, quizzes, or assignments.

## 2. Configure and start Python Lab

On the same host or the dedicated Lab host:

```sh
git clone https://github.com/ozekihiroshi/python-lab-rescue.git
cd python-lab-rescue
cp .env.production.example .env.production
```

Set the real `LAB_HOST`, Moodle HTTPS endpoints, Traefik network, and the Client
ID printed in step 1. Then run:

```sh
sh scripts/verify-production.sh
sh scripts/start-production.sh
```

## 3. Verify before announcing execution access

Use a normal Moodle learner account to launch one lesson, save a change, stop
the server, and launch it again. Verify that the same workspace returns. Also
confirm that a logged-out guest cannot launch the external tool. The optional
direct-submission bridge remains disabled until its matching Moodle secret and
HTTPS endpoint are configured.
