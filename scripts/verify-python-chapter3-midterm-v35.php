<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $l33name = $ja ? 'レッスン3.3：データのクリーニングと監査記録' : 'Lesson 3.3: Data cleaning and audit records';
    $l34name = $ja ? 'レッスン3.4：グループ化と要約統計' : 'Lesson 3.4: Grouping and summary statistics';
    $l33 = $DB->get_record('page', ['course' => $course->id, 'name' => $l33name], '*', MUST_EXIST);
    $l34 = $DB->get_record('page', ['course' => $course->id, 'name' => $l34name], '*', MUST_EXIST);
    foreach (['PYAI-V35-L33-VERIFY-ROWS', 'PYAI-V35-L33-PROJECT-BOUNDARIES', 'copy(deep=True)', 'pd.to_numeric', 'keep=False', 'records_to_verify'] as $token) if (!str_contains($l33->content, $token)) throw new RuntimeException("$shortname 3.3 missing $token");
    foreach (['PYAI-V35-L34-PROJECT-BOUNDARY', 'ascending=[False,False,True]', '再読込', 'Re-read'] as $token) {
        if (($ja && $token === 'Re-read') || (!$ja && $token === '再読込')) continue;
        if (!str_contains($l34->content, $token)) throw new RuntimeException("$shortname 3.4 missing $token");
    }
    $quiznames = $ja
        ? ['理解度チェック：3.3 データのクリーニングと監査記録', '理解度チェック：3.4 グループ化と要約統計']
        : ['Knowledge check: 3.3 Data cleaning and audit records', 'Knowledge check: 3.4 Grouping and summary statistics'];
    foreach ($quiznames as $name) {
        $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);
        if ((int)$DB->count_records('quiz_slots', ['quizid' => $quiz->id]) !== 10 || abs((float)$quiz->sumgrades - 100) > .001 || (int)$quiz->attempts !== 0) throw new RuntimeException("$shortname quiz $name");
    }
    $topic = $ja ? '3.5A 中間実践課題：学校給食の追加配送' : '3.5A Midterm practical project: School meal delivery';
    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topic], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $pagename = $ja ? '3.5A 課題仕様と完成条件' : '3.5A Project brief and completion criteria';
    $ltiname = $ja ? 'Python Lab 3.5A：学校給食の追加配送' : 'Python Lab 3.5A: School meal delivery review';
    $assignname = $ja ? '提出 3.5A：学校給食の追加配送' : 'Submit 3.5A: School meal delivery review';
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    foreach (['meal_delivery_review.py', 'records_to_verify.csv', 'school_delivery_summary.csv', 'check_meal_delivery_review.py', 'SOURCE RECORDS: 37', 'RECORDS TO VERIFY: 4', 'ANALYSIS RECORDS: 33', 'S004', 'pd.to_numeric', '0.0', 'inspect_school_meals.py', 'check_inspect_school_meals.py'] as $token) if (!str_contains($page->content, $token)) throw new RuntimeException("$shortname brief missing $token");
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $path = $ja ? '/ja/P3A_school_meal_delivery_review.ipynb' : '/P3A_school_meal_delivery_review.ipynb';
    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI path");
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
    $configs = [];
    foreach ($DB->get_records('assign_plugin_config', ['assignment' => $assign->id, 'subtype' => 'assignsubmission']) as $config) $configs[$config->plugin . ':' . $config->name] = $config->value;
    foreach (['file:enabled' => '1', 'file:maxfilesubmissions' => '2', 'file:allowedfiletypes' => '.py', 'onlinetext:enabled' => '0'] as $key => $value) if (($configs[$key] ?? null) !== $value) throw new RuntimeException("$shortname assign config $key");
    $modinfo = get_fast_modinfo($course);
    $names = [];
    foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $names[] = $modinfo->get_cm($cmid)->name;
    if ($names !== [$pagename, $ltiname, $assignname]) throw new RuntimeException("$shortname project order");
    $results[] = ['courseid' => (int)$course->id, 'shortname' => $shortname, 'lesson_quizzes' => [10, 10], 'topic' => $topic, 'activities' => $names, 'lti_path' => $path, 'assignment' => ['files' => 2, 'types' => ['.py'], 'online_text' => false]];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
