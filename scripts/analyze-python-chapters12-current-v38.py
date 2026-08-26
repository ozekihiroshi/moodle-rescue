import json
import re
from html import unescape
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
AUDITS = ROOT / "sample-content" / "introduction-to-python" / "structure-audits"


def clean(fragment: str) -> str:
    text = re.sub(r"<[^>]+>", " ", fragment)
    return re.sub(r"\s+", " ", unescape(text)).strip()


def headings(content: str) -> list[dict[str, str]]:
    return [
        {"level": match.group(1).lower(), "text": clean(match.group(2))}
        for match in re.finditer(
            r"<(h[1-6])\b[^>]*>(.*?)</\1>", content, flags=re.I | re.S
        )
    ]


for shortname in ("PYAI-INTRO", "PYAI-INTRO-JA"):
    source = AUDITS / f"chapter3-current-{shortname}.json"
    document = json.loads(source.read_text(encoding="utf-8"))
    selected = []
    for activity in document["activities"]:
        section = activity.get("section_name", "")
        if not re.match(r"^[12]\.", section):
            continue
        item = {
            "cmid": activity["cmid"],
            "modname": activity["modname"],
            "name": activity["name"],
            "section_number": activity["section_number"],
            "section_name": section,
        }
        if activity["modname"] == "page":
            content = activity.get("content", "")
            item["headings"] = headings(content)
            item["code_blocks"] = len(re.findall(r"<pre\b", content, flags=re.I))
            item["tables"] = len(re.findall(r"<table\b", content, flags=re.I))
            item["marker"] = re.findall(r"PYAI-V\d+-[A-Z0-9-]+", content)
        if activity["modname"] == "quiz":
            item["slots"] = activity.get("slots")
        selected.append(item)

    target = AUDITS / f"chapters12-current-{shortname}.json"
    target.write_text(
        json.dumps({"course": shortname, "activities": selected}, indent=2, ensure_ascii=False)
        + "\n",
        encoding="utf-8",
    )
    print(f"wrote {target.relative_to(ROOT)} ({len(selected)} activities)")

