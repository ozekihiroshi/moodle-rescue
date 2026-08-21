#!/usr/bin/env python3
"""Verify notebook activities in the rendered Moodle learner course page."""

import html
import http.cookiejar
import os
import urllib.parse
import urllib.request
from html.parser import HTMLParser


class LoginParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.in_login = False
        self.fields: dict[str, str] = {}

    def handle_starttag(self, tag, attrs) -> None:
        values = dict(attrs)
        if tag == "form" and "login/index.php" in values.get("action", ""):
            self.in_login = True
        elif self.in_login and tag == "input" and values.get("name"):
            self.fields[values["name"]] = values.get("value", "")

    def handle_endtag(self, tag) -> None:
        if tag == "form" and self.in_login:
            self.in_login = False


class TextParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.parts: list[str] = []

    def handle_data(self, data: str) -> None:
        if data.strip():
            self.parts.append(data.strip())


base = os.environ.get("MOODLE_WWWROOT", "http://localhost:8083").rstrip("/")
courseid = os.environ.get("PYTHON_COURSE_ID", "10")
username = os.environ.get("LTI_SMOKE_USERNAME", os.environ["MOODLE_ADMIN_USER"])
password = os.environ.get("LTI_SMOKE_PASSWORD", os.environ["MOODLE_ADMIN_PASSWORD"])

opener = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
)
login_url = f"{base}/login/index.php"
login_page = opener.open(login_url, timeout=30)
parser = LoginParser()
parser.feed(login_page.read().decode("utf-8"))
parser.fields.update({"username": username, "password": password})
response = opener.open(
    urllib.request.Request(login_url, data=urllib.parse.urlencode(parser.fields).encode()),
    timeout=30,
)
if "/login/index.php" in response.geturl():
    raise RuntimeError("Moodle learner login failed")

course = opener.open(f"{base}/course/view.php?id={courseid}", timeout=30)
textparser = TextParser()
textparser.feed(course.read().decode("utf-8"))
rendered = html.unescape(" ".join(textparser.parts))

lesson_sequences = [
    ("Lesson 1: Your first Python program", "Python Lab 01: Programs, values, and output", "Knowledge check: Lesson 1: Your first Python program"),
    ("Lesson 2: Variables, types, input, and calculations", "Python Lab 02: Variables, types, and calculations", "Knowledge check: Lesson 2: Variables, types, input, and calculations"),
    ("Lesson 3: Decisions with conditions", "Python Lab 03: Conditions and boundaries", "Knowledge check: Lesson 3: Decisions with conditions"),
    ("Lesson 4: Repetition with loops", "Python Lab 04: Loops and accumulators", "Knowledge check: Lesson 4: Repetition with loops"),
    ("Lesson 5: Lists and dictionaries", "Python Lab 05: Lists, dictionaries, and records", "Knowledge check: Lesson 5: Lists and dictionaries"),
    ("Lesson 6: Functions, errors, and testing", "Python Lab 06: Functions, errors, and testing", "Knowledge check: Lesson 6: Functions, errors, and testing"),
    ("Lesson 7: Tables, CSV, and pandas", "Python Lab 07: Tables, CSV, and pandas", "Knowledge check: Lesson 7: Tables, CSV, and pandas"),
    ("Lesson 8: Inspecting and selecting data", "Python Lab 08: Filtering and Boolean logic", "Knowledge check: Lesson 8: Inspecting and selecting data"),
    ("Lesson 9: Cleaning data", "Python Lab 09: Cleaning with an audit trail", "Knowledge check: Lesson 9: Cleaning data"),
    ("Lesson 10: Grouping and summary statistics", "Python Lab 10: Grouping and statistics", "Knowledge check: Lesson 10: Grouping and summary statistics"),
    ("Lesson 11: Visualisation and evidence", "Python Lab 11: Visualisation and evidence", "Knowledge check: Lesson 11: Visualisation and evidence"),
    ("Lesson 12: Processing larger CSV files in chunks", "Python Lab 12: Scaling, chunks, and validation", "Applied check: Scaling up safely"),
]
project_sequences = [
    ("Python Lab project: Weekly support report", "Mini-project: Weekly learning-centre support report"),
    ("Python Lab project: Monthly centre performance report", "Foundation project: Monthly learning-centre performance report"),
    ("Python Lab project: Learning-centre analysis", "Data analysis project: Learning centres"),
    ("Python Lab project: Question to evidence", "Final project: From question to evidence"),
    ("Python Lab project: Scale-up capstone", "Scale-up capstone: Operations evidence"),
]

hierarchy_labels = [
    "Chapter 1 — Python Programming Foundations",
    "1.1 Programs, values, and output",
    "1.2 Variables, types, input, and calculations",
    "1.3 Decisions with conditions",
    "1.4 Repetition with loops",
    "1.5 Applied project: Weekly support report",
    "Chapter 2 — Data Structures and Reliable Programs",
    "2.1 Lists, dictionaries, and records",
    "2.2 Functions, errors, and testing",
    "2.3 Applied project: Monthly centre performance report",
    "Chapter 3 — Analysing Tabular Data",
    "3.1 Tables, CSV, and pandas",
    "3.2 Filtering and Boolean logic",
    "3.3 Cleaning data with an audit trail",
    "3.4 Grouping and summary statistics",
    "Chapter 4 — Communicating Evidence",
    "4.1 Visualisation and evidence",
    "4.2 Guided project: Learning-centre analysis",
    "4.3 Final project: From question to evidence",
    "Chapter 5 — Scaling Up",
    "5.1 Processing larger CSV files safely",
    "5.2 Scale-up capstone project",
]

hierarchy_positions = [rendered.find(label) for label in hierarchy_labels]
if -1 in hierarchy_positions or hierarchy_positions != sorted(hierarchy_positions):
    raise RuntimeError("Chapter and subsection headings are missing or out of order")
for page, lab, check in lesson_sequences:
    positions = [rendered.find(item) for item in (page, lab, check)]
    if -1 in positions or positions != sorted(positions) or len(set(positions)) != 3:
        raise RuntimeError(f"Learner order is incorrect: {page} -> {lab} -> {check}")
for lab, assignment in project_sequences:
    lab_position = rendered.find(lab)
    assignment_position = rendered.find(assignment, lab_position + len(lab))
    if lab_position == -1 or assignment_position == -1:
        raise RuntimeError(f"Learner order is incorrect: {lab} -> {assignment}")

print(
    "learner course view: 5 chapters, 17 subsections, 12 lesson labs, "
    "and 5 project labs visible in learning order"
)
