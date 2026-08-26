<?php
// Rewrite Chapter 1.6 as a complete, bilingual loops lesson while preserving activity IDs.
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

function v17_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v17_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v17_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
            ? '<p>正解です。各反復の開始時と終了時の変数を説明してから次へ進みましょう。</p>'
            : '<p>Correct. Explain the variables at the start and end of each iteration.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>反復ごとのループ変数と更新後の状態を書き出し、Notebookで確認して再挑戦しましょう。</p>'
            : '<p>Write down the loop variable and updated state for each iteration, verify them in the Notebook, and try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

if ($language === 'ja') {
    $topicname = '1.6 ループによる繰り返し';
    $topicsummary = '<p>複数の値へ同じ処理を適用し、合計・件数・最大値などの状態を安全に更新します。</p>';
    $pagename = 'レッスン1.6：ループによる繰り返し';
    $ltiname = 'Python Lab 1.6：ループと累積処理';
    $quizname = '理解度チェック：1.6 ループによる繰り返し';
    $pageintro = '<p>forとwhileを使い、反復回数、更新される状態、終了条件を追跡します。</p>';
    $quizintro = '<p>短いループを一回ずつ追い、range、合計、件数、最大値、制御、終了条件を確かめます。何度でも挑戦でき、最高点が記録されます。</p>';
    $body = '<div class="python-sample-lesson"><h2>同じ形の処理を、値の数だけ正確に繰り返す</h2>'
        . '<p>条件分岐によって、一つの値に対する処理を選べるようになりました。次に必要なのは、複数の週、複数の受講者、複数のセンターへ同じ処理を適用することです。処理を手作業でコピーすると、値の追加や修正時に一部だけ直し忘れます。ループは、繰り返す処理を一度だけ記述し、対象となる値を順に渡します。</p>'
        . '<h3>for文は、値の並びから一つずつ取り出す</h3>'
        . '<p><code>for attendance in weekly_attendance:</code>では、リストの各値が順に<code>attendance</code>へ入り、字下げされた処理を実行します。ループ変数は現在処理している値を表す一時的な名前です。ループ外の行は、すべての反復が終わってから一度だけ実行されます。</p>'
        . v17_code("weekly_attendance = [28, 31, 32, 34]\nfor attendance in weekly_attendance:\n    print(\"今週の出席者数:\", attendance)\nprint(\"処理完了\")")
        . '<h3>range()は開始を含み、終了を含まない</h3>'
        . '<p><code>range(start, stop, step)</code>は整数の並びを作ります。<code>range(1, 5)</code>は1、2、3、4で、5を含みません。<code>range(4)</code>は0から3です。期待する最初の値、最後の値、反復回数を先に書くと、一回多い・一回少ないという境界エラーを見つけやすくなります。</p>'
        . v17_code("print(list(range(1, 5)))\nfor week in range(1, 5):\n    print(f\"第{week}週\")\nprint(list(range(10, 4, -2)))")
        . '<h3>アキュムレータはループの外で初期化し、内側で更新する</h3>'
        . '<p>合計のように反復をまたいで残す値をアキュムレータと呼びます。合計は通常0から始め、各反復で現在の値を加えます。初期化をループ内へ置くと毎回0へ戻り、最後の値しか残りません。</p>'
        . v17_code("weekly_attendance = [28, 31, 32, 34]\ntotal = 0\nfor attendance in weekly_attendance:\n    total += attendance\n    print(\"途中の合計:\", total)\nprint(\"合計:\", total)\nprint(\"平均:\", total / len(weekly_attendance))")
        . '<p><code>sum()</code>で合計を求めることもできますが、明示的なループを追跡すると、後で条件付き集計や複数の状態を同時に更新する仕組みが理解できます。目的が単純な合計だけなら、実務では読みやすい<code>sum()</code>を選べます。</p>'
        . '<h3>カウンタは、条件に一致したときだけ1増やす</h3>'
        . '<p>件数を数える変数も0から始めます。合計とカウンタは似ていますが、合計は値を加え、カウンタは一件につき1を加えます。</p>'
        . v17_code("weeks_at_least_30 = 0\nfor attendance in [28, 31, 32, 34]:\n    if attendance >= 30:\n        weeks_at_least_30 += 1\nprint(weeks_at_least_30)")
        . '<h3>最大値は実データから初期化する</h3>'
        . '<p>最大値を0から始めると、すべての値が負の場合に、データに存在しない0を最大値として返します。空でないことが分かっているなら最初の要素から始めます。空データの可能性があるなら、先に分岐して「データなし」を明示します。</p>'
        . v17_code("weekly_change = [-4, -2, -7, -3]\nif weekly_change:\n    largest = weekly_change[0]\n    for change in weekly_change[1:]:\n        if change > largest:\n            largest = change\n    print(\"最大値:\", largest)\nelse:\n    print(\"データなし\")")
        . '<h3>enumerate()は番号と値を同時に取り出す</h3>'
        . '<p>人が読む週番号と値の両方が必要なら、手作業でカウンタを更新するより<code>enumerate()</code>が適します。<code>start=1</code>を指定すると、第1週から始められます。</p>'
        . v17_code("weekly_attendance = [28, 31, 32, 34]\nfor week, attendance in enumerate(weekly_attendance, start=1):\n    print(f\"第{week}週: {attendance}人\")")
        . '<h3>continueは今回の残りを飛ばし、breakはループ全体を止める</h3>'
        . '<p><code>continue</code>は現在の反復の残りを飛ばして次の値へ進みます。<code>break</code>は残りの値を処理せずにループを終了します。通常処理の途中へ多用すると流れが分かりにくくなるため、欠損値を飛ばす、重大な無効値で停止する、といった理由を明確にします。</p>'
        . v17_code("readings = [28, None, 31, -1, 34]\nfor reading in readings:\n    if reading is None:\n        continue\n    if reading < 0:\n        print(\"無効値を検出。停止します\")\n        break\n    print(\"有効値:\", reading)")
        . '<h3>while文は、条件がTrueである間繰り返す</h3>'
        . '<p>値の並びではなく、終了条件によって反復を決める場合は<code>while</code>を使います。条件に使う状態をループ内で終了へ近づけなければ、無限ループになります。実行が終わらない場合はNotebookの停止操作で中断し、条件と更新を確認します。</p>'
        . v17_code("remaining = 3\nwhile remaining > 0:\n    print(\"残り:\", remaining)\n    remaining -= 1\nprint(\"完了\")")
        . '<h3>処理対象が分かっているならfor、終了条件で続けるならwhile</h3>'
        . '<p>リストなどの反復可能な値を処理するならforが自然です。「成功するまで再試行する」「残数が0になるまで処理する」ならwhileが自然です。既知のリストを添字とwhileで処理すると、添字の初期化、境界、更新を自分で管理する必要があり、誤りが増えます。</p>'
        . '<h3>空データでは、ループは一度も実行されない</h3>'
        . '<p>空のリストに対するfor文は0回で正常終了します。合計は0のままですが、平均を<code>total / len(values)</code>で求めると0除算になります。ループ後に件数を確認し、平均が存在しないことを明示します。</p>'
        . v17_code("values = []\ntotal = 0\nfor value in values:\n    total += value\nif values:\n    print(total / len(values))\nelse:\n    print(\"データなし\")")
        . '<h3>例題から応用へ</h3>'
        . '<p>4週間の教材費<code>[82.5, 74.0, 91.5, 80.0]</code>を一つのfor文で処理し、合計、平均、最大値、80を超えた週の件数を求めます。<code>enumerate()</code>で各週も表示します。次に空のリストで実行し、割り算を行わず「データなし」と表示してください。「80を超える」なら80ちょうどを含まないことを、条件式と説明の両方で示します。</p>'
        . '<p>このレッスンを終えると、for、rangeの終端、アキュムレータ、カウンタ、最大値の初期化、enumerate、continue/break、whileの終了条件、空データを説明できます。これらを週間サポート報告のプロジェクトで統合します。</p>'
        . '<p><strong>学習時間の目安：</strong>約3時間</p><p style="display:none">PYAI-V17-LOOPS-FLOW</p></div>';
    $questions = [
        v17_question('L16R-01', '<p>何が表示されますか。</p>' . v17_code('for value in [2, 4, 6]:\n    print(value * 2)'), [['2、4、6', '各値を2倍します。'], ['4、8、12', '正解です。各要素を一回ずつ処理します。'], ['12だけ', 'printはループ内なので毎回実行します。'], ['無限に続く', '要素は三つです。']], 1, 'for文は反復可能な値を一つずつループ変数へ渡します。'),
        v17_question('L16R-02', '<p>何が表示されますか。</p>' . v17_code('print(list(range(2, 8, 2)))'), [['[2, 4, 6]', '正解です。8は終了値なので含みません。'], ['[2, 4, 6, 8]', 'stopは含みません。'], ['[2, 3, 4, 5, 6, 7]', 'stepは2です。'], ['[8, 6, 4, 2]', '正のstepなので増加します。']], 0, 'range(start, stop, step)はstartを含みstopを含みません。'),
        v17_question('L16R-03', '<p>何が表示されますか。</p>' . v17_code('total = 0\nfor value in [3, 5, 2]:\n    total += value\nprint(total)'), [['2', '最後の値だけではありません。'], ['8', '最初の二つだけではありません。'], ['10', '正解です。0→3→8→10と更新します。'], ['15', '値を重複して加えていません。']], 2, 'アキュムレータはループの外で初期化し、各反復の状態を引き継ぎます。'),
        v17_question('L16R-04', '<p>何が表示されますか。</p>' . v17_code('count = 0\nfor value in [9, 10, 11, 10]:\n    if value >= 10:\n        count += 1\nprint(count)'), [['2', '10ちょうども含みます。'], ['3', '正解です。10、11、10の三件です。'], ['4', '9は条件に一致しません。'], ['40', '値の合計ではなく件数です。']], 1, 'カウンタは条件に一致した要素ごとに1増やします。'),
        v17_question('L16R-05', '<p>この最大値計算の誤りは何ですか。</p>' . v17_code('values = [-4, -2, -7]\nlargest = 0\nfor value in values:\n    if value > largest:\n        largest = value\nprint(largest)'), [['ループが一回多い', '反復回数は正しいです。'], ['存在しない0を最大値として表示する', '正解です。実データから初期化します。'], ['-7を表示する', '0から更新されません。'], ['構文エラーになる', '構文は有効です。']], 1, '最大・最小はデータの符号を仮定せず、実データまたは明示的な空状態から始めます。'),
        v17_question('L16R-06', '<p>最初に表示される行はどれですか。</p>' . v17_code('for week, value in enumerate([28, 31], start=1):\n    print(week, value)'), [['0 28', 'start=1を指定しています。'], ['1 28', '正解です。位置は1から始まります。'], ['1 31', '最初の値は28です。'], ['28 1', 'enumerateは位置、値の順です。']], 1, 'enumerateは位置と値を組として返し、startで開始番号を指定できます。'),
        v17_question('L16R-07', '<p>表示される有効値はどれですか。</p>' . v17_code('for value in [2, None, 4, -1, 6]:\n    if value is None:\n        continue\n    if value < 0:\n        break\n    print(value)'), [['2だけ', '4も負の値より前です。'], ['2と4', '正解です。Noneを飛ばし、-1で終了します。'], ['2、4、6', '-1でbreakするため6へ進みません。'], ['Noneと-1', 'どちらもprintへ到達しません。']], 1, 'continueは今回だけを飛ばし、breakは残りの反復すべてを停止します。'),
        v17_question('L16R-08', '<p>このwhile文の問題は何ですか。</p>' . v17_code('remaining = 3\nwhile remaining > 0:\n    print(remaining)'), [['一度も実行しない', '3 > 0はTrueです。'], ['remainingを更新しないため繰り返しが終了しない', '正解です。条件がFalseへ近づきません。'], ['3、2、1と表示して終了する', '減算処理がありません。'], ['whileでは数値を使えない', '数値比較を条件にできます。']], 1, 'whileでは条件に使う状態をループ内で終了へ向けて更新します。'),
        v17_question('L16R-09', '<p>4週間の既知のリストを一回ずつ処理する最も自然な構造はどれですか。</p>', [['for value in weekly_values', '正解です。値の並びが既知です。'], ['while Trueだけ', '明示的な停止処理が必要になります。'], ['四つのprintを手作業で書く', '追加や修正に弱くなります。'], ['再帰関数だけ', 'この処理には不要です。']], 0, '既知の反復可能オブジェクトにはfor、条件変化まで続ける処理にはwhileを選びます。'),
        v17_question('L16R-10', '<p>最終的に表示される組はどれですか。</p>' . v17_code('costs = [82.5, 74.0, 91.5, 80.0]\ntotal = 0\nmaximum = costs[0]\nabove_80 = 0\nfor cost in costs:\n    total += cost\n    if cost > maximum:\n        maximum = cost\n    if cost > 80:\n        above_80 += 1\nprint(total, maximum, above_80)'), [['328.0、91.5、2', '正解です。80ちょうどは>80に含みません。'], ['328.0、91.5、3', '80ちょうどを数えていません。'], ['91.5、328.0、2', '合計と最大値が逆です。'], ['328.0、82.5、2', '91.5で最大値を更新します。']], 0, '一つのループで役割の異なる状態を更新でき、境界条件は件数へ直接影響します。'),
    ];
} else {
    $topicname = '1.6 Repetition with loops';
    $topicsummary = '<p>Apply one operation to several values and safely update state such as totals, counts, and maxima.</p>';
    $pagename = 'Lesson 1.6: Repetition with loops';
    $ltiname = 'Python Lab 1.6: Loops and accumulators';
    $quizname = 'Knowledge check: 1.6 Repetition with loops';
    $pageintro = '<p>Use for and while while tracing iteration counts, updated state, and stopping conditions.</p>';
    $quizintro = '<p>Trace short loops one iteration at a time and check range, totals, counts, maxima, control flow, and termination. Attempts are unlimited and the highest score is retained.</p>';
    $body = '<div class="python-sample-lesson"><h2>Repeat one pattern accurately for every value</h2>'
        . '<p>Conditions let a program select work for one value. Operational data contains several weeks, learners, or centres requiring the same pattern. Copying the code by hand makes additions and corrections fragile. A loop states the repeated operation once and supplies each target value in turn.</p>'
        . '<h3>for takes one value at a time from an iterable</h3>'
        . '<p>In <code>for attendance in weekly_attendance:</code>, each list item is assigned to <code>attendance</code> and the indented block runs. The loop variable is a temporary name for the current value. An unindented statement runs once after all iterations.</p>'
        . v17_code("weekly_attendance = [28, 31, 32, 34]\nfor attendance in weekly_attendance:\n    print(\"Attendance this week:\", attendance)\nprint(\"Processing complete\")")
        . '<h3>range() includes its start and excludes its stop</h3>'
        . '<p><code>range(start, stop, step)</code> produces integers. <code>range(1, 5)</code> gives 1, 2, 3, 4; five is excluded. <code>range(4)</code> gives zero through three. State the expected first value, last value, and iteration count before running to expose an off-by-one error.</p>'
        . v17_code("print(list(range(1, 5)))\nfor week in range(1, 5):\n    print(f\"Week {week}\")\nprint(list(range(10, 4, -2)))")
        . '<h3>Initialise an accumulator before the loop and update it inside</h3>'
        . '<p>An accumulator preserves state across iterations. A total usually starts at zero and receives the current value each time. Initialising it inside the loop resets it on every iteration and leaves only the final item.</p>'
        . v17_code("weekly_attendance = [28, 31, 32, 34]\ntotal = 0\nfor attendance in weekly_attendance:\n    total += attendance\n    print(\"Running total:\", total)\nprint(\"Total:\", total)\nprint(\"Mean:\", total / len(weekly_attendance))")
        . '<p><code>sum()</code> can calculate a simple total. Tracing an explicit loop first reveals how conditional aggregation and several simultaneous results work. In production code, prefer the readable built-in when a plain total is the whole task.</p>'
        . '<h3>A counter increases by one only for a matching item</h3>'
        . '<p>A counter also starts at zero. A total adds a value; a counter adds one item. Separate names keep these two roles clear.</p>'
        . v17_code("weeks_at_least_30 = 0\nfor attendance in [28, 31, 32, 34]:\n    if attendance >= 30:\n        weeks_at_least_30 += 1\nprint(weeks_at_least_30)")
        . '<h3>Initialise a maximum from real data</h3>'
        . '<p>Starting a maximum at zero fails when every observation is negative, returning a value that was not present. For known non-empty data, start with the first item. If empty data is possible, handle it explicitly before selecting the first item.</p>'
        . v17_code("weekly_change = [-4, -2, -7, -3]\nif weekly_change:\n    largest = weekly_change[0]\n    for change in weekly_change[1:]:\n        if change > largest:\n            largest = change\n    print(\"Largest:\", largest)\nelse:\n    print(\"No data\")")
        . '<h3>enumerate() supplies a number and value together</h3>'
        . '<p>When output requires both a human-readable week number and its value, <code>enumerate()</code> is clearer than maintaining a manual counter. <code>start=1</code> begins at Week 1.</p>'
        . v17_code("weekly_attendance = [28, 31, 32, 34]\nfor week, attendance in enumerate(weekly_attendance, start=1):\n    print(f\"Week {week}: {attendance} learners\")")
        . '<h3>continue skips this iteration; break ends the loop</h3>'
        . '<p><code>continue</code> skips the remaining work for the current value and advances. <code>break</code> ends the loop without processing later values. Use them for explicit reasons such as skipping a missing reading or stopping at a critical invalid value, rather than scattering jumps through ordinary work.</p>'
        . v17_code("readings = [28, None, 31, -1, 34]\nfor reading in readings:\n    if reading is None:\n        continue\n    if reading < 0:\n        print(\"Invalid value found; stopping\")\n        break\n    print(\"Valid value:\", reading)")
        . '<h3>while repeats while its condition remains True</h3>'
        . '<p>Use <code>while</code> when a stopping condition rather than a known collection controls repetition. The state used by the condition must change toward termination inside the loop, or the loop may never end. If a Notebook cell does not finish, interrupt it and inspect the condition and update.</p>'
        . v17_code("remaining = 3\nwhile remaining > 0:\n    print(\"Remaining:\", remaining)\n    remaining -= 1\nprint(\"Complete\")")
        . '<h3>Use for for known values and while for a changing stopping condition</h3>'
        . '<p>Use for to process an iterable such as a list. Use while for work such as retrying until success or processing until a remaining count reaches zero. Indexing a known list with while requires manual initialisation, boundary checks, and updates, creating more places for errors.</p>'
        . '<h3>An empty iterable runs a for loop zero times</h3>'
        . '<p>A for loop over an empty list ends normally without entering its body. The total remains zero, but <code>total / len(values)</code> would divide by zero. Check the count after the loop and explicitly report that a mean does not exist.</p>'
        . v17_code("values = []\ntotal = 0\nfor value in values:\n    total += value\nif values:\n    print(total / len(values))\nelse:\n    print(\"No data\")")
        . '<h3>From guided example to transfer</h3>'
        . '<p>Process four weekly material costs <code>[82.5, 74.0, 91.5, 80.0]</code> in one for loop. Calculate total, mean, maximum, and the count of weeks above 80. Display each week with <code>enumerate()</code>. Then run an empty list without dividing and display <code>No data</code>. “Above 80” excludes exactly 80, so the condition and explanation must agree.</p>'
        . '<p>After this lesson, you can explain for, the excluded range endpoint, accumulators, counters, maximum initialisation, enumerate, continue/break, while termination, and empty data. The applied project combines these foundations in a weekly support report.</p>'
        . '<p><strong>Estimated study time:</strong> about 3 hours</p><p style="display:none">PYAI-V17-LOOPS-FLOW</p></div>';
    $questions = [
        v17_question('L16R-01', '<p>What is displayed?</p>' . v17_code('for value in [2, 4, 6]:\n    print(value * 2)'), [['2, 4, 6', 'Each value is multiplied by two.'], ['4, 8, 12', 'Correct: every item is processed once.'], ['12 only', 'print is inside the loop and runs each time.'], ['It continues forever', 'There are three items.']], 1, 'A for loop assigns each iterable item to its loop variable in turn.'),
        v17_question('L16R-02', '<p>What is displayed?</p>' . v17_code('print(list(range(2, 8, 2)))'), [['[2, 4, 6]', 'Correct: stop value eight is excluded.'], ['[2, 4, 6, 8]', 'The stop value is excluded.'], ['[2, 3, 4, 5, 6, 7]', 'The step is two.'], ['[8, 6, 4, 2]', 'A positive step increases.']], 0, 'range(start, stop, step) includes start and excludes stop.'),
        v17_question('L16R-03', '<p>What is displayed?</p>' . v17_code('total = 0\nfor value in [3, 5, 2]:\n    total += value\nprint(total)'), [['2', 'The accumulator contains more than the final value.'], ['8', 'The third value is also added.'], ['10', 'Correct: the states are 0→3→8→10.'], ['15', 'No value is added twice.']], 2, 'Initialise an accumulator before the loop so each iteration inherits the previous state.'),
        v17_question('L16R-04', '<p>What is displayed?</p>' . v17_code('count = 0\nfor value in [9, 10, 11, 10]:\n    if value >= 10:\n        count += 1\nprint(count)'), [['2', 'Exactly ten is included.'], ['3', 'Correct: 10, 11, and 10 match.'], ['4', 'Nine does not match.'], ['40', 'This is a count, not a sum.']], 1, 'A counter increases by one for every item satisfying the condition.'),
        v17_question('L16R-05', '<p>What is wrong with this maximum calculation?</p>' . v17_code('values = [-4, -2, -7]\nlargest = 0\nfor value in values:\n    if value > largest:\n        largest = value\nprint(largest)'), [['The loop has one extra iteration', 'It visits each item once.'], ['It displays zero, which is not in the data', 'Correct: initialise from actual data.'], ['It displays -7', 'largest never changes from zero.'], ['It has a syntax error', 'The syntax is valid.']], 1, 'Do not assume the sign of maximum/minimum data; initialise from real data or an explicit empty state.'),
        v17_question('L16R-06', '<p>Which line is displayed first?</p>' . v17_code('for week, value in enumerate([28, 31], start=1):\n    print(week, value)'), [['0 28', 'start=1 was supplied.'], ['1 28', 'Correct: numbering begins at one.'], ['1 31', 'The first value is 28.'], ['28 1', 'enumerate returns position, then value.']], 1, 'enumerate yields a position and value, with an optional starting position.'),
        v17_question('L16R-07', '<p>Which valid values are displayed?</p>' . v17_code('for value in [2, None, 4, -1, 6]:\n    if value is None:\n        continue\n    if value < 0:\n        break\n    print(value)'), [['2 only', 'Four occurs before the negative value.'], ['2 and 4', 'Correct: None is skipped and -1 ends the loop.'], ['2, 4, and 6', 'break at -1 prevents reaching six.'], ['None and -1', 'Neither reaches print.']], 1, 'continue skips one iteration; break stops all remaining iterations.'),
        v17_question('L16R-08', '<p>What is wrong with this while loop?</p>' . v17_code('remaining = 3\nwhile remaining > 0:\n    print(remaining)'), [['It never runs', '3 > 0 is True.'], ['remaining never changes, so the loop does not terminate', 'Correct: the condition never moves toward False.'], ['It prints 3, 2, 1 and stops', 'There is no decrement.'], ['while cannot use numbers', 'Numeric comparisons are valid conditions.']], 1, 'A while loop must update the state governing its termination.'),
        v17_question('L16R-09', '<p>Which structure most naturally processes a known list of four weekly values once each?</p>', [['for value in weekly_values', 'Correct: the iterable is known.'], ['while True alone', 'It requires separate stopping logic.'], ['Four hand-written print calls', 'It is fragile when data changes.'], ['Recursion only', 'Recursion is unnecessary here.']], 0, 'Choose for for a known iterable and while for repetition controlled by a changing condition.'),
        v17_question('L16R-10', '<p>Which tuple is displayed?</p>' . v17_code('costs = [82.5, 74.0, 91.5, 80.0]\ntotal = 0\nmaximum = costs[0]\nabove_80 = 0\nfor cost in costs:\n    total += cost\n    if cost > maximum:\n        maximum = cost\n    if cost > 80:\n        above_80 += 1\nprint(total, maximum, above_80)'), [['328.0, 91.5, 2', 'Correct: exactly 80 is not above 80.'], ['328.0, 91.5, 3', 'Exactly 80 is excluded.'], ['91.5, 328.0, 2', 'Total and maximum are reversed.'], ['328.0, 82.5, 2', 'The maximum updates at 91.5.']], 0, 'One loop can update several states with distinct purposes, and a boundary directly affects the count.'),
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
$expectedpath = $language === 'ja' ? '/ja/04_loops_accumulators.ipynb' : '/04_loops_accumulators.ipynb';
$newurl = preg_replace('~/(?:ja/)?04_loops_accumulators\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || ($newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath))) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $language === 'ja'
    ? '<p>反復ごとの値と状態を予測し、実行し、境界と空データを変えて説明します。</p>'
    : '<p>Predict each iteration and state, run it, change boundaries and empty data, and explain the result.</p>';
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
    $saved = v17_save_question($category->id, $context->id, $shortname . ' v17: ', $question, $language);
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
    'marker' => 'PYAI-V17-LOOPS-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
