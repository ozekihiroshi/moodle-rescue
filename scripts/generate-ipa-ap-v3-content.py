#!/usr/bin/env python3
"""Generate LessonMark Markdown and optional official page images for IPA AP V3."""

from __future__ import annotations

import argparse
import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "sample-content" / "ap-written-practice-ja"
CATALOG = CONTENT / "question-catalog-v3.json"
OFFICIAL_PROBLEM = "https://www.ipa.go.jp/shiken/mondai-kaiotu/nl10bi0000009lh8-att/2025r07h_ap_pm_qs.pdf"
OFFICIAL_ANSWERS = "https://www.ipa.go.jp/shiken/mondai-kaiotu/nl10bi0000009lh8-att/2025r07h_ap_pm_ans.pdf"
OFFICIAL_COMMENTARY = "https://www.ipa.go.jp/shiken/mondai-kaiotu/nl10bi0000009lh8-att/2025r07h_ap_pm_cmnt.pdf"


def blockquote(text: str) -> str:
    return "\n".join("> " + line if line else ">" for line in text.splitlines())


def render_markdown(question: dict[str, object]) -> str:
    number = int(question["number"])
    title = str(question["title"])
    pages = [int(page) for page in question["pages"]]
    lines = [
        f"# 問{number} {title} — 公式問題と解答解説",
        "",
        "> [!NOTE]",
        f"> 出典：独立行政法人情報処理推進機構（IPA）「令和7年度 春期 応用情報技術者試験 午後 問{number}」（問題冊子{pages[0]}〜{pages[-1]}ページ）。以下の問題画像は公式PDFをページ単位で表示したものです。",
        "",
        "## 最初に行うこと",
        "",
        f"まず{pages[0]}〜{pages[-1]}ページを通して読み、各設問の答案を手元へ書いてください。完全な文章にならなくても構いません。根拠にした本文、図、表も一緒に控えます。",
        "",
        "問題文を確認した後、同じページ内の各設問へ進みます。自分の解答を入力してから、その直下にある公式解答例と解説を開きます。",
        "",
        "## 公式問題",
        "",
    ]
    for page in pages:
        lines.extend([
            f"### 問題冊子 {page}ページ",
            "",
            f"![IPA令和7年度春期応用情報技術者試験午後問{number} 問題冊子{page}ページ](@@PLUGINFILE@@/images/page-{page:02d}.png)",
            "",
        ])

    lines.extend([
        "## 設問に解答し、公式解答例と照合する",
        "",
        "解答例はIPAの公式解答例に基づきます。解説は、問題本文と公式採点講評を照合して本教材が付したものです。まず自分の解答を入力し、その直下にある「公式解答例と解説を確認する」を任意のタイミングで開いてください。",
        "",
        "入力内容はこのブラウザ内だけに保存され、Moodleへ提出・採点されません。正誤だけで終わらず、解説に示された根拠を上の公式問題画像で確認します。",
        "",
    ])
    for answer in question["answers"]:
        lines.extend([
            f"### {answer['label']}",
            "",
            str(answer["instruction"]),
            "",
            "> [!RESPONSE]",
            "> まず自分の解答を入力します。必要な場合は、空欄記号や小問番号を付けて分けてください。",
            "",
            "> [!ANSWER]",
            blockquote("**公式解答例**\n\n" + str(answer["official"]) + "\n\n" + str(answer["explanation"])),
            "",
        ])

    lines.extend([
        "## 問題文を深く読む",
        "",
        "ここからは正答を増やすためだけの設問ではありません。問題文にある事実、設計や判断の根拠、実務で追加確認すべきことを分けて考えます。考察例は唯一の正解ではありません。",
        "",
    ])
    for index, item in enumerate(question["deep_reading"], start=1):
        lines.extend([
            f"### {index}. {item['heading']}",
            "",
            str(item["prompt"]),
            "",
            "> [!RESPONSE]",
            "> 公式問題の本文、図、表を根拠にして考えを入力します。",
            "",
            "> [!ANSWER]",
            blockquote("**考察例**\n\n" + str(item["example"])),
            "",
        ])

    lines.extend([
        "## 出典",
        "",
        f"- [IPA公式問題冊子PDF]({OFFICIAL_PROBLEM})",
        f"- [IPA公式解答例PDF]({OFFICIAL_ANSWERS})",
        f"- [IPA公式採点講評PDF]({OFFICIAL_COMMENTARY})",
        "",
    ])
    return "\n".join(lines)


def render_images(pdf_path: Path, output: Path, pages: list[int]) -> None:
    try:
        import pymupdf
    except ImportError as exc:
        raise SystemExit("PyMuPDF is required only when --pdf is used.") from exc

    document = pymupdf.open(pdf_path)
    image_dir = output / "images"
    image_dir.mkdir(parents=True, exist_ok=True)
    scale = 1032 / document[5].rect.width
    for pdf_page in pages:
        page = document[pdf_page - 1]
        pixmap = page.get_pixmap(matrix=pymupdf.Matrix(scale, scale), alpha=False)
        pixmap.save(image_dir / f"page-{pdf_page:02d}.png")
    document.close()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--pdf", type=Path, help="Official problem PDF used to render source page images")
    args = parser.parse_args()

    catalog = json.loads(CATALOG.read_text(encoding="utf-8"))
    for question in catalog["questions"]:
        number = int(question["number"])
        unit = CONTENT / "units" / f"2025-spring-q{number:02d}-{question['slug']}-v3"
        unit.mkdir(parents=True, exist_ok=True)
        markdown = unit / "10-official-problem-and-commentary.md"
        markdown.write_text(render_markdown(question), encoding="utf-8", newline="\n")
        if args.pdf:
            render_images(args.pdf.resolve(), unit, [int(page) for page in question["pages"]])
        print(markdown.relative_to(ROOT))


if __name__ == "__main__":
    main()
