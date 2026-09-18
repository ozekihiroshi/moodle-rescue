#!/usr/bin/env python3
"""Mechanical HTML-to-Markdown export from an isolated Moodle course manifest.

Run in WSL with pandoc installed. Existing outputs are never overwritten.
"""
import argparse
import hashlib
import html as html_module
import json
import re
import subprocess
from pathlib import Path


def convert(source):
    def pandoc(html):
        blocks = []
        def preserve(match):
            code = html_module.unescape(re.sub(r'<[^>]*>', '', match[1])).strip('\n')
            fence = '`' * max(3, 1 + max((len(s) for s in re.findall(r'`+', code)), default=0))
            blocks.append(fence + '\n' + code + '\n' + fence)
            return '<p>CODEBLOCKPLACEHOLDER' + str(len(blocks) - 1) + '</p>'
        html = re.sub(r'<pre\b[^>]*>(.*?)</pre>', preserve, html, flags=re.S)
        result = subprocess.run(
            ["pandoc", "--from=html", "--to=gfm", "--wrap=none"],
            input=html, text=True, capture_output=True, check=True,
        ).stdout.strip()
        for i, block in reversed(list(enumerate(blocks))):
            result = result.replace('CODEBLOCKPLACEHOLDER' + str(i), block)
        return result

    answers = []
    def answer(match):
        summary = re.search(r"<summary\b[^>]*>(.*?)</summary>", match[1], flags=re.S)
        title = pandoc(summary[1]) if summary else "解答と確認"
        body = re.sub(r"<summary\b[^>]*>.*?</summary>", "", match[1], flags=re.S)
        md = pandoc(body)
        answers.append("> [!ANSWER]\n> " + title + "\n>\n" +
                       "\n".join("> " + line if line else ">" for line in md.splitlines()))
        return "<p>ANSWERPLACEHOLDER" + str(len(answers) - 1) + "</p>"

    source = re.sub(r'<p[^>]*style="display:none"[^>]*>.*?</p>', '', source, flags=re.S)
    source = re.sub(r'<details\b[^>]*>(.*?)</details>', answer, source, flags=re.S)
    source = re.sub(r'</?(?:div|aside)\b[^>]*>', '', source)
    source = re.sub(r'\s(?:style|class)="[^"]*"', '', source)
    markdown = pandoc(source)
    for i, block in reversed(list(enumerate(answers))):
        markdown = markdown.replace("ANSWERPLACEHOLDER" + str(i), block)
    return markdown + "\n"


def normalize_markdown_answers(source):
    """Translate legacy raw HTML disclosures without changing their Markdown body."""
    def replace(match):
        title = html_module.unescape(re.sub(r'<[^>]+>', '', match[1])).strip()
        body = match[2].strip('\n')
        return '> [!ANSWER]\n> ' + title + '\n>\n' + '\n'.join(
            '> ' + line if line else '>' for line in body.splitlines()) + '\n'
    return re.sub(r'<details\b[^>]*>\s*<summary\b[^>]*>(.*?)</summary>(.*?)</details>',
                  replace, source, flags=re.S)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("manifest", type=Path)
    parser.add_argument("output", type=Path)
    parser.add_argument("--chapter", type=int, required=True)
    args = parser.parse_args()
    manifest = json.loads(args.manifest.read_text())
    sections = {s["number"]: s for s in manifest["sections"]}
    records = []
    args.output.mkdir(parents=True, exist_ok=True)
    for activity in manifest["activities"]:
        if activity["module"] != "page":
            continue
        section = sections[activity["section"]]
        name = section["name"] or ""
        if not (activity["section"] == args.chapter or name.startswith(str(args.chapter) + ".")):
            continue
        filename = "page-" + str(activity["id"]) + ".md"
        path = args.output / filename
        source = activity["html"]
        markdown = normalize_markdown_answers(source) if activity["format"] == 4 else convert(source)
        if not path.exists():
            path.write_text(markdown, encoding="utf-8")
        records.append({"pagecmid": activity["id"], "name": activity["name"],
                        "section": activity["section"], "file": filename,
                        "source_sha256": hashlib.sha256(source.encode()).hexdigest()})
    target = args.output / ("chapter-" + str(args.chapter) + ".json")
    if target.exists():
        raise SystemExit("Manifest already exists; inspect rather than overwrite: " + str(target))
    target.write_text(json.dumps({"courseid": manifest["courseid"], "chapter": args.chapter,
                                 "pages": records}, ensure_ascii=False, indent=2) + "\n")
    print(f"Chapter {args.chapter}: {len(records)} page sources exported to {args.output}")


if __name__ == "__main__":
    main()
