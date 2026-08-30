<?php
// Verify the guest-accessible IPA AP Question 1 and Question 2 preview course.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->libdir . '/filelib.php';

$shortname = 'IPA-AP-WRITTEN-JA-PREVIEW';
$courseid = (int) (getenv('IPA_AP_PREVIEW_COURSE_ID') ?: 0);
$course = $courseid > 0
    ? $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST)
    : $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$expectedfullname = '応用情報技術者試験 過去問題学習 — 令和7年度春期 午後・公開体験版（問1・問2）';
$validfullname = $course->fullname === $expectedfullname
    || ($courseid > 0 && str_starts_with($course->fullname, $expectedfullname . ' '));
if (!$validfullname) {
    throw new coding_exception('Unexpected preview course fullname: ' . $course->fullname);
}
if (!(bool) $course->visible) {
    throw new coding_exception('The public preview course is not visible.');
}
foreach (['公開体験版', '問1', '問2', '完全版', '全11問'] as $requiredsummary) {
    if (!str_contains($course->summary, $requiredsummary)) {
        throw new coding_exception('Preview summary is missing: ' . $requiredsummary);
    }
}

$expected = [
    '公開体験版の進め方（問1・問2）' => [
        'section' => 0,
        'images' => 0,
        'required' => ['公開体験版', '問1（情報セキュリティ）', '問2（経営戦略）', '完全版は問1〜問11'],
    ],
    '問1 サイバー攻撃への対策 — 公式問題と解答解説' => [
        'section' => 1,
        'images' => 5,
        'response' => 4,
        'choice' => 16,
        'answers' => 10,
        'required' => ['デジタルフォレンジックス', 'a＝オ（辞書）', 'c＝ア'],
    ],
    '問2 中期事業計画と多角化戦略 — 公式問題と解答解説' => [
        'section' => 2,
        'images' => 5,
        'response' => 10,
        'choice' => 0,
        'answers' => 10,
        'required' => ['コア技術を維持してきたこと', 'シナジー', '映像関連事業を売却'],
    ],
];
$expectedsections = [
    0 => 'はじめに — 公開体験版',
    1 => '令和7年度春期 午後 問1［情報セキュリティ］',
    2 => '令和7年度春期 午後 問2［経営戦略］',
];

$cms = get_coursemodules_in_course('lessonmark', $course->id);
if (count($cms) !== count($expected)) {
    throw new coding_exception(
        'Expected ' . count($expected) . ' preview LessonMark activities; found ' . count($cms) . '.'
    );
}

$renderer = new \mod_lessonmark\local\moodle_markdown_renderer();
$fs = get_file_storage();
$result = [];
$totalimages = 0;
foreach ($cms as $cm) {
    $instance = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
    if (!isset($expected[$instance->name])) {
        throw new coding_exception('Unexpected preview LessonMark activity: ' . $instance->name);
    }
    $spec = $expected[$instance->name];
    $section = $DB->get_record('course_sections', ['id' => $cm->section], '*', MUST_EXIST);
    if ((int) $section->section !== $spec['section']) {
        throw new coding_exception('Unexpected section for ' . $instance->name);
    }
    if ($section->name !== $expectedsections[$spec['section']]) {
        throw new coding_exception('Unexpected section name: ' . $section->name);
    }

    $context = context_module::instance($cm->id);
    $document = $renderer->render($instance->markdownsource, $context);
    if ($document->get_diagnostics()) {
        throw new coding_exception('LessonMark diagnostics found: ' . $instance->name);
    }
    $html = $document->get_content_html();
    foreach ($spec['required'] as $requiredtext) {
        if (!str_contains($html, $requiredtext)) {
            throw new coding_exception(
                'Required preview content is missing from ' . $instance->name . ': ' . $requiredtext
            );
        }
    }
    foreach (['[!RESPONSE]', '[!CHOICE]', '[!ANSWER]'] as $marker) {
        if (str_contains($html, $marker)) {
            throw new coding_exception('Unprocessed LessonMark marker remains: ' . $marker);
        }
    }

    $files = $fs->get_area_files(
        $context->id,
        'mod_lessonmark',
        \mod_lessonmark\local\content_files::FILEAREA,
        \mod_lessonmark\local\content_files::ITEMID,
        'filename',
        false
    );
    if (count($files) !== $spec['images']) {
        throw new coding_exception('Unexpected image count for ' . $instance->name);
    }
    if (substr_count($html, '/pluginfile.php/') !== $spec['images']) {
        throw new coding_exception('Unexpected rendered image count for ' . $instance->name);
    }
    $totalimages += count($files);

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
    $result[] = [
        'cmid' => (int) $cm->id,
        'name' => $instance->name,
        'section' => (int) $section->section,
        'images' => count($files),
        'self_checks' => $selfchecks,
    ];
}

$maxsection = (int) $DB->get_field_sql(
    'SELECT MAX(section) FROM {course_sections} WHERE course = :courseid',
    ['courseid' => $course->id]
);
if ($maxsection > 2) {
    throw new coding_exception('The preview course contains a section after Question 2.');
}

$quizmoduleid = $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST);
$quizcount = $DB->count_records('course_modules', [
    'course' => $course->id,
    'module' => $quizmoduleid,
]);
if ($quizcount !== 0) {
    throw new coding_exception('The preview course must not contain a Quiz activity.');
}

$enrolments = $DB->count_records_sql(
    'SELECT COUNT(1)
       FROM {user_enrolments} ue
       JOIN {enrol} e ON e.id = ue.enrolid
      WHERE e.courseid = :courseid',
    ['courseid' => $course->id]
);
if ($enrolments !== 0) {
    throw new coding_exception('The preview course contains user enrolments.');
}

$guestinstances = array_values(array_filter(
    enrol_get_instances($course->id, false),
    static fn(stdClass $instance): bool => $instance->enrol === 'guest'
));
if (count($guestinstances) !== 1) {
    throw new coding_exception('Expected exactly one guest enrolment instance.');
}
$guestinstance = $guestinstances[0];
if ((int) $guestinstance->status !== ENROL_INSTANCE_ENABLED || $guestinstance->password !== '') {
    throw new coding_exception('Password-free guest access is not enabled.');
}

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
    'fullname' => $course->fullname,
    'edition' => 'public-preview',
    'lessonmarks' => count($cms),
    'questions' => 2,
    'source_page_images' => $totalimages,
    'quiz_activities' => $quizcount,
    'user_enrolments' => $enrolments,
    'guest_access' => [
        'enabled' => true,
        'password_required' => false,
    ],
    'activities' => $result,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
