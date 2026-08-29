# Development resume TODO

Last updated: 2026-08-29

This document is the restart point for work after the Python course alpha
release. Complete the items in priority order unless a newly discovered defect
requires changing that order.

## Current checkpoint

The following work is complete and should not be repeated when development
resumes:

- Moodle Rescue `main` contains the verified English and Japanese course
  artifacts for `0.1.0-alpha.1`.
- The English course (`PYAI-INTRO`) is the canonical edition. The Japanese
  course (`PYAI-INTRO-JA`) is an independent official adaptation.
- Both artifacts were restored successfully as the first copy in a fresh
  Moodle 5.2.2 site.
- Both distribution verification scripts pass.
- Learner accounts, attempts, submissions, completion records, credentials,
  and learner workspaces are excluded from the distributed backups.
- Moodle Rescue checkpoint commit:
  `db59e1571bd15ac1ef3366a782ca18c1cfa9b77d`.
- Python Lab Rescue checkpoint currently used during course development:
  `c0dc9399548fe7b02d6a3b12dd0d5195f1573f45`.

Published course artifacts:

| Edition | Artifact | SHA-256 |
| --- | --- | --- |
| English | `sample-content/introduction-to-python/distribution/python-for-data-foundations-ai-era-0.1.0-alpha.1.mbz` | `f602e2b05c32dcdcccfe443e02ed538060d3b42a3a1a03b4c12800dbe8703e00` |
| Japanese | `sample-content/introduction-to-python-ja/distribution/python-for-data-foundations-ai-era-ja-0.1.0-alpha.1.mbz` | `cd7b1eb4cd96c135a134f329480c699639dd3eb38d2f5b853208f61ee171a051` |

## P0 — Make repeat restore behaviour safe and explicit

During isolated verification, the first English and Japanese restores were
correct. Restoring another copy of the Japanese edition into the same Moodle
site produced 20 question-reference/context mismatches. The first restored
copy remained correct. Moodle appears to reuse question categories by stamp
during the later restore.

- [x] Add the alpha limitation to both distribution READMEs and manifests.
- [x] State that `0.1.0-alpha.1` is verified for the first restore into a fresh
  site and that restoring another copy of the same edition into that site is
  not supported during alpha.
- [ ] Reproduce the second-restore failure in a disposable Compose project.
- [ ] Record the category stamps, question-bank entry contexts, and quiz owning
  contexts before and after the second restore.
- [ ] Decide on the supported fix: generate portable unique category stamps,
  provide a safe pre-restore cleanup/migration, or intentionally reject a
  duplicate restore with clear instructions.
- [ ] Add an automated repeat-restore assertion to the course distribution
  verifier.
- [ ] Verify English-after-English, Japanese-after-Japanese, and both restore
  orders for English and Japanese.

Completion condition: every supported restore sequence has zero question
context mismatches, or unsupported sequences are detected and documented
before an administrator can mistake the result for a valid restore.

Safety note: before changing or deleting question-bank data in the authoring
site, create a new database backup. Do not reuse the old development-attempt
cleanup scripts without reviewing their exact course and context IDs.

## P1 — Pin the Moodle course to a Python Lab release

The Moodle backups contain LTI activities, but Python Lab is deployed and
versioned separately. The course release must identify the exact compatible
Python Lab release rather than relying on the moving repository branch.

- [ ] Run the complete Python Lab verification at commit
  `c0dc9399548fe7b02d6a3b12dd0d5195f1573f45`.
- [ ] Verify that every English and Japanese LTI activity opens the intended
  notebook or project directory.
- [ ] Verify the project starter files, sample data, check programs, and direct
  Moodle submission helpers used by Chapters 1 through 6.
- [ ] Create and push an alpha tag/release in `python-lab-rescue`.
- [ ] Add the compatible Python Lab release/tag and commit to both Moodle course
  manifests and distribution READMEs.
- [ ] Document how a site operator upgrades course materials without
  overwriting learner-edited files.

Completion condition: a new operator can deploy the named Python Lab release,
configure LTI 1.3, restore either course, and open every lab using only the
published instructions.

## P1 — Publish a formal GitHub course release

The `.mbz` files are already committed to `main`; a formal GitHub Release is
still useful as the stable download page for evaluators.

- [ ] Re-run both distribution verifiers from a clean checkout.
- [ ] Confirm the committed `SHA256SUMS` and `manifest.json` values.
- [ ] Create release/tag `python-course-v0.1.0-alpha.1` (or record the final
  agreed tag before creating it).
- [ ] Attach both `.mbz` files, checksums, license notice, and concise release
  notes.
- [ ] Link the GitHub Release from `sample-content/README.md`.
- [ ] Verify the repository and release assets are accessible while signed out.

Completion condition: an unauthenticated evaluator can find, download, verify,
license, and restore both editions without browsing the repository history.

## P1 — Run the production-shaped end-to-end pilot gate

The local WSL setup is verified, but the public learning path still needs a
production-shaped integration rehearsal. Do not use Docker Desktop.

- [ ] Deploy the independent Traefik Rescue gateway with HTTPS and real test
  hostnames.
- [ ] Deploy Moodle Rescue and Python Lab Rescue as separate Compose projects.
- [ ] Confirm that only intended public endpoints are exposed and internal
  service networks remain private.
- [ ] Register LTI 1.3 using the public HTTPS issuer, login, callback, JWKS, and
  target URLs.
- [ ] Restore both course editions into a fresh test Moodle.
- [ ] Test as a non-teacher learner: enrol, open lessons, launch notebooks, save
  work, stop and respawn the lab, run project checks, and submit an assignment.
- [ ] Test as a teacher: view only the submitted artifact, download it, run it
  in Python Lab, and record feedback.
- [ ] Exercise failed submission, failed LTI launch, expired session, and Python
  Lab restart paths.
- [ ] Verify Moodle backup/restore and a separate backup/restore rehearsal for
  Hub state and learner volumes.
- [ ] Record resource limits, logs, monitoring, update procedure, and rollback
  procedure for an invite-only pilot.

Completion condition: the full learner and teacher workflow succeeds over
HTTPS from a clean deployment, and recovery of Moodle plus learner work has
been rehearsed rather than assumed.

## P2 — Editorial and learner validation before beta

The course is an alpha: its overall structure exists, but it still needs human
trial and refinement.

- [ ] Conduct a complete learner walkthrough of the English canonical edition.
- [ ] Record every point where instructions, required data, expected output,
  file locations, saving, checking, or submission are unclear.
- [ ] Apply accepted English corrections first, then adapt the Japanese edition
  while preserving its status as a separate learner-facing course.
- [ ] Check that every lesson has clear topic boundaries, an introduction,
  achievable objectives, worked examples, guided integrated practice, model
  answers, a lesson-specific summary, and a connection to the next lesson.
- [ ] Verify that integrated practice is lighter than the chapter project,
  rehearses its method without copying its subject, and introduces no large
  unlearned API.
- [ ] Review all knowledge checks for learning-oriented retries, useful
  feedback, common misconceptions, 90% mastery messaging, and sensible
  completion rules.
- [ ] Calibrate project difficulty and automated-check feedback with actual
  beginners and an instructor-assisted group.
- [ ] Run accessibility checks for headings, tables, code blocks, link text,
  keyboard navigation, contrast, and screen-reader order.
- [ ] Verify terminology, code, data, expected output, check scripts, and Moodle
  assignments remain aligned across both editions.

Completion condition: representative beginners can complete the course using
the published environment without unpublished help, and observed problems are
either fixed or listed as explicit beta limitations.

## P2 — Operational documentation for real teaching sites

- [ ] Create one start-to-finish operator guide covering gateway, Moodle,
  Python Lab, DNS/TLS, LTI, course restore, enrolment, backup, monitoring, and
  updates.
- [ ] Clearly separate local WSL development, invite-only pilot, and broader
  production deployment requirements.
- [ ] Add sizing guidance for expected concurrent learners.
- [ ] Add retention and privacy guidance for Moodle submissions and learner
  volumes.
- [ ] Add an incident checklist for credential exposure, compromised learner
  code, exhausted resources, and failed backups.
- [ ] Document what Moodle Rescue secures and what remains the operator's
  responsibility.

## Commands to run first after resuming

Run these from Ubuntu 24.04 in WSL, not Docker Desktop:

```sh
cd /mnt/d/workspace/moodle-rescue
git status --short
git pull --ff-only
sh scripts/verify-python-sample-distribution.sh
sh scripts/verify-python-sample-distribution-ja.sh

cd /mnt/d/workspace/python-lab-rescue
git status --short
git pull --ff-only
sh scripts/verify-local.sh
```

Before starting a state-changing task, confirm that the commits and artifact
hashes still match the checkpoint above. If they do not, update this document
with the reason before continuing.

## Recommended restart sequence

1. Document the duplicate-restore limitation immediately.
2. Diagnose and either fix or deliberately constrain repeat restore.
3. Pin and release the compatible Python Lab version.
4. Publish the formal course GitHub Release.
5. Run the HTTPS production-shaped pilot gate.
6. Begin learner trials and editorial work toward beta.
