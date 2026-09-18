<?php
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$course = $DB->get_record('course', ['shortname'=>'PYAI-INTRO-EN-DUAL-GUIDED'], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());
$mi = get_fast_modinfo($course); $cms = $mi->get_cms();
$prep = array_filter($cms, fn($cm) => str_starts_with($cm->idnumber, 'python-guided-') && str_ends_with($cm->idnumber, '-prepare'));
$review = array_filter($cms, fn($cm) => str_starts_with($cm->idnumber, 'python-guided-') && str_ends_with($cm->idnumber, '-review'));
if (count($prep) !== 23 || count($review) !== 23) { throw new RuntimeException('Incomplete units'); }
update_course((object)['id'=>$course->id,'fullname'=>'Python Foundations — Teacher-guided Markdown edition',
    'learningmode'=>'guided','visible'=>1,'showgrades'=>1,
    'summary'=>'<p>Chapters 0–6: predict before class, investigate with your teacher, then reproduce independently. Submit chapter projects in this course and read teacher feedback on the assignment page. Your teacher sets class dates and deadlines.</p>',
    'summaryformat'=>FORMAT_HTML]);
rebuild_course_cache($course->id, true);
$course = get_course($course->id);
$student = $DB->get_record('user', ['username'=>'dual_3a_guided','mnethostid'=>$CFG->mnet_localhost_id], '*', MUST_EXIST);
\core\session\manager::set_user($student);
$mi = get_fast_modinfo($course, $student->id); $cms = $mi->get_cms(); $mi->sort_cm_array($cms);
$expected = array_values(array_map(fn($cm)=>(int)$cm->id, array_filter($cms, fn($cm)=>$cm->uservisible && $cm->modname==='lessonmark')));
$presentation = \mod_lessonmark\local\course_presentation::modules($course);
$actual = array_map(fn($cm)=>(int)$cm->id, $presentation);
if ($actual !== $expected) { throw new RuntimeException('LessonMark presentation order differs from course'); }
$overview = \format_duallearning\local\overview::build($course, $student->id);
$forums = array_filter($cms, fn($cm)=>$cm->uservisible && $cm->modname==='forum' && str_starts_with($cm->idnumber,'python-guided-help-'));
if (count($forums)!==7 || !$overview['hasshortcuts']) { throw new RuntimeException('Guided help shortcuts missing'); }
$counts = [];
foreach ($cms as $cm) { if ($cm->uservisible) { $counts[$cm->modname] = ($counts[$cm->modname] ?? 0) + 1; } }
echo json_encode(['status'=>'PASS','courseid'=>$course->id,'mode'=>course_get_format($course)->get_format_options()['learningmode'],
    'counts'=>$counts,'presentation_order'=>true,'question_forums'=>count($forums)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
