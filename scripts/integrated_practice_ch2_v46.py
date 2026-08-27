from integrated_practice_common_v46 import lesson


PRACTICES = [
lesson("2.1", "Lesson 2.1: Lists, dictionaries, and records", "レッスン2.1：リスト・辞書・レコード",
 ("Before the library project, practise modelling a different collection: a small equipment register.",
  "Create records for projector E01, camera E02, and speaker E03. Store them in one list, find E02, mark it unavailable, and count available items.",
  ["Each record is a dictionary with `id`, `name`, and Boolean `available`.", "The register is a list of records.", "Search by ID rather than assuming a fixed position."],
  ["Start with three dictionary literals.", "Loop through the list and compare `item['id']`.", "Count records whose `available` value is true."],
  "The list represents the collection; each dictionary represents one record. Searching by stable ID survives reordering.",
  "Move E02 to the first position and run again. The same item must still be updated and the count must remain correct."),
 ("図書管理プロジェクトの前に、別の集合である小型備品台帳をモデル化します。",
  "プロジェクターE01、カメラE02、スピーカーE03のレコードを作り、一つのリストへ保存します。E02を探して貸出不可へ変え、利用可能数を数えてください。",
  ["各レコードは`id`、`name`、真偽値`available`を持つ辞書です。", "台帳はレコードのリストです。", "固定位置ではなくIDで探します。"],
  ["3つの辞書リテラルから始めます。", "リストを回り、`item['id']`を比較します。", "`available`が真のレコードを数えます。"],
  "リストが集合を、各辞書が一件のレコードを表します。安定したIDで探せば並び替えに耐えます。",
  "E02を先頭へ移動して再実行します。同じ備品が更新され、件数も変わらないことを確認します。"),
 '''equipment = [
    {"id": "E01", "name": "Projector", "available": True},
    {"id": "E02", "name": "Camera", "available": True},
    {"id": "E03", "name": "Speaker", "available": False},
]
for item in equipment:
    if item["id"] == "E02":
        item["available"] = False
available_count = 0
for item in equipment:
    if item["available"]:
        available_count += 1
print("AVAILABLE:", available_count)''', "AVAILABLE: 1"),

lesson("2.2", "Lesson 2.2: Functions, errors, and testing", "レッスン2.2：関数・エラー・テスト",
 ("Turn the equipment search into a small, testable function before building the larger project.",
  "Write `find_equipment(items, item_id)`. Return the matching dictionary or `None`; do not modify the list. Check one found and one missing ID with assertions.",
  ["Use the exact function name and two parameters.", "Return as soon as a matching ID is found.", "Use `assert` for both expected outcomes."],
  ["The missing return belongs after the loop, not inside it.", "Compare the record ID with the parameter.", "Take a shallow copy before and compare it after the calls."],
  "A clear contract separates input, return value, and side effects. Assertions make examples executable.",
  "Add a duplicate-free fourth record and test it. Then reorder the list; all assertions must still pass."),
 ("大きなプロジェクトの前に、備品検索を小さくテスト可能な関数へします。",
  "`find_equipment(items, item_id)`を書きます。一致する辞書または`None`を返し、リストは変更しません。見つかるIDと存在しないIDを`assert`で確認してください。",
  ["関数名と二つの引数を指定どおりにします。", "一致したら直ちに返します。", "成功と不在の両方を`assert`で確認します。"],
  ["見つからない場合の`return`はループの後です。", "レコードのIDと引数を比較します。", "呼出前に浅いコピーを作り、後で比較します。"],
  "明確な契約が入力、戻り値、副作用を分けます。`assert`により例が実行可能な確認になります。",
  "重複しない4件目を追加してテストし、次に並び替えます。すべての`assert`が通ることを確認します。"),
 '''def find_equipment(items, item_id):
    for item in items:
        if item["id"] == item_id:
            return item
    return None

equipment = [{"id": "E01", "name": "Projector"}, {"id": "E02", "name": "Camera"}]
before = equipment.copy()
assert find_equipment(equipment, "E02")["name"] == "Camera"
assert find_equipment(equipment, "E99") is None
assert equipment == before
print("ALL CHECKS PASSED")''', "ALL CHECKS PASSED"),

lesson("2.3", "Lesson 2.3: File and CSV input/output", "レッスン2.3：ファイル・CSVの読み書き",
 ("Rehearse the project’s read-transform-save cycle with room bookings rather than books.",
  "Create a tiny `bookings.csv`, read it with `csv.DictReader`, convert `people` to `int`, add 2 to booking R02, and save a new `bookings_updated.csv` with `csv.DictWriter`.",
  ["Keep the input file unchanged.", "Use the exact header `id,room,people`.", "Reopen the output and assert that R02 contains 14."],
  ["Create the small input in the rehearsal code.", "Convert CSV text before arithmetic.", "Pass `newline=''` and write the header once."],
  "Reading, typed transformation, separate output, and reopening form one reliable file-processing cycle.",
  "Add a room name containing a comma. Confirm that the `csv` module preserves it without manual splitting."),
 ("本ではなく部屋予約を使い、プロジェクトの読込・変換・保存を予行演習します。",
  "小さな`bookings.csv`を作り、`csv.DictReader`で読み、`people`を`int`へ変換します。R02へ2人追加し、別の`bookings_updated.csv`へ`csv.DictWriter`で保存してください。",
  ["入力ファイルは変更しません。", "見出しは`id,room,people`です。", "出力を再読込し、R02が14人であることを`assert`します。"],
  ["練習コード内で小さな入力を作ります。", "CSVの文字列を算術前に変換します。", "`newline=''`を指定し、見出しは一度だけ書きます。"],
  "読込、型付き変換、別名保存、再読込が、信頼できるファイル処理の一巡です。",
  "カンマを含む部屋名を追加し、手作業の分割なしで`csv`モジュールが保持することを確認します。"),
 '''import csv
from pathlib import Path

source = Path("bookings.csv")
output = Path("bookings_updated.csv")
source.write_text("id,room,people\\nR01,Hall A,8\\nR02,Room B,12\\n", encoding="utf-8")
with source.open(encoding="utf-8", newline="") as file:
    rows = list(csv.DictReader(file))
for row in rows:
    row["people"] = int(row["people"])
    if row["id"] == "R02":
        row["people"] += 2
with output.open("w", encoding="utf-8", newline="") as file:
    writer = csv.DictWriter(file, fieldnames=["id", "room", "people"])
    writer.writeheader()
    writer.writerows(rows)
with output.open(encoding="utf-8", newline="") as file:
    saved = list(csv.DictReader(file))
assert saved[1]["people"] == "14"
print("ROUND TRIP OK")''', "ROUND TRIP OK"),
]
