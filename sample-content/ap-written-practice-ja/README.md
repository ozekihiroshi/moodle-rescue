# 応用情報技術者試験 過去問題学習 — 令和7年度春期 午後（日本語・パイロット版）

IPAが公開する応用情報技術者試験の過去問題を素材に、「公式問題を読む → 同じページで解答する → 直下の公式解答例と解説を開く → 根拠を公式問題へ戻って確認する」という学習をMoodle上に構成する教材です。

- 対象：令和7年度 春期 応用情報技術者試験 午後 問1〜問11
- 公式問題：IPA公式問題冊子の各問を、該当ページ画像として忠実に表示
- 学習画面：LessonMark（各問につき一つの活動）
- 解答保持：現在のブラウザ内だけに保存する未採点の作業答案
- 状態：人による再確認を前提とするパイロット版

## 教材構成：V3 原文・同一ページ自己確認版

V3では、各問の公式問題ページ、設問ごとの作業答案欄、折りたたまれた公式解答例・解説、問題を深く読む考察を、一つのLessonMark活動内へ順番に配置します。学習者は別のQuizページへ往復せず、答案を入力した直後でも、一通り解いた後でも、任意のタイミングで解答を開けます。

公式問題の正本はIPA公式問題冊子のページ画像です。問題文は教材側で要約・再構成しません。公式解答例と教材独自の解説・考察例も表示上で区別します。

答案はMoodleへ提出・採点されず、教師にも表示されません。現在のブラウザのローカルストレージだけに、MoodleユーザーとLessonMark活動を区別して保存されます。採点、受験履歴、修了条件、教師による確認が必要な学習にはMoodle Quizまたは課題を別途使用してください。

この構成には、`RESPONSE`、`CHOICE`、`ANSWER`自己確認ブロックを備えたLessonMark 0.2系が必要です。既定のコース短縮名は `IPA-AP-WRITTEN-JA-V3` です。

## 収録範囲

| 問 | 主な分野 | 題材 | 問題冊子ページ |
|---:|---|---|---|
| 1 | 情報セキュリティ | サイバー攻撃への対策 | 6〜10 |
| 2 | 経営戦略 | 中期事業計画と多角化戦略 | 12〜16 |
| 3 | アルゴリズム・プログラミング | スライドパズルと幅優先探索 | 18〜23 |
| 4 | システムアーキテクチャ・クラウド | BEMSのクラウド移行 | 24〜28 |
| 5 | ネットワーク | 社内LANの障害対応 | 30〜34 |
| 6 | データベース | 販売管理データベースの設計とSQL | 36〜39 |
| 7 | 組込み・ソフトウェア設計 | 電動キックボード共有システムの設計 | 40〜45 |
| 8 | システム開発・エラーハンドリング | CRMシステムのエラーハンドリング | 46〜50 |
| 9 | プロジェクトマネジメント | CCPMによるプロジェクト管理 | 52〜56 |
| 10 | サービスマネジメント | クラウド時代の容量・能力管理 | 58〜62 |
| 11 | システム監査 | 勤務管理システムの監査 | 64〜67 |

ページ対応と公式解答例は `course-manifest-v3.json` と `question-catalog-v3.json` に機械可読な形でも保持します。

## ローカルMoodleを準備する

WSL上でリポジトリのルートから実行します。Docker Desktopは使用しません。

```sh
sh scripts/sync-plugins.sh
docker compose -f docker-compose.local.yml -f docker-compose.lessonmark.yml up -d --build
docker compose -f docker-compose.local.yml -f docker-compose.lessonmark.yml exec -T moodle \
  runuser -u www-data -- php admin/cli/upgrade.php --non-interactive
```

`docker-compose.lessonmark.yml` は、隣接するLessonMark作業ツリーをローカルMoodleへ読み取り専用でマウントする開発・検証用オーバーライドです。

## V3を登録・更新・検証する

初回登録：

```sh
sh scripts/apply-ipa-ap-source-study-course.sh
```

ソースMarkdownを変更した後、又は問を追加した後の冪等同期：

```sh
sh scripts/update-ipa-ap-source-study-course.sh
```

検証：

```sh
docker compose -f docker-compose.local.yml -f docker-compose.lessonmark.yml exec -T moodle \
  runuser -u www-data -- php < scripts/verify-ipa-ap-source-study-course.php
```

検証では、LessonMark 12活動（案内1＋問1〜11）、公式画像55枚、自己確認欄、解答アコーディオン、主要な公式解答例、独立Quizが存在しないことを照合します。

出典と利用条件は、このディレクトリの `THIRD-PARTY-NOTICES.md` およびリポジトリ直下の `THIRD-PARTY-NOTICES.md` を参照してください。
