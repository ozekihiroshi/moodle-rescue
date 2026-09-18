<?php
// Final course-only configuration. No source course, user work, or plugin changes.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/mod/assign/locallib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$course = $DB->get_record('course', ['shortname' => 'PYAI-INTRO-JA-DUAL-PATH'], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());
$specs = [
    614 => [612, ['weekly_support.py']], 631 => [629, ['library_manager.py']],
    654 => [652, ['inspect_school_meals.py', 'meal_delivery_review.py']],
    658 => [656, ['inspect_bus_service.py', 'bus_service_review.py']],
    662 => [660, ['inspect_water_points.py', 'water_point_review.py']],
    682 => [680, ['equipment_lending.py']],
    699 => [697, ['clinic_wait_evidence.py', 'clinic_wait_evidence.png']],
    720 => [718, ['clinic_stock_scaleup.py', 'clinic_stock_summary.csv', 'clinic_stock_evidence.png']],
];
$report = [];
foreach ($specs as $cmid => [$oldpage, $files]) {
    $cm = get_coursemodule_from_id('assign', $cmid, $course->id, false, MUST_EXIST);
    $page = $DB->get_record('course_modules', ['course' => $course->id,
        'idnumber' => 'python-path-page-' . $oldpage], '*', MUST_EXIST);
    $assignment = new assign(context_module::instance($cmid), $cm, $course);
    $assignment->get_submission_plugin_by_type('onlinetext')->set_config('enabled', 0);
    $plugin = $assignment->get_submission_plugin_by_type('file');
    $plugin->set_config('enabled', 1);
    $plugin->set_config('maxfilesubmissions', count($files));
    $extensions = array_unique(array_map(fn($f) => '.' . pathinfo($f, PATHINFO_EXTENSION), $files));
    $plugin->set_config('filetypeslist', implode(',', $extensions));
    $url = new moodle_url('/mod/lessonmark/view.php', ['id' => $page->id]);
    $intro = '<p>完成したファイルを保存し、自分で結果を確認してから自動確認を実行します。'
        . '<a href="' . $url->out(false) . '">課題仕様・完成条件を確認する</a></p><p>提出するファイル：</p><ul>';
    foreach ($files as $file) { $intro .= '<li><code>' . s($file) . '</code></li>'; }
    $intro .= '</ul><p>Python Labから指定ファイルをダウンロードし、この画面へアップロードして保存します。'
        . '保存後に提出ステータスとファイル名を確認してください。Notebookと確認プログラムは提出しません。</p>';
    if (in_array($cmid, [654, 658, 662])) {
        $choice = $DB->get_record('course_modules', ['course' => $course->id, 'idnumber' => 'python-path-page-650'], '*', MUST_EXIST);
        $intro .= '<p>三つの課題のうち一つを提出したら、<a href="'
            . (new moodle_url('/mod/lessonmark/view.php', ['id' => $choice->id]))->out(false)
            . '">第3章の共通確認</a>へ戻って完了マークします。他の課題は追加練習です。</p>';
    }
    $DB->update_record('assign', (object)['id' => $cm->instance, 'intro' => $intro, 'introformat' => FORMAT_HTML]);
    $report[] = ['cmid' => $cmid, 'files' => $files];
}
update_course((object)['id' => $course->id, 'fullname' => 'Python入門 — 自学自習・Markdown対応版']);
rebuild_course_cache($course->id, true);
echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
