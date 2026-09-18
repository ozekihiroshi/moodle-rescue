<?php
// Local working copy only. Publish one chapter's Markdown, never the source course.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/enrollib.php';
require_once $CFG->libdir . '/completionlib.php';
require_once $CFG->libdir . '/gradelib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local only');
}
$root = $argv[1];
$chapter = (int)$argv[2];
$manifest = json_decode(file_get_contents($root . '/chapter-' . $chapter . '.json'), true, 512, JSON_THROW_ON_ERROR);
$course = $DB->get_record('course', ['id' => $manifest['courseid'], 'shortname' => 'PYAI-INTRO-JA-DUAL-PATH'], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());
$moduleid = $DB->get_field('modules', 'id', ['name' => 'lessonmark'], MUST_EXIST);
$chaptersection = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $chapter], '*', MUST_EXIST);
course_update_section($course, $chaptersection, ['visible' => 1]);
if ($chapter === 2) {
    $projectsection = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 27], '*', MUST_EXIST);
    course_update_section($course, $projectsection, ['name' => '2.4 実践プロジェクト：CSV図書記録管理']);
}
$created = [];
foreach ($manifest['pages'] as $page) {
    $old = $DB->get_record('course_modules', ['id' => $page['pagecmid'], 'course' => $course->id], '*', MUST_EXIST);
    $idnumber = 'python-path-page-' . $old->id;
    $existing = $DB->get_record('course_modules', ['course' => $course->id, 'idnumber' => $idnumber]);
    if (!$old->visible && !$existing) {
        continue; // Teacher-only legacy material is not made public.
    }
    $markdown = file_get_contents($root . '/' . $page['file']);
    $supplement = in_array((int)$old->id, [636, 645, 712], true);
    if ($supplement) {
        $markdown = "補足・発展資料です。必要なときに参照でき、進捗の必須完了項目には含めません。\n\n" . $markdown;
    }
    if (!$markdown || !mb_check_encoding($markdown, 'UTF-8') || str_contains($markdown, '@@PLUGINFILE@@')) {
        throw new RuntimeException('Review source or attachments: ' . $page['file']);
    }
    $section = $DB->get_record('course_sections', ['id' => $old->section], '*', MUST_EXIST);
    if (preg_match('/^レッスン(\d+\.\d+)/u', $page['name'], $matches)) {
        $reviewfile = $root . '/review-' . $matches[1] . '.md';
        if (is_file($reviewfile)) {
            $markdown .= "\n" . file_get_contents($reviewfile);
        }
        $links = [];
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->sections[$section->section] ?? [] as $siblingid) {
            $sibling = $modinfo->get_cm($siblingid);
            if ($sibling->modname === 'lti') {
                $links[] = '[Python Labで実行・保存する](' . $sibling->url->out(false) . ')';
            } else if ($sibling->modname === 'quiz') {
                $links[] = '[理解度チェックに取り組む](' . $sibling->url->out(false) . ')';
            }
        }
        $markdown .= "\n## この単元を終えるには\n\n"
            . "本文の例と統合練習を実行し、模範解答と比べて復習します。保存を確認したら、この本文を「完了マークする」にして理解度チェックへ進んでください。"
            . "間違えた問題の解説を読み、本文の該当見出しからやり直せます。\n\n"
            . implode(' → ', $links) . "\n";
    }
    if (!$existing) {
        $info = add_moduleinfo((object)[
            'module' => $moduleid, 'modulename' => 'lessonmark', 'section' => $section->section,
            'name' => $page['name'], 'intro' => '', 'introformat' => FORMAT_HTML,
            'markdownsource' => $markdown, 'visible' => 1, 'visibleoncoursepage' => 1,
            'completion' => COMPLETION_TRACKING_MANUAL, 'groupmode' => 0, 'groupingid' => 0,
            'cmidnumber' => $idnumber,
        ], $course);
        $existing = $DB->get_record('course_modules', ['id' => $info->coursemodule], '*', MUST_EXIST);
    } else {
        $DB->set_field('lessonmark', 'markdownsource', $markdown, ['id' => $existing->instance]);
        $DB->set_field('lessonmark', 'timemodified', time(), ['id' => $existing->instance]);
    }
    $existing->modname = 'lessonmark';
    if ($supplement) {
        $DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_NONE, ['id' => $existing->id]);
    }
    moveto_module($existing, $section, $old);
    set_coursemodule_visible($old->id, 0);
    $created[] = ['page' => $old->id, 'lessonmark' => $existing->id, 'name' => $page['name']];
}
// Keep future chapters closed until their own acceptance check.
foreach ($DB->get_records('course_sections', ['course' => $course->id]) as $section) {
    if (!$section->component && $section->section > $chapter) {
        course_update_section($course, $section, ['visible' => 0]);
    } else if (!$section->component && $section->section === $chapter) {
        course_update_section($course, $section, ['visible' => 1]);
    }
}
// Only existing local verification users; no guest or self enrolment.
$manual = enrol_get_plugin('manual');
$instances = array_filter(enrol_get_instances($course->id, false), fn($e) => $e->enrol === 'manual');
$instance = reset($instances);
if (!$instance) {
    $instance = $DB->get_record('enrol', ['id' => $manual->add_instance($course)], '*', MUST_EXIST);
}
foreach (['dual_3a_guided' => 'student', 'dual_3a_teacher' => 'editingteacher'] as $username => $role) {
    $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id], '*', MUST_EXIST);
    $manual->enrol_user($instance, $user->id, $DB->get_field('role', 'id', ['shortname' => $role], MUST_EXIST));
}
update_course((object)['id' => $course->id, 'visible' => 1]);
if ($chapter === 1) {
    $lab = $DB->get_record('course_modules', ['id' => 613, 'course' => $course->id], '*', MUST_EXIST);
    $DB->set_field('lti', 'toolurl', 'http://localhost:8086/hub/user-redirect/lab/tree/ja/P1_weekly_support_report_dual_path.ipynb', ['id' => $lab->instance]);
    $DB->set_field('lti', 'intro', '<p>weekly_support.pyを完成・保存し、手動確認と8項目の自動確認を行います。'
        . '対応版専用Notebookを使います。ファイルをダウンロードして対応版の1.7提出課題へ提出してください。</p>', ['id' => $lab->instance]);
}
// Align the working-copy settings with the already-published learning-check policy.
foreach (get_fast_modinfo($course)->get_cms() as $cm) {
    $s = get_fast_modinfo($course)->get_section_info($cm->sectionnum);
    if (!$cm->visible || !preg_match('/^' . $chapter . '\./', $s->name ?? '')) {
        continue;
    }
    if ($cm->modname === 'quiz') {
        $after = \mod_quiz\question\display_options::IMMEDIATELY_AFTER
            | \mod_quiz\question\display_options::LATER_WHILE_OPEN
            | \mod_quiz\question\display_options::AFTER_CLOSE;
        $reviews = (object)['id' => $cm->instance];
        foreach (['reviewattempt', 'reviewcorrectness', 'reviewmaxmarks', 'reviewmarks', 'reviewspecificfeedback',
                'reviewgeneralfeedback', 'reviewrightanswer', 'reviewoverallfeedback'] as $field) {
            $reviews->$field = $after;
        }
        $reviews->reviewattempt |= \mod_quiz\question\display_options::DURING;
        $DB->update_record('quiz', $reviews);
        $gradeitem = grade_item::fetch(['courseid' => $course->id, 'itemmodule' => 'quiz',
            'iteminstance' => $cm->instance, 'itemnumber' => 0]);
        if (!$gradeitem || (float)$gradeitem->grademax !== 100.0) {
            throw new RuntimeException('Review grade scale before setting pass grade');
        }
        $gradeitem->gradepass = 90;
        $gradeitem->update();
        $DB->update_record('course_modules', (object)['id' => $cm->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completiongradeitemnumber' => 0,
            'completionpassgrade' => 1]);
    } else if ($cm->modname === 'assign') {
        $DB->set_field('assign', 'completionsubmit', 1, ['id' => $cm->instance]);
        $DB->update_record('course_modules', (object)['id' => $cm->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completiongradeitemnumber' => null,
            'completionpassgrade' => 0]);
    }
}
rebuild_course_cache($course->id, true);
if ($chapter === 3) {
    // One of A/B/C is required. Preserve submissions/grades; a shared manual
    // confirmation tracks the choice instead of requiring all three options.
    $choice = $DB->get_record('course_modules', ['course' => $course->id,
        'idnumber' => 'python-path-page-650'], '*', MUST_EXIST);
    $DB->update_record('course_modules', (object)['id' => $choice->id,
        'completion' => COMPLETION_TRACKING_MANUAL, 'completionview' => 0,
        'completiongradeitemnumber' => null, 'completionpassgrade' => 0]);
    $mi = get_fast_modinfo($course);
    $links = [];
    $ordered = $mi->get_cms();
    $mi->sort_cm_array($ordered);
    foreach ($ordered as $cm) {
        $s = $mi->get_section_info($cm->sectionnum);
        if (!$cm->visible || !preg_match('/^3\.5[ABC]/', $s->name ?? '')) {
            continue;
        }
        $DB->update_record('course_modules', (object)['id' => $cm->id,
            'completion' => COMPLETION_TRACKING_NONE, 'completionview' => 0,
            'completiongradeitemnumber' => null, 'completionpassgrade' => 0]);
        if ($cm->modname === 'assign') {
            $DB->set_field('assign', 'completionsubmit', 0, ['id' => $cm->instance]);
        }
        if ($cm->modname === 'lessonmark') {
            $link = new moodle_url('/mod/lessonmark/view.php', ['id' => $choice->id]);
            $record = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
            $record->markdownsource .= "\n\n## この選択課題を終えたら\n\n"
                . "二つのプログラムを提出し、提出状態を確認したら、[共通の完了確認]("
                . $link->out(false) . ")へ戻って完了マークします。他の二課題は追加練習です。\n";
            $DB->update_record('lessonmark', $record);
            $links[] = '[' . $cm->name . '](' . $cm->url->out(false) . ')';
        }
    }
    $record = $DB->get_record('lessonmark', ['id' => $choice->instance], '*', MUST_EXIST);
    $record->markdownsource .= "\n\n## 課題を開く\n\n" . implode("\n\n", $links) . "\n";
    $DB->update_record('lessonmark', $record);
    rebuild_course_cache($course->id, true);
}
echo json_encode(['course' => $course->id, 'chapter' => $chapter, 'pages' => $created], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
