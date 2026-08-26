from pathlib import Path
import shutil


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "sample-content" / "introduction-to-python" / "python-lab" / "templates"
TARGET = Path("/mnt/d/workspace/python-lab-rescue/course-materials")
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

copied = []
for language in ("", "ja"):
    for filename in FILES:
        relative = Path(language) / filename if language else Path(filename)
        source = SOURCE / relative
        target = TARGET / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)
        copied.append(relative.as_posix())

print(f"copied {len(copied)} Chapter 1/2 notebooks")

