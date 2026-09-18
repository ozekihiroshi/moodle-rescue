<?php
// Local release export: active activities only, no users or learning records.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/backup/util/includes/backup_includes.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local export only'); }
\core\session\manager::set_user(get_admin());
$variants = ['ja-path'=>42,'ja-guided'=>43,'en-path'=>44,'en-guided'=>45];
$key = $argv[1] ?? '';
if (!isset($variants[$key])) { throw new RuntimeException('Expected ja-path, ja-guided, en-path or en-guided'); }
$course=get_course($variants[$key]);
$expected='PYAI-INTRO-'.strtoupper(str_replace('-','-DUAL-',$key));
if ($course->shortname !== $expected) { throw new RuntimeException('Unexpected source'); }
$dir=make_temp_directory('python-dual-distribution');
$bc=new backup_controller(backup::TYPE_1COURSE,$course->id,backup::FORMAT_MOODLE,backup::INTERACTIVE_YES,backup::MODE_GENERAL,get_admin()->id);
$plan=$bc->get_plan(); $plan->get_setting('users')->set_value(0);
foreach (['role_assignments','comments','badges','userscompletion','logs','grade_histories','groups'] as $name) {
    if ($plan->setting_exists($name) && $plan->get_setting($name)->get_status()===base_setting::NOT_LOCKED) { $plan->get_setting($name)->set_value(0); }
}
$excluded=[];
foreach (get_fast_modinfo($course)->get_cms() as $cm) {
    if ($cm->visible) { continue; }
    $setting=$cm->modname.'_'.$cm->id.'_included';
    if (!$plan->setting_exists($setting)) { throw new RuntimeException('Missing exclusion setting '.$setting); }
    $plan->get_setting($setting)->set_value(0); $excluded[]=$cm->id;
}
$bc->finish_ui(); $bc->execute_plan(); $file=$bc->get_results()['backup_destination'];
$path=$dir.'/'.$key.'.mbz';
if (!$file->copy_content_to($path)) { throw new RuntimeException('Copy failed'); }
$bc->destroy();
echo json_encode(['variant'=>$key,'source'=>$course->id,'archive'=>$path,'excluded_legacy_activities'=>$excluded,'users'=>false]).PHP_EOL;
