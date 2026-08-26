<?php
// Rewrite Chapter 2.2 while preserving the existing Moodle activities.
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

function v20_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v20_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v20_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
            ? '<p>正解です。コードの入口、処理、出口を自分の言葉で説明してから次へ進みましょう。</p>'
            : '<p>Correct. Explain the input, process, and output before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>呼び出し順、変数の範囲、例外名、境界値をNotebookで表示して、もう一度確認しましょう。</p>'
            : '<p>Print the call order, scope, exception name, or boundary value in the Notebook, then try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v20_find_and_rename(int $courseid, string $table, string $oldname, string $newname): stdClass {
    global $DB;
    $record = $DB->get_record($table, ['course' => $courseid, 'name' => $newname]);
    if (!$record) {
        $record = $DB->get_record($table, ['course' => $courseid, 'name' => $oldname], '*', MUST_EXIST);
        $record->name = $newname;
        if (property_exists($record, 'timemodified')) {
            $record->timemodified = time();
        }
        $DB->update_record($table, $record);
    }
    return $record;
}

if ($language === 'ja') {
    $chaptername = '第2章 — データ構造と信頼できるプログラム';
    $topicname = '2.2 関数・エラー・テスト';
    $topicsummary = '<p>レコード処理を再利用可能な関数へ分け、入力検証、例外、トレースバック、正常値・境界値・異常値のテストを扱います。</p>';
    $oldpage = 'レッスン6：関数・エラー・テスト';
    $oldlti = 'Python Lab 06：関数・エラー・テスト';
    $oldquiz = '理解度チェック：レッスン6 関数・エラー・テスト';
    $pagename = 'レッスン2.2：関数・エラー・テスト';
    $ltiname = 'Python Lab 2.2：関数・エラー・テスト';
    $quizname = '理解度チェック：2.2 関数・エラー・テスト';
    $pageintro = '<p>2.1のセンターレコード処理を、再利用でき、失敗理由を説明でき、テスト可能な関数へ発展させます。</p>';
    $quizintro = '<p>短いコードから、関数の呼び出し、戻り値、スコープ、例外、テスト結果を確認します。何度でも挑戦でき、最高点が記録されます。</p>';
    $body = '<div class="python-sample-lesson"><h2>長い処理を、役割の明確な関数へ分ける</h2>'
        . '<p>2.1では、複数の学習センターを辞書のリストとして表しました。次は、その処理を一度だけ動くコードから、名前を付けて繰り返し呼び出せる部品へ変えます。関数にする目的は行数を減らすことだけではありません。入力、処理、出力の境界を明らかにし、同じ規則を一か所で保守し、個別にテストできるようにすることです。</p>'
        . '<h3>defで定義し、名前と丸括弧で呼び出す</h3>'
        . '<p><code>def</code>の行には関数名と仮引数を書き、末尾にコロンを置きます。インデントされた本体は、定義した時点では実行されません。呼び出した時に上から実行されるため、定義は呼び出しより先に実行されている必要があります。</p>'
        . v20_code("def completion_rate(completed, registered):\n    return completed / registered * 100\n\nrate = completion_rate(32, 40)\nprint(f\"修了率: {rate:.1f}%\")")
        . '<h3>仮引数は入口、returnは出口になる</h3>'
        . '<p>定義に書く名前を仮引数、呼び出しで渡す値を実引数と呼びます。<code>return</code>は値を呼び出し元へ返し、その時点で関数を終了します。<code>print()</code>は表示するだけで、戻り値を指定しない関数は<code>None</code>を返します。後の計算に使う結果は表示ではなくreturnで返します。</p>'
        . v20_code("def add_with_print(a, b):\n    print(a + b)\n\ndef add_with_return(a, b):\n    return a + b\n\nx = add_with_print(2, 3)\ny = add_with_return(2, 3)\nprint(x, y * 2)")
        . '<h3>ローカル変数によって処理を閉じ込める</h3>'
        . '<p>関数内で代入した変数は原則としてローカル変数で、関数外から直接参照できません。必要な値を引数で受け取り、結果をreturnで返すと、外部の変数に偶然依存するバグを減らせます。同じ入力から同じ結果が得られる関数は、再現とテストも容易です。</p>'
        . '<h3>既定値とキーワード引数で呼び出しを読みやすくする</h3>'
        . '<p>省略可能な仮引数には既定値を付けられます。既定値のない仮引数を先に置きます。キーワード引数なら<code>completed=32</code>のように意味を明記でき、同じ数値型の引数を取り違えにくくなります。</p>'
        . v20_code("def format_centre(name, completed, registered, decimals=1):\n    rate = completed / registered * 100\n    return f\"{name}: {rate:.{decimals}f}%\"\n\nprint(format_centre(\"North\", completed=32, registered=40))")
        . '<h3>docstringと型ヒントで関数の契約を伝える</h3>'
        . '<p>関数直下のdocstringには、目的、入力、戻り値、無効な入力の扱いを書きます。型ヒントは読み手と開発ツールを助けますが、Pythonが実行時に型を自動強制するものではありません。実データの条件はコードで検証します。</p>'
        . v20_code("def safe_rate(completed: int, registered: int) -> float | None:\n    \"\"\"修了率を返す。登録者数が0以下ならNoneを返す。\"\"\"\n    if registered <= 0:\n        return None\n    return completed / registered * 100")
        . '<h3>検証、計算、表示を別の責務に分ける</h3>'
        . '<p>一つの長い関数へすべてを混ぜると、どこで誤ったか分かりにくくなります。必須キーの有無と値の範囲を検証する関数、率を計算する関数、表示を作る関数へ分けます。必須キー欠落には<code>KeyError</code>、値の規則違反には<code>ValueError</code>を<code>raise</code>すると、失敗理由を呼び出し元へ伝えられます。</p>'
        . v20_code("REQUIRED = {\"name\", \"registered\", \"completed\"}\n\ndef validate_centre(centre):\n    missing = REQUIRED - centre.keys()\n    if missing:\n        raise KeyError(f\"必須項目がありません: {sorted(missing)}\")\n    if centre[\"completed\"] > centre[\"registered\"]:\n        raise ValueError(\"修了者数が登録者数を超えています\")")
        . '<h3>エラーは発生段階を分け、トレースバックは最後から読む</h3>'
        . '<p>文法エラーはPythonが構文を解釈できない状態、実行時エラーは実行中に例外が発生した状態、論理エラーは最後まで動いても結果が誤っている状態です。トレースバックでは、まず最後の行の例外名とメッセージを読み、その上にある自分のコード行へ戻ります。</p>'
        . '<h3>tryには失敗し得る最小範囲を置く</h3>'
        . '<p><code>except</code>では、対処できる具体的な例外だけを捕捉します。<code>else</code>は例外がなかった場合、<code>finally</code>は成否にかかわらない後始末に使います。<code>except Exception: pass</code>のように原因を隠してはいけません。予想していない例外は、修正できるよう表へ出す必要があります。</p>'
        . v20_code("try:\n    registered = int(raw_value)\nexcept ValueError:\n    print(\"整数として読めません\")\nelse:\n    print(registered)\nfinally:\n    print(\"入力確認終了\")")
        . '<h3>正常値、境界値、異常値を別々にテストする</h3>'
        . '<p>正常値一件だけでは境界のバグを見つけられません。通常の値、0やしきい値直前直後などの境界、必須キー欠落や範囲違反などの異常値を用意します。<code>assert</code>で期待値を明記し、浮動小数点数は小さな許容誤差で比較します。assertはここでの検査には便利ですが、利用者入力の検証の代わりにはなりません。</p>'
        . v20_code("assert abs(safe_rate(32, 40) - 80.0) < 0.0001\nassert safe_rate(0, 0) is None\nassert safe_rate(1, -1) is None")
        . '<h3>例題から応用へ</h3><p>2.1の3センターを使い、<code>validate_centre()</code>、<code>centre_rate()</code>、<code>summarise_centres()</code>へ処理を分けます。最後の関数は、修了率75%未満のセンター名、地区の集合、全体の登録者数と修了者数を辞書で返します。正常な3件、登録者数0、必須キー欠落、修了者数超過をテストしてください。</p>'
        . '<p>このレッスンを終えると、関数の契約を定め、失敗を分類して伝え、代表的な境界をテストできます。次の2.3では、この部品を組み合わせて月次実績報告を作ります。</p>'
        . '<p><strong>学習時間の目安：</strong>約4時間</p><p style="display:none">PYAI-V20-LESSON22-FLOW</p></div>';
    $questions = [
        v20_question('L22R-01', '<p>何が表示されますか。</p>' . v20_code("def double(value):\n    return value * 2\n\nprint(double(4))"), [['4', '関数は値を2倍します。'], ['8', '正解です。4がvalueへ入り、8を返します。'], ['None', 'returnがあります。'], ['NameError', '呼び出し前に定義されています。']], 1, '関数は定義後に呼び出し、実引数が仮引数へ渡されます。'),
        v20_question('L22R-02', '<p>最後に何が表示されますか。</p>' . v20_code("def show_total(a, b):\n    print(a + b)\n\nresult = show_total(2, 3)\nprint(result)"), [['3', '最初の表示は5です。'], ['5だけ', '呼び出し後にもprintがあります。'], ['5、続いてNone', '正解です。明示的なreturnがありません。'], ['TypeError', 'Noneの表示は可能です。']], 2, 'printは表示し、returnを省略した関数の戻り値はNoneです。'),
        v20_question('L22R-03', '<p>コメントを外した最後の行では何が起こりますか。</p>' . v20_code("def gap(planned, actual):\n    result = planned - actual\n    return result\n\nprint(gap(40, 34))\n# print(result)"), [['6を表示する', '関数呼び出しの戻り値は6ですが、ローカル名は外にありません。'], ['NameError', '正解です。resultは関数のローカル変数です。'], ['Noneを表示する', '名前自体が見つかりません。'], ['SyntaxError', '構文は正しいです。']], 1, '関数内で代入した名前は通常ローカルです。'),
        v20_question('L22R-04', '<p>何が表示されますか。</p>' . v20_code("def label(name, suffix=\" centre\"):\n    return name + suffix\n\nprint(label(\"North\"))"), [['North', '既定のsuffixも連結されます。'], ['North centre', '正解です。省略した引数には既定値が使われます。'], ['centre North', '連結順が逆です。'], ['TypeError', 'suffixは省略できます。']], 1, '既定値を持つ仮引数は呼び出し時に省略できます。'),
        v20_question('L22R-05', '<p>型ヒント<code>registered: int</code>について正しい説明はどれですか。</p>', [['文字列を渡すとPythonが呼び出し前に必ず拒否する', '通常のPython実行は型ヒントを自動強制しません。'], ['読み手や検査ツールに意図を伝える', '正解です。実データの検証は別途必要です。'], ['値を自動でintへ変換する', '自動変換はしません。'], ['docstringを不要にする', '目的や無効入力の方針はdocstringにも必要です。']], 1, '型ヒントは契約を伝えますが、実行時検証そのものではありません。'),
        v20_question('L22R-06', '<p>この入力で最初に送出される例外はどれですか。</p>' . v20_code("required = {\"name\", \"registered\", \"completed\"}\ncentre = {\"name\": \"A\", \"registered\": 10}\nmissing = required - centre.keys()\nif missing:\n    raise KeyError(sorted(missing))\nif centre[\"completed\"] > centre[\"registered\"]:\n    raise ValueError"), [['ValueError', '値比較より前に欠落検査があります。'], ['KeyError', '正解です。completedが欠落しています。'], ['ZeroDivisionError', '除算していません。'], ['例外はない', 'missingは空ではありません。']], 1, '必須キー欠落と値の範囲違反を分けます。'),
        v20_question('L22R-07', '<p>次の状態はどの種類ですか。「コードは最後まで動くが、修了率を<code>registered / completed</code>で計算していた」</p>', [['文法エラー', '構文は解釈できます。'], ['実行時エラー', '値が0でなければ実行できます。'], ['論理エラー', '正解です。動いても計算規則が誤っています。'], ['ImportError', 'importとは関係ありません。']], 2, '実行成功は結果の正しさを保証しません。'),
        v20_question('L22R-08', '<p>文字列<code>"forty"</code>を整数へ変換する失敗だけを処理するexceptはどれですか。</p>', [['except ValueError:', '正解です。int変換の形式不正はValueErrorです。'], ['except KeyError:', '辞書キー欠落ではありません。'], ['except ZeroDivisionError:', '除算ではありません。'], ['except Exception: pass', '広すぎ、原因も隠します。']], 0, '対処できる具体的な例外を狭いtryで捕捉します。'),
        v20_question('L22R-09', '<p>修了率関数の境界テストとして最も重要な追加例はどれですか。通常値<code>(32, 40)</code>は確認済みです。</p>', [['(30, 40)だけ', '通常値を増やすだけでは境界が弱いです。'], ['(0, 0)と登録者数が負の値', '正解です。0除算と無効範囲を確認できます。'], ['(32, 40)をもう一度', '同じケースの反復です。'], ['関数名を変更する', '入力境界のテストではありません。']], 1, '正常値に加え、境界値と異常値を意図的に選びます。'),
        v20_question('L22R-10', '<p>複数センターの集計を検証・計算・表示へ分ける主な利点はどれですか。</p>', [['必ず実行速度が上がる', '分割の主目的ではありません。'], ['各規則を個別に再利用・テストできる', '正解です。責務と失敗箇所も明確になります。'], ['例外が発生しなくなる', '入力が不正なら例外は必要です。'], ['すべての変数がグローバルになる', 'むしろ依存を減らします。']], 1, '明確な責務へ分けると保守、再利用、テストが容易になります。'),
    ];
} else {
    $chaptername = 'Chapter 2 — Data Structures and Reliable Programs';
    $topicname = '2.2 Functions, errors, and testing';
    $topicsummary = '<p>Turn record processing into reusable functions, then handle validation, exceptions, tracebacks, and normal, boundary, and invalid tests.</p>';
    $oldpage = 'Lesson 6: Functions, errors, and testing';
    $oldlti = 'Python Lab 06: Functions, errors, and testing';
    $oldquiz = 'Knowledge check: Lesson 6: Functions, errors, and testing';
    $pagename = 'Lesson 2.2: Functions, errors, and testing';
    $ltiname = 'Python Lab 2.2: Functions, errors, and testing';
    $quizname = 'Knowledge check: 2.2 Functions, errors, and testing';
    $pageintro = '<p>Develop the centre-record processing from 2.1 into reusable functions whose failures can be explained and whose results can be tested.</p>';
    $quizintro = '<p>Read short code and check function calls, return values, scope, exceptions, and tests. Attempts are unlimited and the highest score is retained.</p>';
    $body = '<div class="python-sample-lesson"><h2>Separate a long process into functions with clear responsibilities</h2>'
        . '<p>Lesson 2.1 represented learning centres as a list of dictionaries. Now turn that one-off processing into named components that can be called repeatedly. A function does more than shorten code: it creates an explicit boundary around inputs, processing, and outputs so that one rule can be maintained and tested in one place.</p>'
        . '<h3>Define with def, then call by name</h3><p>Write the function name and parameters after <code>def</code>, followed by a colon. The indented body does not run when it is defined; it runs when called. The definition must execute before its call.</p>'
        . v20_code("def completion_rate(completed, registered):\n    return completed / registered * 100\n\nrate = completion_rate(32, 40)\nprint(f\"Completion rate: {rate:.1f}%\")")
        . '<h3>Parameters are inputs and return is the output</h3><p>Names in the definition are parameters; values supplied by a call are arguments. <code>return</code> sends a value to the caller and ends the function. <code>print()</code> only displays output. A function without an explicit return produces <code>None</code>, so return values needed by later calculations.</p>'
        . v20_code("def add_with_print(a, b):\n    print(a + b)\n\ndef add_with_return(a, b):\n    return a + b\n\nx = add_with_print(2, 3)\ny = add_with_return(2, 3)\nprint(x, y * 2)")
        . '<h3>Local names keep processing contained</h3><p>A variable assigned inside a function is normally local and cannot be read directly outside it. Receive required values through parameters and return results. This reduces accidental dependence on external state and makes the same behaviour easier to reproduce and test.</p>'
        . '<h3>Defaults and keyword arguments make calls readable</h3><p>Optional parameters can have defaults; put required parameters first. A keyword argument such as <code>completed=32</code> makes meaning visible and reduces mistakes when several arguments have the same numeric type.</p>'
        . v20_code("def format_centre(name, completed, registered, decimals=1):\n    rate = completed / registered * 100\n    return f\"{name}: {rate:.{decimals}f}%\"\n\nprint(format_centre(\"North\", completed=32, registered=40))")
        . '<h3>Docstrings and type hints communicate a contract</h3><p>A docstring states purpose, inputs, return value, and invalid-input policy. Type hints help readers and tools, but Python does not automatically enforce them at runtime. Validate conditions imposed by real operational data in code.</p>'
        . v20_code("def safe_rate(completed: int, registered: int) -> float | None:\n    \"\"\"Return the completion rate, or None when registration is not positive.\"\"\"\n    if registered <= 0:\n        return None\n    return completed / registered * 100")
        . '<h3>Separate validation, calculation, and presentation</h3><p>Mixing every responsibility into one long function obscures failures. Use separate functions to check required keys and ranges, calculate a rate, and prepare display. Raise <code>KeyError</code> for a missing required key and <code>ValueError</code> for a violated value rule so the caller receives a meaningful cause.</p>'
        . v20_code("REQUIRED = {\"name\", \"registered\", \"completed\"}\n\ndef validate_centre(centre):\n    missing = REQUIRED - centre.keys()\n    if missing:\n        raise KeyError(f\"Missing required fields: {sorted(missing)}\")\n    if centre[\"completed\"] > centre[\"registered\"]:\n        raise ValueError(\"Completed exceeds registered\")")
        . '<h3>Classify errors by stage and read a traceback from the end</h3><p>A syntax error prevents parsing, a runtime error raises an exception during execution, and a logic error runs but produces a wrong result. Start with the final traceback line for the exception name and message, then move upward to the relevant line in your code.</p>'
        . '<h3>Keep try narrow and catch only expected exceptions</h3><p>Catch a specific exception that the program can handle. <code>else</code> runs when no exception occurred and <code>finally</code> supports cleanup needed in either case. Do not use <code>except Exception: pass</code>, which hides the cause. Unexpected errors should remain visible so they can be fixed.</p>'
        . v20_code("try:\n    registered = int(raw_value)\nexcept ValueError:\n    print(\"Not a valid integer\")\nelse:\n    print(registered)\nfinally:\n    print(\"Input check finished\")")
        . '<h3>Test normal, boundary, and invalid cases separately</h3><p>One normal case cannot expose boundary bugs. Include ordinary values, boundaries such as zero and threshold neighbours, and invalid cases such as missing keys and impossible ranges. State expectations with <code>assert</code> and compare floats with a small tolerance. Assertions are useful checks here but do not replace validation of user data.</p>'
        . v20_code("assert abs(safe_rate(32, 40) - 80.0) < 0.0001\nassert safe_rate(0, 0) is None\nassert safe_rate(1, -1) is None")
        . '<h3>From guided example to transfer</h3><p>Use the three centres from 2.1 and separate the process into <code>validate_centre()</code>, <code>centre_rate()</code>, and <code>summarise_centres()</code>. The last function returns a dictionary containing names below 75%, the set of districts, total registration, and total completion. Test three valid records, zero registration, a missing required key, and completion above registration.</p>'
        . '<p>After this lesson, you can define a function contract, classify and communicate failures, and test representative boundaries. Lesson 2.3 combines these components into a monthly performance report.</p>'
        . '<p><strong>Estimated study time:</strong> about 4 hours</p><p style="display:none">PYAI-V20-LESSON22-FLOW</p></div>';
    $questions = [
        v20_question('L22R-01', '<p>What is displayed?</p>' . v20_code("def double(value):\n    return value * 2\n\nprint(double(4))"), [['4', 'The function doubles the value.'], ['8', 'Correct: four enters value and eight is returned.'], ['None', 'There is an explicit return.'], ['NameError', 'The definition precedes the call.']], 1, 'Call a function after defining it; the argument is bound to its parameter.'),
        v20_question('L22R-02', '<p>What is displayed last?</p>' . v20_code("def show_total(a, b):\n    print(a + b)\n\nresult = show_total(2, 3)\nprint(result)"), [['3', 'The first display is five.'], ['Only 5', 'Another print follows the call.'], ['5, then None', 'Correct: there is no explicit return.'], ['TypeError', 'None can be displayed.']], 2, 'Print displays; a function without explicit return returns None.'),
        v20_question('L22R-03', '<p>What happens if the final comment marker is removed?</p>' . v20_code("def gap(planned, actual):\n    result = planned - actual\n    return result\n\nprint(gap(40, 34))\n# print(result)"), [['It displays 6', 'The returned value is six, but the local name is not outside.'], ['NameError', 'Correct: result is local to the function.'], ['It displays None', 'The name itself is not found.'], ['SyntaxError', 'The syntax is valid.']], 1, 'A name assigned inside a function is normally local.'),
        v20_question('L22R-04', '<p>What is displayed?</p>' . v20_code("def label(name, suffix=\" centre\"):\n    return name + suffix\n\nprint(label(\"North\"))"), [['North', 'The default suffix is also joined.'], ['North centre', 'Correct: omitted suffix uses its default.'], ['centre North', 'The concatenation order is reversed.'], ['TypeError', 'Suffix is optional.']], 1, 'A parameter with a default may be omitted by the caller.'),
        v20_question('L22R-05', '<p>Which statement about the hint <code>registered: int</code> is correct?</p>', [['Python always rejects a string before the call', 'Ordinary Python execution does not enforce the hint.'], ['It communicates intent to readers and checking tools', 'Correct; data validation is still required.'], ['It automatically converts the value to int', 'It performs no conversion.'], ['It makes a docstring unnecessary', 'Purpose and invalid-input policy still need documentation.']], 1, 'Type hints communicate a contract but are not runtime validation.'),
        v20_question('L22R-06', '<p>Which exception is raised first for this input?</p>' . v20_code("required = {\"name\", \"registered\", \"completed\"}\ncentre = {\"name\": \"A\", \"registered\": 10}\nmissing = required - centre.keys()\nif missing:\n    raise KeyError(sorted(missing))\nif centre[\"completed\"] > centre[\"registered\"]:\n    raise ValueError"), [['ValueError', 'The missing-field check comes first.'], ['KeyError', 'Correct: completed is missing.'], ['ZeroDivisionError', 'There is no division.'], ['No exception', 'Missing is not empty.']], 1, 'Distinguish a missing required key from a value range violation.'),
        v20_question('L22R-07', '<p>How should this be classified? “The code finishes, but calculates completion rate as <code>registered / completed</code>.”</p>', [['Syntax error', 'It can be parsed.'], ['Runtime error', 'It can run if completed is nonzero.'], ['Logic error', 'Correct: execution succeeds but the rule is wrong.'], ['ImportError', 'No import is involved.']], 2, 'Successful execution does not prove a correct result.'),
        v20_question('L22R-08', '<p>Which except clause handles only failure to convert <code>"forty"</code> to an integer?</p>', [['except ValueError:', 'Correct: invalid int text raises ValueError.'], ['except KeyError:', 'No dictionary key is involved.'], ['except ZeroDivisionError:', 'There is no division.'], ['except Exception: pass', 'It is too broad and hides the cause.']], 0, 'Catch a specific expected exception around a narrow operation.'),
        v20_question('L22R-09', '<p>Which important boundary tests should be added after checking the normal case <code>(32, 40)</code>?</p>', [['Only (30, 40)', 'This adds another ordinary case.'], ['(0, 0) and a negative registration', 'Correct: these test zero division policy and an invalid range.'], ['Repeat (32, 40)', 'This repeats the same case.'], ['Rename the function', 'That is not an input boundary.']], 1, 'Select normal, boundary, and invalid cases deliberately.'),
        v20_question('L22R-10', '<p>What is the main benefit of separating multi-centre work into validation, calculation, and presentation?</p>', [['It always runs faster', 'Speed is not the main reason.'], ['Each rule can be reused and tested independently', 'Correct; responsibilities and failures become clearer.'], ['Exceptions become impossible', 'Invalid data may still need an exception.'], ['Every variable becomes global', 'The separation reduces external dependence.']], 1, 'Clear responsibilities improve maintenance, reuse, and testing.'),
    ];
}

$chapter = null;
foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
    if ($section && empty($section->component) && $section->name === $chaptername) {
        $chapter = $section;
        break;
    }
}
if (!$chapter) {
    throw new RuntimeException("Chapter not found: {$chaptername}");
}
$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
$delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $delegated, ['summary' => $topicsummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$page = v20_find_and_rename($course->id, 'page', $oldpage, $pagename);
$lti = v20_find_and_rename($course->id, 'lti', $oldlti, $ltiname);
$quiz = v20_find_and_rename($course->id, 'quiz', $oldquiz, $quizname);
$page->intro = $pageintro;
$page->introformat = FORMAT_HTML;
$page->content = $body;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);

$expectedpath = $language === 'ja' ? '/ja/06_functions_errors_testing.ipynb' : '/06_functions_errors_testing.ipynb';
$newurl = preg_replace('~/(?:ja/)?06_functions_errors_testing\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || ($newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath))) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $language === 'ja'
    ? '<p>関数を実行し、引数や境界値を変更して、戻り値と例外を確認します。保存後に理解度チェックへ戻ります。</p>'
    : '<p>Run each function, change arguments and boundary values, and inspect return values and exceptions. Save before returning to the learning check.</p>';
$lti->introformat = FORMAT_HTML;
$lti->timemodified = time();
$DB->update_record('lti', $lti);

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
    $saved = v20_save_question($category->id, $context->id, $shortname . ' v20: ', $question, $language);
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
    'marker' => 'PYAI-V20-LESSON22-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
