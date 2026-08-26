# Canonical content catalogue

This directory contains a machine-generated inventory of translatable content
from the canonical English Moodle course. It gives each course field, activity,
quiz slot, and announcement a stable key for adaptation and change detection.

Regenerate it from the repository root while the canonical local course is
running:

```sh
docker compose -f docker-compose.local.yml exec -T moodle \
  env PYTHON_COURSE_SHORTNAME=PYAI-INTRO PYTHON_CANONICAL_VERSION=1.0.0 \
  runuser -u www-data -- php < scripts/export-python-course-canonical-catalog.php \
  > sample-content/introduction-to-python/localization/canonical-en-1.0.0.json
```

Review catalogue diffs whenever the canonical course changes. Do not hand-edit
the generated English catalogue.

See [`docs/course-language-variants.md`](../../../docs/course-language-variants.md)
for governance of canonical and adapted editions.
