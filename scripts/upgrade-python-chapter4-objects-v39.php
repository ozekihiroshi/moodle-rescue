<?php
// Insert a complete objects/classes chapter and shift the existing later chapters.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/resourcelib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';

use core_courseformat\formatactions;
use core_question\local\bank\question_version_status;

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';

function v39_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

function v39_page_html(array $lesson): string {
    $outcomes = implode('', array_map(fn($value) => '<li>' . s($value) . '</li>', $lesson['outcomes']));
    $summary = implode('', array_map(fn($value) => '<li>' . s($value) . '</li>', $lesson['summary']));
    $stages = '';
    foreach ($lesson['stages'] as $stage) {
        $stages .= '<h2>' . s($stage[0]) . '</h2>' . $stage[1];
    }
    return '<div class="python-sample-lesson"><h2>' . s($lesson['introheading']) . '</h2><p>'
        . s($lesson['intro']) . '</p><h2>' . s($lesson['outcomeheading']) . '</h2><ul>' . $outcomes
        . '</ul><p><strong>' . s($lesson['route']) . '</strong></p>' . $stages
        . '<h2>' . s($lesson['summaryheading']) . '</h2><ul>' . $summary . '</ul><h2>'
        . s($lesson['nextheading']) . '</h2><p>' . s($lesson['next'])
        . '</p><p style="display:none">PYAI-V39-OBJECTS-CLASSES</p></div>';
}

function v39_normal_section(stdClass $course, string $name): ?section_info {
    foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
        if ($section && $section->section > 0 && empty($section->component) && $section->name === $name) {
            return $section;
        }
    }
    return null;
}

function v39_rename_activity(int $courseid, string $table, string $old, string $new): void {
    global $DB;
    $record = $DB->get_record($table, ['course' => $courseid, 'name' => $old]);
    if (!$record) {
        if (!$DB->record_exists($table, ['course' => $courseid, 'name' => $new])) {
            throw new RuntimeException("Missing {$table} activity: {$old}");
        }
        return;
    }
    $record->name = $new;
    if (property_exists($record, 'timemodified')) {
        $record->timemodified = time();
    }
    $DB->update_record($table, $record);
}

function v39_rename_subsection(stdClass $course, string $old, string $new): void {
    global $DB;
    $record = $DB->get_record('subsection', ['course' => $course->id, 'name' => $old]);
    if (!$record) {
        $record = $DB->get_record('subsection', ['course' => $course->id, 'name' => $new], '*', MUST_EXIST);
    } else {
        $record->name = $new;
        $DB->update_record('subsection', $record);
    }
    $delegated = $DB->get_record('course_sections', [
        'course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $record->id,
    ], '*', MUST_EXIST);
    course_update_section($course, $delegated, ['name' => $new]);
}

function v39_prepare_chapters(stdClass $course, bool $ja): section_info {
    global $DB;
    $newname = $ja ? '第4章 — オブジェクトとクラス' : 'Chapter 4 — Objects and Classes';
    $existing = v39_normal_section($course, $newname);
    if (!$existing) {
        course_create_sections_if_missing($course, 6);
        $newsection = get_fast_modinfo($course)->get_section_info(6, MUST_EXIST);
        course_update_section($course, $newsection, [
            'name' => $newname,
            'summary' => $ja
                ? '<p>辞書と関数で扱ってきた状態と処理を、正しい状態を守るオブジェクトとして再構成します。</p>'
                : '<p>Reorganise familiar records and functions as objects that protect valid state and cooperate.</p>',
            'summaryformat' => FORMAT_HTML,
            'visible' => 1,
        ]);
        move_section_to($course, 6, 4);
        rebuild_course_cache($course->id, true);
    }
    $chapter4 = v39_normal_section($course, $newname);
    if (!$chapter4) {
        throw new RuntimeException('New Chapter 4 was not created');
    }

    $chapter5 = get_fast_modinfo($course)->get_section_info(5, MUST_EXIST);
    $chapter6 = get_fast_modinfo($course)->get_section_info(6, MUST_EXIST);
    course_update_section($course, $chapter5, [
        'name' => $ja ? '第5章 — 根拠を伝える' : 'Chapter 5 — Communicating Evidence',
    ]);
    course_update_section($course, $chapter6, [
        'name' => $ja ? '第6章 — より大きなデータへの拡張' : 'Chapter 6 — Scaling Up',
    ]);

    if ($ja) {
        $subsections = [
            ['4.1 可視化と根拠', '5.1 可視化と根拠'],
            ['4.2 ガイド付きプロジェクト：学習センター分析', '5.2 ガイド付きプロジェクト：学習センター分析'],
            ['4.3 最終プロジェクト：問いから根拠へ', '5.3 最終プロジェクト：問いから根拠へ'],
            ['5.1 大きなCSVファイルを安全に処理する', '6.1 大きなCSVファイルを安全に処理する'],
            ['5.2 スケールアップ総合プロジェクト', '6.2 スケールアップ総合プロジェクト'],
        ];
        $activities = [
            ['page','レッスン4.1：可視化と根拠','レッスン5.1：可視化と根拠'],
            ['lti','Python Lab 4.1：可視化と根拠','Python Lab 5.1：可視化と根拠'],
            ['quiz','理解度チェック：4.1 可視化と根拠','理解度チェック：5.1 可視化と根拠'],
            ['page','4.2 データセットとプロジェクト手順','5.2 データセットとプロジェクト手順'],
            ['lti','Python Lab 4.2：学習センター分析','Python Lab 5.2：学習センター分析'],
            ['assign','提出課題4.2：学習センター分析','提出課題5.2：学習センター分析'],
        ];
    } else {
        $subsections = [
            ['4.1 Visualisation and evidence', '5.1 Visualisation and evidence'],
            ['4.2 Guided project: Learning-centre analysis', '5.2 Guided project: Learning-centre analysis'],
            ['4.3 Final project: From question to evidence', '5.3 Final project: From question to evidence'],
            ['5.1 Processing larger CSV files safely', '6.1 Processing larger CSV files safely'],
            ['5.2 Scale-up capstone project', '6.2 Scale-up capstone project'],
        ];
        $activities = [
            ['page','Lesson 4.1: Visualisation and evidence','Lesson 5.1: Visualisation and evidence'],
            ['lti','Python Lab 4.1: Visualisation and evidence','Python Lab 5.1: Visualisation and evidence'],
            ['quiz','Knowledge check: 4.1 Visualisation and evidence','Knowledge check: 5.1 Visualisation and evidence'],
            ['page','4.2 Dataset and project brief','5.2 Dataset and project brief'],
            ['lti','Python Lab 4.2: Learning-centre analysis','Python Lab 5.2: Learning-centre analysis'],
            ['assign','Assignment 4.2: Learning-centre analysis','Assignment 5.2: Learning-centre analysis'],
        ];
    }
    foreach ($subsections as [$old, $new]) {
        v39_rename_subsection($course, $old, $new);
    }
    foreach ($activities as [$table, $old, $new]) {
        v39_rename_activity($course->id, $table, $old, $new);
    }
    rebuild_course_cache($course->id, true);
    return get_fast_modinfo($course)->get_section_info(4, MUST_EXIST);
}

function v39_subsection(stdClass $course, section_info $parent, string $name, string $summary): array {
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
    return [(int)$cm->id, (int)$delegated->id, (int)$delegated->section];
}

function v39_page(stdClass $course, int $section, string $name, string $intro, string $content): stdClass {
    global $DB;
    $record = $DB->get_record('page', ['course' => $course->id, 'name' => $name]);
    if ($record) {
        return get_coursemodule_from_instance('page', $record->id, $course->id, false, MUST_EXIST);
    }
    return add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'modulename' => 'page', 'section' => $section, 'name' => $name,
        'intro' => '<p>' . s($intro) . '</p>', 'introformat' => FORMAT_HTML,
        'content' => $content, 'contentformat' => FORMAT_HTML, 'display' => RESOURCELIB_DISPLAY_OPEN,
        'printintro' => 0, 'printlastmodified' => 0, 'visible' => 1, 'visibleoncoursepage' => 1,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 0,
    ], $course);
}

function v39_cmid(stdClass $module): int {
    return (int)($module->coursemodule ?? $module->id);
}

function v39_lti(stdClass $course, int $section, string $name, string $intro, string $path): stdClass {
    global $DB;
    $record = $DB->get_record('lti', ['course' => $course->id, 'name' => $name]);
    if ($record) {
        return get_coursemodule_from_instance('lti', $record->id, $course->id, false, MUST_EXIST);
    }
    $prototypes = $DB->get_records('lti', ['course' => $course->id], 'id ASC');
    $prototype = reset($prototypes);
    if (!$prototype) {
        throw new RuntimeException('No existing Python Lab LTI prototype was found');
    }
    $toolurl = preg_replace('~/(?:ja/)?[^/]+\.ipynb$~', $path, $prototype->toolurl);
    if (!$toolurl || $toolurl === $prototype->toolurl) {
        throw new RuntimeException('Unable to construct the Python Lab URL');
    }
    return add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST),
        'modulename' => 'lti', 'section' => $section, 'name' => $name,
        'intro' => '<p>' . s($intro) . '</p>', 'introformat' => FORMAT_HTML,
        'typeid' => $prototype->typeid, 'toolurl' => $toolurl,
        'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
        'instructorchoicesendname' => LTI_SETTING_NEVER,
        'instructorchoicesendemailaddr' => LTI_SETTING_NEVER,
        'instructorchoiceacceptgrades' => LTI_SETTING_NEVER, 'grade' => 0,
        'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
        'completion' => 0, 'showdescription' => 1,
    ], $course);
}

function v39_question(int $categoryid, int $contextid, string $prefix, array $data): stdClass {
    $question = (object)['qtype' => 'multichoice', 'category' => "{$categoryid},{$contextid}"];
    $answers = $feedback = $fractions = [];
    foreach ($data['a'] as $index => $answer) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => $index === $data['ok'] ? '<p>Correct.</p>' : '<p>Trace the object and try again.</p>', 'format' => FORMAT_HTML];
        $fractions[] = $index === $data['ok'] ? 1.0 : 0.0;
    }
    $form = (object)[
        'name' => $prefix . $data['id'], 'category' => "{$categoryid},{$contextid}",
        'questiontext' => ['text' => '<p>' . s($data['q']) . '</p>', 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p>' . s($data['why']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 10, 'penalty' => .3333333,
        'status' => question_version_status::QUESTION_STATUS_READY, 'idnumber' => null,
        'single' => 1, 'shuffleanswers' => 1, 'answernumbering' => 'abc', 'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => '<p>Correct. Connect the answer to the object state.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => '<p>Not yet. Run or trace the smallest example and retry.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions, 'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v39_quiz(stdClass $course, int $section, string $name, array $questions): stdClass {
    global $DB;
    $record = $DB->get_record('quiz', ['course' => $course->id, 'name' => $name]);
    if ($record) {
        return get_coursemodule_from_instance('quiz', $record->id, $course->id, false, MUST_EXIST);
    }
    $created = add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'modulename' => 'quiz', 'section' => $section, 'name' => $name,
        'intro' => '<p>This is a repeatable learning check. The highest score is kept; 90% passes and 100% is the target.</p>',
        'introformat' => FORMAT_HTML, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0,
        'overduehandling' => 'autosubmit', 'graceperiod' => 0, 'preferredbehaviour' => 'deferredfeedback',
        'attempts' => 0, 'attemptonlast' => 0, 'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 0, 'questiondecimalpoints' => -1, 'questionsperpage' => 10,
        'navmethod' => QUIZ_NAVMETHOD_FREE, 'shuffleanswers' => 1, 'grade' => 100,
        'reviewattempt' => 69888, 'reviewcorrectness' => 4352, 'reviewmarks' => 4352,
        'reviewspecificfeedback' => 4352, 'reviewgeneralfeedback' => 4352,
        'reviewrightanswer' => 4352, 'reviewoverallfeedback' => 4352,
        'password' => '', 'quizpassword' => '', 'subnet' => '', 'browsersecurity' => '-',
        'delay1' => 0, 'delay2' => 0, 'visible' => 1, 'visibleoncoursepage' => 1,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
    $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
    $context = context_course::instance($course->id);
    $category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
    if (!$category) {
        $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
        $category = reset($categories);
    }
    foreach ($questions as $questiondata) {
        $question = v39_question($category->id, $context->id, $course->shortname . ' v39: ', $questiondata);
        quiz_add_quiz_question($question->id, $quiz, 0, 10);
    }
    $DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    return $created;
}

function v39_assignment(stdClass $course, int $section, string $name, string $intro): stdClass {
    global $DB;
    $record = $DB->get_record('assign', ['course' => $course->id, 'name' => $name]);
    if ($record) {
        return get_coursemodule_from_instance('assign', $record->id, $course->id, false, MUST_EXIST);
    }
    return add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST),
        'modulename' => 'assign', 'section' => $section, 'name' => $name,
        'intro' => $intro, 'introformat' => FORMAT_HTML, 'alwaysshowdescription' => 1,
        'submissiondrafts' => 0, 'requiresubmissionstatement' => 0, 'sendnotifications' => 0,
        'sendlatenotifications' => 0, 'sendstudentnotifications' => 1, 'duedate' => 0,
        'cutoffdate' => 0, 'gradingduedate' => 0, 'allowsubmissionsfromdate' => 0,
        'grade' => 100, 'attemptreopenmethod' => 'manual', 'maxattempts' => -1,
        'teamsubmission' => 0, 'requireallteammemberssubmit' => 0, 'blindmarking' => 0,
        'markingworkflow' => 0, 'markingallocation' => 0,
        'assignsubmission_onlinetext_enabled' => 0, 'assignsubmission_file_enabled' => 1,
        'assignsubmission_file_maxfiles' => 1, 'assignsubmission_file_maxsizebytes' => 0,
        'assignfeedback_comments_enabled' => 1, 'visible' => 1, 'visibleoncoursepage' => 1,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
}

function v39_q(string $id, string $q, array $a, int $ok, string $why): array {
    return compact('id', 'q', 'a', 'ok', 'why');
}

$chapter4 = v39_prepare_chapters($course, $ja);
$commoncode = v39_code("class EquipmentItem:\n    def __init__(self, item_id, name):\n        self.item_id = item_id\n        self.name = name\n        self.borrower_id = None\n\n    def is_available(self):\n        return self.borrower_id is None");

if ($ja) {
    $labels = [
        ['4.1 レコードと関数からオブジェクトへ','レッスン4.1：レコードと関数からオブジェクトへ','Python Lab 4.1：オブジェクトとクラス','理解度チェック：4.1 オブジェクトとクラス','13_objects_classes.ipynb'],
        ['4.2 状態・メソッド・正しいオブジェクト','レッスン4.2：状態・メソッド・正しいオブジェクト','Python Lab 4.2：状態と検証','理解度チェック：4.2 状態と検証','14_object_state_validation.ipynb'],
        ['4.3 複数オブジェクト・合成・責任分担','レッスン4.3：複数オブジェクト・合成・責任分担','Python Lab 4.3：合成と責任分担','理解度チェック：4.3 合成と責任分担','15_composition_responsibility.ipynb'],
        ['4.4 オブジェクトの保存とテスト','レッスン4.4：オブジェクトの保存とテスト','Python Lab 4.4：保存とテスト','理解度チェック：4.4 保存とテスト','16_object_persistence_testing.ipynb'],
    ];
    $lessons = [
        ['introheading'=>'辞書と関数で動くところから始める','intro'=>'一件の機材は辞書で表せます。貸出関数も作れます。同じ問題をクラスでも表し、状態と操作が一つになる変化を比較します。','outcomeheading'=>'このレッスンを終えるとできること','outcomes'=>['クラスとインスタンスを区別する','属性とメソッドをコードから特定する','__init__とselfを使って独立した個体を作る','辞書と関数の方が簡単な場合も判断する'],'route'=>'必須：クラス、インスタンス、属性、メソッド、__init__、self。','stages'=>[['4.1.1 辞書と関数で表した機材','<p>既習の方法は誤りではありません。対象が少なく規則も単純なら十分です。</p>'.v39_code('$item = ["item_id" => "E001"];// Pythonでは辞書と関数で状態を扱ってきました')],['4.1.2 クラスは生成方法と振る舞いを定義する','<p>クラスは設計、インスタンスはそこから作られた個体です。</p>'.$commoncode],['4.1.3 selfは呼び出しを受けた個体を表す','<p><code>first.loan_to()</code>なら<code>self</code>はfirstです。別のインスタンスの属性は変わりません。</p>'],['4.1.4 値の一致と同一性を区別する','<p><code>==</code>は値の比較、<code>is</code>は同じオブジェクトかを調べます。</p>'],['4.1.5 統合練習','<p>二件を作り、一件だけ名称変更と貸出を行い、もう一件が変わらないことを確認します。</p>']],'summaryheading'=>'まとめ','summary'=>['クラスは状態と操作をまとめる設計単位である','インスタンスごとに属性が独立する','小さな処理では辞書と関数も有効である'],'nextheading'=>'次のレッスンへ','next'=>'次は、クラスへ代入を移すだけでなく、メソッドで不正な状態を防ぎます。','route'=>''],
        ['introheading'=>'正しい状態だけを作り、保つ','intro'=>'貸出中の機材を再び貸し出すと記録が壊れます。生成時と状態変更時に条件を確認します。','outcomeheading'=>'このレッスンを終えるとできること','outcomes'=>['生成時に必須値を検証する','状態遷移をメソッドで表す','例外発生後も以前の状態を保つ','正常経路と拒否経路をassertで確認する'],'route'=>'必須：コンストラクタ検証、状態遷移、例外、事後状態。','stages'=>[['4.2.1 生成時に不正な値を拒否する','<p>空白除去後の必須値を確認し、属性へ代入する前に例外を出します。</p>'],['4.2.2 利用可能と貸出中を一つの属性で表す','<p><code>borrower_id is None</code>を利用可能とします。矛盾する二つの真偽値を持たせません。</p>'],['4.2.3 貸出と返却を状態遷移として実装する','<p>先に条件を検査し、許可された場合だけ状態を一度変更します。</p>'],['4.2.4 失敗した操作で途中更新しない','<p>例外後に利用者IDが以前のままであることも確認します。</p>'],['4.2.5 統合練習','<p>正常な貸出・返却、二重貸出、未貸出の返却、空IDを確認します。</p>']],'summaryheading'=>'まとめ','summary'=>['不正なオブジェクトを生成時に拒否した','状態変更の規則を対象のメソッドへ置いた','成功と失敗の後の状態を確認した'],'nextheading'=>'次のレッスンへ','next'=>'次は複数の機材を管理する別のオブジェクトを作り、責任を分けます。'],
        ['introheading'=>'一件の規則とコレクションの規則を分ける','intro'=>'機材は自分の貸出状態を守り、貸出窓口はどの機材が存在するかを管理します。','outcomeheading'=>'このレッスンを終えるとできること','outcomes'=>['オブジェクトのコレクションを管理する','合成を使って別クラスのオブジェクトを保持する','規則を担当オブジェクトへ委譲する','内部コレクションを直接置換させず結果を返す'],'route'=>'必須：コレクション、検索、委譲、合成、責任分担。','stages'=>[['4.3.1 管理オブジェクトが機材を持つ','<p><code>LendingDesk</code>はIDをキーに機材オブジェクトを保持します。</p>'],['4.3.2 ID一意性と検索は窓口が担当する','<p>追加時に重複を拒否し、検索結果がなければNoneまたは公開仕様どおりの例外を使います。</p>'],['4.3.3 貸出規則は機材へ委ねる','<p>窓口は対象を探し、<code>item.loan_to()</code>を呼びます。規則を二重実装しません。</p>'],['4.3.4 利用可能一覧は新しいリストとして返す','<p>外側のコードに内部辞書そのものを置き換えさせません。</p>'],['4.3.5 統合練習','<p>二件を追加し、一件を窓口経由で貸し出し、残る利用可能一覧と集計を確認します。</p>']],'summaryheading'=>'まとめ','summary'=>['合成で窓口が機材を保持した','検索と所属、個体の状態変更を別の責任にした','処理を規則の所有者へ委譲した'],'nextheading'=>'次のレッスンへ','next'=>'次はメモリ上のオブジェクトを保存用レコードへ変換し、振る舞いをテストします。'],
        ['introheading'=>'オブジェクトとCSVの境界を作る','intro'=>'オブジェクトはメモリ上で動き、CSVは文字列の行を保存します。境界で変換し、各メソッドへファイル処理を混ぜません。','outcomeheading'=>'このレッスンを終えるとできること','outcomes'=>['オブジェクトを保存用辞書へ変換する','コレクションを安定した順序でCSV保存する','内部表現ではなく公開された振る舞いをテストする','継承と合成の扱いを区別する'],'route'=>'必須：レコード変換、保存境界、振る舞いのテスト。継承は発展。','stages'=>[['4.4.1 オブジェクトをレコードへ変換する','<p><code>to_record()</code>はCSV列と一致する辞書を返します。利用可能なら利用者IDは空文字列です。</p>'],['4.4.2 コレクションが保存順を調整する','<p>ID順に並べ、各オブジェクトから一件のレコードを取得してDictWriterへ渡します。</p>'],['4.4.3 テストは公開された振る舞いを見る','<p>生成、正常遷移、拒否遷移、独立した個体、委譲、保存結果を確認します。</p>'],['4.4.4 継承は発展として位置付ける','<p>継承、プロパティ、クラスメソッド、dataclassesは発展です。明確な関係がなければ合成を優先します。</p>'],['4.4.5 プロジェクト前の統合確認','<p>機材と窓口を組み合わせ、貸出後の集計と保存CSVをassertで照合します。</p>']],'summaryheading'=>'まとめ','summary'=>['実行中の状態と保存レコードを分離した','コレクションからCSVを安定して保存した','公開仕様に対して正常・異常経路を確認した'],'nextheading'=>'4.5プロジェクトへ','next'=>'共用機材貸出窓口を完成させ、クラスによる状態管理の効果を一つの動く成果物で確認します。'],
    ];
} else {
    $labels = [
        ['4.1 From records and functions to objects','Lesson 4.1: From records and functions to objects','Python Lab 4.1: Objects and classes','Knowledge check: 4.1 Objects and classes','13_objects_classes.ipynb'],
        ['4.2 State, methods, and valid objects','Lesson 4.2: State, methods, and valid objects','Python Lab 4.2: State and validation','Knowledge check: 4.2 State and validation','14_object_state_validation.ipynb'],
        ['4.3 Collections, composition, and responsibility','Lesson 4.3: Collections, composition, and responsibility','Python Lab 4.3: Composition and responsibility','Knowledge check: 4.3 Composition and responsibility','15_composition_responsibility.ipynb'],
        ['4.4 Persistence and testing class-based programs','Lesson 4.4: Persistence and testing class-based programs','Python Lab 4.4: Persistence and testing','Knowledge check: 4.4 Persistence and testing','16_object_persistence_testing.ipynb'],
    ];
    $lessons = [
        ['introheading'=>'Begin with working records and functions','intro'=>'A dictionary can represent one item and a function can lend it. Compare that valid approach with a class and observe what changes.','outcomeheading'=>'After this lesson you can','outcomes'=>['distinguish class and instance','identify attributes and methods','use __init__ and self to create independent instances','decide when a dictionary and functions remain simpler'],'route'=>'Required: class, instance, attribute, method, __init__, and self.','stages'=>[['4.1.1 Represent equipment with a record and function','<p>The earlier approach is not wrong. It is often sufficient for a few simple records.</p>'],['4.1.2 A class defines construction and behaviour','<p>A class is the design; an instance is one object created from it.</p>'.$commoncode],['4.1.3 self means the receiving instance','<p>In <code>first.loan_to()</code>, self is first. Other instances keep their own state.</p>'],['4.1.4 Distinguish equality and identity','<p><code>==</code> compares values while <code>is</code> checks object identity.</p>'],['4.1.5 Integrated practice','<p>Create two objects, rename and lend one, and verify the other is unchanged.</p>']],'summaryheading'=>'Summary','summary'=>['a class joins related state and operations','each instance owns independent attributes','small tasks may still be clearer with records and functions'],'nextheading'=>'Next lesson','next'=>'Methods will now protect valid state rather than only relocate assignments.'],
        ['introheading'=>'Create and preserve valid state','intro'=>'Double lending corrupts a record. Validate construction and every transition before changing state.','outcomeheading'=>'After this lesson you can','outcomes'=>['validate required constructor values','represent transitions with methods','preserve state after a rejected operation','check allowed and rejected paths with assertions'],'route'=>'Required: constructor validation, transitions, exceptions, and post-state.','stages'=>[['4.2.1 Reject invalid construction','<p>Clean required strings and raise before assigning attributes.</p>'],['4.2.2 Represent availability with one source of truth','<p><code>borrower_id is None</code> means available; avoid contradictory flags.</p>'],['4.2.3 Implement loan and return as transitions','<p>Check first and change state once only when the operation is allowed.</p>'],['4.2.4 Avoid partial updates on failure','<p>After an expected exception, assert that the previous borrower remains.</p>'],['4.2.5 Integrated practice','<p>Check normal loan/return, double lending, return while available, and empty identifiers.</p>']],'summaryheading'=>'Summary','summary'=>['invalid objects are rejected at construction','transition rules live beside the state they protect','tests observe successful and failed post-state'],'nextheading'=>'Next lesson','next'=>'A separate manager object will coordinate a collection while each item keeps its own rules.'],
        ['introheading'=>'Separate item rules from collection rules','intro'=>'An item protects its loan state; a lending desk knows which items exist.','outcomeheading'=>'After this lesson you can','outcomes'=>['manage a collection of objects','use composition to contain other objects','delegate work to the object that owns a rule','return useful views without replacement access'],'route'=>'Required: collection, lookup, delegation, composition, responsibility.','stages'=>[['4.3.1 A manager object owns the item collection','<p><code>LendingDesk</code> stores item objects by identifier.</p>'],['4.3.2 The desk owns identity and lookup','<p>Reject duplicate IDs and make missing-item behaviour explicit.</p>'],['4.3.3 Delegate lending to the item','<p>The desk finds the object and calls <code>item.loan_to()</code>; it does not copy the rule.</p>'],['4.3.4 Return a new available-items list','<p>Do not hand callers the internal collection for replacement.</p>'],['4.3.5 Integrated practice','<p>Add two objects, lend one through the desk, and inspect the available list and summary.</p>']],'summaryheading'=>'Summary','summary'=>['composition lets a desk contain items','membership and per-item state have separate owners','delegation avoids duplicated rules'],'nextheading'=>'Next lesson','next'=>'Convert objects at the persistence boundary and test published behaviour.'],
        ['introheading'=>'Build a boundary between objects and CSV','intro'=>'Objects operate in memory while CSV stores string records. Convert at the boundary rather than teaching every domain method about files.','outcomeheading'=>'After this lesson you can','outcomes'=>['convert an object to a storage record','save a collection in stable order','test public behaviour rather than private spelling','place inheritance outside the required route'],'route'=>'Required: record conversion, persistence boundary, behavioural checks. Inheritance is extension work.','stages'=>[['4.4.1 Convert one object to one record','<p><code>to_record()</code> returns the published CSV columns; an available borrower is an empty string.</p>'],['4.4.2 Let the collection coordinate saving','<p>Sort by ID and ask each object for one record before using DictWriter.</p>'],['4.4.3 Test public behaviour','<p>Check construction, normal and rejected transitions, independent instances, delegation, and saved records.</p>'],['4.4.4 Treat inheritance as further study','<p>Inheritance, properties, class methods, and dataclasses are useful later. Prefer composition without a clear is-a relationship.</p>'],['4.4.5 Integrated project readiness check','<p>Combine item and desk, then reconcile summary counts and saved CSV with assertions.</p>']],'summaryheading'=>'Summary','summary'=>['runtime state is separate from storage records','the collection writes a stable CSV','normal and rejected paths are tested against the contract'],'nextheading'=>'Project 4.5','next'=>'Complete the community equipment lending desk and observe how classes organise a growing stateful problem.'],
    ];
}

$basequestions = [
    ['01','What does a class describe?',['How objects are constructed and behave','One fixed object only','A CSV delimiter','A loop counter'],0,'A class defines construction and behaviour.'],
    ['02','What is an instance?',['One object created from a class','The class name itself','Only a method','A file path'],0,'An instance is a particular object.'],
    ['03','What does self refer to inside an instance method?',['The object receiving the call','Every object at once','The module','The parent folder'],0,'self is the receiving instance.'],
    ['04','Where should a rule rejecting double lending live?',['Beside the item state in a method','In every caller','Only in a comment','In a chart title'],0,'Keep a state rule with the state it protects.'],
    ['05','What should happen before changing state in a transition?',['Validate that the operation is allowed','Save partial changes','Delete the object','Change every instance'],0,'Validate first to avoid partial updates.'],
    ['06','What is composition?',['One object contains or uses other objects','Every class inherits','Two strings are joined','A CSV is sorted'],0,'Composition models a has-a relationship.'],
    ['07','Why delegate desk.loan_item to item.loan_to?',['Avoid duplicating the item rule','Make two loans','Hide every error','Replace the collection'],0,'The object owning the rule should enforce it.'],
    ['08','What should to_record return here?',['A dictionary matching storage columns','A new LendingDesk','A chart','Only True'],0,'A record bridges object state to CSV fields.'],
    ['09','What is the strongest object test?',['Observe state after allowed and rejected calls','Check the variable spelling only','Run no code','Compare screenshots'],0,'Behavioural tests observe public results and state.'],
    ['10','When are dictionaries and functions still reasonable?',['When the state and rules are small and clear','Never','Only with inheritance','Only for errors'],0,'Classes are a design choice, not an automatic requirement.'],
];
if ($ja) {
    $basequestions = [
        ['01','クラスが記述するものは何ですか。',['オブジェクトの生成方法と振る舞い','一つの固定オブジェクトだけ','CSVの区切り文字','ループ回数'],0,'クラスは生成方法と振る舞いを定義します。'],
        ['02','インスタンスとは何ですか。',['クラスから作られた一つのオブジェクト','クラス名そのもの','メソッドだけ','ファイルパス'],0,'インスタンスは具体的な一個体です。'],
        ['03','インスタンスメソッド内のselfは何を表しますか。',['呼び出しを受けたオブジェクト','全オブジェクトを同時に','モジュール','親フォルダ'],0,'selfは呼び出しを受けた個体です。'],
        ['04','二重貸出を拒否する規則はどこへ置くのが自然ですか。',['機材の状態を守るメソッド','すべての呼び出し側','コメントだけ','グラフタイトル'],0,'状態を守る規則は状態の近くへ置きます。'],
        ['05','状態遷移で属性を変える前に何をしますか。',['操作が許可されるか検証する','途中の変更を保存する','オブジェクトを削除する','全インスタンスを変更する'],0,'先に検証すると途中更新を防げます。'],
        ['06','合成とは何ですか。',['一つのオブジェクトが別のオブジェクトを保持・利用すること','全クラスが継承すること','文字列結合','CSV整列'],0,'合成はhas-a関係を表します。'],
        ['07','desk.loan_itemからitem.loan_toへ委譲する理由は何ですか。',['機材規則の重複を避けるため','二回貸し出すため','全エラーを隠すため','コレクションを置換するため'],0,'規則を所有する対象が検証します。'],
        ['08','この課題のto_recordが返すものは何ですか。',['保存列に対応する辞書','新しいLendingDesk','グラフ','Trueだけ'],0,'保存境界ではオブジェクトをレコードへ変換します。'],
        ['09','強いオブジェクトの確認方法はどれですか。',['成功・拒否呼び出し後の状態を観察する','変数名だけを見る','実行しない','画面画像だけ比較する'],0,'公開された振る舞いと事後状態を確認します。'],
        ['10','辞書と関数のままでもよいのはいつですか。',['状態と規則が小さく明確な場合','一度もない','継承がある場合だけ','エラーの場合だけ'],0,'クラスは自動的な正解ではなく設計上の選択です。'],
    ];
}

$created = [];
foreach ($labels as $index => [$topicname,$pagename,$ltiname,$quizname,$notebook]) {
    [$subcmid,$sectionid,$sectionnumber] = v39_subsection($course, $chapter4, $topicname, $lessons[$index]['intro']);
    $page = v39_page($course, $sectionnumber, $pagename, $lessons[$index]['intro'], v39_page_html($lessons[$index]));
    $lti = v39_lti($course, $sectionnumber, $ltiname, $lessons[$index]['next'], ($ja ? '/ja/' : '/') . $notebook);
    $questions = [];
    foreach ($basequestions as [$id,$q,$a,$ok,$why]) {
        $questions[] = v39_q('C4L'.($index + 1).'-'.$id, $q, $a, $ok, $why);
    }
    $quiz = v39_quiz($course, $sectionnumber, $quizname, $questions);
    foreach ([v39_cmid($page),v39_cmid($lti),v39_cmid($quiz)] as $cmid) {
        formatactions::cm($course)->move_end_section($cmid, $sectionid);
    }
    $created[] = ['topic'=>$topicname,'subsection_cmid'=>$subcmid,'activities'=>[v39_cmid($page),v39_cmid($lti),v39_cmid($quiz)]];
}

$projecttopic = $ja ? '4.5 応用プロジェクト：共用機材貸出窓口' : '4.5 Applied project: Community equipment lending desk';
[$subcmid,$sectionid,$sectionnumber] = v39_subsection($course, $chapter4, $projecttopic,
    $ja ? '機材と窓口の二つのクラスで、貸出状態とコレクションの規則を実装します。'
        : 'Implement item state and collection rules with cooperating item and desk classes.');
$specpath = $CFG->dirroot . '/../sample-content-not-mounted';
$briefcontent = $ja
    ? '<div class="python-sample-lesson"><h2>課題の状況</h2><p>学習センターの共用機材について、二重貸出を防ぎ、貸出・返却・保存を一貫して扱うプログラムを完成させます。</p><h2>編集するファイル</h2><p>Python Labの<code>projects/equipment-lending/equipment_lending.py</code>です。一から作らず、公開されたクラス名、メソッド名、引数を保ってTODOを完成させます。</p><h2>実装順</h2><ol><li>EquipmentItemの生成と利用可能判定</li><li>貸出・返却・名称変更・レコード変換</li><li>LendingDeskの追加・検索・委譲・一覧・集計</li><li>CSV保存とrun_project</li></ol><h2>代表結果</h2><p>サンプル3件を処理すると、全3件、利用可能2件、貸出中1件です。E001だけがM014へ貸出中になります。</p><h2>完成条件</h2><p><code>python projects/equipment-lending/check_equipment_lending.py</code>を実行し、7領域がOK、最後が<code>ALL TESTS PASSED</code>となることを確認します。提出は<code>equipment_lending.py</code>だけです。</p><p>各メソッドの詳細契約はPython Lab内READMEとスターターのコメントに記載されています。</p><p style="display:none">PYAI-V39-PROJECT45</p></div>'
    : '<div class="python-sample-lesson"><h2>Situation</h2><p>Complete one program that prevents double lending and keeps loan, return, and saved equipment state consistent.</p><h2>File to edit</h2><p>Use <code>projects/equipment-lending/equipment_lending.py</code> in Python Lab. Do not start from an empty file; preserve the published class, method, and argument names.</p><h2>Implementation order</h2><ol><li>EquipmentItem construction and availability</li><li>loan, return, rename, and record conversion</li><li>LendingDesk add, lookup, delegation, list, and summary</li><li>CSV saving and run_project</li></ol><h2>Representative result</h2><p>After the three supplied items are processed, expect 3 total, 2 available, and 1 loaned. Only E001 is on loan to M014.</p><h2>Completion</h2><p>Run <code>python projects/equipment-lending/check_equipment_lending.py</code>. Seven areas must report OK followed by <code>ALL TESTS PASSED</code>. Submit only <code>equipment_lending.py</code>.</p><p>The detailed method contract is in the Python Lab README and starter comments.</p><p style="display:none">PYAI-V39-PROJECT45</p></div>';
$page = v39_page($course, $sectionnumber,
    $ja ? '4.5 課題仕様と完成条件' : '4.5 Project brief and completion contract',
    $ja ? '状況、編集ファイル、公開契約、代表結果、確認方法を先に確認します。' : 'Review the situation, editable file, contract, representative result, and checks.',
    $briefcontent);
$lti = v39_lti($course, $sectionnumber,
    $ja ? 'Python Lab 4.5：共用機材貸出窓口' : 'Python Lab 4.5: Community equipment lending desk',
    $ja ? 'スターターを4段階で完成させ、手動確認後に確認プログラムを実行します。' : 'Complete the starter in four passes, inspect it, then run the checker.',
    ($ja ? '/ja/' : '/') . 'P4_equipment_lending.ipynb');
$assign = v39_assignment($course, $sectionnumber,
    $ja ? '提出課題4.5：共用機材貸出窓口' : 'Assignment 4.5: Community equipment lending desk',
    $ja
        ? '<p><code>ALL TESTS PASSED</code>を確認した<code>equipment_lending.py</code>一つを提出します。確認プログラムや生成CSVは提出しません。</p>'
        : '<p>Submit only <code>equipment_lending.py</code> after the checker reports <code>ALL TESTS PASSED</code>. Do not submit the checker or generated CSV.</p>');
foreach ([v39_cmid($page),v39_cmid($lti),v39_cmid($assign)] as $cmid) {
    formatactions::cm($course)->move_end_section($cmid, $sectionid);
}
$created[] = ['topic'=>$projecttopic,'subsection_cmid'=>$subcmid,'activities'=>[v39_cmid($page),v39_cmid($lti),v39_cmid($assign)]];

rebuild_course_cache($course->id, true);
echo json_encode([
    'status'=>'ok','course_id'=>(int)$course->id,'shortname'=>$course->shortname,
    'chapter4_section'=>(int)$chapter4->section,'topics'=>$created,'marker'=>'PYAI-V39-OBJECTS-CLASSES',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
