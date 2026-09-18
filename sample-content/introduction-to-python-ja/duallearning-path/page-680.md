## 課題の状況

地域学習センターでは共用機材を貸し出しています。始業時に在庫CSVと、その日に受け付けた依頼CSVが届きます。依頼を受付順に処理し、不可能な状態変更を拒否し、閉所時の在庫と全依頼の結果を残すプログラムが必要です。

## 完成させるプログラム

Python Labの`projects/equipment-lending/equipment_lending.py`を開き、TODOを完成させます。空のファイルから作りません。

1.  在庫から5個の`EquipmentItem`を作る。
2.  6件の依頼を入力順に窓口へ渡す。
3.  受理した依頼だけで状態を変更し、拒否理由も残す。
4.  更新後在庫と依頼結果を別々のCSVへ保存する。
5.  集計を画面表示する。

## 配布される原本

`data/equipment_inventory.csv`は開始時の在庫、`data/lending_requests.csv`は当日の依頼です。どちらも編集・上書きしません。

`equipment_inventory.csv`:

    item_id,name,category,borrower_id
    E001,Laptop 01,Computer,
    E002,Portable Projector,Presentation,M021
    E003,Network Practice Kit,Networking,
    E004,Training Camera,Media,M031
    E005,Portable Speaker,Audio,

`lending_requests.csv`:

    request_id,action,item_id,borrower_id
    R001,LOAN,E001,M014
    R002,LOAN,E002,M022
    R003,RETURN,E004,
    R004,LOAN,E005,M018
    R005,RETURN,E003,
    R006,LOAN,E999,M014

## 処理規則

- 空の必須値、ID重複、二重貸出、未貸出品の返却、未知ID、未知操作を拒否する。
- 拒否された依頼は機材状態を変えず、後続依頼を続ける。
- 結果は依頼順、在庫はitem_id順で保存する。
- 公開されたクラス・関数・引数・定数・出力列名を変更しない。

## 配布データで期待される結果

    EQUIPMENT LENDING REPORT
    REQUESTS: 6
    ACCEPTED: 3
    REJECTED: 3
    TOTAL ITEMS: 5
    AVAILABLE ITEMS: 2
    LOANED ITEMS: 3

受理はR001・R003・R004、拒否はR002・R005・R006です。最終利用者はE001=M014、E002=M021、E003=空、E004=空、E005=M018です。

## 実装する順序

1.  `EquipmentItem`の生成・状態・遷移・行変換。
2.  `LendingDesk`の収集・検索・委譲・集計・在庫保存。
3.  `load_inventory`と`process_requests`。
4.  `save_results`と`run_project`。

## 手動確認と自動確認

    python projects/equipment-lending/equipment_lending.py

出力を自分で確認してから、自動確認を実行します。

    python projects/equipment-lending/check_equipment_lending.py

生成された二つのCSVも開きます。全項目が`OK`となり、最後に`ALL TESTS PASSED`と表示されたら完成です。提出は`equipment_lending.py`だけです。
