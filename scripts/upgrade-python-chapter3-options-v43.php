<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/gradelib.php';
require_once $CFG->libdir . '/resourcelib.php';

use core_courseformat\formatactions;

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';

function v43_plugin_config(int $assignment, string $plugin, string $name, string $value): void {
    global $DB;
    $where = ['assignment' => $assignment, 'plugin' => $plugin, 'subtype' => 'assignsubmission', 'name' => $name];
    if ($record = $DB->get_record('assign_plugin_config', $where)) {
        $record->value = $value;
        $DB->update_record('assign_plugin_config', $record);
    } else {
        $DB->insert_record('assign_plugin_config', (object)($where + ['value' => $value]));
    }
}

function v43_subsection(stdClass $course, section_info $parent, string $name, string $summary): array {
    global $DB;
    $record = $DB->get_record('subsection', ['course' => $course->id, 'name' => $name]);
    if (!$record) {
        $created = add_moduleinfo((object)[
            'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'], MUST_EXIST),
            'modulename' => 'subsection', 'section' => $parent->section, 'name' => $name,
            'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
            'completion' => 0,
        ], $course);
        $record = $DB->get_record('subsection', ['id' => $created->instance], '*', MUST_EXIST);
    }
    $cm = get_coursemodule_from_instance('subsection', $record->id, $course->id, false, MUST_EXIST);
    $delegated = $DB->get_record('course_sections', [
        'course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $record->id,
    ], '*', MUST_EXIST);
    course_update_section($course, $delegated, [
        'name' => $name, 'summary' => '<p>' . s($summary) . '</p>',
        'summaryformat' => FORMAT_HTML, 'visible' => 1,
    ]);
    formatactions::cm($course)->move_end_section($cm->id, $parent->id);
    return [$record, $cm, $delegated];
}

function v43_page(stdClass $course, int $sectionnumber, string $name, string $intro, string $content, int $format): stdClass {
    global $DB;
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $name]);
    if (!$page) {
        $created = add_moduleinfo((object)[
            'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
            'modulename' => 'page', 'section' => $sectionnumber, 'name' => $name,
            'intro' => $intro, 'introformat' => FORMAT_HTML,
            'content' => $content, 'contentformat' => $format,
            'display' => RESOURCELIB_DISPLAY_OPEN, 'printintro' => 0, 'printlastmodified' => 0,
            'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
            'completion' => 0, 'showdescription' => 1,
        ], $course);
        return get_coursemodule_from_instance('page', $created->instance, $course->id, false, MUST_EXIST);
    }
    $page->intro = $intro;
    $page->introformat = FORMAT_HTML;
    $page->content = $content;
    $page->contentformat = $format;
    $page->timemodified = time();
    $DB->update_record('page', $page);
    $cm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
    set_coursemodule_visible($cm->id, 1);
    return $cm;
}

function v43_lti(stdClass $course, int $sectionnumber, string $name, string $intro, string $path, bool $ja): stdClass {
    global $DB;
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $name]);
    $prototype = $DB->get_record('lti', [
        'course' => $course->id,
        'name' => $ja ? 'Python Lab 3.5A：学校給食の追加配送' : 'Python Lab 3.5A: School meal delivery review',
    ]);
    if (!$prototype) {
        $prototypes = $DB->get_records('lti', ['course' => $course->id], 'id ASC');
        $prototype = reset($prototypes);
    }
    if (!$prototype) {
        throw new RuntimeException('Python Lab LTI prototype not found');
    }
    $toolurl = preg_replace('~/hub/user-redirect/lab/tree/.*$~', '/hub/user-redirect/lab/tree/' . ltrim($path, '/'), $prototype->toolurl);
    if (!$lti) {
        $created = add_moduleinfo((object)[
            'module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST),
            'modulename' => 'lti', 'section' => $sectionnumber, 'name' => $name,
            'intro' => $intro, 'introformat' => FORMAT_HTML, 'typeid' => $prototype->typeid,
            'toolurl' => $toolurl, 'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
            'instructorchoicesendname' => LTI_SETTING_NEVER,
            'instructorchoicesendemailaddr' => LTI_SETTING_NEVER,
            'instructorchoiceacceptgrades' => LTI_SETTING_NEVER, 'grade' => 0,
            'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
            'completion' => 0, 'showdescription' => 1,
        ], $course);
        return get_coursemodule_from_instance('lti', $created->instance, $course->id, false, MUST_EXIST);
    }
    $lti->intro = $intro;
    $lti->introformat = FORMAT_HTML;
    $lti->toolurl = $toolurl;
    $lti->timemodified = time();
    $DB->update_record('lti', $lti);
    $cm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
    set_coursemodule_visible($cm->id, 1);
    return $cm;
}

function v43_assignment(stdClass $course, int $sectionnumber, string $name, string $brief): stdClass {
    global $DB;
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $name]);
    if (!$assign) {
        $created = add_moduleinfo((object)[
            'module' => $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST),
            'modulename' => 'assign', 'section' => $sectionnumber, 'name' => $name,
            'intro' => $brief, 'introformat' => FORMAT_MARKDOWN, 'alwaysshowdescription' => 1,
            'submissiondrafts' => 0, 'requiresubmissionstatement' => 0,
            'sendnotifications' => 0, 'sendlatenotifications' => 0, 'sendstudentnotifications' => 1,
            'duedate' => 0, 'cutoffdate' => 0, 'gradingduedate' => 0, 'allowsubmissionsfromdate' => 0,
            'grade' => 100, 'attemptreopenmethod' => 'manual', 'maxattempts' => -1,
            'teamsubmission' => 0, 'requireallteammemberssubmit' => 0, 'blindmarking' => 0,
            'markingworkflow' => 0, 'markingallocation' => 0,
            'assignsubmission_onlinetext_enabled' => 0, 'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 2, 'assignsubmission_file_maxsizebytes' => 0,
            'assignfeedback_comments_enabled' => 1, 'visible' => 1, 'visibleoncoursepage' => 1,
            'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
        ], $course);
        $assign = $DB->get_record('assign', ['id' => $created->instance], '*', MUST_EXIST);
    } else {
        $assign->intro = $brief;
        $assign->introformat = FORMAT_MARKDOWN;
        $assign->grade = 100;
        $assign->timemodified = time();
        $DB->update_record('assign', $assign);
    }
    v43_plugin_config($assign->id, 'file', 'enabled', '1');
    v43_plugin_config($assign->id, 'file', 'maxfilesubmissions', '2');
    v43_plugin_config($assign->id, 'file', 'allowedfiletypes', '.py');
    v43_plugin_config($assign->id, 'onlinetext', 'enabled', '0');
    $cm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
    set_coursemodule_visible($cm->id, 1);
    return $cm;
}

$modinfo = get_fast_modinfo($course);
$parent = $modinfo->get_section_info(3, MUST_EXIST);
$selectionname = $ja ? '第3章中間実践：三つから一つを選ぶ' : 'Chapter 3 midterm: Choose one practical project';
$selectionintro = $ja
    ? '<p>A・B・Cのどれか一つについて、第1段階と第2段階の両方を完成すれば第3章の必須条件を満たします。</p>'
    : '<p>Complete both stages of any one option, A, B, or C, to meet the Chapter 3 requirement.</p>';
$selectioncontent = $ja ? <<<'MD'
# 三つの実務課題から一つを選ぶ

三課題は同じpandas技能を使いますが、扱う問題と判断根拠は異なります。

| 選択 | 問題 | 発見 |
|---|---|---|
| 3.5A 学校給食 | 追加配送する学校を選ぶ | 不良データを除くと優先校が変わる |
| 3.5B 公共バス | 改善調査する路線を選ぶ | 平均遅延と乗客影響では優先路線が変わる |
| 3.5C 地域給水 | 現地点検する施設を選ぶ | 故障センサーと継続停止を区別する |

一つを完成すれば必須条件を満たします。余力があれば二つ目、三つ目へ進み、同じ処理を別のデータ意味へ転用してください。どの選択でも、第1段階の原資料確認プログラムと、第2段階の本番処理プログラムの二つを提出します。
MD
    : <<<'MD'
# Choose one of three operational projects

All options use the same pandas foundation, but solve different problems with different decision evidence.

| Option | Problem | Discovery |
|---|---|---|
| 3.5A School meals | choose an additional delivery | the priority changes after invalid data is separated |
| 3.5B Public buses | choose a route investigation | average delay and passenger impact rank routes differently |
| 3.5C Water points | choose a field inspection | a failed sensor must be separated from repeated stoppages |

Complete one option to meet the requirement. If time permits, complete a second or third option to transfer the same processing skills to another data meaning. Every option submits a Stage 1 source-inspection program and a Stage 2 production program.
MD;
$selectioncm = v43_page($course, $parent->section, $selectionname, $selectionintro, $selectioncontent, FORMAT_MARKDOWN);

$options = $ja ? [
    [
        'topic' => '3.5B 中間実践課題：公共バスの改善調査',
        'summary' => '平均遅延と乗客影響の違いを調べ、改善調査する一路線を選びます。',
        'page' => '3.5B 課題仕様と完成条件',
        'lti' => 'Python Lab 3.5B：公共バスの改善調査',
        'assign' => '3.5B提出：公共バスの改善調査',
        'brief' => '/workspace/sample-content/introduction-to-python/project-3b-brief-ja.md',
        'path' => '/ja/P3B_bus_service_review.ipynb',
        'intro' => '<p>第1段階で原資料を確認し、第2段階で路線別の乗客遅延影響を集計します。</p>',
    ],
    [
        'topic' => '3.5C 中間実践課題：地域給水設備の点検',
        'summary' => '故障センサーと継続停止を区別し、現地点検する一施設を選びます。',
        'page' => '3.5C 課題仕様と完成条件',
        'lti' => 'Python Lab 3.5C：地域給水設備の点検',
        'assign' => '3.5C提出：地域給水設備の点検',
        'brief' => '/workspace/sample-content/introduction-to-python/project-3c-brief-ja.md',
        'path' => '/ja/P3C_water_point_review.ipynb',
        'intro' => '<p>第1段階で原資料を確認し、第2段階で停止日と低出力日を集計します。</p>',
    ],
] : [
    [
        'topic' => '3.5B Midterm practical project: Public bus service review',
        'summary' => 'Compare average delay with passenger impact and select one route for investigation.',
        'page' => '3.5B Project brief and completion criteria',
        'lti' => 'Python Lab 3.5B: Public bus service review',
        'assign' => 'Submit 3.5B: Public bus service review',
        'brief' => '/workspace/sample-content/introduction-to-python/project-3b-brief-en.md',
        'path' => '/P3B_bus_service_review.ipynb',
        'intro' => '<p>Inspect the source in Stage 1, then aggregate passenger-delay impact in Stage 2.</p>',
    ],
    [
        'topic' => '3.5C Midterm practical project: Community water-point inspection',
        'summary' => 'Separate failed sensors from repeated stoppages and choose one facility for inspection.',
        'page' => '3.5C Project brief and completion criteria',
        'lti' => 'Python Lab 3.5C: Community water-point inspection',
        'assign' => 'Submit 3.5C: Community water-point inspection',
        'brief' => '/workspace/sample-content/introduction-to-python/project-3c-brief-en.md',
        'path' => '/P3C_water_point_review.ipynb',
        'intro' => '<p>Inspect the source in Stage 1, then aggregate stoppages and low-output days in Stage 2.</p>',
    ],
];

$created = [];
$optioncms = [];
foreach ($options as $option) {
    [$subsection, $subsectioncm, $delegated] = v43_subsection($course, $parent, $option['topic'], $option['summary']);
    $brief = file_get_contents($option['brief']);
    if ($brief === false) {
        throw new RuntimeException('Cannot read ' . $option['brief']);
    }
    $pagecm = v43_page($course, $delegated->section, $option['page'], $option['intro'], $brief, FORMAT_MARKDOWN);
    $lticm = v43_lti($course, $delegated->section, $option['lti'], $option['intro'], $option['path'], $ja);
    $assigncm = v43_assignment($course, $delegated->section, $option['assign'], $brief);
    $delegated->sequence = implode(',', [$pagecm->id, $lticm->id, $assigncm->id]);
    $DB->update_record('course_sections', $delegated);
    foreach ([$pagecm->id, $lticm->id, $assigncm->id] as $cmid) {
        $DB->set_field('course_modules', 'section', $delegated->id, ['id' => $cmid]);
    }
    $created[] = [
        'topic' => $option['topic'], 'pagecmid' => (int)$pagecm->id,
        'lticmid' => (int)$lticm->id, 'assigncmid' => (int)$assigncm->id,
        'lti_path' => $option['path'],
    ];
    $optioncms[] = (int)$subsectioncm->id;
}

// Put the choice guide immediately before A, B, and C while preserving all earlier Chapter 3 activities.
$a = $DB->get_record('subsection', [
    'course' => $course->id,
    'name' => $ja ? '3.5A 中間実践課題：学校給食の追加配送' : '3.5A Midterm practical project: School meal delivery',
]);
if (!$a) {
    throw new RuntimeException('3.5A subsection not found');
}
$acm = get_coursemodule_from_instance('subsection', $a->id, $course->id, false, MUST_EXIST);
$parentrecord = $DB->get_record('course_sections', ['id' => $parent->id], '*', MUST_EXIST);
$sequence = array_values(array_filter(array_map('intval', explode(',', (string)$parentrecord->sequence))));
$tail = array_merge([(int)$selectioncm->id, (int)$acm->id], $optioncms);
$sequence = array_values(array_filter($sequence, fn($cmid) => !in_array($cmid, $tail, true)));
$parentrecord->sequence = implode(',', array_merge($sequence, $tail));
$DB->update_record('course_sections', $parentrecord);

rebuild_course_cache($course->id, true);
echo json_encode([
    'status' => 'ok', 'shortname' => $shortname,
    'selection_cmid' => (int)$selectioncm->id,
    'options' => $created,
    'marker' => 'PYAI-V43-CHAPTER3-OPTIONS',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
