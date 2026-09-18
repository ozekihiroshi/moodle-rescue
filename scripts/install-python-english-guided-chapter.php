<?php
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/assign/locallib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$chapter = (int)$argv[1];
if ($chapter < 0 || $chapter > 6) { throw new RuntimeException('Invalid chapter'); }
$root = '/tmp/english-guided';
$mapping = json_decode(file_get_contents($CFG->dataroot . '/temp/python-english-guided-full/mapping.json'), true, 512, JSON_THROW_ON_ERROR);
$course = $DB->get_record('course', ['id'=>$mapping['courseid'],'shortname'=>'PYAI-INTRO-EN-DUAL-GUIDED'], '*', MUST_EXIST);
$source = get_course($mapping['sourcecourse']); $map = $mapping['map'];
$units = json_decode(file_get_contents($root . '/units.json'), true, 512, JSON_THROW_ON_ERROR);
\core\session\manager::set_user(get_admin());
function english_guided_add($course, $section, $key, $name, $module, $fields) {
    global $DB;
    $idnumber = 'python-guided-' . $key;
    $existing = $DB->get_record('course_modules', ['course'=>$course->id,'idnumber'=>$idnumber]);
    if ($existing) { return $existing->id; }
    $result = add_moduleinfo((object)array_merge([
        'module'=>$DB->get_field('modules','id',['name'=>$module],MUST_EXIST),
        'modulename'=>$module,'section'=>$section,'name'=>$name,'intro'=>'','introformat'=>FORMAT_HTML,
        'visible'=>1,'visibleoncoursepage'=>1,'completion'=>COMPLETION_TRACKING_MANUAL,
        'groupmode'=>0,'groupingid'=>0,'cmidnumber'=>$idnumber,
    ], $fields), $course);
    return $result->coursemodule;
}
$forum = english_guided_add($course,$chapter,'help-'.$chapter,'Chapter '.$chapter.': Questions and help','forum',[
    'type'=>'general','intro'=>'<p>Include the unit number, code you tried, actual output and expected output. In class, you may show your screen to your teacher. Read replies in the same discussion. Do not post passwords or personal information.</p>',
    'completion'=>COMPLETION_TRACKING_NONE,'forcesubscribe'=>0,'trackingtype'=>1,'assessed'=>0,
    'scale'=>0,'maxbytes'=>0,'maxattachments'=>0,
]);
$helpurl = (new moodle_url('/mod/forum/view.php',['id'=>$forum]))->out(false);
$summary = '<h3>Before class → In class → After class</h3><p>Open the preparation page for the unit your teacher assigns. Make a prediction, investigate it using the lesson and Python Lab in class, then review independently and take the knowledge check. Follow your teacher for the schedule; check deadlines on assignment pages.</p>'
    . '<p><a href="'.$helpurl.'">Questions and help for this chapter</a>. If you get stuck, keep the output and show your teacher. Lab files are shared with the self-paced edition, but submissions and grades belong to this course.</p>';
if ($chapter === 0) { $summary = '<h3>Using the teacher-guided edition</h3><p>Start with the learning map and Python Lab orientation. Your teacher will indicate where to begin. Predictions need not be correct: compare them with results together, then reproduce the work independently after class.</p>'.$summary; }
if ($chapter === 3) { $summary .= '<p>Choose one of projects A/B/C. Submit that project, then complete the shared confirmation. The other two projects are optional practice.</p>'; }
$section = $DB->get_record('course_sections',['course'=>$course->id,'section'=>$chapter],'*',MUST_EXIST);
course_update_section($course,$section,['summary'=>$summary,'summaryformat'=>FORMAT_HTML,'visible'=>1]);
$mi = get_fast_modinfo($source); $cms=$mi->get_cms(); $mi->sort_cm_array($cms); $report=[];
foreach ($cms as $old) {
    if (!$old->visible || $old->modname !== 'lessonmark') { continue; }
    $oldsection=$mi->get_section_info($old->sectionnum);
    if ($old->sectionnum != $chapter && !str_starts_with($oldsection->name??'', $chapter.'.')) { continue; }
    $cm=get_coursemodule_from_id('lessonmark',$map[$old->id],$course->id,false,MUST_EXIST);
    $section=$DB->get_record('course_sections',['id'=>$cm->section],'*',MUST_EXIST);
    $text=$DB->get_field('lessonmark','markdownsource',['id'=>$old->instance],MUST_EXIST);
    $text=preg_replace_callback('~(/mod/[a-z]+/(?:view|launch)\.php\?id=)(\d+)~',fn($m)=>$m[1].($map[(int)$m[2]]??$m[2]),$text);
    if (preg_match('/^Lesson (\d+\.\d+)/',$old->name,$match)) {
        $unit=$match[1]; $spec=$units[$unit];
        $peers=array_filter($cms,fn($p)=>$p->sectionnum==$old->sectionnum && $p->visible);
        $labs=array_values(array_filter($peers,fn($p)=>$p->modname==='lti'));
        $quizzes=array_values(array_filter($peers,fn($p)=>$p->modname==='quiz'));
        if (count($labs)!==1 || count($quizzes)!==1) { throw new RuntimeException('Expected Lab and quiz: '.$unit); }
        $lab=$map[$labs[0]->id]; $quiz=$map[$quizzes[0]->id];
        $links="\n\n[Lesson](/mod/lessonmark/view.php?id={$cm->id}) · [Python Lab](/mod/lti/view.php?id=$lab) · [Knowledge check](/mod/quiz/view.php?id=$quiz) · [Questions and help]($helpurl)\n";
        $prepare=english_guided_add($course,$section->section,$unit.'-prepare',$unit.' Before class: Prepare and predict','lessonmark',['markdownsource'=>file_get_contents($root.'/'.$unit.'-prepare.md').$links]);
        $review=english_guided_add($course,$section->section,$unit.'-review',$unit.' After class: Review and check','lessonmark',['markdownsource'=>file_get_contents($root.'/'.$unit.'-review.md').$links]);
        foreach ([$prepare=>'prepare',$review=>'review'] as $id=>$kind) {
            $DB->set_field('lessonmark','markdownsource',file_get_contents($root.'/'.$unit.'-'.$kind.'.md').$links,['id'=>$DB->get_field('course_modules','instance',['id'=>$id],MUST_EXIST)]);
        }
        $text=preg_replace('/\n## Finish this unit\n.*\z/s','',$text);
        $intro="\n\n## Investigate in class\n\n".$spec['classroom']." Compare your prediction with the output rather than only watching the demonstration.\n\n[Before class](/mod/lessonmark/view.php?id=$prepare) · [Python Lab](/mod/lti/view.php?id=$lab)\n";
        $pos=strpos($text,"\n"); $text=substr($text,0,$pos).$intro.substr($text,$pos);
        $text.="\n\n## After class\n\nSave in Lab, then open [this unit's review](/mod/lessonmark/view.php?id=$review) before the knowledge check. Bring unresolved questions to [Questions and help]($helpurl).\n";
        \core_courseformat\formatactions::cm($course)->move_before($prepare,$cm->id);
        \core_courseformat\formatactions::cm($course)->move_before($review,$quiz);
        $report[]=['unit'=>$unit,'prepare'=>$prepare,'lesson'=>$cm->id,'lab'=>$lab,'review'=>$review,'quiz'=>$quiz];
    } elseif (str_contains($old->name,'Project brief')) {
        $intro="\n\n## Working with your teacher\n\nFirst identify the input, deliverables and completion criteria, and explain your plan to your teacher. Implement your own solution, showing intermediate results when you need help. Run the checker and save before submitting the specified files to this course. Read your teacher's grade and comments on the assignment page.\n\n[Questions and help]($helpurl)\n";
        $pos=strpos($text,"\n"); $text=substr($text,0,$pos).$intro.substr($text,$pos);
    }
    $DB->update_record('lessonmark',(object)['id'=>$cm->instance,'markdownsource'=>$text,'timemodified'=>time()]);
}
foreach (get_fast_modinfo($course)->get_instances_of('assign') as $cm) {
    if (!$cm->visible) { continue; }
    (new assign($cm->context,$cm,$course))->get_feedback_plugin_by_type('comments')->set_config('enabled',1);
}
rebuild_course_cache($course->id,true);
echo json_encode(['courseid'=>$course->id,'chapter'=>$chapter,'forum'=>$forum,'units'=>$report],JSON_PRETTY_PRINT).PHP_EOL;
