## 分析ツール：ブール論理、集合、基本統計

これらは独立した数学試験ではなく、Pythonとpandasの処理の中で使います。

### ブール論理

    priority = (registered >= 30) and (attendance_rate < 75)
    review = missing_data or impossible_value
    valid = not duplicate

- **AND：**両方の条件が真である必要があります。
- **OR：**少なくとも一つの条件が真なら真になります。
- **NOT：**真と偽を反転します。

### 集合

    expected = {"Central", "North", "South"}
    observed = set(df["district"].dropna())
    unexpected = observed - expected
    missing = expected - observed

集合は重複のない値を保持します。積集合は共通する値を、差集合は一方だけに存在する値を見つけます。

### 基本統計

- **件数：**観測値がいくつあるか。
- **平均値：**合計を件数で割った値。極端な値の影響を受けます。
- **中央値：**順番に並べた中央の値。極端な値の影響を受けにくい場合があります。
- **範囲：**最大値から最小値を引いた値。
- **重み付き割合：**出席者合計を登録者合計で割った値。通常、各センター割合の単純平均とは異なります。

<!-- -->

    weighted_rate = df["attended"].sum() / df["registered"].sum() * 100

統計量は観察したデータを要約します。それだけで原因を証明するものではありません。
