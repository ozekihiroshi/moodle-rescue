#!/usr/bin/env python3
"""Make the Moodle sequence verifier compare integer IDs explicitly."""

from pathlib import Path


path = Path(__file__).with_name("verify-python-lesson23-v22.php")
text = path.read_text(encoding="utf-8")
old = '    if (array_search($subcm->id, $parentids, true) >= array_search($projectcm->id, $parentids, true)) throw new RuntimeException("$shortname subsection order");\n'
new = '''    $subposition = array_search((int)$subcm->id, $parentids, true);
    $projectposition = array_search((int)$projectcm->id, $parentids, true);
    if ($subposition === false || $projectposition === false || $subposition >= $projectposition) {
        throw new RuntimeException("$shortname subsection order: 2.3=" . var_export($subposition, true) . ", 2.4=" . var_export($projectposition, true) . ", sequence=" . implode(',', $parentids));
    }
'''
if old in text:
    path.write_text(text.replace(old, new), encoding="utf-8")
elif new not in text:
    raise RuntimeError("Verifier sequence assertion was not found")
print(path)
