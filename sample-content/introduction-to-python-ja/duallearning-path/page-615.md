## 教師用模範解答

    registered = 40
    weekly_attendance = [31, 33, 30, 34]
    total = sum(weekly_attendance)
    mean = total / len(weekly_attendance)
    rate = mean / registered * 100
    if rate < 75:
        status = "support"
    elif rate < 85:
        status = "watch"
    else:
        status = "on track"
    print(round(rate, 1), status)

sum()の代わりに明示的なループを使う解答も認めます。74.9、75、84.9、85などの値でのテストを必須としてください。学習者にregisteredまたは週ごとの値を一つ変更させ、実行前に結果がどちらへ変化するか予想させます。
