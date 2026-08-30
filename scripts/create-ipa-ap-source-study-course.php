<?php
// Synchronise the source-faithful IPA AP study course with all afternoon questions.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/lessonmark/lib.php';
require_once $CFG->libdir . '/filelib.php';

\core\session\manager::set_user(get_admin());
putenv('IPA_AP_CREATE_IF_MISSING=1');

$shortname = getenv('IPA_AP_V3_SHORTNAME') ?: 'IPA-AP-WRITTEN-JA-V3';
$contentroot = rtrim(getenv('IPA_AP_CONTENT_ROOT') ?: '/tmp/ap-written-practice-ja', '/');

function ipa_v3_source(string $path): string {
    $source = is_readable($path) ? file_get_contents($path) : false;
    if ($source === false || trim($source) === '' || !mb_check_encoding($source, 'UTF-8')) {
        throw new coding_exception('Invalid Markdown source: ' . $path);
    }
    return $source;
}

function ipa_v3_questions(): array {
    return [
        1 => ['サイバー攻撃への対策', '2025-spring-security-v3', [6, 7, 8, 9, 10], '情報セキュリティ'],
        2 => ['中期事業計画と多角化戦略', '2025-spring-q02-business-strategy-v3', [12, 13, 14, 15, 16], '経営戦略'],
        3 => ['スライドパズルと幅優先探索', '2025-spring-q03-slide-puzzle-bfs-v3', [18, 19, 20, 21, 22, 23], 'アルゴリズム・プログラミング'],
        4 => ['BEMSのクラウド移行', '2025-spring-q04-bems-cloud-migration-v3', [24, 25, 26, 27, 28], 'システムアーキテクチャ・クラウド'],
        5 => ['社内LANの障害対応', '2025-spring-q05-lan-troubleshooting-v3', [30, 31, 32, 33, 34], 'ネットワーク'],
        6 => ['販売管理データベースの設計とSQL', '2025-spring-q06-sales-database-v3', [36, 37, 38, 39], 'データベース'],
        7 => ['電動キックボード共有システムの設計', '2025-spring-q07-kickboard-software-design-v3', [40, 41, 42, 43, 44, 45], '組込み・ソフトウェア設計'],
        8 => ['CRMシステムのエラーハンドリング', '2025-spring-q08-crm-error-handling-v3', [46, 47, 48, 49, 50], 'システム開発・エラーハンドリング'],
        9 => ['CCPMによるプロジェクト管理', '2025-spring-q09-ccpm-project-management-v3', [52, 53, 54, 55, 56], 'プロジェクトマネジメント'],
        10 => ['クラウド時代の容量・能力管理', '2025-spring-q10-capacity-management-v3', [58, 59, 60, 61, 62], 'サービスマネジメント'],
        11 => ['勤務管理システムの監査', '2025-spring-q11-attendance-system-audit-v3', [64, 65, 66, 67], 'システム監査'],
    ];
}

function ipa_v3_sync_lessonmark(stdClass $course, int $section, string $name, string $source,
        array $images = []): array {
    global $DB;

    $instance = $DB->get_record('lessonmark', ['course' => $course->id, 'name' => $name]);
    $created = false;
    if (!$instance) {
        $moduleinfo = add_moduleinfo((object) [
            'module' => $DB->get_field('modules', 'id', ['name' => 'lessonmark'], MUST_EXIST),
            'modulename' => 'lessonmark',
            'section' => $section,
            'name' => $name,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'markdownsource' => $source,
            'displayoptions' => null,
            'visible' => 1,
            'visibleoncoursepage' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'showdescription' => 0,
        ], $course);
        $instance = $DB->get_record('lessonmark', ['id' => $moduleinfo->instance], '*', MUST_EXIST);
        $cm = get_coursemodule_from_id('lessonmark', $moduleinfo->coursemodule, $course->id, false, MUST_EXIST);
        $created = true;
    } else {
        $cm = get_coursemodule_from_instance('lessonmark', $instance->id, $course->id, false, MUST_EXIST);
        $currentsectionnum = (int) $DB->get_field(
            'course_sections',
            'section',
            ['id' => $cm->section],
            MUST_EXIST
        );
        if ($currentsectionnum !== $section) {
            $targetsection = $DB->get_record(
                'course_sections',
                ['course' => $course->id, 'section' => $section],
                '*',
                MUST_EXIST
            );
            (new \core_courseformat\local\cmactions($course))->move_end_section($cm->id, $targetsection->id);
            $cm = get_coursemodule_from_instance('lessonmark', $instance->id, $course->id, false, MUST_EXIST);
        }
    }

    $instance->instance = $instance->id;
    $instance->coursemodule = $cm->id;
    $instance->name = $name;
    $instance->markdownsource = $source;
    $instance->intro = $instance->intro ?? '';
    $instance->introformat = $instance->introformat ?? FORMAT_HTML;

    if ($images !== []) {
        $context = context_module::instance($cm->id);
        $draftitemid = file_get_unused_draft_itemid();
        \mod_lessonmark\local\content_files::prepare_draft_area($draftitemid, $context);
        $usercontext = context_user::instance(get_admin()->id);
        $fs = get_file_storage();
        $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftitemid);
        foreach ($images as $image) {
            if (!is_readable($image)) {
                throw new coding_exception('Image source is not readable: ' . $image);
            }
            $fs->create_file_from_pathname([
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/images/',
                'filename' => basename($image),
                'mimetype' => 'image/png',
                'userid' => get_admin()->id,
            ], $image);
        }
        $instance->lessonmarkfiles = $draftitemid;
    }

    lessonmark_update_instance($instance);

    return [
        'cmid' => (int) $cm->id,
        'instanceid' => (int) $instance->id,
        'name' => $name,
        'section' => $section,
        'created' => $created,
        'source_bytes' => strlen($source),
        'images' => count($images),
    ];
}

$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    if (getenv('IPA_AP_CREATE_IF_MISSING') !== '1') {
        throw new moodle_exception('invalidcourseid', 'error');
    }
    $course = create_course((object) [
        'fullname' => '応用情報技術者試験 過去問題学習 — 令和7年度春期 午後・原文解答解説版',
        'shortname' => $shortname,
        'category' => 1,
        'format' => 'topics',
        'visible' => 1,
        'enablecompletion' => 1,
        'summary' => '<p>IPA公式問題をページ画像で正確に提示し、公式解答例と教材独自解説を同じページで照合するパイロットコースです。</p>',
        'summaryformat' => FORMAT_HTML,
        'startdate' => usergetmidnight(time()),
    ]);
}
$courseidentity = (object) [
    'id' => $course->id,
    'fullname' => '応用情報技術者試験 過去問題学習 — 令和7年度春期 午後・原文解答解説版',
    'summary' => '<p>令和7年度春期の応用情報技術者試験午後・問1〜問11を、IPA公式問題、公式解答例、教材独自解説で学ぶパイロットコースです。</p>',
    'summaryformat' => FORMAT_HTML,
    'timemodified' => time(),
];
$DB->update_record('course', $courseidentity);
$course = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);

if (!$DB->record_exists('modules', ['name' => 'lessonmark'])) {
    throw new coding_exception('Required module is not installed: lessonmark');
}

course_create_sections_if_missing($course, range(0, 11));
$sectiondefinitions = [
    0 => ['はじめに', '原文を正として、同じページ内で解答と解説を照合する学習方法を確認します。'],
];
foreach (ipa_v3_questions() as $number => [$title, $unit, $pages, $domain]) {
    $sectiondefinitions[$number] = [
        "令和7年度春期 午後 問{$number}［{$domain}］",
        "{$domain}分野。公式問題" . count($pages) . "ページと、設問別の公式解答例・解説を同じ活動内で学びます。",
    ];
}
foreach ($sectiondefinitions as $number => [$name, $summary]) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $number], '*', MUST_EXIST);
    $section->name = $name;
    $section->summary = '<p>' . s($summary) . '</p>';
    $section->summaryformat = FORMAT_HTML;
    $DB->update_record('course_sections', $section);
}

$synchronised = [];
$synchronised[] = ipa_v3_sync_lessonmark(
    $course,
    0,
    'この原文・解答解説版の進め方',
    ipa_v3_source($contentroot . '/units/2025-spring-security-v3/00-guide.md')
);
foreach (ipa_v3_questions() as $number => [$title, $unit, $pages, $domain]) {
    $unitroot = $contentroot . '/units/' . $unit;
    $images = [];
    foreach ($pages as $page) {
        $images[] = sprintf('%s/images/page-%02d.png', $unitroot, $page);
    }
    $synchronised[] = ipa_v3_sync_lessonmark(
        $course,
        $number,
        "問{$number} {$title} — 公式問題と解答解説",
        ipa_v3_source($unitroot . '/10-official-problem-and-commentary.md'),
        $images
    );
}

rebuild_course_cache($course->id, true);

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
    'lessonmarks' => count($synchronised),
    'questions' => count(ipa_v3_questions()),
    'synchronised' => $synchronised,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;