#!/usr/bin/env python3
"""Load Moodle grade APIs in the Lesson 2.3 verifier."""

from pathlib import Path


path = Path(__file__).with_name("verify-python-lesson23-v22.php")
text = path.read_text(encoding="utf-8")
old = "require_once $CFG->dirroot . '/mod/quiz/locallib.php';\n"
new = old + "require_once $CFG->libdir . '/gradelib.php';\n"
if new not in text:
    if text.count(old) != 1:
        raise RuntimeError("Quiz require line not found")
    path.write_text(text.replace(old, new), encoding="utf-8")
print(path)
