<?php
// Rewrite Chapter 0 as a continuous beginner-oriented introduction.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
\core\session\manager::set_user(get_admin());

function v11_find_page(int $courseid, array $names): stdClass {
    global $DB;
    foreach ($names as $name) {
        if ($page = $DB->get_record('page', ['course' => $courseid, 'name' => $name])) {
            return $page;
        }
    }
    throw new moodle_exception('Chapter 0 page not found: ' . implode(' / ', $names));
}

function v11_update_page(stdClass $page, string $name, string $intro, string $content): void {
    global $DB;
    $page->name = $name;
    $page->intro = $intro;
    $page->introformat = FORMAT_HTML;
    $page->content = $content;
    $page->contentformat = FORMAT_HTML;
    $page->timemodified = time();
    $DB->update_record('page', $page);
}

function v11_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

if ($language === 'ja') {
    $sectionname = '第0章 — PythonとPython Labを始める';
    $sectionsummary = '<p>プログラムが動く基本的な仕組みを知り、このコースの標準環境であるNotebookを実行、変更、保存、再開します。</p>';
    $guidename = 'はじめに：Python学習の全体地図';
    $guideintro = '<p>Pythonを学ぶ意味とコース全体の道筋をつかんでから、最初のコードへ進みます。</p>';
    $guide = '<div class="python-sample-lesson"><h2>コンピュータに仕事を頼むということ</h2>'
        . '<p>私たちは日常の仕事なら、目的を伝えるだけで相手が状況を補ってくれると期待できます。しかし、コンピュータは人の意図を推測しません。何を、どの順番で、どのように処理するかを、実行できる命令として伝える必要があります。その命令をまとめたものがプログラムです。</p>'
        . '<p>多くのプログラムは、値を受け取り、処理し、結果を外へ示します。たとえば学習センターの月次報告なら、登録者数と修了者数を入力として受け取り、修了率を計算し、表やグラフとして出力します。後で再利用するデータは、画面に表示するだけでなくファイルなどへ保存します。</p>'
        . '<table class="generaltable"><thead><tr><th>働き</th><th>月次報告の例</th></tr></thead><tbody><tr><td>入力</td><td>登録者数、修了者数、実施時間を受け取る</td></tr><tr><td>処理</td><td>合計、差、割合を計算する</td></tr><tr><td>出力</td><td>結果を画面、表、グラフへ示す</td></tr><tr><td>保存</td><td>NotebookやCSVとして後から使える形で残す</td></tr></tbody></table>'
        . '<p>Pythonは、このような命令を比較的読みやすく書ける汎用プログラミング言語です。業務の自動化、データ分析、Webシステム、科学技術計算など、同じ基礎から多くの方向へ進めます。このコースではPythonの一部分だけを暗記するのではなく、別の言語を学ぶときにも役立つ、値、処理の順序、条件、繰り返し、関数、データの持ち方という考え方を身につけます。</p>'
        . '<h3>Pythonコードから結果が生まれるまで</h3>'
        . '<p>人が書いたPythonのソースコードは、Pythonインタプリタによって読み取られ、命令として実行されます。実行中の値はメモリに置かれ、<code>print()</code>を使えば結果を画面へ出せます。ファイルへ書き込めば、実行が終わった後にも結果を残せます。</p>'
        . '<p>ここで三つの問いを区別しておきます。コードの<strong>文法は正しいか</strong>、そのコードを<strong>最後まで実行できるか</strong>、得られた結果が<strong>目的に合っているか</strong>は、別々の問題です。エラーが出ないコードでも計算方法が目的と違えば、正しいプログラムとはいえません。この区別は、コース全体を通して何度も使います。</p>'
        . '<h3>基礎からデータ分析へ進む道筋</h3>'
        . '<p>最初にPython Labの使い方を覚えた後、値と変数から始め、条件分岐、繰り返し、コレクション、関数へ進みます。続いてファイル、例外、モジュールとライブラリ、クラスとオブジェクトを学びます。これらを土台として表形式データを確認、整形、集計、可視化し、最後に一つの問いへ根拠を示す分析プロジェクトを完成させます。</p>'
        . '<table class="generaltable"><thead><tr><th>章</th><th>学ぶこと</th><th>次へつながる力</th></tr></thead><tbody><tr><td>第0章</td><td>Python Labと実行の仕組み</td><td>同じ環境で実行結果を再現する</td></tr><tr><td>第1〜2章</td><td>値、変数、条件、繰り返し</td><td>処理の流れを作る</td></tr><tr><td>第3〜6章</td><td>コレクション、関数、ファイル、例外、クラス</td><td>整理され、変更しやすいプログラムを作る</td></tr><tr><td>第7章</td><td>表形式データの分析</td><td>データを根拠へ変える</td></tr><tr><td>第8章</td><td>最終プロジェクト</td><td>問い、分析、説明を一つの成果物にまとめる</td></tr></tbody></table>'
        . '<h3>手を動かして理解する</h3>'
        . '<p>コードを読むだけでは、動きは十分に身につきません。このコースでは、まず結果を予想し、実行して確かめ、一部を変更し、変化した理由を説明します。理解度チェックは一度で選別する試験ではなく、誤解を見つけて学び直すためのものです。何度でも挑戦でき、90点で合格した後も100点を目指せます。</p>'
        . '<p>次のページでは、コードを書く前に環境の準備で止まらないよう、MoodleからPython Labを開き、Notebookを保存して再開するところまで実際に行います。</p>'
        . '<p style="display:none">PYAI-V11-CHAPTER0-GUIDE</p></div>';

    $labname = 'Python Labの使い方：Notebook・Console・スクリプト';
    $labintro = '<p>MoodleからPython Labを開き、コードの実行結果と自分の説明をNotebookへ保存します。</p>';
    $lab = '<div class="python-sample-lesson"><h2>このコースで使うPython環境</h2>'
        . '<p>Moodleには説明、理解度チェック、課題がありますが、PythonコードそのものはPython Labで実行します。Moodleの「Python Lab 00」を開くと、学習者ごとの作業領域にある<code>00_start_here.ipynb</code>へ進みます。共用PCを使う場合も、ファイルはブラウザで開いたサーバー側の領域へ保存されるため、PCごとにPythonをインストールする必要はありません。</p>'
        . '<p>Pythonにはいくつかの実行方法があります。Consoleでは入力した命令を一つずつ試せます。<code>.py</code>スクリプトでは、保存した命令を通常は上から順にまとめて実行します。Notebookでは説明とコードをセルに分け、途中結果も一緒に残せます。学び直すときに考え方と結果を追えるため、このコースではNotebookを標準にします。</p>'
        . '<table class="generaltable"><thead><tr><th>方法</th><th>向いていること</th><th>このコースでの使い方</th></tr></thead><tbody><tr><td>Console（対話モード）</td><td>短い式や一つの命令をすぐ試す</td><td>必要に応じた小さな実験</td></tr><tr><td><code>.py</code>スクリプト</td><td>保存した一連の命令を実行する</td><td>一般的な実行方法を知る</td></tr><tr><td>Notebook</td><td>説明、コード、結果を一つにまとめる</td><td><strong>例題、練習、提出物の標準</strong></td></tr></tbody></table>'
        . '<h3>最初のセルを実行する</h3>'
        . '<p>Notebookには、文章を書くMarkdownセルと、Pythonを実行するCodeセルがあります。Codeセルを選んで<kbd>Shift</kbd>+<kbd>Enter</kbd>を押すと、Pythonがセルの内容を実行し、その下に結果を返します。次のコードを実行すると、文字列と計算結果が二行に表示されます。</p>'
        . v11_code("print(\"Python Lab is ready\")\nprint(3 + 4)")
        . '<p>一行目の引用符で囲まれた部分は表示する文字です。二行目の<code>3 + 4</code>は、表示する前に計算されます。今は文法を暗記する必要はありません。値を一つ変えて再実行し、結果が変わることを確かめてください。</p>'
        . '<h3>実行中の状態と保存されたNotebook</h3>'
        . '<p>Codeセルを実行すると、PythonのKernelが値をメモリに保持します。セルの左に現れる番号は、画面上の並び順ではなく実際に実行した順番です。そのため、下のセルを先に実行すると、上から読んだだけでは再現できない状態になることがあります。</p>'
        . '<p>結果が説明と合わなくなったら、Kernelを再起動し、すべてのセルを上から実行します。これで、最初から同じ結果を再現できるか確認できます。一方、<kbd>Ctrl</kbd>+<kbd>S</kbd>で保存されるNotebookファイルには、コード、説明、表示結果が残ります。Kernelの一時的な状態と、保存されたファイルは同じものではありません。</p>'
        . '<h3>エラーは失敗ではなく手掛かりになる</h3>'
        . '<p>たとえば引用符を閉じ忘れると、Pythonは命令を読み取れず<code>SyntaxError</code>を示します。文法を読み取れても、存在しない名前を使えば実行中に<code>NameError</code>になります。どちらも表示の最後の行に、エラー名と短い説明があります。まず最後の行を読み、示されたセルを確認し、一度に一か所だけ直して再実行します。</p>'
        . '<p>さらに注意したいのは、エラーを出さずに間違った答えを返す場合です。たとえば「3日間、1日4回」を求めるのに<code>3 + 4</code>と書いても、Pythonは7を返します。コードが動いたことと、目的に合う結果であることは別なので、実行前の予想と実行後の説明が必要になります。</p>'
        . '<h3>Python Lab 00を完了する</h3>'
        . '<ol><li><code>00_start_here.ipynb</code>を開き、Codeセルを上から順に実行します。</li><li>一つの値を変更して再実行し、変化した理由をMarkdownセルに書きます。</li><li>用意された文法エラーと実行時エラーを確認し、最後の行にあるエラー名を読みます。</li><li>Kernelを再起動して、すべてのセルを上から実行します。</li><li>保存して閉じ、再び開いて、自分の変更が残っていることを確認します。</li></ol>'
        . '<p>ここまでできれば、次の章では環境操作ではなくPythonそのものに集中できます。作業領域を開けない、またはサーバーが応答しない場合は、自分で環境を変更せず、Moodleの活動名と画面に出た最後のエラー行を講師または管理者へ伝えてください。</p>'
        . '<p><strong>学習時間の目安：</strong>45〜60分</p><p style="display:none">PYAI-V11-CHAPTER0-LAB</p></div>';
} else {
    $sectionname = 'Chapter 0 — Starting Python and Python Lab';
    $sectionsummary = '<p>Understand the basic model of program execution, then run, change, save, and resume a Notebook in the supported course environment.</p>';
    $guidename = 'Start here: the Python learning map';
    $guideintro = '<p>See why Python is worth learning and where this course is going before running the first program.</p>';
    $guide = '<div class="python-sample-lesson"><h2>Giving work to a computer</h2>'
        . '<p>In everyday work, we often state a goal and expect another person to fill in details from the situation. A computer does not infer that intention. It needs executable instructions that state what to do, in what order, and how to do it. A program is an organised set of those instructions.</p>'
        . '<p>Many programs receive values, process them, and make a result available. A monthly learning-centre report, for example, can receive registrations and completions as input, calculate a completion rate, and produce a table or chart. Data needed again must be saved to a file or service rather than merely displayed.</p>'
        . '<table class="generaltable"><thead><tr><th>Role</th><th>Monthly-report example</th></tr></thead><tbody><tr><td>Input</td><td>Receive registrations, completions, and delivered hours</td></tr><tr><td>Processing</td><td>Calculate totals, differences, and rates</td></tr><tr><td>Output</td><td>Present results on screen, in a table, or in a chart</td></tr><tr><td>Storage</td><td>Keep work in a Notebook or CSV for later use</td></tr></tbody></table>'
        . '<p>Python is a general-purpose language that expresses these instructions in relatively readable form. The same foundation can lead to automation, data analysis, web systems, or scientific computing. This course therefore develops ideas that transfer beyond Python: values, execution order, decisions, repetition, functions, and ways to organise data.</p>'
        . '<h3>From Python source to a result</h3>'
        . '<p>The Python interpreter reads source code written by a person and executes its instructions. Values exist in memory while the program runs. <code>print()</code> can make a result visible, while file operations can preserve data after execution finishes.</p>'
        . '<p>Keep three questions separate: <strong>is the syntax valid</strong>, <strong>can execution finish</strong>, and <strong>does the result meet the purpose</strong>? Code can run without error and still use the wrong calculation. This distinction will matter throughout the course.</p>'
        . '<h3>A route from foundations to data analysis</h3>'
        . '<p>After learning Python Lab, you begin with values and variables, then decisions, repetition, collections, and functions. Files, exceptions, modules, libraries, classes, and objects follow. That foundation is then applied to inspecting, cleaning, summarising, and visualising tabular data. The final project answers one practical question with reproducible evidence.</p>'
        . '<table class="generaltable"><thead><tr><th>Stage</th><th>Subject</th><th>Capability it develops</th></tr></thead><tbody><tr><td>Chapter 0</td><td>Python Lab and execution</td><td>Reproduce a result in the supported environment</td></tr><tr><td>Chapters 1–2</td><td>Values, variables, decisions, and repetition</td><td>Build a flow of processing</td></tr><tr><td>Chapters 3–6</td><td>Collections, functions, files, exceptions, and classes</td><td>Build organised, maintainable programs</td></tr><tr><td>Chapter 7</td><td>Tabular data analysis</td><td>Turn data into evidence</td></tr><tr><td>Chapter 8</td><td>Final project</td><td>Combine a question, analysis, and explanation</td></tr></tbody></table>'
        . '<h3>Learn by changing what runs</h3>'
        . '<p>Reading code is not enough to develop an accurate mental model. Predict a result, run the code, check it, change one part, and explain the change. A learning check is not a one-attempt selection test: it exposes a misunderstanding so that you can study and try again. Attempts are unlimited, 90% passes, and 100% remains the mastery target.</p>'
        . '<p>The next page removes setup uncertainty. You will open Python Lab from Moodle, run a Notebook, save it, and reopen it before concentrating on Python syntax.</p>'
        . '<p style="display:none">PYAI-V11-CHAPTER0-GUIDE</p></div>';

    $labname = 'Using Python Lab: Notebook, Console, and scripts';
    $labintro = '<p>Open Python Lab from Moodle and save both executed code and your explanation in a Notebook.</p>';
    $lab = '<div class="python-sample-lesson"><h2>The supported Python environment</h2>'
        . '<p>Moodle contains explanations, learning checks, and assignments; Python Lab executes the code. Opening “Python Lab 00” from Moodle leads to <code>00_start_here.ipynb</code> in the learner workspace. Even on a shared computer, the file is stored in the server-side workspace opened through the browser, so learners do not install Python separately on each PC.</p>'
        . '<p>Python can be run in several ways. A Console accepts one instruction at a time. A <code>.py</code> script normally runs a saved sequence from top to bottom. A Notebook keeps explanation, code, and visible results together in cells. Because that record supports both practice and review, the Notebook is the course standard.</p>'
        . '<table class="generaltable"><thead><tr><th>Method</th><th>Best suited to</th><th>Course use</th></tr></thead><tbody><tr><td>Console (interactive mode)</td><td>Trying a short expression or instruction immediately</td><td>Small experiments when useful</td></tr><tr><td><code>.py</code> script</td><td>Running a saved sequence of instructions</td><td>Understand common Python execution</td></tr><tr><td>Notebook</td><td>Keeping explanation, code, and results together</td><td><strong>Standard for examples, practice, and submissions</strong></td></tr></tbody></table>'
        . '<h3>Run the first cell</h3>'
        . '<p>A Notebook has Markdown cells for prose and Code cells for Python. Select a Code cell and press <kbd>Shift</kbd>+<kbd>Enter</kbd>; Python runs that cell and returns the result below it. The following code displays text and then a calculated value.</p>'
        . v11_code("print(\"Python Lab is ready\")\nprint(3 + 4)")
        . '<p>The quoted part on the first line is text to display. On the second line, <code>3 + 4</code> is calculated before it is displayed. Do not try to memorise the syntax yet. Change one value, rerun the cell, and observe what changes.</p>'
        . '<h3>Running state and the saved Notebook</h3>'
        . '<p>When a Code cell runs, the Python kernel keeps values in memory. The number beside a cell records actual execution order, not its position on the page. Running a later cell first can therefore create a state that another reader cannot reproduce from top to bottom.</p>'
        . '<p>If the visible results stop matching the explanation, restart the kernel and run every cell from the top. This checks that the Notebook is reproducible from a clean state. Saving with <kbd>Ctrl</kbd>+<kbd>S</kbd> preserves code, prose, and visible results in the Notebook file. Temporary kernel state and the saved file are not the same thing.</p>'
        . '<h3>An error is evidence about the next step</h3>'
        . '<p>An unclosed quote prevents Python from reading an instruction and produces <code>SyntaxError</code>. Valid syntax that uses an unknown name fails during execution with <code>NameError</code>. In both cases, the final traceback line gives an exception name and a concise message. Read that line first, inspect the named cell, change one thing, and run it again.</p>'
        . '<p>A program can also run and return the wrong answer. If a task says “three days, four sessions per day”, <code>3 + 4</code> runs and returns 7, but it does not represent the task. Successful execution and a correct result are different, which is why you predict before running and explain after checking.</p>'
        . '<h3>Complete Python Lab 00</h3>'
        . '<ol><li>Open <code>00_start_here.ipynb</code> and run its Code cells from the top.</li><li>Change one value, rerun it, and explain the changed result in a Markdown cell.</li><li>Observe the prepared syntax and runtime errors and read the exception name on the final line.</li><li>Restart the kernel and run all cells from the top.</li><li>Save, close, and reopen the Notebook to confirm that your change remains.</li></ol>'
        . '<p>Once this works, the next chapter can focus on Python rather than setup. If the workspace will not open or the server does not respond, do not change the managed environment. Report the Moodle activity name and the final visible error line to the teacher or administrator.</p>'
        . '<p><strong>Estimated study time:</strong> 45–60 minutes</p><p style="display:none">PYAI-V11-CHAPTER0-LAB</p></div>';
}

$section0 = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
course_update_section($course, $section0, [
    'name' => $sectionname,
    'summary' => $sectionsummary,
    'summaryformat' => FORMAT_HTML,
]);

$guidepage = v11_find_page($course->id, [
    'Start here: the Python learning map',
    'はじめに：Python学習の全体地図',
    'Start here: course guide',
    'はじめに：コースガイド',
]);
v11_update_page($guidepage, $guidename, $guideintro, $guide);

$labpage = v11_find_page($course->id, [
    'Using Python Lab: Notebook, Console, and scripts',
    'Python Labの使い方：Notebook・Console・スクリプト',
    'Responsible AI: Ask, Read, Run, Check, Modify, Explain',
    '責任あるAI利用：質問・読解・実行・確認・修正・説明',
]);
v11_update_page($labpage, $labname, $labintro, $lab);

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
    'language' => $language,
    'chapter0_pages_updated' => 2,
    'marker' => 'PYAI-V11-CHAPTER0',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
