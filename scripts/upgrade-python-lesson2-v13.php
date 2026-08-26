<?php
// Rewrite Lesson 2 and its learning check around assignment and program state.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';

use core_question\local\bank\question_version_status;

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
\core\session\manager::set_user(get_admin());

function v13_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

function v13_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v13_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
    $question = (object) ['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['choices'] as $index => [$answer, $why]) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => '<p>' . s($why) . '</p>', 'format' => FORMAT_HTML];
        $fractions[] = $index === $data['correct'] ? 1.0 : 0.0;
    }
    $form = (object) [
        'name' => $prefix . $data['id'],
        'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => $data['prompt'], 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p><strong>' . ($language === 'ja' ? '学習ポイント：' : 'Learning point:')
            . '</strong> ' . s($data['explanation']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 10,
        'penalty' => 0.3333333,
        'status' => question_version_status::QUESTION_STATUS_READY,
        'idnumber' => null,
        'single' => 1,
        'shuffleanswers' => 1,
        'answernumbering' => 'abc',
        'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => $language === 'ja'
            ? '<p>正解です。各行の実行後に名前が指す値を説明してから次へ進みましょう。</p>'
            : '<p>Correct. Explain what each name refers to after every line before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>右辺から一行ずつ状態を追い、Notebookで実行してから再挑戦しましょう。</p>'
            : '<p>Trace state one line at a time from the right-hand side, run it in the Notebook, and try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

if ($language === 'ja') {
    $lessonname = 'レッスン2：変数・代入・プログラムの状態';
    $quizname = '理解度チェック：レッスン2 変数・代入・プログラムの状態';
    $intro = '<p>値に意味のある名前を付け、代入を右辺から追いながら、実行によって変化するプログラムの状態を理解します。</p>';
    $body = '<div class="python-sample-lesson"><h2>同じ意味の値を一か所で管理する</h2>'
        . '<p>Lesson 1の最後では、午前の参加枠18を複数の式へ直接書いたため、20へ変えるときに何か所も直す必要がありました。同じ意味の値が離れた場所に重複すると、直し忘れによって出力が矛盾します。そこで、値に一つの名前を付けます。</p>'
        . v13_code("capacity = 40\nmorning = 18\nafternoon = 12\n\nprint(\"合計:\", morning + afternoon)\nprint(\"未使用席:\", capacity - (morning + afternoon))")
        . '<p><code>morning = 18</code>を実行すると、Pythonはまず右辺の18を評価し、<code>morning</code>という名前がその値を指すようにします。この処理が代入です。その後の式は18を直接書く代わりに<code>morning</code>を読みます。午前を20へ変えるときは、最初の代入一か所を直せば、合計と未使用席の両方が同じ情報を使います。</p>'
        . '<h3>代入は、数学の等号とは違う</h3>'
        . '<p>Pythonの一つの<code>=</code>は、「左右が等しい」と主張する記号ではありません。右辺を評価した後、その結果を左辺の名前へ代入する命令です。左右が等しいかを調べるときは二つの<code>==</code>を使います。</p>'
        . v13_code("registered = 40\nprint(registered == 40)\nprint(registered == 35)")
        . '<p>一行目は状態を作り、後の二行はその状態を変えずに比較します。結果は順に<code>True</code>と<code>False</code>です。真偽値と条件による判断は後の章で詳しく学びますが、ここでは<code>=</code>と<code>==</code>を混同しないことが重要です。</p>'
        . '<h3>右辺を読んでから、左辺を更新する</h3>'
        . '<p>一度付けた名前へ、別の値を代入することもできます。これを再代入といいます。次のコードを、各行の実行後に<code>total</code>が指す値を書きながら読んでください。</p>'
        . v13_code("total = 12\namount = 5\ntotal = total + amount\nprint(total)")
        . '<p>三行目では、右辺の<code>total</code>はまだ12です。12と5を加えて17を作り、その後で左辺の<code>total</code>を17へ更新します。したがって、数式として<code>total = total + amount</code>を読むのではなく、「現在の合計に金額を加え、その結果を新しい合計にする」と実行順に読みます。</p>'
        . '<p>この更新は<code>total += amount</code>とも書けます。短い書き方ですが、右辺の値を使ってから左辺を更新する考え方は同じです。このコースでは、まず長い形で状態を追えるようになってから短い形を使います。</p>'
        . '<h3>計算済みの値は、自動では変わらない</h3>'
        . '<p>代入は、その行を実行した時点の結果を名前へ結び付けます。表計算のセルのように、元の値が変わるたび自動で式を再計算するわけではありません。</p>'
        . v13_code("planned = 15\ncancelled = 2\ndelivered = planned - cancelled\n\ncancelled = 4\nprint(\"中止:\", cancelled)\nprint(\"実施:\", delivered)")
        . '<p><code>delivered</code>は三行目で13になりました。その後<code>cancelled</code>を4へ変更しても、すでに計算した<code>delivered</code>は13のままです。実施回数を11へ更新するには、<code>delivered = planned - cancelled</code>をもう一度実行します。どの値を変更したら、どの計算を再実行する必要があるかを理解することが、状態を扱う第一歩です。</p>'
        . '<h3>名前は規則を守り、意味を伝える</h3>'
        . '<p>名前はコードを読む人への説明にもなります。<code>x</code>より<code>completed_learners</code>の方が、何を表すか分かります。ただし、自由な文章をそのまま名前にはできません。</p>'
        . '<table class="generaltable"><thead><tr><th>名前</th><th>使用可否</th><th>理由</th></tr></thead><tbody><tr><td><code>completed_learners</code></td><td>使用できる</td><td>文字とアンダースコアで意味を示している</td></tr><tr><td><code>group2</code></td><td>使用できる</td><td>数字は先頭でなければ使える</td></tr><tr><td><code>2nd_group</code></td><td>使用できない</td><td>数字から始まっている</td></tr><tr><td><code>completed learners</code></td><td>使用できない</td><td>空白を含んでいる</td></tr><tr><td><code>class</code></td><td>使用できない</td><td>Pythonが文法に使う予約語である</td></tr></tbody></table>'
        . '<p>大文字と小文字も区別されるため、<code>Score</code>と<code>score</code>は別の名前です。通常の変数には小文字とアンダースコアを使います。<code>MAX_SEATS</code>のような全大文字は「プログラム中で変更しない値」として扱う慣習ですが、Pythonが再代入を禁止するわけではありません。</p>'
        . '<h3><code>NameError</code>から実行順序を調べる</h3>'
        . '<p>まだ代入していない名前、つづりの違う名前、または大文字・小文字の違う名前を読むと、通常は<code>NameError</code>になります。Tracebackの最後の行にある例外名とメッセージを読み、コード中の名前を一文字ずつ比較します。</p>'
        . v13_code("completed_learners = 29\nprint(completed_learner)")
        . '<p>この例では末尾の<code>s</code>がありません。Notebookでは、正しい代入セルが画面上にあっても、まだ実行していなければ同じエラーになります。名前のつづりだけでなく、必要な代入が先に実行されたかも確認します。</p>'
        . '<h3>Notebookの状態を再現する</h3>'
        . '<p>Kernelは、以前実行したセルで作られた名前を覚えています。そのため、下のセルを先に実行したNotebookが自分の画面では動いても、別の人が上から実行すると失敗する場合があります。保存前にKernelを再起動し、すべてのセルを上から実行してください。これで、必要な代入が使う場所より前にあり、隠れた状態へ依存していないことを確認できます。</p>'
        . '<h3>例題から応用へ</h3>'
        . '<p>ある学習センターは15回の講座を計画し、2回を中止しました。<code>planned</code>、<code>cancelled</code>、<code>delivered</code>を使って三つの値を表示します。まず各行後の状態を予想し、実行後に<code>cancelled</code>を4へ変えてください。実施回数を正しく更新するにはどの行を再実行する必要があるか、Markdownセルで説明します。</p>'
        . '<p>次に、研修室の定員24人、午前利用18人、午後利用20人へ意味のある名前を付け、午前と午後の空席を表示します。定員の代入一か所だけを22へ変え、Kernelを再起動して全セルを上から実行し、空席が4人と2人になることを確認してください。</p>'
        . '<p>このレッスンを終えると、代入を右辺から追い、再代入後の状態を説明し、<code>=</code>と<code>==</code>を区別し、<code>NameError</code>とNotebookの実行順を調べられます。次のレッスンでは、名前が指す値の種類を基本データ型として体系的に学びます。</p>'
        . '<p><strong>学習時間の目安：</strong>約2時間</p><p style="display:none">PYAI-V13-LESSON2-FLOW</p></div>';
    $questions = [
        v13_question('L2R-01', '<p>何が表示されますか。</p>' . v13_code("registered = 40\nprint(registered)"), [['40', '正解です。名前は代入された値を指します。'], ['registered', 'printは名前が指す値を読みます。'], ['True', '比較は行っていません。'], ['何も表示されない', 'printが実行されます。']], 0, '代入後に名前を読むと、その時点で指している値が得られます。'),
        v13_question('L2R-02', '<p>何が表示され、<code>registered</code>の値はどうなりますか。</p>' . v13_code("registered = 40\nprint(registered == 35)"), [['Falseが表示され、registeredは40のまま', '正解です。比較は状態を変更しません。'], ['Falseが表示され、registeredは35になる', '==は代入ではありません。'], ['Trueが表示され、registeredは40のまま', '40と35は等しくありません。'], ['文法エラーになる', '有効な比較です。']], 0, '<code>=</code>は代入、<code>==</code>は状態を変えない比較です。'),
        v13_question('L2R-03', '<p>最後に何が表示されますか。</p>' . v13_code("total = 12\namount = 5\ntotal = total + amount\nprint(total)"), [['5', 'amountだけの値です。'], ['12', '更新前の値です。'], ['17', '正解です。右辺を12と5で評価してから更新します。'], ['エラー', 'totalは先に定義されています。']], 2, '再代入では現在の右辺を評価してから左辺を更新します。'),
        v13_question('L2R-04', '<p>何が表示されますか。</p>' . v13_code("planned = 15\ncancelled = 2\ndelivered = planned - cancelled\ncancelled = 4\nprint(delivered)"), [['11', 'deliveredを再計算していません。'], ['13', '正解です。計算済みの値は自動更新されません。'], ['4', 'これはcancelledの現在値です。'], ['エラー', 'すべての名前は定義されています。']], 1, '代入は実行時点の結果を保存し、依存元の再代入だけでは自動再計算しません。'),
        v13_question('L2R-05', '<p>最後に何が表示されますか。</p>' . v13_code("planned = 15\ncancelled = 2\ndelivered = planned - cancelled\ncancelled = 4\ndelivered = planned - cancelled\nprint(delivered)"), [['11', '正解です。変更後に式を再実行しました。'], ['13', 'これは再計算前です。'], ['19', '加算ではありません。'], ['4', 'cancelledだけの値です。']], 0, '元の状態を変えた後、派生値の代入も再実行すると整合した状態になります。'),
        v13_question('L2R-06', '<p>何が表示されますか。</p>' . v13_code("total = 10\namount = 3\ntotal += amount\nprint(total)"), [['3', 'amountだけではありません。'], ['10', '更新前のtotalです。'], ['13', '正解です。total = total + amountの短縮形です。'], ['103', '数値の加算です。']], 2, '<code>+=</code>も現在値を読んでから左辺を更新します。'),
        v13_question('L2R-07', '<p>次のうち、Pythonで有効かつ意味の分かる変数名はどれですか。</p>', [['2nd_group', '数字から始められません。'], ['class', 'Pythonの予約語です。'], ['completed_learners', '正解です。規則を守り意味も伝わります。'], ['completed learners', '空白を含められません。']], 2, '識別子の規則を守り、値の意味が分かる名前を選びます。'),
        v13_question('L2R-08', '<p>このコードを上から実行すると、最初に何が起こりますか。</p>' . v13_code("print(completed)\ncompleted = 29"), [['29が表示される', '代入は後の行です。'], ['Noneが表示される', '未定義名へ自動でNoneは入りません。'], ['NameErrorになる', '正解です。読む前に代入が必要です。'], ['completedという文字が表示される', '引用符で囲まれていません。']], 2, 'Notebookでも、名前を読む前にその代入セルを実行する必要があります。'),
        v13_question('L2R-09', '<p>Notebookが隠れた実行状態へ依存していないことを最も確実に確認する方法はどれですか。</p>', [['最後のセルだけを再実行する', '古いKernel状態が残ります。'], ['Kernelを再起動し、全セルを上から実行する', '正解です。初期状態から再現できます。'], ['表示結果を手作業で直す', 'コードの再現性を確認できません。'], ['変数名をすべてxにする', '依存関係は解決しません。']], 1, '新しいKernelから上順に全セルが成功することを確認します。'),
        v13_question('L2R-10', '<p>何が表示されますか。</p>' . v13_code("MAX_SEATS = 40\nMAX_SEATS = 36\nprint(MAX_SEATS)"), [['40', '二行目で再代入されています。'], ['36', '正解です。全大文字は慣習であり強制ではありません。'], ['MAX_SEATS', '名前が指す値が表示されます。'], ['再代入が禁止されるためエラー', 'Pythonは定数慣習を強制しません。']], 1, '全大文字は変更しない意図を示しますが、Pythonによる再代入禁止ではありません。'),
    ];
} else {
    $lessonname = 'Lesson 2: Variables, assignment, and program state';
    $quizname = 'Knowledge check: Lesson 2: Variables, assignment, and program state';
    $intro = '<p>Give values meaningful names and trace assignment from the right to understand how execution changes program state.</p>';
    $body = '<div class="python-sample-lesson"><h2>Keep one source for one meaning</h2>'
        . '<p>At the end of Lesson 1, the morning count 18 was written directly in several expressions. Changing it to 20 required several edits, and a missed edit could make the outputs contradict each other. Give the value one name instead.</p>'
        . v13_code("capacity = 40\nmorning = 18\nafternoon = 12\n\nprint(\"Total:\", morning + afternoon)\nprint(\"Unused seats:\", capacity - (morning + afternoon))")
        . '<p>When <code>morning = 18</code> runs, Python first evaluates 18 on the right and makes the name <code>morning</code> refer to that value. This operation is assignment. Later expressions read <code>morning</code> instead of repeating 18. Changing the morning count now requires one edit, and both outputs use the same information.</p>'
        . '<h3>Assignment is not mathematical equality</h3>'
        . '<p>One <code>=</code> in Python does not assert that both sides are equal. It evaluates the right-hand side, then assigns the result to the name on the left. Two signs, <code>==</code>, ask whether values are equal.</p>'
        . v13_code("registered = 40\nprint(registered == 40)\nprint(registered == 35)")
        . '<p>The first line creates state; the other lines compare without changing it. They display <code>True</code> and <code>False</code>. Booleans and decisions are developed later, but distinguishing <code>=</code> from <code>==</code> matters now.</p>'
        . '<h3>Read the right side before updating the left</h3>'
        . '<p>A name can later be assigned another value. This is reassignment. Trace the value of <code>total</code> after every line.</p>'
        . v13_code("total = 12\namount = 5\ntotal = total + amount\nprint(total)")
        . '<p>On the third line, <code>total</code> on the right is still 12. Python adds 12 and 5 to make 17, then updates the name on the left. Read the line as “add the amount to the current total, then make that result the new total”, not as an algebraic equation.</p>'
        . '<p>The same update can be written <code>total += amount</code>. The shorter spelling still reads the current values before updating the left name. This course uses the long form until the state change is clear.</p>'
        . '<h3>A calculated value does not update itself</h3>'
        . '<p>Assignment connects a name to the result produced when that line runs. It is not a spreadsheet formula that recalculates automatically whenever an input changes.</p>'
        . v13_code("planned = 15\ncancelled = 2\ndelivered = planned - cancelled\n\ncancelled = 4\nprint(\"Cancelled:\", cancelled)\nprint(\"Delivered:\", delivered)")
        . '<p><code>delivered</code> became 13 on the third line. Changing <code>cancelled</code> to 4 does not change the already calculated value, so <code>delivered</code> remains 13. Run <code>delivered = planned - cancelled</code> again to update it to 11. Understanding which calculations must run after a state change is a foundation for reliable programs.</p>'
        . '<h3>A name follows rules and communicates meaning</h3>'
        . '<p>Names explain code to its readers. <code>completed_learners</code> communicates more than <code>x</code>, but an identifier cannot be arbitrary prose.</p>'
        . '<table class="generaltable"><thead><tr><th>Name</th><th>Valid?</th><th>Reason</th></tr></thead><tbody><tr><td><code>completed_learners</code></td><td>Yes</td><td>Letters and an underscore communicate meaning</td></tr><tr><td><code>group2</code></td><td>Yes</td><td>A digit is allowed after the first character</td></tr><tr><td><code>2nd_group</code></td><td>No</td><td>An identifier cannot start with a digit</td></tr><tr><td><code>completed learners</code></td><td>No</td><td>An identifier cannot contain a space</td></tr><tr><td><code>class</code></td><td>No</td><td>It is a Python keyword</td></tr></tbody></table>'
        . '<p>Names are case-sensitive, so <code>Score</code> and <code>score</code> are different. Ordinary variables normally use lower-case words and underscores. All capitals, as in <code>MAX_SEATS</code>, signal a convention that the value should not change; Python does not enforce that convention.</p>'
        . '<h3>Use <code>NameError</code> to inspect execution order</h3>'
        . '<p>Reading a name that has not yet been assigned, is misspelled, or differs in case normally raises <code>NameError</code>. Read the exception name and message on the final traceback line, then compare the names character by character.</p>'
        . v13_code("completed_learners = 29\nprint(completed_learner)")
        . '<p>The second name is missing its final <code>s</code>. In a Notebook, the same error can occur when the correct assignment cell is visible but has not run. Check both spelling and whether the required assignment executed first.</p>'
        . '<h3>Make Notebook state reproducible</h3>'
        . '<p>The kernel remembers names created by earlier cell execution. A Notebook may therefore work on your screen after cells were run out of order but fail for a reader who starts at the top. Before saving, restart the kernel and run every cell from the top. This proves that each required assignment appears before use and that the Notebook has no hidden state dependency.</p>'
        . '<h3>From guided example to transfer</h3>'
        . '<p>A learning centre planned 15 sessions and cancelled 2. Use <code>planned</code>, <code>cancelled</code>, and <code>delivered</code> to display all three. Predict state after every line, change <code>cancelled</code> to 4, and explain in Markdown which calculation must run again to update delivery correctly.</p>'
        . '<p>Then give meaningful names to room capacity 24, morning use 18, and afternoon use 20. Display unused places for both periods. Change the single capacity assignment to 22, restart the kernel, run all cells, and confirm that unused places become 4 and 2.</p>'
        . '<p>After this lesson, you can trace assignment from the right, explain state after reassignment, distinguish <code>=</code> from <code>==</code>, and diagnose <code>NameError</code> and Notebook execution order. The next lesson systematically develops the basic data types referred to by these names.</p>'
        . '<p><strong>Estimated study time:</strong> about 2 hours</p><p style="display:none">PYAI-V13-LESSON2-FLOW</p></div>';
    $questions = [
        v13_question('L2R-01', '<p>What is displayed?</p>' . v13_code("registered = 40\nprint(registered)"), [['40', 'Correct: the name refers to the assigned value.'], ['registered', 'print reads the value referred to by the name.'], ['True', 'No comparison occurs.'], ['Nothing', 'print runs.']], 0, 'Reading a name after assignment produces the value it currently refers to.'),
        v13_question('L2R-02', '<p>What is displayed, and what happens to <code>registered</code>?</p>' . v13_code("registered = 40\nprint(registered == 35)"), [['False; registered remains 40', 'Correct: comparison does not change state.'], ['False; registered becomes 35', '== does not assign.'], ['True; registered remains 40', '40 is not equal to 35.'], ['A syntax error', 'This is a valid comparison.']], 0, '<code>=</code> assigns; <code>==</code> compares without changing state.'),
        v13_question('L2R-03', '<p>What is displayed last?</p>' . v13_code("total = 12\namount = 5\ntotal = total + amount\nprint(total)"), [['5', 'That is amount alone.'], ['12', 'That is total before the update.'], ['17', 'Correct: evaluate 12 + 5, then update total.'], ['An error', 'total is already defined.']], 2, 'Reassignment evaluates the current right-hand values before updating the left.'),
        v13_question('L2R-04', '<p>What is displayed?</p>' . v13_code("planned = 15\ncancelled = 2\ndelivered = planned - cancelled\ncancelled = 4\nprint(delivered)"), [['11', 'delivered was not recalculated.'], ['13', 'Correct: calculated values do not update automatically.'], ['4', 'That is the current cancelled value.'], ['An error', 'All names are defined.']], 1, 'Assignment stores the result at execution time; changing an input does not recalculate an existing result.'),
        v13_question('L2R-05', '<p>What is displayed last?</p>' . v13_code("planned = 15\ncancelled = 2\ndelivered = planned - cancelled\ncancelled = 4\ndelivered = planned - cancelled\nprint(delivered)"), [['11', 'Correct: the expression ran again after the change.'], ['13', 'That was the old result.'], ['19', 'The expression subtracts.'], ['4', 'That is cancelled alone.']], 0, 'Re-executing the derived assignment after an input change restores consistent state.'),
        v13_question('L2R-06', '<p>What is displayed?</p>' . v13_code("total = 10\namount = 3\ntotal += amount\nprint(total)"), [['3', 'The result includes the existing total.'], ['10', 'That is before the update.'], ['13', 'Correct: this is shorthand for total = total + amount.'], ['103', 'These are numeric values.']], 2, '<code>+=</code> reads the current values and then updates the left name.'),
        v13_question('L2R-07', '<p>Which is both a valid and meaningful Python variable name?</p>', [['2nd_group', 'An identifier cannot start with a digit.'], ['class', 'This is a Python keyword.'], ['completed_learners', 'Correct: it follows the rules and communicates meaning.'], ['completed learners', 'An identifier cannot contain a space.']], 2, 'Choose an identifier that follows syntax rules and communicates the value’s meaning.'),
        v13_question('L2R-08', '<p>What happens first when this code runs from the top?</p>' . v13_code("print(completed)\ncompleted = 29"), [['29 is displayed', 'The assignment is on the later line.'], ['None is displayed', 'Python does not assign None automatically.'], ['NameError is raised', 'Correct: the name must be assigned before it is read.'], ['The word completed is displayed', 'The name is not quoted text.']], 2, 'A Notebook must execute the assignment cell before another cell reads the name.'),
        v13_question('L2R-09', '<p>What is the best check that a Notebook does not depend on hidden execution state?</p>', [['Rerun only the final cell', 'Old kernel state remains.'], ['Restart the kernel and run every cell from the top', 'Correct: this reproduces the work from a clean state.'], ['Edit the displayed output manually', 'That does not verify the code.'], ['Rename every variable to x', 'That does not fix dependencies.']], 1, 'Confirm that every cell succeeds in order from a fresh kernel.'),
        v13_question('L2R-10', '<p>What is displayed?</p>' . v13_code("MAX_SEATS = 40\nMAX_SEATS = 36\nprint(MAX_SEATS)"), [['40', 'The second line reassigns the name.'], ['36', 'Correct: capitals express a convention, not enforcement.'], ['MAX_SEATS', 'print reads the value.'], ['An error because reassignment is forbidden', 'Python does not enforce the constant convention.']], 1, 'All capitals communicate an intention not to change the value, but Python still permits reassignment.'),
    ];
}

$page = $DB->get_record('page', ['course' => $course->id, 'name' => $lessonname], '*', MUST_EXIST);
$page->intro = $intro;
$page->introformat = FORMAT_HTML;
$page->content = $body;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);

$quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
$attemptsremoved = (int) $DB->count_records('quiz_attempts', ['quiz' => $quiz->id]);
if ($attemptsremoved > 0) {
    quiz_delete_all_attempts($quiz);
}
$settings = \mod_quiz\quiz_settings::create($quiz->id);
$structure = \mod_quiz\structure::create_for_quiz($settings);
foreach (array_reverse($structure->get_slots()) as $slot) {
    $structure->remove_slot($slot->slot);
}
$quiz->intro = $language === 'ja'
    ? '<p>Lesson 2で学んだ代入と状態を、短いコードを一行ずつ追いながら確認します。何度でも挑戦でき、最高点が記録されます。</p>'
    : '<p>Check Lesson 2 assignment and state by tracing short code one line at a time. Attempts are unlimited and the highest score is retained.</p>';
$quiz->introformat = FORMAT_HTML;
$quiz->attempts = 0;
$quiz->grademethod = QUIZ_GRADEHIGHEST;
$quiz->timemodified = time();
$DB->update_record('quiz', $quiz);

$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
if (!$category) {
    $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
    $category = reset($categories);
}
foreach ($questions as $data) {
    $saved = v13_save_question($category->id, $context->id, $shortname . ' v3: ', $data, $language);
    quiz_add_quiz_question($saved->id, $quiz, 0, 10);
}
$DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
rebuild_course_cache($course->id, true);

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $shortname,
    'lesson_marker' => 'PYAI-V13-LESSON2-FLOW',
    'questions' => count($questions),
    'attempts_removed' => $attemptsremoved,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
