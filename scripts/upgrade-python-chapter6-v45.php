<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/resourcelib.php';

use core_courseformat\formatactions;

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';

function v44_find(string $table, int $courseid, string $new, ?string $old = null): ?stdClass {
    global $DB;
    $record = $DB->get_record($table, ['course' => $courseid, 'name' => $new]);
    if (!$record && $old !== null) {
        $record = $DB->get_record($table, ['course' => $courseid, 'name' => $old]);
    }
    return $record ?: null;
}

function v44_subsection(stdClass $course, section_info $parent, string $new, ?string $old, string $summary): array {
    global $DB;
    $record = v44_find('subsection', $course->id, $new, $old);
    if (!$record) {
        $created = add_moduleinfo((object)[
            'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'], MUST_EXIST),
            'modulename' => 'subsection', 'section' => $parent->section, 'name' => $new,
            'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0,
            'groupingid' => 0, 'completion' => 0,
        ], $course);
        $record = $DB->get_record('subsection', ['id' => $created->instance], '*', MUST_EXIST);
    } elseif ($record->name !== $new) {
        $record->name = $new;
        $DB->update_record('subsection', $record);
    }
    $cm = get_coursemodule_from_instance('subsection', $record->id, $course->id, false, MUST_EXIST);
    $delegated = $DB->get_record('course_sections', [
        'course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $record->id,
    ], '*', MUST_EXIST);
    course_update_section($course, $delegated, [
        'name' => $new, 'summary' => '<p>' . s($summary) . '</p>',
        'summaryformat' => FORMAT_HTML, 'visible' => 1,
    ]);
    formatactions::cm($course)->move_end_section($cm->id, $parent->id);
    return [$record, $cm, $delegated];
}

function v44_page(stdClass $course, int $sectionnumber, string $new, ?string $old, string $intro, string $markdown): stdClass {
    global $DB;
    $page = v44_find('page', $course->id, $new, $old);
    if (!$page) {
        $created = add_moduleinfo((object)[
            'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
            'modulename' => 'page', 'section' => $sectionnumber, 'name' => $new,
            'intro' => $intro, 'introformat' => FORMAT_HTML,
            'content' => $markdown, 'contentformat' => FORMAT_MARKDOWN,
            'display' => RESOURCELIB_DISPLAY_OPEN, 'printintro' => 0,
            'printlastmodified' => 0, 'visible' => 1, 'visibleoncoursepage' => 1,
            'groupmode' => 0, 'groupingid' => 0, 'completion' => 0,
            'showdescription' => 1,
        ], $course);
        return get_coursemodule_from_instance('page', $created->instance, $course->id, false, MUST_EXIST);
    }
    $page->name = $new;
    $page->intro = $intro;
    $page->introformat = FORMAT_HTML;
    $page->content = $markdown;
    $page->contentformat = FORMAT_MARKDOWN;
    $page->timemodified = time();
    $DB->update_record('page', $page);
    $cm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
    set_coursemodule_visible($cm->id, 1);
    return $cm;
}

function v44_lti(stdClass $course, int $sectionnumber, string $new, ?string $old, string $intro, string $path): stdClass {
    global $DB;
    $lti = v44_find('lti', $course->id, $new, $old);
    $prototypes = $DB->get_records('lti', ['course' => $course->id], 'id ASC');
    $prototype = reset($prototypes);
    if (!$prototype) {
        throw new RuntimeException('Python Lab LTI prototype not found');
    }
    $toolurl = preg_replace('~/hub/user-redirect/lab/tree/.*$~', '/hub/user-redirect/lab/tree/' . ltrim($path, '/'), $prototype->toolurl);
    if (!$lti) {
        $created = add_moduleinfo((object)[
            'module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST),
            'modulename' => 'lti', 'section' => $sectionnumber, 'name' => $new,
            'intro' => $intro, 'introformat' => FORMAT_HTML, 'typeid' => $prototype->typeid,
            'toolurl' => $toolurl, 'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
            'instructorchoicesendname' => LTI_SETTING_NEVER,
            'instructorchoicesendemailaddr' => LTI_SETTING_NEVER,
            'instructorchoiceacceptgrades' => LTI_SETTING_NEVER, 'grade' => 0,
            'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0,
            'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
        ], $course);
        return get_coursemodule_from_instance('lti', $created->instance, $course->id, false, MUST_EXIST);
    }
    $lti->name = $new;
    $lti->intro = $intro;
    $lti->introformat = FORMAT_HTML;
    $lti->toolurl = $toolurl;
    $lti->timemodified = time();
    $DB->update_record('lti', $lti);
    $cm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
    set_coursemodule_visible($cm->id, 1);
    return $cm;
}

function v44_plugin(int $assignment, string $plugin, string $name, string $value): void {
    global $DB;
    $where = ['assignment' => $assignment, 'plugin' => $plugin, 'subtype' => 'assignsubmission', 'name' => $name];
    if ($record = $DB->get_record('assign_plugin_config', $where)) {
        $record->value = $value;
        $DB->update_record('assign_plugin_config', $record);
    } else {
        $DB->insert_record('assign_plugin_config', (object)($where + ['value' => $value]));
    }
}

function v44_assignment(stdClass $course, int $sectionnumber, string $new, ?string $old, string $brief): stdClass {
    global $DB;
    $assign = v44_find('assign', $course->id, $new, $old);
    if (!$assign) {
        $created = add_moduleinfo((object)[
            'module' => $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST),
            'modulename' => 'assign', 'section' => $sectionnumber, 'name' => $new,
            'intro' => $brief, 'introformat' => FORMAT_MARKDOWN,
            'alwaysshowdescription' => 1, 'submissiondrafts' => 0,
            'requiresubmissionstatement' => 0, 'sendnotifications' => 0,
            'sendlatenotifications' => 0, 'sendstudentnotifications' => 1,
            'duedate' => 0, 'cutoffdate' => 0, 'gradingduedate' => 0,
            'allowsubmissionsfromdate' => 0, 'grade' => 100,
            'attemptreopenmethod' => 'manual', 'maxattempts' => -1,
            'teamsubmission' => 0, 'requireallteammemberssubmit' => 0,
            'blindmarking' => 0, 'markingworkflow' => 0, 'markingallocation' => 0,
            'assignsubmission_onlinetext_enabled' => 0,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 2,
            'assignsubmission_file_maxsizebytes' => 0,
            'assignfeedback_comments_enabled' => 1,
            'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0,
            'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
        ], $course);
        $assign = $DB->get_record('assign', ['id' => $created->instance], '*', MUST_EXIST);
    } else {
        $assign->name = $new;
        $assign->intro = $brief;
        $assign->introformat = FORMAT_MARKDOWN;
        $assign->grade = 100;
        $assign->timemodified = time();
        $DB->update_record('assign', $assign);
    }
    v44_plugin($assign->id, 'file', 'enabled', '1');
    v44_plugin($assign->id, 'file', 'maxfilesubmissions', '2');
    v44_plugin($assign->id, 'file', 'allowedfiletypes', '.py,.png');
    v44_plugin($assign->id, 'onlinetext', 'enabled', '0');
    $cm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
    set_coursemodule_visible($cm->id, 1);
    return $cm;
}

function v44_hide(stdClass $course, string $table, string $name): ?int {
    global $DB;
    $record = $DB->get_record($table, ['course' => $course->id, 'name' => $name]);
    if (!$record) {
        return null;
    }
    $cm = get_coursemodule_from_instance($table === 'assign' ? 'assign' : 'page', $record->id, $course->id, false, MUST_EXIST);
    set_coursemodule_visible($cm->id, 0);
    return (int)$cm->id;
}

$chapter = get_fast_modinfo($course)->get_section_info(6, MUST_EXIST);
course_update_section($course, $chapter, [
    'name' => $ja ? '第6章 — 信頼できる分析を大規模データへ拡張する' : 'Chapter 6 — Scaling Reliable Analysis',
    'summary' => $ja ? '<p>小さな既知データで確認した処理をチャンク単位へ拡張し、全行を照合して再現可能な判断資料を作ります。</p>' : '<p>Scale a workflow tested on known small data, aggregate across chunks, reconcile every row, and produce reproducible decision evidence.</p>',
    'summaryformat' => FORMAT_HTML, 'visible' => 1,
]);

$lessons = $ja ? [
    '61' => ['topic'=>'6.1 読み込む前に調べる','oldtopic'=>'6.1 大きなCSVファイルを安全に処理する','page'=>'レッスン6.1：読み込む前に調べる','oldpage'=>'レッスン12：大きなCSVファイルをチャンクで処理する','lti'=>'Python Lab 6.1：読み込む前に調べる','oldlti'=>'Python Lab 12：スケールアップ・チャンク・検証','path'=>'/ja/20_inspect_before_loading.ipynb','file'=>'chapter6-lesson61-ja.md','summary'=>'ファイル容量、一行の意味、必要列、型、メモリ使用量を確認し、比例的な読み込み計画を作ります。'],
    '62' => ['topic'=>'6.2 チャンクを越えて正しく集計する','oldtopic'=>'6.2 スケールアップ総合プロジェクト','page'=>'レッスン6.2：チャンクを越えて正しく集計する','oldpage'=>'総合プロジェクトガイド：大きなファイルから意思決定に使える根拠へ','lti'=>'Python Lab 6.2：チャンクを越えた集計','oldlti'=>'Python Labプロジェクト：スケールアップ総合課題','path'=>'/ja/21_chunked_aggregation.ipynb','file'=>'chapter6-lesson62-ja.md','summary'=>'合計と件数を小さな状態へ保持し、率を最後に計算してチャンク境界に依存しない要約を作ります。'],
    '63' => ['topic'=>'6.3 照合して再現可能にする','oldtopic'=>null,'page'=>'レッスン6.3：照合して再現可能にする','oldpage'=>null,'lti'=>'Python Lab 6.3：照合と再現','oldlti'=>null,'path'=>'/ja/22_reconcile_reproduce.ipynb','file'=>'chapter6-lesson63-ja.md','summary'=>'fixture、全行照合、来歴、保存後の再読込を組み合わせ、処理結果を再現可能にします。'],
] : [
    '61' => ['topic'=>'6.1 Inspect before loading','oldtopic'=>'6.1 Processing larger CSV files safely','page'=>'Lesson 6.1: Inspect before loading','oldpage'=>'Lesson 12: Processing larger CSV files in chunks','lti'=>'Python Lab 6.1: Inspect before loading','oldlti'=>'Python Lab 12: Scaling up, chunks, and validation','path'=>'/20_inspect_before_loading.ipynb','file'=>'chapter6-lesson61-en.md','summary'=>'Inspect bytes, record meaning, required columns, types, and memory before choosing a proportional reading plan.'],
    '62' => ['topic'=>'6.2 Aggregate correctly across chunks','oldtopic'=>'6.2 Scale-up capstone project','page'=>'Lesson 6.2: Aggregate correctly across chunks','oldpage'=>'Capstone guide: From large file to decision-sized evidence','lti'=>'Python Lab 6.2: Chunked aggregation','oldlti'=>'Python Lab project: Scale-up capstone','path'=>'/21_chunked_aggregation.ipynb','file'=>'chapter6-lesson62-en.md','summary'=>'Retain additive totals and counts, calculate rates last, and make the summary independent of chunk boundaries.'],
    '63' => ['topic'=>'6.3 Reconcile and reproduce','oldtopic'=>null,'page'=>'Lesson 6.3: Reconcile and reproduce','oldpage'=>null,'lti'=>'Python Lab 6.3: Reconcile and reproduce','oldlti'=>null,'path'=>'/22_reconcile_reproduce.ipynb','file'=>'chapter6-lesson63-en.md','summary'=>'Combine a known fixture, whole-file row reconciliation, provenance, and reopened outputs into a reproducible run.'],
];

$visible = [];
$subcms = [];
foreach ($lessons as $key => $data) {
    [$sub, $subcm, $delegated] = v44_subsection($course, $chapter, $data['topic'], $data['oldtopic'], $data['summary']);
    $content = file_get_contents('/workspace/sample-content/introduction-to-python/' . $data['file']);
    if ($content === false) throw new RuntimeException('Cannot read ' . $data['file']);
    $pagecm = v44_page($course, $delegated->section, $data['page'], $data['oldpage'], '<p>' . s($data['summary']) . '</p>', $content);
    $lticm = v44_lti($course, $delegated->section, $data['lti'], $data['oldlti'], '<p>' . s($data['summary']) . '</p>', $data['path']);
    $visible[$key] = [$delegated, $pagecm, $lticm];
    $subcms[$key] = (int)$subcm->id;
}
$projecttopic = $ja ? '6.4 総合プロジェクト：診療所医薬品在庫切れ対応' : '6.4 Capstone project: Clinic medicine stock-out response';
[$projectsub, $projectsubcm, $projectsection] = v44_subsection(
    $course, $chapter, $projecttopic, null,
    $ja ? '12万件の架空診療所在庫記録をチャンク処理し、全行を照合して最初の医薬品補給先を示します。' : 'Process 120,000 fictional clinic-stock records in chunks, reconcile every row, and identify the first medicine resupply.'
);
$brief = file_get_contents('/workspace/sample-content/introduction-to-python/' . ($ja ? 'project-6-brief-ja.md' : 'project-6-brief-en.md'));
if ($brief === false) throw new RuntimeException('Cannot read project brief');
$projectpage = v44_page($course, $projectsection->section, $ja ? '6.4 課題仕様と完成条件' : '6.4 Project brief and completion criteria', null, '<p>Complete the supplied scale-up program and submit code, summary CSV, and evidence PNG.</p>', $brief);
$projectlti = v44_lti($course, $projectsection->section, $ja ? 'Python Lab 6.4：診療所医薬品在庫切れ対応' : 'Python Lab 6.4: Clinic medicine stock-out response', null, '<p>Test on a fixture, generate the full source, process it in chunks, and inspect the deliverables.</p>', $ja ? '/ja/P6_clinic_stock_scaleup.ipynb' : '/P6_clinic_stock_scaleup.ipynb');
$projectassign = v44_assignment($course, $projectsection->section, $ja ? '提出課題6.4：診療所医薬品在庫切れ対応' : 'Assignment 6.4: Clinic medicine stock-out response', null, $brief);
v44_plugin($projectassign->instance, 'file', 'maxfilesubmissions', '3');
v44_plugin($projectassign->instance, 'file', 'allowedfiletypes', '.py,.csv,.png');
$subcms['64'] = (int)$projectsubcm->id;

$hidden = [];
foreach ($ja ? [
    ['assign', 'スケールアップ総合プロジェクト：運営判断の根拠'],
    ['page', '教師用資料：スケールアップ総合プロジェクト（非表示）'],
    ['page', 'オープンデータ発展編：ライセンス・来歴・プライバシー・検証'],
] : [
    ['assign', 'Scale-up capstone: Operations evidence'],
    ['page', 'Teacher reference: Scale-up capstone (hidden)'],
    ['page', 'Open-data extension: licence, provenance, privacy, and validation'],
] as [$table, $name]) {
    $hidden[] = v44_hide($course, $table, $name);
}

foreach ($visible as [$delegated, $pagecm, $lticm]) {
    $delegated->sequence = implode(',', [(int)$pagecm->id, (int)$lticm->id]);
    $DB->update_record('course_sections', $delegated);
    foreach ([$pagecm->id, $lticm->id] as $cmid) {
        $DB->set_field('course_modules', 'section', $delegated->id, ['id' => $cmid]);
    }
}
$projectsection->sequence = implode(',', [(int)$projectpage->id, (int)$projectlti->id, (int)$projectassign->id]);
$DB->update_record('course_sections', $projectsection);
foreach ([$projectpage->id, $projectlti->id, $projectassign->id] as $cmid) {
    $DB->set_field('course_modules', 'section', $projectsection->id, ['id' => $cmid]);
}

$parent = $DB->get_record('course_sections', ['id' => $chapter->id], '*', MUST_EXIST);
$sequence = array_values(array_filter(array_map('intval', explode(',', (string)$parent->sequence))));
$tail = [$subcms['61'], $subcms['62'], $subcms['63'], $subcms['64']];
$sequence = array_values(array_filter($sequence, fn($cmid) => !in_array($cmid, $tail, true)));
$parent->sequence = implode(',', array_merge($sequence, $tail));
$DB->update_record('course_sections', $parent);

rebuild_course_cache($course->id, true);
echo json_encode([
    'status' => 'ok', 'shortname' => $shortname, 'subsections' => $subcms,
    'project_assignment_cmid' => (int)$projectassign->id,
    'hidden_superseded' => array_values(array_filter($hidden)),
    'marker' => 'PYAI-V45-CHAPTER6-CONTENT',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;