## 導入

**ファイルはレコードを持ち、実行中のプログラムはオブジェクトを使います。** CSVはメソッドを保存できません。行からオブジェクトを作り、メソッドを通して処理し、最後に行へ戻します。

## このレッスンの到達目標

- CSV行をオブジェクトへ、オブジェクトを保存行へ変換する。
- 想定された拒否後も続け、依頼ごとの結果を残す。
- 処理手順とドメイン規則を分ける。
- 別データでテストし、原本を保護する。

**学習経路:** 必須 4.4.1〜4.4.5｜統合練習 4.4.6

## 4.4.1 行を読んでオブジェクトを生成する

`DictReader`の文字列をコンストラクタへ渡し、直接コードから作る場合と同じ検証を働かせます。

    def load_inventory(path):
        desk = LendingDesk()
        with Path(path).open(newline="", encoding="utf-8") as handle:
            for row in csv.DictReader(handle):
                item = EquipmentItem(
                    row["item_id"], row["name"], row["category"], row["borrower_id"]
                )
                desk.add_item(item)
        return desk

窓口への追加でID重複も検証されます。読込処理から内部属性へ抜け道を作りません。

## 4.4.2 オブジェクトを一行の保存レコードへ変換する

`to_record()`は逆方向の境界です。メモリ内の`None`をCSVの空欄へ変換します。一件の形は機材、集合の保存は窓口が担当します。

    def to_record(self):
        return {
            "item_id": self.item_id,
            "name": self.name,
            "category": self.category,
            "borrower_id": self.borrower_id or "",
        }

## 4.4.3 依頼を処理して監査結果を残す

業務上の拒否は想定された結果です。公開された`ValueError`と`KeyError`だけを捕捉し、REJECTEDを記録して後続依頼へ進みます。

    def process_requests(desk, path):
        results = []
        with Path(path).open(newline="", encoding="utf-8") as handle:
            for row in csv.DictReader(handle):
                try:
                    if row["action"] == "LOAN":
                        desk.loan_item(row["item_id"], row["borrower_id"])
                    elif row["action"] == "RETURN":
                        desk.return_item(row["item_id"])
                    else:
                        raise ValueError("unknown action")
                    status = "ACCEPTED"
                except (ValueError, KeyError):
                    status = "REJECTED"
                results.append({"request_id": row["request_id"], "status": status})
        return results

予期しない不具合まで`except Exception`で隠しません。

## 4.4.4 規則を複製せず作業を接続する

`run_project()`は在庫読込、依頼処理、二つの保存、集計という仕事の順に読みます。貸出規則を書き直す場所ではありません。

    desk = load_inventory(inventory_path)
    results = process_requests(desk, requests_path)
    desk.save_inventory(inventory_output)
    save_results(results, results_output)

## 4.4.5 別データでテストして原本を守る

E001〜E005を直接書いたプログラムは解答ではありません。X1・X2と別順序の依頼で、結果、最終状態、出力順、原本不変を確認します。

    before = inventory_path.read_bytes()
    result = run_project(inventory_path, requests_path, out1, out2)
    assert inventory_path.read_bytes() == before
    assert result["accepted"] + result["rejected"] == result["requests"]

## 統合練習：章末課題へつながる軽い予行演習

**章末課題との接続：**

備品貸出ではなく自転車レンタルを使い、クラスとCSVの境界を練習します。

### 作るもの

`Rental`へ`to_record()`と`from_record()`クラスメソッドを作ります。2件をCSVへ保存して再読込し、ID、自転車ID、利用中状態が往復後も保たれることを確認します。

### 完成条件

- CSV行は文字列、オブジェクトの状態は真偽値です。

- 直列化は業務メソッドへ散らさず境界へ置きます。

- 入出力の見出しを安定させます。

### 解答への手引き

1.  真偽値は`true`または`false`で保存します。

2.  読込時に明示的に解釈します。

3.  再読込後に属性のタプルを比較します。

**まず自分で作り、実行結果を確認してから模範解答を開いてください。**

> [!ANSWER]
> **模範解答と解説**
>
> ```
> class Rental:
>     def __init__(self, rental_id, bicycle_id, active):
>         self.rental_id = rental_id
>         self.bicycle_id = bicycle_id
>         self.active = active
>
>     def to_record(self):
>         return {"rental_id": self.rental_id, "bicycle_id": self.bicycle_id,
>                 "active": "true" if self.active else "false"}
>
>     @classmethod
>     def from_record(cls, row):
>         value = row["active"].strip().lower()
>         if value not in {"true", "false"}:
>             raise ValueError("invalid active value")
>         return cls(row["rental_id"], row["bicycle_id"], value == "true")
>
> rental = Rental.from_record(Rental("R01", "B07", True).to_record())
> assert (rental.rental_id, rental.bicycle_id, rental.active) == ("R01", "B07", True)
> print("ROUND TRIP OK")
> ```
>
> **代表的な確認結果**
>
>     ROUND TRIP OK
>
> オブジェクトはメモリ上の振る舞いを、レコードは持ち運べる保存形式を提供します。明示的な変換が両方を理解可能にします。

### 値を変えて再確認する

保存行の真偽値を不正値へ変え、`from_record()`が明確な`ValueError`を出すことを確認します。

## まとめ

このレッスンと統合練習を終えると、次のことができます。

- 入力境界で行をオブジェクトへ、出力境界でオブジェクトを行へ変換する。
- 想定された拒否を記録し、予期しない不具合は見えるままにする。
- 処理手順は責任を接続し、規則の第二実装にならない。

## 次のレッスンへ

4.5に必要な概念がそろいました。在庫5件と依頼6件を処理するスターターを完成させます。
