from integrated_practice_common_v46 import lesson


PRACTICES = [
lesson("1.1", "Lesson 1.1: Programs, values, expressions, and output", "レッスン1.1：プログラム・値・式・出力",
 ("A small rehearsal for producing a labelled report, using book packing rather than the chapter project.",
  "A box holds 12 books and 4 full boxes are ready. Display a heading, books per box, boxes, and calculated total on four lines.",
  ["Use only values, `*`, parentheses, and `print()`.", "Show four clear labels and calculate the total.", "Predict the output before running."],
  ["Put text labels in quotes.", "Let Python calculate `12 * 4`; do not type 48 as the answer.", "Use one `print()` for each line."],
  "The expression calculates the value; `print()` makes it visible. The repeated box count prepares the next lesson.",
  "Change 4 boxes to 5. Which two places must change? Lesson 1.2 removes that duplication."),
 ("ラベル付きの短い報告を作る予行演習です。章末課題とは別の、本の箱詰めを題材にします。",
  "1箱に12冊の本が入り、満杯の箱が4箱あります。見出し、1箱の冊数、箱数、計算した合計を4行で表示してください。",
  ["値、`*`、括弧、`print()`だけを使います。", "4つのラベルを明示し、合計を計算します。", "実行前に出力を予想します。"],
  ["文字のラベルは引用符で囲みます。", "答えの48を直接書かず、`12 * 4`をPythonに計算させます。", "出力1行につき`print()`を1回使います。"],
  "式が値を計算し、`print()`が画面に出します。箱数を繰り返すのは次のレッスンへの準備です。",
  "箱数を5へ変えてください。何か所を直す必要がありますか。1.2では、この重複をなくします。"),
 '''print("BOOK PACKING")
print("BOOKS PER BOX:", 12)
print("BOXES:", 4)
print("TOTAL BOOKS:", 12 * 4)''', "BOOK PACKING\nBOOKS PER BOX: 12\nBOXES: 4\nTOTAL BOOKS: 48"),

lesson("1.2", "Lesson 1.2: Variables, assignment, and program state", "レッスン1.2：変数・代入・プログラムの状態",
 ("Use event tickets to practise keeping one source for each value and updating derived state.",
  "An event planned 80 tickets and cancelled 7. Store the values, calculate active tickets, and display all three quantities.",
  ["Use meaningful names.", "Calculate active tickets from the two source values.", "After changing a source, rerun the derived assignment."],
  ["Assign the two source values first.", "The right side is evaluated before the left name changes.", "Print labels with all three variables."],
  "A derived variable stores the result produced when its assignment runs; it does not update like a spreadsheet formula.",
  "Change cancellations to 12. Inspect the old active value, then rerun the calculation and confirm 68."),
 ("行事の入場券を題材に、一つの意味を一つの変数へ置き、導出した状態を更新します。",
  "80枚を発行予定でしたが7枚を取り消しました。予定数と取消数を保存し、有効数を計算して3つの値を表示してください。",
  ["意味の分かる変数名を使います。", "有効数は二つの元の値から計算します。", "元の値を変えた後は導出する代入も再実行します。"],
  ["最初に二つの元の値を代入します。", "右辺を計算してから左辺の名前を更新します。", "3変数をラベル付きで表示します。"],
  "導出した変数は代入文を実行した時点の結果であり、自動更新されません。",
  "取消数を12へ変えて古い有効数を確認し、計算を再実行して68になることを確かめます。"),
 '''planned = 80
cancelled = 7
active = planned - cancelled
print("PLANNED:", planned)
print("CANCELLED:", cancelled)
print("ACTIVE:", active)''', "PLANNED: 80\nCANCELLED: 7\nACTIVE: 73"),

lesson("1.3", "Lesson 1.3: Basic scalar types, conversion, and arithmetic", "レッスン1.3：基本データ型・型変換・算術",
 ("Calculate a battery charge rate while keeping integer, float, text, and Boolean meanings distinct.",
  "A battery stores 37 units out of 48. Display the percentage to one decimal place and whether it is at least 75% charged.",
  ["Keep quantities as integers and the rate as a float.", "Use stored ÷ capacity × 100.", "Compare the unrounded rate with 75."],
  ["Do not reverse numerator and denominator.", "Use `:.1f` only for display.", "The comparison produces a Boolean."],
  "Counts, a calculated rate, and a threshold decision have different types and meanings.",
  "Try stored values 36 and 35. Predict the percentage and Boolean before each run."),
 ("蓄電池の充電率を題材に、整数、浮動小数点数、文字列、真偽値を区別します。",
  "容量48単位に37単位が蓄えられています。割合を小数第1位まで表示し、75%以上かも表示してください。",
  ["二つの量は整数、割合は浮動小数点数にします。", "蓄電量÷容量×100で計算します。", "丸め前の割合を75と比較します。"],
  ["分子と分母を逆にしません。", "表示時だけ`:.1f`を使います。", "比較結果は真偽値です。"],
  "件数、計算した割合、しきい値判定は、それぞれ異なる型と意味を持ちます。",
  "蓄電量を36、35へ変え、各実行前に割合と真偽値を予想してください。"),
 '''capacity = 48
stored = 37
rate = stored / capacity * 100
print(f"CHARGE: {rate:.1f}%")
print("READY:", rate >= 75)''', "CHARGE: 77.1%\nREADY: True"),

lesson("1.4", "Lesson 1.4: Strings, input, and formatted output", "レッスン1.4：文字列・入力・書式付き出力",
 ("Accept a parcel record and turn input text into a calculated, formatted line.",
  "Read destination, parcel count, and price per parcel. Display a line such as `Kumasi: 6 parcels, total 4500`.",
  ["Use three `input()` calls.", "Strip the destination and convert both numeric inputs.", "Keep input, calculation, and presentation separate."],
  ["`input()` returns text.", "Use `int()` for whole numbers.", "Use one f-string for the result."],
  "Conversion happens at the program boundary; calculation then uses numbers and the f-string controls presentation.",
  "Run the unchanged program with another destination and numbers. Do not fix sample answers in the code."),
 ("小包の記録を題材に、入力文字列を計算済みの書式付き出力へ変えます。",
  "配送先、小包数、1個当たり料金を読み、`Kumasi: 6 parcels, total 4500`のように表示してください。",
  ["`input()`を3回使います。", "配送先の空白を除き、二つの数値入力を変換します。", "入力、計算、表示を分けます。"],
  ["`input()`は文字列を返します。", "整数には`int()`を使います。", "結果は一つのf文字列で表示します。"],
  "入口で型変換し、その後は数値として計算し、f文字列で表示を整えます。",
  "プログラムを変えず、別の配送先と数値でも実行します。例の答えをコードへ固定しません。"),
 '''destination = input("Destination: ").strip()
parcels = int(input("Parcels: "))
price = int(input("Price per parcel: "))
total = parcels * price
print(f"{destination}: {parcels} parcels, total {total}")''', "Kumasi: 6 parcels, total 4500"),

lesson("1.5", "Lesson 1.5: Decisions with conditions", "レッスン1.5：条件による判断",
 ("Turn parcel-weight rules into ordered, mutually exclusive branches.",
  "Classify a parcel: negative is `DATA REVIEW`; under 2 kg `SMALL`; under 10 kg `STANDARD`; otherwise `HEAVY`.",
  ["Check invalid data first.", "Use one `if/elif/else` chain.", "Test just below, at, and above 2 and 10."],
  ["Order tests from exceptional to narrower to broader.", "`weight < 2` excludes 2.", "Only the first true branch runs."],
  "The order is part of the rule. Boundary tests show whether the comparison matches the written requirement.",
  "Try -0.1, 1.9, 2.0, 9.9, and 10.0; predict every category before running."),
 ("小包の重量規則を、順序のある排他的な分岐へ変換します。",
  "負なら`DATA REVIEW`、2kg未満なら`SMALL`、10kg未満なら`STANDARD`、それ以外は`HEAVY`とします。",
  ["最初に不正値を確認します。", "一つの`if/elif/else`連鎖を使います。", "2と10の直前、境界上、直後を試します。"],
  ["例外、狭い範囲、広い範囲の順に判定します。", "`weight < 2`は2を含みません。", "最初に真となった一つだけが動きます。"],
  "判定順も規則の一部です。境界値テストで、比較が文章の条件に合うか確認できます。",
  "-0.1、1.9、2.0、9.9、10.0を試し、実行前に各分類を予想します。"),
 '''weight = 2.0
if weight < 0:
    category = "DATA REVIEW"
elif weight < 2:
    category = "SMALL"
elif weight < 10:
    category = "STANDARD"
else:
    category = "HEAVY"
print(category)''', "STANDARD"),

lesson("1.6", "Lesson 1.6: Repetition with loops", "レッスン1.6：ループによる繰り返し",
 ("Use workshop material costs to rehearse the repeated-processing pattern needed by the chapter project.",
  "Process `[82.5, 74.0, 91.5, 80.0]` with one loop. Display each week, total, mean, maximum, and count above 80; display `NO DATA` for an empty list.",
  ["Use accumulators and a conditional counter.", "Do not use `sum()`, `max()`, or `statistics`.", "Avoid division for empty data."],
  ["Handle empty data first.", "Initialise state before the loop.", "Calculate the mean after the loop."],
  "One loop can update several pieces of state; the empty branch prevents an invalid maximum and division by zero.",
  "Add 65.0. Predict which outputs change and which remain unchanged."),
 ("章末課題で必要な反復処理を、研修材料費という別題材で練習します。",
  "`[82.5, 74.0, 91.5, 80.0]`を一つのループで処理し、各週、合計、平均、最大、80超件数を表示します。空なら`NO DATA`とします。",
  ["累積変数と条件付きカウンターを使います。", "`sum()`、`max()`、`statistics`は使いません。", "空データでは割り算しません。"],
  ["空の場合を先に分岐します。", "ループ前に状態を初期化します。", "平均はループ後に計算します。"],
  "一つのループで複数の状態を更新でき、空分岐が不正な最大値とゼロ除算を防ぎます。",
  "65.0を追加し、実行前に変わる出力と変わらない出力を予想します。"),
 '''costs = [82.5, 74.0, 91.5, 80.0]
if not costs:
    print("NO DATA")
else:
    total = 0
    maximum = costs[0]
    above_80 = 0
    for week, cost in enumerate(costs, start=1):
        print(week, cost)
        total += cost
        if cost > maximum:
            maximum = cost
        if cost > 80:
            above_80 += 1
    print("TOTAL:", total)
    print("MEAN:", total / len(costs))
    print("MAXIMUM:", maximum)
    print("ABOVE 80:", above_80)''', "TOTAL: 328.0\nMEAN: 82.0\nMAXIMUM: 91.5\nABOVE 80: 2"),
]
