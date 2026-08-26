#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for lists, dictionaries, and records."""

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / "sample-content/introduction-to-python/python-lab/templates"


def md(cell_id: str, text: str) -> dict:
    return {"cell_type": "markdown", "id": cell_id, "metadata": {}, "source": text.splitlines(keepends=True)}


def code(cell_id: str, text: str) -> dict:
    return {"cell_type": "code", "execution_count": None, "id": cell_id, "metadata": {}, "outputs": [], "source": text.splitlines(keepends=True)}


def notebook(language: str) -> dict:
    if language == "ja":
        cells = [
            md("l21-ja-title", "# 2.1 — リスト・辞書・レコード\n\n複数の値を一つの構造として表し、順序、名前、重複、変更可能性に応じて適切なデータ型を選びます。"),
            md("l21-ja-list", "## リストは順序を持ち、変更できる値の並び\n\n角括弧で作り、0から始まる添字、負の添字、スライスで値を読みます。実務では同じ意味の値をまとめると処理しやすくなります。"),
            code("l21-ja-list-code", "attendance = [28, 31, 30, 33]\nprint(attendance[0])\nprint(attendance[-1])\nprint(attendance[1:3])\nprint(len(attendance))\nprint(31 in attendance)\n"),
            md("l21-ja-mutate", "## リストの内容を変更する\n\n添字への代入、`append()`、`extend()`、`insert()`、`remove()`、`pop()`で内容を変えられます。変更メソッドの多くはリスト自身を変更し、便利な新リストを返すわけではありません。"),
            code("l21-ja-mutate-code", "attendance = [28, 31, 30]\nattendance[0] = 29\nattendance.append(33)\nattendance.extend([32, 34])\nremoved = attendance.pop()\nprint(attendance)\nprint(\"取り出した値:\", removed)\n"),
            md("l21-ja-copy", "## 代入は同じリストを共有し、copy()は別のリストを作る\n\n`backup = original`は二つの名前が同じリストを指します。一方を変更すると他方からも見えます。独立した浅いコピーには`copy()`を使います。"),
            code("l21-ja-copy-code", "original = [28, 31, 30]\nshared = original\nseparate = original.copy()\nshared.append(33)\nprint(\"original:\", original)\nprint(\"shared:\", shared)\nprint(\"separate:\", separate)\n"),
            md("l21-ja-iterate", "## 位置が不要なら値を直接、必要ならenumerate()で読む\n\nリストを処理するために添字を手作業で管理する必要はありません。値だけならfor、週番号も必要ならenumerateを使います。"),
            code("l21-ja-iterate-code", "attendance = [28, 31, 30, 33]\nfor week, value in enumerate(attendance, start=1):\n    print(f\"第{week}週: {value}人\")\n"),
            md("l21-ja-tuple", "## タプルは順序を持つが、作成後に変更できない\n\n丸括弧で作り、添字やループで読めます。座標、固定された月と年度など、要素の組を途中で変更させたくない場合に使います。"),
            code("l21-ja-tuple-code", "report_period = (2026, 8)\nyear, month = report_period\nprint(year, month)\n# report_period[0] = 2027  # TypeError: タプルは変更不能\n"),
            md("l21-ja-dict", "## 辞書はキーで値の意味を表す\n\n辞書は`キー: 値`の組です。添字の位置ではなく、`centre[\"registered\"]`のような名前で値を読みます。同じ辞書内のキーは重複できません。"),
            code("l21-ja-dict-code", "centre = {\n    \"name\": \"North Learning Centre\",\n    \"district\": \"North\",\n    \"registered\": 40,\n    \"completed\": 32,\n}\nprint(centre[\"name\"])\nprint(centre[\"completed\"] / centre[\"registered\"] * 100)\n"),
            md("l21-ja-get", "## 存在しないキーをどのように扱うか決める\n\n角括弧で存在しないキーを読むと`KeyError`です。任意項目なら`get()`で既定値を指定できます。必須項目にgetを使って欠落を隠さず、業務上の必須・任意を区別します。"),
            code("l21-ja-get-code", "centre = {\"name\": \"North\", \"registered\": 40}\nprint(centre.get(\"phone\", \"未登録\"))\nprint(\"completed\" in centre)\ncentre[\"completed\"] = 32\ncentre[\"registered\"] = 42\nprint(centre)\n"),
            md("l21-ja-items", "## items()でキーと値を同時に反復する\n\n辞書を直接for文へ渡すとキーを反復します。キーと値の両方が必要なら`items()`、キーだけなら`keys()`、値だけなら`values()`を使います。"),
            code("l21-ja-items-code", "centre = {\"registered\": 40, \"attended\": 34, \"completed\": 32}\nfor field, value in centre.items():\n    print(field, value)\n"),
            md("l21-ja-records", "## リストの中に辞書を置くと、複数のレコードを表せる\n\n一つの辞書を一件のレコード、一つのキーを項目名として扱えます。これは後でCSVを表やDataFrameとして扱うための橋になります。"),
            code("l21-ja-records-code", "centres = [\n    {\"name\": \"North\", \"district\": \"A\", \"registered\": 40, \"completed\": 32},\n    {\"name\": \"South\", \"district\": \"B\", \"registered\": 35, \"completed\": 24},\n]\nfor centre in centres:\n    rate = centre[\"completed\"] / centre[\"registered\"] * 100\n    print(f\"{centre['name']}: {rate:.1f}%\")\n"),
            md("l21-ja-set", "## 集合は重複を除き、所属と集合演算を扱う\n\n集合は順序や重複ではなく、ある値が含まれるかを扱います。和集合`|`、積集合`&`、差集合`-`でカテゴリの関係を比較できます。表示順には依存しません。"),
            code("l21-ja-set-code", "offered = {\"Python\", \"Data\", \"Office\"}\nrequested = {\"Python\", \"Web\", \"Data\"}\nprint(\"共通:\", offered & requested)\nprint(\"未提供:\", requested - offered)\nprint(\"すべて:\", offered | requested)\n"),
            md("l21-ja-choice", "## 構造を目的で選ぶ\n\n順序付きで変更する値はリスト、固定された組はタプル、名前付き項目は辞書、重複のない所属は集合です。複数のレコードはリストの中に辞書を置いて表せます。"),
            md("l21-ja-transfer", "## 応用練習\n\n3センターを、`name`、`district`、`registered`、`completed`を持つ辞書としてリストへ格納します。ループで各修了率を表示し、75%未満のセンター名を別のリストへ追加し、地区の集合を作ります。登録者数0や必須キー欠落を通常の修了率として処理しないよう確認します。"),
            code("l21-ja-work", "# ここに応用練習の解答を書きます。\n\n"),
            md("l21-ja-complete", "## 完了確認\n\nリストの参照と変更、共有とコピー、タプルの変更不能性、辞書のキーとget、items、レコード、集合演算、構造の使い分けを説明できたら、保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l21-en-title", "# 2.1 — Lists, dictionaries, and records\n\nRepresent several values as one structure and choose a type according to order, names, duplicates, and whether change is allowed."),
            md("l21-en-list", "## A list is an ordered, mutable sequence\n\nCreate a list with brackets and read values with zero-based indexes, negative indexes, and slices. Grouping values with the same meaning makes repeated processing practical."),
            code("l21-en-list-code", "attendance = [28, 31, 30, 33]\nprint(attendance[0])\nprint(attendance[-1])\nprint(attendance[1:3])\nprint(len(attendance))\nprint(31 in attendance)\n"),
            md("l21-en-mutate", "## Modify list contents\n\nIndex assignment, `append()`, `extend()`, `insert()`, `remove()`, and `pop()` change a list. Most mutation methods change the existing list rather than returning a useful new list."),
            code("l21-en-mutate-code", "attendance = [28, 31, 30]\nattendance[0] = 29\nattendance.append(33)\nattendance.extend([32, 34])\nremoved = attendance.pop()\nprint(attendance)\nprint(\"Removed:\", removed)\n"),
            md("l21-en-copy", "## Assignment shares one list; copy() creates another list\n\n`backup = original` gives the same list two names, so a mutation is visible through both. Use `copy()` when an independent shallow copy is required."),
            code("l21-en-copy-code", "original = [28, 31, 30]\nshared = original\nseparate = original.copy()\nshared.append(33)\nprint(\"original:\", original)\nprint(\"shared:\", shared)\nprint(\"separate:\", separate)\n"),
            md("l21-en-iterate", "## Iterate over values directly, or use enumerate() for positions\n\nDo not maintain an index manually when it is unnecessary. Use for for values and enumerate when the report also needs a week number."),
            code("l21-en-iterate-code", "attendance = [28, 31, 30, 33]\nfor week, value in enumerate(attendance, start=1):\n    print(f\"Week {week}: {value} learners\")\n"),
            md("l21-en-tuple", "## A tuple is ordered but immutable\n\nCreate a tuple with parentheses and read it by index or iteration. Use one for a fixed group such as report year and month when its elements should not be replaced."),
            code("l21-en-tuple-code", "report_period = (2026, 8)\nyear, month = report_period\nprint(year, month)\n# report_period[0] = 2027  # TypeError: tuples are immutable\n"),
            md("l21-en-dict", "## A dictionary names each value with a key\n\nA dictionary stores `key: value` pairs. Read by a meaningful key such as `centre[\"registered\"]`, not a numeric position. A key cannot occur twice in the same dictionary."),
            code("l21-en-dict-code", "centre = {\n    \"name\": \"North Learning Centre\",\n    \"district\": \"North\",\n    \"registered\": 40,\n    \"completed\": 32,\n}\nprint(centre[\"name\"])\nprint(centre[\"completed\"] / centre[\"registered\"] * 100)\n"),
            md("l21-en-get", "## Decide how a missing key should be handled\n\nReading a missing key with brackets raises `KeyError`. For an optional field, `get()` can supply a default. Do not hide a missing required field with get; distinguish required and optional operational data."),
            code("l21-en-get-code", "centre = {\"name\": \"North\", \"registered\": 40}\nprint(centre.get(\"phone\", \"not recorded\"))\nprint(\"completed\" in centre)\ncentre[\"completed\"] = 32\ncentre[\"registered\"] = 42\nprint(centre)\n"),
            md("l21-en-items", "## items() iterates over keys and values together\n\nA direct for loop over a dictionary yields keys. Use `items()` for both key and value, `keys()` for keys, and `values()` for values."),
            code("l21-en-items-code", "centre = {\"registered\": 40, \"attended\": 34, \"completed\": 32}\nfor field, value in centre.items():\n    print(field, value)\n"),
            md("l21-en-records", "## A list of dictionaries represents several records\n\nTreat one dictionary as one record and each key as a field name. This structure is the bridge to later CSV tables and DataFrames."),
            code("l21-en-records-code", "centres = [\n    {\"name\": \"North\", \"district\": \"A\", \"registered\": 40, \"completed\": 32},\n    {\"name\": \"South\", \"district\": \"B\", \"registered\": 35, \"completed\": 24},\n]\nfor centre in centres:\n    rate = centre[\"completed\"] / centre[\"registered\"] * 100\n    print(f\"{centre['name']}: {rate:.1f}%\")\n"),
            md("l21-en-set", "## A set represents unique membership and supports set operations\n\nA set focuses on whether a value is present, not its order or duplicates. Union `|`, intersection `&`, and difference `-` compare categories. Do not depend on display order."),
            code("l21-en-set-code", "offered = {\"Python\", \"Data\", \"Office\"}\nrequested = {\"Python\", \"Web\", \"Data\"}\nprint(\"Common:\", offered & requested)\nprint(\"Not offered:\", requested - offered)\nprint(\"All:\", offered | requested)\n"),
            md("l21-en-choice", "## Choose the structure from the purpose\n\nUse a list for ordered mutable values, a tuple for a fixed group, a dictionary for named fields, and a set for unique membership. Use a list of dictionaries for several records."),
            md("l21-en-transfer", "## Transfer exercise\n\nStore three centres as dictionaries containing `name`, `district`, `registered`, and `completed`, inside one list. Display every completion rate, append names below 75% to a separate list, and create a set of districts. Do not calculate an ordinary rate when registration is zero or a required key is missing."),
            code("l21-en-work", "# Write the transfer solution here.\n\n"),
            md("l21-en-complete", "## Completion check\n\nWhen you can explain list access and mutation, sharing and copying, tuple immutability, dictionary keys and get, items, records, set operations, and structure selection, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "2.1", "language": language, "concepts": [f"D{i:02d}" for i in range(1, 11)], "revision": 19},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "05_lists_dictionaries_records.ipynb", "ja": TEMPLATES / "ja/05_lists_dictionaries_records.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "read list values with indexes slices len and membership",
        "modify a list with assignment append extend and pop",
        "distinguish shared list aliases from a shallow copy",
        "recognise a tuple as an ordered immutable group",
        "create read add and update dictionary fields",
        "handle required and optional dictionary keys deliberately",
        "iterate through dictionary keys values and items",
        "process a list of dictionaries as labelled records",
        "use sets for unique membership union intersection and difference",
        "choose and combine list tuple dictionary and set for an operational task",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "2.1 Lists, dictionaries, and records",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"D{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L21R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/05_lists_dictionaries_records.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/05_lists_dictionaries_records.ipynb",
        },
        "implementation": "scripts/upgrade-python-lesson21-v19.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-2-1-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
