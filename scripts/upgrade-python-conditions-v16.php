<?php
// Rewrite Chapter 1.5 as a complete, bilingual lesson while preserving activity IDs.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';

use core_question\local\bank\question_version_status;

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';

function v16_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v16_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v16_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
        'generalfeedback' => ['text' => '<p><strong>' . ($language === 'ja' ? '学習ポイント：' : 'Learning point:') . '</strong> ' . s($data['explanation']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 10,
        'penalty' => 0.3333333,
        'status' => question_version_status::QUESTION_STATUS_READY,
        'idnumber' => null,
        'single' => 1,
        'shuffleanswers' => 1,
        'answernumbering' => 'abc',
        'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => $language === 'ja'
            ? '<p>正解です。条件を上から追い、実行された分岐を説明してから次へ進みましょう。</p>'
            : '<p>Correct. Trace the conditions from the top and explain which branch ran.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>各比較をTrueまたはFalseに分け、最初に実行される処理をNotebookで確認して再挑戦しましょう。</p>'
            : '<p>Reduce each comparison to True or False, verify the first executed action in the Notebook, and try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

if ($language === 'ja') {
    $topicname = '1.5 条件による判断';
    $topicsummary = '<p>比較が作る真偽値を読み、業務ルールを順序のある分岐へ変換し、境界値で正しさを確かめます。</p>';
    $pagename = 'レッスン1.5：条件による判断';
    $ltiname = 'Python Lab 1.5：条件と境界値';
    $quizname = '理解度チェック：1.5 条件による判断';
    $pageintro = '<p>比較式、分岐の順序、複合条件、境界値を使って、意図した処理を一つ選びます。</p>';
    $quizintro = '<p>短いコードを上から追い、真偽値、分岐の順序、境界値、妥当性確認を確かめます。何度でも挑戦でき、最高点が記録されます。</p>';
    $body = '<div class="python-sample-lesson"><h2>値を計算したら、次はその値に応じて処理を選ぶ</h2>'
        . '<p>ここまでに、プログラムは値を作り、変数へ保存し、文字列や数値へ変換して、人に伝わる形で表示できるようになりました。しかし実務では、すべての値を同じように扱うわけではありません。修了率が低いセンターには支援を提案し、順調なセンターには通常の報告を作る、といった選択が必要です。Pythonでは、この選択を<code>if</code>文で表します。</p>'
        . '<h3>条件式はTrueまたはFalseという値を作る</h3>'
        . '<p><code>score &gt;= 50</code>のような比較式を実行すると、結果は真偽値<code>bool</code>の<code>True</code>または<code>False</code>になります。<code>=</code>は右側の値を名前へ代入し、<code>==</code>は二つの値が等しいかを比較します。この違いは小さく見えますが、条件のバグを避けるための基本です。</p>'
        . v16_code("score = 68\nprint(score >= 50)\nprint(score == 68)\nprint(score != 68)\nprint(type(score >= 50))")
        . '<table class="generaltable"><thead><tr><th>演算子</th><th>判定</th></tr></thead><tbody><tr><td><code>==</code> / <code>!=</code></td><td>等しい / 等しくない</td></tr><tr><td><code>&lt;</code> / <code>&lt;=</code></td><td>より小さい / 以下</td></tr><tr><td><code>&gt;</code> / <code>&gt;=</code></td><td>より大きい / 以上</td></tr></tbody></table>'
        . '<h3>if文は条件がTrueのときだけ字下げされた処理を実行する</h3>'
        . '<p><code>if 条件式:</code>の末尾にはコロンが必要です。次の行を字下げすると、その行がif文に属することを表します。Pythonの字下げは見た目だけではなく、プログラムの構造そのものです。</p>'
        . v16_code("score = 68\nif score >= 50:\n    print(\"合格\")\nprint(\"判定完了\")")
        . '<p>68では条件がTrueなので二行とも表示されます。42なら<code>合格</code>は表示されませんが、字下げされていない<code>判定完了</code>は表示されます。どの行が条件の内側にあるかを意識して読みます。</p>'
        . '<h3>elseを加えると、二つの処理から必ず一つを選べる</h3>'
        . '<p><code>else</code>は直前の条件がFalseだった場合を受け持ちます。一回の判定でif側とelse側の両方が実行されることはありません。</p>'
        . v16_code("score = 42\nif score >= 50:\n    result = \"合格\"\nelse:\n    result = \"要復習\"\nprint(result)")
        . '<h3>elifは複数の候補を上から調べ、最初の一つだけを選ぶ</h3>'
        . '<p>結果が三つ以上なら<code>elif</code>を使います。Pythonは上から条件を調べ、最初にTrueとなった分岐を実行すると残りを調べません。そのため、条件が重なる場合は、より限定的な条件を先に置きます。</p>'
        . v16_code("score = 85\nif score >= 90:\n    grade = \"優秀\"\nelif score >= 70:\n    grade = \"合格\"\nelif score >= 50:\n    grade = \"条件付き合格\"\nelse:\n    grade = \"要復習\"\nprint(grade)")
        . '<p>もし<code>score &gt;= 50</code>を最初に置くと、85もそこで一致し、上位の区分へ到達できません。エラーにならずに動くコードでも、業務ルールを誤って実装することがあります。</p>'
        . '<h3>独立したif文は、条件がTrueならそれぞれ実行する</h3>'
        . '<p>二つの独立した<code>if</code>は、両方がTrueなら両方を実行します。一方、<code>if / elif / else</code>は候補から一つだけを選びます。「合格なら通知し、上位成績なら追加の表彰もする」なら独立したif文が適します。「成績区分を一つ決める」なら一連の分岐が適します。</p>'
        . v16_code("score = 80\nif score >= 50:\n    print(\"合格通知\")\nif score >= 70:\n    print(\"上位成績の表彰\")")
        . '<h3>and・or・notで複数の判定を組み合わせる</h3>'
        . '<p><code>and</code>は両方がTrue、<code>or</code>は少なくとも一方がTrueのときTrueになります。<code>not</code>は真偽を反転します。長い条件を一行へ詰め込まず、説明的な名前へ分けると、途中結果を表示して確認できます。</p>'
        . v16_code("registered = 35\ncompletion_rate = 72\nhas_enough_learners = registered >= 30\nrate_needs_support = completion_rate < 75\npriority = has_enough_learners and rate_needs_support\nprint(has_enough_learners, rate_needs_support, priority)")
        . '<p>比較演算は<code>not</code>より先に評価され、次に<code>and</code>、最後に<code>or</code>が評価されます。ただし、優先順位を暗記して読ませるより、<code>(registered &gt;= 30) and (completion_rate &lt; 75)</code>のように括弧と名前で意図を示す方が安全です。</p>'
        . '<h3>境界値は、直前・一致・直後を実行する</h3>'
        . '<p><code>&gt;= 50</code>は50を含み、<code>&gt; 50</code>は含みません。しきい値75と85で区分するなら、74.9、75、84.9、85のように各境界の両側を試します。Pythonでは<code>0 &lt;= score &lt;= 100</code>のように、範囲を連鎖比較として書けます。</p>'
        . v16_code("rate = 75\nif rate < 75:\n    status = \"重点支援\"\nelif rate < 85:\n    status = \"経過観察\"\nelse:\n    status = \"順調\"\nprint(status)")
        . '<h3>通常の分類より先に、入力値が妥当か確認する</h3>'
        . '<p>点数-5を単に「要復習」へ分類すると、コードは動いても意味は誤っています。まず許される範囲を確認し、その後で通常の分類を行います。</p>'
        . v16_code("score = -5\nif not 0 <= score <= 100:\n    result = \"無効な点数\"\nelif score >= 50:\n    result = \"合格\"\nelse:\n    result = \"要復習\"\nprint(result)")
        . '<h3>短絡評価は、結果が決まった後の式を評価しない</h3>'
        . '<p><code>and</code>は左側がFalseなら右側を評価せず、<code>or</code>は左側がTrueなら右側を評価しません。次の例では登録者数が0なので割り算を実行せず、ゼロ除算を避けます。ただし、条件式の右側に更新処理などの副作用を隠すと読みにくくなるため、判定は判定として書きます。</p>'
        . v16_code("registered = 0\ncompleted = 0\nif registered > 0 and completed / registered >= 0.75:\n    status = \"順調\"\nelse:\n    status = \"確認が必要\"\nprint(status)")
        . '<h3>例題から応用へ</h3>'
        . '<p>学習センターの登録者数、修了者数、欠席報告の有無から支援状態を決めます。人数が負、または修了者数が登録者数を超える場合は「データ確認」。欠席報告がある、または修了率が75%未満なら「重点支援」。85%未満なら「経過観察」。それ以外は「順調」とします。無効値と、74.9%、75%、84.9%、85%に相当するデータを試し、すべての分岐へ到達することを確認してください。</p>'
        . '<p>このレッスンを終えると、比較演算子とbool、字下げ、if/elif/elseの順序、独立したif文、and/or/not、境界値、妥当性確認、短絡評価を説明できます。次は、同じ処理を複数の値へ繰り返すループへ進みます。</p>'
        . '<p><strong>学習時間の目安：</strong>約3時間</p><p style="display:none">PYAI-V16-CONDITIONS-FLOW</p></div>';
    $questions = [
        v16_question('L15R-01', '<p>何が表示されますか。</p>' . v16_code('score = 68\nprint(score == 68, score != 68)'), [['True False', '正解です。等しいはTrue、等しくないはFalseです。'], ['68 68', '比較式は元の数値ではなくboolを返します。'], ['False True', '二つの比較が逆です。'], ['SyntaxError', 'どちらも有効な比較演算子です。']], 0, '=は代入、==と!=は比較であり、比較結果はboolです。'),
        v16_question('L15R-02', '<p>何が表示されますか。</p>' . v16_code('score = 42\nif score >= 50:\n    print("合格")\nprint("判定完了")'), [['合格だけ', '条件はFalseです。'], ['判定完了だけ', '正解です。字下げされていない行は実行します。'], ['両方', 'if内は実行しません。'], ['何も表示しない', '最後の行は条件の外側です。']], 1, '字下げはif文に属する処理の範囲を示します。'),
        v16_question('L15R-03', '<p>何が表示されますか。</p>' . v16_code('registered = 0\nif registered > 0:\n    message = "受付中"\nelse:\n    message = "受付なし"\nprint(message)'), [['受付中', '0 > 0はFalseです。'], ['受付なし', '正解です。else側を実行します。'], ['両方', 'if/elseは一方だけです。'], ['NameError', 'どちらの分岐でもmessageへ代入します。']], 1, 'if/elseは条件の真偽に応じて二つの処理から一つを選びます。'),
        v16_question('L15R-04', '<p><code>score = 85</code>で何が表示されますか。</p>' . v16_code('if score >= 50:\n    grade = "合格"\nelif score >= 80:\n    grade = "上位合格"\nelse:\n    grade = "要復習"\nprint(grade)'), [['上位合格', '最初の条件が先にTrueになります。'], ['合格', '正解です。広い条件が先に一致する順序上のバグです。'], ['要復習', '最初の条件はTrueです。'], ['合格と上位合格', '一連の分岐は最初の一つだけです。']], 1, '範囲が重なる分岐では、より限定的な条件を先に置きます。'),
        v16_question('L15R-05', '<p><code>score = 80</code>で何行表示されますか。</p>' . v16_code('if score >= 50:\n    print("合格")\nif score >= 70:\n    print("上位成績")'), [['0行', '両方の条件がTrueです。'], ['1行', '独立した二つのif文です。'], ['2行', '正解です。両方を実行します。'], ['構文エラー', '有効なコードです。']], 2, '独立したif文と、一つだけ選ぶif/elifの連鎖を区別します。'),
        v16_question('L15R-06', '<p>何が表示されますか。</p>' . v16_code('registered = 35\nrate = 82\npriority = registered >= 30 and (rate < 75 or not rate < 85)\nprint(priority)'), [['True', 'rate < 75もnot rate < 85もFalseです。'], ['False', '正解です。右側の括弧全体がFalseです。'], ['82', '論理式はboolを返します。'], ['SyntaxError', '有効な複合条件です。']], 1, '複合条件は括弧内と各比較を小さな真偽値へ分けて追います。'),
        v16_question('L15R-07', '<p>各値の状態として正しい組み合わせはどれですか。</p>' . v16_code('if rate < 75:\n    status = "支援"\nelif rate < 85:\n    status = "観察"\nelse:\n    status = "順調"'), [['74.9=支援、75=観察、84.9=観察、85=順調', '正解です。各境界の直前と境界上を確認しています。'], ['74.9=観察、75=観察、84.9=順調、85=順調', '<の境界を確認してください。'], ['74.9=支援、75=支援、84.9=観察、85=観察', '75と85は前の範囲に含まれません。'], ['すべて観察', '三つの範囲があります。']], 0, '内部境界では直前と一致する値を試し、<と<=の違いを確認します。'),
        v16_question('L15R-08', '<p><code>score = -5</code>で何が表示されますか。</p>' . v16_code('if not 0 <= score <= 100:\n    result = "無効"\nelif score >= 50:\n    result = "合格"\nelse:\n    result = "要復習"\nprint(result)'), [['無効', '正解です。通常分類より先に範囲外を処理します。'], ['合格', '負の点数です。'], ['要復習', '妥当性確認が先です。'], ['何も表示しない', '最初の分岐でresultへ代入します。']], 0, '無効値を通常の業務区分へ混ぜず、先に許容範囲を検証します。'),
        v16_question('L15R-09', '<p>このコードがZeroDivisionErrorにならない理由は何ですか。</p>' . v16_code('registered = 0\ncompleted = 0\nvalid = registered > 0 and completed / registered >= 0.75\nprint(valid)'), [['0 / 0が0になるから', 'Pythonの0除算はエラーです。'], ['andは左側がFalseなら右側を評価しないから', '正解です。短絡評価です。'], ['比較式が例外を無視するから', '例外を無視する仕組みではありません。'], ['printがエラーを消すから', 'printは関係ありません。']], 1, 'andとorの短絡評価では、結果が確定した後の式を評価しません。'),
        v16_question('L15R-10', '<p>何が表示されますか。</p>' . v16_code('registered = 40\ncompleted = 32\nabsence_report = False\nif registered < 0 or completed < 0 or completed > registered:\n    status = "データ確認"\nelif absence_report or completed / registered < 0.75:\n    status = "重点支援"\nelif completed / registered < 0.85:\n    status = "経過観察"\nelse:\n    status = "順調"\nprint(status)'), [['データ確認', '人数の関係は妥当です。'], ['重点支援', '欠席報告はなく、修了率は80%です。'], ['経過観察', '正解です。80%は75%以上85%未満です。'], ['順調', '85%には達していません。']], 2, '実務ルールは妥当性確認、優先対応、通常分類の順に並べます。'),
    ];
} else {
    $topicname = '1.5 Decisions with conditions';
    $topicsummary = '<p>Read Boolean comparisons, translate operational rules into ordered branches, and verify them at boundaries.</p>';
    $pagename = 'Lesson 1.5: Decisions with conditions';
    $ltiname = 'Python Lab 1.5: Conditions and boundaries';
    $quizname = 'Knowledge check: 1.5 Decisions with conditions';
    $pageintro = '<p>Use comparisons, branch order, compound conditions, and boundary tests to select the intended action.</p>';
    $quizintro = '<p>Trace short programs from the top and check Boolean values, branch order, boundaries, and validation. Attempts are unlimited and the highest score is retained.</p>';
    $body = '<div class="python-sample-lesson"><h2>After calculating a value, choose what the program should do with it</h2>'
        . '<p>You can now create values, store them in variables, convert text and numbers, and display a meaningful result. Operational work also requires choices. A centre with a low completion rate may need support, while a centre on track receives an ordinary report. Python represents this choice with an <code>if</code> statement.</p>'
        . '<h3>A condition produces the Boolean value True or False</h3>'
        . '<p>A comparison such as <code>score &gt;= 50</code> produces a value of type <code>bool</code>: either <code>True</code> or <code>False</code>. <code>=</code> assigns the value on its right to a name; <code>==</code> compares two values for equality. This small distinction prevents a common class of decision errors.</p>'
        . v16_code("score = 68\nprint(score >= 50)\nprint(score == 68)\nprint(score != 68)\nprint(type(score >= 50))")
        . '<table class="generaltable"><thead><tr><th>Operator</th><th>Decision</th></tr></thead><tbody><tr><td><code>==</code> / <code>!=</code></td><td>equal / not equal</td></tr><tr><td><code>&lt;</code> / <code>&lt;=</code></td><td>less than / at most</td></tr><tr><td><code>&gt;</code> / <code>&gt;=</code></td><td>greater than / at least</td></tr></tbody></table>'
        . '<h3>if runs its indented block only when the condition is True</h3>'
        . '<p>A colon ends <code>if condition:</code>. Indentation on the next line places that statement inside the if block. In Python, indentation is not decoration; it defines program structure.</p>'
        . v16_code("score = 68\nif score >= 50:\n    print(\"Pass\")\nprint(\"Decision complete\")")
        . '<p>For 68, both lines appear. For 42, <code>Pass</code> is skipped but the unindented <code>Decision complete</code> still runs. Read every statement in relation to its indentation.</p>'
        . '<h3>else selects exactly one of two actions</h3>'
        . '<p><code>else</code> handles the case in which the preceding condition is False. The if and else blocks cannot both run during one decision.</p>'
        . v16_code("score = 42\nif score >= 50:\n    result = \"Pass\"\nelse:\n    result = \"Review\"\nprint(result)")
        . '<h3>elif checks several candidates from the top and selects the first match</h3>'
        . '<p>Use <code>elif</code> for three or more possible results. Python tests conditions from the top and stops after the first True branch. When conditions overlap, put the more selective rule first.</p>'
        . v16_code("score = 85\nif score >= 90:\n    grade = \"Excellent\"\nelif score >= 70:\n    grade = \"Pass\"\nelif score >= 50:\n    grade = \"Conditional pass\"\nelse:\n    grade = \"Review\"\nprint(grade)")
        . '<p>If <code>score &gt;= 50</code> came first, 85 would match it and never reach a higher category. Code can run without an exception while still implementing the wrong operational rule.</p>'
        . '<h3>Independent if statements run independently</h3>'
        . '<p>Two separate <code>if</code> statements can both run. An <code>if / elif / else</code> chain chooses one candidate. Separate statements fit “send a pass notice and also award merit”; one chain fits “assign one grade category.”</p>'
        . v16_code("score = 80\nif score >= 50:\n    print(\"Send pass notice\")\nif score >= 70:\n    print(\"Award merit\")")
        . '<h3>and, or, and not combine decisions</h3>'
        . '<p><code>and</code> is True when both operands are True; <code>or</code> when at least one is True; <code>not</code> reverses a Boolean value. Split a long condition into meaningful names so that each intermediate result can be inspected.</p>'
        . v16_code("registered = 35\ncompletion_rate = 72\nhas_enough_learners = registered >= 30\nrate_needs_support = completion_rate < 75\npriority = has_enough_learners and rate_needs_support\nprint(has_enough_learners, rate_needs_support, priority)")
        . '<p>Comparisons bind before <code>not</code>, then <code>and</code>, then <code>or</code>. Rather than forcing a reader to recall precedence, use parentheses and named parts to make the intended grouping visible.</p>'
        . '<h3>Test immediately below, at, and above each boundary</h3>'
        . '<p><code>&gt;= 50</code> includes 50; <code>&gt; 50</code> does not. For thresholds at 75 and 85, try 74.9, 75, 84.9, and 85. Python also permits the range comparison <code>0 &lt;= score &lt;= 100</code>.</p>'
        . v16_code("rate = 75\nif rate < 75:\n    status = \"priority support\"\nelif rate < 85:\n    status = \"monitor\"\nelse:\n    status = \"on track\"\nprint(status)")
        . '<h3>Validate the value before applying ordinary categories</h3>'
        . '<p>Classifying a score of -5 as merely “Review” produces code that runs but a result that is wrong. Check the permitted domain first, then apply the ordinary classification.</p>'
        . v16_code("score = -5\nif not 0 <= score <= 100:\n    result = \"Invalid score\"\nelif score >= 50:\n    result = \"Pass\"\nelse:\n    result = \"Review\"\nprint(result)")
        . '<h3>Short-circuit evaluation skips an expression after the result is known</h3>'
        . '<p><code>and</code> skips its right operand when the left is False; <code>or</code> skips its right operand when the left is True. The following guard avoids division by zero because registration is zero. Use this for clear safety checks, but do not hide updates or other side effects inside a condition.</p>'
        . v16_code("registered = 0\ncompleted = 0\nif registered > 0 and completed / registered >= 0.75:\n    status = \"on track\"\nelse:\n    status = \"needs review\"\nprint(status)")
        . '<h3>From guided example to transfer</h3>'
        . '<p>Classify a learning centre from registered learners, completed learners, and whether an absence report exists. A negative count or completions above registrations means <code>data review</code>. An absence report or completion below 75% means <code>priority support</code>. Below 85% means <code>monitor</code>; otherwise it is <code>on track</code>. Test invalid data and cases corresponding to 74.9%, 75%, 84.9%, and 85%, reaching every branch.</p>'
        . '<p>After this lesson, you can explain comparisons and bool, indentation, ordered branches, independent if statements, and/or/not, boundaries, validation, and short-circuit evaluation. The next lesson applies the same processing repeatedly with loops.</p>'
        . '<p><strong>Estimated study time:</strong> about 3 hours</p><p style="display:none">PYAI-V16-CONDITIONS-FLOW</p></div>';
    $questions = [
        v16_question('L15R-01', '<p>What is displayed?</p>' . v16_code('score = 68\nprint(score == 68, score != 68)'), [['True False', 'Correct: equality is True and inequality is False.'], ['68 68', 'Comparisons return bool rather than the original number.'], ['False True', 'Both comparison results are reversed.'], ['SyntaxError', 'Both comparison operators are valid.']], 0, '= assigns; == and != compare, producing bool values.'),
        v16_question('L15R-02', '<p>What is displayed?</p>' . v16_code('score = 42\nif score >= 50:\n    print("Pass")\nprint("Decision complete")'), [['Pass only', 'The condition is False.'], ['Decision complete only', 'Correct: the unindented statement still runs.'], ['Both lines', 'The if block is skipped.'], ['Nothing', 'The final statement is outside the condition.']], 1, 'Indentation defines which statements belong to an if block.'),
        v16_question('L15R-03', '<p>What is displayed?</p>' . v16_code('registered = 0\nif registered > 0:\n    message = "Open"\nelse:\n    message = "No intake"\nprint(message)'), [['Open', '0 > 0 is False.'], ['No intake', 'Correct: the else branch runs.'], ['Both', 'if/else selects one branch.'], ['NameError', 'Both possible branches assign message.']], 1, 'if/else selects one of two actions from the Boolean result.'),
        v16_question('L15R-04', '<p>What is displayed for <code>score = 85</code>?</p>' . v16_code('if score >= 50:\n    grade = "Pass"\nelif score >= 80:\n    grade = "High pass"\nelse:\n    grade = "Review"\nprint(grade)'), [['High pass', 'The first condition already matches.'], ['Pass', 'Correct: the broad condition comes first, which is an ordering bug.'], ['Review', 'The first condition is True.'], ['Pass and High pass', 'The chain runs one branch.']], 1, 'Put a more selective overlapping range before a broader range.'),
        v16_question('L15R-05', '<p>How many lines are displayed for <code>score = 80</code>?</p>' . v16_code('if score >= 50:\n    print("Pass")\nif score >= 70:\n    print("Merit")'), [['Zero', 'Both conditions are True.'], ['One', 'These are independent if statements.'], ['Two', 'Correct: both statements run.'], ['Syntax error', 'The program is valid.']], 2, 'Distinguish independent if statements from an exclusive if/elif chain.'),
        v16_question('L15R-06', '<p>What is displayed?</p>' . v16_code('registered = 35\nrate = 82\npriority = registered >= 30 and (rate < 75 or not rate < 85)\nprint(priority)'), [['True', 'Both rate < 75 and not rate < 85 are False.'], ['False', 'Correct: the whole right-hand group is False.'], ['82', 'The logical expression returns bool.'], ['SyntaxError', 'This compound condition is valid.']], 1, 'Trace a compound condition by reducing each comparison and parenthesised group.'),
        v16_question('L15R-07', '<p>Which statuses are correct?</p>' . v16_code('if rate < 75:\n    status = "support"\nelif rate < 85:\n    status = "monitor"\nelse:\n    status = "on track"'), [['74.9=support, 75=monitor, 84.9=monitor, 85=on track', 'Correct: this checks immediately below and at both thresholds.'], ['74.9=monitor, 75=monitor, 84.9=on track, 85=on track', 'Recheck the less-than boundaries.'], ['74.9=support, 75=support, 84.9=monitor, 85=monitor', '75 and 85 are excluded from the preceding ranges.'], ['All are monitor', 'There are three distinct ranges.']], 0, 'At an internal threshold, test a value immediately below and exactly at the threshold.'),
        v16_question('L15R-08', '<p>What is displayed for <code>score = -5</code>?</p>' . v16_code('if not 0 <= score <= 100:\n    result = "Invalid"\nelif score >= 50:\n    result = "Pass"\nelse:\n    result = "Review"\nprint(result)'), [['Invalid', 'Correct: range validation precedes normal classification.'], ['Pass', 'The score is negative.'], ['Review', 'Validation occurs first.'], ['Nothing', 'The first branch assigns result.']], 0, 'Validate the permitted domain before ordinary operational categories.'),
        v16_question('L15R-09', '<p>Why does this code not raise ZeroDivisionError?</p>' . v16_code('registered = 0\ncompleted = 0\nvalid = registered > 0 and completed / registered >= 0.75\nprint(valid)'), [['0 / 0 equals zero', 'Division by zero would raise an error.'], ['and skips the right operand after a False left operand', 'Correct: this is short-circuit evaluation.'], ['Comparisons ignore exceptions', 'They do not suppress exceptions.'], ['print removes the error', 'print is unrelated.']], 1, 'Short-circuit and/or evaluation skips an operand after the result is already known.'),
        v16_question('L15R-10', '<p>What is displayed?</p>' . v16_code('registered = 40\ncompleted = 32\nabsence_report = False\nif registered < 0 or completed < 0 or completed > registered:\n    status = "data review"\nelif absence_report or completed / registered < 0.75:\n    status = "priority support"\nelif completed / registered < 0.85:\n    status = "monitor"\nelse:\n    status = "on track"\nprint(status)'), [['data review', 'The counts have a valid relationship.'], ['priority support', 'There is no absence report and completion is 80%.'], ['monitor', 'Correct: 80% is at least 75% and below 85%.'], ['on track', 'Completion has not reached 85%.']], 2, 'Order practical rules as validation, priority handling, then ordinary classification.'),
    ];
}

$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
$delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $delegated, ['name' => $topicname, 'summary' => $topicsummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
$page->intro = $pageintro;
$page->introformat = FORMAT_HTML;
$page->content = $body;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);

$lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
$expectedpath = $language === 'ja' ? '/ja/03_conditions_boundaries.ipynb' : '/03_conditions_boundaries.ipynb';
$newurl = preg_replace('~/(?:ja/)?03_conditions_boundaries\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || $newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath)) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $language === 'ja'
    ? '<p>条件を予測し、実行し、境界値を変えて結果を説明します。</p>'
    : '<p>Predict conditions, run them, change boundary values, and explain the result.</p>';
$lti->introformat = FORMAT_HTML;
$lti->timemodified = time();
$DB->update_record('lti', $lti);

$quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
$attemptsremoved = (int) $DB->count_records('quiz_attempts', ['quiz' => $quiz->id]);
if ($attemptsremoved > 0) {
    quiz_delete_all_attempts($quiz);
}
$structure = \mod_quiz\structure::create_for_quiz(\mod_quiz\quiz_settings::create($quiz->id));
foreach (array_reverse($structure->get_slots()) as $slot) {
    $structure->remove_slot($slot->slot);
}
$quiz->intro = $quizintro;
$quiz->introformat = FORMAT_HTML;
$quiz->attempts = 0;
$quiz->grademethod = QUIZ_GRADEHIGHEST;
$quiz->grade = 100;
$quiz->questionsperpage = 10;
$quiz->timemodified = time();
$DB->update_record('quiz', $quiz);

$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
if (!$category) {
    $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
    $category = reset($categories);
}
foreach ($questions as $question) {
    $saved = v16_save_question($category->id, $context->id, $shortname . ' v16: ', $question, $language);
    quiz_add_quiz_question($saved->id, $quiz, 0, 10);
}
$DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();

$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
$quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
$delegated->sequence = implode(',', [$pagecm->id, $lticm->id, $quizcm->id]);
$DB->update_record('course_sections', $delegated);
foreach ([$pagecm->id, $lticm->id, $quizcm->id] as $cmid) {
    $DB->set_field('course_modules', 'section', $delegated->id, ['id' => $cmid]);
}
rebuild_course_cache($course->id, true);

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $shortname,
    'topic' => $topicname,
    'pageid' => (int) $page->id,
    'quizid' => (int) $quiz->id,
    'ltiid' => (int) $lti->id,
    'questions' => count($questions),
    'attempts_removed' => $attemptsremoved,
    'lti_path' => $expectedpath,
    'marker' => 'PYAI-V16-CONDITIONS-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
