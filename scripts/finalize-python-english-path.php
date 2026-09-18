<?php
define('CLI_SCRIPT',true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot.'/course/lib.php';
if($CFG->wwwroot!=='http://localhost:8083'){throw new RuntimeException('Local only');}
$course=$DB->get_record('course',['shortname'=>'PYAI-INTRO-EN-DUAL-PATH'],'*',MUST_EXIST);
\core\session\manager::set_user(get_admin());
$mi=get_fast_modinfo($course);$cms=$mi->get_cms();$mi->sort_cm_array($cms);
$newpages=[];$choice=null;$options=[];
foreach($cms as $cm){
    if($cm->modname==='lessonmark'){
        $old=(int)str_replace('python-en-path-page-','',$cm->idnumber);$newpages[$old]=$cm->id;
        if(str_starts_with($cm->name,'Chapter 3 midterm:')){$choice=$cm;}
        if(preg_match('/^3\.5[ABC]/',$cm->name)){$options[]=$cm;}
    }
}
if(count($newpages)!==37||!$choice||count($options)!==3){throw new RuntimeException('Incomplete course structure');}
$manifest=json_decode(file_get_contents('/tmp/python-english-path/source-manifest.json'),true);$map=$manifest['map'];
$rewrite=function($text)use($map,$newpages,$course){
    $text=preg_replace_callback('~(/mod/[a-z]+/(?:view|launch)\.php\?id=)(\d+)~',fn($m)=>$m[1].($map[(int)$m[2]]??$m[2]),$text);
    $text=preg_replace_callback('~/mod/page/view\.php\?id=(\d+)~',fn($m)=>isset($newpages[(int)$m[1]])?'/mod/lessonmark/view.php?id='.$newpages[(int)$m[1]]:$m[0],$text);
    return preg_replace('~(/course/view\.php\?id=)10(?!\d)~','${1}'.$course->id,$text);
};
foreach($cms as $cm){
    if(!$cm->visible){continue;}
    if($cm->modname==='lessonmark'){
        $text=$rewrite($DB->get_field('lessonmark','markdownsource',['id'=>$cm->instance],MUST_EXIST));
        $text=preg_replace('/\n\n## Shared completion confirmation\n.*\z/s','',$text);
        if($cm->id===$choice->id){
            $text.="\n\n## Shared completion confirmation\n\nChoose one project below. Complete both programs, run their checks and submit the two files to that project's assignment. Then return here and mark this page as done. The other two projects are optional; their unfinished status does not block your progress. This manual mark records your confirmation, not automatic grading.\n\n";
            foreach($options as $option){$text.='['.$option->name.']('.$option->url->out(false).")\n\n";}
        }else if(in_array($cm->id,array_map(fn($c)=>$c->id,$options))){
            $text.="\n\n## Shared completion confirmation\n\nAfter checking and submitting both programs, [return to the common completion page](".$choice->url->out(false)."). Mark it as done. The other two options are additional practice.\n";
        }
        $DB->set_field('lessonmark','markdownsource',$text,['id'=>$cm->instance]);
    }else if(in_array($cm->modname,['assign','lti','quiz'])){
        $text=$DB->get_field($cm->modname,'intro',['id'=>$cm->instance]);
        $DB->set_field($cm->modname,'intro',$rewrite($text),['id'=>$cm->instance]);
    }else if($cm->modname==='forum'){$DB->set_field('forum','name','Announcements',['id'=>$cm->instance]);}
    $section=$mi->get_section_info($cm->sectionnum);
    if(preg_match('/^3\.5[ABC]/',$section->name??'')){
        $DB->update_record('course_modules',(object)['id'=>$cm->id,'completion'=>0,'completionview'=>0,'completiongradeitemnumber'=>null,'completionpassgrade'=>0]);
        if($cm->modname==='assign'){$DB->set_field('assign','completionsubmit',0,['id'=>$cm->instance]);}
    }
}
foreach($DB->get_records('course_sections',['course'=>$course->id]) as $section){
    $fields=['summary'=>$rewrite($section->summary)];
    if(str_starts_with($section->name??'','2.4 ')){$fields['name']='2.4 Practical project: CSV library management';}
    if(!$section->component && $section->section<=6){$fields['visible']=1;}
    course_update_section($course,$section,$fields);
}
$DB->update_record('course_modules',(object)['id'=>$choice->id,'completion'=>1,'completionview'=>0]);
update_course((object)['id'=>$course->id,'visible'=>1,'lang'=>'en','learningmode'=>'path',
    'summary'=>'<p>A self-paced Markdown edition of the canonical English Python course. Read, predict, run, compare with the model answer, retry the knowledge check, and complete each chapter project. Chapter 3 requires one of three projects. Python Lab is the workspace; submit the specified files to this course.</p>',
    'summaryformat'=>FORMAT_HTML]);
rebuild_course_cache($course->id,true);
echo json_encode(['courseid'=>$course->id,'lessonmark_pages'=>count($newpages),'choice'=>$choice->id,'options'=>array_map(fn($c)=>$c->id,$options)]).PHP_EOL;
