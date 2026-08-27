# Pythonによるデータ活用：AI時代の基礎

このディレクトリは、Moodleコース`PYAI-INTRO-JA`の日本語教材ソースと
配布物を管理します。英語版`PYAI-INTRO`を正本とする公式日本語適応版です。
実行時の言語切替や逐語訳ではなく、日本語で学ぶ人に合わせて説明を
作り直した独立コースです。

## 現在の公開コース

`0.1.0-alpha.1`を[`distribution/`](distribution/README.md)で公開します。
Chapter 0からChapter 6までの現在の教材を含み、利用者アカウント、受験履歴、
提出履歴などの利用者依存データは除外しています。

英語版と日本語版は、学習経路、到達目標、習熟確認、プロジェクトの進行、
Python Labの利用方法を対応させます。ただし、今後の修正は最初に英語正本へ
反映し、その後、日本語として自然な説明へ適応します。

## 検証

```sh
python3 scripts/verify-python-course-distribution.py \
  sample-content/introduction-to-python-ja/distribution/python-for-data-foundations-ai-era-ja-0.1.0-alpha.1.mbz \
  --shortname PYAI-INTRO-JA --language ja

sh scripts/verify-python-sample-distribution-ja.sh
```

Moodleバックアップを復元してもPython Lab自体やLTI 1.3の秘密情報は
導入されません。利用するサイトごとにPython Labを構築し、外部ツールを
登録してください。

## ライセンス

オリジナル教育コンテンツはCC BY 4.0、実行可能な生成・検証ツールは
GPL-3.0-or-laterです。範囲と推奨表示は
[`LICENSE-CONTENT.md`](../../LICENSE-CONTENT.md)を参照してください。
