# Python 1.1 — Dual Learning自学自習試行版

このディレクトリの`.mbz`は、Markdown / LessonMarkの予習・本文・復習を含む、**一単元だけ**のMoodleコースバックアップです。公開済みの第1〜6章の日本語コースを置き換えるものではありません。

復元にはMoodle 5.2、`format_duallearning` 0.1.0-alpha2、LessonMark、および各サイトで設定したPython Lab LTI 1.3が必要です。**Labの外部ツール活動はバックアップに含めていません。** Moodle標準バックアップにサイト固有のLTI `password` が含まれることを監査で確認したためです。復元後、学習者へ公開する前に、単元内の本文と「03 確認と復習」の間へ、そのサイトの`ja/01_programs_values_output.ipynb`を開く外部ツールを追加してください。完成後、学生アカウントでLab起動と復帰を試してください。

バックアップは利用者、受講履歴、提出物、成績を除外して生成します。初回の復元先は新規コースを選び、既存の公開Pythonコースへ上書きしないでください。原作教材はCC BY 4.0です。

復元手順は、Moodleの「コースをリストア」でこのMBZを選び、**新しいコースとして**復元します。復元後は管理者がPython LabのLTI活動を追加し、学生アカウントで01→02→Lab→03を通して確認してからコースを公開します。Lab未設定のまま公開すると01の案内から先に進めません。

配布物の整合性は`SHA256SUMS`で、構成・Markdown・秘密値の有無は`verify-python-duallearning-unit11-distribution.py`で検証できます。`manifest.json`は単元の範囲と復元試験結果を記録します。
