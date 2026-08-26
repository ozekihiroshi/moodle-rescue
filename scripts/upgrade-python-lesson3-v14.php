<?php
// Insert Chapter 1.3: basic scalar types, conversion, and arithmetic.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';

use core_courseformat\formatactions;
use core_question\local\bank\question_version_status;

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
\core\session\manager::set_user(get_admin());

function v14_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

function v14_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v14_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
            ? '<p>正解です。値、型、演算の順序を説明してから次へ進みましょう。</p>'
            : '<p>Correct. Explain the value, type, and operation order before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>型と演算子を確認し、Notebookで同じコードを実行してから再挑戦しましょう。</p>'
            : '<p>Check the type and operator, run the same code in the Notebook, and try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v14_rename_subsection(stdClass $course, string $oldname, string $newname): stdClass {
    global $DB;
    $instance = $DB->get_record('subsection', ['course' => $course->id, 'name' => $newname]);
    if (!$instance) {
        $instance = $DB->get_record('subsection', ['course' => $course->id, 'name' => $oldname], '*', MUST_EXIST);
        $instance->name = $newname;
        $instance->timemodified = time();
        $DB->update_record('subsection', $instance);
    }
    $delegated = $DB->get_record('course_sections', [
        'course' => $course->id,
        'component' => 'mod_subsection',
        'itemid' => $instance->id,
    ], '*', MUST_EXIST);
    course_update_section($course, $delegated, ['name' => $newname]);
    return get_coursemodule_from_instance('subsection', $instance->id, $course->id, false, MUST_EXIST);
}

function v14_place_before(stdClass $course, int $parentsectionid, int $cmid, int $beforecmid): void {
    global $DB;
    foreach ($DB->get_records('course_sections', ['course' => $course->id]) as $section) {
        $sequence = array_values(array_filter(
            array_map('intval', explode(',', (string) $section->sequence)),
            fn(int $id): bool => $id > 0 && $id !== $cmid
        ));
        $updated = implode(',', $sequence);
        if ($updated !== (string) $section->sequence) {
            $section->sequence = $updated;
            $DB->update_record('course_sections', $section);
        }
    }
    $parent = $DB->get_record('course_sections', ['id' => $parentsectionid, 'course' => $course->id], '*', MUST_EXIST);
    $sequence = array_values(array_filter(array_map('intval', explode(',', (string) $parent->sequence))));
    $position = array_search($beforecmid, $sequence, true);
    if ($position === false) {
        $sequence[] = $cmid;
    } else {
        array_splice($sequence, $position, 0, [$cmid]);
    }
    $parent->sequence = implode(',', $sequence);
    $DB->update_record('course_sections', $parent);
    $DB->set_field('course_modules', 'section', $parent->id, ['id' => $cmid]);
}

if ($language === 'ja') {
    $parentname = '第1章 — プログラミングの基礎と基本データ';
    $topicname = '1.3 基本データ型・型変換・算術';
    $topicsummary = '<p>Pythonの基本的な値の型を区別し、算術演算子と明示的な型変換を使って、意味のある数値結果を作ります。</p>';
    $shift = [
        ['1.5 実践プロジェクト：週間サポート報告', '1.6 実践プロジェクト：週間サポート報告'],
        ['1.4 ループによる繰り返し', '1.5 ループによる繰り返し'],
        ['1.3 条件による判断', '1.4 条件による判断'],
    ];
    $conditiontopic = '1.4 条件による判断';
    $pagename = 'レッスン1.3：基本データ型・型変換・算術';
    $quizname = '理解度チェック：1.3 基本データ型・型変換・算術';
    $ltiname = 'Python Lab 1.3：基本データ型・型変換・算術';
    $intro = '<p>値の型を確認し、型に合った演算と明示的な変換を使って、正しい数値結果を作ります。</p>';
    $body = '<div class="python-sample-lesson"><h2>同じ見た目でも、値の種類が違う</h2>'
        . '<p>Lesson 2では、名前が値を指すことを学びました。次に必要なのは、その値がどのような種類なのかを見分けることです。たとえば<code>34</code>と<code>"34"</code>は画面上では似ていますが、前者は計算に使える整数、後者は二文字からなる文字列です。型が違えば、同じ記号を使っても可能な処理や結果が変わります。</p>'
        . v14_code("print(34, type(34))\nprint(82.5, type(82.5))\nprint(\"34\", type(\"34\"))\nprint(True, type(True))\nprint(None, type(None))")
        . '<table class="generaltable"><thead><tr><th>型</th><th>例</th><th>表すもの</th></tr></thead><tbody><tr><td><code>int</code></td><td><code>34</code></td><td>小数部分を持たない整数</td></tr><tr><td><code>float</code></td><td><code>82.5</code></td><td>小数を含む数値</td></tr><tr><td><code>str</code></td><td><code>"North"</code></td><td>文字の並び</td></tr><tr><td><code>bool</code></td><td><code>True</code>、<code>False</code></td><td>真か偽か</td></tr><tr><td><code>NoneType</code></td><td><code>None</code></td><td>値がないことを示す特別な値</td></tr></tbody></table>'
        . '<p><code>type()</code>は、今ある値の型を確かめます。<code>None</code>は0や空文字列ではなく、「値がない」ことを表す別の値です。真偽値は条件分岐で詳しく扱いますが、この段階では数値や文字列とは異なる型だと理解します。</p>'
        . '<h3>数値を処理する七つの算術演算子</h3>'
        . '<p>数値型には、加算<code>+</code>、減算<code>-</code>、乗算<code>*</code>、除算<code>/</code>、切り下げ除算<code>//</code>、余り<code>%</code>、べき乗<code>**</code>を使えます。</p>'
        . '<table class="generaltable"><thead><tr><th>式</th><th>結果</th><th>用途</th></tr></thead><tbody><tr><td><code>10 + 3</code></td><td>13</td><td>合計を求める</td></tr><tr><td><code>10 - 3</code></td><td>7</td><td>差を求める</td></tr><tr><td><code>10 * 3</code></td><td>30</td><td>同じ量のまとまりを求める</td></tr><tr><td><code>10 / 3</code></td><td>3.333…</td><td>割合や平均を求める</td></tr><tr><td><code>10 // 3</code></td><td>3</td><td>満杯になるまとまり数を求める</td></tr><tr><td><code>10 % 3</code></td><td>1</td><td>まとまりに入らない余りを求める</td></tr><tr><td><code>10 ** 2</code></td><td>100</td><td>べき乗を求める</td></tr></tbody></table>'
        . '<p>Python 3では、整数同士でも<code>/</code>の結果は通常<code>float</code>です。<code>//</code>は単に小数部分を切り捨てる説明だけでは不十分で、負数も含めて小さい整数側へ丸めます。このコースの箱詰め例では正数を使い、「満杯になる箱数」として読みます。</p>'
        . '<h3>優先順位と括弧で、計算の意味を示す</h3>'
        . '<p>べき乗、乗算・除算、加算・減算の順に評価されます。<code>2 + 3 * 4</code>は14、<code>(2 + 3) * 4</code>は20です。優先順位を覚えるだけでなく、人が式の意味を読み間違えないよう括弧を使います。</p>'
        . v14_code("books = 53\nper_box = 12\nfull_boxes = books // per_box\nremaining_books = books % per_box\nprint(\"満杯の箱:\", full_boxes)\nprint(\"余る冊数:\", remaining_books)")
        . '<p>53冊を12冊ずつ分けると、満杯の箱は4箱、余りは5冊です。全冊を収める箱数は5箱なので、<code>//</code>の結果をそのまま「必要な箱数」と呼んではいけません。演算結果が仕事上のどの量を表すかを確認します。</p>'
        . '<h3>型を暗黙に期待せず、明示的に変換する</h3>'
        . '<p>CSVや入力フォームから受け取った数字は、文字列になっていることがあります。文字列の<code>"36"</code>へ数値の4を直接加えることはできません。整数が必要なら<code>int()</code>、小数を含む数値なら<code>float()</code>、表示用の文字列なら<code>str()</code>を使います。</p>'
        . v14_code("learners_text = \"36\"\nlearners = int(learners_text)\nprint(learners + 4)\n\ncost_text = \"487.50\"\ncost = float(cost_text)\nprint(cost, type(cost))")
        . '<p>変換できない内容を無理に変換すると<code>ValueError</code>になります。たとえば<code>int("12.5")</code>は失敗します。小数を表す文字列なら、まず<code>float("12.5")</code>とします。その後で整数化する場合は、小数部分を失ってよいかを判断しなければなりません。</p>'
        . '<p><code>bool()</code>にも注意が必要です。<code>bool("False")</code>は、文字の意味を読んで偽にするのではなく、空ではない文字列なので<code>True</code>になります。外部データの<code>"True"</code>や<code>"False"</code>は、内容を確認して明示的に変換します。</p>'
        . '<h3>正しく実行できても、数値として妥当か確認する</h3>'
        . '<p>0で割ると<code>ZeroDivisionError</code>になります。登録者0人の修了率や、修了者0人の1人当たり費用は、そのまま割り算をせず、後の条件分岐で処理方法を決めます。</p>'
        . '<p><code>float</code>は多くの小数を2進数の近似として保存するため、<code>0.1 + 0.2</code>が<code>0.30000000000000004</code>と表示されることがあります。報告では<code>round()</code>で表示桁を整えられますが、丸めは元データの誤りを直す機能ではありません。何桁に丸めたかを明確にします。</p>'
        . '<h3>例題から応用へ</h3>'
        . '<p>登録者36人のうち29人が修了した場合、修了率は<code>completed / registered * 100</code>です。分子と分母を逆にせず、結果を小数第1位へ丸めます。</p>'
        . v14_code("registered = 36\ncompleted = 29\ncompletion_rate = completed / registered * 100\nprint(\"修了率:\", round(completion_rate, 1), \"%\")")
        . '<p>応用練習では、教材53冊を1箱12冊で梱包したときの満杯の箱、余り、全冊に必要な箱数を表示します。続いて文字列<code>"487.50"</code>を数値へ変換し、登録者30人で割った1人当たり教材費を小数第2位まで表示します。登録者が0人の場合に何が起こるかも説明してください。</p>'
        . '<p>このレッスンを終えると、値と型を区別し、七つの算術演算子、優先順位、明示的な型変換、代表的な数値エラーを説明できます。次に、文字列、入力、書式付き出力を独立して学んだ後、条件分岐へ進みます。</p>'
        . '<p><strong>学習時間の目安：</strong>約2.5時間</p><p style="display:none">PYAI-V14-LESSON13-FLOW</p></div>';
    $questions = [
        v14_question('L13R-01', '<p>何が表示されますか。</p>' . v14_code('print(type(34).__name__)'), [['int', '正解です。34は整数です。'], ['float', '小数点を持たない整数リテラルです。'], ['str', '引用符で囲まれていません。'], ['bool', '真偽値ではありません。']], 0, '値34の型はintです。'),
        v14_question('L13R-02', '<p>何が表示されますか。</p>' . v14_code('print(type("34").__name__)'), [['int', '引用符内は整数ではありません。'], ['float', '数値へ変換していません。'], ['str', '正解です。引用符で囲まれた値は文字列です。'], ['NoneType', '値は存在します。']], 2, '数字に見えても引用符で囲まれた値はstrです。'),
        v14_question('L13R-03', '<p>何が表示されますか。</p>' . v14_code("print(10 / 4)\nprint(10 // 4)"), [['2.5 と 2', '正解です。/は通常float、//は床除算です。'], ['2 と 2', '/は小数部分を保持します。'], ['2.5 と 2.5', '//は床除算です。'], ['4 と 2', '10を4で割ります。']], 0, '/と//は異なる意味と結果型を持ちます。'),
        v14_question('L13R-04', '<p>教材53冊を1箱12冊ずつ入れます。何が表示されますか。</p>' . v14_code('print(53 // 12, 53 % 12)'), [['4 5', '正解です。満杯4箱と余り5冊です。'], ['5 4', '商と余りが逆です。'], ['4.416 0', '床除算と余りを使っています。'], ['5 5', '全冊に必要な箱と満杯箱は別です。']], 0, '//は満杯のまとまり数、%は余りを求めます。'),
        v14_question('L13R-05', '<p>何が表示されますか。</p>' . v14_code('print((2 + 3) * 4)'), [['14', '括弧内が先です。'], ['20', '正解です。5を4倍します。'], ['9', '4は加算ではありません。'], ['24', '式を二段階で追ってください。']], 1, '括弧は通常の優先順位より先に評価されます。'),
        v14_question('L13R-06', '<p>何が表示されますか。</p>' . v14_code('print(int("12") + 3)'), [['123', 'int()で数値へ変換済みです。'], ['15', '正解です。12と3の数値加算です。'], ['12', '3も加算されます。'], ['ValueError', '"12"は整数へ変換できます。']], 1, '有効な整数文字列はint()で明示的に変換できます。'),
        v14_question('L13R-07', '<p>最初の行で何が起こりますか。</p>' . v14_code("value = int(\"12.5\")\nprint(value)"), [['12が代入される', '小数文字列を直接intへ変換できません。'], ['12.5が代入される', 'intは小数型ではありません。'], ['ValueErrorになる', '正解です。まずfloat変換が必要です。'], ['文字列のまま表示される', '変換を試みています。']], 2, '要求した型で解釈できない文字列はValueErrorになります。'),
        v14_question('L13R-08', '<p>何が表示されますか。</p>' . v14_code('print(bool("False"), bool(""))'), [['False False', '非空文字列はTrueです。'], ['True False', '正解です。内容ではなく空かどうかを見ます。'], ['True True', '空文字列はFalseです。'], ['ValueError', 'どちらもboolへ変換できます。']], 1, 'bool(str)は単語の意味ではなく文字列が空かどうかで決まります。'),
        v14_question('L13R-09', '<p>何が起こりますか。</p>' . v14_code('print(10 / 0)'), [['0が表示される', '0除算は定義されません。'], ['Noneが表示される', '自動で欠損値にはなりません。'], ['ZeroDivisionErrorになる', '正解です。分母を事前に扱う必要があります。'], ['10が表示される', '除算は実行されません。']], 2, '0除算はZeroDivisionErrorになり、条件分岐による保護が必要です。'),
        v14_question('L13R-10', '<p>何が表示されますか。</p>' . v14_code("registered = 36\ncompleted = 29\nprint(round(completed / registered * 100, 1))"), [['80.6', '正解です。29/36の百分率を小数第1位へ丸めます。'], ['124.1', '分子と分母が逆です。'], ['0.8', '100倍した後の表示です。'], ['29.0', '修了者数そのものではありません。']], 0, '割合では分子、分母、100倍、丸め位置を確認します。'),
    ];
} else {
    $parentname = 'Chapter 1 — Programming Foundations and Scalar Values';
    $topicname = '1.3 Basic scalar types, conversion, and arithmetic';
    $topicsummary = '<p>Distinguish Python scalar value types and use arithmetic and explicit conversion to produce meaningful numeric results.</p>';
    $shift = [
        ['1.5 Applied project: Weekly support report', '1.6 Applied project: Weekly support report'],
        ['1.4 Repetition with loops', '1.5 Repetition with loops'],
        ['1.3 Decisions with conditions', '1.4 Decisions with conditions'],
    ];
    $conditiontopic = '1.4 Decisions with conditions';
    $pagename = 'Lesson 1.3: Basic scalar types, conversion, and arithmetic';
    $quizname = 'Knowledge check: 1.3 Basic scalar types, conversion, and arithmetic';
    $ltiname = 'Python Lab 1.3: Basic scalar types, conversion, and arithmetic';
    $intro = '<p>Inspect value types and use suitable arithmetic and explicit conversion to produce correct numeric results.</p>';
    $body = '<div class="python-sample-lesson"><h2>Similar appearance does not mean the same kind of value</h2>'
        . '<p>Lesson 2 established that a name refers to a value. The next question is what kind of value it is. <code>34</code> and <code>"34"</code> look similar on screen, but the first is an integer for calculation and the second is a two-character string. A type determines sensible operations and possible results.</p>'
        . v14_code("print(34, type(34))\nprint(82.5, type(82.5))\nprint(\"34\", type(\"34\"))\nprint(True, type(True))\nprint(None, type(None))")
        . '<table class="generaltable"><thead><tr><th>Type</th><th>Example</th><th>Represents</th></tr></thead><tbody><tr><td><code>int</code></td><td><code>34</code></td><td>A whole integer</td></tr><tr><td><code>float</code></td><td><code>82.5</code></td><td>A numeric value with fractional form</td></tr><tr><td><code>str</code></td><td><code>"North"</code></td><td>A sequence of characters</td></tr><tr><td><code>bool</code></td><td><code>True</code>, <code>False</code></td><td>A truth value</td></tr><tr><td><code>NoneType</code></td><td><code>None</code></td><td>The special absence-of-value object</td></tr></tbody></table>'
        . '<p><code>type()</code> inspects the value that exists now. <code>None</code> is not zero or empty text; it is a distinct value representing absence. Booleans are developed with conditions later, but they are already recognisable as a separate type.</p>'
        . '<h3>Seven arithmetic operators process numeric values</h3>'
        . '<p>Numeric types support addition <code>+</code>, subtraction <code>-</code>, multiplication <code>*</code>, division <code>/</code>, floor division <code>//</code>, remainder <code>%</code>, and exponentiation <code>**</code>.</p>'
        . '<table class="generaltable"><thead><tr><th>Expression</th><th>Result</th><th>Typical meaning</th></tr></thead><tbody><tr><td><code>10 + 3</code></td><td>13</td><td>Total</td></tr><tr><td><code>10 - 3</code></td><td>7</td><td>Difference</td></tr><tr><td><code>10 * 3</code></td><td>30</td><td>Equal groups</td></tr><tr><td><code>10 / 3</code></td><td>3.333…</td><td>Rate or average</td></tr><tr><td><code>10 // 3</code></td><td>3</td><td>Complete groups</td></tr><tr><td><code>10 % 3</code></td><td>1</td><td>Remainder</td></tr><tr><td><code>10 ** 2</code></td><td>100</td><td>Exponentiation</td></tr></tbody></table>'
        . '<p>In Python 3, <code>/</code> normally returns a <code>float</code> even for integer operands. <code>//</code> is floor division rather than a general “remove the decimal part” operation. This course first uses positive quantities, where it can represent complete boxes or groups.</p>'
        . '<h3>Precedence and parentheses communicate calculation meaning</h3>'
        . '<p>Exponentiation, multiplication and division, then addition and subtraction are evaluated in order. <code>2 + 3 * 4</code> is 14; <code>(2 + 3) * 4</code> is 20. Parentheses also prevent a reader from misunderstanding the work meaning.</p>'
        . v14_code("books = 53\nper_box = 12\nfull_boxes = books // per_box\nremaining_books = books % per_box\nprint(\"Full boxes:\", full_boxes)\nprint(\"Books remaining:\", remaining_books)")
        . '<p>Fifty-three books make four complete boxes with five books left. Five boxes are required to hold every book, so the result of <code>//</code> must not automatically be labelled “boxes required”. Relate every numeric result to the quantity requested by the task.</p>'
        . '<h3>Convert explicitly instead of assuming a type</h3>'
        . '<p>Numbers received from a CSV or input form may be strings. Numeric 4 cannot be added directly to text <code>"36"</code>. Use <code>int()</code> for an integer, <code>float()</code> for a fractional numeric value, and <code>str()</code> for display text.</p>'
        . v14_code("learners_text = \"36\"\nlearners = int(learners_text)\nprint(learners + 4)\n\ncost_text = \"487.50\"\ncost = float(cost_text)\nprint(cost, type(cost))")
        . '<p>Text that cannot represent the requested type raises <code>ValueError</code>. <code>int("12.5")</code> fails; use <code>float("12.5")</code> first, then decide whether losing the fractional part is genuinely appropriate.</p>'
        . '<p><code>bool()</code> also needs care. <code>bool("False")</code> does not interpret the word; it returns <code>True</code> because the string is non-empty. External <code>"True"</code> and <code>"False"</code> text requires explicit content-aware conversion.</p>'
        . '<h3>Successful execution is not enough: validate the number</h3>'
        . '<p>Division by zero raises <code>ZeroDivisionError</code>. A completion rate for zero registrations or cost per completion for zero completions requires a policy, later implemented with a condition, rather than unprotected division.</p>'
        . '<p>Many decimal <code>float</code> values are binary approximations, so <code>0.1 + 0.2</code> may display as <code>0.30000000000000004</code>. <code>round()</code> can prepare a reporting display, but rounding does not repair invalid data. State the chosen precision and why it is suitable.</p>'
        . '<h3>From guided example to transfer</h3>'
        . '<p>For 29 completions from 36 registrations, the completion rate is <code>completed / registered * 100</code>. Keep numerator and denominator in the intended order and round the report to one decimal place.</p>'
        . v14_code("registered = 36\ncompleted = 29\ncompletion_rate = completed / registered * 100\nprint(\"Completion rate:\", round(completion_rate, 1), \"%\")")
        . '<p>For transfer practice, pack 53 books at 12 per box and display complete boxes, remainder, and boxes required for every book. Then convert material-cost text <code>"487.50"</code>, divide it by 30 registrations, and display cost per learner to two decimals. Explain what happens when registrations are zero.</p>'
        . '<p>After this lesson, you can distinguish value and type and explain seven arithmetic operators, precedence, explicit conversion, and common numeric errors. Strings, input, and formatted output come next as a separate lesson before decisions with conditions.</p>'
        . '<p><strong>Estimated study time:</strong> about 2.5 hours</p><p style="display:none">PYAI-V14-LESSON13-FLOW</p></div>';
    $questions = [
        v14_question('L13R-01', '<p>What is displayed?</p>' . v14_code('print(type(34).__name__)'), [['int', 'Correct: 34 is an integer.'], ['float', 'It is written as an integer literal.'], ['str', 'It is not quoted.'], ['bool', 'It is not a truth value.']], 0, 'The value 34 has type int.'),
        v14_question('L13R-02', '<p>What is displayed?</p>' . v14_code('print(type("34").__name__)'), [['int', 'Quoted characters are not an integer.'], ['float', 'No conversion occurred.'], ['str', 'Correct: quoted characters form a string.'], ['NoneType', 'A value is present.']], 2, 'A quoted value is str even when its characters look numeric.'),
        v14_question('L13R-03', '<p>What is displayed?</p>' . v14_code("print(10 / 4)\nprint(10 // 4)"), [['2.5 then 2', 'Correct: / is true division and // is floor division.'], ['2 then 2', '/ preserves the fractional result.'], ['2.5 then 2.5', '// floors the quotient.'], ['4 then 2', 'Ten is divided by four.']], 0, '/ and // represent different operations and results.'),
        v14_question('L13R-04', '<p>Fifty-three books are packed twelve per box. What is displayed?</p>' . v14_code('print(53 // 12, 53 % 12)'), [['4 5', 'Correct: four complete boxes and five books remain.'], ['5 4', 'The quotient and remainder are reversed.'], ['4.416 0', 'The code uses floor division and remainder.'], ['5 5', 'Boxes required and complete boxes are different quantities.']], 0, '// finds complete groups; % finds the remainder.'),
        v14_question('L13R-05', '<p>What is displayed?</p>' . v14_code('print((2 + 3) * 4)'), [['14', 'Parentheses run first.'], ['20', 'Correct: five is multiplied by four.'], ['9', 'Four is multiplied, not added.'], ['24', 'Trace the two stages.']], 1, 'Parentheses are evaluated before ordinary precedence.'),
        v14_question('L13R-06', '<p>What is displayed?</p>' . v14_code('print(int("12") + 3)'), [['123', 'int() already converted the text.'], ['15', 'Correct: numeric 12 plus numeric 3.'], ['12', 'Three is also added.'], ['ValueError', '"12" is valid integer text.']], 1, 'Valid integer text can be converted explicitly with int().'),
        v14_question('L13R-07', '<p>What happens on the first line?</p>' . v14_code("value = int(\"12.5\")\nprint(value)"), [['12 is assigned', 'Decimal text cannot be passed directly to int().'], ['12.5 is assigned', 'int is not a fractional type.'], ['ValueError is raised', 'Correct: convert to float first.'], ['The original text is displayed', 'A conversion is attempted.']], 2, 'Text that cannot represent the requested type raises ValueError.'),
        v14_question('L13R-08', '<p>What is displayed?</p>' . v14_code('print(bool("False"), bool(""))'), [['False False', 'A non-empty string is true.'], ['True False', 'Correct: truth conversion checks emptiness.'], ['True True', 'The empty string is false.'], ['ValueError', 'Both can be converted to bool.']], 1, 'bool(str) tests whether the string is empty, not the meaning of its word.'),
        v14_question('L13R-09', '<p>What happens?</p>' . v14_code('print(10 / 0)'), [['0 is displayed', 'Division by zero is undefined.'], ['None is displayed', 'Python does not create a missing value automatically.'], ['ZeroDivisionError is raised', 'Correct: protect the denominator before division.'], ['10 is displayed', 'The division does not complete.']], 2, 'Division by zero raises ZeroDivisionError and needs an explicit policy.'),
        v14_question('L13R-10', '<p>What is displayed?</p>' . v14_code("registered = 36\ncompleted = 29\nprint(round(completed / registered * 100, 1))"), [['80.6', 'Correct: 29/36 as a percentage rounded to one decimal.'], ['124.1', 'The numerator and denominator are reversed.'], ['0.8', 'The result has been multiplied by 100.'], ['29.0', 'The code calculates a rate, not the count alone.']], 0, 'Check numerator, denominator, multiplication by 100, and reporting precision.'),
    ];
}

$parent = null;
foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
    if ($section && empty($section->component) && $section->name === $parentname) {
        $parent = $section;
        break;
    }
}
if (!$parent) {
    throw new moodle_exception("Parent chapter not found: {$parentname}");
}

$shifted = [];
foreach ($shift as [$oldname, $newname]) {
    $shifted[] = v14_rename_subsection($course, $oldname, $newname);
}
$conditioncm = end($shifted);

$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname]);
if (!$subsection) {
    $created = add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'], MUST_EXIST),
        'modulename' => 'subsection',
        'section' => $parent->section,
        'name' => $topicname,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => 0,
    ], $course);
    $subsection = $DB->get_record('subsection', ['id' => $created->instance], '*', MUST_EXIST);
}
$subsectioncm = get_coursemodule_from_instance('subsection', $subsection->id, $course->id, false, MUST_EXIST);
$delegated = $DB->get_record('course_sections', [
    'course' => $course->id,
    'component' => 'mod_subsection',
    'itemid' => $subsection->id,
], '*', MUST_EXIST);
course_update_section($course, $delegated, [
    'name' => $topicname,
    'summary' => $topicsummary,
    'summaryformat' => FORMAT_HTML,
    'visible' => 1,
]);
v14_place_before($course, $parent->id, $subsectioncm->id, $conditioncm->id);
rebuild_course_cache($course->id, true);

$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename]);
if (!$page) {
    $created = add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'modulename' => 'page',
        'section' => $delegated->section,
        'name' => $pagename,
        'intro' => $intro,
        'introformat' => FORMAT_HTML,
        'content' => $body,
        'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_OPEN,
        'printintro' => 0,
        'printlastmodified' => 0,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => 0,
        'showdescription' => 0,
    ], $course);
    $page = $DB->get_record('page', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $page->intro = $intro;
    $page->introformat = FORMAT_HTML;
    $page->content = $body;
    $page->contentformat = FORMAT_HTML;
    $page->timemodified = time();
    $DB->update_record('page', $page);
}
$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);

$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
if (!$category) {
    $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
    $category = reset($categories);
}
$quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname]);
$attemptsremoved = 0;
if (!$quiz) {
    $created = add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'modulename' => 'quiz',
        'section' => $delegated->section,
        'name' => $quizname,
        'intro' => $language === 'ja'
            ? '<p>値の型、算術、変換、数値エラーを短いコードで確認します。何度でも挑戦でき、最高点が記録されます。</p>'
            : '<p>Check value types, arithmetic, conversion, and numeric errors with short code. Attempts are unlimited and the highest score is retained.</p>',
        'introformat' => FORMAT_HTML,
        'timeopen' => 0,
        'timeclose' => 0,
        'timelimit' => 0,
        'overduehandling' => 'autosubmit',
        'graceperiod' => 0,
        'preferredbehaviour' => 'deferredfeedback',
        'attempts' => 0,
        'attemptonlast' => 0,
        'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 0,
        'questiondecimalpoints' => -1,
        'questionsperpage' => 10,
        'navmethod' => QUIZ_NAVMETHOD_FREE,
        'shuffleanswers' => 1,
        'grade' => 100,
        'reviewattempt' => 69888,
        'reviewcorrectness' => 4352,
        'reviewmarks' => 4352,
        'reviewspecificfeedback' => 4352,
        'reviewgeneralfeedback' => 4352,
        'reviewrightanswer' => 4352,
        'reviewoverallfeedback' => 4352,
        'password' => '',
        'quizpassword' => '',
        'subnet' => '',
        'browsersecurity' => '-',
        'delay1' => 0,
        'delay2' => 0,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => 0,
        'showdescription' => 1,
    ], $course);
    $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $attemptsremoved = (int) $DB->count_records('quiz_attempts', ['quiz' => $quiz->id]);
    if ($attemptsremoved > 0) {
        quiz_delete_all_attempts($quiz);
    }
    $structure = \mod_quiz\structure::create_for_quiz(\mod_quiz\quiz_settings::create($quiz->id));
    foreach (array_reverse($structure->get_slots()) as $slot) {
        $structure->remove_slot($slot->slot);
    }
}
$quiz->attempts = 0;
$quiz->grademethod = QUIZ_GRADEHIGHEST;
$quiz->timemodified = time();
$DB->update_record('quiz', $quiz);
if (!$DB->record_exists('quiz_slots', ['quizid' => $quiz->id])) {
    foreach ($questions as $data) {
        $saved = v14_save_question($category->id, $context->id, $shortname . ' v4: ', $data, $language);
        quiz_add_quiz_question($saved->id, $quiz, 0, 10);
    }
}
$DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
$quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

$lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname]);
if (!$lti) {
    $prototype = $DB->get_record('lti', [
        'course' => $course->id,
        'name' => $language === 'ja' ? 'Python Lab 02：変数・代入・状態' : 'Python Lab 02: Variables, assignment, and state',
    ], '*', MUST_EXIST);
    $toolurl = preg_replace(
        '~/hub/user-redirect/lab/tree/.*$~',
        '/hub/user-redirect/lab/tree/' . ($language === 'ja' ? 'ja/' : '') . '03_basic_scalar_types.ipynb',
        $prototype->toolurl
    );
    $created = add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST),
        'modulename' => 'lti',
        'section' => $delegated->section,
        'name' => $ltiname,
        'intro' => $language === 'ja'
            ? '<p>基本データ型・型変換・算術のNotebookを開き、予想、実行、変更、説明を行います。</p><p><strong>Notebook:</strong> <code>ja/03_basic_scalar_types.ipynb</code></p>'
            : '<p>Open the scalar types, conversion, and arithmetic Notebook; predict, run, change, and explain.</p><p><strong>Notebook:</strong> <code>03_basic_scalar_types.ipynb</code></p>',
        'introformat' => FORMAT_HTML,
        'typeid' => $prototype->typeid,
        'toolurl' => $toolurl,
        'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
        'instructorchoicesendname' => LTI_SETTING_NEVER,
        'instructorchoicesendemailaddr' => LTI_SETTING_NEVER,
        'instructorchoiceacceptgrades' => LTI_SETTING_NEVER,
        'grade' => 0,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => 0,
        'showdescription' => 1,
    ], $course);
    $lti = $DB->get_record('lti', ['id' => $created->instance], '*', MUST_EXIST);
}
$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);

$actions = formatactions::cm($course);
$actions->move_end_section($pagecm->id, $delegated->id);
$actions->move_end_section($lticm->id, $delegated->id);
$actions->move_end_section($quizcm->id, $delegated->id);
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
    'marker' => 'PYAI-V14-LESSON13-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
