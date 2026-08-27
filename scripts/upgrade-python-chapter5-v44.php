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

$chapter = get_fast_modinfo($course)->get_section_info(5, MUST_EXIST);
course_update_section($course, $chapter, [
    'name' => $ja ? '第5章 — 根拠を伝える' : 'Chapter 5 — Communicating Evidence',
    'summary' => $ja
        ? '<p>問いに合う指標・粒度・図を選び、誤解を生まない比較と限定した根拠文を作ります。</p>'
        : '<p>Choose metric, grain, and chart from the question, then communicate an honest comparison with a bounded evidence statement.</p>',
    'summaryformat' => FORMAT_HTML, 'visible' => 1,
]);

$lessondata = $ja ? [
    '51' => ['5.1 問いから図へ', '5.1 可視化と根拠', 'レッスン5.1：問いから図へ', 'レッスン5.1：可視化と根拠', 'Python Lab 5.1：問いから図へ', 'Python Lab 5.1：可視化と根拠', '/ja/17_question_to_chart.ipynb', 'chapter5-lesson51-ja.md', '問いと図の粒度を決め、比較・時間変化・関係・分布に合う図を選びます。'],
    '52' => ['5.2 誤解を生まない比較', '5.2 ガイド付きプロジェクト：学習センター分析', 'レッスン5.2：誤解を生まない比較', '5.2 データセットとプロジェクト手順', 'Python Lab 5.2：誤解を生まない比較', 'Python Lab 5.2：学習センター分析', '/ja/18_honest_comparisons.ipynb', 'chapter5-lesson52-ja.md', '総量・一人当たり・割合を区別し、軸、分母、粒度、件数を明示します。'],
] : [
    '51' => ['5.1 From a question to a chart', '5.1 Visualisation and evidence', 'Lesson 5.1: From a question to a chart', 'Lesson 5.1: Visualisation and evidence', 'Python Lab 5.1: From a question to a chart', 'Python Lab 5.1: Visualisation and evidence', '/17_question_to_chart.ipynb', 'chapter5-lesson51-en.md', 'Define question and plotted grain, then choose a chart for comparison, ordered change, relationship, or distribution.'],
    '52' => ['5.2 Honest comparisons', '5.2 Guided project: Learning-centre analysis', 'Lesson 5.2: Honest comparisons', '5.2 Dataset and project brief', 'Python Lab 5.2: Honest comparisons', 'Python Lab 5.2: Learning-centre analysis', '/18_honest_comparisons.ipynb', 'chapter5-lesson52-en.md', 'Distinguish total, per-person, and percentage views while making scale, denominator, grain, and count visible.'],
];

$visible = [];
$subcms = [];
foreach ($lessondata as $key => $data) {
    [$topic, $oldtopic, $page, $oldpage, $lti, $oldlti, $path, $file, $summary] = $data;
    [$sub, $subcm, $delegated] = v44_subsection($course, $chapter, $topic, $oldtopic, $summary);
    $content = file_get_contents('/workspace/sample-content/introduction-to-python/' . $file);
    if ($content === false) throw new RuntimeException('Cannot read ' . $file);
    $pagecm = v44_page($course, $delegated->section, $page, $oldpage, '<p>' . s($summary) . '</p>', $content);
    $lticm = v44_lti($course, $delegated->section, $lti, $oldlti, '<p>' . s($summary) . '</p>', $path);
    $visible[$key] = [$delegated, $pagecm, $lticm];
    $subcms[$key] = (int)$subcm->id;
}

[$sub53, $subcm53, $delegated53] = v44_subsection(
    $course, $chapter,
    $ja ? '5.3 図から根拠文へ' : '5.3 From chart to evidence statement',
    null,
    $ja ? '観察・範囲・限界を短い根拠文へまとめ、保存した図まで確認します。' : 'Turn an observed comparison into a bounded statement and verify the saved visual deliverable.'
);
$content53 = file_get_contents('/workspace/sample-content/introduction-to-python/' . ($ja ? 'chapter5-lesson53-ja.md' : 'chapter5-lesson53-en.md'));
$pagecm53 = v44_page($course, $delegated53->section, $ja ? 'レッスン5.3：図から根拠文へ' : 'Lesson 5.3: From chart to evidence statement', null, '<p>Observation, scope, limitation, and reproducible output.</p>', $content53);
$lticm53 = v44_lti($course, $delegated53->section, $ja ? 'Python Lab 5.3：図から根拠文へ' : 'Python Lab 5.3: From chart to evidence statement', null, '<p>Write and verify a short evidence statement.</p>', $ja ? '/ja/19_evidence_statements.ipynb' : '/19_evidence_statements.ipynb');
$visible['53'] = [$delegated53, $pagecm53, $lticm53];
$subcms['53'] = (int)$subcm53->id;

[$sub54, $subcm54, $delegated54] = v44_subsection(
    $course, $chapter,
    $ja ? '5.4 応用プロジェクト：診療所の待ち時間' : '5.4 Applied project: Clinic waiting-time evidence',
    $ja ? '5.3 最終プロジェクト：問いから根拠へ' : '5.3 Final project: From question to evidence',
    $ja ? '総負担と患者一人当たりの経験を別々に可視化し、支援対象を根拠とともに示します。' : 'Visualise total burden and individual experience separately, then identify a support target with evidence.'
);
$brief = file_get_contents('/workspace/sample-content/introduction-to-python/' . ($ja ? 'project-5-brief-ja.md' : 'project-5-brief-en.md'));
$projectpage = v44_page($course, $delegated54->section, $ja ? '5.4 課題仕様と完成条件' : '5.4 Project brief and completion criteria', $ja ? '振り返りと次のステップ' : 'Reflection and next steps', '<p>Complete the supplied starter and submit the program and generated PNG.</p>', $brief);
$projectlti = v44_lti($course, $delegated54->section, $ja ? 'Python Lab 5.4：診療所の待ち時間' : 'Python Lab 5.4: Clinic waiting-time evidence', $ja ? 'Python Labプロジェクト：問いから根拠へ' : 'Python Lab project: Question to evidence', '<p>Complete, test, and inspect the clinic waiting-time evidence project.</p>', $ja ? '/ja/P5_clinic_wait_evidence.ipynb' : '/P5_clinic_wait_evidence.ipynb');
$projectassign = v44_assignment($course, $delegated54->section, $ja ? '提出課題5.4：診療所の待ち時間' : 'Assignment 5.4: Clinic waiting-time evidence', $ja ? '最終プロジェクト：問いから根拠へ' : 'Final project: From question to evidence', $brief);
$subcms['54'] = (int)$subcm54->id;

$hidden = [];
$hidden[] = v44_hide($course, 'assign', $ja ? '提出課題5.2：学習センター分析' : 'Assignment 5.2: Learning-centre analysis');
$hidden[] = v44_hide($course, 'page', $ja ? '模範解答と採点メモ（学習者には非表示）' : 'Model answers and grading notes (hidden from students)');
$hidden[] = v44_hide($course, 'page', $ja ? '教師用解答：つながりのある応用課題（非表示）' : 'Teacher answers: Connected transfer challenges (hidden)');

// Quizzes are inserted by add-python-chapter5-questions-v44.php. For now keep page and Lab first.
foreach ($visible as [$delegated, $pagecm, $lticm]) {
    $delegated->sequence = implode(',', [(int)$pagecm->id, (int)$lticm->id]);
    $DB->update_record('course_sections', $delegated);
    foreach ([$pagecm->id, $lticm->id] as $cmid) {
        $DB->set_field('course_modules', 'section', $delegated->id, ['id' => $cmid]);
    }
}
$delegated54->sequence = implode(',', [(int)$projectpage->id, (int)$projectlti->id, (int)$projectassign->id]);
$DB->update_record('course_sections', $delegated54);
foreach ([$projectpage->id, $projectlti->id, $projectassign->id] as $cmid) {
    $DB->set_field('course_modules', 'section', $delegated54->id, ['id' => $cmid]);
}

$parent = $DB->get_record('course_sections', ['id' => $chapter->id], '*', MUST_EXIST);
$sequence = array_values(array_filter(array_map('intval', explode(',', (string)$parent->sequence))));
$tail = [$subcms['51'], $subcms['52'], $subcms['53'], $subcms['54']];
$sequence = array_values(array_filter($sequence, fn($cmid) => !in_array($cmid, $tail, true)));
$parent->sequence = implode(',', array_merge($sequence, $tail));
$DB->update_record('course_sections', $parent);

rebuild_course_cache($course->id, true);
echo json_encode([
    'status' => 'ok', 'shortname' => $shortname,
    'subsections' => $subcms, 'project_assignment_cmid' => (int)$projectassign->id,
    'hidden_superseded' => array_values(array_filter($hidden)),
    'marker' => 'PYAI-V44-CHAPTER5-CONTENT',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
