# Security policy

## Supported versions

Moodle Rescue is alpha software. Security fixes target the latest revision on
the default branch. Older snapshots and locally modified deployments are not
covered unless explicitly stated.

## Reporting a vulnerability

Do not open a public issue containing an exploit, credential, personal data,
learner record, production hostname, or sensitive log. Use GitHub private
vulnerability reporting when it is available. Otherwise contact the
maintainer privately through the contact method on the maintainer's GitHub
profile.

Include the affected revision, deployment mode, reproduction steps, impact,
and suggested mitigation. Remove secrets and personal data from all evidence.

## Operational boundary

This repository supplies network separation, immutable production plugin
builds, backup and restore gates, and safer examples. It does not replace host
hardening, a firewall, TLS and DNS operations, secret management, Moodle patch
management, monitoring, privacy controls, safeguarding, backups, restore
rehearsals, or incident response.
