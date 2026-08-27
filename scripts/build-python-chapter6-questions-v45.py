from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "sample-content" / "introduction-to-python" / "chapter6-questions-v45"


EN = {
    "61": [
        ("sample", "What does `pd.read_csv(path, nrows=5)` establish?", ["The final whole-file ranking", "The first five rows for planning the read", "That the file has exactly five rows", "That no later row is invalid"], 1, "A sample helps inspect structure; it cannot prove whole-file results."),
        ("usecols", "The decision uses only district, medicine, stockout_hours, and patients_turned_away. Which read best records that boundary?", ["Read every column", "Use `usecols` for those four columns", "Convert the CSV to an image", "Read the file twice"], 1, "usecols limits memory and makes the data requirement explicit."),
        ("types", "Why can a numeric CSV column be inferred as object?", ["A later text value can prevent one numeric type", "CSV always stores Python objects", "The file is too short", "groupby changes the source"], 0, "CSV has no stored types; values drive inference."),
        ("coerce", "What does `pd.to_numeric(values, errors='coerce')` do to an invalid number?", ["Deletes the whole file", "Turns it into missing data", "Makes it zero", "Raises every time"], 1, "Coercion exposes failed conversions as missing for review."),
        ("memory", "Which call includes memory used by referenced strings?", ["df.memory_usage(deep=True).sum()", "len(df.columns)", "df.head()", "df.index"], 0, "deep=True gives a more realistic estimate for object/string data."),
        ("chunksize", "With `chunksize=10000`, what does read_csv return?", ["One ten-thousand-column table", "Successive DataFrames", "Only row 10000", "A chart"], 1, "The iterator yields manageable DataFrame chunks."),
        ("count", "Chunk lengths are 10, 10, 10, 10, and 8. What should the processed count be?", ["10", "40", "48", "50"], 2, "Processed rows are the sum of all chunk lengths."),
        ("privacy", "A question does not require patient identifiers. What is the best reading plan?", ["Read them in case they become interesting", "Exclude them from usecols", "Publish them separately", "Convert them to integers"], 1, "Do not collect or load fields the stated purpose does not require."),
        ("samplelimit", "Why must missingness not be concluded from `head(5)`?", ["head changes values", "Problems may occur after those rows", "Five rows use more memory", "Missingness applies only to charts"], 1, "A leading sample is not evidence about every later record."),
        ("plan", "What should be known before a full large-file read?", ["Only chart colour", "File size, required columns, record meaning, and types", "The final recommendation", "Every learner's computer model"], 1, "These checks define a proportional and reproducible reading plan."),
    ],
    "62": [
        ("state", "What should survive after a processed chunk is released?", ["Every source row", "Only totals and counts needed by final metrics", "A screenshot", "The pandas documentation"], 1, "Small aggregation state replaces retention of all detail."),
        ("key", "Which key preserves district-medicine grain?", ["district only", "medicine only", "(district, medicine)", "chunk number"], 2, "Both grouping fields must be present in the state key."),
        ("add", "A group appears in two chunks. What should update_totals do?", ["Keep only the later chunk", "Add compatible totals", "Average the row labels", "Reject the group"], 1, "Chunk boundaries must not split the final group result."),
        ("average", "Chunk A has rate 100% for 1 row; B has 0% for 9 rows. What is the whole rate?", ["50%", "10%", "90%", "0%"], 1, "Merge one event over ten rows, not the two chunk percentages."),
        ("stockday", "Which expression creates a 1 for a positive stockout and 0 otherwise?", ["df.stockout_hours.gt(0).astype(int)", "df.stockout_hours.mean()", "len(df)", "df.sort_values()"], 0, "The Boolean condition becomes an additive event count."),
        ("review", "Why count review rows in every chunk?", ["To make the chart colourful", "To account for rows excluded from analysis", "To increase the priority", "To replace validation"], 1, "Rejected rows remain part of source reconciliation."),
        ("boundary", "What should happen when chunksize changes from 997 to 2048?", ["The final summary stays the same", "Every total doubles", "Groups disappear", "The source is rewritten"], 0, "Chunk size changes boundaries, not the mathematical result."),
        ("replace", "What bug occurs if repeated group state is assigned rather than incremented?", ["Only the last partial total remains", "The file gets more columns", "pandas changes language", "Nothing"], 0, "Assignment discards earlier chunks for that key."),
        ("ratewhen", "When should stockout_rate be calculated?", ["Before reading any row", "After merged stockout_days and clinic_days are known", "Separately then unweighted averaged", "From filenames"], 1, "The whole numerator and denominator must be available."),
        ("grain", "The final table has one row per district and medicine. What controls its maximum size?", ["Source row count only", "Number of distinct district-medicine pairs", "Chunk size", "PNG resolution"], 1, "Decision state scales with group cardinality."),
    ],
    "63": [
        ("fixture", "Why use a 48-row fixture before 120,000 rows?", ["To hard-code its answer", "To inspect and manually verify the same rules", "To avoid testing", "To replace the full run"], 1, "A known small case makes logic checkable before scaling."),
        ("reconcile", "Source=120000, analysis=119977, review=23. Is the run reconciled?", ["Yes", "No", "Only if chunksize is 23", "Only after plotting"], 0, "119977 + 23 equals 120000."),
        ("missing", "Source=100, analysis=96, review=3. What does reconciliation reveal?", ["One row is unexplained", "All rows are explained", "The chart is wrong", "There are 96 columns"], 0, "The accounted counts total only 99."),
        ("provenance", "Which set is most useful for reproduction?", ["Filename, generation method, rows, columns, chunk size, quality rules", "Only chart colour", "Only final rank", "Only Python version"], 0, "A result must remain connected to its source and processing conditions."),
        ("reopen", "Why reopen a saved summary CSV?", ["To check the actual shared file's columns and rows", "To make the source larger", "To remove validation", "To change chunk boundaries"], 0, "Serialization can change index, types, names, and rounding."),
        ("source", "What should happen to the generated source during analysis?", ["It is overwritten with the summary", "It remains unchanged", "It is sorted in place", "It becomes the PNG"], 1, "Outputs belong in a separate location."),
        ("independent", "Two chunk sizes give equal summaries. What does this support?", ["Boundary-independent merge logic", "Causal explanation", "Perfect source accuracy", "No need for row reconciliation"], 0, "It tests one important property, not every possible error."),
        ("limits", "A run reconciles. What does that NOT prove by itself?", ["Every row was accounted for", "Every quality rule and calculation is correct", "Analysis plus review equals source", "No rows silently disappeared"], 1, "Reconciliation is necessary control evidence, not complete proof."),
        ("contract", "Why validate required columns before processing?", ["To fail clearly when the input contract is broken", "To pick a chart colour", "To increase row count", "To average chunk rates"], 0, "A missing field should produce an explicit, early failure."),
        ("workflow", "Which order is strongest?", ["Full run, guess, then fixture", "Fixture, full run, reconcile, reopen outputs, checker", "Chart, delete source, run", "Checker only"], 1, "The order moves from understandable evidence to scale and independent verification."),
    ],
}


JA = {
    "61": [
        ("sample", "`pd.read_csv(path, nrows=5)`で確認できることは何ですか。", ["全件の最終順位", "読み込み計画に使う先頭5行", "ファイルが必ず5行であること", "後続行に不正値がないこと"], 1, "標本は構造確認用で、全件の結論にはなりません。"),
        ("usecols", "地区・医薬品・在庫切れ時間・患者受入不能数だけを使います。適切な読み込みはどれですか。", ["全列を読む", "4列をusecolsで指定する", "CSVを画像にする", "同じファイルを2回読む"], 1, "usecolsはメモリと分析範囲を限定します。"),
        ("types", "数値列がobjectと推測されることがある理由は何ですか。", ["途中の文字列が一つの数値型を妨げる", "CSVは常にPython objectを保存する", "行数が少ない", "groupbyが原本を変更する"], 0, "CSVに型情報はなく、値から推測されます。"),
        ("coerce", "`pd.to_numeric(values, errors='coerce')`は不正な数値をどうしますか。", ["ファイル全体を削除する", "欠損値にする", "0にする", "必ず例外にする"], 1, "変換失敗を欠損として要確認にできます。"),
        ("memory", "文字列が参照する領域も含めてメモリを測る呼び出しはどれですか。", ["df.memory_usage(deep=True).sum()", "len(df.columns)", "df.head()", "df.index"], 0, "deep=Trueでより現実的な使用量になります。"),
        ("chunksize", "`chunksize=10000`を指定したread_csvが返すものは何ですか。", ["1万列の表", "順番に得られるDataFrame", "10000行目だけ", "グラフ"], 1, "反復すると小さなDataFrameが順に得られます。"),
        ("count", "チャンク長が10、10、10、10、8なら処理件数はいくつですか。", ["10", "40", "48", "50"], 2, "全チャンクの行数を合計します。"),
        ("privacy", "問いに患者識別子が不要なときの適切な計画はどれですか。", ["念のため読む", "usecolsから除外する", "別に公開する", "整数へ変換する"], 1, "利用目的に不要な列を読み込みません。"),
        ("samplelimit", "head(5)だけで全件の欠損を判断できない理由は何ですか。", ["headが値を変更する", "6行目以降に問題があり得る", "5行はメモリを使いすぎる", "欠損は図だけの概念"], 1, "先頭標本は後続行の証拠ではありません。"),
        ("plan", "大規模ファイルを全件処理する前に必要な確認はどれですか。", ["図の色だけ", "容量・必要列・一行の意味・型", "最終提言", "全受講者のPC機種"], 1, "比例的で再現可能な読み込み計画を作ります。"),
    ],
    "62": [
        ("state", "処理済みチャンクを解放した後も残すべきものは何ですか。", ["全原本行", "最終指標に必要な合計と件数", "スクリーンショット", "pandas文書"], 1, "小さな集計状態を残します。"),
        ("key", "地区・医薬品の粒度を保つキーはどれですか。", ["地区だけ", "医薬品だけ", "(地区, 医薬品)", "チャンク番号"], 2, "二つのグループ列をキーに含めます。"),
        ("add", "同じグループが二つのチャンクに現れたときの処理はどれですか。", ["後の値だけ残す", "加算可能な合計を足す", "行名を平均する", "グループを除外する"], 1, "境界でグループ集計を分断しません。"),
        ("average", "Aは1件中100%、Bは9件中0%です。全体率はいくつですか。", ["50%", "10%", "90%", "0%"], 1, "10件中1件として計算します。"),
        ("stockday", "在庫切れ時間が正なら1、それ以外は0を作る式はどれですか。", ["df.stockout_hours.gt(0).astype(int)", "df.stockout_hours.mean()", "len(df)", "df.sort_values()"], 0, "真偽値を加算可能な件数へ変換します。"),
        ("review", "各チャンクで要確認行も数える理由は何ですか。", ["図を彩るため", "分析から除外した行を説明するため", "順位を上げるため", "検証を省くため"], 1, "除外行も原本照合に含まれます。"),
        ("boundary", "chunksizeを997から2048へ変えたとき期待する結果はどれですか。", ["最終要約は同じ", "全合計が倍になる", "グループが消える", "原本が書き換わる"], 0, "境界は変わっても数学的結果は変わりません。"),
        ("replace", "繰り返し現れるグループを加算せず代入すると何が起きますか。", ["最後の部分集計だけ残る", "列が増える", "言語が変わる", "何も起きない"], 0, "前のチャンクの値を失います。"),
        ("ratewhen", "stockout_rateを計算する時点はいつですか。", ["一行も読む前", "全チャンクの分子と分母を統合した後", "各チャンク率を単純平均した後", "ファイル名から"], 1, "全体の分子と分母が必要です。"),
        ("grain", "最終表が地区・医薬品ごとに一行なら、最大行数を決めるものは何ですか。", ["原本行数だけ", "異なる地区・医薬品の組合せ数", "チャンクサイズ", "PNG解像度"], 1, "状態はグループの種類数に応じて増えます。"),
    ],
    "63": [
        ("fixture", "12万件の前に48件fixtureを使う理由は何ですか。", ["答えを直接書くため", "同じ規則を目視・手計算で確認するため", "テストを避けるため", "大規模実行をなくすため"], 1, "小さな既知例でロジックを確認します。"),
        ("reconcile", "原本120000、分析119977、要確認23は照合していますか。", ["はい", "いいえ", "chunksizeが23のときだけ", "図を作った後だけ"], 0, "119977+23=120000です。"),
        ("missing", "原本100、分析96、要確認3から分かることは何ですか。", ["1行が説明されていない", "全件説明済み", "図が誤り", "96列ある"], 0, "説明済みは99行だけです。"),
        ("provenance", "再現に最も役立つ記録はどれですか。", ["ファイル名・生成方法・行列・チャンクサイズ・品質規則", "図の色だけ", "最終順位だけ", "Python版だけ"], 0, "結果を入力と処理条件へ結び付けます。"),
        ("reopen", "保存した要約CSVを再読込する理由は何ですか。", ["共有する実ファイルの列と行を確認する", "原本を大きくする", "検証を削る", "境界を変える"], 0, "保存でインデックス、型、名前、丸めが変わり得ます。"),
        ("source", "分析中の生成原本はどうしますか。", ["要約で上書きする", "変更しない", "その場で並べ替える", "PNGへ変える"], 1, "出力は別の場所へ保存します。"),
        ("independent", "二つのチャンクサイズで要約が一致することが支持するものは何ですか。", ["境界に依存しない統合", "因果関係", "原本の完全な正確性", "照合が不要なこと"], 0, "重要な性質一つを検査します。"),
        ("limits", "件数が照合していても、それだけでは保証しないものは何ですか。", ["全行を数えたこと", "すべての品質規則と計算が正しいこと", "分析+要確認=原本", "行が消えていないこと"], 1, "照合は必要ですが完全な証明ではありません。"),
        ("contract", "処理前に必須列を検証する理由は何ですか。", ["入力契約の破損を明確に早く失敗させる", "図の色を決める", "行数を増やす", "率を平均する"], 0, "不足列を曖昧な後続エラーにしません。"),
        ("workflow", "最も確かな作業順はどれですか。", ["全件実行、推測、fixture", "fixture、全件、照合、保存再確認、自動確認", "作図、原本削除、実行", "自動確認だけ"], 1, "理解可能な小規模確認から独立検査へ進みます。"),
    ],
}


def build() -> None:
    for language, bank in [("en", EN), ("ja", JA)]:
        directory = OUT / language
        directory.mkdir(parents=True, exist_ok=True)
        for lesson, items in bank.items():
            records = [
                {"id": qid, "p": prompt, "c": choices, "ok": correct, "why": why}
                for qid, prompt, choices, correct, why in items
            ]
            (directory / f"{lesson}.json").write_text(
                json.dumps(records, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
            )
    print("built six Chapter 6 question files")


if __name__ == "__main__":
    build()
