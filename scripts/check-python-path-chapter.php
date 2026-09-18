<?php
// Read-only public API check; simulated completion is rolled back in this isolated course.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->libdir . '/completionlib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local working-copy check only');
}
$course = $DB->get_record('course', ['shortname' => 'PYAI-INTRO-JA-DUAL-PATH'], '*', MUST_EXIST);
$student = $DB->get_record('user', ['username' => 'dual_3a_guided', 'mnethostid' => $CFG->mnet_localhost_id], '*', MUST_EXIST);
\core\session\manager::set_user($student);
$mi = get_fast_modinfo($course, $student->id);
$cms = $mi->get_cms();
$mi->sort_cm_array($cms);
$visible = array_filter($cms, fn($cm) => $cm->uservisible && !$cm->deletioninprogress);
$results = ['version' => get_config('format_duallearning', 'version'), 'visible_lessons' => [], 'quiz_slots' => []];
foreach ($visible as $cm) {
    if ($cm->modname === 'lessonmark') {
        $results['visible_lessons'][] = $cm->name;
        $record = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
        $renderer = new \mod_lessonmark\local\moodle_markdown_renderer();
        $rendered = $renderer->render($record->markdownsource, $cm->context)->get_content_html();
        if (preg_match('/<\/?(?:details|summary)\b/i', $record->markdownsource)) {
            throw new RuntimeException('Legacy HTML disclosure needs Markdown conversion: ' . $cm->id);
        }
        $originalid = (int)str_replace('python-path-page-', '', $cm->idnumber);
        $originalcm = $DB->get_record('course_modules', ['id' => $originalid, 'course' => $course->id], '*', MUST_EXIST);
        $original = $DB->get_record('page', ['id' => $originalcm->instance], '*', MUST_EXIST);
        if (in_array((int)$original->contentformat, [FORMAT_HTML, FORMAT_MARKDOWN])) {
            $preblocks = function($html) {
                $doc = new DOMDocument();
                @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
                $blocks = [];
                foreach ($doc->getElementsByTagName('pre') as $pre) {
                    $blocks[] = trim(str_replace("\r\n", "\n", $pre->textContent));
                }
                return $blocks;
            };
            $originalhtml = $original->contentformat == FORMAT_HTML ? $original->content
                : $renderer->render($original->content, $cm->context)->get_content_html();
            $sourceblocks = $preblocks($originalhtml);
            $convertedblocks = $preblocks($rendered);
            // Reviewed submission-bridge removal is the sole intentional code deletion.
            $sourceblocks = array_values(array_filter($sourceblocks,
                fn($b) => !str_contains($b, '/submit_weekly_support.py')));
            foreach ($sourceblocks as $block) {
                if (!in_array($block, $convertedblocks, true)) {
                    throw new RuntimeException('Code block changed during conversion: ' . $cm->id . "\n" . $block
                        . "\nRendered blocks:\n" . json_encode($convertedblocks, JSON_UNESCAPED_UNICODE));
                }
            }
            $results['preserved_code_blocks'][$cm->id] = count($sourceblocks);
        }
        if (str_contains($record->markdownsource, '[!ANSWER]') && !str_contains($rendered, '<details')) {
            throw new RuntimeException('Answer disclosure not rendered: ' . $cm->id);
        }
    }
    if ($cm->modname === 'quiz') {
        $results['quiz_slots'][$cm->name] = $DB->count_records('quiz_slots', ['quizid' => $cm->instance]);
    }
}
$results['next_initial'] = \format_duallearning\local\overview::build($course, $student->id)['next'];
$expectedpresentation = array_values(array_map(fn($cm) => (int)$cm->id,
    array_filter($visible, fn($cm) => $cm->modname === 'lessonmark' && $cm->visible && $cm->visibleoncoursepage)));
$presentation = \mod_lessonmark\local\course_presentation::modules($course);
$results['presentation_expected'] = $expectedpresentation;
$results['presentation_actual'] = array_map(fn($cm) => (int)$cm->id, $presentation);
$results['presentation_names'] = array_map(fn($cm) => $cm->name, $presentation);
$results['presentation_order_pass'] = $results['presentation_actual'] === $expectedpresentation;
$transaction = $DB->start_delegated_transaction();
try {
    foreach ($visible as $cm) {
        if ($cm->modname === 'lessonmark' && str_starts_with($cm->name, 'レッスン1.3')) {
            $expected = $cm->url->out(false);
            break;
        }
        if ($cm->completion) {
            $existing = $DB->get_record('course_modules_completion', ['coursemoduleid' => $cm->id, 'userid' => $student->id]);
            if ($existing) {
                $DB->set_field('course_modules_completion', 'completionstate', COMPLETION_COMPLETE, ['id' => $existing->id]);
            } else {
                $DB->insert_record('course_modules_completion', (object)['coursemoduleid' => $cm->id,
                    'userid' => $student->id, 'completionstate' => COMPLETION_COMPLETE, 'viewed' => 1,
                    'timemodified' => time()]);
            }
        }
    }
    \cache::make('core', 'completion')->purge();
    $results['next_after_12_simulated'] = \format_duallearning\local\overview::build($course, $student->id)['next'];
    $results['order_pass'] = $results['next_after_12_simulated']['url'] === $expected;
    $transaction->rollback(new RuntimeException('ROLLBACK_TEST_STATE'));
} catch (RuntimeException $e) {
    if ($e->getMessage() !== 'ROLLBACK_TEST_STATE') {
        throw $e;
    }
}
\cache::make('core', 'completion')->purge();
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
exit($results['order_pass'] && $results['presentation_order_pass'] ? 0 : 1);
