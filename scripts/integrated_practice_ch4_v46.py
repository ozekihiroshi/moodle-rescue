from integrated_practice_common_v46 import lesson


PRACTICES = [
lesson("4.1", "Lesson 4.1: From records and functions to objects", "レッスン4.1：レコードと関数からオブジェクトへ",
 ("The chapter project manages equipment loans. This lighter rehearsal instead models a meeting room and compares a dictionary with an object.",
  "Create `Room('R01', 'Blue Room', 12)`. It must expose those three attributes and provide `label()` returning `R01 — Blue Room (12 seats)`.",
  ["Define one class with `__init__` and `label`.", "Construct two independent rooms.", "Changing one room name must not change the other."],
  ["Store constructor arguments on `self`.", "A method receives the current object as `self`.", "Return the label rather than printing inside the method."],
  "The object bundles one room’s state with behaviour that belongs to that state. Two instances share the class but keep separate data.",
  "Create R02 with 20 seats and print both labels. Then rename only R01 and confirm R02 is unchanged."),
 ("章末では備品貸出を扱います。この軽い練習では会議室をモデル化し、辞書とオブジェクトの違いを確認します。",
  "`Room('R01', 'Blue Room', 12)`を作ります。3属性を持ち、`R01 — Blue Room (12 seats)`を返す`label()`を用意してください。",
  ["`__init__`と`label`を持つ一つのクラスを定義します。", "独立した部屋を二つ作ります。", "一方の名前変更が他方へ影響しないことを確認します。"],
  ["コンストラクタ引数を`self`へ保存します。", "メソッドは現在のオブジェクトを`self`で受け取ります。", "メソッド内で表示せず、ラベルを返します。"],
  "オブジェクトは一つの部屋の状態と、その状態に属する振る舞いをまとめます。インスタンスはクラスを共有してもデータは独立します。",
  "20席のR02を作って両方を表示し、R01だけ改名してR02が変わらないことを確認します。"),
 '''class Room:
    def __init__(self, room_id, name, seats):
        self.room_id = room_id
        self.name = name
        self.seats = seats

    def label(self):
        return f"{self.room_id} — {self.name} ({self.seats} seats)"

room = Room("R01", "Blue Room", 12)
print(room.label())''', "R01 — Blue Room (12 seats)"),

lesson("4.2", "Lesson 4.2: State, methods, and valid objects", "レッスン4.2：状態・メソッド・正しいオブジェクト",
 ("Use a room reservation to practise valid construction and controlled state transitions.",
  "Define `Reservation` with states `PENDING`, `CONFIRMED`, and `CANCELLED`. Confirmation is allowed only from pending; cancellation is allowed unless already cancelled.",
  ["Reject an empty booking ID or a non-positive party size in `__init__`.", "Methods enforce transitions and raise `ValueError` for invalid moves.", "Test the normal path and one invalid repeated confirmation."],
  ["Start every valid object in `PENDING`.", "Check current state before changing it.", "A failed method must leave state unchanged."],
  "The class protects its invariant instead of relying on every caller to remember the rules.",
  "Cancel a fresh reservation, then try to confirm it. Confirm that an exception occurs and state remains `CANCELLED`."),
 ("部屋予約を使い、正しい生成と制御された状態遷移を練習します。",
  "`PENDING`、`CONFIRMED`、`CANCELLED`を持つ`Reservation`を定義します。確認は待機中のみ、取消は未取消の場合のみ許可します。",
  ["空の予約IDと0以下の人数を`__init__`で拒否します。", "メソッドが遷移を守り、不正時は`ValueError`を出します。", "正常経路と確認の二重実行をテストします。"],
  ["有効なオブジェクトは必ず`PENDING`で始めます。", "変更前に現在状態を確認します。", "失敗したメソッドは状態を変えません。"],
  "クラスが不変条件を守るため、すべての呼出側が規則を暗記する必要がなくなります。",
  "新規予約を取消してから確認し、例外後も`CANCELLED`のままであることを確かめます。"),
 '''class Reservation:
    def __init__(self, booking_id, people):
        if not booking_id.strip() or people <= 0:
            raise ValueError("invalid reservation")
        self.booking_id = booking_id.strip()
        self.people = people
        self.status = "PENDING"

    def confirm(self):
        if self.status != "PENDING":
            raise ValueError("cannot confirm")
        self.status = "CONFIRMED"

    def cancel(self):
        if self.status == "CANCELLED":
            raise ValueError("already cancelled")
        self.status = "CANCELLED"

r = Reservation("B01", 4)
r.confirm()
assert r.status == "CONFIRMED"
print("STATE OK")''', "STATE OK"),

lesson("4.3", "Lesson 4.3: Collections, composition, and responsibility", "レッスン4.3：複数オブジェクト・合成・責任分担",
 ("Build a small timetable that owns room reservations, keeping collection rules out of each reservation.",
  "Define `Schedule.add(reservation)`, `find(booking_id)`, and `count_by_status()`. Reject duplicate IDs. `Reservation` remains responsible only for one booking.",
  ["`Schedule` contains a list of `Reservation` objects.", "Search by booking ID and return `None` when absent.", "The status count is a dictionary derived from current objects."],
  ["Initialise the collection in `Schedule.__init__`.", "Call `find` before appending.", "Loop over reservations to build counts."],
  "Composition assigns one-record rules to `Reservation` and collection-wide rules to `Schedule`.",
  "Add a third status to one reservation and confirm `count_by_status()` reflects current state without stored duplicate totals."),
 ("部屋予約を所有する小さな予定表を作り、集合の規則を一件の予約から分離します。",
  "`Schedule.add(reservation)`、`find(booking_id)`、`count_by_status()`を定義し、ID重複を拒否します。`Reservation`は一件だけを担当します。",
  ["`Schedule`は`Reservation`オブジェクトのリストを持ちます。", "IDで検索し、不在なら`None`を返します。", "状態件数は現在のオブジェクトから作る辞書です。"],
  ["`Schedule.__init__`で集合を初期化します。", "追加前に`find`を呼びます。", "予約をループして件数を作ります。"],
  "合成により、一件の規則を`Reservation`へ、集合全体の規則を`Schedule`へ割り当てます。",
  "一件の状態を変更し、重複した合計を保存せずに`count_by_status()`へ反映されることを確認します。"),
 '''class Reservation:
    def __init__(self, booking_id, status="PENDING"):
        self.booking_id = booking_id
        self.status = status


class Schedule:
    def __init__(self):
        self.reservations = []

    def find(self, booking_id):
        for reservation in self.reservations:
            if reservation.booking_id == booking_id:
                return reservation
        return None

    def add(self, reservation):
        if self.find(reservation.booking_id) is not None:
            raise ValueError("duplicate booking")
        self.reservations.append(reservation)

    def count_by_status(self):
        counts = {}
        for reservation in self.reservations:
            counts[reservation.status] = counts.get(reservation.status, 0) + 1
        return counts''', "A duplicate booking raises ValueError"),

lesson("4.4", "Lesson 4.4: Persistence and testing class-based programs", "レッスン4.4：オブジェクトの保存とテスト",
 ("Rehearse the class-to-CSV boundary with bicycle rentals rather than equipment loans.",
  "Give `Rental` a `to_record()` method and `from_record()` class method. Save two rentals to CSV, reload them, and assert that ID, bicycle ID, and active state survive the round trip.",
  ["CSV rows contain strings; the object uses Boolean state.", "Serialisation belongs at the boundary, not scattered through business methods.", "The input and output headers remain stable."],
  ["Write Boolean values as `true` or `false`.", "Parse them explicitly when loading.", "Compare attribute tuples after reopening."],
  "Objects provide behaviour in memory; records provide portable persistence. Explicit conversion keeps both representations understandable.",
  "Change one saved row to an invalid Boolean and confirm `from_record()` raises a clear `ValueError`."),
 ("備品貸出ではなく自転車レンタルを使い、クラスとCSVの境界を練習します。",
  "`Rental`へ`to_record()`と`from_record()`クラスメソッドを作ります。2件をCSVへ保存して再読込し、ID、自転車ID、利用中状態が往復後も保たれることを確認します。",
  ["CSV行は文字列、オブジェクトの状態は真偽値です。", "直列化は業務メソッドへ散らさず境界へ置きます。", "入出力の見出しを安定させます。"],
  ["真偽値は`true`または`false`で保存します。", "読込時に明示的に解釈します。", "再読込後に属性のタプルを比較します。"],
  "オブジェクトはメモリ上の振る舞いを、レコードは持ち運べる保存形式を提供します。明示的な変換が両方を理解可能にします。",
  "保存行の真偽値を不正値へ変え、`from_record()`が明確な`ValueError`を出すことを確認します。"),
 '''class Rental:
    def __init__(self, rental_id, bicycle_id, active):
        self.rental_id = rental_id
        self.bicycle_id = bicycle_id
        self.active = active

    def to_record(self):
        return {"rental_id": self.rental_id, "bicycle_id": self.bicycle_id,
                "active": "true" if self.active else "false"}

    @classmethod
    def from_record(cls, row):
        value = row["active"].strip().lower()
        if value not in {"true", "false"}:
            raise ValueError("invalid active value")
        return cls(row["rental_id"], row["bicycle_id"], value == "true")

rental = Rental.from_record(Rental("R01", "B07", True).to_record())
assert (rental.rental_id, rental.bicycle_id, rental.active) == ("R01", "B07", True)
print("ROUND TRIP OK")''', "ROUND TRIP OK"),
]
