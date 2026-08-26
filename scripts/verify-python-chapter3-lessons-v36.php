<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
$results = [];
$names = [
 'PYAI-INTRO' => [
  'Lesson 3.1: Tabular data, CSV, and pandas' => ['3.1.1','3.1.2','3.1.3','3.1.4','3.1.5','3.1.6','value_counts(dropna=False, sort=False)','to_string(index=False'],
  'Lesson 3.2: Data selection, filtering, and Boolean logic' => ['3.2.1','3.2.2','3.2.3','3.2.4','3.2.5','3.2.6','3.2.7'],
  'Lesson 3.3: Data cleaning and audit records' => ['3.3.1','3.3.2','3.3.3','3.3.4','3.3.5','3.3.6','3.3.7','3.3.8','records_to_verify'],
  'Lesson 3.4: Grouping and summary statistics' => ['3.4.1','3.4.2','3.4.3','3.4.4','3.4.5','3.4.6','3.4.7','ascending=[False,False,True]'],
 ],
 'PYAI-INTRO-JA' => [
  'レッスン3.1：表形式データ・CSV・pandas' => ['3.1.1','3.1.2','3.1.3','3.1.4','3.1.5','3.1.6','value_counts(dropna=False, sort=False)','to_string(index=False'],
  'レッスン3.2：データの選択・抽出とブール論理' => ['3.2.1','3.2.2','3.2.3','3.2.4','3.2.5','3.2.6','3.2.7'],
  'レッスン3.3：データのクリーニングと監査記録' => ['3.3.1','3.3.2','3.3.3','3.3.4','3.3.5','3.3.6','3.3.7','3.3.8','records_to_verify'],
  'レッスン3.4：グループ化と要約統計' => ['3.4.1','3.4.2','3.4.3','3.4.4','3.4.5','3.4.6','3.4.7','ascending=[False,False,True]'],
 ],
];
foreach ($names as $shortname => $pages) {
 $course=$DB->get_record('course',['shortname'=>$shortname],'*',MUST_EXIST);
 foreach ($pages as $pagename => $tokens) {
  $page=$DB->get_record('page',['course'=>$course->id,'name'=>$pagename],'*',MUST_EXIST);
  if (!str_contains($page->content,'PYAI-V36-CHAPTER3-TOPICS')) throw new RuntimeException("$shortname $pagename marker");
  $last=-1;
  foreach ($tokens as $token) { $pos=strpos($page->content,$token); if ($pos===false) throw new RuntimeException("$shortname $pagename missing $token"); if (preg_match('/^3\.[1-4]\.[0-9]+$/',$token)) { if ($pos <= $last) throw new RuntimeException("$shortname $pagename order $token"); $last=$pos; } }
  $results[]=['course'=>$shortname,'page'=>$pagename,'groups'=>substr_count($page->content,'border-left:4px')];
 }
 $quiznames=$shortname==='PYAI-INTRO-JA'
  ? ['理解度チェック：3.1 表形式データ・CSV・pandas','理解度チェック：3.2 データの選択・抽出とブール論理','理解度チェック：3.3 データのクリーニングと監査記録','理解度チェック：3.4 グループ化と要約統計']
  : ['Knowledge check: 3.1 Tabular data, CSV, and pandas','Knowledge check: 3.2 Data selection, filtering, and Boolean logic','Knowledge check: 3.3 Data cleaning and audit records','Knowledge check: 3.4 Grouping and summary statistics'];
 foreach($quiznames as $name){$quiz=$DB->get_record('quiz',['course'=>$course->id,'name'=>$name],'*',MUST_EXIST);if((int)$DB->count_records('quiz_slots',['quizid'=>$quiz->id])!==10)throw new RuntimeException("$shortname $name slots");}
}
echo json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
