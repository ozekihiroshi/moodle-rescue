<?php
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/gradelib.php';
require_once $CFG->dirroot . '/mod/assign/locallib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$chapter = (int)$argv[1]; $root = '/tmp/python-english-path';
if ($chapter < 0 || $chapter > 6) { throw new RuntimeException('Invalid chapter'); }
$manifest = json_decode(file_get_contents($root . '/source-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$pages = json_decode(file_get_contents($root . '/chapter-' . $chapter . '.json'), true, 512, JSON_THROW_ON_ERROR)['pages'];
$course = $DB->get_record('course', ['id'=>$manifest['courseid'],'shortname'=>'PYAI-INTRO-EN-DUAL-PATH'], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());
$map = $manifest['map'];
if ($chapter === 6) {
    foreach (get_fast_modinfo($course)->get_instances_of('lti') as $legacy) {
        if ($legacy->name === 'Python Lab 12: Scaling, chunks, and validation') {
            set_coursemodule_visible($legacy->id, 0);
        }
    }
    rebuild_course_cache($course->id, true);
}
$specfiles = [
    '1.7'=>['weekly_support.py'], '2.4'=>['library_manager.py'],
    '3.5A'=>['inspect_school_meals.py','meal_delivery_review.py'],
    '3.5B'=>['inspect_bus_service.py','bus_service_review.py'],
    '3.5C'=>['inspect_water_points.py','water_point_review.py'],
    '4.5'=>['equipment_lending.py'], '5.4'=>['clinic_wait_evidence.py','clinic_wait_evidence.png'],
    '6.4'=>['clinic_stock_scaleup.py','clinic_stock_summary.csv','clinic_stock_evidence.png'],
];
$report = [];
foreach ($pages as $page) {
    if (!$page['visible']) { continue; }
    $old = $DB->get_record('course_modules', ['id'=>$page['pagecmid'],'course'=>$course->id], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['id'=>$old->section], '*', MUST_EXIST);
    preg_match('/^(\d+\.\d+[ABC]?)/', $section->name ?? '', $unitmatch);
    $unit = $unitmatch[1] ?? '';
    $markdown = file_get_contents($root . '/' . $page['file']);
    $markdown = preg_replace_callback('~(/mod/[a-z]+/(?:view|launch)\.php\?id=)(\d+)~',
        fn($m)=>$m[1] . ($map[(int)$m[2]] ?? $m[2]), $markdown);
    $mi = get_fast_modinfo($course); $peers = array_filter($mi->get_cms(), fn($cm)=>$cm->section == $old->section && $cm->visible);
    $labs = array_values(array_filter($peers, fn($cm)=>$cm->modname==='lti'));
    $quizzes = array_values(array_filter($peers, fn($cm)=>$cm->modname==='quiz'));
    $assignments = array_values(array_filter($peers, fn($cm)=>$cm->modname==='assign'));
    $lesson = preg_match('/^Lesson\s+\d+\.\d+/i', $page['name']) === 1;
    $supplement = !$lesson && count($quizzes)>0;
    if ($lesson) {
        if (count($labs)!==1 || count($quizzes)!==1) { throw new RuntimeException('Ambiguous lesson links'); }
        if (is_file($root . '/review-' . $unit . '.md')) { $markdown .= "\n" . file_get_contents($root . '/review-' . $unit . '.md'); }
        $markdown .= "\n\n## Finish this unit\n\nRun the examples and integrated practice, compare your result with the model answer, and save in Python Lab. Mark this lesson as done and try the knowledge check. You may retry; use each explanation to revisit the relevant section.\n\n"
            . '[Run and save in Python Lab](' . $labs[0]->url->out(false) . ') → [Try the knowledge check](' . $quizzes[0]->url->out(false) . ")\n";
    }
    if ($supplement) { $markdown = "Optional reference: use this when helpful. It is not a required completion item.\n\n" . $markdown; }
    if (str_contains($markdown,'@@PLUGINFILE@@') || str_contains($markdown,'<details')) { throw new RuntimeException('Review source assets/disclosures'); }
    $idnumber = 'python-en-path-page-' . $old->id;
    $existing = $DB->get_record('course_modules', ['course'=>$course->id,'idnumber'=>$idnumber]);
    if (!$existing) {
        $info = add_moduleinfo((object)[
            'module'=>$DB->get_field('modules','id',['name'=>'lessonmark'],MUST_EXIST),'modulename'=>'lessonmark',
            'section'=>$section->section,'name'=>$page['name'],'intro'=>'','introformat'=>FORMAT_HTML,
            'markdownsource'=>$markdown,'visible'=>1,'visibleoncoursepage'=>1,'completion'=>$supplement?0:1,
            'groupmode'=>0,'groupingid'=>0,'cmidnumber'=>$idnumber,
        ], $course);
        $existing = $DB->get_record('course_modules',['id'=>$info->coursemodule],'*',MUST_EXIST);
    } else { $DB->set_field('lessonmark','markdownsource',$markdown,['id'=>$existing->instance]); }
    \core_courseformat\formatactions::cm($course)->move_before($existing->id, $old->id);
    set_coursemodule_visible($old->id, 0);
    foreach ($quizzes as $cm) {
        $after = \mod_quiz\question\display_options::IMMEDIATELY_AFTER | \mod_quiz\question\display_options::LATER_WHILE_OPEN | \mod_quiz\question\display_options::AFTER_CLOSE;
        $record = (object)['id'=>$cm->instance,'attempts'=>0,'grademethod'=>1];
        foreach (['reviewattempt','reviewcorrectness','reviewmaxmarks','reviewmarks','reviewspecificfeedback','reviewgeneralfeedback','reviewrightanswer','reviewoverallfeedback'] as $field) { $record->$field=$after; }
        $record->reviewattempt |= \mod_quiz\question\display_options::DURING;
        $DB->update_record('quiz',$record);
        $grade = grade_item::fetch(['courseid'=>$course->id,'itemmodule'=>'quiz','iteminstance'=>$cm->instance,'itemnumber'=>0]);
        if (!$grade || (float)$grade->grademax!==100.0) { throw new RuntimeException('Unexpected quiz scale'); }
        $grade->gradepass=90; $grade->update();
        $DB->update_record('course_modules',(object)['id'=>$cm->id,'completion'=>2,'completiongradeitemnumber'=>0,'completionpassgrade'=>1]);
    }
    if (isset($specfiles[$unit]) && count($assignments)===1) {
        $cm=$assignments[0]; $assignment=new assign($cm->context,$cm,$course);
        $assignment->get_submission_plugin_by_type('onlinetext')->set_config('enabled',0);
        $files=$assignment->get_submission_plugin_by_type('file'); $files->set_config('enabled',1);
        $files->set_config('maxfilesubmissions',count($specfiles[$unit]));
        $files->set_config('filetypeslist',implode(',',array_unique(array_map(fn($f)=>'.'.pathinfo($f,PATHINFO_EXTENSION),$specfiles[$unit]))));
        $intro='<p>Save your work, inspect the result and run the checker. <a href="'
            . (new moodle_url('/mod/lessonmark/view.php',['id'=>$existing->id]))->out(false) . '">Read the project specification</a>.</p><p>Submit these files:</p><ul>';
        foreach ($specfiles[$unit] as $f) { $intro.='<li><code>'.s($f).'</code></li>'; }
        $intro.='</ul><p>Download the saved files from Python Lab and upload them here. Save and check the submission status and filenames. Do not submit the Notebook or checker. Use this edition, not the original course.</p>';
        $optional=str_starts_with($unit,'3.5');
        $DB->update_record('assign',(object)['id'=>$cm->instance,'intro'=>$intro,'introformat'=>FORMAT_HTML,'completionsubmit'=>$optional?0:1]);
        $DB->update_record('course_modules',(object)['id'=>$cm->id,'completion'=>$optional?0:2,'completiongradeitemnumber'=>null,'completionpassgrade'=>0]);
        $markdown .= "\n\n## Submit and check\n\nDownload the specified files from Python Lab and [submit to this course](" . $cm->url->out(false) . "). Check the saved filenames and submission status.\n";
        $DB->set_field('lessonmark','markdownsource',$markdown,['id'=>$existing->instance]);
    }
    if ($unit==='1.7' && count($labs)===1) {
        $DB->set_field('lti','toolurl','http://localhost:8086/hub/user-redirect/lab/tree/P1_weekly_support_report_dual_path.ipynb',['id'=>$labs[0]->instance]);
        $DB->set_field('lti','intro','<p>Complete and save weekly_support.py, check it, then upload the saved file to this edition’s 1.7 assignment.</p>',['id'=>$labs[0]->instance]);
    }
    $report[]=['unit'=>$unit,'name'=>$page['name'],'page'=>$old->id,'lessonmark'=>$existing->id,'lesson'=>(bool)$lesson,'supplement'=>$supplement];
}
$parent=$DB->get_record('course_sections',['course'=>$course->id,'section'=>$chapter],'*',MUST_EXIST);
course_update_section($course,$parent,['visible'=>1]);
rebuild_course_cache($course->id,true);
echo json_encode(['chapter'=>$chapter,'pages'=>$report],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
