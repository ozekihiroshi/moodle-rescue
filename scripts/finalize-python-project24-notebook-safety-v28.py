from __future__ import annotations

import json
import runpy
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
FINALIZER = ROOT / "scripts" / "finalize-python-project24-learner-brief-v25.py"


def collapse_adjacent_rule(lines: list[str], marker: str) -> bool:
    for index in range(len(lines) - 2):
        if marker not in lines[index] or marker not in lines[index + 1]:
            continue
        second_marker = lines[index + 1].find(marker)
        prefix = lines[index + 1][:second_marker].rstrip()
        if prefix and lines[index + 2].strip() == prefix.strip():
            lines[index + 1] = prefix
            del lines[index + 2]
            return True
    return False


source = FINALIZER.read_text(encoding="utf-8")
lines = source.splitlines()
while collapse_adjacent_rule(lines, "IDs and titles"):
    pass
while collapse_adjacent_rule(lines, "ID"):
    pass
FINALIZER.write_text("\n".join(lines) + "\n", encoding="utf-8")

# Keep the v26 alignment script safe to rerun: once the replacement exists,
# do not treat its original text as another replacement target.
aligner = ROOT / "scripts" / "align-python-project24-v26.py"
aligner_source = aligner.read_text(encoding="utf-8")
old_loop = '''for old, new in replacements.items():
    if old not in text:
        if new in text:
            continue
        raise SystemExit("Finalizer alignment anchor missing: " + old[:80])
    text = text.replace(old, new, 1)
'''
new_loop = '''for old, new in replacements.items():
    if new in text:
        continue
    if old not in text:
        raise SystemExit("Finalizer alignment anchor missing: " + old[:80])
    text = text.replace(old, new, 1)
'''
if old_loop in aligner_source:
    aligner.write_text(aligner_source.replace(old_loop, new_loop, 1), encoding="utf-8")
elif new_loop not in aligner_source:
    raise SystemExit("Alignment idempotency anchor missing")

# Regenerate the canonical READMEs, specifications, Moodle body, and the cells
# managed by the earlier finalizer before applying the execution-safety cells.
runpy.run_path(str(FINALIZER), run_name="__main__")

ja_warning = (
    "\u672a\u5b8c\u6210\u306e\u95a2\u6570\u304c\u3042\u308b\u9593\u306f\u3001"
    "\u5b9f\u884c\u3059\u308b\u3068NotImplementedError\u304c\u8868\u793a\u3055\u308c\u307e\u3059\u3002"
    "\u3053\u308c\u306fPython Lab\u306e\u6545\u969c\u3067\u306f\u306a\u304f\u3001"
    "\u672a\u5b9f\u88c5\u7b87\u6240\u304c\u6b8b\u3063\u3066\u3044\u308b\u3053\u3068\u3092\u793a\u3057\u307e\u3059\u3002"
)
ja_fail = (
    "\u30d7\u30ed\u30b0\u30e9\u30e0\u306f\u672a\u5b8c\u6210\u304b\u3001\u30a8\u30e9\u30fc\u304c\u3042\u308a\u307e\u3059\u3002"
    "\u8868\u793a\u3055\u308c\u305f\u30a8\u30e9\u30fc\u3068README\u3092\u78ba\u8a8d\u3057\u3001"
    "\u4fee\u6b63\u30fb\u4fdd\u5b58\u3057\u3066\u3082\u3046\u4e00\u5ea6\u5b9f\u884c\u3057\u3066\u304f\u3060\u3055\u3044\u3002"
)
ja_old_output = (
    "\u76f4\u524d\u306e\u5b9f\u884c\u304c\u6210\u529f\u3057\u3066\u3044\u306a\u3044\u305f\u3081\u3001"
    "\u51fa\u529b\u306f\u8868\u793a\u3057\u307e\u305b\u3093\u3002"
    "\u4ee5\u524d\u306eCSV\u304c\u6b8b\u3063\u3066\u3044\u308b\u53ef\u80fd\u6027\u304c\u3042\u308a\u307e\u3059\u3002"
)
ja_missing_output = (
    "\u5b9f\u884c\u306f\u6210\u529f\u3057\u307e\u3057\u305f\u304c\u3001\u51fa\u529bCSV\u304c\u3042\u308a\u307e\u305b\u3093\u3002"
    "save_books()\u3068run_project()\u3092\u78ba\u8a8d\u3057\u3066\u304f\u3060\u3055\u3044\u3002"
)

notebook_paths = [
    (ROOT / "sample-content" / "introduction-to-python" / "python-lab" / "templates" / "P2_csv_library_manager.ipynb", False),
    (ROOT / "sample-content" / "introduction-to-python" / "python-lab" / "templates" / "ja" / "P2_csv_library_manager.ipynb", True),
]

for path, japanese in notebook_paths:
    notebook = json.loads(path.read_text(encoding="utf-8"))
    cell3 = "".join(notebook["cells"][3]["source"])
    warning = ja_warning if japanese else (
        "Before the functions are complete, running the starter normally raises "
        "`NotImplementedError`. This indicates unfinished work, not a broken Python Lab."
    )
    if warning not in cell3:
        cell3 = cell3.rstrip() + "\n\n" + warning + "\n"
        notebook["cells"][3]["source"] = cell3.splitlines(keepends=True)

    cwd_label = "\u73fe\u5728\u306e\u4f5c\u696d\u30d5\u30a9\u30eb\u30c0" if japanese else "Working folder"
    project_label = "\u30d7\u30ed\u30b8\u30a7\u30af\u30c8\u30d5\u30a9\u30eb\u30c0" if japanese else "Project folder"
    found_label = "\u30d7\u30ed\u30b8\u30a7\u30af\u30c8\u3092\u78ba\u8a8d\u3067\u304d\u305f" if japanese else "Project found"
    cell2 = f'''from pathlib import Path
import csv

project = Path.cwd() / "projects" / "library-manager"
print({cwd_label!r} + ":", Path.cwd())
print({project_label!r} + ":", project)
print({found_label!r} + ":", project.is_dir())

with (project / "data" / "books.csv").open(encoding="utf-8", newline="") as file:
    for row in csv.DictReader(file):
        print(row)
'''
    notebook["cells"][2]["source"] = cell2.splitlines(keepends=True)

    exit_label = "\u30d7\u30ed\u30b0\u30e9\u30e0\u306e\u7d42\u4e86\u30b3\u30fc\u30c9" if japanese else "Program exit code"
    fail_message = ja_fail if japanese else (
        "The program is still incomplete or contains an error. Review the traceback "
        "and README, edit, save, and run again."
    )
    cell4 = f'''# Save library_manager.py with Ctrl+S before running.
import subprocess
import sys

program_result = subprocess.run([sys.executable, str(project / "library_manager.py")])
program_succeeded = program_result.returncode == 0
print({exit_label!r} + ":", program_result.returncode)
if not program_succeeded:
    print({fail_message!r})
'''
    notebook["cells"][4]["source"] = cell4.splitlines(keepends=True)

    old_message = ja_old_output if japanese else (
        "Output is not shown because the preceding run did not succeed. "
        "An older CSV may still exist."
    )
    missing_message = ja_missing_output if japanese else (
        "The run succeeded, but the expected output CSV was not created. "
        "Review save_books() and run_project()."
    )
    cell5 = f'''# Show the CSV only when the immediately preceding program run succeeded.
output_file = project / "output" / "books_updated.csv"
if not globals().get("program_succeeded", False):
    print({old_message!r})
elif output_file.is_file():
    print(output_file.read_text(encoding="utf-8"))
else:
    print({missing_message!r})
'''
    notebook["cells"][5]["source"] = cell5.splitlines(keepends=True)
    path.write_text(json.dumps(notebook, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")

verifier = ROOT / "scripts" / "verify-python-project24-v24.php"
verifier_source = verifier.read_text(encoding="utf-8")
anchor = '    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI path");\n'
ja_rule = "ID\u3068\u66f8\u540d\u306f\u3001\u691c\u8a3c\u30fb\u691c\u7d22\u30fb\u4fdd\u5b58\u306e\u524d\u306b\u524d\u5f8c\u306e\u7a7a\u767d\u3092"
check = f'''    $whitespaceRule = $ja ? '{ja_rule}' : 'IDs and titles are stripped';
    if (substr_count($page->content, $whitespaceRule) !== 1 || substr_count($assign->intro, $whitespaceRule) !== 1) throw new RuntimeException("$shortname whitespace rule must appear exactly once");
'''
if check not in verifier_source:
    if anchor not in verifier_source:
        raise SystemExit("Moodle verifier anchor missing")
    verifier.write_text(verifier_source.replace(anchor, check + anchor, 1), encoding="utf-8")

print("Project 2.4 duplicate rule and Notebook execution safety finalized")
