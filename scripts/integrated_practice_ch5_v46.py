from integrated_practice_common_v46 import lesson


PRACTICES = [
lesson("5.1", "Lesson 5.1: From a question to a chart", "レッスン5.1：問いから図へ",
 ("The chapter project concerns clinic waits. This lighter rehearsal asks which community bus route has the highest average delay.",
  "Aggregate six trip records by route, create a horizontal bar chart of mean delay minutes, label axes and title, and highlight only the highest route.",
  ["State the question before choosing the chart.", "The plotted table has one row per route.", "Use a zero-based minute axis and direct value labels."],
  ["Group with named aggregation for mean delay and trip count.", "Sort before plotting.", "Use one contrast colour for the maximum and a neutral colour for others."],
  "The chart is the last step of a chain: question, metric, grain, summary table, then visual encoding.",
  "Replace mean delay with total delay. Explain why the highlighted route may change even when both calculations are correct."),
 ("章末では診療所待ち時間を扱います。この軽い練習では、平均遅延が最大の地域バス路線を調べます。",
  "6便を路線別に集計し、平均遅延分の横棒グラフを作ります。軸と表題を付け、最大の路線だけを強調してください。",
  ["図を選ぶ前に問いを定めます。", "描画表は一行一路線です。", "分単位の軸は0から始め、値ラベルを付けます。"],
  ["平均遅延と便数を名前付き集計で作ります。", "描画前に並べ替えます。", "最大だけ対比色、他は中立色にします。"],
  "図は、問い、指標、粒度、集計表、視覚表現という処理の最後にあります。",
  "平均遅延を総遅延へ変えます。どちらも正しくても強調路線が変わり得る理由を説明します。"),
 '''import pandas as pd
import matplotlib.pyplot as plt
trips = pd.DataFrame({"route": ["A", "B", "C", "A", "B", "C"],
                      "delay_minutes": [4, 12, 7, 8, 6, 9]})
summary = trips.groupby("route", as_index=False).agg(
    average_delay=("delay_minutes", "mean"), trips=("delay_minutes", "size"))
summary = summary.sort_values("average_delay")
top = summary["average_delay"].idxmax()
colors = ["#d0d7de"] * len(summary)
colors[list(summary.index).index(top)] = "#d9485f"
ax = summary.plot.barh(x="route", y="average_delay", color=colors, legend=False)
ax.set(xlabel="Average delay (minutes)", ylabel="Route", title="Average bus delay by route")
ax.set_xlim(left=0)
for container in ax.containers:
    ax.bar_label(container, fmt="%.1f")
plt.tight_layout()''', "Route B has the highest average delay: 9.0 minutes"),

lesson("5.2", "Lesson 5.2: Honest comparisons", "レッスン5.2：誤解を生まない比較",
 ("Use food deliveries to compare total burden with the experience per delivery.",
  "For three zones, calculate total late minutes and average late minutes per delivery. Keep delivery count beside both metrics and rank each metric separately.",
  ["Totals and averages answer different questions.", "Compute averages from compatible totals and counts.", "Use an honest zero-based bar axis."],
  ["Aggregate `late_minutes` sum and delivery count first.", "Calculate the mean after aggregation.", "Sort each copy without losing the common summary table."],
  "A large service can lead in total burden while another has the worse per-delivery experience. Sample size gives both values context.",
  "Add ten on-time deliveries to one zone. Predict which metric changes and why."),
 ("食品配送を題材に、総負担と一配送当たりの経験を比較します。",
  "3地区について遅延分の合計と一配送当たり平均を求めます。両指標に配送件数を添え、それぞれ別に順位付けしてください。",
  ["合計と平均は別の問いへ答えます。", "互換性のある合計と件数から平均を求めます。", "棒の軸は正直に0から始めます。"],
  ["まず遅延分合計と配送件数を集計します。", "平均は集計後に計算します。", "共通集計表を失わず、コピーを別々に並べます。"],
  "規模の大きい地区が総負担で1位でも、別地区の一配送当たり経験が悪い場合があります。件数が両指標の文脈になります。",
  "一地区へ定刻配送を10件追加します。どの指標が変わるか、理由とともに予想します。"),
 '''import pandas as pd
deliveries = pd.DataFrame({
    "zone": ["North", "North", "North", "East", "East", "South"],
    "late_minutes": [10, 20, 0, 25, 5, 18],
})
summary = deliveries.groupby("zone", as_index=False).agg(
    total_late_minutes=("late_minutes", "sum"),
    deliveries=("late_minutes", "size"),
)
summary["average_late_minutes"] = summary["total_late_minutes"] / summary["deliveries"]
print(summary.sort_values("total_late_minutes", ascending=False).to_string(index=False))
print(summary.sort_values("average_late_minutes", ascending=False).to_string(index=False))''', "Total burden: North 30; highest average: South 18.0"),

lesson("5.3", "Lesson 5.3: From chart to evidence statement", "レッスン5.3：図から根拠文へ",
 ("Turn a small solar-maintenance chart into bounded evidence rather than an unsupported story.",
  "Given site completion rates A=92%, B=76%, C=84% for April–June, write exactly three sentences: an observation with values, scope and denominator, and a limitation with the next question.",
  ["Use only facts available in the data.", "Name the period and explain that completed jobs ÷ scheduled jobs is the denominator.", "Do not claim a cause."],
  ["Sentence 1 answers what differs.", "Sentence 2 answers what records and denominator were compared.", "Sentence 3 says what the data cannot establish and what to inspect next."],
  "A concise evidence statement separates observed comparison, analytical scope, and untested explanation.",
  "Change site B to 88%. Rewrite only the sentence whose factual comparison changes; keep the limitation bounded."),
 ("小さな太陽光設備保守の図を、根拠のない物語ではなく範囲を限定した根拠文へ変えます。",
  "4～6月の完了率A=92%、B=76%、C=84%から、ちょうど3文を書きます。数値付き観察、範囲と分母、限界と次の問いです。",
  ["データにある事実だけを使います。", "期間と、完了作業÷予定作業という分母を示します。", "原因を断定しません。"],
  ["第1文は何が違うかを書きます。", "第2文は対象記録と分母を書きます。", "第3文は分からないことと次に調べることを書きます。"],
  "短い根拠文は、観察した比較、分析範囲、未検証の説明を分離します。",
  "Bを88%へ変え、事実比較が変わる文だけを書き換えます。限界の範囲は保ちます。"),
 '''rates = {"Site A": 92, "Site B": 76, "Site C": 84}
lowest = min(rates, key=rates.get)
print(f"From April to June, {lowest} had the lowest completion rate at {rates[lowest]}%, compared with 92% at Site A and 84% at Site C.")
print("The rates compare completed jobs with scheduled jobs recorded for each site during the three-month period.")
print("These records do not establish why Site B was lower; the next check should compare job type, cancellations, and staffing by site.")''',
 "Three sentences: observation, scope/denominator, and bounded limitation"),
]
