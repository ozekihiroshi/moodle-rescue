<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$pagename = $ja ? '4.2 データセットとプロジェクト手順' : '4.2 Dataset and project brief';
$ltiname = $ja ? 'Python Lab 4.2：学習センター分析' : 'Python Lab 4.2: Learning-centre analysis';
$assignname = $ja ? '提出課題4.2：学習センター分析' : 'Assignment 4.2: Learning-centre analysis';
$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
$lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
$assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);

$csv = base64_decode((string)getenv('PYAI_DATA_B64'), true);
if ($csv === false || !str_starts_with($csv, "month,centre_id,centre_name,district,course,")) {
    throw new RuntimeException('Valid learning-centres-practice.csv was not supplied');
}
$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($pagecm->id);
$fs = get_file_storage();
$existing = $fs->get_file($context->id, 'mod_page', 'content', 0, '/', 'learning-centres-practice.csv');
if ($existing) {
    $existing->delete();
}
$stored = $fs->create_file_from_string([
    'contextid' => $context->id,
    'component' => 'mod_page',
    'filearea' => 'content',
    'itemid' => 0,
    'filepath' => '/',
    'filename' => 'learning-centres-practice.csv',
], $csv);

$download = '@@PLUGINFILE@@/learning-centres-practice.csv?forcedownload=1';
if ($ja) {
    $page->content = '<div class="python-sample-lesson"><h2>まずデータを取得する</h2>'
        . '<p><a class="btn btn-primary" href="' . $download . '">learning-centres-practice.csvをダウンロード</a></p>'
        . '<p>同じファイルはPython Labの<code>/home/jovyan/work/data/learning-centres-practice.csv</code>にもあります。Notebookの最初のセルが自動的に探し、実際に読んだ絶対パスを表示します。読み込み直後は<strong>24行×10列</strong>です。</p>'
        . '<h3>今回答える具体的な問い</h3><p>2026年1〜6月について、<strong>Python Foundations</strong>と<strong>Digital Skills</strong>のどちらが全体修了率が高く、どちらが一人修了当たり教材費が低いでしょうか。両方の差を数値で示し、この24件だけでコースの優劣を決められるか判断し、次に必要なデータを一つ提案します。</p>'
        . '<h3>作成する集計表</h3><p>コース別に<code>records</code>、<code>centres</code>、<code>registered</code>、<code>completed</code>、<code>material_cost</code>、<code>completion_rate</code>、<code>cost_per_completion</code>を作ります。修了率は修了者合計÷登録者合計、一人修了当たり教材費は教材費合計÷修了者合計です。</p>'
        . '<h3>作業順</h3><ol><li>絶対パス、24行×10列、列名、先頭5行を確認する。</li><li>型、欠損、地区名、数値範囲を表示する。</li><li>欠損出席、修了者数＞出席者数、業務キー重複を別々に数え、監査表へ記録する。</li><li>問題行を推測修正せず、分析可能行でコース別集計を作る。</li><li>行数、登録者、修了者、教材費を明細と照合する。</li><li>0〜100%軸の修了率グラフを作る。</li><li>指定された5項目へ300〜500字で回答する。</li></ol>'
        . '<h3>回答する5項目</h3><ol><li>二つの全体修了率と差（パーセントポイント）。</li><li>二つの一人修了当たり教材費と差。</li><li>どちらが高い／低いか。ただし優劣や原因は断定しない。</li><li>分析対象件数、対象期間、少なくとも一つの限界。</li><li>次に必要なデータを一つ。</li></ol>'
        . '<p style="display:none">PYAI-V28-PROJECT42-CONCRETE</p></div>';
    $assign->intro = '<h2>提出するNotebook</h2><p>上から「すべて実行」してエラーがないNotebook一つを提出します。</p>'
        . '<h3>必須チェック</h3><ul><li>読んだ絶対パスと<code>(24, 10)</code></li><li>三つの品質検査と監査表</li><li>指定したコース別集計列</li><li>行数・登録者・修了者・教材費の照合</li><li>0〜100%軸、タイトル、単位、分析対象件数を持つ主図</li><li>上記5項目へ答える300〜500字の報告</li></ul>'
        . '<h3>評価（100点）</h3><p>ファイル確認と問い10、検査と監査20、集計と分母25、照合15、図15、5項目への回答15。</p>';
    $lti->intro = '<p>CSVはMoodleからダウンロードでき、Python Lab内にもあります。最初に絶対パスと24行×10列を確認し、Notebookの順番に沿って完成させます。</p>';
} else {
    $page->content = '<div class="python-sample-lesson"><h2>Get the data first</h2>'
        . '<p><a class="btn btn-primary" href="' . $download . '">Download learning-centres-practice.csv</a></p>'
        . '<p>The same file is available in Python Lab at <code>/home/jovyan/work/data/learning-centres-practice.csv</code>. The notebook locates it and prints the absolute path used. Immediately after loading it must have <strong>24 rows and 10 columns</strong>.</p>'
        . '<h3>Concrete question</h3><p>For January–June 2026, which course has the higher overall completion rate, and which has the lower material cost per completion: <strong>Python Foundations</strong> or <strong>Digital Skills</strong>? Quantify both differences, decide whether these 24 records justify ranking the courses, and propose one item of data needed next.</p>'
        . '<h3>Required summary</h3><p>By course create <code>records</code>, <code>centres</code>, <code>registered</code>, <code>completed</code>, <code>material_cost</code>, <code>completion_rate</code>, and <code>cost_per_completion</code>. Completion is total completed / total registered; cost per completion is total cost / total completed.</p>'
        . '<h3>Steps</h3><ol><li>Confirm absolute path, 24×10 shape, columns, and first five rows.</li><li>Display types, missingness, district labels, and numeric ranges.</li><li>Count missing attendance, completion above attendance, and duplicate business keys separately.</li><li>Do not guess corrections; aggregate analysis-ready rows.</li><li>Reconcile rows, registrations, completions, and cost.</li><li>Create a completion chart on a 0–100% axis.</li><li>Answer the five prompts in 150–250 words.</li></ol>'
        . '<h3>Five report prompts</h3><ol><li>Both overall completion rates and their percentage-point difference.</li><li>Both costs per completion and their difference.</li><li>Which is higher/lower without claiming superiority or cause.</li><li>Analysis n, period, and at least one limitation.</li><li>One additional item of data needed next.</li></ol>'
        . '<p style="display:none">PYAI-V28-PROJECT42-CONCRETE</p></div>';
    $assign->intro = '<h2>Notebook submission</h2><p>Submit one notebook that completes Run All without errors.</p>'
        . '<h3>Required checks</h3><ul><li>Loaded absolute path and <code>(24, 10)</code></li><li>Three quality checks and audit table</li><li>Specified course summary columns</li><li>Row, registration, completion, and cost reconciliation</li><li>Primary chart with 0–100% axis, title, unit, and analysis n</li><li>150–250 words answering all five prompts</li></ul>'
        . '<h3>Rubric (100)</h3><p>File and question 10; inspection and audit 20; aggregation and denominators 25; reconciliation 15; chart 15; five-prompt answer 15.</p>';
    $lti->intro = '<p>The CSV is downloadable from Moodle and also present in Python Lab. First verify the absolute path and 24×10 shape, then follow the notebook in order.</p>';
}
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);
$assign->introformat = FORMAT_HTML;
$assign->timemodified = time();
$DB->update_record('assign', $assign);
$lti->introformat = FORMAT_HTML;
$lti->timemodified = time();
$DB->update_record('lti', $lti);
rebuild_course_cache($course->id, true);
echo json_encode([
    'courseid' => (int)$course->id,
    'shortname' => $shortname,
    'pageid' => (int)$page->id,
    'assignid' => (int)$assign->id,
    'attached_file' => $stored->get_filename(),
    'bytes' => $stored->get_filesize(),
    'sha256' => hash('sha256', $csv),
    'marker' => 'PYAI-V28-PROJECT42-CONCRETE',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
