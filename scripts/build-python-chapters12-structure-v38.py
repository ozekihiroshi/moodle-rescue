#!/usr/bin/env python3
"""Apply the Chapter 3 textbook hierarchy to Chapters 1 and 2.

The script restructures prose and Markdown headings while preserving code cells,
code blocks, data files, project contracts, Moodle activity IDs, and quizzes.
"""

from __future__ import annotations

import html
import json
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "sample-content" / "introduction-to-python"
AUDITS = CONTENT / "structure-audits"
TEMPLATES = CONTENT / "python-lab" / "templates"
SCRIPTS = ROOT / "scripts"

GROUP_STYLE = "margin-top:2em;padding-bottom:.35em;border-bottom:2px solid #0f6cbf"
NOTE_STYLE = "margin:1em 0;padding:.75em 1em;border-left:4px solid #5b7c99;background:#f6f8fa"
CHECK_STYLE = "margin:.9em 0;padding:.65em .85em;border:1px solid #d8dce1;background:#fbfcfd"


def lesson(
    number: str,
    cmid: int,
    groups: list[tuple[str, list[int]]],
    outcomes: list[str],
    summary: list[str],
    next_text: str,
    route: str,
    time: str,
    checks: dict[str, str] | None = None,
) -> dict:
    return {
        "number": number,
        "cmid": cmid,
        "groups": groups,
        "outcomes": outcomes,
        "summary": summary,
        "next": next_text,
        "route": route,
        "time": time,
        "checks": checks or {},
    }


SPECS = {
    "en": {
        "source": "chapter3-current-PYAI-INTRO.json",
        "shortname": "PYAI-INTRO",
        "labels": {
            "intro": "Introduction",
            "outcomes": "Learning outcomes",
            "route": "Learning route",
            "summary": "Summary",
            "next": "Next",
            "outcome_lead": "At the end of this lesson, you can:",
            "summary_lead": "This lesson established the following:",
            "time": "Estimated learning time",
            "check": "Quick check",
        },
        "lessons": [
            lesson(
                "1.1", 35,
                [
                    ("1.1.1 Read values, expressions, and visible output", [0, 1, 2]),
                    ("1.1.2 Express the intended calculation order", [3]),
                    ("1.1.3 Change a program and verify the result", [4]),
                    ("1.1.4 Integrated practice: write and explain a short program", [5]),
                ],
                [
                    "Identify instructions, values, expressions, and output in a short program.",
                    "Predict execution from top to bottom and distinguish evaluation from display.",
                    "Use parentheses to make the intended order of a calculation visible.",
                    "Change a small program and check whether all displayed results remain consistent.",
                ],
                [
                    "Read a short program as a sequence of instructions rather than only its final answer.",
                    "Distinguished numbers, quoted text, expressions, and intentional output.",
                    "Used prediction, execution, and explanation to check a program.",
                ],
                "Lesson 1.2 gives repeated values meaningful names, so one change can update every dependent calculation consistently.",
                "Required: 1.1.1–1.1.3  |  Integration: 1.1.4", "about 2 hours",
                {"1.1.1": "Which part of the program calculates a value, and which part makes that value visible?", "1.1.3": "If one business value appears three times, what can go wrong when only one occurrence is changed?"},
            ),
            lesson(
                "1.2", 37,
                [
                    ("1.2.1 Create and update program state with assignment", [0, 1, 2, 3]),
                    ("1.2.2 Choose meaningful names and diagnose NameError", [4, 5]),
                    ("1.2.3 Make Notebook state reproducible", [6]),
                    ("1.2.4 Integrated practice: trace and update a small model", [7]),
                ],
                [
                    "Explain assignment as evaluating the right side before updating the name on the left.",
                    "Trace the value associated with each name after reassignment.",
                    "Distinguish assignment with = from comparison with ==.",
                    "Choose valid, meaningful names and diagnose spelling or execution-order errors.",
                ],
                [
                    "Used names as a single source for values that have one meaning.",
                    "Traced reassignment and calculations that do not update automatically.",
                    "Restarted and ran a Notebook from the top to expose hidden state dependencies.",
                ],
                "Lesson 1.3 examines the kinds of scalar values stored by those names and the arithmetic and conversions permitted for each kind.",
                "Required: 1.2.1–1.2.3  |  Integration: 1.2.4", "about 2 hours",
                {"1.2.1": "When total = total + amount runs, which value of total is read first?", "1.2.3": "Why can a Notebook work before restart but fail when another learner runs it from the top?"},
            ),
            lesson(
                "1.3", 267,
                [
                    ("1.3.1 Distinguish the basic scalar types", [0]),
                    ("1.3.2 Calculate with numeric operators and explicit order", [1, 2]),
                    ("1.3.3 Convert and validate values deliberately", [3, 4]),
                    ("1.3.4 Integrated practice: calculate and check a rate", [5]),
                ],
                [
                    "Distinguish int, float, str, bool, and None by value and purpose.",
                    "Use the seven arithmetic operators and parentheses appropriately.",
                    "Convert between compatible scalar types explicitly and interpret conversion errors.",
                    "Check that a numeric result is plausible as well as executable.",
                ],
                [
                    "Connected value types to the operations Python can perform.",
                    "Made arithmetic order explicit and recognised important numeric edge cases.",
                    "Converted only when the source value and target type were compatible.",
                ],
                "Lesson 1.4 develops text as a sequence, accepts keyboard input, and combines values into clear formatted output.",
                "Required: 1.3.1–1.3.3  |  Integration: 1.3.4", "about 2.5 hours",
                {"1.3.1": "Why do 34 and '34' require different operations even though they look similar?", "1.3.3": "What should be checked after a conversion succeeds but before the number is used?"},
            ),
            lesson(
                "1.4", 275,
                [
                    ("1.4.1 Create, inspect, and transform strings", [0, 1, 2, 3]),
                    ("1.4.2 Combine text and values with compatible types", [4]),
                    ("1.4.3 Accept input and convert it for calculation", [5]),
                    ("1.4.4 Produce meaningful formatted output", [6]),
                    ("1.4.5 Read simple delimited text", [7]),
                    ("1.4.6 Integrated practice: input, calculate, and report", [8]),
                ],
                [
                    "Create strings and read characters or slices by position.",
                    "Explain that string methods return new values rather than modifying the original string.",
                    "Accept input as text and convert it before numeric calculation.",
                    "Produce labelled, formatted output with f-strings.",
                    "Split a simple delimited record while recognising that full CSV needs a CSV library.",
                ],
                [
                    "Treated text as an ordered, immutable sequence.",
                    "Matched types deliberately when combining text and numbers.",
                    "Connected keyboard input, conversion, calculation, and formatted output.",
                ],
                "Lesson 1.5 uses comparisons and Boolean logic to choose what a program should do with the values it has calculated.",
                "Required: 1.4.1–1.4.4  |  Supporting: 1.4.5  |  Integration: 1.4.6", "about 2.5 hours",
                {"1.4.3": "What type does input() return even when the learner types digits?", "1.4.4": "What information must accompany a number so that another person can understand the output?"},
            ),
            lesson(
                "1.5", 39,
                [
                    ("1.5.1 Build comparisons that produce Boolean values", [0, 1]),
                    ("1.5.2 Select between two actions with if and else", [2, 3]),
                    ("1.5.3 Select among several or independent actions", [4, 5]),
                    ("1.5.4 Combine decisions with Boolean logic", [6]),
                    ("1.5.5 Validate domains and test boundaries", [7, 8]),
                    ("1.5.6 Use short-circuit evaluation as a guard", [9]),
                    ("1.5.7 Integrated practice: implement and test an operational rule", [10]),
                ],
                [
                    "Build comparisons and explain the resulting bool value.",
                    "Use indentation and if/elif/else to implement mutually exclusive rules.",
                    "Distinguish one branch chain from independent if statements.",
                    "Combine conditions with and, or, and not using explicit grouping.",
                    "Test invalid values and points immediately below, at, and above a boundary.",
                ],
                [
                    "Connected comparisons and Boolean values to indented branches.",
                    "Ordered overlapping conditions so that the first matching branch is correct.",
                    "Validated the permitted domain and exercised every boundary and branch.",
                ],
                "Lesson 1.6 repeats the same decision and update pattern over several values without copying the same code many times.",
                "Required: 1.5.1–1.5.5  |  Supporting: 1.5.6  |  Integration: 1.5.7", "about 3 hours",
                {"1.5.3": "When should two independent if statements be used instead of one if/elif/else chain?", "1.5.5": "Which three values reveal whether a threshold at 80 has been implemented correctly?"},
            ),
            lesson(
                "1.6", 41,
                [
                    ("1.6.1 Repeat over known values with for and range", [0, 1, 2]),
                    ("1.6.2 Build totals and conditional counts", [3, 4]),
                    ("1.6.3 Track an extreme value and its position", [5, 6]),
                    ("1.6.4 Control one iteration or the whole loop", [7]),
                    ("1.6.5 Repeat until a condition changes with while", [8, 9]),
                    ("1.6.6 Handle zero iterations and empty input", [10]),
                    ("1.6.7 Integrated practice: process a sequence and report the result", [11]),
                ],
                [
                    "Choose for for known values and while for a changing stopping condition.",
                    "Use range and enumerate without off-by-one errors.",
                    "Build totals, counts, and maximum tracking with correct initial values.",
                    "Use break and continue only when their effect on the loop is clear.",
                    "Define sensible behaviour for empty input and zero iterations.",
                ],
                [
                    "Traced each iteration and the state changed by the loop body.",
                    "Used accumulators, conditional counters, and position tracking.",
                    "Selected a loop form and initial value from the processing purpose.",
                ],
                "Project 1.7 combines input, conversion, conditions, and a for loop to turn five daily records into one checked weekly support report.",
                "Required: 1.6.1–1.6.3, 1.6.5–1.6.6  |  Supporting: 1.6.4  |  Integration: 1.6.7", "about 3 hours",
                {"1.6.2": "Where must an accumulator be initialised, and why?", "1.6.6": "What should the result mean when the loop processes no values?"},
            ),
            lesson(
                "2.1", 43,
                [
                    ("2.1.1 Store and modify ordered values with lists", [0, 1, 2, 3]),
                    ("2.1.2 Use tuples for fixed ordered values", [4]),
                    ("2.1.3 Represent named fields with dictionaries", [5, 6, 7]),
                    ("2.1.4 Represent several records with a list of dictionaries", [8]),
                    ("2.1.5 Represent unique membership with sets", [9]),
                    ("2.1.6 Search, add, update, and remove records in an equipment register", [10, 11]),
                    ("2.1.7 Integrated practice: maintain another small register", [12, 13]),
                ],
                [
                    "Choose a list, tuple, dictionary, or set from the meaning of the data.",
                    "Modify a collection while distinguishing shared references from copies.",
                    "Represent one record with named fields and several records as a list of dictionaries.",
                    "Search by a stable ID and define the result when no record exists.",
                    "Apply add, read, update, and remove operations without losing collection invariants.",
                ],
                [
                    "Selected data structures from ordering, mutability, named fields, and uniqueness.",
                    "Represented record collections and searched them by stable ID.",
                    "Maintained a collection through explicit add, update, and remove rules.",
                ],
                "Lesson 2.2 moves these operations into small functions with public contracts, explicit errors, and automatic tests.",
                "Required: 2.1.1, 2.1.3–2.1.6  |  Supporting: 2.1.2  |  Integration: 2.1.7", "about 3 hours",
                {"2.1.1": "When does assignment share the same list, and when is a copy required?", "2.1.6": "What must a search function return when the requested ID is absent?"},
            ),
            lesson(
                "2.2", 45,
                [
                    ("2.2.1 Define a function with explicit inputs and output", [0, 1, 2]),
                    ("2.2.2 Control scope and communicate the calling contract", [3, 4, 5]),
                    ("2.2.3 Separate validation, processing, and presentation", [6]),
                    ("2.2.4 Read errors and catch only expected exceptions", [7, 8]),
                    ("2.2.5 Test normal, boundary, and invalid cases", [9]),
                    ("2.2.6 Specify and test search and mutation contracts", [10, 11]),
                    ("2.2.7 Integrated practice: connect tested functions", [12, 13]),
                ],
                [
                    "Define and call functions with parameters and useful return values.",
                    "Explain local scope, defaults, keyword arguments, docstrings, and type hints as parts of a contract.",
                    "Separate validation, calculation, state change, and presentation responsibilities.",
                    "Classify syntax, runtime, and logical errors and keep exception handling narrow.",
                    "Test return values, raised exceptions, and state before and after a call.",
                ],
                [
                    "Used functions to give each processing responsibility a stable name and contract.",
                    "Handled only predictable exceptional cases near the operation that can fail.",
                    "Verified normal, boundary, invalid, and state-changing behaviour independently.",
                ],
                "Lesson 2.3 uses these function and error contracts to read persistent records from CSV and save a separately verifiable result.",
                "Required: 2.2.1–2.2.6  |  Integration: 2.2.7", "about 3 hours",
                {"2.2.1": "Why is returning a value usually more reusable than printing inside a calculation function?", "2.2.5": "For a state-changing function, what must be checked in addition to its return value?"},
            ),
            lesson(
                "2.3", 285,
                [
                    ("2.3.1 Resolve the intended file path", [0, 1]),
                    ("2.3.2 Open text files with an explicit mode and encoding", [2]),
                    ("2.3.3 Read CSV records without losing their structure", [3, 4]),
                    ("2.3.4 Validate headers, rows, and converted values", [5]),
                    ("2.3.5 Save to a separate file and verify by re-reading", [6]),
                    ("2.3.6 Integrated practice: build a complete CSV round trip", [7]),
                ],
                [
                    "Resolve file paths from a defined base rather than an assumed current directory.",
                    "Open and close text files safely with an explicit mode and UTF-8 encoding.",
                    "Read CSV with DictReader and convert field values according to a declared schema.",
                    "Validate the header and every row before using the records.",
                    "Write a separate CSV with DictWriter and verify the saved product by re-reading it.",
                ],
                [
                    "Separated path resolution, opening, CSV parsing, conversion, and validation.",
                    "Preserved quoted CSV fields that contain commas.",
                    "Protected the source file and validated the output after a complete round trip.",
                ],
                "Project 2.4 combines record structures, tested functions, validation, and CSV input/output in a library-record update program.",
                "Required: 2.3.1–2.3.5  |  Integration: 2.3.6", "about 3 hours",
                {"2.3.3": "Why is split(',') not a correct general CSV parser?", "2.3.5": "Why does re-reading the output verify more than a successful write call?"},
            ),
        ],
    },
    "ja": {
        "source": "chapter3-current-PYAI-INTRO-JA.json",
        "shortname": "PYAI-INTRO-JA",
        "labels": {
            "intro": "導入",
            "outcomes": "このレッスンの到達目標",
            "route": "学習経路",
            "summary": "まとめ",
            "next": "次のレッスンへ",
            "outcome_lead": "このレッスンを終えると、次のことができます。",
            "summary_lead": "このレッスンでは、次を確認しました。",
            "time": "学習時間の目安",
            "check": "確認",
        },
        "lessons": [],
    },
}


JA_TEXT = {
    "1.1": ([
        ("1.1.1 値・式・出力を読み分ける", [0, 1, 2]),
        ("1.1.2 計算の意図を順序として表す", [3]),
        ("1.1.3 プログラムを変更し、結果を確かめる", [4]),
        ("1.1.4 統合練習：短いプログラムを書いて説明する", [5]),
    ], ["短いプログラムから命令・値・式・出力を見分けられる。", "上から順に実行結果を予測し、計算と表示を区別できる。", "括弧を使って計算順序と意図を明確にできる。", "プログラムを変更し、表示結果どうしに矛盾がないか確認できる。"], ["結果だけでなく、結果を生んだ命令の順序としてプログラムを読みました。", "数値、引用符で囲まれた文字列、式、意図した出力を区別しました。", "予測、実行、説明の順で短いプログラムを確認しました。"], "1.2では、繰り返し現れる値に名前を付け、一か所の変更を関連する計算へ一貫して反映させます。", "必須：1.1.1～1.1.3　｜　統合練習：1.1.4", "約2時間"),
    "1.2": ([
        ("1.2.1 代入によってプログラムの状態を作り、更新する", [0, 1, 2, 3]),
        ("1.2.2 意味のある名前を選び、NameErrorを調べる", [4, 5]),
        ("1.2.3 Notebookの状態を再現可能にする", [6]),
        ("1.2.4 統合練習：小さなモデルの状態を追跡する", [7]),
    ], ["右辺を評価してから左辺の名前を更新する代入の流れを説明できる。", "再代入の後で各変数が指す値を追跡できる。", "代入の=と比較の==を区別できる。", "有効で意味のある名前を選び、綴りや実行順によるエラーを調べられる。"], ["一つの意味を持つ値に名前を付け、情報源を一か所にしました。", "再代入と、自動では再計算されない計算結果を追跡しました。", "カーネルを再起動して上から実行し、隠れた状態への依存を確認しました。"], "1.3では、変数が保持する基本データ型と、型ごとに使える算術演算・型変換を整理します。", "必須：1.2.1～1.2.3　｜　統合練習：1.2.4", "約2時間"),
    "1.3": ([
        ("1.3.1 基本的なスカラー型を区別する", [0]),
        ("1.3.2 数値演算子と明示した順序で計算する", [1, 2]),
        ("1.3.3 必要な型へ変換し、値を検証する", [3, 4]),
        ("1.3.4 統合練習：割合を計算して確かめる", [5]),
    ], ["int・float・str・bool・Noneを、値と用途から区別できる。", "7種類の算術演算子と括弧を適切に使える。", "互換性のある型を明示的に変換し、変換エラーを読める。", "実行できたかだけでなく、数値が妥当か確認できる。"], ["データ型とPythonが実行できる操作を結び付けました。", "算術の順序を明確にし、注意すべき数値の境界を確認しました。", "元の値と変換先の型に互換性があるときだけ変換しました。"], "1.4では、文字列を並びとして扱い、キーボード入力を受け取り、値を意味の分かる出力へ組み立てます。", "必須：1.3.1～1.3.3　｜　統合練習：1.3.4", "約2.5時間"),
    "1.4": ([
        ("1.4.1 文字列を作り、調べ、変換する", [0, 1, 2, 3]),
        ("1.4.2 型を合わせて文字列と値を組み合わせる", [4]),
        ("1.4.3 入力を受け取り、計算用の型へ変換する", [5]),
        ("1.4.4 意味の分かる書式付き出力を作る", [6]),
        ("1.4.5 単純な区切り文字付きテキストを読む", [7]),
        ("1.4.6 統合練習：入力・計算・報告をつなぐ", [8]),
    ], ["文字列を作り、位置を使って文字や部分文字列を取り出せる。", "文字列メソッドが元の文字列を変えず、新しい値を返すと説明できる。", "入力を文字列として受け取り、数値計算の前に変換できる。", "f文字列でラベルと値を組み合わせた出力を作れる。", "単純な区切り文字付きデータと、本格的なCSV処理の違いを説明できる。"], ["文字列を順序のある変更不能な並びとして扱いました。", "文字列と数値を組み合わせる前に型を合わせました。", "キーボード入力、型変換、計算、書式付き出力を一つの流れにしました。"], "1.5では、比較とブール論理を使い、計算した値に応じてプログラムが行う処理を選びます。", "必須：1.4.1～1.4.4　｜　補足：1.4.5　｜　統合練習：1.4.6", "約2.5時間"),
    "1.5": ([
        ("1.5.1 ブール値を生む比較条件を作る", [0, 1]),
        ("1.5.2 ifとelseで二つの処理から選ぶ", [2, 3]),
        ("1.5.3 複数候補と独立した処理を区別する", [4, 5]),
        ("1.5.4 ブール論理で条件を組み合わせる", [6]),
        ("1.5.5 値の範囲を検証し、境界をテストする", [7, 8]),
        ("1.5.6 短絡評価を安全確認に使う", [9]),
        ("1.5.7 統合練習：業務ルールを実装して検証する", [10]),
    ], ["比較式を作り、その結果となるbool値を説明できる。", "インデントとif・elif・elseで排他的な規則を実装できる。", "一つの分岐連鎖と独立したif文を使い分けられる。", "and・or・notを明示的なまとまりで組み合わせられる。", "不正値と、境界の直前・境界上・直後をテストできる。"], ["比較とブール値をインデントされた分岐へ結び付けました。", "重なる条件を並べ、最初に真となる分岐が正しくなるようにしました。", "許容範囲を先に検証し、すべての境界と分岐を試しました。"], "1.6では、同じ判断と更新を複数の値へ適用し、同じコードを何度も書かずに処理します。", "必須：1.5.1～1.5.5　｜　補足：1.5.6　｜　統合練習：1.5.7", "約3時間"),
    "1.6": ([
        ("1.6.1 forとrangeで既知の値を繰り返し処理する", [0, 1, 2]),
        ("1.6.2 合計と条件付き件数を作る", [3, 4]),
        ("1.6.3 最大・最小などの値と位置を追跡する", [5, 6]),
        ("1.6.4 一回の処理またはループ全体を制御する", [7]),
        ("1.6.5 条件が変わるまでwhileで繰り返す", [8, 9]),
        ("1.6.6 0回の繰り返しと空の入力を扱う", [10]),
        ("1.6.7 統合練習：値の並びを処理して報告する", [11]),
    ], ["既知の値にはfor、変化する終了条件にはwhileを選べる。", "rangeとenumerateを境界のずれなく使える。", "正しい初期値から合計・件数・最大値を更新できる。", "breakとcontinueが繰り返しへ与える影響を説明できる。", "空の入力と0回の繰り返しに対する結果を定義できる。"], ["各繰り返しと、ループ本体が変更する状態を追跡しました。", "アキュムレータ、条件付きカウンタ、位置の追跡を使いました。", "処理目的からループの種類と初期値を選びました。"], "1.7では、入力・型変換・条件分岐・forループを組み合わせ、5日分の日報から自動確認可能な週間報告を作ります。", "必須：1.6.1～1.6.3、1.6.5～1.6.6　｜　補足：1.6.4　｜　統合練習：1.6.7", "約3時間"),
    "2.1": ([
        ("2.1.1 リストで順序のある値を保持し、変更する", [0, 1, 2, 3]),
        ("2.1.2 タプルで固定された順序付き値を表す", [4]),
        ("2.1.3 辞書で名前付きフィールドを表す", [5, 6, 7]),
        ("2.1.4 辞書のリストで複数レコードを表す", [8]),
        ("2.1.5 集合で重複のない所属を表す", [9]),
        ("2.1.6 備品台帳のレコードを検索・追加・更新・削除する", [10, 11]),
        ("2.1.7 統合練習：別の小さな台帳を管理する", [12, 13]),
    ], ["データの意味からリスト・タプル・辞書・集合を選べる。", "同じオブジェクトの共有とコピーを区別してコレクションを変更できる。", "一件を名前付きフィールドで、複数件を辞書のリストで表せる。", "安定したIDで検索し、見つからない場合の結果を定義できる。", "追加・参照・更新・削除でコレクションの規則を保てる。"], ["順序、変更可能性、名前付きフィールド、一意性から構造を選びました。", "レコードの集合を表し、安定したIDで検索しました。", "追加・更新・削除の規則を明示して台帳を保守しました。"], "2.2では、これらの操作を公開仕様のある小さな関数へ分け、エラーと自動テストを扱います。", "必須：2.1.1、2.1.3～2.1.6　｜　補足：2.1.2　｜　統合練習：2.1.7", "約3時間"),
    "2.2": ([
        ("2.2.1 入力と出力が明確な関数を定義する", [0, 1, 2]),
        ("2.2.2 スコープを管理し、呼び出し規約を伝える", [3, 4, 5]),
        ("2.2.3 検証・処理・表示の責任を分ける", [6]),
        ("2.2.4 エラーを読み、予測できる例外だけを捕捉する", [7, 8]),
        ("2.2.5 正常・境界・異常ケースをテストする", [9]),
        ("2.2.6 検索・更新関数の契約と状態変化をテストする", [10, 11]),
        ("2.2.7 統合練習：テスト済み関数を接続する", [12, 13]),
    ], ["引数と有用な戻り値を持つ関数を定義し、呼び出せる。", "ローカルスコープ、既定値、キーワード引数、docstring、型ヒントを関数契約として説明できる。", "検証・計算・状態変更・表示の責任を分けられる。", "構文・実行時・論理エラーを区別し、狭い範囲で例外を処理できる。", "戻り値、発生する例外、呼び出し前後の状態をテストできる。"], ["各処理責任に安定した名前と契約を与えました。", "予測できる例外だけを、失敗する処理の近くで扱いました。", "正常・境界・異常・状態変更を個別に検証しました。"], "2.3では、関数とエラーの契約を使い、CSVから永続的なレコードを読み、別の検証可能な結果として保存します。", "必須：2.2.1～2.2.6　｜　統合練習：2.2.7", "約3時間"),
    "2.3": ([
        ("2.3.1 読み込むファイルの場所を確定する", [0, 1]),
        ("2.3.2 モードと文字コードを明示してファイルを開く", [2]),
        ("2.3.3 構造を壊さずにCSVレコードを読む", [3, 4]),
        ("2.3.4 ヘッダー・各行・変換した値を検証する", [5]),
        ("2.3.5 別ファイルへ保存し、再読込で確認する", [6]),
        ("2.3.6 統合練習：CSVの往復処理を完成させる", [7]),
    ], ["現在位置を仮定せず、定義した基準位置からパスを作れる。", "モードとUTF-8を明示し、安全にテキストファイルを開閉できる。", "DictReaderでCSVを読み、宣言したスキーマに従って値を変換できる。", "使用前にヘッダーと各行を検証できる。", "DictWriterで別のCSVへ保存し、再読込した成果物を確認できる。"], ["パス解決、ファイルを開く処理、CSV解析、型変換、検証を分けました。", "カンマを含む引用符付きフィールドを壊さずに読みました。", "原本を保護し、保存後の再読込まで含む往復処理を検証しました。"], "2.4では、レコード構造、テスト済み関数、入力検証、CSV入出力を組み合わせ、図書台帳を更新するプログラムを作ります。", "必須：2.3.1～2.3.5　｜　統合練習：2.3.6", "約3時間"),
}


JA_CMIDS = {"1.1": 181, "1.2": 183, "1.3": 271, "1.4": 279, "1.5": 185, "1.6": 187, "2.1": 189, "2.2": 191, "2.3": 289}
for en_spec in SPECS["en"]["lessons"]:
    number = en_spec["number"]
    groups, outcomes, summary, next_text, route, time = JA_TEXT[number]
    checks = {
        groups[0][0].split()[0]: "この項目の中心となる値・処理・結果を、自分の言葉で区別して説明できますか。",
        groups[-2][0].split()[0]: "境界や例外的な入力を一つ選び、期待する結果を説明できますか。",
    }
    SPECS["ja"]["lessons"].append(lesson(number, JA_CMIDS[number], groups, outcomes, summary, next_text, route, time, checks))


def plain_heading(fragment: str) -> str:
    return html.unescape(re.sub(r"<[^>]+>", "", fragment)).strip()


def parse_page(content: str) -> list[tuple[str, str, str]]:
    inner = re.sub(r"^<div[^>]*>", "", content.strip(), count=1)
    inner = re.sub(r"</div>\s*$", "", inner, count=1)
    pattern = re.compile(r"<h([234])[^>]*>(.*?)</h\1>", re.I | re.S)
    matches = list(pattern.finditer(inner))
    result = []
    for index, match in enumerate(matches):
        body = inner[match.end(): matches[index + 1].start() if index + 1 < len(matches) else len(inner)]
        result.append((match.group(1), plain_heading(match.group(2)), body))
    return result


def clean_body(body: str, language: str) -> str:
    body = re.sub(r'<p><strong>(?:Estimated study time|Learning time|学習時間の目安)[^<]*</strong>.*?</p>', '', body, flags=re.I | re.S)
    body = re.sub(r'<p style="display:none">PYAI-[^<]+</p>', '', body)
    if language == "en":
        body = re.sub(r'<p>After this lesson,.*?</p>', '', body, flags=re.S)
    else:
        body = re.sub(r'<p>このレッスンを終えると.*?</p>', '', body, flags=re.S)
    return body.strip()


def take_intro(body: str) -> tuple[str, str]:
    match = re.match(r"\s*(<p>.*?</p>)", body, flags=re.S)
    if not match:
        raise RuntimeError("First section does not start with an introduction paragraph")
    return match.group(1), body[match.end():].strip()


def h2(text: str) -> str:
    return f'<h2 style="{GROUP_STYLE}">{html.escape(text)}</h2>'


def render_page(content: str, spec: dict, labels: dict, language: str) -> str:
    sections = parse_page(content)
    if not sections or sections[0][0] != "2":
        raise RuntimeError(f"{spec['number']}: expected an initial h2")
    first_title = sections[0][1]
    first_body = clean_body(sections[0][2], language)
    intro, first_remainder = take_intro(first_body)
    sections[0] = (sections[0][0], first_title, first_remainder)
    original_pre = re.findall(r"<pre\b.*?</pre>", content, flags=re.I | re.S)

    pieces = ['<div class="python-sample-lesson python-sample-lesson-v38">']
    pieces += [h2(labels["intro"]), f'<p><strong>{html.escape(first_title)}</strong></p>', intro]
    pieces += [h2(labels["outcomes"]), f'<p>{html.escape(labels["outcome_lead"])}</p><ul>']
    pieces += [f'<li>{html.escape(item)}</li>' for item in spec["outcomes"]]
    pieces += ['</ul>', f'<aside style="{NOTE_STYLE}"><strong>{html.escape(labels["route"])}:</strong> {html.escape(spec["route"])}</aside>']

    used = []
    for title, indices in spec["groups"]:
        pieces.append(h2(title))
        for index in indices:
            if index >= len(sections):
                raise RuntimeError(f"{spec['number']}: section index {index} out of range")
            used.append(index)
            _, old_title, body = sections[index]
            body = clean_body(body, language)
            if index != 0:
                pieces.append(f'<h3>{html.escape(old_title)}</h3>')
            pieces.append(body)
        key = title.split()[0]
        if key in spec["checks"]:
            pieces.append(f'<p style="{CHECK_STYLE}"><strong>{html.escape(labels["check"])}:</strong> {html.escape(spec["checks"][key])}</p>')

    if sorted(used) != list(range(len(sections))):
        raise RuntimeError(f"{spec['number']}: sections not used exactly once: {used} / {len(sections)}")
    pieces += [h2(labels["summary"]), f'<p>{html.escape(labels["summary_lead"])}</p><ul>']
    pieces += [f'<li>{html.escape(item)}</li>' for item in spec["summary"]]
    pieces += ['</ul>', h2(labels["next"]), f'<p>{html.escape(spec["next"])}</p>']
    pieces += [f'<p><strong>{html.escape(labels["time"])}:</strong> {html.escape(spec["time"])}</p>', '<p style="display:none">PYAI-V38-TEXTBOOK-STRUCTURE</p></div>']
    result = ''.join(pieces)
    if re.findall(r"<pre\b.*?</pre>", result, flags=re.I | re.S) != original_pre:
        raise RuntimeError(f"{spec['number']}: code blocks changed")
    if '<h4' in result:
        raise RuntimeError(f"{spec['number']}: unexpected h4")
    return result


NOTEBOOKS = {
    "1.1": ("01_programs_values_output.ipynb", "cell-001", "cell-013", ["cell-003", "cell-009", "cell-011", "cell-014"], ["cell-002"]),
    "1.2": ("02_variables_types_calculations.ipynb", "l2-en-title", "l2-en-complete", ["l2-en-one-source", "l2-en-names", "l2-en-kernel", "l2-en-guided"], []),
    "1.3": ("03_basic_scalar_types.ipynb", "l13-en-title", "l13-en-complete", ["l13-en-types", "l13-en-arithmetic", "l13-en-conversion", "l13-en-guided"], []),
    "1.4": ("04_strings_input_formatting.ipynb", "l14-en-title", "l14-en-complete", ["l14-en-create", "l14-en-combine", "l14-en-input", "l14-en-format", "l14-en-split", "l14-en-transfer"], []),
    "1.5": ("03_conditions_boundaries.ipynb", "l15-en-title", "l15-en-complete", ["l15-en-bool", "l15-en-if", "l15-en-chain", "l15-en-logic", "l15-en-boundary", "l15-en-short", "l15-en-transfer"], []),
    "1.6": ("04_loops_accumulators.ipynb", "l16-en-title", "l16-en-complete", ["l16-en-for", "l16-en-total", "l16-en-maximum", "l16-en-control", "l16-en-while", "l16-en-choice", "l16-en-transfer"], []),
    "2.1": ("05_lists_dictionaries_records.ipynb", "l21-en-title", "l21-en-complete", ["l21-en-list", "l21-en-tuple", "l21-en-dict", "l21-en-records", "l21-en-set", "l21-v23-en-search", "l21-en-transfer"], []),
    "2.2": ("06_functions_errors_testing.ipynb", "l22-en-title", "l22-en-complete", ["l22-en-def", "l22-en-scope", "l22-en-record", "l22-en-errors", "l22-en-tests", "l22-v23-en-contract", "l22-en-transfer"], []),
    "2.3": ("07_files_csv.ipynb", "l23-en-title", "l23-en-complete", ["l23-en-path", "l23-en-text", "l23-en-csv", "l23-en-validation", "l23-en-write", "l23-en-transfer"], []),
}


def local_id(cell_id: str, language: str) -> str:
    return cell_id.replace("-en-", "-ja-") if language == "ja" else cell_id


def replace_first_heading(cell: dict, heading: str) -> None:
    lines = ''.join(cell.get("source", [])).splitlines(keepends=True)
    if not lines or not lines[0].startswith('#'):
        raise RuntimeError(f"{cell.get('id')}: heading missing")
    lines[0] = heading + ('\n' if lines[0].endswith('\n') else '')
    cell["source"] = lines


def overview(spec: dict, labels: dict, language: str) -> str:
    lead = "このNotebookでは、本文の考え方を実際のコードで確かめます。" if language == "ja" else "Use this Notebook to verify the lesson ideas with executable code."
    bullets = '\n'.join(f'- {item}' for item in spec["outcomes"])
    return f"## {labels['intro']}\n\n{lead}\n\n## {labels['outcomes']}\n\n{bullets}\n\n> **{labels['route']}:** {spec['route']}\n"


def closing(spec: dict, labels: dict) -> str:
    bullets = '\n'.join(f'- {item}' for item in spec["summary"])
    return f"## {labels['summary']}\n\n{bullets}\n\n## {labels['next']}\n\n{spec['next']}\n\n**{labels['time']}:** {spec['time']}\n"


def update_notebooks() -> dict:
    snapshot = {}
    specs_by_language = {language: {item["number"]: item for item in data["lessons"]} for language, data in SPECS.items()}
    for language in ("en", "ja"):
        folder = Path("ja") if language == "ja" else Path()
        labels = SPECS[language]["labels"]
        for number, (filename, title_id, complete_id, starts, removals) in NOTEBOOKS.items():
            relative = folder / filename
            path = TEMPLATES / relative
            document = json.loads(path.read_text(encoding="utf-8"))
            key = relative.as_posix()
            snapshot[key] = [
                {"id": cell.get("id"), "source": cell.get("source", [])}
                for cell in document["cells"] if cell.get("cell_type") == "code"
            ]
            cells = document["cells"]
            actual_title = local_id(title_id, language)
            actual_complete = local_id(complete_id, language)
            actual_removals = {local_id(item, language) for item in removals}
            cells[:] = [cell for cell in cells if cell.get("id") not in actual_removals and not str(cell.get("id", "")).startswith(f"chapters12-{language}-{number.replace('.', '')}-overview-v38")]
            by_id = {cell.get("id"): cell for cell in cells}
            title_index = next(index for index, cell in enumerate(cells) if cell.get("id") == actual_title)
            spec = specs_by_language[language][number]
            cells.insert(title_index + 1, {
                "cell_type": "markdown", "id": f"chapters12-{language}-{number.replace('.', '')}-overview-v38", "metadata": {},
                "source": overview(spec, labels, language).splitlines(keepends=True),
            })
            by_id = {cell.get("id"): cell for cell in cells}
            start_ids = [local_id(item, language) for item in starts]
            group_titles = [title for title, _ in spec["groups"]]
            if len(start_ids) != len(group_titles):
                raise RuntimeError(f"{key}: group count mismatch")
            for cell in cells:
                if cell.get("cell_type") != "markdown" or cell.get("id") in {actual_title, actual_complete, f"chapters12-{language}-{number.replace('.', '')}-overview-v38"}:
                    continue
                source = ''.join(cell.get("source", []))
                if source.startswith("## "):
                    replace_first_heading(cell, "### " + source.splitlines()[0][3:])
            for cell_id, group_title in zip(start_ids, group_titles):
                replace_first_heading(by_id[cell_id], "## " + group_title)
            by_id[actual_complete]["source"] = closing(spec, labels).splitlines(keepends=True)
            document.setdefault("metadata", {}).setdefault("pyai", {})["structure_revision"] = 38
            path.write_text(json.dumps(document, ensure_ascii=False, indent=1) + '\n', encoding="utf-8", newline='\n')
            numbered = [
                ''.join(cell.get("source", [])).splitlines()[0]
                for cell in cells
                if cell.get("cell_type") == "markdown" and ''.join(cell.get("source", [])).startswith(f"## {number}.")
            ]
            if len(numbered) != len(group_titles):
                raise RuntimeError(f"{key}: numbered groups {numbered}")
    return snapshot


def write_php(pages: dict, expected: dict) -> None:
    encoded = json.dumps(pages, ensure_ascii=False, separators=(",", ":"))
    upgrade = f'''<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
core\\session\\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname'=>$shortname], '*', MUST_EXIST);
$all = json_decode(<<<'JSON'
{encoded}
JSON, true, 512, JSON_THROW_ON_ERROR);
foreach ($all[$shortname] as $cmid => $content) {{
    $cm = get_coursemodule_from_id('page', (int)$cmid, $course->id, false, MUST_EXIST);
    $page = $DB->get_record('page', ['id'=>$cm->instance], '*', MUST_EXIST);
    if ($page->content !== $content) {{
        $page->content = $content;
        $page->timemodified = time();
        $DB->update_record('page', $page);
    }}
    echo json_encode(['course'=>$shortname,'cmid'=>(int)$cmid,'page'=>$page->name], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), PHP_EOL;
}}
rebuild_course_cache($course->id, true);
'''
    (SCRIPTS / "upgrade-python-chapters12-structure-v38.php").write_text(upgrade, encoding="utf-8", newline='\n')

    expected_json = json.dumps(expected, ensure_ascii=False, separators=(",", ":"))
    verify = f'''<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
$expected = json_decode(<<<'JSON'
{expected_json}
JSON, true, 512, JSON_THROW_ON_ERROR);
$result=[];
foreach($expected as $shortname=>$pages) {{
  $course=$DB->get_record('course',['shortname'=>$shortname],'*',MUST_EXIST);
  foreach($pages as $cmid=>$spec) {{
    $cm=get_coursemodule_from_id('page',(int)$cmid,$course->id,false,MUST_EXIST);
    $page=$DB->get_record('page',['id'=>$cm->instance],'*',MUST_EXIST);
    if(!str_contains($page->content,'PYAI-V38-TEXTBOOK-STRUCTURE'))throw new RuntimeException("$shortname $cmid marker");
    if(substr_count($page->content,'<h4')!==0)throw new RuntimeException("$shortname $cmid h4");
    $groups=preg_match_all('/<h2[^>]*>'.preg_quote($spec['number'], '/').'\\.[0-9]+ /u',$page->content);
    if($groups!==(int)$spec['groups'])throw new RuntimeException("$shortname $cmid groups $groups");
    if(substr_count($page->content,'<pre')!==(int)$spec['pre'])throw new RuntimeException("$shortname $cmid code blocks");
    $result[]=['course'=>$shortname,'cmid'=>(int)$cmid,'lesson'=>$spec['number'],'groups'=>$groups,'code_blocks'=>(int)$spec['pre']];
  }}
  $prefix=$shortname==='PYAI-INTRO-JA'?'理解度チェック：':'Knowledge check:';
  foreach($DB->get_records_select('quiz','course = ? AND name LIKE ?',[$course->id,$prefix.'%']) as $quiz) {{
    if(preg_match('/(?:1\\.[1-6]|2\\.[1-3])/', $quiz->name) && $DB->count_records('quiz_slots',['quizid'=>$quiz->id])!==10)throw new RuntimeException($quiz->name.' slots');
  }}
}}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
'''
    (SCRIPTS / "verify-python-chapters12-structure-v38.php").write_text(verify, encoding="utf-8", newline='\n')
    apply = '''#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root_dir"
for shortname in PYAI-INTRO PYAI-INTRO-JA; do
  docker compose -f docker-compose.local.yml exec -T -e PYTHON_COURSE_SHORTNAME="$shortname" moodle runuser -u www-data -- php < scripts/upgrade-python-chapters12-structure-v38.php
done
docker compose -f docker-compose.local.yml exec -T moodle runuser -u www-data -- php < scripts/verify-python-chapters12-structure-v38.php
'''
    (SCRIPTS / "apply-python-chapters12-structure-v38.sh").write_text(apply, encoding="utf-8", newline='\n')


def main() -> None:
    pages = {}
    expected = {}
    structure_report = {}
    for language, language_spec in SPECS.items():
        source = json.loads((AUDITS / language_spec["source"]).read_text(encoding="utf-8"))
        current = {int(item["cmid"]): item for item in source["activities"] if item["modname"] == "page"}
        shortname = language_spec["shortname"]
        pages[shortname] = {}
        expected[shortname] = {}
        structure_report[shortname] = []
        for spec in language_spec["lessons"]:
            item = current[spec["cmid"]]
            rendered = render_page(item["content"], spec, language_spec["labels"], language)
            pages[shortname][str(spec["cmid"])] = rendered
            pre = len(re.findall(r"<pre\b", item["content"], flags=re.I))
            expected[shortname][str(spec["cmid"])] = {"number": spec["number"], "groups": len(spec["groups"]), "pre": pre}
            structure_report[shortname].append({
                "number": spec["number"], "cmid": spec["cmid"],
                "before": [entry[1] for entry in parse_page(item["content"])],
                "after": [title for title, _ in spec["groups"]],
                "outcomes": spec["outcomes"], "route": spec["route"], "next": spec["next"],
            })

    code_snapshot = update_notebooks()
    (AUDITS / "chapters12-pre-v38-notebook-code.json").write_text(json.dumps(code_snapshot, ensure_ascii=False, indent=2) + '\n', encoding="utf-8")
    (AUDITS / "chapters12-structure-v38.json").write_text(json.dumps(structure_report, ensure_ascii=False, indent=2) + '\n', encoding="utf-8")
    write_php(pages, expected)
    print(json.dumps({"pages": sum(map(len, pages.values())), "notebooks": len(code_snapshot), "revision": 38}, ensure_ascii=False))


if __name__ == "__main__":
    main()

