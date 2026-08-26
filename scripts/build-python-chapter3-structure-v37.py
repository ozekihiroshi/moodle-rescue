#!/usr/bin/env python3
"""Build the Chapter 3 textbook-style structure without changing lesson meaning."""

from __future__ import annotations

import html
import json
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONTENT = ROOT / "sample-content" / "introduction-to-python"
AUDITS = CONTENT / "structure-audits"
TEMPLATES = CONTENT / "python-lab" / "templates"
UPGRADE = ROOT / "scripts" / "upgrade-python-chapter3-structure-v37.php"
VERIFY = ROOT / "scripts" / "verify-python-chapter3-structure-v37.php"
APPLY = ROOT / "scripts" / "apply-python-chapter3-structure-v37.sh"

GROUP_STYLE = "margin-top:2em;padding-bottom:.35em;border-bottom:2px solid #0f6cbf"
NOTE_STYLE = "margin:1em 0;padding:.75em 1em;border-left:4px solid #5b7c99;background:#f6f8fa"
CHECK_STYLE = "margin:.9em 0;padding:.65em .85em;border:1px solid #d8dce1;background:#fbfcfd"


def lesson_specs() -> dict[str, dict]:
    return {
        "ja": {
            "source": "chapter3-current-PYAI-INTRO-JA.json",
            "shortname": "PYAI-INTRO-JA",
            "pages": {
                "レッスン3.1：表形式データ・CSV・pandas": {
                    "cmid": 193,
                    "number": "3.1",
                    "time": "約4時間",
                    "intro_heading": "導入",
                    "outcomes_heading": "このレッスンの到達目標",
                    "route": "必須：3.1.1〜3.1.4　／　補足：3.1.5　／　統合練習：3.1.6",
                    "route_label": "学習経路",
                    "summary_heading": "まとめ",
                    "next_heading": "次のレッスンへ",
                    "outcomes": [
                        "表形式データの一行・一列・セルが何を表すか説明できる。",
                        "CSVの読込条件と実際に読み込んだファイルを確認できる。",
                        "DataFrameの形、列名、型、欠損、カテゴリ値を調べられる。",
                        "計算列を追加し、indexを混入させずCSVへ保存できる。",
                    ],
                    "summary": [
                        "一行の観測単位と列の意味を決めてから表を扱いました。",
                        "CSVの場所と読込条件を確認し、DataFrame全体を観察しました。",
                        "計算結果を別の表として作り、保存後の形と列を確かめました。",
                    ],
                    "next": "確認できる表ができました。3.2では、分析の問いを列と行条件へ分け、必要なレコードだけを再現可能な方法で選びます。",
                    "first": "3.1.1 表形式データ — 行・列・セルの意味",
                    "removals": ["次の3.2では、分析の問いに合う行と列を選びます。"],
                    "groups": [
                        ("3.1.1 行・列・セルとスキーマを理解する", "3.1.1 表形式データ — 行・列・セルの意味", [("一行を一観測、一列を一変数としてそろえる", None)]),
                        ("3.1.2 CSVと読込条件を理解する", "3.1.2 CSV — 表を交換するテキスト形式", [("相対パスと実際に読み込むファイル", None), ("read_csvの前提をコードへ明示する", None)]),
                        ("3.1.3 DataFrameを読み込み、内容を確認する", "3.1.3 pandas — DataFrameを読み、確かめる", [("全件表示とカテゴリ値の件数を確認する", None), ("一列はSeries、複数列の表はDataFrame", None)]),
                        ("3.1.4 計算列を作り、表を保存する", "3.1.4 pandas — 列計算とCSV出力", [("indexと業務上の識別子を区別する", None)]),
                        ("3.1.5 読込問題を原因別に切り分ける", "3.1.5 読込問題を原因別に切り分ける", []),
                        ("3.1.6 統合練習：表を作り、保存し、再確認する", "3.1.6 例題を別の表へ応用する", []),
                    ],
                    "checks": {
                        "3.1.1": "この表で一行が何を表すか、識別子と測定値を分けて説明してください。",
                        "3.1.3": "『CSVを読み込めた』だけでは不十分です。次に確認する四つの情報を挙げてください。",
                    },
                },
                "レッスン3.2：データの選択・抽出とブール論理": {
                    "cmid": 195, "number": "3.2", "time": "約4時間",
                    "intro_heading": "導入", "outcomes_heading": "このレッスンの到達目標",
                    "route": "必須：3.2.1〜3.2.6　／　統合練習：3.2.7", "route_label": "学習経路",
                    "summary_heading": "まとめ", "next_heading": "次のレッスンへ",
                    "outcomes": ["分析の問いを表示列と行条件へ分解できる。", "比較式からブールマスクを作り、複数条件を正しく組み合わせられる。", "所属・範囲・欠損を明示した条件を作れる。", "抽出前後の件数と並び順を確認し、同じ結果を再現できる。"],
                    "summary": ["問いを、表示する列と判定に使う部分条件へ分けました。", "名前を付けたブールマスクを組み合わせ、locで行と列を選びました。", "欠損と境界を明示し、抽出件数と並び順を検証しました。"],
                    "next": "再現可能な抽出条件を作れるようになりました。3.3では、抽出前のデータに含まれる欠損、表記ゆれ、矛盾、重複を、根拠を残しながら扱います。",
                    "first": "3.2.1 問いを表示列と行条件へ分ける",
                    "drop_paragraphs": ["このレッスンでは値を選びますが、まだ値を直しません。"],
                    "groups": [
                        ("3.2.1 問いを表示列と行条件へ分ける", "3.2.1 問いを表示列と行条件へ分ける", [("必要列を検査し、部分条件へ分ける", None), ("列名で必要な項目を選ぶ", None)]),
                        ("3.2.2 locとilocで行・列を選ぶ", "3.2.2 locとilocで行・列を選ぶ", []),
                        ("3.2.3 比較式をブールマスクにする", "3.2.3 比較式をブールマスクにする", []),
                        ("3.2.4 ブール論理で複数条件を組み立てる", "3.2.4 ブール論理で複数条件を組み立てる", [("AND・OR・NOTを件数で確かめる", None)]),
                        ("3.2.5 所属・範囲・欠損を条件に表す", "3.2.5 所属・範囲・欠損を条件に表す", [("欠損を条件から偶然落とさない", None)]),
                        ("3.2.6 抽出し、並べ、件数を検証する", "3.2.6 マスクで抽出し、件数を検証する", [("マスクと行をindexで対応させる", None)]),
                        ("3.2.7 統合練習：別の問いを条件へ翻訳する", "3.2.7 条件を別の問いへ応用する", []),
                    ],
                    "checks": {"3.2.4": "Seriesの条件でandではなく&を使う理由を、各行の判定という言葉を使って説明してください。", "3.2.6": "最終結果だけでなく、どの件数を残すと条件の誤りを見つけやすいでしょうか。"},
                },
                "レッスン3.3：データのクリーニングと監査記録": {
                    "cmid": 196, "number": "3.3", "time": "約3時間",
                    "intro_heading": "導入", "outcomes_heading": "このレッスンの到達目標",
                    "route": "必須：3.3.1〜3.3.6　／　統合練習：3.3.7", "route_label": "学習経路",
                    "summary_heading": "まとめ", "next_heading": "次のレッスンへ",
                    "outcomes": ["原本、作業用、分析用のデータを分けて保持できる。", "型変換失敗、欠損、表記ゆれ、範囲違反、項目間矛盾、重複を区別して検出できる。", "複数の品質理由を残した確認対象表を作れる。", "件数照合と監査記録により、分析用データが作られた過程を説明できる。"],
                    "summary": ["原本を保ったまま作業用コピーへ品質フラグを追加しました。", "問題ごとに名前付き規則を作り、一行に複数ある理由も残しました。", "分析対象と確認対象を分け、件数・制約・監査記録を再検証しました。"],
                    "next": "分析に使える行と確認が必要な行を説明できる形で分けました。3.4では、分析用データを目的に合う粒度へ集計し、判断に使える指標と順位を作ります。",
                    "first": "3.3.1 原本を保ち、品質問題を定義する",
                    "drop_paragraphs": ["このレッスンを終えたら、元データを保持し"],
                    "groups": [
                        ("3.3.1 原本を保ち、品質問題を定義する", "3.3.1 原本を保ち、品質問題を定義する", []),
                        ("3.3.2 原本・作業用・分析用データを分ける", "3.3.2 原本・作業用・分析用データを分ける", [("修正前に型・欠損・カテゴリ・範囲を確認する", None)]),
                        ("3.3.3 型変換失敗と欠損を扱う", "3.3.3 型変換と欠損を区別する", [("欠損と0を混同しない", None)]),
                        ("3.3.4 表記を整え、元の値を残す", "3.3.4 表記を整え、元の値を残す", []),
                        ("3.3.5 範囲・項目間制約・重複を検査する", "3.3.5 範囲・項目間制約・重複を検査する", [("業務キーで重複グループ全体を調べる", None)]),
                        ("3.3.6 確認対象と監査記録を残す", "3.3.6 品質フラグから確認対象表を作る", [("問題理由を行単位の確認対象表へ残す", None), ("3.3.7 監査記録と再検証を残す", "監査記録とassertで再検証する"), ("課題へ進む前に境界値を検証する", None)]),
                        ("3.3.7 統合練習：品質規則を別の表へ適用する", "3.3.8 品質規則を別のデータへ応用する", []),
                    ],
                    "checks": {"3.3.3": "空欄を0へ置き換える前に、二つの値が業務上同じ意味か説明してください。", "3.3.6": "一行が複数規則へ違反したとき、理由を一つに絞らないのはなぜでしょうか。"},
                },
                "レッスン3.4：グループ化と要約統計": {
                    "cmid": 198, "number": "3.4", "time": "約3時間",
                    "intro_heading": "導入", "outcomes_heading": "このレッスンの到達目標",
                    "route": "必須：3.4.1〜3.4.5　／　統合練習：3.4.6", "route_label": "学習経路",
                    "summary_heading": "まとめ", "next_heading": "3.5A 中間実践課題へ",
                    "outcomes": ["明細と集計結果について、一行が表す粒度を定義できる。", "groupbyと名前付きaggで、件数・合計・統計量・条件付き件数を作れる。", "率の分子と分母、構成比の合計、順位の比較順を説明できる。", "明細との照合、CSV保存、再読込により集計結果を検証できる。"],
                    "summary": ["先に結果表の粒度を決めてからgroupbyを行いました。", "件数、統計量、率、構成比、順位を、それぞれの定義と分母に結び付けました。", "明細との合計照合と再読込により、保存された成果物まで検証しました。"],
                    "next": "3.5Aでは、原資料を確認する小プログラムと、品質判定・集計・順位付けを行う本番プログラムを完成させます。3.1〜3.4の処理を初めて一つの意思決定へ接続します。",
                    "first": "3.4.1 明細と集計結果の粒度を決める",
                    "drop_paragraphs": ["集計粒度を定義し、件数の種類と統計量を選び"],
                    "groups": [
                        ("3.4.1 明細と集計結果の粒度を決める", "3.4.1 明細と集計結果の粒度を決める", []),
                        ("3.4.2 groupbyで分け、数え、まとめる", "3.4.2 groupbyで分け、数え、まとめる", [("size・count・nuniqueの対象を区別する", None), ("条件付き件数を名前付き集計へ入れる", None)]),
                        ("3.4.3 合計・統計量・率を計算する", "3.4.3 要約統計と割合を計算する", [("率は対応する分子と分母の合計から求める", None), ("複数キーで比較の階層を保つ", None)]),
                        ("3.4.4 判断に使う指標と順位を作る", None, [("3.4.4 複数条件で再現可能な順位を作る", "複数条件で再現可能な順位を作る"), ("3.4.5 構成比の分母と合計を確認する", "構成比の分母と100%を確認する")]),
                        ("3.4.5 集計結果を照合し、保存後に再確認する", "3.4.6 集計・保存・再読込を照合する", [("用途別CSVを保存し、再読込して検証する", None)]),
                        ("3.4.6 統合練習：別の判断に必要な集計を作る", "3.4.7 集計と順位を別の判断へ応用する", []),
                    ],
                    "checks": {"3.4.1": "元明細と地区別集計では、一行が表すものはどのように変わりますか。", "3.4.4": "順位を丸める前の値で決め、最後に安定したIDを使う理由を説明してください。"},
                },
            },
        },
    }


def english_from_japanese(ja: dict) -> dict:
    """Return the separately authored English instructional structure."""
    # English text is explicit so that the Japanese version remains an adaptation, not runtime translation.
    return {
        "source": "chapter3-current-PYAI-INTRO.json", "shortname": "PYAI-INTRO",
        "pages": {
            "Lesson 3.1: Tabular data, CSV, and pandas": _en_page(47, "3.1", "about 4 hours",
                ["Explain what one row, one column, and one cell represent in a table.", "State CSV loading assumptions and identify the file actually loaded.", "Inspect a DataFrame's shape, columns, inferred types, missingness, and raw categories.", "Add derived columns and save a CSV without an unintended index."],
                ["Defined the observation represented by a row and the variable represented by a column.", "Checked the CSV location and loading assumptions before interpreting the DataFrame.", "Inspected the complete table, then calculated and saved a separate result."],
                "The table is now inspectable. Lesson 3.2 turns an analysis question into displayed columns and reproducible row conditions.",
                "Required: 3.1.1–3.1.4 / Supporting: 3.1.5 / Integrated practice: 3.1.6",
                "3.1.1 Tabular data — rows, columns, and cells",
                [("3.1.1 Understand rows, columns, cells, and schema", "3.1.1 Tabular data — rows, columns, and cells", [("Align one observation per row and one variable per column", None)]), ("3.1.2 Understand CSV and loading assumptions", "3.1.2 CSV — a text format for exchanging tables", [("Relative paths and the file actually loaded", None), ("Express read_csv assumptions in code", None)]), ("3.1.3 Load and inspect a DataFrame", "3.1.3 pandas — load and inspect a DataFrame", [("Display every row and count raw category values", None), ("One column is a Series; multiple columns form a DataFrame", None)]), ("3.1.4 Calculate columns and save a table", "3.1.4 pandas — column calculations and CSV output", [("Distinguish the index from an operational identifier", None)]), ("3.1.5 Diagnose loading problems by cause", "3.1.5 Diagnose loading problems by cause", []), ("3.1.6 Integrated practice: build, save, re-read, and reconcile a table", "3.1.6 Transfer the example to another table", [])],
                {"3.1.1": "Explain what one row represents and distinguish identifiers from measured values.", "3.1.3": "Loading is not the same as loading correctly. Name four checks that should follow."}, removals=["Lesson 3.2 will select rows and columns that match an analysis question."]),
            "Lesson 3.2: Data selection, filtering, and Boolean logic": _en_page(49, "3.2", "about 4 hours",
                ["Decompose an analysis question into displayed columns and row conditions.", "Create Boolean masks from comparisons and combine them correctly.", "Represent membership, ranges, and missingness explicitly.", "Verify a filtered result through counts and deterministic ordering."],
                ["Separated an analysis question into output columns and named partial conditions.", "Combined Boolean masks and selected rows and columns with loc.", "Made boundaries and missingness explicit, then verified counts and order."],
                "The selection is reproducible. Lesson 3.3 handles missingness, inconsistent labels, contradictions, and duplicates while preserving evidence.",
                "Required: 3.2.1–3.2.6 / Integrated practice: 3.2.7", "3.2.1 Separate a question into displayed columns and row conditions",
                [("3.2.1 Separate a question into displayed columns and row conditions", "3.2.1 Separate a question into displayed columns and row conditions", [("Validate required columns and separate partial conditions", None), ("Select the fields needed for the result", None)]), ("3.2.2 Select rows and columns with labels and positions", "3.2.2 Select rows and columns with loc and iloc", []), ("3.2.3 Turn comparisons into Boolean masks", "3.2.3 Turn comparisons into Boolean masks", []), ("3.2.4 Build compound conditions with Boolean logic", "3.2.4 Build compound conditions with Boolean logic", [("Verify AND, OR, and NOT through counts", None)]), ("3.2.5 Express membership, ranges, and missingness", "3.2.5 Express membership, ranges, and missingness", [("Do not lose missing rows accidentally", None)]), ("3.2.6 Filter, order, and verify the result", "3.2.6 Filter with masks and verify row counts", [("Align masks and rows by index label", None)]), ("3.2.7 Integrated practice: translate and verify another question", "3.2.7 Transfer the conditions to another question", [])],
                {"3.2.4": "Explain why Series conditions use & rather than and, referring to one decision per row.", "3.2.6": "Which intermediate counts help reveal a reversed condition or boundary?"}, drop=["This lesson selects values without changing them."]),
            "Lesson 3.3: Data cleaning and audit records": _en_page(50, "3.3", "about 3 hours",
                ["Keep source, working, and analysis-ready data separate.", "Detect conversion failures, missingness, label variation, invalid ranges, cross-field contradictions, and duplicate keys.", "Build a verification table that preserves every applicable issue reason.", "Explain the production of analysis-ready data through reconciled counts and an audit record."],
                ["Kept the source unchanged while adding quality flags to a working copy.", "Applied named rules and retained multiple reasons on the same row.", "Separated analysis and verification records, then rechecked counts, constraints, and the audit trail."],
                "The valid and review-required records are now separated with evidence. Lesson 3.4 aggregates the analysis-ready records into indicators and priorities.",
                "Required: 3.3.1–3.3.6 / Integrated practice: 3.3.7", "3.3.1 Preserve the source and define quality problems",
                [("3.3.1 Preserve the source and define quality problems", "3.3.1 Preserve the source and define quality problems", []), ("3.3.2 Separate source, working, and analysis-ready data", "3.3.2 Separate source, working, and analysis-ready data", [("Inspect types, missingness, categories, and ranges first", None)]), ("3.3.3 Handle conversion failures and missing values", "3.3.3 Separate type conversion from missingness", [("Do not confuse missingness with zero", None)]), ("3.3.4 Normalise labels while retaining source values", "3.3.4 Normalise labels while retaining source values", []), ("3.3.5 Test ranges, cross-field constraints, and duplicates", "3.3.5 Test ranges, cross-field constraints, and duplicates", [("Use a business key to flag every duplicate row", None)]), ("3.3.6 Build verification and audit evidence", "3.3.6 Build a verification table from quality flags", [("Preserve row-level reasons in the verification report", None), ("3.3.7 Record the audit and validate again", "Record the audit and validate again"), ("Validate boundary cases before the project", None)]), ("3.3.7 Integrated practice: apply the quality workflow to another table", "3.3.8 Transfer quality rules to another dataset", [])],
                {"3.3.3": "Before replacing a blank with zero, explain whether the two values have the same operational meaning.", "3.3.6": "Why should a multi-issue row retain every applicable reason?"}, drop=["At completion you can preserve source data"]),
            "Lesson 3.4: Grouping and summary statistics": _en_page(52, "3.4", "about 3 hours",
                ["Define the grain represented by one detail row and one summary row.", "Use groupby and named aggregation for counts, totals, statistics, and conditional counts.", "Explain the numerator and denominator of a rate, the total of a proportion, and the comparison order of a ranking.", "Validate a summary against detail and again after CSV saving and re-reading."],
                ["Defined result grain before using groupby.", "Connected counts, statistics, rates, proportions, and rankings to their definitions.", "Reconciled totals and verified the saved products after re-reading them."],
                "Project 3.5A combines source inspection, quality rules, aggregation, and ranking in one operational decision using two submitted programs.",
                "Required: 3.4.1–3.4.5 / Integrated practice: 3.4.6", "3.4.1 Define the grain of detail and summary rows",
                [("3.4.1 Define the grain of detail and summary rows", "3.4.1 Define the grain of detail and summary rows", []), ("3.4.2 Group, count, and aggregate", "3.4.2 Split, count, and combine with groupby", [("Distinguish what size, count, and nunique count", None), ("Put a conditional count into named aggregation", None)]), ("3.4.3 Calculate totals, statistics, and rates", "3.4.3 Calculate summary statistics and rates", [("Calculate rates from compatible aggregate totals", None), ("Preserve comparison hierarchy with multiple keys", None)]), ("3.4.4 Build indicators used for a decision", None, [("3.4.4 Build reproducible rankings from multiple conditions", "Build a reproducible ranking from multiple conditions"), ("3.4.5 Check the denominator and 100% total of proportions", "Check the proportion denominator and 100% total")]), ("3.4.5 Reconcile, save, and re-read the result", "3.4.6 Reconcile aggregation, saving, and re-reading", [("Save purpose-specific CSVs and validate them after re-reading", None)]), ("3.4.6 Integrated practice: build and validate another summary", "3.4.7 Transfer aggregation and ranking to another decision", [])],
                {"3.4.1": "How does the meaning of one row change between the detail table and a district summary?", "3.4.4": "Why rank unrounded values and finish with a stable ID tie-break key?"}, drop=["If you can define aggregation grain"]),
        },
    }


def _en_page(cmid, number, time, outcomes, summary, next_text, route, first, groups, checks, removals=None, drop=None):
    return {"cmid": cmid, "number": number, "time": time, "intro_heading": "Introduction", "outcomes_heading": "Learning outcomes", "route": route, "route_label": "Learning route", "summary_heading": "Summary", "next_heading": "Next", "outcomes": outcomes, "summary": summary, "next": next_text, "first": first, "groups": groups, "checks": checks, "removals": removals or [], "drop_paragraphs": drop or []}


def plain_heading(fragment: str) -> str:
    return html.unescape(re.sub(r"<[^>]+>", "", fragment)).strip()


def parse_page(content: str) -> tuple[str, dict[str, str]]:
    inner = re.sub(r"^<div[^>]*>", "", content.strip(), count=1)
    inner = re.sub(r"</div>\s*$", "", inner, count=1)
    pattern = re.compile(r"<h([234])[^>]*>(.*?)</h\1>", re.I | re.S)
    matches = list(pattern.finditer(inner))
    sections = {}
    tagline = ""
    for index, match in enumerate(matches):
        title = plain_heading(match.group(2))
        body = inner[match.end() : matches[index + 1].start() if index + 1 < len(matches) else len(inner)]
        if match.group(1) == "2" and not tagline:
            tagline = title
        else:
            sections[title] = body
    return tagline, sections


def clean_body(body: str, spec: dict) -> str:
    body = re.sub(r'<p><strong>(?:Learning time|学習時間の目安)[^<]*</strong>.*?</p>', '', body, flags=re.I | re.S)
    body = re.sub(r'<p style="display:none">PYAI-[^<]+</p>', '', body)
    for value in spec.get("removals", []):
        body = body.replace(value, "")
    for prefix in spec.get("drop_paragraphs", []):
        body = re.sub(rf"<p>{re.escape(prefix)}.*?</p>", "", body, flags=re.S)
    return body.strip()


def take_intro(body: str) -> tuple[str, str]:
    match = re.match(r"\s*(<p>.*?</p>)", body, flags=re.S)
    if not match:
        raise RuntimeError("First numbered section does not start with an introduction paragraph")
    return match.group(1), body[match.end() :]


def h2(text: str) -> str:
    return f'<h2 style="{GROUP_STYLE}">{html.escape(text)}</h2>'


def render_page(old_content: str, spec: dict) -> str:
    tagline, sections = parse_page(old_content)
    first_body = clean_body(sections[spec["first"]], spec)
    intro, sections[spec["first"]] = take_intro(first_body)
    pieces = ['<div class="python-sample-lesson python-sample-lesson-v37">']
    pieces += [h2(spec["intro_heading"]), f'<p><strong>{html.escape(tagline)}</strong></p>', intro]
    pieces += [h2(spec["outcomes_heading"]), '<p>' + ("このレッスンを終えると、次のことができます。" if spec["intro_heading"] == "導入" else "At the end of this lesson, you can:") + '</p><ul>']
    pieces += [f'<li>{html.escape(item)}</li>' for item in spec["outcomes"]]
    pieces += ['</ul>', f'<aside style="{NOTE_STYLE}"><strong>{html.escape(spec["route_label"])}:</strong> {html.escape(spec["route"])}</aside>']
    for title, primary, children in spec["groups"]:
        pieces.append(h2(title))
        if primary:
            if primary not in sections:
                raise RuntimeError(f"Missing section: {primary}")
            pieces.append(clean_body(sections[primary], spec))
        for old, replacement in children:
            if old not in sections:
                raise RuntimeError(f"Missing child section: {old}")
            subtitle = replacement or re.sub(r"^3\.[1-4]\.[0-9]+\s+", "", old)
            pieces += [f'<h3>{html.escape(subtitle)}</h3>', clean_body(sections[old], spec)]
        key = title.split()[0]
        if key in spec.get("checks", {}):
            label = "確認" if spec["intro_heading"] == "導入" else "Quick check"
            pieces.append(f'<p style="{CHECK_STYLE}"><strong>{label}:</strong> {html.escape(spec["checks"][key])}</p>')
    pieces += [h2(spec["summary_heading"]), '<p>' + ("このレッスンでは、次を確認しました。" if spec["intro_heading"] == "導入" else "This lesson established the following:") + '</p><ul>']
    pieces += [f'<li>{html.escape(item)}</li>' for item in spec["summary"]]
    pieces += ['</ul>', h2(spec["next_heading"]), f'<p>{html.escape(spec["next"])}</p>', f'<p><strong>{"学習時間の目安" if spec["intro_heading"] == "導入" else "Estimated learning time"}:</strong> {html.escape(spec["time"])}</p>', '<p style="display:none">PYAI-V37-TEXTBOOK-STRUCTURE</p></div>']
    result = ''.join(pieces)
    if '<h4' in result:
        raise RuntimeError('h4 remained after hierarchy rebuild')
    return result


NOTEBOOK_CONFIG = {
    "07_tables_csv_pandas.ipynb": ("l31-en-title", "l31-en-complete", "3.1", 6),
    "08_filtering_boolean_logic.ipynb": ("l32-en-title", "l32-en-complete", "3.2", 7),
    "09_cleaning_audit_trail.ipynb": ("l33-en-title", "l33-en-complete", "3.3", 7),
    "10_grouping_statistics.ipynb": ("l34-en-title", "l34-en-complete", "3.4", 6),
    "ja/07_tables_csv_pandas.ipynb": ("l31-ja-title", "l31-ja-complete", "3.1", 6),
    "ja/08_filtering_boolean_logic.ipynb": ("l32-ja-title", "l32-ja-complete", "3.2", 7),
    "ja/09_cleaning_audit_trail.ipynb": ("l33-ja-title", "l33-ja-complete", "3.3", 7),
    "ja/10_grouping_statistics.ipynb": ("l34-ja-title", "l34-ja-complete", "3.4", 6),
}


NOTEBOOK_HEADINGS = {
    "09_cleaning_audit_trail.ipynb": {"l33-en-types": "## 3.3.3 Handle conversion failures and missing values", "l33-en-actions": "## 3.3.6 Build verification and audit evidence", "l33-en-verification": "### Preserve row-level reasons in the verification report", "l33-en-audit": "### Record the audit and validate again", "l33-en-validate": "### Reapply constraints and reconcile counts", "l33-en-transfer": "## 3.3.7 Integrated practice: apply the quality workflow to another table"},
    "ja/09_cleaning_audit_trail.ipynb": {"l33-ja-types": "## 3.3.3 型変換失敗と欠損を扱う", "l33-ja-actions": "## 3.3.6 確認対象と監査記録を残す", "l33-ja-verification": "### 問題理由を行単位の確認対象表へ残す", "l33-ja-audit": "### 監査記録とassertで再検証する", "l33-ja-validate": "### 同じ制約と件数で再検証する", "l33-ja-transfer": "## 3.3.7 統合練習：品質規則を別の表へ適用する"},
    "10_grouping_statistics.ipynb": {"l34-en-split": "## 3.4.2 Group, count, and aggregate", "l34-en-stats": "## 3.4.3 Calculate totals, statistics, and rates", "l34-en-ranking": "## 3.4.4 Build indicators used for a decision", "l34-en-multikey": "### Preserve comparison hierarchy with multiple keys", "l34-en-denominator": "### Check the proportion denominator and 100% total", "l34-en-validate": "## 3.4.5 Reconcile, save, and re-read the result", "l34-en-transfer": "## 3.4.6 Integrated practice: build and validate another summary"},
    "ja/10_grouping_statistics.ipynb": {"l34-ja-split": "## 3.4.2 groupbyで分け、数え、まとめる", "l34-ja-stats": "## 3.4.3 合計・統計量・率を計算する", "l34-ja-ranking": "## 3.4.4 判断に使う指標と順位を作る", "l34-ja-multikey": "### 複数キーで比較の階層を保つ", "l34-ja-denominator": "### 構成比の分母と100%を確認する", "l34-ja-validate": "## 3.4.5 集計結果を照合し、保存後に再確認する", "l34-ja-transfer": "## 3.4.6 統合練習：別の判断に必要な集計を作る"},
    "07_tables_csv_pandas.ipynb": {"l31-en-table": "## 3.1.1 Understand rows, columns, cells, and schema", "l31-en-csv": "## 3.1.2 Understand CSV and loading assumptions", "l31-en-inspect": "## 3.1.3 Load and inspect a DataFrame", "l31-en-derived": "## 3.1.4 Calculate columns and save a table", "l31-en-transfer": "## 3.1.6 Integrated practice: build, save, re-read, and reconcile a table"},
    "ja/07_tables_csv_pandas.ipynb": {"l31-ja-table": "## 3.1.1 行・列・セルとスキーマを理解する", "l31-ja-csv": "## 3.1.2 CSVと読込条件を理解する", "l31-ja-inspect": "## 3.1.3 DataFrameを読み込み、内容を確認する", "l31-ja-derived": "## 3.1.4 計算列を作り、表を保存する", "l31-ja-transfer": "## 3.1.6 統合練習：表を作り、保存し、再確認する"},
    "08_filtering_boolean_logic.ipynb": {"l32-en-loc": "## 3.2.2 Select rows and columns with labels and positions", "l32-en-result": "## 3.2.6 Filter, order, and verify the result", "l32-en-transfer": "## 3.2.7 Integrated practice: translate and verify another question"},
    "ja/08_filtering_boolean_logic.ipynb": {"l32-ja-result": "## 3.2.6 抽出し、並べ、件数を検証する", "l32-ja-transfer": "## 3.2.7 統合練習：別の問いを条件へ翻訳する"},
}


def replace_heading(cell: dict, heading: str) -> None:
    lines = ''.join(cell.get('source', [])).splitlines(keepends=True)
    if not lines or not lines[0].startswith('#'):
        raise RuntimeError(f"No heading in {cell.get('id')}")
    lines[0] = heading + ('\n' if lines[0].endswith('\n') else '')
    cell['source'] = lines


def notebook_overview(spec: dict) -> str:
    ja = spec['intro_heading'] == '導入'
    outcomes = '\n'.join(f'- {item}' for item in spec['outcomes'])
    return f"## {'導入' if ja else 'Introduction'}\n\n{spec['next'] if False else ('このNotebookでは、Moodle本文の概念を実際のデータとコードで確かめます。' if ja else 'Use this Notebook to verify the lesson concepts with actual data and code.')}\n\n## {'このレッスンの到達目標' if ja else 'Learning outcomes'}\n\n{outcomes}\n\n> **{spec['route_label']}:** {spec['route']}\n"


def notebook_closing(spec: dict) -> str:
    ja = spec['intro_heading'] == '導入'
    bullets = '\n'.join(f'- {item}' for item in spec['summary'])
    return f"## {'まとめ' if ja else 'Summary'}\n\n{bullets}\n\n## {spec['next_heading']}\n\n{spec['next']}\n\n**{'学習時間の目安' if ja else 'Estimated learning time'}:** {spec['time']}\n"


def update_notebooks(all_specs: dict) -> None:
    for relative, (title_id, complete_id, number, expected) in NOTEBOOK_CONFIG.items():
        path = TEMPLATES / relative
        document = json.loads(path.read_text(encoding='utf-8'))
        cells = document['cells']
        by_id = {cell.get('id'): cell for cell in cells}
        language = 'ja' if relative.startswith('ja/') else 'en'
        page_spec = next(value for value in all_specs[language]['pages'].values() if value['number'] == number)
        overview_id = f"chapter3-{language}-{number.replace('.', '')}-overview-v37"
        cells[:] = [cell for cell in cells if cell.get('id') != overview_id]
        title_index = next(i for i, cell in enumerate(cells) if cell.get('id') == title_id)
        cells.insert(title_index + 1, {'cell_type': 'markdown', 'id': overview_id, 'metadata': {}, 'source': notebook_overview(page_spec).splitlines(keepends=True)})
        if number == '3.4':
            prefix = 'l34-ja' if language == 'ja' else 'l34-en'
            moving_ids = {f'{prefix}-multikey', f'{prefix}-multikey-code'}
            moving = [cell for cell in cells if cell.get('id') in moving_ids]
            if len(moving) != 2:
                raise RuntimeError(f'{relative}: missing multi-key cell pair')
            cells[:] = [cell for cell in cells if cell.get('id') not in moving_ids]
            ranking_index = next(i for i, cell in enumerate(cells) if cell.get('id') == f'{prefix}-ranking')
            for offset, cell in enumerate(moving):
                cells.insert(ranking_index + offset, cell)
        by_id = {cell.get('id'): cell for cell in cells}
        for cell_id, heading in NOTEBOOK_HEADINGS.get(relative, {}).items():
            replace_heading(by_id[cell_id], heading)
        by_id[complete_id]['source'] = notebook_closing(page_spec).splitlines(keepends=True)
        document.setdefault('metadata', {}).setdefault('pyai', {})['structure_revision'] = 37
        path.write_text(json.dumps(document, ensure_ascii=False, indent=1) + '\n', encoding='utf-8', newline='\n')
        headings = [''.join(c.get('source', [])).splitlines()[0] for c in cells if c['cell_type'] == 'markdown' and ''.join(c.get('source', [])).startswith(f'## {number}.')]
        if len(headings) != expected:
            raise RuntimeError(f"{relative}: expected {expected} numbered groups, found {headings}")


def write_php(pages: dict) -> None:
    encoded = json.dumps(pages, ensure_ascii=False, separators=(',', ':'))
    upgrade = rf'''<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$all = json_decode(<<<'JSON'
{encoded}
JSON, true, 512, JSON_THROW_ON_ERROR);
foreach ($all[$shortname] as $name => $spec) {{
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
    if ((int)$cm->id !== (int)$spec['cmid']) throw new RuntimeException("$shortname $name CMID changed");
    if (!str_contains($page->content, 'PYAI-V37-TEXTBOOK-STRUCTURE')) {{
        if (!str_contains($page->content, 'PYAI-V36-CHAPTER3-TOPICS')) throw new RuntimeException("$shortname $name missing v36 source marker");
        $page->content = $spec['content'];
        $page->timemodified = time();
        $DB->update_record('page', $page);
    }}
    echo json_encode(['course'=>$shortname,'cmid'=>(int)$cm->id,'page'=>$name], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), PHP_EOL;
}}
rebuild_course_cache($course->id, true);
'''
    UPGRADE.write_text(upgrade, encoding='utf-8', newline='\n')
    verify = '''<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
$courses = [
 'PYAI-INTRO' => [47=>['Lesson 3.1: Tabular data, CSV, and pandas',6],49=>['Lesson 3.2: Data selection, filtering, and Boolean logic',7],50=>['Lesson 3.3: Data cleaning and audit records',7],52=>['Lesson 3.4: Grouping and summary statistics',6]],
 'PYAI-INTRO-JA' => [193=>['レッスン3.1：表形式データ・CSV・pandas',6],195=>['レッスン3.2：データの選択・抽出とブール論理',7],196=>['レッスン3.3：データのクリーニングと監査記録',7],198=>['レッスン3.4：グループ化と要約統計',6]],
];
$result=[];
foreach($courses as $shortname=>$pages){
 $course=$DB->get_record('course',['shortname'=>$shortname],'*',MUST_EXIST);
 foreach($pages as $cmid=>$expected){
  $cm=get_coursemodule_from_id('page',$cmid,$course->id,false,MUST_EXIST);
  $page=$DB->get_record('page',['id'=>$cm->instance],'*',MUST_EXIST);
  if($page->name!==$expected[0])throw new RuntimeException("$shortname CMID $cmid name");
  foreach(['PYAI-V37-TEXTBOOK-STRUCTURE','Learning outcomes','Summary'] as $token){
   $local=$shortname==='PYAI-INTRO-JA' ? ['Learning outcomes'=>'このレッスンの到達目標','Summary'=>'まとめ'][$token]??$token : $token;
   if(!str_contains($page->content,$local))throw new RuntimeException("$shortname $cmid missing $local");
  }
  if(substr_count($page->content,'<h4')!==0)throw new RuntimeException("$shortname $cmid h4");
  $groups=preg_match_all('/<h2[^>]*>3\\.[1-4]\\.[0-9]+ /u',$page->content);
  if($groups!==$expected[1])throw new RuntimeException("$shortname $cmid groups $groups");
  $result[]=['course'=>$shortname,'cmid'=>$cmid,'groups'=>$groups,'h3'=>substr_count($page->content,'<h3>')];
 }
 $quiznames=$shortname==='PYAI-INTRO-JA'
  ? ['理解度チェック：3.1 表形式データ・CSV・pandas','理解度チェック：3.2 データの選択・抽出とブール論理','理解度チェック：3.3 データのクリーニングと監査記録','理解度チェック：3.4 グループ化と要約統計']
  : ['Knowledge check: 3.1 Tabular data, CSV, and pandas','Knowledge check: 3.2 Data selection, filtering, and Boolean logic','Knowledge check: 3.3 Data cleaning and audit records','Knowledge check: 3.4 Grouping and summary statistics'];
 foreach($quiznames as $name){$quiz=$DB->get_record('quiz',['course'=>$course->id,'name'=>$name],'*',MUST_EXIST);if($DB->count_records('quiz_slots',['quizid'=>$quiz->id])!==10)throw new RuntimeException("$name slots");}
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
'''
    VERIFY.write_text(verify, encoding='utf-8', newline='\n')
    apply = '''#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root_dir"
for shortname in PYAI-INTRO PYAI-INTRO-JA; do
  docker compose -f docker-compose.local.yml exec -T -e PYTHON_COURSE_SHORTNAME="$shortname" moodle runuser -u www-data -- php < scripts/upgrade-python-chapter3-structure-v37.php
done
docker compose -f docker-compose.local.yml exec -T moodle runuser -u www-data -- php < scripts/verify-python-chapter3-structure-v37.php
'''
    APPLY.write_text(apply, encoding='utf-8', newline='\n')


def main() -> None:
    specs = lesson_specs()
    specs['en'] = english_from_japanese(specs['ja'])
    pages = {}
    for language, lang_spec in specs.items():
        data = json.loads((AUDITS / lang_spec['source']).read_text(encoding='utf-8'))
        current = {item['name']: item for item in data['activities'] if item['modname'] == 'page'}
        pages[lang_spec['shortname']] = {}
        for name, page_spec in lang_spec['pages'].items():
            item = current[name]
            pages[lang_spec['shortname']][name] = {'cmid': page_spec['cmid'], 'content': render_page(item['content'], page_spec)}
    update_notebooks(specs)
    write_php(pages)
    print(json.dumps({'pages': sum(len(x) for x in pages.values()), 'notebooks': len(NOTEBOOK_CONFIG), 'revision': 37}, ensure_ascii=False))


if __name__ == '__main__':
    main()
