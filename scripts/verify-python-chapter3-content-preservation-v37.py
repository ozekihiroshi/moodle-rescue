import json
import re
from pathlib import Path


root = Path(__file__).resolve().parents[1]
content = root / "sample-content" / "introduction-to-python"
php = (root / "scripts" / "upgrade-python-chapter3-structure-v37.php").read_text(encoding="utf-8")
generated = json.loads(re.search(r"<<<'JSON'\n(.*?)\nJSON,", php, re.S).group(1))

sources = {
    "PYAI-INTRO": content / "structure-audits" / "chapter3-current-PYAI-INTRO.json",
    "PYAI-INTRO-JA": content / "structure-audits" / "chapter3-current-PYAI-INTRO-JA.json",
}
for course, path in sources.items():
    current = {
        item["name"]: item["content"]
        for item in json.loads(path.read_text(encoding="utf-8"))["activities"]
        if item["modname"] == "page"
    }
    for name, spec in generated[course].items():
        before = current[name]
        after = spec["content"]
        if before.count("<pre") != after.count("<pre"):
            raise RuntimeError(f"{course} {name}: code block count changed")
        for token in ("read_csv", "DataFrame", "sort_values", "to_csv"):
            if token in before and token not in after:
                raise RuntimeError(f"{course} {name}: lost {token}")
        print(f"ok page: {course} {name} — {after.count('<pre')} code blocks retained")

template_root = content / "python-lab" / "templates"
distributed_root = Path("/mnt/d/workspace/python-lab-rescue/course-materials")
for relative in [
    "07_tables_csv_pandas.ipynb", "08_filtering_boolean_logic.ipynb",
    "09_cleaning_audit_trail.ipynb", "10_grouping_statistics.ipynb",
    "ja/07_tables_csv_pandas.ipynb", "ja/08_filtering_boolean_logic.ipynb",
    "ja/09_cleaning_audit_trail.ipynb", "ja/10_grouping_statistics.ipynb",
]:
    new = json.loads((template_root / relative).read_text(encoding="utf-8"))
    old = json.loads((distributed_root / relative).read_text(encoding="utf-8"))
    new_code = {cell.get("id"): "".join(cell.get("source", [])) for cell in new["cells"] if cell["cell_type"] == "code"}
    old_code = {cell.get("id"): "".join(cell.get("source", [])) for cell in old["cells"] if cell["cell_type"] == "code"}
    if new_code != old_code:
        changed = sorted(set(new_code) | set(old_code) - {key for key in new_code if new_code.get(key) == old_code.get(key)})
        raise RuntimeError(f"{relative}: code cells changed: {changed}")
    print(f"ok notebook: {relative} — {len(new_code)} code cells unchanged")
