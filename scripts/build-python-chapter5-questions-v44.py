from pathlib import Path
import json


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "sample-content" / "introduction-to-python" / "chapter5-questions-v44"


EN = {
    "51": [
        ("01", "Which chart best compares total waiting minutes across three clinics?", ["A bar chart", "A line joining clinic names", "A histogram of clinic names", "An unlabelled pie"], 0, "Bars compare a small number of separate categories."),
        ("02", "Which preparation is essential before plotting weekly change?", ["Sort the week values in their real order", "Randomise the rows", "Replace every missing week with zero", "Remove the time axis"], 0, "A line chart requires meaningful order and visible gaps."),
        ("03", "What does one point represent in a scatter of patients_seen against average_wait_minutes from the source table?", ["One clinic-time-week record", "One axis label", "All clinics combined", "A causal mechanism"], 0, "Scatter pairs two quantities from the same observational unit."),
        ("04", "Which chart examines the distribution of one numeric variable?", ["Histogram", "Unordered line chart", "One decorative icon", "A table header"], 0, "A histogram counts values in adjacent numeric intervals."),
        ("05", "Why display the plot table before drawing?", ["To inspect the exact values and grain encoded by the chart", "To make labels unnecessary", "To prove causation", "To alter the source silently"], 0, "The chart remains traceable to inspected values."),
        ("06", "What should normally be true of the length axis in a category bar chart?", ["It begins at zero", "It begins just below the smallest value", "It has no units", "It changes for each bar"], 0, "Bar length encodes magnitude, so a zero baseline prevents exaggeration."),
        ("07", "Which label is complete for total waiting burden?", ["Total waiting time (minutes)", "Rate", "Value", "Better clinic"], 0, "The label names both the measure and unit."),
        ("08", "A monthly series has no March record. What should the analyst do first?", ["Expose the gap and investigate it", "Convert March to zero without explanation", "Join February and April and claim continuity", "Delete the month column"], 0, "Missing observation is not the same as observed zero."),
        ("09", "Which statement about a scatter plot is correct?", ["It can show association but does not establish cause", "It proves the x value caused y", "It must use category names on both axes", "It automatically validates the source"], 0, "Observed relationships need further evidence before a causal claim."),
        ("10", "Which check directly validates a clinic-level plot table?", ["Its totals reconcile with the analysis records", "Its colours look attractive", "Its title is removed", "Its rows are randomly ordered"], 0, "Numerical reconciliation checks that aggregation did not lose or duplicate records."),
    ],
    "52": [
        ("01", "Central has the greatest total wait, while Riverside Evening has the longest average wait. What follows?", ["The metrics answer different questions", "One result must be an error", "Average and total are interchangeable", "Clinic size never matters"], 0, "Total burden and individual experience are distinct questions."),
        ("02", "How should six weeks' combined average wait be calculated?", ["Sum waiting minutes and divide by summed patients", "Take an unweighted mean of any displayed percentages", "Divide patients by minutes", "Use only the largest week"], 0, "Compatible numerators and denominators are combined before division."),
        ("03", "Why show patient count beside an average wait?", ["The amount of supporting data affects interpretation", "Count makes every average equal", "Count proves the cause", "Count changes minutes to percent"], 0, "An average based on two observations has different context from one based on hundreds."),
        ("04", "What is the denominator of over_60_rate?", ["patients_seen", "total_wait_minutes", "number of clinics", "week label"], 0, "The percentage describes the share of observed patients who waited over 60 minutes."),
        ("05", "Why rank using unrounded values?", ["Early rounding can create or change ties", "Rounded values cannot be displayed", "It removes the need for an ID", "It makes missing values zero"], 0, "Use full precision for decisions and round only for communication."),
        ("06", "What is the role of clinic_id as a final sort key?", ["It makes ties reproducible", "It changes the main metric", "It proves one clinic is better", "It replaces validation"], 0, "A stable identifier gives deterministic order after substantive criteria tie."),
        ("07", "Why is a bar axis from 45 to 50 misleading for 46 and 48 minutes?", ["It exaggerates a two-minute difference in bar length", "Minutes cannot be plotted", "The values become missing", "It averages the bars"], 0, "A truncated length scale removes the magnitude baseline."),
        ("08", "Can clinic-level and clinic-time-slot rows be placed in one ranking?", ["No, they have different grain", "Yes, grain never matters", "Only after removing labels", "Only if colours match"], 0, "Every ranked row must represent the same kind of unit."),
        ("09", "What should a missing weekly record be treated as?", ["Missing until investigated", "Observed zero", "The weekly mean", "Proof of closure"], 0, "Absence of a record does not establish a zero value."),
        ("10", "Which code calculates a combined service over-60 percentage after aggregation?", ["over_60_minutes / patients_seen * 100", "patients_seen / over_60_minutes", "mean(total_wait_minutes)", "len(clinic_name)"], 0, "The aggregated affected count is divided by the aggregated population count."),
    ],
    "53": [
        ("01", "Which sentence is a bounded observation?", ["Riverside Evening averaged 48.1 minutes in the six-week data", "Staff shortage caused every delay", "Riverside is bad", "The chart proves the policy failed"], 0, "It names an observed value and scope without inventing a cause."),
        ("02", "What should follow a numerical observation in a short evidence note?", ["Population or period and a material limitation", "A decorative adjective", "A hidden denominator", "An unsupported diagnosis"], 0, "Scope and limitation keep the claim interpretable."),
        ("03", "Which is a valid limitation of the clinic records?", ["They do not identify the cause of waiting", "They contain no numbers", "They automatically represent every future week", "They prove staffing levels"], 0, "Operational records describe observed waits but lack causal variables."),
        ("04", "What is a restrained way to identify the support target in a chart?", ["One contrast colour plus a direct label", "3D area distortion", "Remove all other clinics", "Use colour without a legend or label"], 0, "Direct, limited emphasis preserves context."),
        ("05", "Why reopen the saved PNG?", ["To verify the delivered file retains title, axes, units, and target", "To change the source CSV", "To prove causation", "To avoid saving code"], 0, "The actual deliverable must be checked outside the live plotting state."),
        ("06", "Which sentence improperly turns association into cause?", ["Staff shortage caused the longer waits", "The recorded average was 48.1 minutes", "The comparison covers six weeks", "Investigate staffing next"], 0, "The dataset does not measure or isolate the cause."),
        ("07", "What makes a figure reproducible?", ["Source, aggregation, plotting code, and saved output remain connected", "Only a screenshot remains", "Axes are removed", "Values are entered by hand"], 0, "Another person should be able to regenerate the figure from the same evidence path."),
        ("08", "What does 'investigate staffing next' represent?", ["A bounded next question", "A proven cause", "A source-data value", "A chart type"], 0, "It suggests further inquiry without claiming the answer is already known."),
        ("09", "Why state that 36 clinic-time-week records were analysed?", ["It defines the scope supporting the claim", "It guarantees a causal result", "It removes the need for units", "It changes the average"], 0, "Readers need to know the population and period represented."),
        ("10", "Which three-sentence order is strongest?", ["Numerical observation; scope/denominator; limitation", "Cause; praise; decoration", "Title; colour; filename", "Limitation only; no result; no scope"], 0, "Each sentence performs one evidence-communication job."),
    ],
}


JA_PROMPTS = {
    "51": [
        ("三つの診療所の待ち時間合計を比較するのに最も適する図はどれですか。", ["棒グラフ", "診療所名を線で結ぶ折れ線", "診療所名のヒストグラム", "ラベルのない円グラフ"], "棒は少数の独立したカテゴリを比較します。"),
        ("週ごとの変化を描く前に必要な準備はどれですか。", ["週を実際の順序へ並べる", "行を無作為に並べる", "欠けた週を説明なく0にする", "時間軸を削除する"], "折れ線には意味のある順序と見える欠測が必要です。"),
        ("患者数と平均待ち時間の散布図で一点が表すものは何ですか。", ["一つの診療所・時間帯・週の記録", "一つの軸ラベル", "全診療所の合計", "因果の仕組み"], "散布図は同じ観測単位の二数量を結びます。"),
        ("一つの数量の分布を見る図はどれですか。", ["ヒストグラム", "順序のない折れ線", "装飾アイコン一つ", "表の見出し"], "ヒストグラムは連続する数値区間の件数を数えます。"),
        ("描画前に描画表を表示する理由は何ですか。", ["図が表す正確な値と粒度を確認する", "ラベルを不要にする", "因果を証明する", "原資料を黙って変える"], "図から確認済みの値へたどれるようにします。"),
        ("カテゴリ棒グラフの長さの軸は通常どうしますか。", ["0から始める", "最小値の直前から始める", "単位を消す", "棒ごとに変える"], "棒の長さが大きさを表すため0基準が誇張を防ぎます。"),
        ("待ち時間総負担の完全な軸ラベルはどれですか。", ["Total waiting time (minutes)", "Rate", "Value", "Better clinic"], "指標と単位の両方を示します。"),
        ("月別系列に3月の記録がない場合、最初に何をしますか。", ["欠けたことを示して調べる", "説明なく0へ変える", "2月と4月を結び連続と断定する", "月列を削除する"], "記録がないことと観測値0は別です。"),
        ("散布図について正しい説明はどれですか。", ["関連は見えるが原因までは証明しない", "xがyを起こしたと証明する", "両軸にカテゴリ名が必須", "原資料を自動検証する"], "観察された関係から因果を述べるには別の根拠が必要です。"),
        ("診療所別描画表を直接検証する方法はどれですか。", ["合計を分析用明細と照合する", "色の美しさだけを見る", "表題を削除する", "行を無作為にする"], "数値照合は集計時の欠落や二重計上を検出します。"),
    ],
    "52": [
        ("総待ち時間はCentral、平均待ちはRiverside Eveningが最大です。何が言えますか。", ["二つの指標は別の問いへ答える", "どちらかが必ず誤り", "平均と合計は同じ", "診療所規模は無関係"], "総負担と一人の経験は別の問いです。"),
        ("6週間を合わせた平均待ち時間はどう計算しますか。", ["待ち時間を合計し患者数合計で割る", "表示済み割合を単純平均する", "患者数を分数で割る", "最大週だけを使う"], "互換性のある分子と分母を合計してから割ります。"),
        ("平均待ち時間に患者数を添える理由は何ですか。", ["根拠となるデータ量が解釈に影響する", "すべての平均を同じにする", "原因を証明する", "分を割合へ変える"], "2人の平均と数百人の平均では文脈が違います。"),
        ("over_60_rateの分母はどれですか。", ["patients_seen", "total_wait_minutes", "診療所数", "週ラベル"], "診療患者のうち60分を超えた人の割合です。"),
        ("順位を丸め前の値で決める理由は何ですか。", ["早い丸めが同順位を作ったり変えたりする", "丸めた値は表示できない", "IDが不要になる", "欠測を0にする"], "判断には元の精度を使い、表示時だけ丸めます。"),
        ("最後の並べ替えキーにclinic_idを使う役割は何ですか。", ["同順位でも毎回同じ順にする", "主要指標を変える", "優れた診療所を証明する", "検証を不要にする"], "安定したIDが再現可能な順序を作ります。"),
        ("46分と48分の棒を45分から始める問題は何ですか。", ["2分の差を長さで誇張する", "分は描画できない", "値が欠測になる", "棒を平均する"], "切り取った長さ軸は大きさの基準を失わせます。"),
        ("診療所別行と診療所・時間帯別行を同じ順位へ入れられますか。", ["粒度が違うため入れない", "粒度は無関係なので入れる", "ラベルを消せば入れられる", "色が同じなら入れられる"], "順位の各行は同じ種類の単位を表す必要があります。"),
        ("欠けた週の記録をどう扱いますか。", ["調査するまで欠測として残す", "観測された0とする", "週平均とする", "休業の証明とする"], "記録がないことは値0を意味しません。"),
        ("集計後の60分超割合を求める式はどれですか。", ["over_60_minutes / patients_seen * 100", "patients_seen / over_60_minutes", "mean(total_wait_minutes)", "len(clinic_name)"], "影響を受けた人数を対象患者数で割ります。"),
    ],
    "53": [
        ("範囲を限定した観察文はどれですか。", ["6週間のデータでRiverside Eveningの平均は48.1分だった", "人員不足が全遅延を起こした", "Riversideは悪い", "図が政策失敗を証明した"], "値と範囲を示し、原因を作っていません。"),
        ("数値の観察に続ける内容として適切なのはどれですか。", ["対象・期間と重要な限界", "装飾的な形容詞", "隠した分母", "根拠のない診断"], "範囲と限界により主張を解釈できます。"),
        ("診療記録の妥当な限界はどれですか。", ["待ち時間の原因までは特定できない", "数値を含まない", "将来の全週を自動代表する", "人員数を証明する"], "業務記録は待ち時間を記述しますが原因変数を持ちません。"),
        ("図で支援対象を抑制して示す方法はどれですか。", ["一つの対比色と直接ラベル", "3D面積の歪み", "他診療所をすべて削除", "説明のない色だけ"], "限定した直接強調は文脈を保ちます。"),
        ("保存PNGを開き直す理由は何ですか。", ["表題・軸・単位・対象が成果物に残るか確認する", "原資料を変更する", "因果を証明する", "コード保存を避ける"], "実際に渡すファイルを実行中の画面とは別に確認します。"),
        ("関連を原因へ変えてしまった文はどれですか。", ["人員不足が長い待ち時間を発生させた", "記録上の平均は48.1分だった", "比較期間は6週間だった", "次に人員を調べる"], "このデータは原因を測定・分離していません。"),
        ("図を再現可能にするものは何ですか。", ["原資料、集計、描画コード、保存出力の接続", "スクリーンショットだけ", "軸の削除", "値の手入力"], "別の人が同じ経路から図を再生成できる必要があります。"),
        ("『次に人員配置を調べる』は何に当たりますか。", ["限定した次の問い", "証明済みの原因", "原資料の値", "図の種類"], "答えを知っていると断定せず、次の調査を示します。"),
        ("36件を分析したと示す理由は何ですか。", ["主張を支える範囲を定義する", "因果を保証する", "単位を不要にする", "平均を変える"], "読み手には対象集団と期間が必要です。"),
        ("三文の根拠説明として強い順序はどれですか。", ["数値観察、範囲・分母、限界", "原因、賞賛、装飾", "表題、色、ファイル名", "限界だけで結果も範囲もない"], "各文に一つの根拠伝達上の役割があります。"),
    ],
}


def build():
    for language in ("en", "ja"):
        destination = OUT / language
        destination.mkdir(parents=True, exist_ok=True)
        for lesson, questions in EN.items():
            result = []
            for index, question in enumerate(questions):
                identifier, prompt, choices, correct, reason = question
                if language == "ja":
                    prompt, choices, reason = JA_PROMPTS[lesson][index]
                result.append({"id": identifier, "p": prompt, "c": choices, "ok": correct, "why": reason})
            (destination / f"{lesson}.json").write_text(
                json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
            )
    print("built Chapter 5 question banks")


if __name__ == "__main__":
    build()
