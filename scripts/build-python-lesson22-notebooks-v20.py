#!/usr/bin/env python3
"""Build canonical and Japanese notebooks for functions, errors, and testing."""

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
            md("l22-ja-title", "# 2.2 — 関数・エラー・テスト\n\n2.1で作ったレコード処理を、名前を付けて再利用でき、入力と結果を検証できる関数へ分けます。"),
            md("l22-ja-def", "## 関数は処理に名前を付ける\n\n`def`で関数を定義します。定義しただけでは本体は実行されず、関数を呼び出したときに、インデントされた本体が実行されます。定義は呼び出しより先に実行されている必要があります。"),
            code("l22-ja-def-code", "def completion_rate(completed, registered):\n    return completed / registered * 100\n\nrate = completion_rate(32, 40)\nprint(f\"修了率: {rate:.1f}%\")\n"),
            md("l22-ja-terms", "## 仮引数は入口、戻り値は出口\n\n定義に書く`completed`と`registered`は仮引数、呼び出しで渡す`32`と`40`は実引数です。`return`は計算結果を呼び出し元へ返し、その時点で関数を終了します。`print()`は画面に表示するだけなので、後の計算に使う値の代わりにはなりません。"),
            code("l22-ja-return", "def add_with_print(a, b):\n    print(a + b)\n\ndef add_with_return(a, b):\n    return a + b\n\nprinted_result = add_with_print(2, 3)\nreturned_result = add_with_return(2, 3)\nprint(\"print版の戻り値:\", printed_result)\nprint(\"return版を再計算:\", returned_result * 2)\n"),
            md("l22-ja-scope", "## 関数内の名前は原則としてローカル\n\n関数内で代入した変数はローカル変数で、通常は関数外から参照できません。必要な値は引数で受け取り、結果は`return`で返すと、外部状態への依存が減り、同じ入力で同じ結果を確認しやすくなります。"),
            code("l22-ja-scope-code", "def difference(planned, actual):\n    gap = planned - actual\n    return gap\n\nprint(difference(40, 34))\n# print(gap)  # NameError: gapは関数の外には存在しない\n"),
            md("l22-ja-default", "## 既定値とキーワード引数は呼び出しの意味を明確にする\n\n省略可能な仮引数には既定値を付けられます。既定値のない仮引数を先に書きます。キーワード引数を使うと、同じ型の値が並ぶ呼び出しでも意味を読み取りやすくなります。"),
            code("l22-ja-default-code", "def format_centre(name, completed, registered, decimals=1):\n    rate = completed / registered * 100\n    return f\"{name}: {rate:.{decimals}f}%\"\n\nprint(format_centre(\"North\", completed=32, registered=40))\nprint(format_centre(\"South\", completed=24, registered=35, decimals=2))\n"),
            md("l22-ja-doc", "## docstringと型ヒントは契約を伝える\n\n関数直下のdocstringには目的、入力、戻り値、無効な入力の扱いを書きます。型ヒントは読み手と開発ツールへの情報であり、実行時に型を自動強制する仕組みではありません。"),
            code("l22-ja-doc-code", "def safe_rate(completed: int, registered: int) -> float | None:\n    \"\"\"修了率を返す。登録者数が0以下ならNoneを返す。\"\"\"\n    if registered <= 0:\n        return None\n    return completed / registered * 100\n\nprint(safe_rate(32, 40))\nprint(safe_rate(0, 0))\n"),
            md("l22-ja-record", "## 一つの関数には一つの明確な責務を与える\n\nレコードの検証、率の計算、表示を一つの長い処理へ混ぜず、小さな関数へ分けます。必須キーの欠落と、値の範囲違反も区別します。"),
            code("l22-ja-record-code", "REQUIRED_FIELDS = {\"name\", \"registered\", \"completed\"}\n\ndef validate_centre(centre):\n    missing = REQUIRED_FIELDS - centre.keys()\n    if missing:\n        raise KeyError(f\"必須項目がありません: {sorted(missing)}\")\n    if centre[\"registered\"] < 0 or centre[\"completed\"] < 0:\n        raise ValueError(\"人数を負数にはできません\")\n    if centre[\"completed\"] > centre[\"registered\"]:\n        raise ValueError(\"修了者数が登録者数を超えています\")\n\ndef centre_rate(centre):\n    validate_centre(centre)\n    if centre[\"registered\"] == 0:\n        return None\n    return centre[\"completed\"] / centre[\"registered\"] * 100\n\ncentre = {\"name\": \"North\", \"registered\": 40, \"completed\": 32}\nprint(centre_rate(centre))\n"),
            md("l22-ja-errors", "## エラーは発生段階で分けて読む\n\n文法エラーは実行前に構文を解釈できない状態、実行時エラーは実行中に例外が発生した状態、論理エラーは実行できても結果が誤っている状態です。トレースバックは最後の行から例外名とメッセージを確認し、その上の自分のコード行へ戻ります。"),
            code("l22-ja-error-code", "def broken_rate(completed, registered):\n    return completed / registerd * 100  # 名前の綴りが違う\n\ntry:\n    broken_rate(32, 40)\nexcept NameError as error:\n    print(type(error).__name__)\n    print(error)\n"),
            md("l22-ja-exceptions", "## 予想できる例外だけを狭く捕捉する\n\n`try`には失敗し得る最小範囲を置き、`except`では対処できる具体的な例外を指定します。`else`は例外がなかった場合、`finally`は成否にかかわらず必要な後始末に使います。原因を隠す`except Exception: pass`は避けます。"),
            code("l22-ja-exception-code", "raw = \"40\"\ntry:\n    registered = int(raw)\nexcept ValueError:\n    print(\"整数として読めません\")\nelse:\n    print(\"登録者数:\", registered)\nfinally:\n    print(\"入力確認を終了しました\")\n"),
            md("l22-ja-tests", "## テストは正常値・境界値・異常値を分ける\n\n正常値だけでは境界のバグを見つけられません。`assert`で期待値を明記し、通常の値、0などの境界、無効な値を確認します。浮動小数点数は完全一致ではなく許容誤差で比較します。`assert`は学習時の検査には便利ですが、利用者入力の検証の代わりにはしません。"),
            code("l22-ja-test-code", "assert abs(safe_rate(32, 40) - 80.0) < 0.0001\nassert safe_rate(0, 0) is None\nassert safe_rate(1, -1) is None\n\ntry:\n    validate_centre({\"name\": \"Bad\", \"registered\": 5, \"completed\": 7})\nexcept ValueError:\n    pass\nelse:\n    raise AssertionError(\"ValueErrorを期待しました\")\n\nprint(\"すべてのテストに合格しました\")\n"),
            md("l22-ja-transfer", "## 応用練習\n\n2.1の3センターのリストを使い、`validate_centre()`、`centre_rate()`、`summarise_centres()`へ処理を分けてください。最後の関数は、修了率75%未満のセンター名、地区の集合、全体の登録者数と修了者数を辞書で返します。正常な3件、登録者数0、必須キー欠落、修了者数超過をテストします。"),
            code("l22-ja-work", "# ここに応用練習の解答を書きます。\n"),
            md("l22-ja-complete", "## 完了確認\n\n関数の定義と呼び出し、仮引数と実引数、returnとprint、ローカル変数、既定値、docstring、型ヒント、入力検証、raise、try/except、トレースバック、正常値・境界値・異常値のテストを説明できたら保存して理解度チェックへ進みます。"),
        ]
    else:
        cells = [
            md("l22-en-title", "# 2.2 — Functions, errors, and testing\n\nTurn the record processing from 2.1 into named, reusable functions whose inputs and results can be checked."),
            md("l22-en-def", "## A function gives a process a name\n\nDefine a function with `def`. Defining it does not run its body; calling it runs the indented body. The definition must have executed before the call."),
            code("l22-en-def-code", "def completion_rate(completed, registered):\n    return completed / registered * 100\n\nrate = completion_rate(32, 40)\nprint(f\"Completion rate: {rate:.1f}%\")\n"),
            md("l22-en-terms", "## Parameters are inputs and return values are outputs\n\n`completed` and `registered` in the definition are parameters; `32` and `40` in the call are arguments. `return` sends a result to the caller and ends the function. `print()` only displays text, so it cannot replace a returned value needed by later calculations."),
            code("l22-en-return", "def add_with_print(a, b):\n    print(a + b)\n\ndef add_with_return(a, b):\n    return a + b\n\nprinted_result = add_with_print(2, 3)\nreturned_result = add_with_return(2, 3)\nprint(\"Print version returned:\", printed_result)\nprint(\"Returned value reused:\", returned_result * 2)\n"),
            md("l22-en-scope", "## Names assigned inside a function are normally local\n\nA variable assigned inside a function is local and normally cannot be read outside it. Receive required values as parameters and return results. Reducing dependence on external state makes behaviour easier to repeat and test."),
            code("l22-en-scope-code", "def difference(planned, actual):\n    gap = planned - actual\n    return gap\n\nprint(difference(40, 34))\n# print(gap)  # NameError: gap is local to the function\n"),
            md("l22-en-default", "## Defaults and keyword arguments clarify calls\n\nGive optional parameters default values, placing required parameters first. Keyword arguments make calls readable when several values have the same type."),
            code("l22-en-default-code", "def format_centre(name, completed, registered, decimals=1):\n    rate = completed / registered * 100\n    return f\"{name}: {rate:.{decimals}f}%\"\n\nprint(format_centre(\"North\", completed=32, registered=40))\nprint(format_centre(\"South\", completed=24, registered=35, decimals=2))\n"),
            md("l22-en-doc", "## A docstring and type hints communicate the contract\n\nDescribe purpose, inputs, return value, and invalid-input policy in the docstring. Type hints help readers and tools; Python does not automatically enforce them at runtime."),
            code("l22-en-doc-code", "def safe_rate(completed: int, registered: int) -> float | None:\n    \"\"\"Return completion percentage, or None when registration is not positive.\"\"\"\n    if registered <= 0:\n        return None\n    return completed / registered * 100\n\nprint(safe_rate(32, 40))\nprint(safe_rate(0, 0))\n"),
            md("l22-en-record", "## Give each function one clear responsibility\n\nSeparate record validation, rate calculation, and display instead of mixing them into one long process. Distinguish a missing required key from a value outside its valid range."),
            code("l22-en-record-code", "REQUIRED_FIELDS = {\"name\", \"registered\", \"completed\"}\n\ndef validate_centre(centre):\n    missing = REQUIRED_FIELDS - centre.keys()\n    if missing:\n        raise KeyError(f\"Missing required fields: {sorted(missing)}\")\n    if centre[\"registered\"] < 0 or centre[\"completed\"] < 0:\n        raise ValueError(\"Counts cannot be negative\")\n    if centre[\"completed\"] > centre[\"registered\"]:\n        raise ValueError(\"Completed cannot exceed registered\")\n\ndef centre_rate(centre):\n    validate_centre(centre)\n    if centre[\"registered\"] == 0:\n        return None\n    return centre[\"completed\"] / centre[\"registered\"] * 100\n\ncentre = {\"name\": \"North\", \"registered\": 40, \"completed\": 32}\nprint(centre_rate(centre))\n"),
            md("l22-en-errors", "## Classify an error by when it appears\n\nA syntax error prevents Python from parsing the program. A runtime error raises an exception during execution. A logic error runs but produces the wrong result. Read a traceback from its final exception name and message, then move upward to the relevant line in your code."),
            code("l22-en-error-code", "def broken_rate(completed, registered):\n    return completed / registerd * 100  # Intentional spelling error\n\ntry:\n    broken_rate(32, 40)\nexcept NameError as error:\n    print(type(error).__name__)\n    print(error)\n"),
            md("l22-en-exceptions", "## Catch only expected exceptions, in a narrow region\n\nPlace only the operation that may fail in `try` and catch a specific exception you can handle. `else` runs when there was no exception; `finally` supports cleanup required in either case. Avoid `except Exception: pass`, which hides causes."),
            code("l22-en-exception-code", "raw = \"40\"\ntry:\n    registered = int(raw)\nexcept ValueError:\n    print(\"Not a valid integer\")\nelse:\n    print(\"Registered:\", registered)\nfinally:\n    print(\"Input check finished\")\n"),
            md("l22-en-tests", "## Test normal, boundary, and invalid cases separately\n\nA normal case alone cannot expose boundary bugs. State expected results with `assert`, including ordinary values, a boundary such as zero, and invalid values. Compare floats with a tolerance. Assertions are useful checks here, but do not replace validation of user data."),
            code("l22-en-test-code", "assert abs(safe_rate(32, 40) - 80.0) < 0.0001\nassert safe_rate(0, 0) is None\nassert safe_rate(1, -1) is None\n\ntry:\n    validate_centre({\"name\": \"Bad\", \"registered\": 5, \"completed\": 7})\nexcept ValueError:\n    pass\nelse:\n    raise AssertionError(\"Expected ValueError\")\n\nprint(\"All tests passed\")\n"),
            md("l22-en-transfer", "## Transfer exercise\n\nUsing the three-centre list from 2.1, separate the work into `validate_centre()`, `centre_rate()`, and `summarise_centres()`. The last function returns a dictionary containing names below 75%, the set of districts, total registration, and total completion. Test three valid records, zero registration, a missing required key, and completion above registration."),
            code("l22-en-work", "# Write the transfer solution here.\n"),
            md("l22-en-complete", "## Completion check\n\nWhen you can explain definition and call, parameters and arguments, return versus print, local names, defaults, docstrings, type hints, validation, raise, try/except, traceback reading, and normal/boundary/invalid tests, save and continue to the learning check."),
        ]

    return {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3 (ipykernel)", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "version": "3"},
            "pyai": {"lesson": "2.2", "language": language, "concepts": [f"F{i:02d}" for i in range(1, 11)], "revision": 20},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }


def main() -> None:
    targets = {"en": TEMPLATES / "06_functions_errors_testing.ipynb", "ja": TEMPLATES / "ja/06_functions_errors_testing.ipynb"}
    for language, target in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(notebook(language), ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        print(f"wrote {target.relative_to(ROOT)}")

    descriptions = [
        "define and call a function after its definition",
        "distinguish parameters arguments return values and printed output",
        "use local scope and explicit inputs and outputs",
        "use default and keyword arguments deliberately",
        "document a function contract with a docstring and type hints",
        "decompose record validation calculation and presentation",
        "classify syntax runtime and logic errors and read a traceback",
        "raise and catch specific exceptions without hiding causes",
        "test normal boundary and invalid cases including float tolerance",
        "build and test a reusable centre-summary pipeline",
    ]
    concept_map = {
        "schema_version": 1,
        "lesson": "2.2 Functions, errors, and testing",
        "canonical_language": "en",
        "adaptations": ["ja"],
        "concepts": [
            {"id": f"F{i:02d}", "description": description, "lesson": True, "notebook": True, "question": f"L22R-{i:02d}", "teacher": False}
            for i, description in enumerate(descriptions, start=1)
        ],
        "notebooks": {
            "en": "sample-content/introduction-to-python/python-lab/templates/06_functions_errors_testing.ipynb",
            "ja": "sample-content/introduction-to-python/python-lab/templates/ja/06_functions_errors_testing.ipynb",
        },
        "implementation": "scripts/upgrade-python-lesson22-v20.php",
    }
    target = ROOT / "sample-content/introduction-to-python/localization/lesson-2-2-concept-map-v1.json"
    target.write_text(json.dumps(concept_map, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
