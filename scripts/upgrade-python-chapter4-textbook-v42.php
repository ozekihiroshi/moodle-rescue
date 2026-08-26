<?php
define('CLI_SCRIPT',true);require '/var/www/html/config.php';
$sn=getenv('PYTHON_COURSE_SHORTNAME')?:'PYAI-INTRO';$course=$DB->get_record('course',['shortname'=>$sn],'*',MUST_EXIST);$ja=$sn==='PYAI-INTRO-JA';
$root='/workspace/sample-content/introduction-to-python/chapter4-pages-v41/';
$items=$ja?[
'レッスン4.1：レコードと関数からオブジェクトへ'=>'ja/41.html',
'レッスン4.2：状態・メソッド・正しいオブジェクト'=>'ja/42.html',
'レッスン4.3：複数オブジェクト・合成・責任分担'=>'ja/43.html',
'レッスン4.4：オブジェクトの保存とテスト'=>'ja/44.html',
'4.5 課題仕様と完成条件'=>'ja/45.html'
]:[
'Lesson 4.1: From records and functions to objects'=>'en/41.html',
'Lesson 4.2: State, methods, and valid objects'=>'en/42.html',
'Lesson 4.3: Collections, composition, and responsibility'=>'en/43.html',
'Lesson 4.4: Persistence and testing class-based programs'=>'en/44.html',
'4.5 Project brief and completion contract'=>'en/45.html'
];
foreach($items as $name=>$relative){$path=$root.$relative;if(!is_readable($path))throw new RuntimeException($path);$p=$DB->get_record('page',['course'=>$course->id,'name'=>$name],'*',MUST_EXIST);$p->content=file_get_contents($path);$p->contentformat=FORMAT_HTML;$p->timemodified=time();$DB->update_record('page',$p);}
rebuild_course_cache($course->id,true);echo json_encode(['status'=>'ok','shortname'=>$sn,'pages'=>count($items),'marker'=>'PYAI-V42-CHAPTER4-COMPLETE'],JSON_UNESCAPED_UNICODE).PHP_EOL;
