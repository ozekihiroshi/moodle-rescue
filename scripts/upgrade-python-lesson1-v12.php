<?php
// Rewrite Lesson 1 as a continuous introduction to program execution.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
\core\session\manager::set_user(get_admin());

function v12_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

if ($language === 'ja') {
    $name = 'レッスン1：プログラム・値・式・出力';
    $intro = '<p>短いプログラムを上から順に読み、値から式の結果が生まれ、<code>print()</code>によって出力される流れを確かめます。</p>';
    $content = '<div class="python-sample-lesson"><h2>最初のプログラムを読む</h2>'
        . '<p>Python Labの準備ができたので、短いプログラムから始めます。次の例は、ある講座を1日3回、5日間実施し、各回に16人が参加できる場合の規模を表示します。まず実行せず、三行に何が表示されるか予想してください。</p>'
        . v12_code("print(\"講座実施計画\")\nprint(3 * 5)\nprint(3 * 5 * 16)")
        . '<p>Pythonインタプリタは、通常この三つの命令を上から順に実行します。一行目は見出しを表示し、二行目は実施回数の15、三行目は参加可能人数の240を表示します。プログラムを読むときは、画面に見える結果だけでなく、その結果を作った命令を一行ずつ追います。</p>'
        . '<table class="generaltable"><thead><tr><th>用語</th><th>この例にあるもの</th><th>役割</th></tr></thead><tbody><tr><td>命令</td><td><code>print(...)</code></td><td>コンピュータに行わせる処理を示す</td></tr><tr><td>値</td><td><code>3</code>、<code>5</code>、<code>16</code>、見出しの文字</td><td>プログラムが扱うデータそのもの</td></tr><tr><td>式</td><td><code>3 * 5 * 16</code></td><td>評価されると一つの値になる</td></tr><tr><td>出力</td><td>表示された三行</td><td>処理した結果をプログラムの外へ示す</td></tr></tbody></table>'
        . '<h3>式は値を作り、<code>print()</code>がそれを外へ示す</h3>'
        . '<p><code>3 * 5</code>という式は、Pythonに評価されると15という値になります。その値を<code>print()</code>へ渡したため、画面に15が表示されます。式を評価することと、結果を出力することは別の働きです。</p>'
        . '<p>Notebookでは、セルの最後に<code>3 * 5</code>だけを書くと、学習を助けるために結果が表示されます。しかし、同じ行を通常の<code>.py</code>スクリプトへ書いただけでは表示されません。プログラムとして外へ示したい結果には<code>print()</code>を使うと、実行方法が変わっても意図が明確です。</p>'
        . '<h3>同じ記号でも、値が違えば意味が変わる</h3>'
        . '<p>次の二行はよく似ていますが、同じ結果にはなりません。</p>'
        . v12_code("print(3 + 4)\nprint(\"3 + 4\")")
        . '<p>一行目の<code>3</code>と<code>4</code>は数値なので、<code>+</code>は加算を行い7を作ります。二行目は引用符の内側全体が文字としての値なので、<code>3 + 4</code>という文字がそのまま表示されます。値には種類があり、その種類によって可能な操作が決まります。型の名前と変換は後のレッスンで体系的に学ぶので、ここでは数値と引用符で囲んだ文字を区別できれば十分です。</p>'
        . '<h3>計算の順序をコードに表す</h3>'
        . '<p>このレッスンでは、加算<code>+</code>、減算<code>-</code>、乗算<code>*</code>だけを使います。乗算は加算や減算より先に評価され、括弧の内側はさらに先に評価されます。</p>'
        . v12_code("print(2 + 3 * 4)\nprint((2 + 3) * 4)")
        . '<p>最初の式は<code>3 * 4</code>を先に計算するため14、二番目は括弧内の<code>2 + 3</code>を先に計算するため20になります。優先順位を覚えていても、仕事上の意味を明確にするために括弧を使うことがあります。たとえば午前18人と午後12人の合計を定員40人から引くなら、<code>40 - (18 + 12)</code>と書けば、先に参加者を合計する意図が読み取れます。</p>'
        . '<h3>値を変えると、プログラムの弱点が見える</h3>'
        . '<p>次の例は午前と午後の参加枠、その合計、未使用席を表示します。</p>'
        . v12_code("print(\"研修参加枠\")\nprint(\"午前:\", 18)\nprint(\"午後:\", 12)\nprint(\"合計:\", 18 + 12)\nprint(\"未使用席:\", 40 - (18 + 12))")
        . '<p>実行前に五行を予想してから、Notebookで上から実行してください。次に午前の参加枠を20へ変えます。このコードでは、午前の18が三か所に直接書かれているため、三か所すべてを直さなければ出力が矛盾します。Pythonは三つの18が同じ意味だとは推測できません。次のレッスンで一つの値に名前を付けると、この変更漏れを防ぎやすくなります。</p>'
        . '<p>コードが動かない場合は、閉じ引用符や閉じ括弧を忘れていないか確認します。コードが動いても、合計と未使用席が同時に正しいかを確認します。文法、実行、目的に合う結果という第0章の三つの問いが、ここでも役立ちます。</p>'
        . '<h3>自分で作る</h3>'
        . '<p>教材を1箱に12冊ずつ入れ、満杯の箱を4箱用意するとします。見出し、1箱の冊数、箱数、合計冊数を、意味の分かるラベル付きで四行に表示してください。使うものは、数値、文字、<code>*</code>、括弧、<code>print()</code>だけです。完成したら箱数を5へ変え、どの行を変更する必要があったかMarkdownセルに書きます。</p>'
        . '<p>このレッスンを終えると、命令、値、式、出力を短いコードの中で指し示し、上から順に結果を予想できます。理解度チェックでは、用語の暗記だけでなく、実際のコードが何を表示するかを確かめます。その後、レッスン2で値に名前を付け、変化するプログラムの状態を扱います。</p>'
        . '<p><strong>学習時間の目安：</strong>約2時間</p><p style="display:none">PYAI-V12-LESSON1-FLOW</p></div>';
} else {
    $name = 'Lesson 1: Programs, values, expressions, and output';
    $intro = '<p>Trace a short program from top to bottom and observe how expressions produce values that <code>print()</code> makes visible.</p>';
    $content = '<div class="python-sample-lesson"><h2>Read the first program</h2>'
        . '<p>Python Lab is ready, so begin with a short program. This example represents a course that runs three times a day for five days, with sixteen places in each session. Before running it, predict the three displayed lines.</p>'
        . v12_code("print(\"Course delivery plan\")\nprint(3 * 5)\nprint(3 * 5 * 16)")
        . '<p>The Python interpreter normally executes these instructions from top to bottom. The first line displays a heading, the second displays 15 sessions, and the third displays 240 available places. Reading a program means tracing the instructions that produced a result, not merely looking at the result.</p>'
        . '<table class="generaltable"><thead><tr><th>Term</th><th>In this example</th><th>Role</th></tr></thead><tbody><tr><td>Instruction</td><td><code>print(...)</code></td><td>States an operation for the computer to perform</td></tr><tr><td>Value</td><td><code>3</code>, <code>5</code>, <code>16</code>, and the heading text</td><td>Data handled by the program</td></tr><tr><td>Expression</td><td><code>3 * 5 * 16</code></td><td>Evaluates to one value</td></tr><tr><td>Output</td><td>The three displayed lines</td><td>Makes a processed result visible outside the program</td></tr></tbody></table>'
        . '<h3>An expression makes a value; <code>print()</code> makes it visible</h3>'
        . '<p>The expression <code>3 * 5</code> evaluates to the value 15. Passing that value to <code>print()</code> makes 15 appear on screen. Evaluation and output are two separate operations.</p>'
        . '<p>A Notebook helpfully displays <code>3 * 5</code> when it is the final expression in a cell. A normal <code>.py</code> script does not display that expression by itself. Use <code>print()</code> for an intended program output so that the intention remains clear in a Notebook, Console, or script.</p>'
        . '<h3>The kind of value changes what an operator means</h3>'
        . '<p>These two lines look similar but do not produce the same result.</p>'
        . v12_code("print(3 + 4)\nprint(\"3 + 4\")")
        . '<p>On the first line, <code>3</code> and <code>4</code> are numeric values, so <code>+</code> adds them and produces 7. On the second, everything inside the quotes is a text value, so the characters <code>3 + 4</code> are displayed unchanged. Every value has a kind that determines sensible operations. Later lessons develop type names and conversion systematically; for now, distinguish numbers from quoted text.</p>'
        . '<h3>Express the intended order of calculation</h3>'
        . '<p>This lesson uses only addition <code>+</code>, subtraction <code>-</code>, and multiplication <code>*</code>. Multiplication is evaluated before addition or subtraction, while parentheses are evaluated first.</p>'
        . v12_code("print(2 + 3 * 4)\nprint((2 + 3) * 4)")
        . '<p>The first expression is 14 because <code>3 * 4</code> is evaluated first. The second is 20 because the parentheses produce 5 before multiplication. Even when you know precedence, parentheses can make work meaning explicit. To subtract morning 18 and afternoon 12 from capacity 40, <code>40 - (18 + 12)</code> clearly says to combine attendance before finding unused places.</p>'
        . '<h3>Changing a value exposes a weakness</h3>'
        . '<p>The following program displays morning and afternoon places, their total, and unused capacity.</p>'
        . v12_code("print(\"Workshop places\")\nprint(\"Morning:\", 18)\nprint(\"Afternoon:\", 12)\nprint(\"Total:\", 18 + 12)\nprint(\"Unused seats:\", 40 - (18 + 12))")
        . '<p>Predict all five lines, then run the cell from the top. Now change the morning places to 20. Because the value 18 is written directly in three locations, all three must be edited or the outputs contradict each other. Python cannot infer that those three values have the same meaning. Lesson 2 gives the value one name and makes this kind of change safer.</p>'
        . '<p>If the code will not run, check for a missing closing quote or parenthesis. If it runs, check that total and unused seats can both be true. The Chapter 0 distinction between syntax, execution, and a result that meets the purpose applies here.</p>'
        . '<h3>Create your own program</h3>'
        . '<p>Suppose each box contains twelve learning books and four full boxes are prepared. Display a heading, books per box, number of boxes, and total books on four clearly labelled lines. Use only numbers, text, <code>*</code>, parentheses, and <code>print()</code>. Change the box count to five and explain in a Markdown cell which lines needed editing.</p>'
        . '<p>After this lesson, you can point to an instruction, value, expression, and output in a short program and predict its execution from the top. The learning check uses running code rather than definition recall alone. Lesson 2 then gives values names and introduces changing program state.</p>'
        . '<p><strong>Estimated study time:</strong> about 2 hours</p><p style="display:none">PYAI-V12-LESSON1-FLOW</p></div>';
}

$page = $DB->get_record('page', ['course' => $course->id, 'name' => $name]);
if (!$page) {
    $alternatives = $language === 'ja'
        ? ['レッスン1：値・データ型・式・出力']
        : ['Lesson 1: Values, data types, expressions, and output'];
    foreach ($alternatives as $alternative) {
        if ($page = $DB->get_record('page', ['course' => $course->id, 'name' => $alternative])) {
            break;
        }
    }
}
if (!$page) {
    throw new moodle_exception('Lesson 1 page not found');
}

$page->name = $name;
$page->intro = $intro;
$page->introformat = FORMAT_HTML;
$page->content = $content;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $shortname,
    'lesson' => $name,
    'marker' => 'PYAI-V12-LESSON1-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
