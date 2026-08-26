<?php
// Replace the conditions learning check with self-contained code-reading questions.
define('CLI_SCRIPT',true);
require '/var/www/html/config.php';
require_once $CFG->dirroot.'/course/lib.php';
require_once $CFG->dirroot.'/course/modlib.php';
require_once $CFG->dirroot.'/mod/quiz/locallib.php';
require_once $CFG->dirroot.'/question/editlib.php';
use core_question\local\bank\question_version_status;
\core\session\manager::set_user(get_admin());

$shortname=getenv('PYTHON_COURSE_SHORTNAME')?:'PYAI-INTRO';
$course=$DB->get_record('course',['shortname'=>$shortname],'*',MUST_EXIST);
$lang=$shortname==='PYAI-INTRO-JA'?'ja':'en';
function v10_q(string $id,string $concept,string $prompt,array $choices,int $correct,string $point):array{return compact('id','concept','prompt','choices','correct','point');}
function v10_save(int $categoryid,int $contextid,string $prefix,array $d,string $lang):stdClass{
    $q=(object)['qtype'=>'multichoice','category'=>$categoryid.','.$contextid];$answers=$feedback=$fractions=[];
    foreach($d['choices'] as $i=>[$answer,$why]){$answers[]=['text'=>$answer,'format'=>FORMAT_PLAIN];$feedback[]=['text'=>'<p>'.s($why).'</p>','format'=>FORMAT_HTML];$fractions[]=$i===$d['correct']?1.0:0.0;}
    $form=(object)['name'=>$prefix.$d['id'].' ['.$d['concept'].']','category'=>$categoryid.','.$contextid,
        'questiontext'=>['text'=>$d['prompt'],'format'=>FORMAT_HTML],
        'generalfeedback'=>['text'=>'<p><strong>'.($lang==='ja'?'学習ポイント：':'Learning point:').'</strong> '.s($d['point']).'</p>','format'=>FORMAT_HTML],
        'defaultmark'=>10,'penalty'=>0.3333333,'status'=>question_version_status::QUESTION_STATUS_READY,'idnumber'=>null,
        'single'=>1,'shuffleanswers'=>1,'answernumbering'=>'abc','showstandardinstruction'=>1,
        'correctfeedback'=>['text'=>$lang==='ja'?'<p>正解です。コードを上から追って理由も説明しましょう。</p>':'<p>Correct. Trace the code from the top and explain why.</p>','format'=>FORMAT_HTML],
        'partiallycorrectfeedback'=>['text'=>'','format'=>FORMAT_HTML],
        'incorrectfeedback'=>['text'=>$lang==='ja'?'<p>よくある誤解です。各条件を上からTrue/Falseと判定し、最初に実行されるブロックをNotebookで確認してください。</p>':'<p>This is a common misconception. Mark each condition True or False from the top, then verify the first executed block in the Notebook.</p>','format'=>FORMAT_HTML],
        'shownumcorrect'=>0,'answer'=>$answers,'fraction'=>$fractions,'feedback'=>$feedback,'hint'=>[]];
    return question_bank::get_qtype('multichoice')->save_question($q,$form);
}
$code=fn(string $s):string=>'<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'.s($s).'</code></pre>';
if($lang==='ja'){
 $quizname='理解度チェック：レッスン3 条件による判断';
 $intro='<p>コードと業務ルールを上から追うための学習確認です。各条件をTrue/Falseと予想し、最初に実行されるブロック、境界値、複合条件、検証順序を確認します。何度でも挑戦でき、90%以上で合格、100%を目指せます。</p>';
 $lessonbody=<<<'HTML'
<div class="python-sample-lesson"><h2>条件によって処理を選ぶ</h2>
<p>同じプログラムでも、値によって行う処理を変えたいことがあります。得点が基準に達していれば「合格」、達していなければ「もう一度確認」と表示する場合です。Pythonでは、この選択を<code>if</code>文で表します。</p>
<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>score = 68
if score &gt;= 50:
    result = "Pass"
else:
    result = "Not yet"
print(result)</code></pre>
<p><code>score &gt;= 50</code>は<code>True</code>か<code>False</code>になる条件式です。68では条件が真なので、直後のインデントされた処理を実行します。偽なら<code>else</code>側を実行します。インデントは見た目の飾りではなく、処理がどの分岐に属するかを示します。</p>
<p>結果が三つ以上ある場合は<code>elif</code>を加えます。</p>
<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>score = 80
if score &gt;= 90:
    result = "Excellent"
elif score &gt;= 70:
    result = "Pass"
else:
    result = "Review"
print(result)</code></pre>
<p>条件は上から順に調べられます。80は90以上ではありませんが、70以上なので二番目が真となり、<code>"Pass"</code>が選ばれます。残りは調べません。つまり、この一連の分岐は最初に真となった一つだけを実行します。</p>
<p>このため順序が重要です。「50以上」を「80以上」より先に書くと、85も最初の条件に一致し、後の条件へ到達しません。範囲が重なるときは、より限定的な条件を先に置きます。コードが動くことと、意図した分類になることは別です。</p>
<table class="generaltable"><thead><tr><th>比較</th><th>意味</th></tr></thead><tbody><tr><td><code>==</code> / <code>!=</code></td><td>等しい / 等しくない</td></tr><tr><td><code>&lt;</code> / <code>&lt;=</code></td><td>より小さい / 以下</td></tr><tr><td><code>&gt;</code> / <code>&gt;=</code></td><td>より大きい / 以上</td></tr></tbody></table>
<p><code>&gt;= 50</code>は50を含み、<code>&gt; 50</code>は含みません。そこで49、50、51を試し、境界の直前・境界上・直後を確認します。</p>
<p>複数の条件には<code>and</code>、<code>or</code>、<code>not</code>を使います。<code>and</code>は両方が真、<code>or</code>は少なくとも一方が真、<code>not</code>は真偽を反転します。読みにくいときは、各比較を一つずつ判定してから全体を判断します。</p>
<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>registered = 35
rate = 72
if registered &gt;= 30 and rate &lt; 75:
    status = "Review"
else:
    status = "No review"
print(status)</code></pre>
<p>この例では二つとも真なので<code>"Review"</code>です。分類前には値の妥当性も確認します。点数が-5や120なら通常の合否に入れず、先に無効値として扱います。</p>
<p>最後に三段階の例へ戻り、69、70、89、90を試します。実行前に上から条件を追い、最初に<code>True</code>になる場所を書いてください。予想と結果が一致すれば、条件だけでなく分岐の順序も読めています。</p>
<aside style="border-left:4px solid #777;padding:.7em 1em;margin-top:1.5em"><strong>補足：</strong>独立した二つの<code>if</code>は両方が真なら両方を実行します。一つを選ぶ<code>if / elif / else</code>とは目的が異なります。</aside>
<p>ここまで読めたら、境界値と複合条件を含む短い分岐を自分で追跡できます。学習時間の目安は2時間です。</p><p style="display:none">PYAI-V10-CONDITIONS-FLOW</p></div>
HTML;
 $qs=[
 v10_q('L3R-01','C01','<p>次のコードを実行したとき、何が表示されますか。</p>'.$code("score = 80\nif score >= 90:\n    print(\"Excellent\")\nelif score >= 70:\n    print(\"Pass\")\nelse:\n    print(\"Review\")"),[['Passだけ','正解です。最初はFalse、二番目はTrueです。'],['ExcellentとPass','if/elif/elseでは最初に真となった一つだけです。'],['Reviewだけ','elseには進みません。'],['何も表示しない','二番目が真です。']],0,'C01 if/elif/elseは上から調べ、最初のTrueのブロック一つだけを実行します。'),
 v10_q('L3R-02','C02','<p>次のコードで<code>score = 50</code>のとき何が表示されますか。</p>'.$code("score = 50\nif score >= 50:\n    print(\"Pass\")\nelse:\n    print(\"Not yet\")"),[['Pass','正解です。>=は50を含みます。'],['Not yet','50は条件に含まれます。'],['両方','一方だけです。'],['エラー','有効な比較です。']],0,'C02 境界値そのものをコード内で評価します。'),
 v10_q('L3R-03','C03','<p>次のコードで<code>score = 85</code>の出力はどれですか。</p>'.$code("score = 85\nif score >= 50:\n    grade = \"Pass\"\nelif score >= 80:\n    grade = \"High pass\"\nelse:\n    grade = \"Not yet\"\nprint(grade)"),[['High pass','先にscore >= 50が真になります。'],['Pass','正解です。この順序では広い条件が先に一致します。'],['Not yet','最初の条件が真です。'],['PassとHigh pass','elifは続けて実行されません。']],1,'C03 重なり合う条件は具体的・厳しい条件を先に置きます。'),
 v10_q('L3R-04','C04','<p>次の二つは<code>score = 80</code>で同じ結果になりますか。</p>'.$code("if score >= 50:\n    print(\"Pass\")\nif score >= 70:\n    print(\"Merit\")\n\n# 比較対象\nif score >= 50:\n    print(\"Pass\")\nelif score >= 70:\n    print(\"Merit\")"),[['同じ。どちらも二行表示','elif側は最初の真で止まります。'],['異なる。独立したifは二行、if/elifはPassだけ','正解です。'],['異なる。独立したifは何も表示しない','両条件が真です。'],['常に構文エラー','どちらも有効です。']],1,'C04 独立したifと排他的なif/elif連鎖を区別します。'),
 v10_q('L3R-05','C05','<p>次のコードでpriorityがTrueになる組合せはどれですか。</p>'.$code("priority = registered >= 30 and rate < 75"),[['registered=28, rate=70','左がFalseです。'],['registered=35, rate=80','右がFalseです。'],['registered=35, rate=70','正解です。両方Trueです。'],['registered=20, rate=90','両方Falseです。']],2,'C05 andは両方の条件がTrueのときだけTrueです。'),
 v10_q('L3R-06','C06','<p>次のコードでreviewがFalseになる行はどれですか。</p>'.$code("review = attendance is None or completed > attendance"),[['attendance=None, completed=20','左がTrueです。'],['attendance=30, completed=35','右がTrueです。'],['attendance=30, completed=25','正解です。両方Falseです。'],['attendance=None, completed=50','少なくとも左がTrueです。']],2,'C06 orは両方FalseのときだけFalseです。'),
 v10_q('L3R-07','C07','<p>次のコードで<code>score = -5</code>なら何が表示されますか。</p>'.$code("score = -5\nif score < 0 or score > 100:\n    result = \"Invalid\"\nelif score >= 50:\n    result = \"Pass\"\nelse:\n    result = \"Not yet\"\nprint(result)"),[['Invalid','正解です。分類前の妥当性検証に入ります。'],['Pass','負の点数です。'],['Not yet','通常分類より先に無効値を処理します。'],['何も表示しない','resultは設定されます。']],0,'C07 無効値を通常の業務分類より先に分けます。'),
 v10_q('L3R-08','C08','<p>次の式で<code>score = 100</code>の結果は何ですか。</p>'.$code("valid = 0 <= score <= 100\nprint(valid)"),[['True','正解です。両端を含みます。'],['False','<=は100を含みます。'],['None','比較は真偽値を返します。'],['構文エラー','Pythonの連鎖比較は有効です。']],0,'C08 Pythonの連鎖比較と包含境界を読みます。'),
 v10_q('L3R-09','C09','<p>次のコードで<code>registered = 35</code>、<code>rate = 82</code>の出力はどれですか。</p>'.$code("if registered >= 30 and not rate >= 85:\n    print(\"Review\")\nelse:\n    print(\"No review\")"),[['Review','正解です。registered条件はTrue、rate >= 85はFalse、そのnotはTrueです。'],['No review','notを適用した後はTrueです。'],['両方','一方だけです。'],['エラー','有効な論理式です。']],0,'C09 notを適用する対象を明確にして複合条件を追います。'),
 v10_q('L3R-10','C10','<p>次の分類の境界誤りを最もよく発見するテスト値の組はどれですか。</p>'.$code("if rate < 75:\n    status = \"support\"\nelif rate < 85:\n    status = \"watch\"\nelse:\n    status = \"on track\""),[['75と85だけ','境界直前も必要です。'],['0と100だけ','内部境界を十分に検査できません。'],['74.9, 75, 84.9, 85','正解です。各境界の直前と境界上を確認します。'],['80だけ','中央の一分岐だけです。']],2,'C10 各しきい値の直前・境界上・必要なら直後をテストします。')];
}else{
 $quizname='Knowledge check: Lesson 3: Decisions with conditions';
 $intro='<p>Use this learning check to trace code and operational rules from the top. Predict each Boolean result, the first executed branch, boundaries, compound conditions, and validation order. Retry as needed; 90% passes and 100% is the goal.</p>';
 $lessonbody=<<<'HTML'
<div class="python-sample-lesson"><h2>Choose an action with conditions</h2>
<p>A program often needs to do different work for different values. A score that reaches a standard might produce “Pass”, while a lower score produces “Not yet”. Python expresses this choice with an <code>if</code> statement.</p>
<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>score = 68
if score &gt;= 50:
    result = "Pass"
else:
    result = "Not yet"
print(result)</code></pre>
<p><code>score &gt;= 50</code> is a condition evaluated as <code>True</code> or <code>False</code>. For 68 it is true, so the first indented block runs. Otherwise the <code>else</code> block runs. Indentation identifies the code belonging to each branch.</p>
<p>Add <code>elif</code> when more than two outcomes are possible.</p>
<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>score = 80
if score &gt;= 90:
    result = "Excellent"
elif score &gt;= 70:
    result = "Pass"
else:
    result = "Review"
print(result)</code></pre>
<p>Python checks from the top. Eighty is not at least 90, but it is at least 70, so <code>"Pass"</code> is selected and the rest is skipped. The chain runs only the first true branch.</p>
<p>Order therefore matters. If “at least 50” comes before “at least 80”, 85 matches the broad condition first and never reaches the specific one. Put the more selective overlapping condition first. Code that runs does not necessarily implement the intended rule.</p>
<table class="generaltable"><thead><tr><th>Comparison</th><th>Meaning</th></tr></thead><tbody><tr><td><code>==</code> / <code>!=</code></td><td>equal / not equal</td></tr><tr><td><code>&lt;</code> / <code>&lt;=</code></td><td>less than / at most</td></tr><tr><td><code>&gt;</code> / <code>&gt;=</code></td><td>greater than / at least</td></tr></tbody></table>
<p><code>&gt;= 50</code> includes 50, while <code>&gt; 50</code> does not. Test 49, 50, and 51 to check just below, exactly at, and just above the boundary.</p>
<p>Combine conditions with <code>and</code>, <code>or</code>, and <code>not</code>. <code>and</code> needs both sides, <code>or</code> needs at least one, and <code>not</code> reverses a Boolean result. When the expression is difficult, evaluate each comparison separately first.</p>
<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>registered = 35
rate = 72
if registered &gt;= 30 and rate &lt; 75:
    status = "Review"
else:
    status = "No review"
print(status)</code></pre>
<p>Both comparisons are true, so the result is <code>"Review"</code>. Before classification, also check validity. A score of -5 or 120 should be reported as invalid before ordinary pass/not-yet rules.</p>
<p>Return to the three-range example and try 69, 70, 89, and 90. Before running, trace from the top and write where the first <code>True</code> occurs. When prediction and output agree, you are reading both conditions and order.</p>
<aside style="border-left:4px solid #777;padding:.7em 1em;margin-top:1.5em"><strong>Supplement:</strong> two independent <code>if</code> statements can both run when both are true. That differs from an <code>if / elif / else</code> chain that chooses one branch.</aside>
<p>At this point you can trace a short decision containing boundaries and compound conditions. Estimated study time: 2 hours.</p><p style="display:none">PYAI-V10-CONDITIONS-FLOW</p></div>
HTML;
 $qs=[
 v10_q('L3R-01','C01','<p>What is displayed?</p>'.$code("score = 80\nif score >= 90:\n    print(\"Excellent\")\nelif score >= 70:\n    print(\"Pass\")\nelse:\n    print(\"Review\")"),[['Pass only','Correct.'],['Excellent and Pass','Only the first true branch runs.'],['Review only','Else is not reached.'],['Nothing','The second condition is true.']],0,'C01 Trace an if/elif/else chain from the top.'),
 v10_q('L3R-02','C02','<p>What is displayed when <code>score = 50</code>?</p>'.$code("score = 50\nif score >= 50:\n    print(\"Pass\")\nelse:\n    print(\"Not yet\")"),[['Pass','Correct; >= includes 50.'],['Not yet','50 is included.'],['Both','Only one branch.'],['Error','Valid comparison.']],0,'C02 Evaluate the stated boundary in complete code.'),
 v10_q('L3R-03','C03','<p>What is displayed for <code>score = 85</code>?</p>'.$code("score = 85\nif score >= 50:\n    grade = \"Pass\"\nelif score >= 80:\n    grade = \"High pass\"\nelse:\n    grade = \"Not yet\"\nprint(grade)"),[['High pass','The first condition already matched.'],['Pass','Correct; the broad condition is first.'],['Not yet','First condition is true.'],['Both','elif does not continue.']],1,'C03 Put more specific overlapping conditions first.'),
 v10_q('L3R-04','C04','<p>Do these produce the same output for <code>score = 80</code>?</p>'.$code("if score >= 50:\n    print(\"Pass\")\nif score >= 70:\n    print(\"Merit\")\n\n# Compare with\nif score >= 50:\n    print(\"Pass\")\nelif score >= 70:\n    print(\"Merit\")"),[['Same; both print two lines','The chain stops after Pass.'],['Different; independent if prints two lines, chain prints only Pass','Correct.'],['Different; independent if prints nothing','Both independent conditions are true.'],['Always syntax error','Both forms are valid.']],1,'C04 Distinguish independent if statements from an exclusive chain.'),
 v10_q('L3R-05','C05','<p>Which makes priority True?</p>'.$code("priority = registered >= 30 and rate < 75"),[['registered=28, rate=70','Left false.'],['registered=35, rate=80','Right false.'],['registered=35, rate=70','Correct.'],['registered=20, rate=90','Both false.']],2,'C05 and requires both conditions.'),
 v10_q('L3R-06','C06','<p>Which row makes review False?</p>'.$code("review = attendance is None or completed > attendance"),[['attendance=None, completed=20','Left true.'],['attendance=30, completed=35','Right true.'],['attendance=30, completed=25','Correct; both false.'],['attendance=None, completed=50','Left true.']],2,'C06 or is false only when both operands are false.'),
 v10_q('L3R-07','C07','<p>What is displayed for <code>score = -5</code>?</p>'.$code("score = -5\nif score < 0 or score > 100:\n    result = \"Invalid\"\nelif score >= 50:\n    result = \"Pass\"\nelse:\n    result = \"Not yet\"\nprint(result)"),[['Invalid','Correct.'],['Pass','Negative is invalid.'],['Not yet','Validation happens first.'],['Nothing','result is assigned.']],0,'C07 Validate the domain before ordinary classification.'),
 v10_q('L3R-08','C08','<p>What is printed for <code>score = 100</code>?</p>'.$code("valid = 0 <= score <= 100\nprint(valid)"),[['True','Correct; both ends included.'],['False','<= includes 100.'],['None','Comparison returns Boolean.'],['Syntax error','Chained comparison is valid.']],0,'C08 Read chained comparisons and inclusive boundaries.'),
 v10_q('L3R-09','C09','<p>What is printed for registered 35 and rate 82?</p>'.$code("if registered >= 30 and not rate >= 85:\n    print(\"Review\")\nelse:\n    print(\"No review\")"),[['Review','Correct.'],['No review','not makes the second part true.'],['Both','One branch.'],['Error','Valid logic.']],0,'C09 Trace not within a compound condition.'),
 v10_q('L3R-10','C10','<p>Which values best expose boundary errors?</p>'.$code("if rate < 75:\n    status = \"support\"\nelif rate < 85:\n    status = \"watch\"\nelse:\n    status = \"on track\""),[['75 and 85 only','Need values just below too.'],['0 and 100 only','Misses internal boundaries.'],['74.9, 75, 84.9, 85','Correct.'],['80 only','Tests one middle branch.']],2,'C10 Test just below and exactly at each threshold.')];
}
$lessonname=$lang==='ja'?'レッスン3：条件による判断':'Lesson 3: Decisions with conditions';
$lessonpage=$DB->get_record('page',['course'=>$course->id,'name'=>$lessonname],'*',MUST_EXIST);
$lessonpage->intro=$lang==='ja'?'<p>値を比較し、条件の順序を追い、意図した処理を一つ選びます。</p>':'<p>Compare values, trace conditions in order, and select the intended action.</p>';
$lessonpage->introformat=FORMAT_HTML;$lessonpage->content=$lessonbody;$lessonpage->contentformat=FORMAT_HTML;$lessonpage->timemodified=time();$DB->update_record('page',$lessonpage);
foreach($DB->get_records('page',['course'=>$course->id]) as $contentpage){
 $before=$contentpage->content;
 $contentpage->content=preg_replace('~<p><strong>(?:Naledi[^<]*|ナレディ[^<]*)</strong>.*?</p>~su','',$contentpage->content);
 $contentpage->content=preg_replace('~<div[^>]*><strong>(?:AI checkpoint|AI利用の確認|AIチェックポイント)[^<]*</strong>.*?</div>~su','',$contentpage->content);
 if($contentpage->content!==$before){$contentpage->timemodified=time();$DB->update_record('page',$contentpage);}
}
foreach(['Teacher guide (hidden from students)','教師用ガイド（学習者には非表示）'] as $teachername){
 if($teacherpage=$DB->get_record('page',['course'=>$course->id,'name'=>$teachername])){
  $teachercm=get_coursemodule_from_instance('page',$teacherpage->id,$course->id,false,MUST_EXIST);
  course_delete_module($teachercm->id);
 }
}
$quiz=$DB->get_record('quiz',['course'=>$course->id,'name'=>$quizname],'*',MUST_EXIST);
$attemptsremoved=(int)$DB->count_records('quiz_attempts',['quiz'=>$quiz->id]);
if($attemptsremoved>0){quiz_delete_all_attempts($quiz);}
$settings=\mod_quiz\quiz_settings::create($quiz->id);$structure=\mod_quiz\structure::create_for_quiz($settings);
foreach(array_reverse($structure->get_slots()) as $slot){$structure->remove_slot($slot->slot);}
$quiz->name=$quizname;$quiz->intro=$intro;$quiz->introformat=FORMAT_HTML;$quiz->attempts=0;$quiz->grademethod=QUIZ_GRADEHIGHEST;$quiz->timemodified=time();$DB->update_record('quiz',$quiz);
$context=context_course::instance($course->id);$category=$DB->get_record('question_categories',['contextid'=>$context->id,'name'=>'Python course checks']);
if(!$category){$all=$DB->get_records('question_categories',['contextid'=>$context->id],'id ASC');$category=reset($all);}
foreach($qs as $d){$saved=v10_save($category->id,$context->id,$shortname.' v2: ',$d,$lang);quiz_add_quiz_question($saved->id,$quiz,0,10);}
$DB->set_field('quiz_slots','maxmark',10,['quizid'=>$quiz->id]);\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
rebuild_course_cache($course->id,true);
echo json_encode(['upgraded'=>true,'version'=>10,'course_id'=>(int)$course->id,'shortname'=>$shortname,'questions'=>count($qs),'attempts_removed'=>$attemptsremoved],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
