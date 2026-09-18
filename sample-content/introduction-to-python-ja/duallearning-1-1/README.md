# Python 1.1 — Dual Learning対応の単元試行版

公開済み日本語コース`PYAI-INTRO-JA`のレッスン1.1を、Markdownを正本とする独立した自学自習単元に作り直した試行版です。英語版`PYAI-INTRO`が教材体系の正本である位置付けは変えません。既存の二つの公開コースは更新・置換しません。

## 学習者の経路

1. `01-prepare.md`でPython Labの実行・保存と、結果を予想する作業を確認する。
2. `02-lesson.md`を読み、値・式・出力、計算順、変更後の整合をコードで確かめる。統合練習の模範解答は必要な時に開く。
3. 同じ単元のPython Lab 1.1でコードを実行・保存する。
4. `03-review.md`で別の数を使い、自己確認と復習を行う。

LessonMarkの三活動は手動完了、Lab起動は完了・提出・採点の代用ではありません。単元の概要には経路と困った時の戻り先だけを書き、教材本文を二重登録しません。

## ローカルでの再現

`format_duallearning`の[`v0.1.0-alpha2`](https://github.com/ozekihiroshi/moodle-format_duallearning/releases/tag/v0.1.0-alpha2)を`D:\workspace\moodle-format_duallearning`相当の兄弟ディレクトリへ配置します。既存のLessonMark、Python Lab向けLTI 1.3、Python日本語版レッスン1.1、`Dual Learning 検証用`カテゴリがあるローカルMoodleを前提にします。

WSL上で、`docker-compose.local.yml`、`docker-compose.lessonmark.yml`、`docker-compose.python-duallearning.yml`の順にComposeを重ねて起動します。MoodleのアップグレードCLIはWebユーザー`www-data`で実行し、root所有キャッシュを作らないでください。

```sh
docker compose -f docker-compose.local.yml -f docker-compose.lessonmark.yml \
  -f docker-compose.python-duallearning.yml up -d --no-deps --force-recreate moodle moodle-cron
docker exec -u www-data moodle-rescue-local php /var/www/html/admin/cli/upgrade.php --non-interactive
```

既存コースを上書きしない作成スクリプトは`create-python-duallearning-unit11.php`です。作成後は`verify-python-duallearning-unit11.php`でMoodle内のMarkdown、活動順、LTI、完了設定を検証します。検証用教師と学生の登録は別スクリプトで行い、配布バックアップには含めません。

## 配布範囲と制限

[`distribution/`](distribution/README.md)のMBZは一単元だけを含みます。第1〜6章のDual Learning対応完成版ではありません。利用者、完了、成績、提出は含めません。サイト固有のLTI秘密情報を避けるため、**Python Lab活動自体は配布MBZから除外**します。復元先では、単元を学習者へ公開する前に、そのサイトのPython Lab LTI 1.3登録を行い、1.1のNotebookを開く外部ツール活動を本文と復習の間へ追加してください。

原作教材はCC BY 4.0、作成・検証スクリプトはGPL-3.0-or-laterです。詳細はリポジトリの`LICENSE-CONTENT.md`を参照してください。
