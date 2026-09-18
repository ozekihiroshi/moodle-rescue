<?php
// Restore the public artifact as a new hidden, user-free acceptance course.
define('CLI_SCRIPT',true);
require '/var/www/html/public/config.php';
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = true;
require_once $CFG->dirroot.'/course/lib.php';
require_once $CFG->dirroot.'/backup/util/includes/restore_includes.php';
if($CFG->wwwroot!=='http://localhost:8083'){throw new RuntimeException('Local test only');}
$key=$argv[1]??'';
if(!in_array($key,['en-path','en-guided','ja-path','ja-guided'])){throw new RuntimeException('Unknown variant');}
$short='DUAL-DIST-TEST-'.strtoupper($key);
$admin=get_admin(); \core\session\manager::set_user($admin);
$target=$DB->get_record('course',['shortname'=>$short]);
if(!$target || !$DB->count_records('course_modules',['course'=>$target->id])){
    $file='/tmp/python-dual-dist/python-foundations-dual-'.$key.'-0.1.0-alpha.1.mbz';
    $temp=restore_controller::get_tempdir_name(0,$admin->id);
    $path=make_backup_temp_directory($temp);
    if(!get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($file,$path)){throw new RuntimeException('Extract failed');}
    $id=$target ? $target->id : restore_dbops::create_new_course('[Restore test] '.$key,$short,1);
    $rc=new restore_controller($temp,$id,backup::INTERACTIVE_NO,backup::MODE_GENERAL,$admin->id,backup::TARGET_NEW_COURSE);
    if(!$rc->execute_precheck()){throw new RuntimeException(json_encode($rc->get_precheck_results()));}
    $rc->execute_plan(); $rc->destroy();
    update_course((object)['id'=>$id,'shortname'=>$short,'fullname'=>'[Restore test] '.$key,'visible'=>0]);
    $target=get_course($id);
}
$mi=get_fast_modinfo($target); $cms=$mi->get_cms();$mi->sort_cm_array($cms); $counts=[];$links=0;$slots=0;
foreach($cms as $cm){
    if(!$cm->visible){throw new RuntimeException('Legacy hidden activity restored');}
    $counts[$cm->modname]=($counts[$cm->modname]??0)+1;
    if($cm->modname==='lessonmark'){
        $text=$DB->get_field('lessonmark','markdownsource',['id'=>$cm->instance],MUST_EXIST);
        if(str_contains($text,'$@')){throw new RuntimeException('Undecoded token in '.$cm->id);}
        preg_match_all('~/mod/[a-z]+/view\.php\?id=(\d+)~',$text,$matches);
        foreach($matches[1] as $id){if(!isset($cms[$id])){throw new RuntimeException('Cross-course restored link '.$cm->id.' -> '.$id);} $links++;}
    }
    if($cm->modname==='quiz'){
        $q=$DB->get_record('quiz',['id'=>$cm->instance],'*',MUST_EXIST);
        if($q->attempts!=0||$q->grademethod!=1){throw new RuntimeException('Quiz settings');}
        $refs=$DB->get_records('question_references',['usingcontextid'=>$cm->context->id,'component'=>'mod_quiz','questionarea'=>'slot']);
        if(count($refs)!==10){throw new RuntimeException('Question count');}
        foreach($refs as $ref){
            $entry=$DB->get_record('question_bank_entries',['id'=>$ref->questionbankentryid],'*',MUST_EXIST);
            $category=$DB->get_record('question_categories',['id'=>$entry->questioncategoryid],'*',MUST_EXIST);
            if((int)$category->contextid!==(int)$cm->context->id){throw new RuntimeException('Wrong question context');}
        }
        $slots+=count($refs);
    }
    if($cm->modname==='assign'){
        if($DB->count_records('assign_submission',['assignment'=>$cm->instance])){throw new RuntimeException('Submission leaked');}
        if($DB->count_records('assign_grades',['assignment'=>$cm->instance])){throw new RuntimeException('Grade leaked');}
    }
    if($cm->modname==='lti'){
        $lti=$DB->get_record('lti',['id'=>$cm->instance],'*',MUST_EXIST);
        if(!str_starts_with($lti->toolurl,'https://python-lab.example.invalid/')||!empty($lti->resourcekey)||!empty($lti->password)){throw new RuntimeException('Local LTI configuration leaked');}
    }
}
$guided=str_ends_with($key,'guided');
foreach(['lessonmark'=>$guided?83:37,'lti'=>32,'quiz'=>23,'assign'=>8] as $module=>$count){if(($counts[$module]??0)!==$count){throw new RuntimeException('Activity count '.$module);}}
$mode=course_get_format($target)->get_format_options()['learningmode'];
if($mode!==($guided?'guided':'path')){throw new RuntimeException('Wrong restored mode');}
$enrolled=$DB->count_records_sql('SELECT COUNT(*) FROM {user_enrolments} u JOIN {enrol} e ON e.id=u.enrolid WHERE e.courseid=?',[$target->id]);
if($enrolled){throw new RuntimeException('User enrolments leaked');}
$result=['variant'=>$key,'restored_courseid'=>$target->id,'status'=>'PASS','hidden'=>true,'mode'=>$mode,'activities'=>$counts,'questions'=>$slots,'internal_links'=>$links,'users'=>0];
file_put_contents(make_temp_directory('python-dual-distribution').'/restore-'.$key.'.json',json_encode($result,JSON_PRETTY_PRINT));
echo json_encode($result).PHP_EOL;
