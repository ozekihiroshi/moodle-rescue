<?php
// Verify the source-faithful IPA AP study course and all eleven questions.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->libdir . '/filelib.php';
require_once $CFG->libdir . '/clilib.php';

[$options, $unrecognized] = cli_get_params([
    'courseid' => 0,
    'shortname' => getenv('IPA_AP_V3_SHORTNAME') ?: 'IPA-AP-WRITTEN-JA-V3',
    'help' => false,
], [
    'h' => 'help',
]);
if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}
if ($options['help']) {
    echo "Verify the IPA AP written-practice course.\n\n";
    echo "Options:\n";
    echo "--courseid=INTEGER       Verify this exact restored course.\n";
    echo "--shortname=SHORTNAME    Course shortname when courseid is omitted.\n";
    echo "-h, --help               Show this help.\n";
    exit(0);
}

$course = (int) $options['courseid'] > 0
    ? $DB->get_record('course', ['id' => (int) $options['courseid']], '*', MUST_EXIST)
    : $DB->get_record('course', ['shortname' => $options['shortname']], '*', MUST_EXIST);
$expectedfullname = '応用情報技術者試験 過去問題学習 — 令和7年度春期 午後・原文解答解説版';
$validfullname = $course->fullname === $expectedfullname
    || ((int) $options['courseid'] > 0 && str_starts_with($course->fullname, $expectedfullname . ' '));
if (!$validfullname) {
    throw new coding_exception('Unexpected course fullname: ' . $course->fullname);
}
$expecteddomains = [
    1 => '情報セキュリティ',
    2 => '経営戦略',
    3 => 'アルゴリズム・プログラミング',
    4 => 'システムアーキテクチャ・クラウド',
    5 => 'ネットワーク',
    6 => 'データベース',
    7 => '組込み・ソフトウェア設計',
    8 => 'システム開発・エラーハンドリング',
    9 => 'プロジェクトマネジメント',
    10 => 'サービスマネジメント',
    11 => 'システム監査',
];
$expected = [
    'この原文・解答解説版の進め方' => [
        'images' => 0,
        'required' => ['令和7年度春期 午後 — 収録分野', '情報セキュリティ', 'システム監査'],
    ],
    '問1 サイバー攻撃への対策 — 公式問題と解答解説' => [
        'images' => 5, 'response' => 4, 'choice' => 16, 'answers' => 10,
        'required' => ['デジタルフォレンジックス', 'a＝オ（辞書）', 'c＝ア', '問題文を深く読む'],
    ],
    '問2 中期事業計画と多角化戦略 — 公式問題と解答解説' => [
        'images' => 5, 'response' => 10, 'choice' => 0, 'answers' => 10,
        'required' => ['コア技術を維持してきたこと', 'シナジー', '映像関連事業を売却'],
    ],
    '問3 スライドパズルと幅優先探索 — 公式問題と解答解説' => [
        'images' => 6, 'response' => 4, 'choice' => 0, 'answers' => 4,
        'required' => ['explore_queue.isEmpty()', 'checkGoal(new_state.board, goal_board)'],
    ],
    '問4 BEMSのクラウド移行 — 公式問題と解答解説' => [
        'images' => 5, 'response' => 9, 'choice' => 0, 'answers' => 9,
        'required' => ['BEMSゲートウェイ', 'g＝22', '900'],
    ],
    '問5 社内LANの障害対応 — 公式問題と解答解説' => [
        'images' => 5, 'response' => 11, 'choice' => 0, 'answers' => 11,
        'required' => ['192.168.1.21', 'nslookup', 'SNMPトラップ'],
    ],
    '問6 販売管理データベースの設計とSQL — 公式問題と解答解説' => [
        'images' => 4, 'response' => 6, 'choice' => 0, 'answers' => 6,
        'required' => ['LEFT OUTER JOIN', 'ORDER BY 売上実績数 DESC', '集計粒度を日次から週次'],
    ],
    '問7 電動キックボード共有システムの設計 — 公式問題と解答解説' => [
        'images' => 6, 'response' => 9, 'choice' => 0, 'answers' => 9,
        'required' => ['0.1km／時未満が一定時間継続', '電動KBの状態', '①＝0.40'],
    ],
    '問8 CRMシステムのエラーハンドリング — 公式問題と解答解説' => [
        'images' => 5, 'response' => 8, 'choice' => 0, 'answers' => 8,
        'required' => ['AbstractController', 'エラーの詳細情報を出力しない', 'ログは開発担当者も参照するから'],
    ],
    '問9 CCPMによるプロジェクト管理 — 公式問題と解答解説' => [
        'images' => 5, 'response' => 9, 'choice' => 0, 'answers' => 9,
        'required' => ['費用及び要求充足度', 'b＝41', 'J3チームだった要員'],
    ],
    '問10 クラウド時代の容量・能力管理 — 公式問題と解答解説' => [
        'images' => 5, 'response' => 8, 'choice' => 0, 'answers' => 8,
        'required' => ['前日の注文情報に基づく翌日の生産計画', '増強したストレージの容量を元に戻す', 'キャパシティ計画'],
    ],
    '問11 勤務管理システムの監査 — 公式問題と解答解説' => [
        'images' => 4, 'response' => 9, 'choice' => 0, 'answers' => 9,
        'required' => ['入力コントロール', 'd＝一定の組合せ', 'g＝X年3月末日'],
    ],
];

$cms = get_coursemodules_in_course('lessonmark', $course->id);
if (count($cms) !== count($expected)) {
    throw new coding_exception('Expected ' . count($expected) . ' LessonMark activities; found ' . count($cms) . '.');
}

$renderer = new \mod_lessonmark\local\moodle_markdown_renderer();
$result = [];
$totalimages = 0;
$seen = [];
foreach ($cms as $cm) {
    $instance = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
    if (!array_key_exists($instance->name, $expected)) {
        throw new coding_exception('Unexpected LessonMark activity: ' . $instance->name);
    }
    $spec = $expected[$instance->name];
    $context = context_module::instance($cm->id);
    $document = $renderer->render($instance->markdownsource, $context);
    if ($document->get_diagnostics()) {
        throw new coding_exception('LessonMark diagnostics found: ' . $instance->name);
    }
    $html = $document->get_content_html();
    foreach (($spec['required'] ?? []) as $requiredtext) {
        if (!str_contains($html, $requiredtext)) {
            throw new coding_exception('Required answer text is missing from ' . $instance->name . ': ' . $requiredtext);
        }
    }
    foreach (['[!RESPONSE]', '[!CHOICE]', '[!ANSWER]'] as $marker) {
        if (str_contains($html, $marker)) {
            throw new coding_exception('Unprocessed self-check marker remains in ' . $instance->name . ': ' . $marker);
        }
    }

    $files = get_file_storage()->get_area_files(
        $context->id,
        'mod_lessonmark',
        \mod_lessonmark\local\content_files::FILEAREA,
        \mod_lessonmark\local\content_files::ITEMID,
        'filename',
        false
    );
    if (count($files) !== $spec['images']) {
        throw new coding_exception('Unexpected stored image count for ' . $instance->name . '.');
    }
    if (substr_count($html, '/pluginfile.php/') !== $spec['images']) {
        throw new coding_exception('Unexpected rendered image count for ' . $instance->name . '.');
    }
    $totalimages += $spec['images'];

    $selfchecks = null;
    if (isset($spec['answers'])) {
        $selfchecks = [
            'response_controls' => substr_count($html, 'data-self-check-input="response"'),
            'choice_controls' => substr_count($html, 'data-self-check-input="choice"'),
            'answer_disclosures' => substr_count($html, 'mod_lessonmark-selfcheck__answer'),
        ];
        foreach ([
            'response_controls' => $spec['response'],
            'choice_controls' => $spec['choice'],
            'answer_disclosures' => $spec['answers'],
        ] as $control => $wanted) {
            if ($selfchecks[$control] !== $wanted) {
                throw new coding_exception(
                    "Unexpected {$control} for {$instance->name}: {$selfchecks[$control]}; expected {$wanted}."
                );
            }
        }
    }
    $sectionrecord = $DB->get_record('course_sections', ['id' => $cm->section], '*', MUST_EXIST);
    $currentsection = (int) $sectionrecord->section;
    $expectedsection = 0;
    if (preg_match('/^問([0-9]+)/u', $instance->name, $matches)) {
        $expectedsection = (int) $matches[1];
    }
    if ($currentsection !== $expectedsection) {
        throw new coding_exception(
            "Unexpected section for {$instance->name}: {$currentsection}; expected {$expectedsection}."
        );
    }
    $expectedsectionname = $expectedsection === 0
        ? 'はじめに'
        : "令和7年度春期 午後 問{$expectedsection}［{$expecteddomains[$expectedsection]}］";
    if ($sectionrecord->name !== $expectedsectionname) {
        throw new coding_exception(
            "Unexpected section name for {$instance->name}: {$sectionrecord->name}; expected {$expectedsectionname}."
        );
    }
    $seen[$instance->name] = true;
    $result[] = [
        'cmid' => (int) $cm->id,
        'name' => $instance->name,
        'section' => $currentsection,
        'html_bytes' => strlen($html),
        'diagnostics' => 0,
        'images' => count($files),
        'self_checks' => $selfchecks,
    ];
}
foreach (array_keys($expected) as $name) {
    if (!isset($seen[$name])) {
        throw new coding_exception('Expected LessonMark activity was not found: ' . $name);
    }
}

$quizmoduleid = $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST);
$quizactivitycount = $DB->count_records_sql(
    'SELECT COUNT(1) FROM {course_modules} WHERE course = :courseid AND module = :moduleid',
    ['courseid' => $course->id, 'moduleid' => $quizmoduleid]
);
if ($quizactivitycount !== 0) {
    throw new coding_exception('V3 must not contain a separate Quiz activity.');
}

$enrolments = $DB->count_records_sql(
    'SELECT COUNT(1) FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid WHERE e.courseid = :courseid',
    ['courseid' => $course->id]
);
if ($enrolments !== 0) {
    throw new coding_exception('Distribution course contains user enrolments.');
}

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
    'lessonmarks' => count($cms),
    'questions' => 11,
    'source_page_images' => $totalimages,
    'quiz_activities' => $quizactivitycount,
    'user_enrolments' => $enrolments,
    'activities' => $result,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
