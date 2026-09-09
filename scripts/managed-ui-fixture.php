<?php
// Synthetic acceptance fixture; refuses all sites except the isolated 8096 lab.
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
if ($CFG->wwwroot !== 'http://localhost:8096' || $CFG->dbname !== 'managed_ui') {
    throw new RuntimeException('Not the managed UI laboratory.');
}
\core\session\manager::set_user(get_admin());
$shortname = 'MANAGED-UI-ACCEPTANCE';
$course = $DB->get_record('course', ['shortname' => $shortname]);
if (($argv[1] ?? 'check') === 'create') {
    if ($course || get_config('mod_lessonmark', 'version') != 2026083001) {
        throw new RuntimeException('Requires alpha2 and absent fixture.');
    }
    $course = create_course((object) ['fullname' => 'Managed UI upgrade acceptance',
        'shortname' => $shortname, 'category' => 1, 'format' => 'topics', 'numsections' => 1]);
    course_create_sections_if_missing($course, 1);
    $module = $DB->get_record('modules', ['name' => 'lessonmark'], '*', MUST_EXIST);
    $info = add_moduleinfo((object) [
        'course' => $course->id, 'module' => $module->id, 'modulename' => 'lessonmark',
        'section' => 1, 'name' => '画像付き更新確認', 'intro' => '', 'introformat' => FORMAT_HTML,
        'markdownsource' => "# 更新確認\n\n本文と画像を保持します。\n\n![確認用画像](@@PLUGINFILE@@/images/確認.png)\n",
        'displayoptions' => '{"toc":true}', 'visible' => 1, 'visibleoncoursepage' => 1,
        'cmidnumber' => '', 'groupmode' => 0, 'groupingid' => 0, 'completion' => 0,
        'completionview' => 0, 'completionexpected' => 0, 'availabilityconditionsjson' => null,
    ], $course);
    $cm = get_coursemodule_from_instance('lessonmark', $info->instance, $course->id, false, MUST_EXIST);
    $im = imagecreatetruecolor(80, 48);
    imagefilledrectangle($im, 0, 0, 79, 47, imagecolorallocate($im, 30, 60, 220));
    ob_start(); imagepng($im); $bytes = ob_get_clean(); imagedestroy($im);
    get_file_storage()->create_file_from_string([
        'contextid' => context_module::instance($cm->id)->id, 'component' => 'mod_lessonmark',
        'filearea' => 'content', 'itemid' => 0, 'filepath' => '/images/',
        'filename' => '確認.png', 'mimetype' => 'image/png',
    ], $bytes);
}
if (!$course) {
    throw new RuntimeException('Fixture missing.');
}
$item = $DB->get_record('lessonmark', ['course' => $course->id], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('lessonmark', $item->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$file = get_file_storage()->get_file($context->id, 'mod_lessonmark', 'content', 0, '/images/', '確認.png');
if (!$file || getimagesizefromstring($file->get_content())[0] !== 80) {
    throw new RuntimeException('Image missing or damaged.');
}
$html = (new \mod_lessonmark\local\moodle_markdown_renderer())->render($item->markdownsource, $context)->get_content_html();
if (!str_contains(rawurldecode(html_entity_decode($html)), '/images/確認.png') || str_contains($html, '@@PLUGINFILE@@')) {
    throw new RuntimeException('Image URL rendering failed.');
}
echo json_encode([
    'courseid' => $course->id, 'cmid' => $cm->id, 'id' => $item->id,
    'recordsha256' => hash('sha256', json_encode($item)),
    'sourcehash' => hash('sha256', $item->markdownsource),
    'imagepath' => $file->get_filepath() . $file->get_filename(),
    'imagehash' => hash('sha256', $file->get_content()),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
