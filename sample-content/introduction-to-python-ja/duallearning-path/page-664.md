## 導入

**すでに動く設計から始めます。** 第2章では一件を辞書で表し、関数へ渡しました。少数の受動的なレコードなら、今もその形が適切です。貸出機材には変化する利用者があり、複数の操作が同じ状態について合意する必要があります。二つの設計を比較して、クラスが何を改善するか判断します。

## このレッスンの到達目標

- 実行コードからクラス・オブジェクト・インスタンスを区別する。
- `__init__`と`self`で独立した個体を作る。
- 左側のオブジェクトから属性とメソッド呼び出しを読む。
- 小さな問題にクラスと辞書＋関数のどちらが適するか判断する。

**学習経路:** 必須 4.1.1〜4.1.4｜統合練習 4.1.5

## 4.1.1 レコードと関数から始める

辞書が状態を保存し、関数が渡されたレコードを変更します。データ、操作、呼び出し側をコード上で特定してください。

    item = {
        "item_id": "E001",
        "name": "Laptop 01",
        "category": "Computer",
        "borrower_id": None,
    }

    def loan_item(record, borrower_id):
        if record["borrower_id"] is not None:
            raise ValueError("already on loan")
        record["borrower_id"] = borrower_id

    loan_item(item, "M014")
    print(item["borrower_id"])

この版は短く明確です。貸出・返却・名称変更・検証・保存が増え、すべての関数が同じキーを知るようになると負担が現れます。データがあるからではなく、関連する状態と規則が増えるからクラスを検討します。

**小確認：**読み取り専用レコードなら、クラスはどの振る舞いを整理するでしょうか。

## 4.1.2 生成方法と振る舞いを一つに定義する

クラスは定義です。クラスを呼び出すとオブジェクトが作られ、その個体をインスタンスと呼びます。`__init__`が初期属性を作り、メソッドが可能な操作を表します。

    class EquipmentItem:
        def __init__(self, item_id, name, category):
            self.item_id = item_id
            self.name = name
            self.category = category
            self.borrower_id = None

        def is_available(self):
            return self.borrower_id is None

        def loan_to(self, borrower_id):
            if not self.is_available():
                raise ValueError("already on loan")
            self.borrower_id = borrower_id

クラス自体にE001やE002が保存されるのではありません。どの機材もどう始まり、一件がどう状態を答えたり変更したりするかを定義します。

## 4.1.3 selfを呼び出しを受けた個体として読む

`first.loan_to("M014")`の実行中、`self`は`first`です。したがって`self.attribute`は呼び出しを受けた個体の属性です。

    first = EquipmentItem("E001", "Laptop 01", "Computer")
    second = EquipmentItem("E002", "Projector", "Presentation")

    first.loan_to("M014")

    print(first.borrower_id)   # M014
    print(second.borrower_id)  # None

secondは変化しません。呼び出しの左側が、どの個体の状態を扱うかを明示します。`EquipmentItem.loan_to(first, "M014")`とも書けますが、通常は個体から呼び出します。

## 4.1.4 属性・メソッド・値の一致・同一性を分ける

`item.name`は状態を保存する属性、`item.is_available()`はオブジェクトを使う操作です。括弧だけでなく、データと操作という役割で読み分けます。

    a = EquipmentItem("E001", "Laptop 01", "Computer")
    b = EquipmentItem("E001", "Laptop 01", "Computer")

    print(a is b)                  # False
    print(a.item_id == b.item_id)  # True

`is`は同じオブジェクトか、`==`は値が等しいかを問います。別々に作った二件が同じIDを持つことはできますが、後で窓口が業務上の重複として拒否します。

## 統合練習：章末課題へつながる軽い予行演習

**章末課題との接続：**

章末では備品貸出を扱います。この軽い練習では会議室をモデル化し、辞書とオブジェクトの違いを確認します。

### 作るもの

`Room('R01', 'Blue Room', 12)`を作ります。3属性を持ち、`R01 — Blue Room (12 seats)`を返す`label()`を用意してください。

### 完成条件

- `__init__`と`label`を持つ一つのクラスを定義します。

- 独立した部屋を二つ作ります。

- 一方の名前変更が他方へ影響しないことを確認します。

### 解答への手引き

1.  コンストラクタ引数を`self`へ保存します。

2.  メソッドは現在のオブジェクトを`self`で受け取ります。

3.  メソッド内で表示せず、ラベルを返します。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> **模範解答と解説**
>
> ```
> class Room:
>     def __init__(self, room_id, name, seats):
>         self.room_id = room_id
>         self.name = name
>         self.seats = seats
>
>     def label(self):
>         return f"{self.room_id} — {self.name} ({self.seats} seats)"
>
> room = Room("R01", "Blue Room", 12)
> print(room.label())
> ```
>
> **代表的な確認結果**
>
>     R01 — Blue Room (12 seats)
>
> オブジェクトは一つの部屋の状態と、その状態に属する振る舞いをまとめます。インスタンスはクラスを共有してもデータは独立します。

### 値を変えて再確認する

20席のR02を作って両方を表示し、R01だけ改名してR02が変わらないことを確認します。

## まとめ

このレッスンと統合練習を終えると、次のことができます。

- クラスは生成方法と関連する振る舞いを定義し、インスタンスはそこから作られた一個体である。
- `self`が呼び出し対象を示すため、各インスタンスの属性は独立する。
- 状態と規則が結び付くとクラスが有効になるが、すべてのレコードに自動的に優れるわけではない。

## 次のレッスンへ

次は不正な生成と状態遷移を変更前に拒否し、オブジェクトの状態を信頼できるものにします。
