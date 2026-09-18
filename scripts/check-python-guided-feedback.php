<?php
// One explicit local fixture: feedback without a mastery grade or notification.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/mod/assign/locallib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$english = ($argv[1] ?? '') === '--english';
$course = $DB->get_record('course', ['shortname'=>$english ? 'PYAI-INTRO-EN-DUAL-GUIDED' : 'PYAI-INTRO-JA-DUAL-GUIDED'], '*', MUST_EXIST);
$mapping = json_decode(file_get_contents($CFG->dataroot . '/temp/' . ($english ? 'python-english-guided-full' : 'python-guided-full') . '/mapping.json'), true);
$cm = get_coursemodule_from_id('assign', $mapping['map'][$english ? 1021 : 614], $course->id, false, MUST_EXIST);
$student = $DB->get_record('user', ['username'=>'dual_3a_guided'], '*', MUST_EXIST);
$teacher = $DB->get_record('user', ['username'=>'dual_3a_teacher'], '*', MUST_EXIST);
\core\session\manager::set_user($teacher);
$assignment = new assign(context_module::instance($cm->id), $cm, $course);
$submission = $assignment->get_user_submission($student->id, false);
if (!$submission || $submission->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED) { throw new RuntimeException('Fixture must already be submitted'); }
$message = '【動作確認用コメント】保存済みファイルを受け取りました。自動確認結果と境界値の処理を照合する流れを確認しています。これは習熟度の評価ではありません。';
if ($english) { $message = '[Local test feedback] Your saved file was received. This checks the workflow for comparing automated checks with boundary handling; it is not an assessment of your mastery.'; }
$existing = $assignment->get_user_grade($student->id, false);
if ($existing) {
    $comment = $DB->get_record('assignfeedback_comments', ['grade'=>$existing->id]);
    if (!$comment || $comment->commenttext !== $message) { throw new RuntimeException('Existing feedback is not a test fixture; not overwritten'); }
} else {
    $assignment->save_grade($student->id, (object)['attemptnumber'=>$submission->attemptnumber,'grade'=>-1,
        'addattempt'=>0,'sendstudentnotifications'=>0,
        'assignfeedbackcomments_editor'=>['text'=>$message,'format'=>FORMAT_HTML,'itemid'=>0]]);
}
$grade = $assignment->get_user_grade($student->id, false);
$comment = $DB->get_record('assignfeedback_comments', ['grade'=>$grade->id], '*', MUST_EXIST);
if ($comment->commenttext !== $message || $grade->grader != $teacher->id) { throw new RuntimeException('Feedback mismatch'); }
\core\session\manager::set_user($student);
if (has_capability('mod/assign:grade', context_module::instance($cm->id))) { throw new RuntimeException('Student can grade'); }
$overview = \format_duallearning\local\overview::build($course, $student->id);
echo json_encode(['status'=>'PASS','cmid'=>$cm->id,'grader'=>$teacher->id,'feedback_saved'=>true,
    'grade_awarded'=>false,'student_cannot_grade'=>true,'overview_assignments'=>$overview['assignments']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
