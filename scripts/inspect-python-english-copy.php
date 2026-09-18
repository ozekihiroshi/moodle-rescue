<?php
define('CLI_SCRIPT',true);
require '/var/www/html/public/config.php';
if ($CFG->wwwroot!=='http://localhost:8083') { throw new RuntimeException('Local only'); }
foreach (['source'=>$DB->get_record('course',['id'=>10],'*',MUST_EXIST),
    'copy'=>$DB->get_record('course',['shortname'=>'PYAI-INTRO-EN-DUAL-PATH'],'*',MUST_EXIST)] as $label=>$course) {
    $mi=get_fast_modinfo($course);$cms=$mi->get_cms();$mi->sort_cm_array($cms);$rows=[];
    foreach($cms as $cm){$rows[]=['id'=>$cm->id,'section'=>$cm->sectionnum,'sectionname'=>$mi->get_section_info($cm->sectionnum)->name,'module'=>$cm->modname,'name'=>$cm->name,'visible'=>$cm->visible];}
    $result[$label]=['courseid'=>$course->id,'shortname'=>$course->shortname,'rows'=>$rows];
}
echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
