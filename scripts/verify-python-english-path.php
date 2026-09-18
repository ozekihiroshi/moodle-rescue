<?php
define('CLI_SCRIPT',true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot.'/course/lib.php';
require_once $CFG->libdir.'/gradelib.php';
if($CFG->wwwroot!=='http://localhost:8083'){throw new RuntimeException('Local only');}
$guided = ($argv[1] ?? '') === '--guided';
$course=$DB->get_record('course',['shortname'=>$guided ? 'PYAI-INTRO-EN-DUAL-GUIDED' : 'PYAI-INTRO-EN-DUAL-PATH'],'*',MUST_EXIST);
$student=$DB->get_record('user',['username'=>'dual_3a_guided'],'*',MUST_EXIST);
\core\session\manager::set_user($student);
$mi=get_fast_modinfo($course,$student->id);$cms=$mi->get_cms();$mi->sort_cm_array($cms);
$counts=[];$units=[];$links=0;$questions=0;
foreach($cms as $cm){
    if(!$cm->uservisible){continue;}$counts[$cm->modname]=($counts[$cm->modname]??0)+1;
    $section=$mi->get_section_info($cm->sectionnum);
    if($cm->modname==='lessonmark'){
        if(preg_match('/^Lesson\s+(\d+\.\d+)/',$cm->name,$match)){$units[]=$match[1];}
        $text=$DB->get_field('lessonmark','markdownsource',['id'=>$cm->instance],MUST_EXIST);
        if(preg_match('/[\x{3040}-\x{30ff}\x{4e00}-\x{9fff}]/u',$text)){throw new RuntimeException('Non-English lesson: '.$cm->id);}
    }else if(in_array($cm->modname,['assign','lti','quiz'])){$text=$DB->get_field($cm->modname,'intro',['id'=>$cm->instance]);}
    else{$text='';}
    preg_match_all('~/mod/[a-z]+/(?:view|launch)\.php\?id=(\d+)~',$text,$matches);
    foreach($matches[1] as $id){if(!isset($cms[$id])||!$cms[$id]->uservisible){throw new RuntimeException('Wrong or hidden link: '.$cm->id.' -> '.$id);} $links++;}
    if($cm->modname==='quiz'){
        $quiz=$DB->get_record('quiz',['id'=>$cm->instance],'*',MUST_EXIST);
        $item=grade_item::fetch(['courseid'=>$course->id,'itemmodule'=>'quiz','iteminstance'=>$cm->instance,'itemnumber'=>0]);
        if($quiz->attempts!=0||$quiz->grademethod!=1||(float)$item->gradepass!==90.0){throw new RuntimeException('Quiz learning policy');}
        $slots=$DB->get_records('quiz_slots',['quizid'=>$cm->instance]);
        if(count($slots)!==10){throw new RuntimeException('Expected ten questions');}
        foreach($slots as $slot){
            $ref=$DB->get_record('question_references',['component'=>'mod_quiz','questionarea'=>'slot','itemid'=>$slot->id],'*',MUST_EXIST);
            $versions=$DB->get_records('question_versions',['questionbankentryid'=>$ref->questionbankentryid],'version DESC');
            $v=$ref->version?array_values(array_filter($versions,fn($v)=>$v->version==$ref->version))[0]:reset($versions);
            $q=$DB->get_record('question',['id'=>$v->questionid],'*',MUST_EXIST);
            if(preg_match('/[\x{3040}-\x{30ff}\x{4e00}-\x{9fff}]/u',$q->questiontext.$q->generalfeedback)){throw new RuntimeException('Non-English question: '.$q->id);}
            $questions++;
        }
    }
}
foreach(['lessonmark'=>$guided ? 83 : 37,'quiz'=>23,'lti'=>32,'assign'=>8] as $module=>$count){if(($counts[$module]??0)!==$count){throw new RuntimeException('Unexpected count: '.$module.' '.($counts[$module]??0));}}
$expected=[];
foreach([1=>6,2=>3,3=>4,4=>4,5=>3,6=>3] as $chapter=>$lessons){for($n=1;$n<=$lessons;$n++){$expected[]=$chapter.'.'.$n;}}
if($units!==$expected){throw new RuntimeException('Lesson sequence');}
$presentation=\mod_lessonmark\local\course_presentation::modules($course);
$ids=array_values(array_map(fn($c)=>(int)$c->id,array_filter($cms,fn($c)=>$c->uservisible&&$c->modname==='lessonmark')));
if(array_map(fn($c)=>(int)$c->id,$presentation)!==$ids){throw new RuntimeException('Presentation order');}
$overview=\format_duallearning\local\overview::build($course,$student->id);
echo json_encode(['status'=>'PASS','courseid'=>$course->id,'counts'=>$counts,'english_questions'=>$questions,'internal_links'=>$links,'unit_order'=>$units,'next'=>$overview['next']],JSON_PRETTY_PRINT).PHP_EOL;
