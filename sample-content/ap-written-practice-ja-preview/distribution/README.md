# IPA AP public preview course 0.1.0-alpha.1

Moodleコース「応用情報技術者試験 過去問題学習 — 令和7年度春期 午後・公開体験版（問1・問2）」（IPA-AP-WRITTEN-JA-PREVIEW）を、新しいコースとして復元できる配布物です。

## 内容

- ipa-ap-written-practice-ja-preview-2025-spring-0.1.0-alpha.1.mbz
- manifest.json
- SHA256SUMS
- LICENSE.txt

このバックアップはMoodle 5.2.2で作成し、ユーザーおよびユーザー依存データを除外しています。案内、問1、問2のLessonMark活動3件と、公式問題ページ画像10枚を含みます。

## 復元

1. LessonMark 0.2系をインストールします。
2. 「サイト管理 > コース > コースをリストアする」を開きます。
3. バージョン付きMBZを、新しいコースとして復元します。
4. コース名に「公開体験版（問1・問2）」と表示されることを確認します。
5. 「参加者 > 登録方法」で、パスワードなしゲストアクセスを明示的に有効化します。
6. ログアウト状態またはゲスト利用者として、案内、問1、問2だけを表示できることを確認します。

バックアップ内にはパスワードなしゲスト登録方法が含まれますが、Moodleは新しいコースへの復元時に安全のため無効化します。必ず復元後に管理画面から有効化してください。CLIを使う場合は、復元後のcourse IDを指定して次を実行できます。

    docker compose --env-file .env exec -T moodle runuser -u www-data -- php -- --courseid=COURSE_ID < scripts/enable-ipa-ap-preview-guest.php

Moodleサイト全体でゲストログインが無効な場合、コース側の設定だけでは公開されません。必要に応じてサイトポリシーを確認してください。自動ゲストログインはサイト全体へ影響するため、運用方針を確認してから設定します。

## CLIから復元

ローカルComposeでは、カテゴリーIDを第1引数に指定します。省略時は1です。

    sh scripts/restore-ipa-ap-preview-distribution.sh 1

本番Composeでは、LessonMarkソースマウント用オーバーライドを空にします。

    COMPOSE_FILE=docker-compose.yml IPA_AP_COMPOSE_OVERRIDE= sh scripts/restore-ipa-ap-preview-distribution.sh 1

復元コマンドが表示したcourse IDを使い、ゲスト公開を明示的に有効化できます。

    docker compose --env-file .env exec -T moodle runuser -u www-data -- php -- --courseid=COURSE_ID < scripts/enable-ipa-ap-preview-guest.php

## 検証

WSLまたはLinuxでリポジトリのルートから実行します。

    sh scripts/verify-ipa-ap-preview-distribution.sh

## Alpha版の制限

作業答案は現在のブラウザ内だけに保存され、Moodleへ提出・採点されません。共有PCではブラウザデータの扱いを案内してください。公式試験資料にはIPAの利用条件が適用されます。
