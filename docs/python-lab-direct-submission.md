# Python Lab direct submission

Python Lab is the learner workspace. Moodle Assignment is the system of record for submitted work and teacher review. Teachers can review the submitted `weekly_support.py` without access to learner workspace volumes.

For Project 1.7, `submit_weekly_support.py` reruns the supplied checker and sends `weekly_support.py` only when all checks pass. The service identifies the learner with the JupyterHub API token. Moodle accepts only a signed, recent, non-replayed request for the fixed Assignment idnumber `pyai-project-1-weekly-support`, then checks enrolment and `mod/assign:submit` before using the standard Assignment API.

## Local setup

Generate one shared secret into both ignored `.env` files:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/configure-python-lab-submit-secret-v2.ps1
```

Build Moodle:

```bash
docker compose -f docker-compose.local.yml up -d --build
```

Build Python Lab with the direct-submission overlay:

```bash
docker compose -f docker-compose.local.yml -f docker-compose.lti.yml -f docker-compose.submit-v4.yml up -d --build
```

Apply the file-only Assignment setting to both language courses from WSL:

```bash
bash scripts/apply-python-lab-submit-v32.sh PYAI-INTRO PYAI-INTRO-JA
```

Never expose port 8090 publicly. It is an internal learner-to-Hub endpoint. Rotate the shared secret by rerunning the setup script and recreating both Moodle and JupyterHub containers.
