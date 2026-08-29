# Changelog

## Unreleased

### Added

- Public security policy and development restart checklist.
- Explicit duplicate-course restore limitation in both course distributions.
- Static Compose validation for all four deployment and test configurations.

### Changed

- Generalized the production Traefik network, certificate resolver, and AWS
  region settings.
- Required production database and hostname values during Compose expansion.
- Removed application-level HTTP redirect labels because the independent
  Traefik Rescue gateway supplies the global redirect.

## Python course 0.1.0-alpha.1 — 2026-08-27

### Added

- Canonical English and official Japanese Moodle course backups.
- Versioned manifests, SHA-256 checksums, portable license notices, and fresh
  Moodle restore verification.

### Known limitations

- Intended for evaluation and controlled pilots, not unattended production.
- A second restore of the same edition into the same site is not supported.
- Python Lab requires a separate compatible deployment and site-specific LTI
  1.3 registration.
