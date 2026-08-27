# Python for Data: Foundations in the AI Era

This directory contains the canonical English source and release workspace for
the Moodle course `PYAI-INTRO`.

## Current public course

Release `0.1.0-alpha.1` is available in
[`distribution/`](distribution/README.md). It contains the current Chapters 0
through 6 authoring course, with user accounts and user-dependent records
excluded. The ignored `build/` directory is only an intermediate workspace and
is not a release channel.

The course combines Python foundations, data structures and files, pandas data
work, classes and objects, visual evidence, and a final scale-up project.
Integrated practices rehearse the method used by the larger chapter projects.

## English and Japanese editions

English is the normative edition. The separate `PYAI-INTRO-JA` course is an
official Japanese adaptation, released from
[`../introduction-to-python-ja/`](../introduction-to-python-ja/README.md). It is
not an automatically synchronised translation.

## Verify and export

The local authoring environment runs with Docker Engine in WSL. Before
promotion, run the current chapter verifiers, generate a distribution-safe
backup with `scripts/backup-course-for-distribution.php`, and verify the final
archive:

```sh
python3 scripts/verify-python-course-distribution.py \
  sample-content/introduction-to-python/distribution/python-for-data-foundations-ai-era-0.1.0-alpha.1.mbz \
  --shortname PYAI-INTRO --language en

sh scripts/verify-python-sample-distribution.sh
```

Every content change requires a new versioned `.mbz`, checksum, manifest, and
verification run. Do not publish an unversioned file from `build/`.

## Python Lab

Notebook templates and project files are under `python-lab/`. Generate learner
materials into the separate `python-lab-rescue` project and verify every
Notebook in the actual single-user image. Restoring the Moodle backup does not
deploy Python Lab or transfer LTI signing material; the receiving site must
register its own LTI 1.3 tool.

## License

Original course content, generated Notebooks, and fictional datasets are CC BY
4.0. Executable tooling is GPL-3.0-or-later. See
[`LICENSE-CONTENT.md`](../../LICENSE-CONTENT.md).
