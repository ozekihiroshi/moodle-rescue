#!/usr/bin/env python3
"""Apply reviewed exact-match Japanese translation memory to pending segments."""

from __future__ import annotations

import argparse
import json
from pathlib import Path


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("segments", type=Path)
    parser.add_argument("memory", type=Path)
    args = parser.parse_args()

    document = json.loads(args.segments.read_text(encoding="utf-8"))
    memory = json.loads(args.memory.read_text(encoding="utf-8"))
    translations = memory["translations"]
    applied = 0
    for segment in document["segments"]:
        if segment["status"] != "pending":
            continue
        target = translations.get(segment["source"])
        if target is None:
            continue
        if not isinstance(target, str) or target == "":
            raise SystemExit(f"Empty translation-memory target for {segment['source']!r}")
        segment["target"] = target
        segment["status"] = "adapted"
        segment["provenance"] = "reviewed_exact_translation_memory"
        applied += 1

    args.segments.write_text(json.dumps(document, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Applied translation memory to {applied} pending segments")


if __name__ == "__main__":
    main()
