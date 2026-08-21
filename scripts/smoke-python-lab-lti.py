#!/usr/bin/env python3
import http.cookiejar
import os
import re
import subprocess
import time
import urllib.parse
import urllib.request
from html.parser import HTMLParser


class FormParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.forms: list[dict[str, object]] = []
        self.current: dict[str, object] | None = None

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = dict(attrs)
        if tag == "form":
            self.current = {
                "action": values.get("action", ""),
                "method": (values.get("method") or "get").lower(),
                "fields": {},
            }
            self.forms.append(self.current)
        elif tag == "input" and self.current is not None and values.get("name"):
            fields = self.current["fields"]
            assert isinstance(fields, dict)
            fields[values["name"]] = values.get("value", "")

    def handle_endtag(self, tag: str) -> None:
        if tag == "form":
            self.current = None


def parse_forms(html: str) -> list[dict[str, object]]:
    parser = FormParser()
    parser.feed(html)
    return parser.forms


def submit_form(opener, page_url: str, form: dict[str, object]):
    fields = form["fields"]
    assert isinstance(fields, dict)
    action = urllib.parse.urljoin(page_url, str(form["action"]))
    payload = urllib.parse.urlencode(fields).encode("utf-8")
    return opener.open(urllib.request.Request(action, data=payload), timeout=180)


def choose_form(html: str, required_fields: set[str]) -> dict[str, object]:
    for form in parse_forms(html):
        fields = form["fields"]
        assert isinstance(fields, dict)
        if required_fields.issubset(fields):
            return form
    raise RuntimeError(f"Expected auto-submit form was not found: {sorted(required_fields)}")


moodle_url = os.environ.get("MOODLE_WWWROOT", "http://localhost:8083").rstrip("/")
hub_url = os.environ.get("PYTHON_LAB_PUBLIC_URL", "http://localhost:8086").rstrip("/")
cmid = os.environ.get("PYTHON_LAB_COURSE_MODULE_ID", "77").strip()
login_user = os.environ.get("LTI_SMOKE_USERNAME", os.environ["MOODLE_ADMIN_USER"])
login_password = os.environ.get("LTI_SMOKE_PASSWORD", os.environ["MOODLE_ADMIN_PASSWORD"])
expected_marker = os.environ.get("LTI_SMOKE_EXPECT_MARKER", "")
write_marker = os.environ.get("LTI_SMOKE_WRITE_MARKER", "")
expect_absent = os.environ.get("LTI_SMOKE_EXPECT_ABSENT", "").lower() in {"1", "true", "yes"}

cookies = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))

login_url = f"{moodle_url}/login/index.php"
login_page = opener.open(login_url, timeout=30)
login_html = login_page.read().decode("utf-8")
login_form = choose_form(login_html, {"logintoken", "username", "password"})
login_fields = login_form["fields"]
assert isinstance(login_fields, dict)
login_fields["username"] = login_user
login_fields["password"] = login_password
login_response = submit_form(opener, login_page.geturl(), login_form)
login_result = login_response.read().decode("utf-8")
if "/login/index.php" in login_response.geturl() or "Invalid login" in login_result:
    raise RuntimeError("Moodle login failed")

launch_page = opener.open(f"{moodle_url}/mod/lti/launch.php?id={cmid}", timeout=30)
launch_html = launch_page.read().decode("utf-8")
login_init_form = choose_form(launch_html, {"iss", "login_hint", "target_link_uri"})
login_init_response = submit_form(opener, launch_page.geturl(), login_init_form)
authorization_html = login_init_response.read().decode("utf-8")

authorization_form = choose_form(authorization_html, {"id_token", "state"})
callback_response = submit_form(opener, login_init_response.geturl(), authorization_form)
callback_response.read()

match = re.search(r"/(?:spawn-pending|user)/([^/?]+)", callback_response.geturl())
if not match:
    raise RuntimeError(f"Hub did not identify the LTI learner: {callback_response.geturl()}")
username = urllib.parse.unquote(match.group(1))
if not re.fullmatch(r"[a-zA-Z0-9]+", username):
    raise RuntimeError(f"Smoke-test username requires Docker escaping: {username!r}")
container = f"python-lab-{username}"

deadline = time.monotonic() + 180
while time.monotonic() < deadline:
    result = subprocess.run(
        ["docker", "inspect", "--format", "{{.State.Running}}", container],
        check=False,
        stdout=subprocess.PIPE,
        stderr=subprocess.DEVNULL,
        text=True,
    )
    if result.returncode == 0 and result.stdout.strip() == "true":
        break
    time.sleep(1)
else:
    raise RuntimeError("Timed out waiting for the LTI learner container")

deadline = time.monotonic() + 180
while time.monotonic() < deadline:
    response = opener.open(f"{hub_url}/user/{urllib.parse.quote(username)}/", timeout=10)
    if response.geturl().endswith(
        f"/user/{urllib.parse.quote(username)}/lab/tree/00_start_here.ipynb"
    ):
        break
    time.sleep(1)
else:
    raise RuntimeError("Timed out waiting for the start notebook")

subprocess.run(
    [
        "docker",
        "exec",
        container,
        "python",
        "-c",
        (
            "import pathlib, pandas, matplotlib, openpyxl; "
            "assert pathlib.Path('/home/jovyan/work/00_start_here.ipynb').is_file()"
        ),
    ],
    check=True,
)

marker_check = (
    "import os; from pathlib import Path; "
    "p=Path('/home/jovyan/work/.lti-workspace-marker'); "
    "expected=os.environ.get('EXPECTED_MARKER',''); "
    "absent=os.environ.get('EXPECT_ABSENT') == '1'; "
    "assert not absent or not p.exists(), 'another learner marker is visible'; "
    "assert not expected or p.read_text() == expected, 'own marker was not preserved'; "
    "write=os.environ.get('WRITE_MARKER',''); "
    "p.write_text(write) if write else None"
)
subprocess.run(
    [
        "docker",
        "exec",
        "-e",
        f"EXPECTED_MARKER={expected_marker}",
        "-e",
        f"EXPECT_ABSENT={'1' if expect_absent else '0'}",
        "-e",
        f"WRITE_MARKER={write_marker}",
        container,
        "python",
        "-c",
        marker_check,
    ],
    check=True,
)

xsrf = next((cookie.value for cookie in cookies if cookie.name == "_xsrf"), "")
if not xsrf:
    raise RuntimeError("Authenticated JupyterHub XSRF cookie was not found")
stop_request = urllib.request.Request(
    f"{hub_url}/hub/api/users/{urllib.parse.quote(username)}/server",
    method="DELETE",
    headers={"X-XSRFToken": xsrf},
)
opener.open(stop_request, timeout=30).read()

print("Moodle LTI login, JupyterLab spawn, course materials, and package imports: ok")
