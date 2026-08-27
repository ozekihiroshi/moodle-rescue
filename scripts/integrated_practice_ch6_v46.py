from integrated_practice_common_v46 import lesson


PRACTICES = [
lesson("6.1", "Lesson 6.1: Inspect before loading", "レッスン6.1：読み込む前に調べる",
 ("The chapter project scales clinic-stock data. This lighter rehearsal plans a selective read of solar-maintenance logs.",
  "Create a 12-row CSV with six columns. Inspect bytes and two rows, then read only `site_id,date,downtime_hours` and compare deep memory use with the full DataFrame.",
  ["The conclusion is based on all 12 rows, not the two-row sample.", "Use `nrows`, `usecols`, and `memory_usage(deep=True)`.", "Confirm selected columns are exactly the three requested."],
  ["Generate the tiny CSV in the code.", "Use the sample only to inspect names and values.", "Measure both DataFrames with the same method."],
  "Sampling plans the load; selective reading expresses the data boundary. Neither substitutes for processing every required row.",
  "Add a long free-text note column. Predict which load’s memory grows most, then measure it."),
 ("章末では診療所在庫データを拡大処理します。この軽い練習では太陽光設備保守ログの選択読込を計画します。",
  "6列12行のCSVを作り、バイト数と2行を確認します。次に`site_id,date,downtime_hours`だけ読み、全列DataFrameとのdeep memoryを比較してください。",
  ["結論は2行標本ではなく12行全件に基づきます。", "`nrows`、`usecols`、`memory_usage(deep=True)`を使います。", "選択列が指定の3列だけか確認します。"],
  ["小さなCSVはコード内で生成します。", "標本は列名と値の形の確認だけに使います。", "二つのDataFrameを同じ方法で測ります。"],
  "標本は読込計画を立て、選択読込はデータ境界を表します。どちらも必要全行の処理の代わりではありません。",
  "長い自由記述列を追加し、どちらの読込のメモリが大きく増えるか予想して測ります。"),
 '''from pathlib import Path
import pandas as pd
source = Path("solar_maintenance.csv")
pd.DataFrame({
    "site_id": [f"S{i % 3 + 1}" for i in range(12)],
    "date": [f"2026-09-{i + 1:02d}" for i in range(12)],
    "downtime_hours": [i % 5 for i in range(12)],
    "technician": ["T1", "T2"] * 6,
    "weather": ["clear", "rain", "cloud"] * 4,
    "note": ["routine check"] * 12,
}).to_csv(source, index=False)
print("BYTES:", source.stat().st_size)
print(pd.read_csv(source, nrows=2))
full = pd.read_csv(source)
selected = pd.read_csv(source, usecols=["site_id", "date", "downtime_hours"])
print("FULL MEMORY:", full.memory_usage(deep=True).sum())
print("SELECTED MEMORY:", selected.memory_usage(deep=True).sum())
assert selected.columns.tolist() == ["site_id", "date", "downtime_hours"]''', "Selected memory is lower than full memory"),

lesson("6.2", "Lesson 6.2: Aggregate correctly across chunks", "レッスン6.2：チャンクを越えて正しく集計する",
 ("Use warehouse dispatches to rehearse preserving additive state across chunk boundaries.",
  "Process a 12-row CSV in chunks of 4. For each destination, accumulate dispatch count and package total, then calculate packages per dispatch after all chunks.",
  ["Keep only grouped totals between chunks.", "Do not average chunk averages.", "Reconcile source rows with accumulated dispatch counts."],
  ["Group each chunk by destination.", "Add partial counts and package sums into a dictionary.", "Divide total packages by total dispatches at the end."],
  "Counts and sums are additive; averages are derived only after their numerator and denominator have been combined.",
  "Run with chunk sizes 3 and 5. The final table and reconciled row count must be identical."),
 ("倉庫からの発送を題材に、チャンク境界を越えて加算可能な状態を保持します。",
  "12行CSVを4行ずつ処理し、配送先別の発送件数と荷物合計を累積します。全チャンク後に一発送当たり荷物数を求めてください。",
  ["チャンク間ではグループ別合計だけを保持します。", "チャンク平均を平均しません。", "原本行数と累積発送件数を照合します。"],
  ["各チャンクを配送先別に集計します。", "部分件数と荷物合計を辞書へ加算します。", "最後に荷物合計÷発送件数を計算します。"],
  "件数と合計は加算可能です。平均は分子と分母を統合してから導出します。",
  "チャンクサイズ3と5でも実行し、最終表と照合行数が同一になることを確認します。"),
 '''import pandas as pd
pd.DataFrame({
    "dispatch_id": [f"D{i:02d}" for i in range(1, 13)],
    "destination": ["North", "East", "South"] * 4,
    "packages": [8, 5, 7, 9, 6, 4, 10, 8, 5, 7, 9, 6],
}).to_csv("warehouse_dispatches.csv", index=False)

totals = {}
source_rows = 0
for chunk in pd.read_csv("warehouse_dispatches.csv", chunksize=4):
    source_rows += len(chunk)
    part = chunk.groupby("destination", as_index=False).agg(
        dispatches=("dispatch_id", "size"), packages=("packages", "sum"))
    for row in part.itertuples(index=False):
        state = totals.setdefault(row.destination, {"dispatches": 0, "packages": 0})
        state["dispatches"] += row.dispatches
        state["packages"] += row.packages
summary = pd.DataFrame([{"destination": key, **value} for key, value in totals.items()])
summary["packages_per_dispatch"] = summary["packages"] / summary["dispatches"]
assert source_rows == summary["dispatches"].sum()
print(summary.sort_values("destination").to_string(index=False))''', "Chunk size does not change the final summary"),

lesson("6.3", "Lesson 6.3: Reconcile and reproduce", "レッスン6.3：照合して再現可能にする",
 ("Use a food-warehouse fixture to rehearse the controls that make a large run explainable.",
  "Split eight rows into valid and review records using a non-negative quantity rule. Save a product summary, reopen it, and verify row reconciliation, columns, and repeated-run equality.",
  ["Every source row belongs to exactly one side.", "The summary is derived only from valid rows.", "Two chunk sizes produce the same sorted summary."],
  ["Record source, valid, and review counts.", "Sort and reset index before comparison.", "Reopen the actual CSV rather than trusting only memory."],
  "Fixture checks, reconciliation, deterministic ordering, and output reopening test different failure points; no single check replaces the others.",
  "Corrupt one quantity with text. Confirm it moves to review, reconciliation remains true, and the valid summary changes predictably."),
 ("食品倉庫のfixtureを使い、大規模実行を説明可能にする統制を練習します。",
  "8行を、数量が0以上という規則で有効と要確認へ分けます。商品別要約を保存して再読込し、件数照合、列、再実行一致を確認してください。",
  ["全原本行が必ずどちらか一方へ入ります。", "要約は有効行だけから作ります。", "二つのチャンクサイズで同じ並べ替え済み要約になります。"],
  ["原本、有効、要確認件数を記録します。", "比較前に並べ替え、indexを振り直します。", "メモリだけでなく実際のCSVを再読込します。"],
  "fixture確認、件数照合、決定的順序、出力再読込は異なる失敗点を検査し、一つで他を代替できません。",
  "数量一つを文字列で壊し、要確認へ移ること、照合が保たれること、要約が予想どおり変わることを確認します。"),
 '''import pandas as pd
records = pd.DataFrame({"product": ["Rice", "Beans", "Rice", "Oil"],
                        "quantity": [20, 15, -2, 8]})
working = records.copy()
working["quantity"] = pd.to_numeric(working["quantity"], errors="coerce")
review_mask = working["quantity"].isna() | working["quantity"].lt(0)
valid = working.loc[~review_mask].copy()
review = working.loc[review_mask].copy()
assert len(records) == len(valid) + len(review)
summary = valid.groupby("product", as_index=False).agg(total_quantity=("quantity", "sum"))
summary = summary.sort_values("product").reset_index(drop=True)
summary.to_csv("food_summary.csv", index=False)
saved = pd.read_csv("food_summary.csv")
assert saved.columns.tolist() == summary.columns.tolist()
assert len(saved) == len(summary)
print("RECONCILED:", len(records), len(valid), len(review))''', "RECONCILED: 4 3 1"),
]
