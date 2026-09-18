# レッスン5.1：問いから図へ

## 導入

第3章では、検証した集計表を作りました。図は分析後に添える飾りではなく、その表を別の表現へ変換したものです。最初に判断したい問いと、図の一本または一点が何を表すかを決め、その後で図の種類を選びます。

## このレッスンを終えると

- 描画前に、問いと図の粒度を定義できる。
- 棒、折れ線、散布図、ヒストグラムを、調べる関係に合わせて選べる。
- 描画用の表を先に表示し、元データと照合できる。
- 表題、軸、単位、期間、必要な件数を図へ示せる。

> **必須経路：**5.1.1～5.1.5　統合練習：5.1.6

## 5.1.1 問いと図の粒度を決める

「診療所データを図にする」だけでは問いになりません。「6週間で待ち時間の総負担が最大だった診療所はどこか」なら、比較対象、指標、期間、結果の一行が明確です。原資料の一行は診療所・時間帯・週ですが、描画表の一行は一診療所にします。

```python
clinic = records.groupby(["clinic_id", "clinic_name"], as_index=False).agg(
    patients_seen=("patients_seen", "sum"),
    total_wait_minutes=("total_wait_minutes", "sum"),
)
display(clinic)
```

描画表の表示も根拠の一部です。各棒の元になった値を人間が確認できます。

## 5.1.2 カテゴリの大きさを棒で比べる

棒グラフは少数の独立したカテゴリを比べます。棒の長さが大きさを表すため、長さの軸は原則として0から始めます。順位が問いに含まれる場合は、意図した順へ並べます。

```python
plot_data = clinic.sort_values("total_wait_minutes")
ax = plot_data.plot.barh(
    x="clinic_name", y="total_wait_minutes", legend=False
)
ax.set(
    title="Total waiting burden by clinic",
    xlabel="Total waiting time (minutes)",
    ylabel="",
)
ax.set_xlim(left=0)
```

割合なら0～100%を使えます。件数や時間には固有の単位があります。分母の分からない「率」というラベルだけでは不十分です。

## 5.1.3 順序のある変化を折れ線で追う

折れ線は順序のある観測値を結びます。日付や期間を変換して並べ、欠けた期間を0として隠しません。順序のない診療所名を線で結ぶと、存在しない途中経路を表してしまいます。

```python
trend = records.query(
    "clinic_id == 'C002' and time_slot == 'Evening'"
).copy()
trend["average_wait_minutes"] = (
    trend["total_wait_minutes"] / trend["patients_seen"]
)
trend = trend.sort_values("week")
```

## 5.1.4 関係と分布を見る

散布図では、一つの記録が持つ二つの数量を一点として結びます。集まりや外れた点は見えますが、一方がもう一方の原因だとは証明できません。ヒストグラムは、一つの数量を連続する区間へ分けて件数を数えます。区間幅によって形が変わるため、件数を示し、複数の妥当な幅も確認します。

| 問い | 適する図 | 一本・一点が表すもの |
|---|---|---|
| 診療所を比較 | 棒 | 一診療所の合計または割合 |
| 週ごとの変化 | 折れ線 | 時間順の一週間の値 |
| 患者数と平均待ち時間 | 散布図 | 一つの診療所・時間帯・週 |
| 平均待ち時間の分布 | ヒストグラム | 数値区間に入る記録数 |

## 5.1.5 描画する値を検証し、意味をラベルにする

描画前に、描画表を分析用データと照合します。

```python
assert clinic["patients_seen"].sum() == records["patients_seen"].sum()
assert clinic["total_wait_minutes"].sum() == records["total_wait_minutes"].sum()
```

完成図には、具体的な表題、軸名、単位、対象期間、必要な標本数を示します。凡例は複数系列を識別するときだけ必要です。色だけに依存せず、直接ラベル、明暗差、記号、線種も利用します。

## 統合練習：章末課題へつながる軽い予行演習

> **章末課題との接続：**章末では診療所待ち時間を扱います。この軽い練習では、平均遅延が最大の地域バス路線を調べます。

### 作るもの

6便を路線別に集計し、平均遅延分の横棒グラフを作ります。軸と表題を付け、最大の路線だけを強調してください。

### 完成条件

- 図を選ぶ前に問いを定めます。
- 描画表は一行一路線です。
- 分単位の軸は0から始め、値ラベルを付けます。

### 解答への手引き

1. 平均遅延と便数を名前付き集計で作ります。
2. 描画前に並べ替えます。
3. 最大だけ対比色、他は中立色にします。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> 模範解答と解説
>
> ```python
> import pandas as pd
> import matplotlib.pyplot as plt
> trips = pd.DataFrame({"route": ["A", "B", "C", "A", "B", "C"],
>                       "delay_minutes": [4, 12, 7, 8, 6, 9]})
> summary = trips.groupby("route", as_index=False).agg(
>     average_delay=("delay_minutes", "mean"), trips=("delay_minutes", "size"))
> summary = summary.sort_values("average_delay")
> top = summary["average_delay"].idxmax()
> colors = ["#d0d7de"] * len(summary)
> colors[list(summary.index).index(top)] = "#d9485f"
> ax = summary.plot.barh(x="route", y="average_delay", color=colors, legend=False)
> ax.set(xlabel="Average delay (minutes)", ylabel="Route", title="Average bus delay by route")
> ax.set_xlim(left=0)
> for container in ax.containers:
>     ax.bar_label(container, fmt="%.1f")
> plt.tight_layout()
> ```
>
> **代表的な確認結果**
>
> ```text
> Route B has the highest average delay: 9.0 minutes
> ```
>
> 図は、問い、指標、粒度、集計表、視覚表現という処理の最後にあります。


### 値を変えて再確認する

平均遅延を総遅延へ変えます。どちらも正しくても強調路線が変わり得る理由を説明します。
## まとめ




このレッスンと統合練習を終えると、次のことができます。

- 問いが図の粒度と種類を決めます。
- 棒はカテゴリ比較、折れ線は順序、散布図は二数量の関係、ヒストグラムは分布を表します。
- 描画表、照合、ラベル、単位、期間、件数を残すと、図から元の根拠をたどれます。

## 次のレッスンへ

同じ原資料から、異なる問いへ答える正しい図を複数作れます。5.2では、総量、平均、割合、軸、集計単位が順位をどのように変えるかを扱います。
