# Labの独立性とMoodle連携の初回設定

Labの起動・更新・作業保存はLabリポジトリが担当し、受講登録・教材活動・提出・評定はMoodleが担当します。Moodleへ接続する部分だけをLTI 1.3登録情報として受け渡します。Lab本体の変更やMoodleコースの複製は、連携の前提ではありません。

## 管理者が最初に一度行うこと

1. Labを単独で検証します。Javaは`java-lab-rescue`の`python3 scripts/setup.py standalone`、`python3 scripts/lab.py --standalone build`、`python3 scripts/lab.py --standalone up`を使用。8088でユーザー`learner`と生成パスワードで入れます。検証用領域はMoodle利用者の領域と分離します。
2. Moodleに外部ツールを登録し、接続情報を出力します。以下はこのリポジトリ直下で実行します。

```sh
python3 scripts/register-lab.py --lab java \
  --url http://localhost:8087 --name "Java Lab" \
  --output build/lab-connections/java.json --apply
```

`--apply`を付けたときだけ未登録のツールを作成します。同名の正しい登録があれば同じClient IDを再利用し、コース・学生・課題は作成/変更しません。名前が重複する場合や同名で別URLが登録されている場合は停止します。既存ローカル試作を接続する場合は`--name "Java Lab Prototype"`とし、`--apply`なしで既存登録を書き出せます。

出力先に接続情報がすでにある場合は、新規登録をせず既存登録の確認だけを行います。別の登録には別の出力ファイルを指定してください。

別のMoodleコンテナなら`--container`で指定します。PHPは必ず`www-data`で実行します。Moodle管理画面から手動登録する従来手順も使用できます。

3. Java Lab側で接続情報を取り込みます。

```sh
cd ../java-lab-rescue
python3 scripts/setup.py connect ../moodle-rescue/build/lab-connections/java.json
python3 scripts/lab.py check
python3 scripts/lab.py build
python3 scripts/lab.py up
```

既存`.env`の保存先・ネットワーク名は維持します。すでに別のClient ID/Moodle URL/ポートを使う環境へ上書きする操作は拒否します。別の接続には別環境を用意します。設定変更やイメージ更新時は、学生へ保存を依頼して先に`stop`してください。

現在の自動取り込みはローカルHTTP連携用です。外部ツール登録の出力形式はHTTPSも扱いますが、公開用設定・TLS・受入試験は別手順で、AWS配備は今回の対象外です。

## 教師が単元ごとに行うこと

Moodleコースで「外部ツール」活動を追加し、登録したJava Labを選びます。単元のURL例:

```text
http://localhost:8087/hub/user-redirect/ide/?folder=/home/jovyan/work/java-intro/course-v2/lesson-1-1
```

Labイメージにその初期ファイルが含まれることを確認します。開き方は新しいウィンドウ、学生は受講登録したMoodleアカウントで起動します。Lab活動の追加だけで、教材本文・標準課題・採点基準が自動作成されるわけではありません。提出先の標準課題を別に作成します。

## 確認すること

サービスの正常性確認と、学生の利用確認を区別します。`lab.py check`は設定、`checks/local.py`は稼働状態を確認します。最後に学生アカウントで「Moodle→実習→編集・実行→保存→再起動→提出」を通します。異なる学生が同じファイル領域を開かないこと、ゲストが実習できないことも確認します。

単独モードは`python3 checks/standalone.py`でログイン・IDE・Java実行・停止再開後の保持を検査できます。この試験は単独検証環境を一度停止します。公開サービスには使いません。

## Python Labとの共通化と残る差

両LabともMoodleで登録情報を出力し、Labで同じ形のコマンドで取り込みます。

| 項目 | Python Lab | Java Lab |
|---|---|---|
| 登録オプション | `--lab python --url http://localhost:8086 --name "Python Lab"` | `--lab java --url http://localhost:8087 --name "Java Lab"` |
| 出力ファイル | `python.json` | `java.json` |
| 各Labで取り込み | `python3 scripts/setup.py connect <python.json>` | `python3 scripts/setup.py connect <java.json>` |
| 基本起動 | `sh scripts/start-local.sh` | `python3 scripts/lab.py up` |

Pythonの初回LTI導入では`.env`をコピーする前に取り込みます。既存の単独ログイン環境の認証方式変更は拒否します。直接提出を使用する既存Python環境は、従来の`start-lti-submit-local.sh`で起動してください。保存領域や提出設定は取り込みで変更しません。Pythonの[導入案内](https://github.com/ozekihiroshi/python-lab-rescue/blob/codex/lab-connection-import/docs/connection.md)は専用ブランチで提供しています（main統合前）。Javaは標準課題へのファイル提出を維持します。

JSONはschema_version=1、kind、platform（issuer/authorize_url/jwks_url/client_id/deployment_id）、tool（base_url/login_url/callback_url/target_url）を持ちます。パスワードや署名秘密鍵を含みませんが、実環境設定としてGit管理外に保管します。

検証（2026-09-22）: ローカルMoodleで新規登録・同条件の再実行（同じID）・異なるURLへの上書き拒否を確認。検証専用登録は活動から未使用であることを確認して削除しました。既存Java/Python登録は読み取り出力のみ。Java側で既存接続の取り込み、単独ログイン・IDE・Java実行・停止再開後の保持、既存Moodle受講者のLTI起動を確認しました。

Python取り込み検証（2026-09-22）: 6件の自動テストが成功。既存設定への2回の取り込みが同じ結果となり、直接提出を含むCompose全体の解決済み構成が取り込み前後で一致しました。

Pythonの署名付きLTI起動・教材ファイル・ライブラリの利用も確認しました。既存試験コースは学生非公開のため、この起動試験は管理者アカウントで実施しています。
