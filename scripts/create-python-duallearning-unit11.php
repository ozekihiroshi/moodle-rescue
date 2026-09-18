<?php
// Build one isolated, self-paced Python unit on the local Moodle evaluation site.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';
require_once $CFG->libdir . '/enrollib.php';

if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('This builder is restricted to the inspected local development site.');
}
if (get_config('format_duallearning', 'version') != 2026091800) {
    throw new RuntimeException('Dual Learning 0.1.0-alpha2 must be installed first.');
}
if ($DB->record_exists('course', ['shortname' => 'DUAL-PY-11-PATH-JA'])) {
    throw new RuntimeException('The trial course already exists. Inspect it; do not overwrite learner work.');
}

\core\session\manager::set_user(get_admin());
$sourcecourse = $DB->get_record('course', ['shortname' => 'PYAI-INTRO-JA'], '*', MUST_EXIST);
$sourcelab = null;
foreach (get_fast_modinfo($sourcecourse)->get_cms() as $cm) {
    if ($cm->modname === 'lti' && str_contains($cm->name, '1.1')) {
        $sourcelab = $DB->get_record('lti', ['id' => $cm->instance], '*', MUST_EXIST);
        break;
    }
}
if (!$sourcelab || !str_ends_with($sourcelab->toolurl, '/ja/01_programs_values_output.ipynb')) {
    throw new RuntimeException('The published 1.1 Python Lab activity was not found.');
}

$sourcepath = '/srv/python-duallearning-1-1';
$materials = [
    'prepare' => ['01-prepare.md', '01 始める前に：Labと予想'],
    'lesson' => ['02-lesson.md', '02 レッスン1.1：プログラム・値・式・出力'],
    'review' => ['03-review.md', '03 確認と復習'],
];
foreach ($materials as $key => [$filename, $name]) {
    $text = file_get_contents($sourcepath . '/' . $filename);
    if ($text === false || !mb_check_encoding($text, 'UTF-8') || trim($text) === '') {
        throw new RuntimeException('Invalid Markdown source: ' . $filename);
    }
    $materials[$key][] = $text;
}

$category = $DB->get_record('course_categories', ['idnumber' => 'dual-learning-pilot'], '*', MUST_EXIST);
$course = create_course((object) [
    'category' => $category->id,
    'shortname' => 'DUAL-PY-11-PATH-JA',
    'fullname' => '【検証】Python 1.1 — 自学自習・Markdown対応版',
    'idnumber' => 'dual-python-1-1-path-ja',
    'format' => 'duallearning',
    'numsections' => 0,
    'summary' => '<p>Python日本語版1.1のDual Learning対応を確認する、独立した一単元です。既存の公開コースは変更しません。</p>',
    'summaryformat' => FORMAT_HTML,
    'visible' => 0,
    'lang' => 'ja',
    'startdate' => time(),
    'enddate' => 0,
    'enablecompletion' => 1,
    'showcompletionconditions' => 1,
    'newsitems' => 0,
    'showgrades' => 0,
]);
update_course((object) ['id' => $course->id, 'learningmode' => 'path']);
$course = get_course($course->id);
foreach (enrol_get_instances($course->id, false) as $instance) {
    if ($instance->enrol !== 'manual') {
        $plugin = enrol_get_plugin($instance->enrol);
        if ($plugin) {
            $plugin->update_status($instance, ENROL_INSTANCE_DISABLED);
        }
    }
}

$summary = '<h3>この単元でできるようになること</h3>'
    . '<p>短いプログラムの命令・値・式・出力を読み分け、予想、実行、説明の順で結果を確かめます。</p>'
    . '<h3>自分で進める順番</h3>'
    . '<p>01 準備 → 02 本文と統合練習 → Python Labで実行 → 03 確認と復習。'
    . '説明を読んだ印と、コードを正しく実行できたことは別です。</p>'
    . '<h3>止まったとき・復習するとき</h3>'
    . '<p>Labが動かなければ01へ、結果が違えば02の該当箇所へ戻ります。'
    . '同じアカウントのLabに保存したNotebookから再開できます。</p>';
$section = \format_duallearning\local\unit_starter::create($course, '1.1 プログラム・値・式・出力', $summary, FORMAT_HTML);

function add_python11_activity(stdClass $course, stdClass $section, string $module, string $name,
        string $key, array $additional): stdClass {
    global $DB;
    $data = (object) array_merge([
        'module' => $DB->get_field('modules', 'id', ['name' => $module], MUST_EXIST),
        'modulename' => $module,
        'section' => (int) $section->section,
        'name' => $name,
        'intro' => '',
        'introformat' => FORMAT_HTML,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'completion' => COMPLETION_TRACKING_NONE,
        'groupmode' => 0,
        'groupingid' => 0,
        'cmidnumber' => 'dual-python-11-' . $key,
    ], $additional);
    return add_moduleinfo($data, $course);
}

$created = [];
foreach (['prepare', 'lesson'] as $key) {
    [$filename, $name, $markdown] = $materials[$key];
    $activity = add_python11_activity($course, $section, 'lessonmark', $name, $key, [
        'markdownsource' => $markdown,
        'completion' => COMPLETION_TRACKING_MANUAL,
    ]);
    $created[$key] = (int) $activity->coursemodule;
}
$lab = add_python11_activity($course, $section, 'lti', 'Python Lab 1.1：実行して確かめる', 'lab', [
    'typeid' => $sourcelab->typeid,
    'toolurl' => $sourcelab->toolurl,
    'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
    'instructorchoicesendname' => LTI_SETTING_NEVER,
    'instructorchoicesendemailaddr' => LTI_SETTING_NEVER,
    'instructorchoiceacceptgrades' => LTI_SETTING_NEVER,
    'grade' => 0,
    'intro' => '<p>本文と確認のコードをここで実行します。開くだけでは完了や提出にはなりません。'
        . '作業を止めるときはNotebookを保存してください。</p>',
]);
$created['lab'] = (int) $lab->coursemodule;
[$filename, $name, $markdown] = $materials['review'];
$review = add_python11_activity($course, $section, 'lessonmark', $name, 'review', [
    'markdownsource' => $markdown,
    'completion' => COMPLETION_TRACKING_MANUAL,
]);
$created['review'] = (int) $review->coursemodule;

course_update_section($course, $section, ['visible' => 1]);
update_course((object) ['id' => $course->id, 'visible' => 1]);
rebuild_course_cache($course->id, true);
echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
    'format' => get_course($course->id)->format,
    'learningmode' => course_get_format($course)->get_format_options()['learningmode'] ?? null,
    'sectionid' => (int) $section->id,
    'activities' => $created,
    'source_sha256' => array_combine(array_keys($materials), array_map(
        static fn(array $entry): string => hash('sha256', $entry[2]), array_values($materials)
    )),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
