import html
import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "backups" / "visual-verification" / "chapters12-v38"
SOURCE = OUTPUT / "pages.json"
LABELS = {181: "lesson11-ja", 185: "lesson15-ja", 189: "lesson21-ja", 289: "lesson23-ja"}

STYLE = """
body{margin:0;background:#f5f6f7;color:#1f2933;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Noto Sans JP",sans-serif;line-height:1.65}
header{background:#fff;border-bottom:1px solid #d8dce1;padding:18px 28px;color:#1f2933}
main{max-width:1040px;margin:24px auto;background:#fff;padding:32px 42px;border:1px solid #d8dce1;border-radius:8px}
h1{font-size:2rem;margin:.2em 0 1em}h2{font-size:1.42rem}h3{font-size:1.16rem;margin-top:1.5em}
pre{white-space:pre-wrap}table{border-collapse:collapse;width:100%;margin:1em 0}th,td{border:1px solid #d8dce1;padding:.55em .7em;text-align:left}th{background:#f1f3f5}
code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace}aside{box-sizing:border-box}
"""

OUTPUT.mkdir(parents=True, exist_ok=True)
for page in json.loads(SOURCE.read_text(encoding="utf-8")):
    cmid = int(page["cmid"])
    document = f"""<!doctype html><html lang="ja"><head><meta charset="utf-8"><title>{html.escape(page['name'])}</title><style>{STYLE}</style></head><body><header>Moodle local display verification — CMID {cmid}</header><main><h1>{html.escape(page['name'])}</h1>{page['content']}</main></body></html>"""
    target = OUTPUT / f"{LABELS[cmid]}.html"
    target.write_text(document, encoding="utf-8")
    print(f"built: {target}")

