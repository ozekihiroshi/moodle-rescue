<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
$courses = [
 'PYAI-INTRO' => [47=>['Lesson 3.1: Tabular data, CSV, and pandas',6],49=>['Lesson 3.2: Data selection, filtering, and Boolean logic',7],50=>['Lesson 3.3: Data cleaning and audit records',7],52=>['Lesson 3.4: Grouping and summary statistics',6]],
 'PYAI-INTRO-JA' => [193=>['レッスン3.1：表形式データ・CSV・pandas',6],195=>['レッスン3.2：データの選択・抽出とブール論理',7],196=>['レッスン3.3：データのクリーニングと監査記録',7],198=>['レッスン3.4：グループ化と要約統計',6]],
];
$result=[];
foreach($courses as $shortname=>$pages){
 $course=$DB->get_record('course',['shortname'=>$shortname],'*',MUST_EXIST);
 foreach($pages as $cmid=>$expected){
  $cm=get_coursemodule_from_id('page',$cmid,$course->id,false,MUST_EXIST);
  $page=$DB->get_record('page',['id'=>$cm->instance],'*',MUST_EXIST);
  if($page->name!==$expected[0])throw new RuntimeException("$shortname CMID $cmid name");
  foreach(['PYAI-V37-TEXTBOOK-STRUCTURE','Learning outcomes','Summary'] as $token){
   $local=$shortname==='PYAI-INTRO-JA' ? ['Learning outcomes'=>'このレッスンの到達目標','Summary'=>'まとめ'][$token]??$token : $token;
   if(!str_contains($page->content,$local))throw new RuntimeException("$shortname $cmid missing $local");
  }
  if(substr_count($page->content,'<h4')!==0)throw new RuntimeException("$shortname $cmid h4");
  $groups=preg_match_all('/<h2[^>]*>3\.[1-4]\.[0-9]+ /u',$page->content);
  if($groups!==$expected[1])throw new RuntimeException("$shortname $cmid groups $groups");
  $result[]=['course'=>$shortname,'cmid'=>$cmid,'groups'=>$groups,'h3'=>substr_count($page->content,'<h3>')];
 }
 $quiznames=$shortname==='PYAI-INTRO-JA'
  ? ['理解度チェック：3.1 表形式データ・CSV・pandas','理解度チェック：3.2 データの選択・抽出とブール論理','理解度チェック：3.3 データのクリーニングと監査記録','理解度チェック：3.4 グループ化と要約統計']
  : ['Knowledge check: 3.1 Tabular data, CSV, and pandas','Knowledge check: 3.2 Data selection, filtering, and Boolean logic','Knowledge check: 3.3 Data cleaning and audit records','Knowledge check: 3.4 Grouping and summary statistics'];
 foreach($quiznames as $name){$quiz=$DB->get_record('quiz',['course'=>$course->id,'name'=>$name],'*',MUST_EXIST);if($DB->count_records('quiz_slots',['quizid'=>$quiz->id])!==10)throw new RuntimeException("$name slots");}
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
