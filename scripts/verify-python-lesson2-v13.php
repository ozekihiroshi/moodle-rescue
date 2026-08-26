<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$result = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
    $lessonname = $language === 'ja'
        ? 'レッスン2：変数・代入・プログラムの状態'
        : 'Lesson 2: Variables, assignment, and program state';
    $quizname = $language === 'ja'
        ? '理解度チェック：レッスン2 変数・代入・プログラムの状態'
        : 'Knowledge check: Lesson 2: Variables, assignment, and program state';
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $lessonname], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V13-LESSON2-FLOW') !== 1) {
        throw new RuntimeException("{$shortname}: Lesson 2 marker missing or duplicated");
    }
    foreach (['Naledi', 'ナレディ', 'AI checkpoint', 'AI利用の確認', 'same mutable list', 'アンパック'] as $forbidden) {
        if (str_contains($page->content, $forbidden)) {
            throw new RuntimeException("{$shortname}: forbidden Lesson 2 text {$forbidden}");
        }
    }
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $slots = (int) $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    if ($slots !== 10) {
        throw new RuntimeException("{$shortname}: expected 10 Lesson 2 quiz slots, found {$slots}");
    }
    if ((float) $quiz->sumgrades !== 100.0) {
        throw new RuntimeException("{$shortname}: Lesson 2 sumgrades is {$quiz->sumgrades}, expected 100");
    }
    $result[] = [
        'courseid' => (int) $course->id,
        'shortname' => $shortname,
        'marker' => 1,
        'quiz_slots' => $slots,
        'sumgrades' => (float) $quiz->sumgrades,
    ];
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
