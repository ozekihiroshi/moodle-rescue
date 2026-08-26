#!/usr/bin/env python3
"""Align Project 3A Moodle submission with its two assessed artefacts."""

from pathlib import Path


TARGET = Path(__file__).with_name("build-python-chapter3-moodle-v35.py")


REPLACEMENTS = {
    "'assignsubmission_file_maxfiles' => 1,": "'assignsubmission_file_maxfiles' => 2,",
    "v35_plugin_config($assign->id, 'file', 'maxfilesubmissions', '1');":
        "v35_plugin_config($assign->id, 'file', 'maxfilesubmissions', '2');",
    "v35_plugin_config($assign->id, 'file', 'allowedfiletypes', '.py');":
        "v35_plugin_config($assign->id, 'file', 'allowedfiletypes', '.py,.ipynb');",
    "'pd.to_numeric', '0.0'] as $token":
        "'pd.to_numeric', '0.0', 'P3A_school_meal_delivery_review.ipynb'] as $token",
    "'file:maxfilesubmissions' => '1', 'file:allowedfiletypes' => '.py'":
        "'file:maxfilesubmissions' => '2', 'file:allowedfiletypes' => '.py,.ipynb'",
    "'assignment' => ['one_file' => true, 'type' => '.py', 'online_text' => false]":
        "'assignment' => ['files' => 2, 'types' => ['.py', '.ipynb'], 'online_text' => false]",
}


def main() -> None:
    text = TARGET.read_text(encoding="utf-8")
    changed = 0
    for old, new in REPLACEMENTS.items():
        if new in text:
            continue
        if old not in text:
            raise RuntimeError(f"Expected Moodle builder fragment not found: {old}")
        text = text.replace(old, new, 1)
        changed += 1
    TARGET.write_text(text, encoding="utf-8", newline="\n")
    print({"builder": str(TARGET), "replacements": changed, "max_files": 2})


if __name__ == "__main__":
    main()
