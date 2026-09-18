## 導入

**信頼できる状態を保てて初めて、オブジェクトは役立ちます。** 一つの機材が利用可能であると同時に利用者へ貸出中であってはいけません。二重貸出も、貸出中でない機材の返却も拒否し、拒否後は元の状態を保ちます。

## このレッスンの到達目標

- 生成時に必須属性を検証する。
- 不変条件と、それを守るメソッドの責任を説明する。
- 状態変更より前に不正な遷移を拒否する。
- 成功と拒否をassertで確認する。

**学習経路:** 必須 4.2.1〜4.2.4｜統合練習 4.2.5

## 4.2.1 生成時に正しい初期状態を作る

コンストラクタは最初の境界です。前後空白を取り除き、空のID・名称・分類を拒否します。

    def required(value, label):
        cleaned = value.strip()
        if not cleaned:
            raise ValueError(f"{label} must not be empty")
        return cleaned

    self.item_id = required(item_id, "item_id")

中途半端なオブジェクトを後で直すのではなく、生成完了か例外かのどちらかにします。

## 4.2.2 一つの状態から不変条件を表す

公開操作の完了後も守る条件が不変条件です。`borrower_id is None`なら利用可能、空でない利用者なら貸出中とします。

    def is_available(self):
        return self.borrower_id is None

利用可能フラグを別に保存しないため、利用者との矛盾が生じません。

## 4.2.3 状態変更より先に検証する

拒否条件をすべて確認してから代入します。例外になれば以前の利用者は変わりません。

    def loan_to(self, borrower_id):
        borrower_id = borrower_id.strip()
        if not borrower_id:
            raise ValueError("borrower_id must not be empty")
        if not self.is_available():
            raise ValueError("item is already on loan")
        self.borrower_id = borrower_id

    def return_item(self):
        if self.is_available():
            raise ValueError("item is not on loan")
        self.borrower_id = None

先に代入して後で検証すると、例外を出しても状態が壊れます。

## 4.2.4 成功・拒否・状態保持をテストする

境界のバグは正常処理だけでは見つかりません。成功前後と拒否後を確認します。

    item = EquipmentItem("E001", "Laptop 01", "Computer")
    assert item.is_available()
    item.loan_to("M014")
    try:
        item.loan_to("M021")
    except ValueError:
        pass
    else:
        raise AssertionError("double lending should fail")
    assert item.borrower_id == "M014"

## 統合練習：章末課題へつながる軽い予行演習

**章末課題との接続：**

部屋予約を使い、正しい生成と制御された状態遷移を練習します。

### 作るもの

`PENDING`、`CONFIRMED`、`CANCELLED`を持つ`Reservation`を定義します。確認は待機中のみ、取消は未取消の場合のみ許可します。

### 完成条件

- 空の予約IDと0以下の人数を`__init__`で拒否します。

- メソッドが遷移を守り、不正時は`ValueError`を出します。

- 正常経路と確認の二重実行をテストします。

### 解答への手引き

1.  有効なオブジェクトは必ず`PENDING`で始めます。

2.  変更前に現在状態を確認します。

3.  失敗したメソッドは状態を変えません。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> **模範解答と解説**
>
> ```
> class Reservation:
>     def __init__(self, booking_id, people):
>         if not booking_id.strip() or people <= 0:
>             raise ValueError("invalid reservation")
>         self.booking_id = booking_id.strip()
>         self.people = people
>         self.status = "PENDING"
>
>     def confirm(self):
>         if self.status != "PENDING":
>             raise ValueError("cannot confirm")
>         self.status = "CONFIRMED"
>
>     def cancel(self):
>         if self.status == "CANCELLED":
>             raise ValueError("already cancelled")
>         self.status = "CANCELLED"
>
> r = Reservation("B01", 4)
> r.confirm()
> assert r.status == "CONFIRMED"
> print("STATE OK")
> ```
>
> **代表的な確認結果**
>
>     STATE OK
>
> クラスが不変条件を守るため、すべての呼出側が規則を暗記する必要がなくなります。

### 値を変えて再確認する

新規予約を取消してから確認し、例外後も`CANCELLED`のままであることを確かめます。

## まとめ

このレッスンと統合練習を終えると、次のことができます。

- コンストラクタが正しい初期状態を確立する。
- 不変条件が状態変更へ一つの基準を与える。
- 変更前に検証し、拒否経路もテストする。

## 次のレッスンへ

一件の機材は自分を守れるようになりました。次は窓口が多数の機材を持ち、検索後に処理を委譲します。
