from integrated_practice_common_v46 import lesson


PRACTICES = [
lesson("3.1", "Lesson 3.1: Tabular data, CSV, and pandas", "レッスン3.1：表形式データ・CSV・pandas",
 ("Use a four-row community-workshop table to rehearse the first stage of the intermediate project: inspect before transforming.",
  "Read the CSV text into a DataFrame. Display shape, columns, dtypes, every row, and a view sorted by `workshop_id,date` without changing the original order.",
  ["One row represents one workshop on one date.", "The original and sorted view both contain four rows.", "The original index order remains `[0, 1, 2, 3]`."],
  ["Use `io.StringIO` so no external file is needed.", "Call `sort_values` and `reset_index(drop=True)` for the view.", "Do not assign the sorted result back to `records`."],
  "Inspection establishes row meaning, columns, types, and ordering before analysis. A derived view need not mutate the source DataFrame.",
  "Reverse the CSV row order. The source display changes, but the sorted view must remain in workshop/date order."),
 ("4行の地域講習会表を使い、中間課題の最初の段階である『加工前の確認』を練習します。",
  "CSV文字列をDataFrameへ読み、形、列名、型、全行、`workshop_id,date`順の表示用表を出します。元の順序は変えません。",
  ["一行は一つの講習会の一日分です。", "原表と並べ替え表はどちらも4行です。", "元のindex順は`[0, 1, 2, 3]`のままです。"],
  ["外部ファイルを不要にするため`io.StringIO`を使います。", "表示用表は`sort_values`と`reset_index(drop=True)`で作ります。", "並べ替え結果を`records`へ代入しません。"],
  "確認により、分析前に一行の意味、列、型、順序を把握します。派生表示のために原表を変更する必要はありません。",
  "CSVの行順を逆にします。原表の表示は変わりますが、並べ替え表は同じ講習会・日付順になることを確認します。"),
 '''from io import StringIO
import pandas as pd

csv_text = """workshop_id,date,district,registered,attended
W02,2026-09-02,east,18,15
W01,2026-09-02,North,20,18
W02,2026-09-01, east ,16,14
W01,2026-09-01,North,19,17
"""
records = pd.read_csv(StringIO(csv_text))
print("SHAPE:", records.shape)
print("COLUMNS:", records.columns.tolist())
print(records.dtypes)
print(records.to_string(index=False))
view = records.sort_values(["workshop_id", "date"]).reset_index(drop=True)
print(view.to_string(index=False))
assert records.index.tolist() == [0, 1, 2, 3]''', "SHAPE: (4, 5)"),

lesson("3.2", "Lesson 3.2: Data selection, filtering, and Boolean logic", "レッスン3.2：データの選択・抽出とブール論理",
 ("Use market-stall inspections to practise selecting evidence with explicit Boolean conditions.",
  "From five records, select stalls where `temperature > 8` and `food_type == 'cold'`. Show only ID, temperature, and food type; keep the original unchanged.",
  ["Build each condition separately, then combine them with `&`.", "Use parentheses around both comparisons.", "The result contains S02 and S05 only."],
  ["Inspect the two Boolean Series before combining them.", "Use `.loc[mask, columns]`.", "Do not use Python `and` between Series."],
  "A filter is a documented inclusion rule. Separate masks make the logic inspectable and reduce precedence mistakes.",
  "Change the rule to temperature at least 8. Predict whether S03 enters before running."),
 ("市場の食品売場点検を題材に、明示的なブール条件で根拠を抽出します。",
  "5件から、`temperature > 8`かつ`food_type == 'cold'`の売場を選び、ID、温度、食品種だけ表示します。元表は変更しません。",
  ["各条件を別々に作り、`&`で結合します。", "二つの比較を括弧で囲みます。", "結果はS02とS05だけです。"],
  ["結合前に二つのBoolean Seriesを確認します。", "`.loc[mask, columns]`を使います。", "Series同士にPythonの`and`は使いません。"],
  "フィルターは採用規則をコード化したものです。マスクを分けると論理を確認でき、優先順位の誤りを減らせます。",
  "条件を8度以上へ変えます。実行前にS03が入るか予想します。"),
 '''import pandas as pd
records = pd.DataFrame({
    "stall_id": ["S01", "S02", "S03", "S04", "S05"],
    "temperature": [5, 11, 8, 12, 9],
    "food_type": ["cold", "cold", "cold", "hot", "cold"],
})
too_warm = records["temperature"] > 8
cold_food = records["food_type"] == "cold"
review = records.loc[too_warm & cold_food, ["stall_id", "temperature", "food_type"]]
print(review.to_string(index=False))
assert review["stall_id"].tolist() == ["S02", "S05"]''', "S02 and S05"),

lesson("3.3", "Lesson 3.3: Data cleaning and audit records", "レッスン3.3：データのクリーニングと監査記録",
 ("Use a tiny water-container log to rehearse explicit quality flags and an audit trail without repairing the source silently.",
  "Convert `litres` with `errors='coerce'`. Flag missing numbers, negative numbers, and duplicate `container_id,date` groups with `keep=False`; create separate valid and review tables.",
  ["Preserve a deep copy of the original.", "One row may carry more than one flag.", "Every source row appears in exactly one output table."],
  ["Use `isna()`, `< 0`, and `duplicated(..., keep=False)`.", "Combine flags with `|` for the review mask.", "Reconcile lengths after splitting."],
  "Cleaning is a rule-driven classification, not a silent rewrite. Separate flags explain why each row needs review.",
  "Add a row that is both negative and duplicated. Confirm both flags are true and reconciliation still holds."),
 ("小さな給水容器記録を使い、原本を黙って直さず、品質フラグと監査経路を作ります。",
  "`litres`を`errors='coerce'`で変換します。数値欠損、負数、`container_id,date`重複全行をフラグ化し、有効表と要確認表へ分けてください。",
  ["原表のdeep copyを保持します。", "一行が複数フラグを持って構いません。", "全原本行が必ず一方の出力へ入ります。"],
  ["`isna()`、`< 0`、`duplicated(..., keep=False)`を使います。", "要確認マスクは`|`で結合します。", "分割後に行数を照合します。"],
  "クリーニングは規則に基づく分類であり、黙った書換えではありません。別々のフラグが理由を残します。",
  "負数かつ重複となる一行を追加し、両フラグと件数照合を確認します。"),
 '''import pandas as pd
records = pd.DataFrame({
    "container_id": ["C01", "C02", "C02", "C03"],
    "date": ["2026-09-01", "2026-09-01", "2026-09-01", "2026-09-02"],
    "litres": [20, "", 18, -2],
})
original = records.copy(deep=True)
working = records.copy(deep=True)
working["litres"] = pd.to_numeric(working["litres"], errors="coerce")
working["missing_number"] = working["litres"].isna()
working["negative_number"] = working["litres"].lt(0).fillna(False)
working["duplicate_record"] = working.duplicated(["container_id", "date"], keep=False)
review_mask = working[["missing_number", "negative_number", "duplicate_record"]].any(axis=1)
valid = working.loc[~review_mask].copy()
review = working.loc[review_mask].copy()
assert len(records) == len(valid) + len(review)
assert records.equals(original)
print("VALID:", len(valid), "REVIEW:", len(review))''', "VALID: 1 REVIEW: 3"),

lesson("3.4", "Lesson 3.4: Grouping and summary statistics", "レッスン3.4：グループ化と要約統計",
 ("Use bicycle-repair jobs to rehearse grouping, derived indicators, deterministic ranking, and reconciliation.",
  "Group six valid jobs by mechanic. Calculate job count, total minutes, and mean minutes; rank highest mean first and break ties by mechanic ID.",
  ["Use named aggregation.", "Rank with the unrounded mean and round only for display.", "Job counts and total minutes must reconcile with the source."],
  ["Use `groupby('mechanic_id', as_index=False).agg(...)`.", "Sort by mean descending and ID ascending.", "Compare grouped sums with source sums."],
  "Grouping changes row meaning from one job to one mechanic. Reconciliation proves the summary still accounts for its source.",
  "Add one 60-minute job for M02. Predict the new first mechanic before running."),
 ("自転車修理を題材に、グループ化、導出指標、決定的な順位、照合を練習します。",
  "有効な6件を整備担当者別にまとめ、件数、合計分、平均分を求めます。平均の降順、同率は担当者ID昇順で並べてください。",
  ["名前付き集計を使います。", "順位は丸め前の平均で決め、表示時だけ丸めます。", "件数と合計分を原明細と照合します。"],
  ["`groupby('mechanic_id', as_index=False).agg(...)`を使います。", "平均降順、ID昇順で並べます。", "集計表の合計と原表の合計を比較します。"],
  "一行の意味が一修理から一担当者へ変わります。照合により、集計が原明細を説明できることを確かめます。",
  "M02へ60分の修理を一件追加します。実行前に新しい1位を予想します。"),
 '''import pandas as pd
jobs = pd.DataFrame({
    "job_id": ["J1", "J2", "J3", "J4", "J5", "J6"],
    "mechanic_id": ["M01", "M02", "M01", "M03", "M02", "M03"],
    "minutes": [30, 45, 50, 20, 35, 40],
})
summary = jobs.groupby("mechanic_id", as_index=False).agg(
    job_count=("job_id", "size"),
    total_minutes=("minutes", "sum"),
    mean_minutes=("minutes", "mean"),
)
summary = summary.sort_values(["mean_minutes", "mechanic_id"], ascending=[False, True])
assert summary["job_count"].sum() == len(jobs)
assert summary["total_minutes"].sum() == jobs["minutes"].sum()
print(summary.round({"mean_minutes": 1}).to_string(index=False))''', "FIRST: M01"),
]
