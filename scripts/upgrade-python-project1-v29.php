<?php
// Replace Project 1.7 with a concrete script task and black-box acceptance check.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';

if ($ja) {
    $topicname = '1.7 小プロジェクト：週間サポート報告';
    $topicsummary = '<p>5日分の問い合わせ件数と解決件数を入力し、週間報告を作るPythonスクリプトを完成させます。自分で実行した後、提供された確認プログラムの全8項目を通過させます。</p>';
    $oldtopic = '1.7 実践プロジェクト：週間サポート報告';
    $pagename = '1.7 課題仕様と完成条件';
    $ltiname = 'Python Labプロジェクト1.7：週間サポート報告';
    $assignname = 'プロジェクト1.7：学習センター週間サポート報告';
    $path = '/ja/P1_weekly_support_report.ipynb';
    $pageintro = '<p>作成するプログラム、入力、出力、自動確認の約束を確認します。</p>';
    $pagecontent = '<div class="python-project"><h2>週間サポート報告を自動化する</h2>'
        . '<p>学習センターでは、月曜日から金曜日までの問い合わせ件数と解決件数を手作業で集計しています。計算間違いや判定の不一致を防ぐため、10個の整数を入力すると週間報告を表示するPythonプログラムを作成します。</p>'
        . '<h3>成果物</h3><p><code>weekly_support.py</code>一つです。Notebookは作業手順、<code>check_weekly_support.py</code>は自動確認用であり、提出しません。</p>'
        . '<h3>使用する範囲</h3><p>変数、整数、文字列入出力、<code>int()</code>、f文字列、<code>if / elif / else</code>、<code>for</code>、比較・論理・算術演算だけを使います。関数、リスト、辞書、ファイル入出力は必要ありません。</p>'
        . '<h3>入力</h3><p>月曜日から金曜日の順に、各日の問い合わせ件数と解決件数を<code>input()</code>で受け取ります。</p>'
        . '<h3>出力</h3><pre><code>WEEKLY SUPPORT REPORT\nTOTAL RECEIVED: 75\nTOTAL RESOLVED: 67\nUNRESOLVED: 8\nRESOLUTION RATE: 89.3%\nSTATUS: REVIEW\nBUSIEST DAY: Thursday</code></pre>'
        . '<h3>業務ルール</h3><ol><li>負の件数、または解決件数が問い合わせ件数を超える場合は<code>RESULT: INVALID</code>とします。</li><li>問い合わせ合計0件は、解決率<code>N/A</code>、状態<code>NO REQUESTS</code>、最繁忙日<code>NONE</code>とします。</li><li>解決率90%以上は<code>ON TRACK</code>、80%以上は<code>REVIEW</code>、80%未満は<code>PRIORITY SUPPORT</code>です。</li><li>最繁忙日が同数なら最初の曜日を表示します。</li></ol>'
        . '<h3>自動確認の約束</h3><ul><li>ファイル名、入力順、英大文字の出力項目名を変更しない。</li><li><code>check_weekly_support.py</code>を変更しない。</li><li>最初に自分で実行して出力を確認し、その後確認プログラムを実行する。</li><li>全項目が<code>OK</code>となり、最後に<code>ALL TESTS PASSED</code>と表示されるまで修正する。</li></ul>'
        . '<p style="display:none">PYAI-V29-PROJECT17-SCRIPT-CHECK</p></div>';
    $ltiintro = '<p><strong>作るもの：</strong><code>weekly_support.py</code>。5日分の問い合わせと解決件数から週間報告を作り、自分で実行した後、提供された確認プログラムの全8項目を通過させます。</p>';
    $assignment = '<div class="python-project"><h2>提出：週間サポート報告プログラム</h2>'
        . '<p>Python Labで完成させた<code>weekly_support.py</code>だけを提出します。</p>'
        . '<h3>提出前確認</h3><ul><li>最初のデータを自分で入力し、75件、67件、8件、89.3%、<code>REVIEW</code>、<code>Thursday</code>を確認した。</li><li>確認プログラムの8項目がすべて<code>OK</code>になった。</li><li>最後に<code>ALL TESTS PASSED</code>と表示された。</li><li>スターターの<code>PROGRAM INCOMPLETE</code>とすべての<code>TODO</code>を処理した。</li></ul>'
        . '<p><strong>提出しないもの：</strong>Notebook、<code>check_weekly_support.py</code>、説明文、画面画像。</p>'
        . '<p style="display:none">PYAI-V29-PROJECT17-SUBMISSION</p></div>';
} else {
    $topicname = '1.7 Mini-project: Weekly support report';
    $topicsummary = '<p>Build a Python script that reads five days of received and resolved support counts, produces a weekly report, and passes all eight checks supplied with the project.</p>';
    $oldtopic = '1.7 Applied project: Weekly support report';
    $pagename = '1.7 Project brief and completion contract';
    $ltiname = 'Python Lab project 1.7: Weekly support report';
    $assignname = 'Project 1.7: Weekly learning-centre support report';
    $path = '/P1_weekly_support_report.ipynb';
    $pageintro = '<p>Read the required program, input, output, and automatic-check contract.</p>';
    $pagecontent = '<div class="python-project"><h2>Automate a weekly support report</h2>'
        . '<p>A learning centre manually totals received and resolved support requests from Monday to Friday. Build a Python program that reads ten integers and produces a consistent weekly report.</p>'
        . '<h3>Deliverable</h3><p>Submit one file: <code>weekly_support.py</code>. The Notebook gives the workflow and <code>check_weekly_support.py</code> checks the result; neither is submitted.</p>'
        . '<h3>Allowed course content</h3><p>Use variables, integers, string input/output, <code>int()</code>, f-strings, <code>if / elif / else</code>, <code>for</code>, and comparison, Boolean, and arithmetic operators. Functions, lists, dictionaries, and file input/output are not required.</p>'
        . '<h3>Input</h3><p>In Monday-to-Friday order, read each day’s received count and resolved count with <code>input()</code>.</p>'
        . '<h3>Output</h3><pre><code>WEEKLY SUPPORT REPORT\nTOTAL RECEIVED: 75\nTOTAL RESOLVED: 67\nUNRESOLVED: 8\nRESOLUTION RATE: 89.3%\nSTATUS: REVIEW\nBUSIEST DAY: Thursday</code></pre>'
        . '<h3>Operational rules</h3><ol><li>A negative count or resolved above received produces <code>RESULT: INVALID</code>.</li><li>Zero total received uses rate <code>N/A</code>, status <code>NO REQUESTS</code>, and busiest day <code>NONE</code>.</li><li>At least 90% is <code>ON TRACK</code>; at least 80% is <code>REVIEW</code>; below 80% is <code>PRIORITY SUPPORT</code>.</li><li>On a busiest-day tie, report the first day.</li></ol>'
        . '<h3>Automatic-check contract</h3><ul><li>Keep the filename, input order, and uppercase output labels unchanged.</li><li>Do not change <code>check_weekly_support.py</code>.</li><li>Run the script yourself before running the checker.</li><li>Revise until every item is <code>OK</code> and the last line is <code>ALL TESTS PASSED</code>.</li></ul>'
        . '<p style="display:none">PYAI-V29-PROJECT17-SCRIPT-CHECK</p></div>';
    $ltiintro = '<p><strong>Build:</strong> <code>weekly_support.py</code>. Turn five days of received and resolved counts into a weekly report, run it yourself, then pass all eight supplied checks.</p>';
    $assignment = '<div class="python-project"><h2>Submit the weekly support report program</h2>'
        . '<p>Submit only the completed <code>weekly_support.py</code> from Python Lab.</p>'
        . '<h3>Before submitting</h3><ul><li>Enter the initial data yourself and confirm 75, 67, 8, 89.3%, <code>REVIEW</code>, and <code>Thursday</code>.</li><li>All eight automatic checks display <code>OK</code>.</li><li>The final line is <code>ALL TESTS PASSED</code>.</li><li><code>PROGRAM INCOMPLETE</code> and every <code>TODO</code> have been dealt with.</li></ul>'
        . '<p><strong>Do not submit:</strong> the Notebook, <code>check_weekly_support.py</code>, an essay, or a screenshot.</p>'
        . '<p style="display:none">PYAI-V29-PROJECT17-SUBMISSION</p></div>';
}

$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname]);
if (!$subsection) {
    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $oldtopic], '*', MUST_EXIST);
    $subsection->name = $topicname;
    $DB->update_record('subsection', $subsection);
}
$delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $delegated, ['name' => $topicname, 'summary' => $topicsummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename]);
if (!$page) {
    $created = add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'modulename' => 'page', 'section' => $delegated->section, 'name' => $pagename,
        'intro' => $pageintro, 'introformat' => FORMAT_HTML,
        'content' => $pagecontent, 'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_OPEN, 'printintro' => 0, 'printlastmodified' => 0,
        'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
        'completion' => 0, 'showdescription' => 1,
    ], $course);
    $page = $DB->get_record('page', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $page->intro = $pageintro; $page->introformat = FORMAT_HTML;
    $page->content = $pagecontent; $page->contentformat = FORMAT_HTML; $page->timemodified = time();
    $DB->update_record('page', $page);
}

$lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
$lti->toolurl = preg_replace('~/(?:ja/)?P1_weekly_support_report\.ipynb$~', $path, $lti->toolurl);
if (!$lti->toolurl || !str_ends_with($lti->toolurl, $path)) throw new RuntimeException('Cannot set Project 1.7 LTI path');
$lti->intro = $ltiintro; $lti->introformat = FORMAT_HTML; $lti->timemodified = time();
$DB->update_record('lti', $lti);

$assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
$assign->intro = $assignment; $assign->introformat = FORMAT_HTML; $assign->grade = 100; $assign->timemodified = time();
$DB->update_record('assign', $assign);

function upsert_assign_config(int $assignment, string $name, string $value): void {
    global $DB;
    $where = ['assignment' => $assignment, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => $name];
    $record = $DB->get_record('assign_plugin_config', $where);
    if ($record) { $record->value = $value; $DB->update_record('assign_plugin_config', $record); }
    else { $DB->insert_record('assign_plugin_config', (object)($where + ['value' => $value])); }
}
upsert_assign_config((int)$assign->id, 'enabled', '1');
upsert_assign_config((int)$assign->id, 'maxfilesubmissions', '1');
upsert_assign_config((int)$assign->id, 'accepted_types', '.py');

$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
$assigncm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
$delegated->sequence = implode(',', [$pagecm->id, $lticm->id, $assigncm->id]);
$DB->update_record('course_sections', $delegated);
foreach ([$pagecm, $lticm, $assigncm] as $cm) {
    $DB->set_field('course_modules', 'section', $delegated->id, ['id' => $cm->id]);
}
$DB->set_field('course_modules', 'showdescription', 1, ['id' => $lticm->id]);
rebuild_course_cache($course->id, true);

echo json_encode([
    'courseid' => (int)$course->id, 'shortname' => $shortname, 'topic' => $topicname,
    'activities' => [$pagename, $ltiname, $assignname], 'lti_path' => $path,
    'accepted_types' => '.py', 'marker' => 'PYAI-V29-PROJECT17-SCRIPT-CHECK',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
