<?php
// Create or refresh the guest-accessible Question 1 and Question 2 preview course.

putenv('IPA_AP_V3_SHORTNAME=IPA-AP-WRITTEN-JA-PREVIEW');
putenv('IPA_AP_CREATE_IF_MISSING=1');

ob_start();
require __DIR__ . '/update-ipa-ap-source-study-course.php';
ob_end_clean();

$course = $DB->get_record(
    'course',
    ['shortname' => 'IPA-AP-WRITTEN-JA-PREVIEW'],
    '*',
    MUST_EXIST
);

// The shared source updater creates all questions. Remove later questions from
// this separate course, working backwards so section numbers remain stable.
for ($sectionnumber = 11; $sectionnumber >= 3; $sectionnumber--) {
    $section = $DB->get_record(
        'course_sections',
        ['course' => $course->id, 'section' => $sectionnumber]
    );
    if ($section && !course_delete_section($course, $sectionnumber, true, false)) {
        throw new coding_exception('Could not remove preview section ' . $sectionnumber);
    }
}

$oldguidename = 'この原文・解答解説版の進め方';
$previewguidename = '公開体験版の進め方（問1・問2）';
$oldguide = $DB->get_record('lessonmark', [
    'course' => $course->id,
    'name' => $oldguidename,
]);
$previouspreview = $DB->get_record('lessonmark', [
    'course' => $course->id,
    'name' => $previewguidename,
]);
if ($previouspreview && (!$oldguide || $previouspreview->id !== $oldguide->id)) {
    $previouscm = get_coursemodule_from_instance(
        'lessonmark',
        $previouspreview->id,
        $course->id,
        false,
        MUST_EXIST
    );
    course_delete_module($previouscm->id);
}
$guide = $oldguide ?: $DB->get_record('lessonmark', [
    'course' => $course->id,
    'name' => $previewguidename,
], '*', MUST_EXIST);
$guidecm = get_coursemodule_from_instance(
    'lessonmark',
    $guide->id,
    $course->id,
    false,
    MUST_EXIST
);
$guide->instance = $guide->id;
$guide->coursemodule = $guidecm->id;
$guide->name = $previewguidename;
$guide->markdownsource = ipa_v3_source(
    (getenv('IPA_AP_CONTENT_ROOT') ?: '/tmp/ap-written-practice-ja') . '/preview/00-guide.md'
);
$guide->intro = $guide->intro ?? '';
$guide->introformat = $guide->introformat ?? FORMAT_HTML;
lessonmark_update_instance($guide);

$courseidentity = (object) [
    'id' => $course->id,
    'fullname' => '応用情報技術者試験 過去問題学習 — 令和7年度春期 午後・公開体験版（問1・問2）',
    'summary' => '<p><strong>公開体験版：</strong>問1（情報セキュリティ）と問2（経営戦略）を収録し、LessonMarkによるMarkdown教材と同一ページ自己確認を体験できます。完全版は全11問です。</p>',
    'summaryformat' => FORMAT_HTML,
    'visible' => 1,
    'timemodified' => time(),
];
$DB->update_record('course', $courseidentity);

$sectionzero = $DB->get_record(
    'course_sections',
    ['course' => $course->id, 'section' => 0],
    '*',
    MUST_EXIST
);
$sectionzero->name = 'はじめに — 公開体験版';
$sectionzero->summary = '<p><strong>公開体験版：</strong>問1・問2を収録しています。完全版は全11問です。</p>';
$sectionzero->summaryformat = FORMAT_HTML;
$DB->update_record('course_sections', $sectionzero);

$guestplugin = enrol_get_plugin('guest');
if (!$guestplugin) {
    throw new coding_exception('Required enrolment plugin is unavailable: guest');
}
$guestinstance = null;
foreach (enrol_get_instances($course->id, false) as $instance) {
    if ($instance->enrol === 'guest') {
        $guestinstance = $instance;
        break;
    }
}
if ($guestinstance) {
    $guestplugin->update_status($guestinstance, ENROL_INSTANCE_ENABLED);
    $DB->set_field('enrol', 'password', '', ['id' => $guestinstance->id]);
} else {
    $guestplugin->add_instance($course, [
        'status' => ENROL_INSTANCE_ENABLED,
        'password' => '',
    ]);
}

rebuild_course_cache($course->id, true);

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
    'fullname' => $courseidentity->fullname,
    'edition' => 'public-preview',
    'questions' => [1, 2],
    'guest_access' => true,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
