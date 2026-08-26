# Python for Data: Foundations in the AI Era

This directory contains the reproducible source for the Moodle sample course
`PYAI-INTRO`.

## Version 2 curriculum work

The immutable 1.0.0 distribution remains the current release. The approved
systematic v2 curriculum, migration audit, and complete 120-question mapping
are maintained in:

- [`curriculum-v2.md`](curriculum-v2.md)
- [`curriculum-v2-audit.md`](curriculum-v2-audit.md)
- [`v2-question-audit.md`](v2-question-audit.md)
- [`concept-coverage-v2.md`](concept-coverage-v2.md)
- [`chapter-0-1-concept-gap-audit-v2.md`](chapter-0-1-concept-gap-audit-v2.md)
- [`localization/chapter-0-1-concept-map-v2.json`](localization/chapter-0-1-concept-map-v2.json)

The local authoring courses currently contain the reviewed v2 prototype for
Chapter 0 and Chapter 1 Lesson 1. Do not promote it as a version 2 release
until the complete learner path has been rebuilt and restored in a disposable
course.

## Ready-to-restore release

The versioned Moodle backup in [`distribution/`](distribution/README.md) is the
Git-managed sample-course release. It can be restored without running the
course-generation scripts. The `build/` directory remains an ignored workspace
for intermediate exports; only a verified backup is promoted to
`distribution/`.

## Learning outcome

After about 38 hours of study, a learner should be able to read and adapt
basic Python, load a CSV file, clean and summarise tabular data, create an
appropriate chart, and explain a result in their own words. The course permits
responsible AI assistance and requires learners to run, check, modify, and
explain AI-assisted work.

## Included Moodle activities

- Orientation and responsible-AI guidance
- Four real announcements and Naledi's connected learning-centre story
- Five chapters containing 17 native Moodle subsections
- 12 lesson pages with worked examples, transfer challenges, and scale-up links
- 12 retryable learning checks with 10 questions each (120 total)
- All 12 checks required for completion at 90%, with unlimited attempts and highest score retained
- Highest score retained, 90% pass threshold, five feedback bands, and a 100% mastery target
- Misconception-driven feedback plus Boolean logic, sets, and basic statistics
- A 24-row practice CSV and deterministic large-dataset generator
- 12 lesson notebooks and five project templates opened through deep-linked LTI activities
- Mini-project, foundation project, data-analysis project, and scale-up capstone
- Reflection and AI-use declaration prompts
- Hidden teacher guides, project answers, grading notes, and oral prompts

## Create the course

From the repository root, with the WSL-hosted local Moodle environment running:

```sh
docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/create-python-sample-course.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/upgrade-python-sample-course-v2.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/upgrade-python-sample-course-v3.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/upgrade-python-sample-course-v4.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/upgrade-python-sample-course-v5.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/group-python-course-chapters.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/upgrade-python-sample-course-v7.php


scripts/apply-python-sample-course-v9.sh PYAI-INTRO
scripts/apply-python-sample-course-v9.sh PYAI-INTRO-JA
docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/upgrade-python-sample-course-v8.php
```

The first command refuses to overwrite an existing populated `PYAI-INTRO`
course. The v2 through v5 and v7 upgrades, plus the chapter-grouping step, are
idempotent. The v8 prototype preserves any attempted v1 Lesson 1 quiz as a
hidden archive and creates a new activity before changing its questions.
The v9 runner applies that foundation and then upgrades Lesson 1.2 in the
selected English or Japanese course. It refuses to replace a Lesson 1.2 quiz
that already has attempts.
The v4 upgrade clears attempts in this reproducible development course before
normalising quiz structure and grading; exported release courses contain no
learner attempt history.

## Verify and export

```sh
docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/verify-python-sample-course.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/verify-python-course-chapters.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/verify-python-learning-check-policy.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/verify-python-course-v8-prototype.php

python scripts/verify-python-lesson-1-2-concept-map-v2.py

# Copy the distribution-safe CLI below the Moodle root, then run it.
docker cp scripts/backup-course-for-distribution.php \
  moodle-rescue-local:/var/www/html/admin/cli/backup-course-for-distribution.php
docker exec moodle-rescue-local php \
  /var/www/html/admin/cli/backup-course-for-distribution.php \
  --courseid=<course-id> --destination=/var/moodlebackups
```

Copy the generated `.mbz` into this directory's ignored `build/` folder, run
all verification steps, and then deliberately promote the verified file to
`distribution/` as a versioned release. Do not link documentation to an
unversioned file in `build/`.

The reviewed Notebook documents in
`python-lab/templates/` are the source of truth. The generator publishes them
without reconstructing their content from string literals. English notebooks
are at the workspace root; language-specific adaptations use subdirectories
such as `ja/` so one Moodle account can open both editions safely.

## Attach Python Lab through LTI 1.3

With the separate `python-lab-rescue` project available, configure the local
site tool and add the launch activity idempotently:

```sh
docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/configure-python-lab-lti.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/verify-python-lab-lti.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/configure-python-lab-notebooks.php

docker compose -f docker-compose.local.yml exec -T moodle \
  runuser -u www-data -- php < scripts/verify-python-lab-notebooks.php
```

Generate the learner notebooks, rebuild the Python Lab image, and verify every
notebook in the actual image:

```sh
python3 sample-content/introduction-to-python/python-lab/generate-notebooks.py \
  --output ../python-lab-rescue/course-materials

cd ../python-lab-rescue
docker compose -f docker-compose.local.yml --profile build build singleuser-image
docker run --rm \
  -v /mnt/d/workspace/moodle-rescue/sample-content/introduction-to-python/python-lab/verify-notebooks.py:/tmp/verify-notebooks.py:ro \
  python-lab-rescue-singleuser:2026-07-28 \
  python /tmp/verify-notebooks.py /opt/python-lab/course-materials
```

The lesson activities open the exact notebook through the LTI 1.3
`target_link_uri`. New course-material releases add missing notebooks without
overwriting files already edited in a learner's persistent workspace.

The first command prints the non-secret Client ID and course-module ID. Put the
Client ID and the documented Moodle endpoints in the ignored
`python-lab-rescue/.env`, set `LAB_AUTH_MODE=lti13`, and restart Python Lab.

The signed end-to-end launch can then be tested without displaying the Moodle
administrator password:

```sh
set -a
. ./.env
set +a
PYTHON_LAB_PUBLIC_URL=http://localhost:8086 \
PYTHON_LAB_COURSE_MODULE_ID=<id printed above> \
  python3 scripts/smoke-python-lab-lti.py
```

The site-tool registration and Client ID remain site-specific. A backup may
contain course activities, but their tool type and endpoints must not be
assumed portable. Rerun both Python Lab configuration scripts after restoring
the course on a target site.

## Design notes

The content is intentionally low-bandwidth: all essential explanations,
datasets, and code examples are text embedded in Moodle. The supported lab path
assumes an online Moodle session and opens the centrally managed Python Lab;
teachers may separately permit a local Python installation. No paid service or
AI account is required.

The `datasets/` directory contains a small fictional operations file and a
standard-library Python generator for 10,000 to 250,000 or more deterministic
rows. Learners validate the workflow on these offline files before transferring
it to an appropriately licensed open dataset.

## License

The original educational content, generated notebooks, fictional dataset, and
versioned `.mbz` release are licensed under CC BY 4.0. The executable source
and integration scripts are licensed under GPL-3.0-or-later. See the
repository's [`LICENSE-CONTENT.md`](../../LICENSE-CONTENT.md) for scope and
attribution requirements.
