# 日本語Python教材 — 自学自習Markdown対応版

ローカルコース42（`PYAI-INTRO-JA-DUAL-PATH`）の第0〜6章の原稿です。英語原本を維持し、日本語版で新方式を先行検証しています。既存公開教材を置き換えるリリース用バックアップではありません。

## 構成

- `chapter-0.json`〜`chapter-6.json`：コピー元活動ID、原稿、原本ハッシュの対応。
- `page-*.md`：本文、統合練習、解答、章末課題。
- `review-*.md`：初期章の追加復習。
- `P1_weekly_support_report_dual_path.ipynb`：現コースへの標準提出を案内する作業用Notebook。学習者プログラムとは別ファイル。

準備→本文→Labで実行・保存→解答と比較→理解度チェック→章末課題という流れです。第3章はA/B/Cの一つを完成・提出し、共通ページで完了マークします。未選択課題は必須扱いしません。

## ローカル反映と検査

`install-python-path-chapter.php`は、このローカル作業コピー専用です。任意の本番コースへ流用しないでください。既存LessonMark IDを維持し、旧ページは非表示で保持します。前の章を再反映すると後続章を非表示にするため、再反映は対象章から第6章まで順番に行い、最後に`finalize-python-path-course.php`を実行します。

コンテナに原稿とスクリプトをコピーした後、www-dataとして次を実行します（パスはコンテナ内）。

```sh
php /tmp/install-python-path-chapter.php /tmp/duallearning-path 6
php /tmp/finalize-python-path-course.php
php /tmp/check-python-path-chapter.php
php /tmp/check-python-path-choice.php
```

追加検証用スクリプトはリポジトリの`scripts/`にあります。

- `verify-python-path-projects.py`：一時コピーで模範解答と第3〜6章の確認プログラムを実行。
- `check-python-path-lab-links.py`：32リンクのソース・検証ユーザー環境でのNotebook実在確認。
- `check-python-path-submission-roundtrip.php`：検証ユーザーの提出を書き込むテスト。通常の読み取り検査とは異なります。既存提出を勝手に上書きするためには使用しません。

検証環境はMoodle 5.2、Dual Learning alpha5（2026091803）、LessonMark 0.3.0-alpha4（2026091800）と順序修正版です。配布時にはプラグインのリリースと依存関係を改めて固定してください。

詳細な検証範囲・制限・公開前作業は[進捗記録](../../../docs/python-self-paced-markdown-progress.md)を参照してください。
