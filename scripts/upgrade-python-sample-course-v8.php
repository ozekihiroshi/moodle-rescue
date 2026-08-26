<?php
// Build the v2 Chapter 0 and first Chapter 1 lesson prototype.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';

$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;

use core_question\local\bank\question_version_status;

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
\core\session\manager::set_user(get_admin());

function v8_find_record(string $table, int $courseid, array $names): stdClass {
    global $DB;
    foreach ($names as $name) {
        if ($record = $DB->get_record($table, ['course' => $courseid, 'name' => $name])) {
            return $record;
        }
    }
    throw new moodle_exception("Could not find {$table}: " . implode(' / ', $names));
}

function v8_update_page(stdClass $page, string $name, string $intro, string $content): void {
    global $DB;
    $page->name = $name;
    $page->intro = $intro;
    $page->introformat = FORMAT_HTML;
    $page->content = $content;
    $page->contentformat = FORMAT_HTML;
    $page->timemodified = time();
    $DB->update_record('page', $page);
}

function v8_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

function v8_upsert_marked_section(string $html, string $marker, string $section): string {
    $start = '<!-- ' . $marker . ':START -->';
    $end = '<!-- ' . $marker . ':END -->';
    $block = $start . $section . $end;
    $pattern = '~' . preg_quote($start, '~') . '.*?' . preg_quote($end, '~') . '~s';
    if (preg_match($pattern, $html)) {
        return preg_replace($pattern, $block, $html);
    }
    return $html . $block;
}

function v8_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v8_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
    $question = (object) ['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['choices'] as $index => [$answer, $why]) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => '<p>' . s($why) . '</p>', 'format' => FORMAT_HTML];
        $fractions[] = $index === $data['correct'] ? 1.0 : 0.0;
    }
    $correctfeedback = $language === 'ja'
        ? '<p>正解です。なぜそうなるかを説明してから次へ進みましょう。</p>'
        : '<p>Correct. Explain why before moving on.</p>';
    $incorrectfeedback = $language === 'ja'
        ? '<p>よくある間違いです。選択肢の説明を読み、Notebookで小さな例を実行してから再挑戦しましょう。</p>'
        : '<p>This is a common mistake. Read the option feedback, run a small Notebook example, and try again.</p>';
    $form = (object) [
        'name' => $prefix . $data['id'],
        'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => '<p>' . s($data['prompt']) . '</p>', 'format' => FORMAT_HTML],
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
        'correctfeedback' => ['text' => $correctfeedback, 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $incorrectfeedback, 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

$content = [];
if ($language === 'ja') {
    $content['course_summary'] = '<p>Pythonを初めて学ぶ人のための体系的なコースです。Python Labの操作から始め、値、データ型、プログラム構造、コレクション、関数、ファイル、例外、ライブラリ、クラスとオブジェクトを学び、その基礎を使って表形式データの分析と説明へ進みます。</p>';
    $content['section0_name'] = '第0章 — PythonとPython Labを始める';
    $content['section0_summary'] = '<p>Pythonの全体像を理解し、Notebookを開き、実行、変更、保存、再開できるようにします。</p>';
    $content['chapter1_name'] = '第1章 — 値・データ型・式';
    $content['chapter1_summary'] = '<p>Pythonが扱う主要な値とデータ型を理解し、式を計算して意味の分かる出力を作ります。</p>';
    $content['topic_name'] = '1.1 値・データ型・式・出力';
    $content['topic_summary'] = '<p>値の型、算術演算子、計算順序、文字列と数値の違いを確認し、結果を表示します。</p>';
    $content['guide_name'] = 'はじめに：Python学習の全体地図';
    $content['guide_intro'] = '<p>最初に、Pythonで何を学び、このコースがどこまで扱うかを確認します。</p>';
    $content['guide'] = '<h2>Pythonを学び始める前に</h2>'
        . '<p>Pythonは、読みやすい文法を持つ汎用プログラミング言語です。業務自動化、データ分析、Webシステム、科学技術計算、AIなど幅広い分野で使われます。AIがコードを提案できる時代でも、値、型、処理の順序、エラーを理解できれば、提案を検証し、自分の目的に合わせて変更できます。</p>'
        . '<h3>このコースの学習地図</h3><ol>'
        . '<li><strong>第0章：</strong>Python LabとNotebook</li><li><strong>第1章：</strong>値、データ型、式</li>'
        . '<li><strong>第2章：</strong>条件分岐と繰り返し</li><li><strong>第3章：</strong>リスト、タプル、辞書、集合</li>'
        . '<li><strong>第4章：</strong>関数とテスト</li><li><strong>第5章：</strong>ファイル、例外、モジュール、ライブラリ</li>'
        . '<li><strong>第6章：</strong>クラスとオブジェクト</li><li><strong>第7章：</strong>データ分析</li>'
        . '<li><strong>第8章：</strong>問いから根拠を作るプロジェクト</li></ol>'
        . '<h3>修了時にできること</h3><ul><li>基本的なPythonプログラムを読み、作り、テストする</li><li>ファイルを安全に読み書きする</li><li>小さなクラスを定義して利用する</li><li>CSVを確認、クリーニング、集計、可視化する</li><li>Notebookと短い報告で根拠を説明する</li></ul>'
        . '<h3>このコースの標準環境</h3><p>すべての例題と課題は、Moodleから開く<strong>Python LabのNotebook</strong>で実行します。PCへPythonを個別にインストールする必要はありません。Consoleと<code>.py</code>ファイルも紹介しますが、提出物はNotebookに統一します。</p>'
        . '<h3>学習方法</h3><p><strong>予想する → 実行する → 確認する → 変更する → 説明する</strong>の順で進めます。理解度チェックは何度でも挑戦でき、最高点が記録されます。90点で合格し、100点を目標にできます。</p>'
        . '<h3>AI支援について</h3><p>AIによる説明、ヒント、デバッグ支援を利用して構いません。ただし、使用したコードは自分で実行して結果を確認し、変更し、説明できるようにしてください。AI利用そのものを問う試験は行いません。</p>'
        . '<h3>次に進む前の確認</h3><ul><li>Moodleへログインできる</li><li>Python Labを開ける</li><li>Notebookを保存して再度開ける</li></ul><p style="display:none">PYAI-V8-CHAPTER0</p>';
    $content['lab_name'] = 'Python Labの使い方：Notebook・Console・スクリプト';
    $content['lab_intro'] = '<p>このコースでコードを実行、保存、再開する方法を練習します。</p>';
    $content['lab'] = '<h2>Pythonを実行する三つの方法</h2><table class="generaltable"><thead><tr><th>方法</th><th>特徴</th><th>このコースでの扱い</th></tr></thead><tbody>'
        . '<tr><td>Notebook</td><td>説明とコードをセルに分け、少しずつ実行して結果を残せる</td><td><strong>標準。すべての課題で使用</strong></td></tr>'
        . '<tr><td>Console（対話モード）</td><td>一行ずつ入力し、その場で結果を確認する</td><td>短い確認に使用</td></tr>'
        . '<tr><td><code>.py</code>スクリプト</td><td>命令をファイルに保存し、上から順にまとめて実行する</td><td>実行方法を体験する</td></tr></tbody></table>'
        . '<h3>Notebookの基本操作</h3><ol><li>Moodleの「Python Lab 00」を開きます。</li><li><strong>Code</strong>セルを選び、<kbd>Shift</kbd>+<kbd>Enter</kbd>で実行します。</li><li>値を一つ変更して、同じセルを再実行します。</li><li><strong>Markdown</strong>セルへ説明を書きます。</li><li><kbd>Ctrl</kbd>+<kbd>S</kbd>で保存します。</li><li>Notebookを閉じて再度開き、変更が残っていることを確認します。</li></ol>'
        . '<h3>実行順序とKernel</h3><p>Notebookは実行した値をKernelのメモリに保持します。セル左側の番号は実行順です。結果が説明と合わなくなったら、<strong>Kernelを再起動して上からすべて実行</strong>します。<code>[*]</code>が長く続く場合は処理中、入力待ち、または停止していない処理を疑います。</p>'
        . '<h3>エラーが出たら</h3><ol><li>最後の行にあるエラー名とメッセージを読みます。</li><li>示されたセルと行を確認します。</li><li>一度に一か所だけ直します。</li><li>もう一度実行して結果を比較します。</li></ol>'
        . '<h3>完了条件</h3><p><code>00_start_here.ipynb</code>を開き、すべてのセルを順番に実行し、値と説明を変更して保存します。pandasやデータ分析はまだ使いません。</p><p style="display:none">PYAI-V8-LAB-GUIDE</p>';
    $content['lesson_name'] = 'レッスン1：値・データ型・式・出力';
    $content['lesson_intro'] = '<p>数値と文字列を区別し、算術演算子を使って式を計算し、結果を表示します。</p>';
    $content['quiz_name'] = '理解度チェック：レッスン1 値・データ型・式・出力';
    $content['quiz_intro'] = '<p><strong>必須の理解度チェック：</strong>このレッスンで学んだ値、型、算術演算子、計算順序、出力を確認します。何度でも挑戦でき、最高点が記録されます。</p>';
    $content['lti_name'] = 'Python Lab 01：値・データ型・式・出力';
    $content['start_lti_name'] = 'Python Lab 00：Notebookを始める';
    $content['narrative_names'] = ['ナレディの紹介：コースとともに発展する一つの報告業務', 'Meet Naledi: One reporting task that grows with the course'];
} else {
    $content['course_summary'] = '<p>A systematic first Python course. Begin with Python Lab, then learn values, data types, program structure, collections, functions, files, exceptions, libraries, classes, and objects before applying that foundation to tabular data analysis and explanation.</p>';
    $content['section0_name'] = 'Chapter 0 — Starting Python and Python Lab';
    $content['section0_summary'] = '<p>Understand the Python learning map and learn to open, run, change, save, and resume a Notebook.</p>';
    $content['chapter1_name'] = 'Chapter 1 — Values, Data Types, and Expressions';
    $content['chapter1_summary'] = '<p>Understand Python values and principal data types, evaluate expressions, and produce meaningful output.</p>';
    $content['topic_name'] = '1.1 Values, types, expressions, and output';
    $content['topic_summary'] = '<p>Inspect value types, use arithmetic operators and precedence, distinguish text from numbers, and display results.</p>';
    $content['guide_name'] = 'Start here: the Python learning map';
    $content['guide_intro'] = '<p>First understand what Python is, what this course covers, and where it can take you.</p>';
    $content['guide'] = '<h2>Before you begin Python</h2>'
        . '<p>Python is a general-purpose programming language with readable syntax. It is used for automation, data analysis, web systems, scientific computing, and AI. Even when AI can suggest code, understanding values, types, execution order, and errors lets you verify suggestions and adapt them to your purpose.</p>'
        . '<h3>The course map</h3><ol><li><strong>Chapter 0:</strong> Python Lab and Notebooks</li><li><strong>Chapter 1:</strong> values, data types, and expressions</li><li><strong>Chapter 2:</strong> decisions and repetition</li><li><strong>Chapter 3:</strong> lists, tuples, dictionaries, and sets</li><li><strong>Chapter 4:</strong> functions and testing</li><li><strong>Chapter 5:</strong> files, exceptions, modules, and libraries</li><li><strong>Chapter 6:</strong> classes and objects</li><li><strong>Chapter 7:</strong> data analysis</li><li><strong>Chapter 8:</strong> a question-to-evidence project</li></ol>'
        . '<h3>What you will be able to do</h3><ul><li>read, create, and test basic Python programs;</li><li>read and write files safely;</li><li>define and use a small class;</li><li>inspect, clean, summarise, and visualise CSV data;</li><li>explain evidence in a Notebook and concise report.</li></ul>'
        . '<h3>The supported environment</h3><p>Run every example and assignment in a <strong>Python Lab Notebook</strong> opened from Moodle. You do not need to install Python on the PC. Console and <code>.py</code> execution are demonstrated, but submitted work uses Notebooks.</p>'
        . '<h3>How to study</h3><p><strong>Predict → Run → Check → Change → Explain.</strong> Learning checks allow unlimited attempts, retain the highest score, pass at 90%, and encourage a 100% mastery target.</p>'
        . '<h3>AI assistance</h3><p>You may use AI for explanations, hints, and debugging help. Run, verify, modify, and explain any assisted code. The course does not test whether you can classify AI-use policies.</p>'
        . '<h3>Ready to continue?</h3><ul><li>You can sign in to Moodle.</li><li>You can open Python Lab.</li><li>You can save and reopen a Notebook.</li></ul><p style="display:none">PYAI-V8-CHAPTER0</p>';
    $content['lab_name'] = 'Using Python Lab: Notebook, Console, and scripts';
    $content['lab_intro'] = '<p>Practise running, saving, and resuming code in the supported course environment.</p>';
    $content['lab'] = '<h2>Three ways to run Python</h2><table class="generaltable"><thead><tr><th>Method</th><th>What it does</th><th>Use in this course</th></tr></thead><tbody><tr><td>Notebook</td><td>Runs explanation and code in cells and keeps visible results</td><td><strong>Required for examples and submissions</strong></td></tr><tr><td>Console (interactive mode)</td><td>Runs one entered statement and immediately shows the result</td><td>Short experiments</td></tr><tr><td><code>.py</code> script</td><td>Saves instructions in a file and runs them from top to bottom</td><td>Demonstration</td></tr></tbody></table>'
        . '<h3>Notebook essentials</h3><ol><li>Open “Python Lab 00” from Moodle.</li><li>Select a <strong>Code</strong> cell and press <kbd>Shift</kbd>+<kbd>Enter</kbd>.</li><li>Change one value and run the cell again.</li><li>Write an explanation in a <strong>Markdown</strong> cell.</li><li>Press <kbd>Ctrl</kbd>+<kbd>S</kbd> to save.</li><li>Close and reopen the Notebook and confirm your change remains.</li></ol>'
        . '<h3>Execution order and the kernel</h3><p>The kernel keeps values created by executed cells. Numbers beside cells show execution order. If results no longer match the page, restart the kernel and run all cells from the top. A long-running <code>[*]</code> means the kernel is busy, waiting for input, or running code that has not stopped.</p>'
        . '<h3>When an error appears</h3><ol><li>Read the exception name and message on the last line.</li><li>Inspect the named cell and line.</li><li>Change one thing.</li><li>Run again and compare.</li></ol>'
        . '<h3>Definition of done</h3><p>Open <code>00_start_here.ipynb</code>, run every cell in order, change one value and one explanation, save, close, and reopen it. This orientation does not use pandas or data analysis.</p><p style="display:none">PYAI-V8-LAB-GUIDE</p>';
    $content['lesson_name'] = 'Lesson 1: Values, data types, expressions, and output';
    $content['lesson_intro'] = '<p>Distinguish numbers from text, evaluate expressions with arithmetic operators, and display meaningful results.</p>';
    $content['quiz_name'] = 'Knowledge check: Lesson 1: Values, types, expressions, and output';
    $content['quiz_intro'] = '<p><strong>Required learning check:</strong> Check only the values, types, arithmetic operators, precedence, and output taught in this lesson. You may retry and your highest score is kept.</p>';
    $content['lti_name'] = 'Python Lab 01: Values, types, expressions, and output';
    $content['start_lti_name'] = 'Python Lab 00: Getting started with Notebooks';
    $content['narrative_names'] = ['Meet Naledi: One reporting task that grows with the course', 'ナレディの紹介：コースとともに発展する一つの報告業務'];
}

$content['lesson'] = $language === 'ja'
    ? '<div class="python-sample-lesson"><p><strong>学習時間の目安：</strong>2.5時間</p><h3>このレッスンでできるようになること</h3><ul><li>値とデータ型の違いを説明する</li><li>文字列と数値を区別する</li><li>七つの算術演算子を使う</li><li>括弧と優先順位を考えて式の結果を予想する</li><li><code>print()</code>で意味の分かる出力を作る</li></ul><p><strong>最終プロジェクトとの関係：</strong>データの列を正しい型として扱い、割合や合計を計算する基礎になります。</p><h3>値とデータ型</h3><table class="generaltable"><thead><tr><th>型</th><th>例</th><th>用途</th></tr></thead><tbody><tr><td><code>int</code></td><td><code>34</code></td><td>整数の人数や件数</td></tr><tr><td><code>float</code></td><td><code>82.5</code></td><td>小数を含む時間、金額、割合</td></tr><tr><td><code>str</code></td><td><code>"Python"</code></td><td>文字列、名称、説明</td></tr><tr><td><code>bool</code></td><td><code>True</code></td><td>真または偽。第2章で詳しく扱う</td></tr><tr><td><code>None</code></td><td><code>None</code></td><td>値がないことを表す。後の章で扱う</td></tr></tbody></table>'
        . v8_code("print(type(34))\nprint(type(82.5))\nprint(type(\"34\"))")
        . '<h3>算術演算子</h3><table class="generaltable"><thead><tr><th>演算子</th><th>意味</th><th>例</th><th>結果</th></tr></thead><tbody><tr><td><code>+</code></td><td>加算</td><td><code>10 + 3</code></td><td>13</td></tr><tr><td><code>-</code></td><td>減算</td><td><code>10 - 3</code></td><td>7</td></tr><tr><td><code>*</code></td><td>乗算</td><td><code>10 * 3</code></td><td>30</td></tr><tr><td><code>/</code></td><td>除算</td><td><code>10 / 3</code></td><td>3.333…</td></tr><tr><td><code>//</code></td><td>切り捨て除算</td><td><code>10 // 3</code></td><td>3</td></tr><tr><td><code>%</code></td><td>余り</td><td><code>10 % 3</code></td><td>1</td></tr><tr><td><code>**</code></td><td>べき乗</td><td><code>10 ** 2</code></td><td>100</td></tr></tbody></table><p>乗算と除算は加算と減算より先に行われます。迷う場合や意図を明確にしたい場合は括弧を使います。</p>'
        . v8_code("print(2 + 3 * 4)      # 14\nprint((2 + 3) * 4)    # 20")
        . '<h3>文字列と式</h3>' . v8_code("print(7 + 5)\nprint(\"7 + 5\")\nprint(\"参加者数:\", 34)")
        . '<p>引用符の外側にある数値式は計算されます。引用符の内側は文字列としてそのまま表示されます。<code>print()</code>には文字列と数値を別々の引数として渡せます。</p><h3>一緒に実行する例題</h3><p>一つの研修について、予定時間、実施時間、残り時間を計算して表示します。</p>'
        . v8_code("scheduled_hours = 24\ndelivered_hours = 20\nremaining_hours = scheduled_hours - delivered_hours\n\nprint(\"予定時間:\", scheduled_hours)\nprint(\"実施時間:\", delivered_hours)\nprint(\"残り時間:\", remaining_hours)")
        . '<ol><li>実行前に三行の出力を予想します。</li><li>Notebookで実行します。</li><li><code>delivered_hours</code>を18へ変更して再実行します。</li><li>どの出力が、なぜ変わったかをMarkdownセルへ書きます。</li></ol><h3>よくある間違い</h3><ul><li><code>"10" + "3"</code>は数値の13ではなく文字列の<code>"103"</code>になる</li><li><code>10 + "3"</code>は数値と文字列を直接加算できないため<code>TypeError</code>になる</li><li>引用符を閉じ忘れると<code>SyntaxError</code>になる</li><li><code>/</code>と<code>//</code>では結果が異なる</li><li>計算順序が不明確なら括弧を使う</li></ul><h3>応用練習</h3><p>教材を53冊、1箱に12冊ずつ入れます。必要な箱数の考え方、満杯になる箱数、余る冊数を<code>/</code>、<code>//</code>、<code>%</code>を使って確認し、ラベル付きで表示してください。どの値を実務で使うか説明します。</p><p><strong>次へ進む条件：</strong>Notebookのすべての例を実行し、値を変更して結果を説明してから理解度チェックへ進みます。</p><p style="display:none">PYAI-V8-LESSON1</p></div>'
    : '<div class="python-sample-lesson"><p><strong>Estimated study time:</strong> 2.5 hours</p><h3>Capability</h3><ul><li>explain the difference between a value and a data type;</li><li>distinguish text from numbers;</li><li>use all seven arithmetic operators;</li><li>predict expressions using parentheses and precedence;</li><li>produce meaningful output with <code>print()</code>.</li></ul><p><strong>Final-project connection:</strong> these ideas let you interpret column types and calculate totals, differences, and rates correctly.</p><h3>Values and data types</h3><table class="generaltable"><thead><tr><th>Type</th><th>Example</th><th>Typical use</th></tr></thead><tbody><tr><td><code>int</code></td><td><code>34</code></td><td>whole counts</td></tr><tr><td><code>float</code></td><td><code>82.5</code></td><td>decimal hours, costs, and rates</td></tr><tr><td><code>str</code></td><td><code>"Python"</code></td><td>names and explanations</td></tr><tr><td><code>bool</code></td><td><code>True</code></td><td>true/false decisions, developed in Chapter 2</td></tr><tr><td><code>None</code></td><td><code>None</code></td><td>absence of a value, developed later</td></tr></tbody></table>'
        . v8_code("print(type(34))\nprint(type(82.5))\nprint(type(\"34\"))")
        . '<h3>Arithmetic operators</h3><table class="generaltable"><thead><tr><th>Operator</th><th>Meaning</th><th>Example</th><th>Result</th></tr></thead><tbody><tr><td><code>+</code></td><td>addition</td><td><code>10 + 3</code></td><td>13</td></tr><tr><td><code>-</code></td><td>subtraction</td><td><code>10 - 3</code></td><td>7</td></tr><tr><td><code>*</code></td><td>multiplication</td><td><code>10 * 3</code></td><td>30</td></tr><tr><td><code>/</code></td><td>division</td><td><code>10 / 3</code></td><td>3.333…</td></tr><tr><td><code>//</code></td><td>floor division</td><td><code>10 // 3</code></td><td>3</td></tr><tr><td><code>%</code></td><td>remainder</td><td><code>10 % 3</code></td><td>1</td></tr><tr><td><code>**</code></td><td>power</td><td><code>10 ** 2</code></td><td>100</td></tr></tbody></table><p>Multiplication and division happen before addition and subtraction. Use parentheses when the intended order is unclear.</p>'
        . v8_code("print(2 + 3 * 4)      # 14\nprint((2 + 3) * 4)    # 20")
        . '<h3>Text and expressions</h3>' . v8_code("print(7 + 5)\nprint(\"7 + 5\")\nprint(\"Learners:\", 34)")
        . '<p>A numeric expression outside quotes is evaluated. Characters inside quotes are displayed as text. <code>print()</code> can receive text and a number as separate arguments.</p><h3>Guided example</h3><p>Calculate and display scheduled, delivered, and remaining training hours.</p>'
        . v8_code("scheduled_hours = 24\ndelivered_hours = 20\nremaining_hours = scheduled_hours - delivered_hours\n\nprint(\"Scheduled hours:\", scheduled_hours)\nprint(\"Delivered hours:\", delivered_hours)\nprint(\"Remaining hours:\", remaining_hours)")
        . '<ol><li>Predict all three output lines.</li><li>Run the code in the Notebook.</li><li>Change <code>delivered_hours</code> to 18 and rerun.</li><li>Explain in a Markdown cell what changed and why.</li></ol><h3>Common errors</h3><ul><li><code>"10" + "3"</code> produces text <code>"103"</code>, not number 13;</li><li><code>10 + "3"</code> raises <code>TypeError</code> because a number and text cannot be added directly;</li><li>an unclosed quote raises <code>SyntaxError</code>;</li><li><code>/</code> and <code>//</code> produce different results;</li><li>parentheses make intended precedence explicit.</li></ul><h3>Transfer exercise</h3><p>There are 53 books and each box holds 12. Use <code>/</code>, <code>//</code>, and <code>%</code> to investigate required boxes, completely filled boxes, and remaining books. Display labelled results and explain which value the work task needs.</p><p><strong>Before the check:</strong> run every Notebook example, change values, and explain the result.</p><p style="display:none">PYAI-V8-LESSON1</p></div>';

// Refined prototype: keep the first lesson focused on the transferable execution model.
if ($language === 'ja') {
    $content['section0_summary'] = '<p>コンピュータがプログラムを実行する基本モデルを理解し、Notebookを開き、実行、変更、保存、再開できるようにします。</p>';
    $content['chapter1_name'] = '第1章 — プログラミングの基礎と基本データ';
    $content['chapter1_summary'] = '<p>プログラム、値、式、出力から始め、変数と実行時の状態、基本データ型、文字列と入力へ順に進みます。</p><ol><li>1.1 プログラム・値・式・出力</li><li>1.2 変数・代入・プログラムの状態</li><li>1.3 基本データ型・変換・算術演算</li><li>1.4 文字列・入力・書式付き出力</li></ol>';
    $content['topic_name'] = '1.1 プログラム・値・式・出力';
    $content['topic_summary'] = '<p>命令が上から順に実行され、値を使った式が評価され、結果が出力される流れを学びます。データ型の体系と変数は後続レッスンで扱います。</p>';
    $content['guide_intro'] = '<p>Pythonを学ぶ前に、コンピュータがプログラムを実行する仕組みと、このコースの全体像を確認します。</p>';
    $content['guide'] = '<h2>プログラミングとは何か</h2><p>コンピュータは人の目的を推測するのではなく、明示された命令を実行します。プログラムは、入力を受け取り、メモリ上で処理し、画面やファイルへ結果を出すための有限な命令列です。</p><table class="generaltable"><thead><tr><th>要素</th><th>役割</th></tr></thead><tbody><tr><td>入力</td><td>人、ファイル、センサーなどから値を受け取る</td></tr><tr><td>処理</td><td>CPUが命令を実行し、メモリ上の値を計算・比較・変更する</td></tr><tr><td>出力</td><td>結果を画面、Notebook、ファイルなどへ示す</td></tr><tr><td>保存</td><td>電源や実行終了後にも必要なデータをファイル等へ残す</td></tr></tbody></table><h3>Pythonコードが動くまで</h3><ol><li>人がPythonのソースコードを書く</li><li>Pythonインタプリタが文法を読み取り、命令を実行する</li><li>実行中の値や状態はメモリに置かれる</li><li><code>print()</code>などが結果を出力し、保存処理がファイルへ残す</li></ol><p><strong>文法が正しいか、実行できるか、結果が目的に合うかは別の問いです。</strong>この区別はCやJavaなど他言語を学ぶときにも役立ちます。値、変数、条件、繰り返し、関数という考え方は多くの言語に共通しますが、翻訳方法、型の規則、メモリ管理は言語ごとに異なります。</p><h3>Pythonを選ぶ理由</h3><p>Pythonは読みやすい汎用言語で、業務自動化、データ分析、Web、科学技術計算、AIに使われます。このコースではPython全体の基礎を学び、その基礎をデータ分析へつなげます。</p><h3>このコースの学習地図</h3><ol><li>Python Labと実行モデル</li><li>値、変数、基本データ型、文字列</li><li>条件分岐と繰り返し</li><li>コレクション</li><li>関数とテスト</li><li>ファイル、例外、モジュール、ライブラリ</li><li>クラスとオブジェクト</li><li>データ分析</li><li>問いから根拠を作るプロジェクト</li></ol><h3>学び方</h3><p><strong>予想 → 実行 → 確認 → 変更 → 説明</strong>の順で進めます。理解度チェックは何度でも挑戦でき、最高点が記録されます。AI支援は利用できますが、コードを自分で実行、確認、変更、説明してください。</p><p style="display:none">PYAI-V8-CHAPTER0-REFINED</p>';
    $content['lab'] = '<h2>コードはどこで動くのか</h2><p>Moodleは教材と課題を管理し、Python Labはコードを実行します。NotebookのCodeセルを実行すると、PythonインタプリタがPython Labサーバ上で命令を処理し、結果をNotebookへ返します。Notebookファイルは学習者の保存領域に残ります。</p><h3>三つの実行方法</h3><table class="generaltable"><thead><tr><th>方法</th><th>動き</th><th>このコース</th></tr></thead><tbody><tr><td>Notebook</td><td>説明とコードをセルに分け、実行結果も保存できる</td><td><strong>標準</strong></td></tr><tr><td>Console</td><td>一行ずつ入力し、その場で結果を確認する</td><td>短い実験</td></tr><tr><td><code>.py</code>スクリプト</td><td>保存した命令を通常は上から順にまとめて実行する</td><td>仕組みを体験</td></tr></tbody></table><h3>Notebookの操作</h3><ol><li>Python Lab 00を開く</li><li>Codeセルを<kbd>Shift</kbd>+<kbd>Enter</kbd>で実行する</li><li>一か所変更して再実行する</li><li>Markdownセルへ説明を書く</li><li><kbd>Ctrl</kbd>+<kbd>S</kbd>で保存する</li><li>閉じて再度開き、変更を確認する</li></ol><h3>Kernelと保存の違い</h3><p>Kernelの状態は実行中のメモリです。Notebookファイルは保存された記録です。結果が混乱したらKernelを再起動して上からすべて実行します。エラーは最後の行にある名前とメッセージから読みます。</p><p style="display:none">PYAI-V8-LAB-GUIDE-REFINED</p>';
    $content['lesson_name'] = 'レッスン1：プログラム・値・式・出力';
    $content['lesson_intro'] = '<p>命令、値、式、出力の関係を理解し、短いプログラムを上から順に読み、<code>print()</code>で結果を表示します。</p>';
    $content['quiz_name'] = '理解度チェック：レッスン1 プログラム・値・式・出力';
    $content['quiz_intro'] = '<p><strong>必須の理解度チェック：</strong>このレッスンで学んだ命令の順序、値、簡単な式、括弧、明示的な出力だけを確認します。何度でも挑戦でき、最高点が記録されます。</p>';
    $content['lti_name'] = 'Python Lab 01：プログラム・値・式・出力';
    $content['lesson'] = '<div class="python-sample-lesson"><p><strong>学習時間の目安：</strong>2時間</p><h3>このレッスンでできるようになること</h3><ul><li>プログラム、命令、値、式、出力を区別する</li><li>短いプログラムを上から順に追う</li><li><code>+</code>、<code>-</code>、<code>*</code>と括弧を使った簡単な式を評価する</li><li><code>print()</code>で意味の分かる出力を作る</li></ul><p><strong>最終プロジェクトとの関係：</strong>分析コードを一行ずつ読み、計算結果を再現可能な形でNotebookへ残す最初の基礎です。</p><h3>プログラムの最小モデル</h3><p>ソースコードは人が書いた命令です。Pythonインタプリタが命令を読み、式を評価し、必要な結果を出力します。通常の短いプログラムは上から下へ進みます。</p>' . v8_code("print(\"学習センター報告\")\nprint(6 * 4)\nprint(24 - 5)") . '<table class="generaltable"><thead><tr><th>用語</th><th>この例</th><th>意味</th></tr></thead><tbody><tr><td>命令</td><td><code>print(...)</code></td><td>コンピュータに行わせる処理</td></tr><tr><td>値</td><td><code>6</code>、<code>4</code>、文字列</td><td>プログラムが扱うデータそのもの</td></tr><tr><td>式</td><td><code>6 * 4</code></td><td>評価すると一つの値になる記述</td></tr><tr><td>出力</td><td>画面に現れる三行</td><td>プログラムが外部へ示した結果</td></tr></tbody></table><h3>値の種類は操作を決める</h3><p><code>6</code>と<code>"6"</code>は見た目が似ていますが、前者は計算に使う数、後者は文字です。この段階では「値には種類があり、できる操作を決める」とだけ理解します。型名、変換、完全な演算子一覧はレッスン3で体系的に学びます。</p><h3>式と括弧</h3><p>ここでは加算、減算、乗算だけを使います。括弧の内側は先に評価されます。</p>' . v8_code("print(8 + 5)\nprint(8 * 5)\nprint((8 + 2) * 5)") . '<h3>Notebookの表示とプログラムの出力</h3><p>Notebookではセルの最後に式だけを書くと、その値が補助的に表示されます。一方、<code>.py</code>スクリプトでは式を書くだけでは通常表示されません。この教材では、外へ示す結果には<code>print()</code>を使います。これは実行方法が変わっても意図が明確です。</p><h3>一緒に実行する例題</h3><p>午前と午後の参加枠を合計し、定員との差を表示します。変数は次のレッスンで学ぶため、今は値を式へ直接書きます。</p>' . v8_code("print(\"研修参加枠\")\nprint(\"午前:\", 18)\nprint(\"午後:\", 12)\nprint(\"合計:\", 18 + 12)\nprint(\"未使用席:\", 40 - (18 + 12))") . '<ol><li>実行前に五行の出力を予想する</li><li>Notebookで上から実行する</li><li>午前を20へ変え、合計と未使用席を同じ値に直して再実行する</li><li>変更が二か所必要だった理由を書く。次のレッスンでは変数でこの重複をなくす</li></ol><h3>よくある間違い</h3><ul><li>文字を引用符で囲まず、名前として解釈される</li><li>閉じ括弧や引用符を忘れ、文法エラーになる</li><li>Notebookの最終式表示を、常に行われる出力だと思う</li><li>同じ意味の値を複数箇所へ直接書き、変更漏れを起こす</li></ul><h3>応用練習</h3><p>講座を1日3回、5日間実施し、各回の定員は16人です。見出し、総実施回数、総参加可能人数を三行で表示してください。使うのは数値、<code>+</code>、<code>-</code>、<code>*</code>、括弧、<code>print()</code>だけです。</p><h3>次の見通し</h3><ol><li>レッスン2で値に名前を付け、変更に強いプログラムを作る</li><li>レッスン3で基本データ型、変換、演算子を体系的に学ぶ</li><li>レッスン4で文字列、入力、書式付き出力を学ぶ</li></ol><p style="display:none">PYAI-V8-LESSON1-REFINED</p></div>';
} else {
    $content['section0_summary'] = '<p>Understand the basic model by which a computer executes a program, then open, run, change, save, and resume a Notebook.</p>';
    $content['chapter1_name'] = 'Chapter 1 — Programming Foundations and Scalar Values';
    $content['chapter1_summary'] = '<p>Begin with programs, values, expressions, and output; then progress through variables and state, scalar types, and finally strings and input.</p><ol><li>1.1 Programs, values, expressions, and output</li><li>1.2 Variables, assignment, and program state</li><li>1.3 Basic scalar types, conversion, and arithmetic</li><li>1.4 Strings, input, and formatted output</li></ol>';
    $content['topic_name'] = '1.1 Programs, values, expressions, and output';
    $content['topic_summary'] = '<p>Learn how instructions run in order, expressions produce values, and programs produce output. The type system and variables are developed in later lessons.</p>';
    $content['guide_intro'] = '<p>Before learning Python syntax, understand how a computer executes a program and see the complete course map.</p>';
    $content['guide'] = '<h2>What programming means</h2><p>A computer does not infer human intention. It follows explicit instructions. A program is a finite sequence of instructions that accepts input, processes values in memory, and produces output or saved data.</p><table class="generaltable"><thead><tr><th>Part</th><th>Role</th></tr></thead><tbody><tr><td>Input</td><td>Receive values from a person, file, sensor, or other system</td></tr><tr><td>Processing</td><td>The CPU executes instructions that calculate, compare, or change values in memory</td></tr><tr><td>Output</td><td>Present a result on screen, in a Notebook, or to another system</td></tr><tr><td>Storage</td><td>Keep data in a file or service after execution ends</td></tr></tbody></table><h3>From Python source to a result</h3><ol><li>A person writes Python source code.</li><li>The Python interpreter reads the syntax and executes instructions.</li><li>Values and program state exist in memory while the program runs.</li><li><code>print()</code> produces output; file operations create persistent storage.</li></ol><p><strong>Valid syntax, successful execution, and a result that matches the purpose are three different questions.</strong> This distinction transfers to C, Java, and other languages. Values, variables, decisions, loops, and functions are widespread ideas; translation, type rules, and memory management differ between languages.</p><h3>Why Python</h3><p>Python is a readable general-purpose language used for automation, data analysis, web systems, scientific work, and AI. This course teaches a broad Python foundation and then applies it to data analysis.</p><h3>Course map</h3><ol><li>Python Lab and the execution model</li><li>values, variables, scalar types, and strings</li><li>decisions and repetition</li><li>collections</li><li>functions and testing</li><li>files, exceptions, modules, and libraries</li><li>classes and objects</li><li>data analysis</li><li>a question-to-evidence project</li></ol><h3>How to study</h3><p><strong>Predict → Run → Check → Change → Explain.</strong> Learning checks allow retries and retain the highest score. AI assistance is permitted, but run, verify, modify, and explain assisted code.</p><p style="display:none">PYAI-V8-CHAPTER0-REFINED</p>';
    $content['lab'] = '<h2>Where code runs</h2><p>Moodle manages course material and assignments; Python Lab executes code. When a Code cell runs, the Python interpreter processes it on the Python Lab server and returns output to the Notebook. The Notebook file remains in the learner workspace.</p><h3>Three execution methods</h3><table class="generaltable"><thead><tr><th>Method</th><th>Behaviour</th><th>Course use</th></tr></thead><tbody><tr><td>Notebook</td><td>Separates explanation and code into cells and can save visible results</td><td><strong>Standard</strong></td></tr><tr><td>Console</td><td>Runs one entered statement and immediately shows a result</td><td>Short experiments</td></tr><tr><td><code>.py</code> script</td><td>Normally runs saved instructions from top to bottom</td><td>Understand the wider environment</td></tr></tbody></table><h3>Notebook operation</h3><ol><li>Open Python Lab 00.</li><li>Run a Code cell with <kbd>Shift</kbd>+<kbd>Enter</kbd>.</li><li>Change one part and rerun.</li><li>Write an explanation in a Markdown cell.</li><li>Save with <kbd>Ctrl</kbd>+<kbd>S</kbd>.</li><li>Close and reopen the file to confirm persistence.</li></ol><h3>Kernel state versus a saved file</h3><p>Kernel state is working memory during execution. The Notebook file is a saved record. If results become confusing, restart the kernel and run all cells from the top. Read errors from the exception name and message on the last line.</p><p style="display:none">PYAI-V8-LAB-GUIDE-REFINED</p>';
    $content['lesson_name'] = 'Lesson 1: Programs, values, expressions, and output';
    $content['lesson_intro'] = '<p>Relate instructions, values, expressions, and output; trace a short program from top to bottom; and display results with <code>print()</code>.</p>';
    $content['quiz_name'] = 'Knowledge check: Lesson 1: Programs, values, expressions, and output';
    $content['quiz_intro'] = '<p><strong>Required learning check:</strong> Check only instruction order, values, simple expressions, parentheses, and explicit output taught in this lesson. You may retry and your highest score is kept.</p>';
    $content['lti_name'] = 'Python Lab 01: Programs, values, expressions, and output';
    $content['lesson'] = '<div class="python-sample-lesson"><p><strong>Estimated study time:</strong> 2 hours</p><h3>Capability</h3><ul><li>distinguish a program, instruction, value, expression, and output;</li><li>trace a short program from top to bottom;</li><li>evaluate simple expressions using <code>+</code>, <code>-</code>, <code>*</code>, and parentheses;</li><li>produce meaningful output with <code>print()</code>.</li></ul><p><strong>Final-project connection:</strong> this is the first foundation for reading analysis code line by line and leaving reproducible results in a Notebook.</p><h3>A minimal model of a program</h3><p>Source code contains instructions written by people. The Python interpreter reads the instructions, evaluates expressions, and produces requested output. A short ordinary program normally proceeds from top to bottom.</p>' . v8_code("print(\"Learning centre report\")\nprint(6 * 4)\nprint(24 - 5)") . '<table class="generaltable"><thead><tr><th>Term</th><th>In this example</th><th>Meaning</th></tr></thead><tbody><tr><td>Instruction</td><td><code>print(...)</code></td><td>A requested operation</td></tr><tr><td>Value</td><td><code>6</code>, <code>4</code>, and the text</td><td>Data handled by the program</td></tr><tr><td>Expression</td><td><code>6 * 4</code></td><td>A description that evaluates to one value</td></tr><tr><td>Output</td><td>The three displayed lines</td><td>A result made visible outside the running calculation</td></tr></tbody></table><h3>A value kind determines possible operations</h3><p><code>6</code> and <code>"6"</code> look similar, but one is a number for calculation and the other is text. For now, retain one idea: every value has a kind that determines sensible operations. Lesson 3 teaches type names, conversion, and the complete arithmetic reference systematically.</p><h3>Expressions and parentheses</h3><p>This lesson uses addition, subtraction, and multiplication only. An expression in parentheses is evaluated first.</p>' . v8_code("print(8 + 5)\nprint(8 * 5)\nprint((8 + 2) * 5)") . '<h3>Notebook display and program output</h3><p>A Notebook helpfully displays a value when an expression is the final line of a cell. A <code>.py</code> script normally does not display an expression by itself. Use <code>print()</code> for intended output; that intent remains clear across Notebook, Console, and script execution.</p><h3>Guided example</h3><p>Display morning and afternoon places, their total, and the difference from capacity. Variables come next, so values are written directly in the expressions for now.</p>' . v8_code("print(\"Workshop places\")\nprint(\"Morning:\", 18)\nprint(\"Afternoon:\", 12)\nprint(\"Total:\", 18 + 12)\nprint(\"Unused seats:\", 40 - (18 + 12))") . '<ol><li>Predict all five output lines.</li><li>Run from the top.</li><li>Change the morning value to 20 in both relevant expressions and rerun.</li><li>Explain why two edits were necessary. Lesson 2 removes this duplication with a variable.</li></ol><h3>Common errors</h3><ul><li>omitting quotes around text, so Python treats it as a name;</li><li>forgetting a closing quote or parenthesis, producing a syntax error;</li><li>mistaking the Notebook final-expression display for output that every program produces;</li><li>writing the same meaning in several places and missing one during a change.</li></ul><h3>Transfer exercise</h3><p>A course runs three sessions per day for five days, with 16 places per session. Display a heading, the total sessions, and the total available places on three lines. Use only numeric values, <code>+</code>, <code>-</code>, <code>*</code>, parentheses, and <code>print()</code>.</p><h3>What comes next</h3><ol><li>Lesson 2 gives values names and introduces program state.</li><li>Lesson 3 systematically teaches scalar types, conversion, and arithmetic.</li><li>Lesson 4 develops strings, input, and formatted output.</li></ol><p style="display:none">PYAI-V8-LESSON1-REFINED</p></div>';
}

// Evidence-complete additions governed by chapter-0-1-concept-map-v2.json.
if ($language === 'ja') {
    $content['guide'] = str_replace(
        'AI支援は利用できますが、コードを自分で実行、確認、変更、説明してください。',
        '',
        $content['guide']
    );
    $chapter0evidence = '<h3>問題を三種類に分ける</h3>'
        . '<p><strong>文法の問題</strong>はPythonが命令を読み取れない状態、<strong>実行時の問題</strong>は読み取れた処理が実行中に失敗する状態、<strong>誤った結果</strong>はコードは動くが目的に合わない状態です。Python Lab 00で三種類を一つずつ確認します。</p>'
        . '<h3>Kernelの状態を確認する</h3><p>Kernelを再起動して途中のセルだけを実行し、隠れた状態への依存を確認します。その後、再起動して全セルを上から実行できる状態へ戻します。</p>'
        . '<h3>第0章の完了証拠</h3><p>Codeセルの変更、Markdownの説明、三種類の問題の区別、Kernel再起動後の全セル実行、保存後の再表示をNotebookへ残します。Python Lab自体に問題がある場合は環境を変更せず、活動名と最後のエラー行を講師または管理者へ伝えます。</p>'
        . '<p style="display:none">PYAI-V8-CHAPTER0-EVIDENCE</p>';
    $content['lab'] = str_replace(
        '<p style="display:none">PYAI-V8-LAB-GUIDE-REFINED</p>',
        $chapter0evidence . '<p style="display:none">PYAI-V8-LAB-GUIDE-REFINED</p>',
        $content['lab']
    );
    $lesson1evidence = '<h3>動くコードも目的に照らして確認する</h3>'
        . '<p><code>(5 + 3) * 16</code>と<code>5 * 3 * 16</code>はどちらも動きますが、「5日間、1日3回、各回16人」を表すのは後者です。Notebookで両方を実行し、文法だけでなく課題との一致を説明します。</p>'
        . '<p>応用練習の解答から、命令、二つのリテラル値、一つの式、出力を指し示し、すべての出力へ分かるラベルを付けます。</p>'
        . '<p style="display:none">PYAI-V8-LESSON1-EVIDENCE</p>';
    $content['lesson'] = str_replace(
        '<p style="display:none">PYAI-V8-LESSON1-REFINED</p>',
        $lesson1evidence . '<p style="display:none">PYAI-V8-LESSON1-REFINED</p>',
        $content['lesson']
    );
    $content['teacher_name'] = '教師用ガイド（学習者には非表示）';
    $content['teacher_support'] = '<h2>第0章・レッスン1 指導メモ</h2>'
        . '<h3>完了を確認する証拠</h3><ul><li>CodeセルとMarkdownセルの両方がある</li><li>文法、実行時、誤結果の三種類を本人が説明できる</li><li>Kernel再起動後に全セルを上から実行できる</li><li>保存したNotebookを再度開ける</li></ul>'
        . '<h3>答えを与えない支援</h3><ul><li>Markdownへコードを入力した場合：「今のセルは説明用と実行用のどちらですか」と尋ねる</li><li>途中セルだけ動かない場合：「Kernel再起動後、この名前を作ったセルは実行済みですか」と尋ねる</li><li>最初のエラーで止まる場合：Tracebackの最後の行から、例外名とメッセージを読ませる</li><li>引用符内を計算だと思う場合：<code>print("3 + 4")</code>と<code>print(3 + 4)</code>を並べて予想させる</li><li>式が動けば正しいと思う場合：数値が課題文のどの量を表すか対応付けさせる</li><li>重複値の一方だけを直した場合：二つの出力が同時に正しいかを確認させる</li></ul>'
        . '<h3>合格後の確認</h3><p>選択肢を暗記したかではなく、値を一つ変更した例を実行前に予想し、結果を説明できるか確認します。</p>'
        . '<p style="display:none">PYAI-V8-TEACHER-CH0-L1</p>';
} else {
    $content['guide'] = str_replace(
        'AI assistance is permitted, but run, verify, modify, and explain assisted code.',
        '',
        $content['guide']
    );
    $chapter0evidence = '<h3>Classify three kinds of problem</h3>'
        . '<p>A <strong>syntax problem</strong> prevents Python from reading an instruction; a <strong>runtime problem</strong> fails during an understood operation; a <strong>wrong result</strong> runs but does not meet the purpose. Python Lab 00 lets you observe each kind separately.</p>'
        . '<h3>Check kernel state</h3><p>Restart the kernel and run a later cell alone to expose a hidden-state dependency. Then restart and run all cells from the top to restore a reproducible Notebook.</p>'
        . '<h3>Chapter 0 completion evidence</h3><p>Leave evidence of a changed Code cell, a Markdown explanation, the three problem types, a clean restart-and-run-all, and a saved/reopened file. If Python Lab itself fails, do not change the managed environment; report the activity name and final error line to the teacher or administrator.</p>'
        . '<p style="display:none">PYAI-V8-CHAPTER0-EVIDENCE</p>';
    $content['lab'] = str_replace(
        '<p style="display:none">PYAI-V8-LAB-GUIDE-REFINED</p>',
        $chapter0evidence . '<p style="display:none">PYAI-V8-LAB-GUIDE-REFINED</p>',
        $content['lab']
    );
    $lesson1evidence = '<h3>Check working code against its purpose</h3>'
        . '<p>Both <code>(5 + 3) * 16</code> and <code>5 * 3 * 16</code> run, but only the second represents “five days, three sessions per day, sixteen places per session”. Run both in the Notebook and explain why valid syntax is not enough.</p>'
        . '<p>In the transfer solution, point to an instruction, two literal values, an expression, and its output. Give every output line a label another reader can understand.</p>'
        . '<p style="display:none">PYAI-V8-LESSON1-EVIDENCE</p>';
    $content['lesson'] = str_replace(
        '<p style="display:none">PYAI-V8-LESSON1-REFINED</p>',
        $lesson1evidence . '<p style="display:none">PYAI-V8-LESSON1-REFINED</p>',
        $content['lesson']
    );
    $content['teacher_name'] = 'Teacher guide (hidden from students)';
    $content['teacher_support'] = '<h2>Chapter 0 and Lesson 1 facilitation notes</h2>'
        . '<h3>Completion evidence</h3><ul><li>both Code and Markdown cells are present;</li><li>the learner explains syntax, runtime, and wrong-result problems;</li><li>restart-and-run-all succeeds from a clean kernel;</li><li>the saved Notebook can be reopened.</li></ul>'
        . '<h3>Prompts that do not give away the answer</h3><ul><li>Code typed into Markdown: “Is this cell for explanation or execution?”</li><li>A later cell fails after restart: “Has the cell that creates this name run?”</li><li>The learner stops at an error: ask for the exception name and message on the final traceback line.</li><li>Quoted arithmetic is treated as calculation: predict <code>print("3 + 4")</code> beside <code>print(3 + 4)</code>.</li><li>Running code is assumed correct: ask which quantity in the task each number represents.</li><li>Only one repeated literal is changed: ask whether both displayed results can still be true.</li></ul>'
        . '<h3>After a passing check</h3><p>Confirm transfer, not option-position memory: change one value, ask for a prediction, run it, and request an explanation.</p>'
        . '<p style="display:none">PYAI-V8-TEACHER-CH0-L1</p>';
}
$course->summary = $content['course_summary'];
$course->summaryformat = FORMAT_HTML;
$course->timemodified = time();
$DB->update_record('course', $course);

$section0 = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
course_update_section($course, $section0, ['name' => $content['section0_name'], 'summary' => $content['section0_summary'], 'summaryformat' => FORMAT_HTML]);

$guide = v8_find_record('page', $course->id, ['Start here: course guide', 'はじめに：コースガイド', $content['guide_name']]);
v8_update_page($guide, $content['guide_name'], $content['guide_intro'], $content['guide']);

$labguide = v8_find_record('page', $course->id, ['Responsible AI: Ask, Read, Run, Check, Modify, Explain', '責任あるAI利用：質問・読解・実行・確認・修正・説明', $content['lab_name']]);
v8_update_page($labguide, $content['lab_name'], $content['lab_intro'], $content['lab']);

$teacher = v8_find_record('page', $course->id, [
    'Teacher guide (hidden from students)',
    '教師用ガイド（学習者には非表示）',
    $content['teacher_name'],
]);
$teachercontent = v8_upsert_marked_section(
    $teacher->content,
    'PYAI-V8-TEACHER-CH0-L1',
    $content['teacher_support']
);
v8_update_page(
    $teacher,
    $content['teacher_name'],
    (string) $teacher->intro,
    $teachercontent
);
$teachercm = get_coursemodule_from_instance(
    'page',
    $teacher->id,
    $course->id,
    false,
    MUST_EXIST
);
set_coursemodule_visible($teachercm->id, 0);

$chapter1names = ['Chapter 1 — Python Programming Foundations', '第1章 — Pythonプログラミングの基礎', 'Chapter 1 — Values, Data Types, and Expressions', '第1章 — 値・データ型・式', $content['chapter1_name']];
$chapter1 = null;
foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
    if ($section && empty($section->component) && in_array($section->name, $chapter1names, true)) {
        $chapter1 = $DB->get_record('course_sections', ['id' => $section->id], '*', MUST_EXIST);
        break;
    }
}
if (!$chapter1) {
    throw new moodle_exception('Chapter 1 section not found.');
}
course_update_section($course, $chapter1, ['name' => $content['chapter1_name'], 'summary' => $content['chapter1_summary'], 'summaryformat' => FORMAT_HTML]);

$subsection = v8_find_record('subsection', $course->id, ['1.1 Programs, values, and output', '1.1 プログラム・値・出力', '1.1 Values, types, expressions, and output', '1.1 値・データ型・式・出力', $content['topic_name']]);
$subsection->name = $content['topic_name'];
$subsection->timemodified = time();
$DB->update_record('subsection', $subsection);
$delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $delegated, ['name' => $content['topic_name'], 'summary' => $content['topic_summary'], 'summaryformat' => FORMAT_HTML]);

$lesson = v8_find_record('page', $course->id, ['Lesson 1: Your first Python program', 'レッスン1：初めてのPythonプログラム', 'Lesson 1: Values, data types, expressions, and output', 'レッスン1：値・データ型・式・出力', $content['lesson_name']]);
v8_update_page($lesson, $content['lesson_name'], $content['lesson_intro'], $content['lesson']);

$lessonlti = v8_find_record('lti', $course->id, ['Python Lab 01: Programs, values, and output', 'Python Lab 01：プログラム・値・出力', 'Python Lab 01: Values, types, expressions, and output', 'Python Lab 01：値・データ型・式・出力', $content['lti_name']]);
$lessonlti->name = $content['lti_name'];
if ($language === 'ja') {
    $lessonlti->toolurl = preg_replace('~/hub/user-redirect/lab/tree/.*$~', '/hub/user-redirect/lab/tree/ja/01_programs_values_output.ipynb', $lessonlti->toolurl);
}
$lessonlti->timemodified = time();
$DB->update_record('lti', $lessonlti);

$startlti = v8_find_record('lti', $course->id, ['Python Lab — Run and save your code', 'Python Lab — コードを実行して保存する', 'Python Lab — コードを実行して保存', $content['start_lti_name']]);
$startlti->name = $content['start_lti_name'];
if ($language === 'ja') {
    $startlti->toolurl = preg_replace('~/hub/user-redirect/lab/tree/.*$~', '/hub/user-redirect/lab/tree/ja/00_start_here.ipynb', $startlti->toolurl);
}
$startlti->timemodified = time();
$DB->update_record('lti', $startlti);

foreach ($content['narrative_names'] as $narrativename) {
    if ($narrative = $DB->get_record('page', ['course' => $course->id, 'name' => $narrativename])) {
        $cm = get_coursemodule_from_instance('page', $narrative->id, $course->id, false, MUST_EXIST);
        set_coursemodule_visible($cm->id, 0);
    }
}

$quiz = v8_find_record('quiz', $course->id, [
    $content['quiz_name'],
    'Knowledge check: Lesson 1: Your first Python program',
    '理解度チェック：レッスン1 初めてのPythonプログラム',
    'Knowledge check: Lesson 1: Values, types, expressions, and output',
    '理解度チェック：レッスン1 値・データ型・式・出力',
]);
$attemptcount = (int) $DB->count_records('quiz_attempts', ['quiz' => $quiz->id]);
$sourcequiztoarchive = null;
$sourcecmtoarchive = null;
if ($attemptcount > 0) {
    // Preserve the attempted activity. Create a new empty quiz from the same
    // configuration without using backup/restore and archive v1 only after
    // all v2 questions have been saved successfully.
    $sourcequiztoarchive = $quiz;
    $sourcecmtoarchive = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $moduleinfo = clone $quiz;
    unset($moduleinfo->id);
    $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST);
    $moduleinfo->modulename = 'quiz';
    $moduleinfo->section = (int) $delegated->section;
    $moduleinfo->name = $content['quiz_name'];
    $moduleinfo->intro = $content['quiz_intro'];
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->visible = 1;
    $moduleinfo->visibleoncoursepage = 1;
    $moduleinfo->groupmode = (int) $sourcecmtoarchive->groupmode;
    $moduleinfo->groupingid = (int) $sourcecmtoarchive->groupingid;
    $moduleinfo->completion = (int) $sourcecmtoarchive->completion;
    $moduleinfo->completionview = (int) $sourcecmtoarchive->completionview;
    $moduleinfo->completiongradeitemnumber = 0;
    $moduleinfo->completionpassgrade = 1;
    $moduleinfo->completionexpected = 0;
    $moduleinfo->showdescription = 0;
    $moduleinfo->password = '';
    $moduleinfo->quizpassword = '';
    $moduleinfo->precreateattempts = 0;
    $created = add_moduleinfo($moduleinfo, $course);
    $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
}
$quizsettings = \mod_quiz\quiz_settings::create($quiz->id);
$structure = \mod_quiz\structure::create_for_quiz($quizsettings);
$slots = array_reverse($structure->get_slots());
foreach ($slots as $slot) {
    $structure->remove_slot($slot->slot);
}

$context = context_course::instance($course->id);
// Replace the earlier broad type/operator check with questions limited to Lesson 1.
$questions = $language === 'ja' ? [
    v8_question('L1R-01', 'このレッスンでいう「命令」に最もよく当てはまるものはどれですか。', [['コンピュータに行わせる処理を記したもの', '正解です。命令は実行してほしい処理を記述します。'], ['画面に出た結果だけ', 'それは出力です。'], ['数値だけ', '数値は値の一種です。'], ['Notebookのファイル名', 'ファイル名そのものは命令ではありません。']], 0, '命令はコンピュータに行わせる処理を明示したものです。'),
    v8_question('L1R-02', '<code>print("A")</code>、<code>print("B")</code>をこの順に実行すると、通常どう表示されますか。', [['Bの後にA', '短い通常のプログラムは上から順に進みます。'], ['Aの後にB', '正解です。上の命令が先です。'], ['Aだけ', '二つのprintが実行されます。'], ['何も表示されない', 'printは明示的に出力します。']], 1, '命令は通常、上から下へ実行されます。'),
    v8_question('L1R-03', '式の説明として最も適切なものはどれですか。', [['評価すると一つの値になる記述', '正解です。'], ['必ず文字列を表示する命令', '表示はprintなどの役割です。'], ['保存済みNotebookだけ', 'Notebookはファイル形式です。'], ['エラーの別名', '式は正しく評価できる場合があります。']], 0, '式は評価されて一つの値になります。'),
    v8_question('L1R-04', '<code>print("3 + 4")</code>は何を表示しますか。', [['7', '引用符内は計算式ではなく文字です。'], ['3 + 4', '正解です。引用符内の文字が表示されます。'], ['34', '二つの文字列を連結しているコードではありません。'], ['何も表示しない', 'printは文字を出力します。']], 1, '引用符内は文字として扱われます。'),
    v8_question('L1R-05', '<code>print(3 + 4)</code>は何を表示しますか。', [['3 + 4', '引用符がないため数値式として評価されます。'], ['34', '文字列の連結ではありません。'], ['7', '正解です。式を評価した値がprintへ渡されます。'], ['エラー', 'この式とprintは有効です。']], 2, '3 + 4が先に7へ評価され、7が出力されます。'),
    v8_question('L1R-06', '<code>print(6 * 4)</code>の出力はどれですか。', [['10', 'これは加算の結果です。'], ['24', '正解です。'], ['64', '数値を並べた文字ではありません。'], ['2', 'この式は除算ではありません。']], 1, '*は乗算です。'),
    v8_question('L1R-07', '<code>print((2 + 3) * 4)</code>の出力はどれですか。', [['14', '括弧内を先に評価します。'], ['20', '正解です。2 + 3の後で4倍します。'], ['9', '4は加算ではなく乗算されています。'], ['24', '式を二段階で追ってください。']], 1, '括弧内の2 + 3が5になり、5 * 4は20です。'),
    v8_question('L1R-08', 'Notebookと<code>.py</code>スクリプトの両方で、意図した結果を明示的に表示する方法はどれですか。', [['式だけを書く', 'Notebookは最終式を補助表示しますが、通常のスクリプトでは表示されません。'], ['printを使う', '正解です。出力する意図が明確です。'], ['Markdownだけを書く', 'Markdownは説明用です。'], ['ファイル名を変更する', '出力方法は変わりません。']], 1, '外部へ示す結果にはprintを使うと実行方法が変わっても明確です。'),
    v8_question('L1R-09', '<code>print("参加者数: 18)</code>で末尾の引用符を忘れた場合、最初に疑うものはどれですか。', [['文法の誤り', '正解です。文字列が閉じていません。'], ['計算結果の丸め', '計算は行っていません。'], ['CSVの欠損値', 'このコードはCSVを使っていません。'], ['pandasの版', 'pandasを使っていません。']], 0, '閉じ引用符や閉じ括弧の忘れは、まず文法を確認します。'),
    v8_question('L1R-10', '同じ午前参加者数を二つの式へ直接書き、後で一方だけ変更したときの問題は何ですか。', [['二つの出力が矛盾する可能性がある', '正解です。次のレッスンでは値に名前を付けて重複を減らします。'], ['Pythonが必ず自動修正する', 'Pythonは人の意図を推測しません。'], ['すべて文字列になる', '変更漏れと値の種類は別問題です。'], ['Notebookを保存できなくなる', '保存機能とは関係ありません。']], 0, '同じ意味を複数箇所へ直接書くと変更漏れが起きます。'),
] : [
    v8_question('L1R-01', 'Which best describes an instruction in this lesson?', [['A written operation for the computer to perform', 'Correct: it states requested work.'], ['Only a displayed result', 'That is output.'], ['Only a number', 'A number is one kind of value.'], ['A Notebook filename', 'A filename is not itself an instruction.']], 0, 'An instruction explicitly states an operation to perform.'),
    v8_question('L1R-02', 'If <code>print("A")</code> is followed by <code>print("B")</code>, what normally appears?', [['B then A', 'A short ordinary program proceeds from the top.'], ['A then B', 'Correct: the upper instruction runs first.'], ['Only A', 'Both print instructions run.'], ['Nothing', 'print explicitly produces output.']], 1, 'Instructions normally execute from top to bottom.'),
    v8_question('L1R-03', 'Which best defines an expression?', [['A description that evaluates to one value', 'Correct.'], ['An instruction that must display text', 'Display is a separate concern.'], ['Only a saved Notebook', 'A Notebook is a file format.'], ['Another word for an error', 'Many expressions evaluate successfully.']], 0, 'An expression is evaluated to produce one value.'),
    v8_question('L1R-04', 'What does <code>print("3 + 4")</code> display?', [['7', 'Characters inside quotes are not calculated.'], ['3 + 4', 'Correct: the quoted characters are text.'], ['34', 'This code is not joining two strings.'], ['Nothing', 'print displays the text.']], 1, 'Characters inside quotes are treated as text.'),
    v8_question('L1R-05', 'What does <code>print(3 + 4)</code> display?', [['3 + 4', 'Without quotes, this is a numeric expression.'], ['34', 'This is not string joining.'], ['7', 'Correct: the evaluated value is passed to print.'], ['An error', 'This is a valid expression and print call.']], 2, 'The expression evaluates to 7 before print displays it.'),
    v8_question('L1R-06', 'What does <code>print(6 * 4)</code> display?', [['10', 'That would be addition.'], ['24', 'Correct.'], ['64', 'These are numbers, not written characters.'], ['2', 'The expression is not division.']], 1, '* performs multiplication.'),
    v8_question('L1R-07', 'What does <code>print((2 + 3) * 4)</code> display?', [['14', 'Evaluate the parentheses first.'], ['20', 'Correct: add first, then multiply by four.'], ['9', 'Four is multiplied, not added.'], ['24', 'Trace the two stages again.']], 1, 'The parentheses produce 5, then 5 * 4 produces 20.'),
    v8_question('L1R-08', 'How do you explicitly display intended output in both a Notebook and a <code>.py</code> script?', [['Write only an expression', 'A Notebook may helpfully display a final expression, but a normal script does not.'], ['Use print', 'Correct: the output intention is explicit.'], ['Write only Markdown', 'Markdown is explanation, not Python output.'], ['Rename the file', 'That does not produce output.']], 1, 'Use print for output whose intent should remain clear across execution methods.'),
    v8_question('L1R-09', 'If a closing quote is missing in <code>print("Learners: 18)</code>, what should you suspect first?', [['A syntax problem', 'Correct: the string is not closed.'], ['Rounding', 'There is no calculation here.'], ['Missing CSV data', 'This code does not use CSV.'], ['The pandas version', 'This code does not use pandas.']], 0, 'A missing closing quote or parenthesis is a syntax problem.'),
    v8_question('L1R-10', 'The same morning count is written directly in two expressions. Later, only one is changed. What is the risk?', [['The two outputs can contradict each other', 'Correct: Lesson 2 gives the value one name to reduce duplication.'], ['Python always corrects it automatically', 'Python does not infer the intended relationship.'], ['Every value becomes text', 'Duplication and value kinds are different issues.'], ['The Notebook cannot be saved', 'This does not affect saving.']], 0, 'Repeated meanings can produce missed edits; variables will address this next.'),
];
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
if (!$category) {
    $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
    $category = reset($categories);
}
if (!$category) {
    throw new moodle_exception('Question category not found.');
}

$quiz->name = $content['quiz_name'];
$quiz->intro = $content['quiz_intro'];
$quiz->introformat = FORMAT_HTML;
$quiz->timemodified = time();
$DB->update_record('quiz', $quiz);
foreach ($questions as $questiondata) {
    $question = v8_save_question($category->id, $context->id, $shortname . ' v2: ', $questiondata, $language);
    quiz_add_quiz_question($question->id, $quiz, 0, 10);
}
$DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
$quizsettings = \mod_quiz\quiz_settings::create($quiz->id);
$quizsettings->get_grade_calculator()->recompute_quiz_sumgrades();

if ($sourcequiztoarchive && $sourcecmtoarchive) {
    $sourcequiztoarchive->name = ($language === 'ja' ? '[旧版受験履歴・非表示] ' : '[earlier attempt archive - hidden] ')
        . $sourcequiztoarchive->name;
    $sourcequiztoarchive->timemodified = time();
    $DB->update_record('quiz', $sourcequiztoarchive);
    set_coursemodule_visible($sourcecmtoarchive->id, 0);
    $newcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $DB->set_field('course_completion_criteria', 'moduleinstance', $newcm->id, [
        'course' => $course->id,
        'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
        'moduleinstance' => $sourcecmtoarchive->id,
    ]);
}

rebuild_course_cache($course->id, true);

echo json_encode([
    'upgraded' => true,
    'version' => 8,
    'prototype' => 'Chapter 0 and Chapter 1 lesson 1',
    'course_id' => (int) $course->id,
    'shortname' => $shortname,
    'language' => $language,
    'lesson_1_questions_replaced' => count($questions),
    'narrative_page_hidden' => true,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
