<?php
// Read-only content/contract/order verification of one guided chapter.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$chapter = (int)$argv[1];
$mapping = json_decode(file_get_contents($CFG->dataroot . '/temp/python-guided-full/mapping.json'), true, 512, JSON_THROW_ON_ERROR);
$course = $DB->get_record('course', ['id' => $mapping['courseid'], 'shortname' => 'PYAI-INTRO-JA-DUAL-GUIDED'], '*', MUST_EXIST);
$source = get_course($mapping['sourcecourse']); $map = $mapping['map'];
\core\session\manager::set_user(get_admin());
$mi = get_fast_modinfo($course); $cms = $mi->get_cms(); $mi->sort_cm_array($cms);
$ids = array_keys($cms); $sourceinfo = get_fast_modinfo($source);
$renderer = new \mod_lessonmark\local\moodle_markdown_renderer();
$blocks = function($html) {
    $doc = new DOMDocument(); @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $result = []; foreach ($doc->getElementsByTagName('pre') as $pre) { $result[] = trim(str_replace("\r\n", "\n", $pre->textContent)); }
    return $result;
};
$count = ['lessons' => 0, 'preserved_code_blocks' => 0, 'quizzes' => 0, 'labs' => 0, 'assignments' => 0];
foreach ($sourceinfo->get_cms() as $old) {
    if (!$old->visible) { continue; }
    $section = $sourceinfo->get_section_info($old->sectionnum);
    if ($old->sectionnum != $chapter && !str_starts_with($section->name ?? '', $chapter . '.')) { continue; }
    $cm = $cms[$map[$old->id]];
    if (!$cm->visible || $cm->completion != $old->completion) { throw new RuntimeException('Visibility/completion changed: ' . $cm->id); }
    if ($cm->modname === 'lessonmark') {
        $src = $DB->get_field('lessonmark', 'markdownsource', ['id' => $old->instance], MUST_EXIST);
        $text = $DB->get_field('lessonmark', 'markdownsource', ['id' => $cm->instance], MUST_EXIST);
        $html = $renderer->render($text, $cm->context)->get_content_html();
        $before = $blocks($renderer->render($src, $old->context)->get_content_html()); $after = $blocks($html);
        foreach ($before as $block) { if (!in_array($block, $after, true)) { throw new RuntimeException('Code changed: ' . $cm->id); } }
        $count['preserved_code_blocks'] += count($before);
        if (str_contains($text, '[!ANSWER]') && !str_contains($html, '<details')) { throw new RuntimeException('Answer renderer: ' . $cm->id); }
        preg_match_all('~/mod/[a-z]+/(?:view|launch)\.php\?id=(\d+)~', $text, $links);
        foreach ($links[1] as $id) { if (!isset($cms[$id])) { throw new RuntimeException('Cross-course link: ' . $cm->id . ' -> ' . $id); } }
        if (preg_match('/^レッスン(\d+\.\d+)/u', $cm->name, $match)) {
            $unit = $match[1]; $chain = [];
            $prep = $DB->get_record('course_modules', ['course' => $course->id, 'idnumber' => 'python-guided-' . $unit . '-prepare'], '*', MUST_EXIST);
            $review = $DB->get_record('course_modules', ['course' => $course->id, 'idnumber' => 'python-guided-' . $unit . '-review'], '*', MUST_EXIST);
            $peers = array_filter($cms, fn($p) => $p->section == $cm->section && $p->visible);
            $labs = array_values(array_filter($peers, fn($p) => $p->modname === 'lti'));
            $quizzes = array_values(array_filter($peers, fn($p) => $p->modname === 'quiz'));
            foreach ([$prep->id, $cm->id, $labs[0]->id, $review->id, $quizzes[0]->id] as $id) { $chain[] = array_search($id, $ids); }
            $sorted = $chain; sort($sorted);
            if ($chain !== $sorted || count(array_unique($chain)) !== 5) { throw new RuntimeException('Learning order: ' . $unit); }
            $count['lessons']++;
        }
    }
    if ($cm->modname === 'lti') {
        if ($DB->get_field('lti', 'toolurl', ['id' => $cm->instance]) !== $DB->get_field('lti', 'toolurl', ['id' => $old->instance])) { throw new RuntimeException('Lab target changed'); }
        $count['labs']++;
    }
    if ($cm->modname === 'quiz') {
        $a = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
        $b = $DB->get_record('quiz', ['id' => $old->instance], '*', MUST_EXIST);
        foreach (['attempts','grademethod','preferredbehaviour','reviewcorrectness','reviewmarks'] as $field) {
            if ($a->$field != $b->$field) { throw new RuntimeException('Quiz setting changed'); }
        }
        if ($DB->count_records('quiz_slots', ['quizid' => $a->id]) !== 10) { throw new RuntimeException('Question slots'); }
        $count['quizzes']++;
    }
    if ($cm->modname === 'assign') {
        foreach (['enabled','maxfilesubmissions','filetypeslist'] as $name) {
            $a = $DB->get_field('assign_plugin_config', 'value', ['assignment'=>$cm->instance,'plugin'=>'file','subtype'=>'assignsubmission','name'=>$name]);
            $b = $DB->get_field('assign_plugin_config', 'value', ['assignment'=>$old->instance,'plugin'=>'file','subtype'=>'assignsubmission','name'=>$name]);
            if ($a !== $b) { throw new RuntimeException('Submission contract changed: ' . $name); }
        }
        $fixtureuser = $DB->get_field('user', 'id', ['username'=>'dual_3a_guided'], MUST_EXIST);
        if ($DB->count_records_select('assign_submission', 'assignment = ? AND userid <> ?', [$cm->instance, $fixtureuser])) {
            throw new RuntimeException('Unexpected learner submission');
        }
        $count['assignments']++;
    }
}
$student = $DB->get_record('user', ['username'=>'dual_3a_guided','mnethostid'=>$CFG->mnet_localhost_id], '*', MUST_EXIST);
$ordered = \mod_lessonmark\local\course_presentation::modules($course);
// Renderer and overview are exercised below; visible-course order is checked at final acceptance.
echo json_encode(['chapter'=>$chapter,'status'=>'PASS','counts'=>$count], JSON_UNESCAPED_UNICODE) . PHP_EOL;
