from __future__ import annotations

import builtins
import json
import os
import tempfile
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "sample-content" / "introduction-to-python" / "integrated-practices-v46.json"


def main():
    items = json.loads(DATA.read_text(encoding="utf-8"))
    failures = []
    original_input = builtins.input
    os.environ.setdefault("MPLBACKEND", "Agg")
    for item in items:
        key = item["key"]
        try:
            compiled = compile(item["code"], f"integrated-practice-{key}", "exec")
            answers = iter(["Kumasi", "6", "750"])
            builtins.input = lambda _prompt="": next(answers)
            with tempfile.TemporaryDirectory(prefix=f"pyai-v46-{key.replace('.', '-')}-") as temp:
                previous = Path.cwd()
                os.chdir(temp)
                try:
                    exec(compiled, {"__name__": "__main__"})
                finally:
                    os.chdir(previous)
        except Exception as exc:
            failures.append(f"{key}: {type(exc).__name__}: {exc}")
        finally:
            builtins.input = original_input
    if failures:
        raise SystemExit("\n".join(failures))
    print(f"verified {len(items)} model answers")


if __name__ == "__main__":
    main()
