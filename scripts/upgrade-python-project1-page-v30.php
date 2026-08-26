<?php
// Edit only the Project 1.7 brief page for a clearer learner reading flow.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$pagename = $ja ? '1.7 課題仕様と完成条件' : '1.7 Project brief and completion contract';
$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);

if ($ja) {
    $page->intro = '<p>手元の日報をキーボードで入力し、週間報告を作るプログラムを完成させます。</p>';
    $page->content = <<<'HTML'
<div class="python-project">
<h2>1.7 小プロジェクト：週間サポート報告</h2>
<div style="border-left:5px solid #0f6cbf;padding:.8em 1em;background:#eef6fc;margin:1em 0">
<h3 style="margin-top:0">この課題ですること</h3>
<ol>
<li>Python Labで<code>weekly_support.py</code>のTODOを完成させます。</li>
<li>下のサンプル日報をキーボードから入力し、結果を自分で確認します。</li>
<li><code>check_weekly_support.py</code>を実行し、8項目を確認します。</li>
<li><code>ALL TESTS PASSED</code>になった<code>weekly_support.py</code>だけを提出します。</li>
</ol>
</div>

<h3>課題の状況</h3>
<p>学習センターの担当者は、毎日の業務終了時に「受け付けた問い合わせ件数」と「その日のうちに解決した件数」を日報へ記録しています。週末には、手元の日報を見ながら月曜日から金曜日までの数字をプログラムへ入力します。プログラムは5日分を集計し、週間報告を画面に表示します。</p>
<p>担当者は日報の数字を順番に入力します。プログラムは、その後の集計、解決率の計算、対応状況の判定、最繁忙日の特定を自動化します。</p>

<h3>入力方法と動作確認用のサンプル日報</h3>
<p>次の表はCSVファイルでも、プログラム内へ直接記述するデータでもありません。完成したプログラムを確認するための架空の日報です。<code>weekly_support.py</code>を実行してから、表を見ながら上から順番に入力します。実際には毎週異なる数字を入力できるプログラムを作ります。</p>
<table class="generaltable"><thead><tr><th>曜日</th><th>問い合わせ件数</th><th>解決件数</th></tr></thead><tbody>
<tr><td>Monday</td><td>12</td><td>10</td></tr><tr><td>Tuesday</td><td>18</td><td>16</td></tr>
<tr><td>Wednesday</td><td>15</td><td>15</td></tr><tr><td>Thursday</td><td>20</td><td>17</td></tr>
<tr><td>Friday</td><td>10</td><td>9</td></tr></tbody></table>
<p>例えば、<code>Monday received:</code>には<code>12</code>、続く<code>Monday resolved:</code>には<code>10</code>を入力します。同じ操作をFridayまで続けます。</p>
<p>実務ではCSVや表計算シートから読み込むこともできますが、まだファイル入出力を学んでいないため、今回は<code>input()</code>と繰り返し処理を使って手入力します。ファイルの読み込みは後の課題で扱います。</p>

<h3>作成するプログラム</h3>
<p>5日分の入力を受け取り、次の項目を計算して表示します。</p>
<ul><li>問い合わせ合計</li><li>解決合計</li><li>未解決件数</li><li>解決率</li><li>対応状況</li><li>最も問い合わせが多かった曜日</li></ul>
<p><strong>実装上の約束：</strong>この課題では繰り返し処理を練習するため、月曜日から金曜日までの入力を一つの<code>for</code>ループで処理します。同じ入力コードを5回繰り返して書かないでください。</p>
<table class="generaltable"><thead><tr><th>条件</th><th>仕様</th></tr></thead><tbody>
<tr><td>未解決件数</td><td>問い合わせ合計 − 解決合計</td></tr>
<tr><td>解決率</td><td>解決合計 ÷ 問い合わせ合計 × 100。小数第1位まで表示</td></tr>
<tr><td>解決率90%以上</td><td><code>ON TRACK</code></td></tr>
<tr><td>解決率80%以上90%未満</td><td><code>REVIEW</code></td></tr>
<tr><td>解決率80%未満</td><td><code>PRIORITY SUPPORT</code></td></tr>
<tr><td>最繁忙日が同数</td><td>最初に現れた曜日</td></tr>
</tbody></table>

<h3>期待される出力</h3>
<p>サンプル日報を入力した結果は次のとおりです。英大文字の項目名は確認プログラムが読み取るため変更しません。</p>
<pre><code>WEEKLY SUPPORT REPORT
TOTAL RECEIVED: 75
TOTAL RESOLVED: 67
UNRESOLVED: 8
RESOLUTION RATE: 89.3%
STATUS: REVIEW
BUSIEST DAY: Thursday</code></pre>

<h3>問い合わせ0件と不正データ</h3>
<ul>
<li>5日間の問い合わせ合計が0件なら、解決率は<code>N/A</code>、状態は<code>NO REQUESTS</code>、最繁忙日は<code>NONE</code>とします。</li>
<li>負の件数、または解決件数が問い合わせ件数を超える値は不正です。</li>
<li>不正な値があってもFridayまで入力を続けます。入力後は見出し、合計、解決率、状態、最繁忙日を表示せず、<code>RESULT: INVALID</code>だけを表示します。</li>
<li>この課題では整数が入力されるものとし、文字や小数の例外処理は後で扱います。</li>
</ul>

<h3>使用するPython</h3>
<p>変数、整数、文字列、<code>input()</code>、<code>int()</code>、<code>print()</code>、f文字列、算術・比較・論理演算子、<code>if / elif / else</code>、<code>for</code>、<code>range()</code>だけで完成できます。関数、リスト、辞書、クラス、ファイル入出力、pandasは必要ありません。</p>

<details><summary><strong>考えるためのヒント</strong></summary>
<ol>
<li>二つの週間合計を、一日の入力ごとにどう更新しますか。</li>
<li>不正な値があったことを、入力終了までどの変数へ記録しますか。</li>
<li>最も多い件数と曜日をどう保持しますか。同数で最初の曜日を残す比較は何ですか。</li>
<li>問い合わせ合計0件で割り算を避けるには、どの処理を先に判定しますか。</li>
<li>80%と90%を正しく区分するには、条件をどの順序で書きますか。</li>
</ol>
<p>Python Labの<code>weekly_support.py</code>には入力の枠とTODOがあります。TODOを完成させ、最後の<code>PROGRAM INCOMPLETE</code>を削除します。</p>
</details>

<h3>確認手順</h3>
<h4>1. 自分で確認する</h4>
<p><code>weekly_support.py</code>を実行し、サンプル日報の10個の値を入力します。「期待される出力」と一致することを確認します。</p>
<p>Python Labのターミナルから実行する場合：</p>
<pre><code>python projects/weekly-support/weekly_support.py</code></pre>
<h4>2. 確認プログラムを実行する</h4>
<pre><code>python projects/weekly-support/check_weekly_support.py</code></pre>
<p>確認プログラムは変更しません。通常の1週間、80%ちょうど、90%ちょうど、80%未満、最繁忙日の同率、問い合わせ0件、解決件数超過、負の件数の8項目を確認します。<code>NG</code>では期待値と実際の値を読み、<code>weekly_support.py</code>だけを修正します。</p>

<h3>提出物と完成条件</h3>
<p>提出するのは完成した<code>weekly_support.py</code>一つです。Notebook、<code>check_weekly_support.py</code>、画面画像、説明文は提出しません。</p>
<ul><li>サンプル日報で期待される週間報告を確認できた。</li><li>8項目がすべて<code>OK</code>となり、最後に<code>ALL TESTS PASSED</code>と表示された。</li></ul>
<p style="display:none">PYAI-V30-PROJECT17-READABLE-BRIEF</p>
</div>
HTML;
} else {
    $page->intro = '<p>Enter figures from five daily records and complete a program that displays a weekly support report.</p>';
    $page->content = <<<'HTML'
<div class="python-project">
<h2>1.7 Mini-project: Weekly support report</h2>
<div style="border-left:5px solid #0f6cbf;padding:.8em 1em;background:#eef6fc;margin:1em 0"><h3 style="margin-top:0">What you will do</h3><ol>
<li>Complete the TODOs in <code>weekly_support.py</code> in Python Lab.</li><li>Enter the sample daily records below and inspect the result yourself.</li>
<li>Run <code>check_weekly_support.py</code> and pass its eight checks.</li><li>Submit only the completed <code>weekly_support.py</code>.</li></ol></div>
<h3>Situation</h3><p>At the end of each day, a learning-centre staff member records received support requests and requests resolved that day. At week end, the staff member reads the Monday-to-Friday daily records and types the figures into the program. The program aggregates them and displays a weekly report.</p>
<h3>Input and sample daily records</h3><p>This table is not a CSV file and is not copied into the program. It is fictional data for checking the completed program. Run <code>weekly_support.py</code>, then type the values from top to bottom. The finished program must accept different figures each week.</p>
<table class="generaltable"><thead><tr><th>Day</th><th>Received</th><th>Resolved</th></tr></thead><tbody>
<tr><td>Monday</td><td>12</td><td>10</td></tr><tr><td>Tuesday</td><td>18</td><td>16</td></tr><tr><td>Wednesday</td><td>15</td><td>15</td></tr><tr><td>Thursday</td><td>20</td><td>17</td></tr><tr><td>Friday</td><td>10</td><td>9</td></tr></tbody></table>
<p>For example, enter <code>12</code> at <code>Monday received:</code>, then <code>10</code> at <code>Monday resolved:</code>, and continue through Friday. This project uses manual input because file input has not yet been studied; later projects will read files.</p>
<h3>Program requirements</h3><p>The staff member enters the figures from the daily records. The program automates the totals, resolution-rate calculation, status decision, and identification of the busiest day. Display totals received and resolved, unresolved count, resolution rate to one decimal place, status, and busiest day. At least 90% is <code>ON TRACK</code>; at least 80% is <code>REVIEW</code>; below 80% is <code>PRIORITY SUPPORT</code>. On a busiest-day tie, keep the first day.</p><p><strong>Implementation contract:</strong> This project practises repetition, so process the Monday-to-Friday inputs with one <code>for</code> loop. Do not write the same input code five times.</p>
<h3>Expected output</h3><pre><code>WEEKLY SUPPORT REPORT
TOTAL RECEIVED: 75
TOTAL RESOLVED: 67
UNRESOLVED: 8
RESOLUTION RATE: 89.3%
STATUS: REVIEW
BUSIEST DAY: Thursday</code></pre>
<h3>Zero requests and invalid data</h3><ul><li>For zero total requests use rate <code>N/A</code>, status <code>NO REQUESTS</code>, and busiest day <code>NONE</code>.</li><li>A negative count or resolved above received is invalid.</li><li>Continue through Friday after an invalid value, then display only <code>RESULT: INVALID</code>; do not display the report heading or result fields.</li><li>Assume integer input in this project. Non-integer exception handling is studied later.</li></ul>
<h3>Python in scope</h3><p>Use variables, integers, strings, <code>input()</code>, <code>int()</code>, <code>print()</code>, f-strings, arithmetic, comparisons, Boolean operators, <code>if / elif / else</code>, <code>for</code>, and <code>range()</code>. Functions, lists, dictionaries, classes, file input, and pandas are not required.</p>
<details><summary><strong>Hints</strong></summary><ol><li>How will you update two weekly totals after each input?</li><li>How will you remember an invalid value until input finishes?</li><li>Which comparison retains the first day on a tie?</li><li>Which case must be handled before division?</li><li>In what order should 80% and 90% be tested?</li></ol><p>The starter has TODOs. Complete them and remove <code>PROGRAM INCOMPLETE</code>.</p></details>
<h3>Checking</h3><h4>1. Check it yourself</h4><p>Run the script, enter the ten sample values, and compare with the expected output.</p><p>From the Python Lab terminal:</p><pre><code>python projects/weekly-support/weekly_support.py</code></pre><h4>2. Run the supplied checker</h4><pre><code>python projects/weekly-support/check_weekly_support.py</code></pre><p>Do not change the checker. It checks a standard week, exactly 80%, exactly 90%, below 80%, a busiest-day tie, zero requests, resolved above received, and a negative count. Change only <code>weekly_support.py</code> when a result is <code>NG</code>.</p>
<h3>Submission and completion</h3><p>Submit only <code>weekly_support.py</code>, not the Notebook, checker, screenshot, or an essay. You are finished when the sample output is correct and all eight checks are <code>OK</code>, ending with <code>ALL TESTS PASSED</code>.</p>
<p style="display:none">PYAI-V30-PROJECT17-READABLE-BRIEF</p></div>
HTML;
}

$page->introformat = FORMAT_HTML;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);
rebuild_course_cache($course->id, true);
echo json_encode(['shortname' => $shortname, 'pageid' => (int)$page->id, 'name' => $pagename, 'marker' => 'PYAI-V30-PROJECT17-READABLE-BRIEF'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
