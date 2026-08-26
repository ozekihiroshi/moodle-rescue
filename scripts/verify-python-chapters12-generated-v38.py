import json
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "sample-content" / "introduction-to-python"
AUDITS = CONTENT / "structure-audits"
TEMPLATES = CONTENT / "python-lab" / "templates"

expected = {
    "01_programs_values_output.ipynb": ("1.1", 4),
    "02_variables_types_calculations.ipynb": ("1.2", 4),
    "03_basic_scalar_types.ipynb": ("1.3", 4),
    "04_strings_input_formatting.ipynb": ("1.4", 6),
    "03_conditions_boundaries.ipynb": ("1.5", 7),
    "04_loops_accumulators.ipynb": ("1.6", 7),
    "05_lists_dictionaries_records.ipynb": ("2.1", 7),
    "06_functions_errors_testing.ipynb": ("2.2", 7),
    "07_files_csv.ipynb": ("2.3", 6),
}

snapshot = json.loads((AUDITS / "chapters12-pre-v38-notebook-code.json").read_text(encoding="utf-8"))
for language in ("", "ja"):
    for filename, (number, count) in expected.items():
        relative = (Path(language) / filename).as_posix() if language else filename
        document = json.loads((TEMPLATES / relative).read_text(encoding="utf-8"))
        current_code = [
            {"id": cell.get("id"), "source": cell.get("source", [])}
            for cell in document["cells"] if cell.get("cell_type") == "code"
        ]
        if current_code != snapshot[relative]:
            raise RuntimeError(f"{relative}: code cells changed")
        markdown = [
            "".join(cell.get("source", []))
            for cell in document["cells"] if cell.get("cell_type") == "markdown"
        ]
        groups = [text.splitlines()[0] for text in markdown if text.startswith(f"## {number}.")]
        if len(groups) != count:
            raise RuntimeError(f"{relative}: expected {count} groups, found {groups}")
        if any(line.startswith("#### ") for text in markdown for line in text.splitlines()):
            raise RuntimeError(f"{relative}: unexpected fourth-level heading")
        if not any("summary" in text.lower() or "まとめ" in text for text in markdown):
            raise RuntimeError(f"{relative}: summary missing")
        if not any("next" in text.lower() or "次" in text or "1.7" in text or "2.4" in text for text in markdown):
            raise RuntimeError(f"{relative}: next connection missing")
        if document.get("metadata", {}).get("pyai", {}).get("structure_revision") != 38:
            raise RuntimeError(f"{relative}: revision metadata missing")
        print(f"ok: {relative} - {groups[0]} ... {groups[-1]}")

php = (ROOT / "scripts" / "upgrade-python-chapters12-structure-v38.php").read_text(encoding="utf-8")
if php.count("PYAI-V38-TEXTBOOK-STRUCTURE") != 18:
    raise RuntimeError("generated Moodle payload does not contain 18 page markers plus guard")
if "<h4" in php:
    raise RuntimeError("generated Moodle payload contains h4")
print("generated Moodle payload: 18 pages, no h4")

