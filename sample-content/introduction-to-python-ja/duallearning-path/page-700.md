## 解答例の利用指針

完全一致を求める正解ではなく、話し合いのための参考として使ってください。

### 基礎プロジェクト

    def summarise(scores, pass_mark=50):
        valid = [s for s in scores if 0 <= s <= 100]
        if not valid:
            return None
        return {
            "count": len(valid),
            "mean": sum(valid) / len(valid),
            "minimum": min(valid),
            "maximum": max(valid),
            "passes": sum(s >= pass_mark for s in valid),
        }

ループを使った同等の解答も認めます。不正な値をどのように報告したか尋ねてください。説明せずに無視する処理は、根拠として弱くなります。

### 学習センター分析

良い提出物は、`str.strip().str.title()`で地区名を統一し、completion_rateを数値へ変換し、欠損値1件と範囲外の値1件を報告します。除外または修正は理由がある場合だけ行い、有効な行をグループ化し、件数を併記し、グラフにラベルを付けます。また、この小さな架空データでは原因を説明できないことを記します。

### 最終プロジェクト

唯一の正しいコードはありません。問いからクリーニング、計算、グラフ、主張へ至る追跡可能な流れを評価してください。説明可能な限界と透明なAI利用を評価し、しきい値の変更や、グラフの種類を選んだ理由の説明を求めます。
