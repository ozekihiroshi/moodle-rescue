## 学習センターデータの発展

このコースでは、個人情報を含まない架空の運営データを使います。小さな既知のデータで処理を確認してから、同じスキーマの大きなデータへ進みます。

### 24行の練習用ファイル

`data/learning-centres-practice.csv`には24件のセンター・月レコードと10列があります。空欄、地区名の表記ゆれ、出席者数を上回る修了者数を意図的に含みます。3.1では存在を確認し、3.2で選択し、3.3で品質問題として扱います。

    month,centre_id,centre_name,district,course,registered,attended,completed,training_hours,material_cost
    2026-01,C001,Gaborone Learning Centre,South,Python Foundations,32,28,24,24,410.50

### 10,000行から250,000行へ

付属の生成スクリプトは同じシードから同じ架空データを作ります。まず10,000行で処理と件数を検証し、後のスケーリング課題で250,000行以上へ進みます。

    python data/generate-learning-centre-data.py --rows 10000 --output learning-centres-10000.csv
    python data/generate-learning-centre-data.py --rows 250000 --output learning-centres-large.csv

大きなデータでも、読み込んだパス、shape、列名、型、欠損数を最初に記録する手順は変わりません。
