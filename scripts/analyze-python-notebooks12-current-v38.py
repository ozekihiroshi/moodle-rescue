import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content" / "introduction-to-python" / "python-lab" / "templates"
FILES = [
    "01_programs_values_output.ipynb",
    "02_variables_types_calculations.ipynb",
    "03_basic_scalar_types.ipynb",
    "04_strings_input_formatting.ipynb",
    "03_conditions_boundaries.ipynb",
    "04_loops_accumulators.ipynb",
    "05_lists_dictionaries_records.ipynb",
    "06_functions_errors_testing.ipynb",
    "07_files_csv.ipynb",
]


result = {}
for language in ("", "ja"):
    for relative in FILES:
        path = BASE / language / relative
        document = json.loads(path.read_text(encoding="utf-8"))
        key = str(Path(language) / relative) if language else relative
        cells = []
        for index, cell in enumerate(document["cells"]):
            source = "".join(cell.get("source", []))
            first = source.splitlines()[0] if source.splitlines() else ""
            cells.append(
                {
                    "index": index,
                    "id": cell.get("id"),
                    "type": cell.get("cell_type"),
                    "first_line": first,
                }
            )
        result[key] = {
            "metadata": document.get("metadata", {}).get("pyai", {}),
            "cells": cells,
        }

target = ROOT / "sample-content" / "introduction-to-python" / "structure-audits" / "notebooks12-current.json"
target.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
print(f"wrote {target.relative_to(ROOT)} ({len(result)} notebooks)")

