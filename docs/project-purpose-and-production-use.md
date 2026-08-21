# Project purpose and production use

## From plugin test bed to education-site foundation

Moodle Rescue began as a reproducible environment for developing and testing
Secure S3 Storage for Moodle. That origin remains important: plugin changes
must be testable against known Moodle, database, object-storage, backup, and
restore components.

The same reproducibility is valuable to education providers. Installing a
learning-management system is only the first step; a useful deployment also
needs secure operating practices, recoverable data, working examples, and a
course that teachers and learners can try immediately. Moodle Rescue is being
developed as a bridge between those needs.

The project now has three first-class outputs:

- a Docker-based Moodle foundation for local development and
  production-shaped deployment;
- testable security, plugin, backup, and recovery workflows; and
- versioned sample courses that demonstrate both subject teaching and useful
  Moodle activity patterns.

## Intended users

The repository is intended for Moodle developers and administrators, schools,
learning centres, NGOs, and technical staff supporting education projects. It
is especially useful where a small team must establish a repeatable site and
then hand it to educators who need concrete examples rather than an empty LMS.

## Environment boundary

`docker-compose.local.yml` is for local development, plugin validation, course
authoring, and restore rehearsal. It binds Moodle to localhost and is not the
public deployment recipe.

`docker-compose.yml` is production-shaped. It integrates with Traefik, builds
tracked and checksum-verified plugins into an immutable application image,
separates web and Cron services, avoids static AWS credentials, and supports
the repository's backup and recovery gates. These are deployable building
blocks and safer defaults, not a claim that every resulting site is secure by
construction.

The site operator remains responsible for:

- DNS, trusted TLS certificates, firewall and network policy;
- unique secrets, least-privilege credentials, and key rotation;
- capacity planning, monitoring, logs, alerting, mail, and availability;
- Moodle, image, operating-system, and plugin security updates;
- backup schedules, retention, off-site copies, and regular restore rehearsals;
- privacy, safeguarding, accessibility, copyright, and local education policy;
- reviewing sample content before it is assigned to learners.

The release gates in this repository are evidence-producing checks. They make
important failures visible but do not replace operational ownership or a
site-specific security review.

## Content as part of the infrastructure

The first sample course, [Python for Data: Foundations in the AI Era](../sample-content/introduction-to-python/README.md),
is designed to be useful immediately and to serve as a course-design model for
teachers. It progresses from Python foundations to practical tabular-data
analysis, uses learning checks that may be retried, and teaches responsible AI
assistance through the cycle Ask, Read, Run, Check, Modify, Explain.

The `.mbz` backup is intentionally kept separate from Docker images. This lets
operators update infrastructure and teaching content independently, select
which courses to import, and add translations or locally relevant datasets
without rebuilding Moodle.

Python Lab is also a separate service. Moodle opens it through LTI 1.3 so that
learners can work in persistent server-side notebooks from shared computers.
An imported course retains its external-tool activities, but each receiving
Moodle site must register its own trusted LTI platform/tool relationship.

## Continuing direction

Development should continue around these principles:

1. secure and recoverable defaults;
2. reproducible builds, upgrades, backups, and restore evidence;
3. clear separation between Moodle, shared routing, object storage, and
   optional learning tools;
4. practical usability for educators and learners, not infrastructure alone;
5. versioned, inspectable, and locally adaptable sample content; and
6. honest documentation of what automation verifies and what operators must
   still decide.

Future work can expand the course catalogue, localisation, open-data projects,
shared Traefik deployment, and Python Lab operations while preserving those
boundaries.
