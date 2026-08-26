<?php
// Finalise the save, check, and submission instructions for Project 1.7.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$page = $DB->get_record_select('page', 'course = :course AND content LIKE :marker', [
    'course' => $course->id,
    'marker' => '%PYAI-V30-PROJECT17-READABLE-BRIEF%',
], '*', MUST_EXIST);

// Remove the earlier direct-submission appendix; the final section below
// presents direct and standard Moodle submission as equal valid routes.
$patterns = [
    '~<h3>Submit from Python Lab to Moodle</h3>.*?<p style="display:none">PYAI-V31-DIRECT-SUBMIT</p>~su',
    '~<h3>Python LabからMoodleへ提出する</h3>.*?<p style="display:none">PYAI-V31-DIRECT-SUBMIT</p>~su',
];
$content = preg_replace($patterns, '', $page->content);

if ($ja) {
    $heading = '<h3>提出物と完成条件</h3>';
    $replacement = <<<'HTML'
<h3>保存・確認・提出</h3>
<div style="border-left:5px solid #0f6cbf;padding:.8em 1em;background:#eef6fc;margin:1em 0">
<h4 style="margin-top:0">最初に保存する</h4>
<p><code>weekly_support.py</code>を編集したら、必ず<kbd>Ctrl</kbd> + <kbd>S</kbd>で保存してください。この環境では自動保存を前提にしません。保存せずにページを再読み込みすると、編集内容が失われる場合があります。実行、確認、提出で使われるのは最後に保存されたファイルです。</p>
</div>
<ol>
<li><code>weekly_support.py</code>のTODOを完成させ、<kbd>Ctrl</kbd> + <kbd>S</kbd>で保存します。</li>
<li>プログラムを実行し、サンプル日報の10個の値を入力して、期待される出力と比較します。</li>
<li>次の確認プログラムを実行します。<pre><code>python projects/weekly-support/check_weekly_support.py</code></pre></li>
<li>8項目がすべて<code>OK</code>となり、最後に<code>ALL TESTS PASSED</code>と表示されたら、次のどちらかの方法で提出します。</li>
</ol>

<h4>方法A：Python Labから直接提出する</h4>
<p>Project Notebookの提出セル、または次のコマンドを実行します。確認プログラムがもう一度実行され、合格した場合だけ、最後に保存された<code>weekly_support.py</code>がMoodle課題へ送られます。</p>
<pre><code>python /home/jovyan/work/ja/projects/weekly-support/submit_weekly_support.py</code></pre>
<p><code>提出が完了しました</code>とMoodle提出IDが表示されたら完了です。同じコマンドを再実行すると、現在保存されているファイルで再提出します。</p>

<h4>方法B：Moodleの標準提出画面を使う</h4>
<p>Python Labのファイル一覧で<code>weekly_support.py</code>を右クリックしてダウンロードします。次にMoodleの課題提出画面で、そのファイルをアップロードして提出を確定します。</p>

<h4>提出物と完成条件</h4>
<p>提出するのは完成した<code>weekly_support.py</code>一つです。Notebook、<code>check_weekly_support.py</code>、画面画像、説明文は提出しません。</p>
<ul>
<li>サンプル日報から期待される週間報告を表示できる。</li>
<li>8項目がすべて<code>OK</code>となり、最後に<code>ALL TESTS PASSED</code>と表示される。</li>
<li>方法Aまたは方法Bで、保存済みの<code>weekly_support.py</code>がMoodle課題に提出されている。</li>
</ul>
<p style="display:none">PYAI-V30-PROJECT17-READABLE-BRIEF</p>
<p style="display:none">PYAI-V33-PROJECT17-SAVE-SUBMIT</p>
HTML;
} else {
    $heading = '<h3>Submission and completion</h3>';
    $replacement = <<<'HTML'
<h3>Save, check, and submit</h3>
<div style="border-left:5px solid #0f6cbf;padding:.8em 1em;background:#eef6fc;margin:1em 0">
<h4 style="margin-top:0">Save first</h4>
<p>After editing <code>weekly_support.py</code>, always press <kbd>Ctrl</kbd> + <kbd>S</kbd>. Do not rely on automatic saving in this environment. Reloading the page before saving may discard your changes. Running, checking, and submitting use the last saved file.</p>
</div>
<ol>
<li>Complete the TODOs in <code>weekly_support.py</code>, then press <kbd>Ctrl</kbd> + <kbd>S</kbd>.</li>
<li>Run the program, enter the ten sample values, and compare the result with the expected output.</li>
<li>Run the checker.<pre><code>python projects/weekly-support/check_weekly_support.py</code></pre></li>
<li>When all eight items are <code>OK</code> and the last line is <code>ALL TESTS PASSED</code>, choose either submission method below.</li>
</ol>

<h4>Method A: Submit directly from Python Lab</h4>
<p>Run the submission cell in the Project Notebook or the following command. The checker runs again. Only a passing, last-saved <code>weekly_support.py</code> is sent to the Moodle Assignment.</p>
<pre><code>python /home/jovyan/work/projects/weekly-support/submit_weekly_support.py</code></pre>
<p><code>SUBMISSION COMPLETE</code> and a Moodle submission ID confirm success. Run the command again to resubmit the currently saved file.</p>

<h4>Method B: Use Moodle's standard submission page</h4>
<p>In Python Lab, right-click <code>weekly_support.py</code> in the file browser and download it. Then upload that file on the Moodle Assignment submission page and confirm the submission.</p>

<h4>Deliverable and completion conditions</h4>
<p>Submit only the completed <code>weekly_support.py</code>. Do not submit the Notebook, <code>check_weekly_support.py</code>, screenshots, or an essay.</p>
<ul>
<li>The sample daily records produce the expected weekly report.</li>
<li>All eight checks are <code>OK</code> and the checker ends with <code>ALL TESTS PASSED</code>.</li>
<li>The saved <code>weekly_support.py</code> has been submitted to the Moodle Assignment by Method A or Method B.</li>
</ul>
<p style="display:none">PYAI-V30-PROJECT17-READABLE-BRIEF</p>
<p style="display:none">PYAI-V33-PROJECT17-SAVE-SUBMIT</p>
HTML;
}

$start = strrpos($content, $heading);
$marker = '<p style="display:none">PYAI-V30-PROJECT17-READABLE-BRIEF</p>';
$end = strpos($content, $marker, $start === false ? 0 : $start);
if ($start === false || $end === false) {
    throw new moodle_exception('Project 1.7 completion section was not found');
}
$end += strlen($marker);
$content = substr($content, 0, $start) . $replacement . substr($content, $end);

$page->content = $content;
$page->timemodified = time();
$DB->update_record('page', $page);
rebuild_course_cache($course->id, true);

echo json_encode([
    'shortname' => $shortname,
    'pageid' => (int)$page->id,
    'marker' => 'PYAI-V33-PROJECT17-SAVE-SUBMIT',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
