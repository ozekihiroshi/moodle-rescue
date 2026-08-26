<?php
// Bind Project 1.7 to the Python Lab submission bridge.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$assignname = $ja
    ? 'プロジェクト1.7：学習センター週間サポート報告'
    : 'Project 1.7: Weekly learning-centre support report';
$pagename = $ja
    ? '1.7 課題仕様と完成条件'
    : '1.7 Project brief and completion contract';
$ltiname = $ja
    ? 'Python Labプロジェクト1.7：週間サポート報告'
    : 'Python Lab project 1.7: Weekly support report';

$assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
$assign->submissiondrafts = 0;
$assign->timemodified = time();
$DB->update_record('assign', $assign);
$assigncm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
$DB->set_field('course_modules', 'idnumber', 'pyai-project-1-weekly-support', ['id' => $assigncm->id]);

function v31_config(int $assignment, string $name, string $value): void {
    global $DB;
    $where = ['assignment' => $assignment, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => $name];
    if ($record = $DB->get_record('assign_plugin_config', $where)) {
        $record->value = $value;
        $DB->update_record('assign_plugin_config', $record);
    } else {
        $DB->insert_record('assign_plugin_config', (object)($where + ['value' => $value]));
    }
}
v31_config((int)$assign->id, 'enabled', '1');
v31_config((int)$assign->id, 'maxfilesubmissions', '1');
v31_config((int)$assign->id, 'filetypeslist', '.py');

$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
if (!str_contains($page->content, 'PYAI-V31-DIRECT-SUBMIT')) {
    $direct = $ja
        ? '<h3>Python LabからMoodleへ提出する</h3><p><code>weekly_support.py</code>を保存し、自動確認で<code>ALL TESTS PASSED</code>を確認した後、Project Notebookの「Moodleへ提出」セルを実行します。確認プログラムがもう一度実行され、合格したファイルだけがこのMoodle課題へ送られます。ダウンロードや手作業のアップロードは不要です。</p><pre><code>!python /home/jovyan/work/ja/projects/weekly-support/submit_weekly_support.py</code></pre><p><code>提出が完了しました</code>とMoodle提出IDが表示されたら完了です。もう一度実行すると、その時点のファイルで再提出されます。</p><p style="display:none">PYAI-V31-DIRECT-SUBMIT</p>'
        : '<h3>Submit from Python Lab to Moodle</h3><p>Save <code>weekly_support.py</code> and confirm <code>ALL TESTS PASSED</code>. Then run the Project Notebook cell named “Submit to Moodle”. The checker runs once more and only a passing file is sent to this Moodle Assignment. No download or manual upload is required.</p><pre><code>!python /home/jovyan/work/projects/weekly-support/submit_weekly_support.py</code></pre><p>Completion is confirmed by <code>SUBMISSION COMPLETE</code> and a Moodle submission ID. Running it again replaces the submitted snapshot with the current file.</p><p style="display:none">PYAI-V31-DIRECT-SUBMIT</p>';
    $page->content .= $direct;
    $page->timemodified = time();
    $DB->update_record('page', $page);
}

$lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
$lti->intro .= $ja
    ? '<p>完成後はNotebookの提出セルからMoodleへ直接提出できます。</p>'
    : '<p>When complete, use the submission cell in the Notebook to submit directly to Moodle.</p>';
$lti->timemodified = time();
$DB->update_record('lti', $lti);

rebuild_course_cache($course->id, true);
echo json_encode([
    'shortname' => $shortname,
    'assignment_cmid' => (int)$assigncm->id,
    'assignment_idnumber' => 'pyai-project-1-weekly-support',
    'marker' => 'PYAI-V31-DIRECT-SUBMIT',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
