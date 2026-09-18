<?php
// User-free local adaptation of the canonical English course.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->libdir . '/externallib.php';
require_once $CFG->dirroot . '/course/externallib.php';
require_once $CFG->libdir . '/enrollib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
\core\session\manager::set_user(get_admin());
$source = $DB->get_record('course', ['id'=>10], '*', MUST_EXIST);
$shortname = 'PYAI-INTRO-EN-DUAL-PATH';
$course = $DB->get_record('course', ['shortname'=>$shortname]);
if ($course) {
    if (($argv[1] ?? '') !== '--resume-mapping' || $course->visible || is_file($CFG->dataroot . '/temp/python-english-path/manifest.json')) {
        throw new RuntimeException('Already exists; inspect instead of replacing');
    }
} else {
    $result = core_course_external::duplicate_course($source->id, 'Python Foundations — Self-paced Markdown edition',
        $shortname, $source->category, 0, [['name'=>'users','value'=>0]]);
    $course = get_course($result['id']);
}
update_course((object)['id'=>$course->id,'format'=>'duallearning','learningmode'=>'path','lang'=>'en',
    'visible'=>0,'enablecompletion'=>1,'showgrades'=>1,'enddate'=>0]);
$course = get_course($course->id);
$key = fn($cm)=>get_fast_modinfo($cm->course)->get_section_info($cm->sectionnum)->name . '|' . $cm->modname . '|' . $cm->name;
$targets = []; $map = [];
foreach (get_fast_modinfo($course)->get_cms() as $cm) {
    if (isset($targets[$key($cm)])) { throw new RuntimeException('Ambiguous activity'); }
    $targets[$key($cm)] = $cm->id;
}
foreach (get_fast_modinfo($source)->get_cms() as $cm) {
    if (!isset($targets[$key($cm)])) { throw new RuntimeException('Restore mismatch'); }
    $map[$cm->id] = $targets[$key($cm)];
}
$manual = enrol_get_plugin('manual'); $instance = null;
foreach (enrol_get_instances($course->id, false) as $enrol) {
    if ($enrol->enrol === 'manual') { $instance = $enrol; }
    else { enrol_get_plugin($enrol->enrol)->update_status($enrol, ENROL_INSTANCE_DISABLED); }
}
if (!$instance) { $instance = $DB->get_record('enrol', ['id'=>$manual->add_instance($course)], '*', MUST_EXIST); }
foreach (['dual_3a_guided'=>'student','dual_3a_teacher'=>'editingteacher'] as $username=>$role) {
    $user = $DB->get_record('user', ['username'=>$username,'mnethostid'=>$CFG->mnet_localhost_id], '*', MUST_EXIST);
    $manual->enrol_user($instance, $user->id, $DB->get_field('role','id',['shortname'=>$role], MUST_EXIST));
}
$mi = get_fast_modinfo($course); $cms = $mi->get_cms(); $mi->sort_cm_array($cms);
$manifest = ['source_course'=>10,'courseid'=>(int)$course->id,'shortname'=>$shortname,'map'=>$map,'users_included'=>false,'sections'=>[],'activities'=>[]];
foreach ($mi->get_section_info_all() as $section) {
    $manifest['sections'][] = ['id'=>$section->id,'number'=>$section->section,'name'=>$section->name,'component'=>$section->component,'summary'=>$section->summary];
}
foreach ($cms as $cm) {
    $record = $DB->get_record($cm->modname, ['id'=>$cm->instance], '*', MUST_EXIST);
    $entry = ['id'=>$cm->id,'module'=>$cm->modname,'section'=>$cm->sectionnum,'name'=>$cm->name,'visible'=>(bool)$cm->visible];
    if ($cm->modname === 'page') { $entry += ['html'=>$record->content,'format'=>(int)$record->contentformat]; }
    if ($cm->modname === 'lti') { $entry['toolurl'] = $record->toolurl; }
    if ($cm->modname === 'assign') { $entry['intro'] = $record->intro; }
    $manifest['activities'][] = $entry;
}
$path = make_temp_directory('python-english-path') . '/manifest.json';
file_put_contents($path, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo json_encode(['courseid'=>$course->id,'source'=>$source->shortname,'manifest'=>$path]) . PHP_EOL;
