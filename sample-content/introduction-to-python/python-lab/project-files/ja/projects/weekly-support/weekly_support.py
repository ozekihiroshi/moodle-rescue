"""プロジェクト1.7：週間サポート報告を完成させます。

ファイル名、入力順、英大文字の出力項目名は変更しません。
check_weekly_support.pyは変更しません。
"""

total_received = 0
total_resolved = 0
busiest_received = -1
busiest_day = ""
valid_data = True

for day_number in range(1, 6):
    if day_number == 1:
        day_name = "Monday"
    elif day_number == 2:
        day_name = "Tuesday"
    elif day_number == 3:
        day_name = "Wednesday"
    elif day_number == 4:
        day_name = "Thursday"
    else:
        day_name = "Friday"

    received = int(input(f"{day_name}の問い合わせ件数: "))
    resolved = int(input(f"{day_name}の解決件数: "))

    # TODO 1：業務ルールに従い、不正データを記録します。
    # TODO 2：二つの値を週間合計へ加えます。
    # TODO 3：最繁忙件数と曜日を更新します。同数なら最初の曜日を残します。

# TODO 4：不正データならRESULT: INVALIDを表示します。
# TODO 5：問い合わせ合計0件を処理します。
# TODO 6：それ以外では、未解決件数、解決率、状態を計算します。
# TODO 7：指定された英大文字の出力項目名を正確に表示します。

print("\nPROGRAM INCOMPLETE")
