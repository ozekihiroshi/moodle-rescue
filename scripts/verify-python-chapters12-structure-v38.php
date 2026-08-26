<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
$expected = json_decode(<<<'JSON'
{"PYAI-INTRO":{"35":{"number":"1.1","groups":4,"pre":4},"37":{"number":"1.2","groups":4,"pre":5},"267":{"number":"1.3","groups":4,"pre":4},"275":{"number":"1.4","groups":6,"pre":7},"39":{"number":"1.5","groups":7,"pre":9},"41":{"number":"1.6","groups":7,"pre":9},"43":{"number":"2.1","groups":7,"pre":10},"45":{"number":"2.2","groups":7,"pre":8},"285":{"number":"2.3","groups":6,"pre":4}},"PYAI-INTRO-JA":{"181":{"number":"1.1","groups":4,"pre":4},"183":{"number":"1.2","groups":4,"pre":5},"271":{"number":"1.3","groups":4,"pre":4},"279":{"number":"1.4","groups":6,"pre":7},"185":{"number":"1.5","groups":7,"pre":9},"187":{"number":"1.6","groups":7,"pre":9},"189":{"number":"2.1","groups":7,"pre":10},"191":{"number":"2.2","groups":7,"pre":8},"289":{"number":"2.3","groups":6,"pre":4}}}
JSON, true, 512, JSON_THROW_ON_ERROR);
$result=[];
foreach($expected as $shortname=>$pages) {
  $course=$DB->get_record('course',['shortname'=>$shortname],'*',MUST_EXIST);
  foreach($pages as $cmid=>$spec) {
    $cm=get_coursemodule_from_id('page',(int)$cmid,$course->id,false,MUST_EXIST);
    $page=$DB->get_record('page',['id'=>$cm->instance],'*',MUST_EXIST);
    if(!str_contains($page->content,'PYAI-V38-TEXTBOOK-STRUCTURE'))throw new RuntimeException("$shortname $cmid marker");
    if(substr_count($page->content,'<h4')!==0)throw new RuntimeException("$shortname $cmid h4");
    $groups=preg_match_all('/<h2[^>]*>'.preg_quote($spec['number'], '/').'\.[0-9]+ /u',$page->content);
    if($groups!==(int)$spec['groups'])throw new RuntimeException("$shortname $cmid groups $groups");
    if(substr_count($page->content,'<pre')!==(int)$spec['pre'])throw new RuntimeException("$shortname $cmid code blocks");
    $result[]=['course'=>$shortname,'cmid'=>(int)$cmid,'lesson'=>$spec['number'],'groups'=>$groups,'code_blocks'=>(int)$spec['pre']];
  }
  $prefix=$shortname==='PYAI-INTRO-JA'?'理解度チェック：':'Knowledge check:';
  foreach($DB->get_records_select('quiz','course = ? AND name LIKE ?',[$course->id,$prefix.'%']) as $quiz) {
    if(preg_match('/(?:1\.[1-6]|2\.[1-3])/', $quiz->name) && $DB->count_records('quiz_slots',['quizid'=>$quiz->id])!==10)throw new RuntimeException($quiz->name.' slots');
  }
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
