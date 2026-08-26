import json
import re
from html import unescape
from pathlib import Path


root = Path(__file__).resolve().parents[1]
php = (root / "scripts" / "upgrade-python-chapter3-structure-v37.php").read_text(encoding="utf-8")
raw = re.search(r"<<<'JSON'\n(.*?)\nJSON,", php, re.S).group(1)
pages = json.loads(raw)

expected = {"3.1": 6, "3.2": 7, "3.3": 7, "3.4": 6}
for course, items in pages.items():
    print(f"\n### {course}")
    for name, spec in items.items():
        headings = []
        for level, text in re.findall(r"<h([23])[^>]*>(.*?)</h\1>", spec["content"], re.S):
            headings.append((int(level), unescape(re.sub(r"<[^>]+>", "", text))))
        number = re.search(r"3\.[1-4]", name).group(0)
        groups = [text for level, text in headings if level == 2 and re.match(rf"{re.escape(number)}\.\d+ ", text)]
        if len(groups) != expected[number]:
            raise RuntimeError(f"{name}: {groups}")
        if "<h4" in spec["content"]:
            raise RuntimeError(f"{name}: h4 remained")
        print(name)
        for level, text in headings:
            print("  " + "  " * (level - 2) + text)

templates = root / "sample-content" / "introduction-to-python" / "python-lab" / "templates"
print("\n### Notebooks")
for relative in [
    "07_tables_csv_pandas.ipynb", "08_filtering_boolean_logic.ipynb",
    "09_cleaning_audit_trail.ipynb", "10_grouping_statistics.ipynb",
    "ja/07_tables_csv_pandas.ipynb", "ja/08_filtering_boolean_logic.ipynb",
    "ja/09_cleaning_audit_trail.ipynb", "ja/10_grouping_statistics.ipynb",
]:
    document = json.loads((templates / relative).read_text(encoding="utf-8"))
    headings = [
        "".join(cell.get("source", [])).splitlines()[0]
        for cell in document["cells"]
        if cell["cell_type"] == "markdown" and "".join(cell.get("source", [])).startswith("##")
    ]
    print(relative, headings)
