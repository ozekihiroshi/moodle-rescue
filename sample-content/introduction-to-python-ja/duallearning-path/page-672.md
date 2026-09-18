## 導入

**実際の窓口は多数のオブジェクトを調整します。** 機材は貸出可能かを知っていますが、全機材の検索まで知る必要はありません。窓口クラスがコレクションを持ち、状態規則は機材に残します。

## このレッスンの到達目標

- 安定した識別子をキーにオブジェクトを保存する。
- 機材を含む窓口を合成として表す。
- 状態変更を規則を持つオブジェクトへ委譲する。
- 第二の真実を作らず集計する。

**学習経路:** 必須 4.3.1〜4.3.4｜統合練習 4.3.5

## 4.3.1 機材オブジェクトから窓口を合成する

合成は一つのオブジェクトが別のオブジェクトを含む関係です。IDをキーとする辞書で検索と一意性を明確にします。

    class LendingDesk:
        def __init__(self):
            self.items = {}

        def add_item(self, item):
            if item.item_id in self.items:
                raise ValueError("duplicate item ID")
            self.items[item.item_id] = item

        def find_item(self, item_id):
            return self.items.get(item_id.strip())

        def loan_item(self, item_id, borrower_id):
            item = self.find_item(item_id)
            if item is None:
                raise KeyError(item_id)
            item.loan_to(borrower_id)

値は属性のコピーではなく、実際の`EquipmentItem`です。

## 4.3.2 情報を持つクラスへ責任を置く

窓口は「どの機材か」、機材は「その遷移が許されるか」を判断します。窓口は検索後に`item.loan_to()`を呼び、二重貸出規則を複製しません。

    desk.loan_item("E001", "M014")
    # desk: E001を探す
    # item: 自分の状態を検証して変更する

## 4.3.3 存在しない対象と不正な状態を区別する

未知IDは対象が存在しないので`KeyError`、存在する機材が遷移を拒否すると`ValueError`です。違いが監査結果の理由になります。

    try:
        desk.loan_item("E999", "M014")
    except KeyError:
        print("unknown item")

    try:
        desk.loan_item("E002", "M014")
    except ValueError:
        print("item cannot be loaned")

## 4.3.4 現在状態から集計値を導く

利用可能件数は各機材の現在状態から計算できます。別の合計変数を保存すると、すべての遷移で二か所を更新する必要があります。

    def summary(self):
        available = sum(item.is_available() for item in self.items.values())
        return {
            "total_items": len(self.items),
            "available_items": available,
            "loaned_items": len(self.items) - available,
        }

## 統合練習：章末課題へつながる軽い予行演習

**章末課題との接続：**

部屋予約を所有する小さな予定表を作り、集合の規則を一件の予約から分離します。

### 作るもの

`Schedule.add(reservation)`、`find(booking_id)`、`count_by_status()`を定義し、ID重複を拒否します。`Reservation`は一件だけを担当します。

### 完成条件

- `Schedule`は`Reservation`オブジェクトのリストを持ちます。

- IDで検索し、不在なら`None`を返します。

- 状態件数は現在のオブジェクトから作る辞書です。

### 解答への手引き

1.  `Schedule.__init__`で集合を初期化します。

2.  追加前に`find`を呼びます。

3.  予約をループして件数を作ります。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> **模範解答と解説**
>
> ```
> class Reservation:
>     def __init__(self, booking_id, status="PENDING"):
>         self.booking_id = booking_id
>         self.status = status
>
>
> class Schedule:
>     def __init__(self):
>         self.reservations = []
>
>     def find(self, booking_id):
>         for reservation in self.reservations:
>             if reservation.booking_id == booking_id:
>                 return reservation
>         return None
>
>     def add(self, reservation):
>         if self.find(reservation.booking_id) is not None:
>             raise ValueError("duplicate booking")
>         self.reservations.append(reservation)
>
>     def count_by_status(self):
>         counts = {}
>         for reservation in self.reservations:
>             counts[reservation.status] = counts.get(reservation.status, 0) + 1
>         return counts
> ```
>
> **代表的な確認結果**
>
>     A duplicate booking raises ValueError
>
> 合成により、一件の規則を`Reservation`へ、集合全体の規則を`Schedule`へ割り当てます。

### 値を変えて再確認する

一件の状態を変更し、重複した合計を保存せずに`count_by_status()`へ反映されることを確認します。

## まとめ

このレッスンと統合練習を終えると、次のことができます。

- 合成により調整役がドメインオブジェクトの集合を持つ。
- 委譲により検索・一意性・状態遷移を適切な場所へ置く。
- 現在状態から集計し、古くなる保存値を作らない。

## 次のレッスンへ

次はCSVとオブジェクトの境界を作り、受理・拒否の結果を一件ずつ残します。
