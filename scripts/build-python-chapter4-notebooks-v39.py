#!/usr/bin/env python3
"""Build bilingual Chapter 4 Notebook sources."""

from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id: str, value: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": value.splitlines(True)}


def code(cell_id: str, value: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id,
            "metadata": {}, "outputs": [], "source": value.splitlines(True)}


def document(cells: list[dict]) -> dict:
    return {"cells": cells, "metadata": {"kernelspec": {"display_name": "Python 3",
            "language": "python", "name": "python3"}, "language_info": {"name": "python"}},
            "nbformat": 4, "nbformat_minor": 5}


RECORD = '''item = {"item_id": "E001", "name": "Laptop 01", "borrower_id": None}

def loan_item(record, borrower_id):
    if record["borrower_id"] is not None:
        raise ValueError("already on loan")
    record["borrower_id"] = borrower_id

loan_item(item, "M014")
print(item)'''

FIRST_CLASS = '''class EquipmentItem:
    def __init__(self, item_id, name, category="General"):
        item_id, name, category = item_id.strip(), name.strip(), category.strip()
        if not item_id or not name or not category:
            raise ValueError("ID, name, and category are required")
        self.item_id = item_id
        self.name = name
        self.category = category
        self.borrower_id = None

    def is_available(self):
        return self.borrower_id is None

    def loan_to(self, borrower_id):
        borrower_id = borrower_id.strip()
        if not borrower_id or not self.is_available():
            raise ValueError("loan not allowed")
        self.borrower_id = borrower_id

    def return_item(self):
        if self.is_available():
            raise ValueError("item is not on loan")
        self.borrower_id = None'''

DESK = '''class LendingDesk:
    def __init__(self):
        self.items = {}

    def add_item(self, item):
        if item.item_id in self.items:
            raise ValueError("duplicate item ID")
        self.items[item.item_id] = item

    def find_item(self, item_id):
        return self.items.get(item_id)

    def loan_item(self, item_id, borrower_id):
        item = self.find_item(item_id)
        if item is None:
            raise KeyError(item_id)
        item.loan_to(borrower_id)

    def available_items(self):
        return sorted(
            [item for item in self.items.values() if item.is_available()],
            key=lambda item: item.item_id,
        )'''

SAVE = '''from pathlib import Path
import csv

def to_record(item):
    return {"item_id": item.item_id, "name": item.name,
            "category": item.category, "borrower_id": item.borrower_id or ""}

def save_inventory(desk, path):
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", newline="", encoding="utf-8") as handle:
        writer = csv.DictWriter(handle,
            fieldnames=["item_id", "name", "category", "borrower_id"])
        writer.writeheader()
        for item in sorted(desk.items.values(), key=lambda value: value.item_id):
            writer.writerow(to_record(item))'''


def lesson41(ja: bool) -> list[dict]:
    if ja:
        texts = [
            "# 4.1 レコードと関数からオブジェクトへ\n\n既習の辞書と関数でも機材を扱えます。動く方法とクラスを比較し、状態と処理を一つの単位にする意味を確かめます。",
            "## 4.1.1 クラスは状態と操作を結び付ける\n\n`EquipmentItem`がクラス、そこから作る各値がインスタンスです。`self`はメソッドを受け取った個体を表します。",
            "## 4.1.2 複数のインスタンスは独立する\n\n一件だけ貸し出し、もう一件の状態が変わらないことを確認します。値が同じことと同一オブジェクトであることも区別します。",
            "## 統合練習\n\n`rename(new_name)`を追加し、二件のうち一件だけ名称を変更します。辞書と関数の方が簡単な場合も考えてください。",
            "## まとめと次へ\n\nクラス、インスタンス、属性、メソッド、`__init__`、`self`をコード上で確認しました。次はメソッドで正しい状態を守ります。",
        ]
    else:
        texts = [
            "# 4.1 From records and functions to objects\n\nDictionaries and functions already work. Compare that approach with a class and observe what changes when state and operations form one unit.",
            "## 4.1.1 A class joins state and operations\n\n`EquipmentItem` is the class; each value made from it is an instance. `self` is the instance receiving a method call.",
            "## 4.1.2 Instances keep independent state\n\nLoan only one of two items and confirm the other does not change. Also distinguish equal-looking values from object identity.",
            "## Integrated practice\n\nAdd `rename(new_name)` and rename only one of two items. Identify when a dictionary and function would still be the simpler choice.",
            "## Summary and connection\n\nYou identified class, instance, attribute, method, `__init__`, and `self` in code. Next, methods will protect valid state.",
        ]
    return [md("c41-a", texts[0]), code("c41-b", RECORD), md("c41-c", texts[1]),
            code("c41-d", FIRST_CLASS + '\n\nfirst = EquipmentItem("E001", "Laptop")\nsecond = EquipmentItem("E002", "Projector")\nfirst.loan_to("M014")\nprint(first.borrower_id, second.borrower_id)'),
            md("c41-e", texts[2]), code("c41-f", 'another = EquipmentItem("E002", "Projector")\nprint(second is another)\nprint(second.item_id == another.item_id)'),
            md("c41-g", texts[3]), md("c41-h", texts[4])]


def lesson42(ja: bool) -> list[dict]:
    title = "# 4.2 状態・メソッド・正しいオブジェクト" if ja else "# 4.2 State, methods, and valid objects"
    intro = "属性があり得る状態を表すよう、生成と状態変更の直前で検証します。" if ja else "Validate construction and state transitions so attributes continue to describe a possible state."
    parts = (["## 4.2.1 生成時に不正な値を拒否する", "## 4.2.2 メソッドで状態を遷移させる",
              "## 4.2.3 失敗後も以前の状態を保つ", "## まとめと次へ\n\n正常経路と拒否経路を確認しました。次は複数の機材を一つの管理オブジェクトで扱います。"] if ja else
             ["## 4.2.1 Reject invalid construction", "## 4.2.2 Represent transitions with methods",
              "## 4.2.3 Preserve the previous state after failure", "## Summary and connection\n\nYou checked allowed and rejected paths. Next, one manager object will coordinate many items."])
    test = '''item = EquipmentItem("E001", "Laptop")
assert item.is_available()
item.loan_to("M014")
try:
    item.loan_to("M021")
except ValueError:
    pass
assert item.borrower_id == "M014"
item.return_item()
assert item.is_available()
print("state checks passed")'''
    return [md("c42-a", title + "\n\n" + intro), md("c42-b", parts[0]), code("c42-c", FIRST_CLASS),
            md("c42-d", parts[1]), code("c42-e", test), md("c42-f", parts[2] + "\n\nAdd checks for empty IDs and returning an available item."), md("c42-g", parts[3])]


def lesson43(ja: bool) -> list[dict]:
    if ja:
        parts = ["# 4.3 複数オブジェクト・合成・責任分担\n\n貸出窓口は機材を保持しますが、各機材の状態規則を複製しません。",
                 "## 4.3.1 管理オブジェクトがコレクションを持つ", "## 4.3.2 規則を持つ機材へ処理を委ねる",
                 "## 4.3.3 新しい一覧を返して内部を守る", "## 統合練習\n\n二件を追加し、一件を窓口経由で貸し出し、もう一件だけが利用可能一覧に残ることを確認します。",
                 "## まとめと次へ\n\n検索と所属は窓口、一件の状態変更は機材に分担しました。次は保存境界とテストを扱います。"]
    else:
        parts = ["# 4.3 Collections, composition, and responsibility\n\nA lending desk contains items without copying each item's state rules.",
                 "## 4.3.1 A manager object owns the collection", "## 4.3.2 Delegate to the object that owns the rule",
                 "## 4.3.3 Return a new view rather than replacement access", "## Integrated practice\n\nAdd two items, lend one through the desk, and confirm only the other appears in the available list.",
                 "## Summary and connection\n\nThe desk owns search and membership; each item owns its state transitions. Next, handle persistence and testing boundaries."]
    return [md("c43-a", parts[0]), code("c43-b", FIRST_CLASS), md("c43-c", parts[1]), code("c43-d", DESK),
            md("c43-e", parts[2]), md("c43-f", parts[3]), md("c43-g", parts[4]), md("c43-h", parts[5])]


def lesson44(ja: bool) -> list[dict]:
    if ja:
        parts = ["# 4.4 オブジェクトの保存とテスト\n\nメモリ上のオブジェクトとCSVレコードを境界で変換します。",
                 "## 4.4.1 一つのオブジェクトを保存用レコードへ変換する", "## 4.4.2 コレクションが保存を調整する",
                 "## 4.4.3 内部表現でなく振る舞いを確認する", "## 発展\n\n継承、プロパティ、クラスメソッド、`dataclasses`は発展です。明確な関係がなければまず合成を使います。",
                 "## まとめとプロジェクトへ\n\n生成、状態遷移、合成、レコード変換、テストがそろいました。4.5へ進めます。"]
    else:
        parts = ["# 4.4 Persistence and testing class-based programs\n\nConvert between in-memory objects and CSV records at a deliberate boundary.",
                 "## 4.4.1 Convert one object to a storage record", "## 4.4.2 Let the collection coordinate saving",
                 "## 4.4.3 Check behaviour rather than private spelling", "## Further study\n\nInheritance, properties, class methods, and `dataclasses` are extensions. Prefer composition until a genuine relationship is clear.",
                 "## Summary and project connection\n\nConstruction, transitions, composition, record conversion, and tests are ready for Project 4.5."]
    test = '''item = EquipmentItem("E001", "Laptop")
item.loan_to("M014")
try:
    item.loan_to("M021")
except ValueError:
    pass
else:
    raise AssertionError("double lending should fail")
assert item.borrower_id == "M014"'''
    return [md("c44-a", parts[0]), code("c44-b", FIRST_CLASS + "\n\n" + DESK), md("c44-c", parts[1]),
            code("c44-d", 'item = EquipmentItem("E001", "Laptop", "Computer")\nprint({"item_id": item.item_id, "name": item.name, "category": item.category, "borrower_id": item.borrower_id or ""})'),
            md("c44-e", parts[2]), code("c44-f", SAVE), md("c44-g", parts[3]), code("c44-h", test), md("c44-i", parts[4]), md("c44-j", parts[5])]


def project(ja: bool) -> list[dict]:
    title = ("# プロジェクト4.5 — 共用機材貸出窓口\n\nMoodleの公開仕様を読み、配布スターターの`TODO`を完成させます。" if ja else
             "# Project 4.5 — Community equipment lending desk\n\nRead the published Moodle contract and complete the TODOs in the supplied starter.")
    route = ("## 4段階で完成させる\n\n1. 機材の生成と利用可能判定。\n2. 貸出・返却・名称変更・レコード変換。\n3. 窓口のコレクションと委譲。\n4. 集計とCSV保存。\n\n実行前にCtrl+Sで保存します。" if ja else
             "## Complete four passes\n\n1. Item construction and availability.\n2. Loan, return, rename, and record conversion.\n3. Desk collection and delegation.\n4. Summary and CSV saving.\n\nSave with Ctrl+S before each run.")
    inspect = ("## 結果を自分で確認する\n\n全3件、利用可能2件、貸出中1件になり、CSVでE001だけがM014へ貸出中であることを確認します。" if ja else
               "## Inspect the result yourself\n\nExpect 3 total, 2 available, and 1 loaned. In the CSV only E001 is on loan to M014.")
    finish = ("## 自動確認と提出\n\n最初の`NG`から直し、`ALL TESTS PASSED`後に`equipment_lending.py`だけを提出します。" if ja else
              "## Automatic check and submission\n\nRepair from the first `NG`. After `ALL TESTS PASSED`, submit only `equipment_lending.py`.")
    return [md("p45-a", title), code("p45-b", 'from pathlib import Path\nproject = Path.cwd() / "projects" / "equipment-lending"\nprint("Project found:", project.is_dir())\nprint(project / "equipment_lending.py")'),
            md("p45-c", route), code("p45-d", '!python projects/equipment-lending/equipment_lending.py'), md("p45-e", inspect),
            code("p45-f", 'output = project / "output" / "equipment_inventory.csv"\nprint(output.read_text(encoding="utf-8") if output.is_file() else "No output yet")'),
            md("p45-g", finish), code("p45-h", '!python projects/equipment-lending/check_equipment_lending.py')]


BUILDERS = [("13_objects_classes.ipynb", lesson41), ("14_object_state_validation.ipynb", lesson42),
            ("15_composition_responsibility.ipynb", lesson43), ("16_object_persistence_testing.ipynb", lesson44),
            ("P4_equipment_lending.ipynb", project)]


def main() -> None:
    written = []
    for filename, builder in BUILDERS:
        for ja in (False, True):
            path = OUT / ("ja" if ja else "") / filename
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(json.dumps(document(builder(ja)), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
            written.append(str(path.relative_to(ROOT)))
    print(json.dumps({"notebooks": len(written), "files": written}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
