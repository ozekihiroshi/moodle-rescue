#!/usr/bin/env python3
"""Verify the source tree for the IPA AP V3 LessonMark course."""

from __future__ import annotations

import json
import struct
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "sample-content" / "ap-written-practice-ja"


def png_dimensions(path: Path) -> tuple[int, int]:
    with path.open("rb") as stream:
        signature = stream.read(24)
    if signature[:8] != b"\x89PNG\r\n\x1a\n":
        raise AssertionError(f"Not a PNG file: {path}")
    return struct.unpack(">II", signature[16:24])


def main() -> None:
    manifest = json.loads((CONTENT / "course-manifest-v3.json").read_text(encoding="utf-8"))
    catalog = json.loads((CONTENT / "question-catalog-v3.json").read_text(encoding="utf-8"))
    assert manifest["course"]["fullname"] == (
        "応用情報技術者試験 過去問題学習 — 令和7年度春期 午後・原文解答解説版"
    )
    assert manifest["source"]["question_domains"] == {
        "1": "情報セキュリティ",
        "2": "経営戦略",
        "3": "アルゴリズム・プログラミング",
        "4": "システムアーキテクチャ・クラウド",
        "5": "ネットワーク",
        "6": "データベース",
        "7": "組込み・ソフトウェア設計",
        "8": "システム開発・エラーハンドリング",
        "9": "プロジェクトマネジメント",
        "10": "サービスマネジメント",
        "11": "システム監査",
    }
    questions = catalog["questions"]
    assert [item["number"] for item in questions] == list(range(2, 12))
    assert len(manifest["activities"]) == 12

    total_images = 5
    total_answers = 10
    for question in questions:
        number = int(question["number"])
        pages = [int(page) for page in question["pages"]]
        unit = CONTENT / "units" / f"2025-spring-q{number:02d}-{question['slug']}-v3"
        markdown_path = unit / "10-official-problem-and-commentary.md"
        markdown = markdown_path.read_text(encoding="utf-8")
        expected_answers = len(question["answers"]) + len(question["deep_reading"])
        assert markdown.count("> [!RESPONSE]") == expected_answers, markdown_path
        assert markdown.count("> [!ANSWER]") == expected_answers, markdown_path
        assert markdown.count("> [!CHOICE]") == 0, markdown_path
        for answer in question["answers"]:
            for line in str(answer["official"]).splitlines():
                assert line in markdown, (markdown_path, line)
        image_files = sorted((unit / "images").glob("page-*.png"))
        expected_files = [unit / "images" / f"page-{page:02d}.png" for page in pages]
        assert image_files == expected_files, unit
        for image in image_files:
            width, height = png_dimensions(image)
            assert width == 1032 and 1459 <= height <= 1461, (image, (width, height))
        total_images += len(image_files)
        total_answers += expected_answers

    assert total_images == 55
    assert total_answers == 93
    print(json.dumps({
        "questions": 11,
        "lessonmark_activities": 12,
        "official_page_images": total_images,
        "answer_disclosures": total_answers,
        "status": "ok",
    }, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
