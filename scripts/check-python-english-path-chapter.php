<?php
define('CLI_SCRIPT',true);
require '/var/www/html/public/config.php';
require_once $CFG->libdir . '/filelib.php';
if ($CFG->wwwroot!=='http://localhost:8083') { throw new RuntimeException('Local only'); }
$chapter=(int)$argv[1];
$course=$DB->get_record('course',['shortname'=>'PYAI-INTRO-EN-DUAL-PATH'],'*',MUST_EXIST);
\core\session\manager::set_user(get_admin());
$manifest=json_decode(file_get_contents('/tmp/python-english-path/chapter-'.$chapter.'.json'),true);
$renderer=new \mod_lessonmark\local\moodle_markdown_renderer();
$blocks=function($html){$doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);$result=[];
    foreach($doc->getElementsByTagName('pre') as $pre){$result[]=trim(str_replace("\r\n","\n",$pre->textContent));}return $result;};
$counts=['pages'=>0,'code_blocks'=>0,'lessons'=>0];
foreach($manifest['pages'] as $page){
    if(!$page['visible']){continue;}
    $old=$DB->get_record('course_modules',['id'=>$page['pagecmid'],'course'=>$course->id],'*',MUST_EXIST);
    $cm=$DB->get_record('course_modules',['course'=>$course->id,'idnumber'=>'python-en-path-page-'.$old->id],'*',MUST_EXIST);
    $original=$DB->get_record('page',['id'=>$old->instance],'*',MUST_EXIST);
    $text=$DB->get_field('lessonmark','markdownsource',['id'=>$cm->instance],MUST_EXIST);
    if(preg_match('/[\x{3040}-\x{30ff}\x{4e00}-\x{9fff}]/u',$text)){throw new RuntimeException('Unexpected Japanese text: '.$cm->id);}
    if(str_contains($text,'<details')||str_contains($text,'PLACEHOLDER')){throw new RuntimeException('Unconverted syntax');}
    $html=$renderer->render($text,context_module::instance($cm->id))->get_content_html();
    $source=$original->contentformat==FORMAT_HTML?$original->content:$renderer->render($original->content,context_module::instance($old->id))->get_content_html();
    $before=array_filter($blocks($source),fn($b)=>!str_contains($b,'/submit_weekly_support.py'));
    $after=$blocks($html);
    foreach($before as $block){if(!in_array($block,$after,true)){throw new RuntimeException('Code changed: '.$cm->id."\n".$block);}}
    if(str_contains($text,'[!ANSWER]')&&!str_contains($html,'<details')){throw new RuntimeException('Disclosure missing');}
    $counts['pages']++;$counts['code_blocks']+=count($before);
    if(preg_match('/^Lesson\s+\d+\.\d+/i',$page['name'])){
        $counts['lessons']++;
        if(!str_contains($text,'## Finish this unit')){throw new RuntimeException('Missing self-paced handoff');}
    }
}
echo json_encode(['chapter'=>$chapter,'status'=>'PASS','counts'=>$counts]).PHP_EOL;
