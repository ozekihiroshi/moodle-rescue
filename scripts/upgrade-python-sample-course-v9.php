<?php
// Apply v8 foundations, then upgrade Lesson 1.2 to variables and state.
require __DIR__ . '/upgrade-python-sample-course-v8.php';

// Evidence contract: V01 V02 V03 V04 V05 V06 V07 V08 V09 V10.
if ($language === 'ja') {
    $n=['topic'=>'1.2 変数・代入・プログラムの状態','lesson'=>'レッスン2：変数・代入・プログラムの状態','lab'=>'Python Lab 02：変数・代入・状態','quiz'=>'理解度チェック：レッスン2 変数・代入・プログラムの状態'];
    $summary='<p>意味の分かる名前を付け、代入を右辺から追跡し、再現可能なNotebookの状態を作ります。</p>';
    $intro='<p>変数・代入・再代入・名前のエラーを、予想と状態の追跡によって理解します。</p>';
    $body=<<<'HTML'
<div class="python-sample-lesson"><h2>名前を使って変更に強いプログラムを作る</h2>
<p>前のレッスンでは同じ値を複数箇所へ直接書いたため、一方だけ直す危険がありました。変数は値やオブジェクトに意味の分かる名前を与えます。ここではデータ型一覧や入力へ進まず、<strong>名前・代入・実行中の状態</strong>に集中します。</p>
<h3>名前は値を指す（V01）</h3><p><code>registered = 40</code>では右辺を評価し、名前が結果のオブジェクトを指すようにします。この考えは後のリストやクラスにもつながります。</p>
<h3>代入と比較（V02）</h3><p><code>=</code>は代入、<code>==</code>は左右が等しいかを調べる比較です。</p>
HTML
        .v8_code("registered = 40\nprint(registered == 40)")
        .<<<'HTML'
<h3>右辺を先に評価する（V03・V04）</h3><p><code>total = total + amount</code>は数式の等号ではありません。現在の値で右辺を計算し、その後で<code>total</code>を更新します。再代入は現在の状態を変え、過去の値を自動保存しません。</p>
HTML
        .v8_code("total = 12\namount = 5\nprint(\"更新前:\", total)\ntotal = total + amount\nprint(\"更新後:\", total)")
        .<<<'HTML'
<h3>名前の規則とNameError（V05・V06）</h3><ul><li>文字・数字・アンダースコアを使えるが数字から始めない</li><li>予約語を使わない</li><li>大文字と小文字を区別する</li><li><code>x</code>より<code>completed_learners</code>のように意味を示す</li></ul><p>未定義名やつづり間違いでは通常<code>NameError</code>になります。Tracebackの最終行を読み、定義名と一文字ずつ比較します。</p>
<h3>Notebookの隠れた状態をなくす（V08）</h3><p>Kernelは以前実行したセルの名前を覚えています。保存後に<strong>Restart Kernel and Run All Cells</strong>を行い、上から成功することを確認します。</p>
<h3>この先のための予告</h3><ul><li>V07：二つの名前が同じ変更可能なリストを指すことがあり、代入は常にコピーではない（第3章で詳説）。</li><li>V09：<code>MAX_SEATS</code>の大文字は定数として扱う慣習であり、Pythonによる強制ではない。</li><li>V10：アンパックは名前と値の個数を一致させる。</li></ul>
<h3>例題</h3><p>15回を計画し2回を中止した実施回数を表示します。実行前に各行後の状態を書き、<code>cancelled</code>だけを4へ変更します。</p>
HTML
        .v8_code("planned = 15\ncancelled = 2\ndelivered = planned - cancelled\nprint(\"計画:\", planned)\nprint(\"中止:\", cancelled)\nprint(\"実施:\", delivered)")
        .<<<'HTML'
<h3>よくある間違い</h3><ul><li><code>=</code>を比較だと思う</li><li>右辺を読む前に左辺が変わると思う</li><li><code>Score</code>と<code>score</code>を同じ名前だと思う</li><li>セルを飛び越して隠れた状態へ依存する</li><li>代入でリストも必ず複製されると思う</li></ul>
<h3>応用練習</h3><p>研修室の定員24人、午前利用18人、午後利用20人に意味のある名前を付け、午前・午後の空席を表示します。定員一か所だけを22へ変え、Kernel再起動後に全セルを実行してください。</p>
<h3>完了時にできること</h3><p>各代入後に名前が指す値、<code>=</code>と<code>==</code>、<code>NameError</code>の原因を説明し、Restart and Run Allで再現できます。学習時間の目安は2時間です。次は基本データ型・変換・算術を体系的に学びます。</p><p style="display:none">PYAI-V9-LESSON-1-2</p></div>
HTML;
    $teacher='<h2>レッスン1.2 指導メモ</h2><p>完成コードより各行後の状態説明を確認します。</p><ul><li>V01/V02：名前が何を指すか、式は状態を変えるか</li><li>V03/V04：右辺評価時のtotalはいくつか</li><li>V05/V06：Traceback最終行とつづり・大小文字</li><li>V07：二つの名前は同じリストか</li><li>V08：新しいKernelから上順で動くか</li><li>V09/V10：大文字は強制か、個数不一致では何が起きるか</li></ul><h3>教師用解答</h3><p><code>capacity = 24</code>、<code>morning_used = 18</code>、<code>afternoon_used = 20</code>を一度ずつ定義し差を表示します。定員22では空席4と2です。</p><p style="display:none">PYAI-V9-TEACHER-L1-2</p>';
    $questions=[
        v8_question('L2R-01','registered = 40の説明は？',[['registeredが値40を指す','V01 正解です。'],['40がregisteredを指す','逆です。'],['必ず固定型の箱を作る','参照モデルが後の内容と整合します。'],['40を表示する','表示しません。']],0,'V01 名前は右辺の評価結果を指します。'),
        v8_question('L2R-02','状態を変えず40と等しいか調べる式は？',[['registered = 40','代入です。'],['registered == 40','V02 正解です。'],['40 = registered','値は代入先にできません。'],['print = 40','別名を上書きします。']],1,'V02 =は代入、==は比較です。'),
        v8_question('L2R-03','total=12; amount=5; total=total+amount のtotalは？',[['5','amountだけです。'],['12','更新前です。'],['17','V03 正解です。'],['エラー','定義済みです。']],2,'V03 右辺を現在値で評価してから代入します。'),
        v8_question('L2R-04','openの後closedを再代入した現在値は？',[['両方','自動履歴はありません。'],['closed','V04 正解です。'],['open','更新前です。'],['エラー','再代入は有効です。']],1,'V04 再代入は現在状態を変えます。'),
        v8_question('L2R-05','有効で意味のある名前は？',[['2nd_group','数字開始不可。'],['class','予約語です。'],['completed_learners','V05 正解です。'],['Completed Learners','空白不可。']],2,'V05 規則と意味を確認します。'),
        v8_question('L2R-06','completed定義後completeでNameError。最初に何をする？',[['0を代入','原因を隠します。'],['Traceback最終行とつづりを確認','V06 正解です。'],['再インストール','局所問題です。'],['文字列化','直りません。']],1,'V06 未定義・つづり・実行順を確認します。'),
        v8_question('L2R-07','b = a 後にbのリスト変更がaにも見える理由は？',[['同じ変更可能オブジェクトを指すことがある','V07 正解です。'],['常に深いコピー','違います。'],['リストは定数','変更可能です。'],['大小文字を無視','区別します。']],0,'V07 代入は常にコピーではありません。'),
        v8_question('L2R-08','Notebookの隠れた状態を検査する方法は？',[['最後だけ再実行','古い状態が残ります。'],['Kernel再起動後に全セルを上から実行','V08 正解です。'],['改名','検査になりません。'],['出力編集','再現性なし。']],1,'V08 新しいKernelからRun Allします。'),
        v8_question('L2R-09','MAX_SEATSの大文字の意味は？',[['再代入禁止','禁止されません。'],['定数として扱う慣習','V09 正解です。'],['文字列型','数値です。'],['自動global','違います。']],1,'V09 大文字は慣習であり強制ではありません。'),
        v8_question('L2R-10','centre, month = ("North",) の問題は？',[['名前二つと値一つで個数不一致','V10 正解です。'],['引用符不正','正しいです。'],['タプル代入不可','アンパック可。'],['monthがNone','自動補完なし。']],0,'V10 アンパックでは個数を合わせます。'),
    ];
} else {
    $n=['topic'=>'1.2 Variables, assignment, and program state','lesson'=>'Lesson 2: Variables, assignment, and program state','lab'=>'Python Lab 02: Variables, assignment, and state','quiz'=>'Knowledge check: Lesson 2: Variables, assignment, and program state'];
    $summary='<p>Give values meaningful names, trace assignment from the right, and produce reproducible Notebook state.</p>';
    $intro='<p>Understand variables, assignment, reassignment, and name errors by predicting execution and tracing state.</p>';
    $body=<<<'HTML'
<div class="python-sample-lesson"><h2>Use names to make programs safe to change</h2>
<p>The previous lesson repeated one value in several expressions, so one edit could be missed. A variable gives a value or object one meaningful name. This lesson concentrates on <strong>names, assignment, and runtime state</strong>; scalar types, input, conversion, and formatting follow in Lessons 1.3 and 1.4.</p>
<h3>A name refers to a value (V01)</h3><p>In <code>registered = 40</code>, Python evaluates the right side and makes the name refer to that result. This model remains useful for later lists and classes.</p>
<h3>Assignment and comparison (V02)</h3><p><code>=</code> assigns; <code>==</code> compares and produces a Boolean.</p>
HTML
        .v8_code("registered = 40\nprint(registered == 40)")
        .<<<'HTML'
<h3>Evaluate the right side first (V03, V04)</h3><p><code>total = total + amount</code> is not algebraic equality. Read current values on the right, calculate, then update <code>total</code>. Reassignment changes current state without automatic history.</p>
HTML
        .v8_code("total = 12\namount = 5\nprint(\"before:\", total)\ntotal = total + amount\nprint(\"after:\", total)")
        .<<<'HTML'
<h3>Naming rules and NameError (V05, V06)</h3><ul><li>letters, digits, and underscores, but not a leading digit;</li><li>no Python keywords;</li><li>case-sensitive names;</li><li>prefer <code>completed_learners</code> to <code>x</code>.</li></ul><p>Undefined or misspelled names normally raise <code>NameError</code>. Read the final traceback line and compare spelling exactly.</p>
<h3>Remove hidden Notebook state (V08)</h3><p>A kernel remembers names from earlier execution. Save, choose <strong>Restart Kernel and Run All Cells</strong>, and confirm success from the top.</p>
<h3>Three previews</h3><ul><li>V07: two names may refer to one mutable list; assignment does not always copy (Chapter 3).</li><li>V09: <code>MAX_SEATS</code> signals a constant convention; Python does not enforce it.</li><li>V10: unpacking requires matching numbers of names and values.</li></ul>
<h3>Guided example</h3><p>A centre planned 15 sessions and cancelled 2. Record state after each line, run, then change only <code>cancelled</code> to 4.</p>
HTML
        .v8_code("planned = 15\ncancelled = 2\ndelivered = planned - cancelled\nprint(\"Planned:\", planned)\nprint(\"Cancelled:\", cancelled)\nprint(\"Delivered:\", delivered)")
        .<<<'HTML'
<h3>Common mistakes</h3><ul><li>reading <code>=</code> as comparison;</li><li>changing the left name before reading the right;</li><li>treating <code>Score</code> and <code>score</code> as one;</li><li>running cells out of order;</li><li>assuming assignment always copies a list.</li></ul>
<h3>Transfer exercise</h3><p>A room holds 24 people; morning uses 18 places and afternoon 20. Give each one meaningful name and display unused places. Change capacity once to 22 and prove reproducibility with Restart and Run All.</p>
<h3>Completion capability</h3><p>Explain state after each assignment, distinguish <code>=</code> from <code>==</code>, diagnose <code>NameError</code>, and reproduce the result from a clean kernel. Estimated time: 2 hours. Next: scalar types, conversion, and arithmetic.</p><p style="display:none">PYAI-V9-LESSON-1-2</p></div>
HTML;
    $teacher='<h2>Lesson 1.2 facilitation notes</h2><p>Assess state explanations, not only finished code.</p><ul><li>V01/V02: what does the name refer to; does this change state?</li><li>V03/V04: what is total when the right side is evaluated?</li><li>V05/V06: read the final traceback line and compare spelling/case.</li><li>V07: do both names refer to the same list?</li><li>V08: does it run from a fresh kernel?</li><li>V09/V10: is upper case enforced; what happens when counts differ?</li></ul><h3>Teacher reference</h3><p>Define <code>capacity = 24</code>, <code>morning_used = 18</code>, and <code>afternoon_used = 20</code> once and display both differences. Capacity 22 gives 4 and 2.</p><p style="display:none">PYAI-V9-TEACHER-L1-2</p>';
    $questions=[
        v8_question('L2R-01','Which best explains registered = 40?',[['registered refers to 40','V01 Correct.'],['40 refers to registered','Reversed.'],['Always a fixed typed box','Reference model generalises.'],['Displays 40','It does not.']],0,'V01 A name refers to the evaluated result.'),
        v8_question('L2R-02','Which checks equality without changing state?',[['registered = 40','Assigns.'],['registered == 40','V02 Correct.'],['40 = registered','Invalid target.'],['print = 40','Overwrites a name.']],1,'V02 = assigns; == compares.'),
        v8_question('L2R-03','After total=12; amount=5; total=total+amount, total is?',[['5','Only amount.'],['12','Before update.'],['17','V03 Correct.'],['Error','Already defined.']],2,'V03 Evaluate right side first.'),
        v8_question('L2R-04','After open then closed reassignment, current status is?',[['Both','No automatic history.'],['closed','V04 Correct.'],['open','Earlier.'],['Error','Valid.']],1,'V04 Reassignment changes current state.'),
        v8_question('L2R-05','Which is valid and meaningful?',[['2nd_group','Leading digit.'],['class','Keyword.'],['completed_learners','V05 Correct.'],['Completed Learners','Space.']],2,'V05 Apply identifier rules and meaning.'),
        v8_question('L2R-06','completed is defined; complete raises NameError. First action?',[['Assign zero','Hides cause.'],['Read final traceback line and compare spelling','V06 Correct.'],['Reinstall','Local issue.'],['Convert to text','No.']],1,'V06 Check definition, spelling, case, and order.'),
        v8_question('L2R-07','Why may changing b after b = a appear through a?',[['Same mutable object','V07 Correct.'],['Always deep copy','False.'],['Lists constant','False.'],['Case ignored','False.']],0,'V07 Assignment may make another reference.'),
        v8_question('L2R-08','Best hidden-state check?',[['Rerun final cell','Old state.'],['Restart kernel and Run All','V08 Correct.'],['Rename','No.'],['Edit output','No.']],1,'V08 Verify from a clean kernel.'),
        v8_question('L2R-09','What does upper case in MAX_SEATS mean?',[['Reassignment forbidden','No.'],['Constant convention','V09 Correct.'],['Text only','No.'],['Automatically global','No.']],1,'V09 Convention, not enforcement.'),
        v8_question('L2R-10','Problem with centre, month = ("North",)?',[['Two names, one value','V10 Correct.'],['Quotes invalid','Valid.'],['Tuple cannot unpack','It can.'],['month becomes None','No.']],0,'V10 Unpacking requires matching counts.'),
    ];
}
$sub=v8_find_record('subsection',$course->id,['1.2 Variables, types, input, and calculations','1.2 変数・データ型・入力・計算',$n['topic']]);
$sub->name=$n['topic']; $sub->timemodified=time(); $DB->update_record('subsection',$sub);
$section=$DB->get_record('course_sections',['course'=>$course->id,'component'=>'mod_subsection','itemid'=>$sub->id],'*',MUST_EXIST);
course_update_section($course,$section,['name'=>$n['topic'],'summary'=>$summary,'summaryformat'=>FORMAT_HTML]);
$page=v8_find_record('page',$course->id,['Lesson 2: Variables, types, input, and calculations','レッスン2：変数・データ型・入力・計算',$n['lesson']]);
v8_update_page($page,$n['lesson'],$intro,$body);
$lti=v8_find_record('lti',$course->id,['Python Lab 02: Variables, types, and calculations','Python Lab 02：変数・データ型・計算',$n['lab']]);
$lti->name=$n['lab'];
$lti->toolurl=preg_replace('~/hub/user-redirect/lab/tree/.*$~','/hub/user-redirect/lab/tree/'.($language==='ja'?'ja/':'').'02_variables_types_calculations.ipynb',$lti->toolurl);
$lti->timemodified=time(); $DB->update_record('lti',$lti);
$teacherpage=v8_find_record('page',$course->id,['Teacher guide (hidden from students)','教師用ガイド（学習者には非表示）']);
v8_update_page($teacherpage,$teacherpage->name,(string)$teacherpage->intro,v8_upsert_marked_section($teacherpage->content,'PYAI-V9-TEACHER-L1-2',$teacher));
set_coursemodule_visible(get_coursemodule_from_instance('page',$teacherpage->id,$course->id,false,MUST_EXIST)->id,0);
$quiz=v8_find_record('quiz',$course->id,['Knowledge check: Lesson 2: Variables, types, input, and calculations','理解度チェック：レッスン2 変数・データ型・入力・計算',$n['quiz']]);
if ($DB->record_exists('quiz_attempts',['quiz'=>$quiz->id])) { throw new moodle_exception('Lesson 2 has attempts; v9 refuses to overwrite them.'); }
$settings=\mod_quiz\quiz_settings::create($quiz->id); $structure=\mod_quiz\structure::create_for_quiz($settings);
foreach (array_reverse($structure->get_slots()) as $slot) { $structure->remove_slot($slot->slot); }
$quiz->name=$n['quiz'];
$quiz->intro=$language==='ja'?'<p>これは一回限りの試験ではなく理解を定着させる確認です。説明を読み、Notebookで確かめて再挑戦してください。90%以上で合格、100%を目指せます。</p>':'<p>This is a learning check, not a one-time test. Read feedback, verify in the Notebook, and retry. 90% passes; aim for 100%.</p>';
$quiz->introformat=FORMAT_HTML; $quiz->attempts=0; $quiz->grademethod=QUIZ_GRADEHIGHEST; $quiz->timemodified=time(); $DB->update_record('quiz',$quiz);
$context=context_course::instance($course->id); $category=$DB->get_record('question_categories',['contextid'=>$context->id,'name'=>'Python course checks']);
if (!$category) { $categories=$DB->get_records('question_categories',['contextid'=>$context->id],'id ASC'); $category=reset($categories); }
foreach ($questions as $data) { $saved=v8_save_question($category->id,$context->id,$shortname.' v2: ',$data,$language); quiz_add_quiz_question($saved->id,$quiz,0,10); }
$DB->set_field('quiz_slots','maxmark',10,['quizid'=>$quiz->id]);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
rebuild_course_cache($course->id,true);
echo json_encode(['upgraded'=>true,'version'=>9,'course_id'=>(int)$course->id,'shortname'=>$shortname,'language'=>$language,'lesson'=>$n['lesson'],'questions'=>count($questions)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
