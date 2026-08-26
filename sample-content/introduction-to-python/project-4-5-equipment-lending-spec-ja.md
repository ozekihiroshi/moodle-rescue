# プロジェクト4.5 — 共用機材貸出窓口

用意済みの `equipment_lending.py` を完成させます。空のファイルから作りません。

プログラムは次を読み込みます。

- `data/equipment_inventory.csv`：開始時の在庫5件
- `data/lending_requests.csv`：受付順の貸出・返却依頼6件

二つの原本を変更せず、全依頼を順番に処理し、不正な遷移では状態を変えず、次を作成します。

- `output/equipment_inventory_after.csv`
- `output/lending_results.csv`

期待値は依頼6、受理3、拒否3、全機材5、利用可能2、貸出中3です。

TODOを EquipmentItem（1〜6）、LendingDesk（7〜12）、ファイル・依頼処理（13〜16）の順に実装します。Ctrl+Sで保存し、プログラムを実行して二つの出力を確認後、次を実行します。

```text
python projects/equipment-lending/check_equipment_lending.py
```

`ALL TESTS PASSED` が表示されたら完成です。提出は `equipment_lending.py` だけです。
