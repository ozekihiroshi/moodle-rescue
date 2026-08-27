from __future__ import annotations

import json
from pathlib import Path

from integrated_practice_ch1_v46 import PRACTICES as CH1
from integrated_practice_ch2_v46 import PRACTICES as CH2
from integrated_practice_ch3_v46 import PRACTICES as CH3
from integrated_practice_ch4_v46 import PRACTICES as CH4
from integrated_practice_ch5_v46 import PRACTICES as CH5
from integrated_practice_ch6_v46 import PRACTICES as CH6


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "sample-content" / "introduction-to-python" / "integrated-practices-v46.json"


def main():
    items = CH1 + CH2 + CH3 + CH4 + CH5 + CH6
    keys = [item["key"] for item in items]
    assert len(items) == 23, len(items)
    assert len(keys) == len(set(keys)), keys
    OUT.write_text(json.dumps(items, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"built {len(items)} bilingual integrated practices: {OUT}")


if __name__ == "__main__":
    main()
