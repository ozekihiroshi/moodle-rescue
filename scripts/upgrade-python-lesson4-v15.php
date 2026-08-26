<?php
// Insert Chapter 1.4 and normalise all Chapter 1 activity numbering.

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

function v15_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

function v15_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v15_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
            ? '<p>正解です。文字列がどのように処理されたか説明してから次へ進みましょう。</p>'
            : '<p>Correct. Explain how the string was processed before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>文字列の位置、戻り値の型、または書式をNotebookで確認して再挑戦しましょう。</p>'
            : '<p>Check the string position, return type, or format in the Notebook and try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v15_rename_subsection(stdClass $course, string $oldname, string $newname): stdClass {
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

function v15_place_before(stdClass $course, int $parentsectionid, int $cmid, int $beforecmid): void {
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

function v15_rename_activity(int $courseid, string $table, array $oldnames, string $newname): ?stdClass {
    global $DB;
    if ($record = $DB->get_record($table, ['course' => $courseid, 'name' => $newname])) {
        return $record;
    }
    foreach ($oldnames as $oldname) {
        if ($record = $DB->get_record($table, ['course' => $courseid, 'name' => $oldname])) {
            $record->name = $newname;
            if (property_exists($record, 'timemodified')) {
                $record->timemodified = time();
            }
            $DB->update_record($table, $record);
            return $record;
        }
    }
    return null;
}

if ($language === 'ja') {
    $parentname = '第1章 — プログラミングの基礎と基本データ';
    $parentsummary = '<p>プログラムの実行、変数、基本データ型、文字列と入力、条件分岐、繰り返しを順に学び、週間サポート報告へ統合します。</p><ol><li>1.1 プログラム・値・式・出力</li><li>1.2 変数・代入・プログラムの状態</li><li>1.3 基本データ型・型変換・算術</li><li>1.4 文字列・入力・書式付き出力</li><li>1.5 条件による判断</li><li>1.6 ループによる繰り返し</li><li>1.7 実践プロジェクト</li></ol>';
    $topicname = '1.4 文字列・入力・書式付き出力';
    $topicsummary = '<p>文字列を取り出して整え、外部入力を適切な型へ変換し、f文字列で意味の分かる出力を作ります。</p>';
    $shift = [
        ['1.6 実践プロジェクト：週間サポート報告', '1.7 実践プロジェクト：週間サポート報告'],
        ['1.5 ループによる繰り返し', '1.6 ループによる繰り返し'],
        ['1.4 条件による判断', '1.5 条件による判断'],
    ];
    $conditiontopic = '1.5 条件による判断';
    $pagename = 'レッスン1.4：文字列・入力・書式付き出力';
    $quizname = '理解度チェック：1.4 文字列・入力・書式付き出力';
    $ltiname = 'Python Lab 1.4：文字列・入力・書式付き出力';
    $intro = '<p>文字列を文字の並びとして扱い、入力を変換し、読み手に意味が伝わる出力を作ります。</p>';
    $body = '<div class="python-sample-lesson"><h2>数値だけでは、仕事の意味を伝えられない</h2>'
        . '<p>前のレッスンでは修了率80.6という数値を作りました。しかし、画面に<code>80.6</code>だけ表示しても、修了率なのか費用なのか分かりません。プログラムは数値を計算するだけでなく、名称、説明、単位と組み合わせて人に伝える必要があります。その役割を担う基本データ型が文字列<code>str</code>です。</p>'
        . '<h3>引用符で文字列の範囲を示す</h3>'
        . '<p>一重引用符と二重引用符は、どちらも文字列を作ります。内容に二重引用符を含めるなら外側に一重引用符を使うなど、読みやすい方を選べます。改行は<code>\n</code>、タブは<code>\t</code>、バックスラッシュ自体は<code>\\</code>と書きます。</p>'
        . v15_code("course = \"Python入門\"\nmessage = '講師は「実行して確認」と言いました。'\nprint(course)\nprint(message)\nprint(\"1行目\\n2行目\")")
        . '<p>閉じ引用符を忘れるとPythonは文字列の終わりを判断できず、通常は<code>SyntaxError</code>になります。引用符の種類とエスケープは、文字列の内容とPythonの文法を区別するためにあります。</p>'
        . '<h3>文字列は、0から位置を数える文字の並び</h3>'
        . '<p>文字列の各文字には位置があります。最初の文字は0番、次は1番です。負の位置は末尾から数え、<code>-1</code>は最後の文字を表します。</p>'
        . v15_code("centre = \"North Centre\"\nprint(centre[0])\nprint(centre[-1])\nprint(centre[0:5])\nprint(len(centre))")
        . '<p><code>centre[0:5]</code>のようなスライスは、開始位置を含み終了位置を含みません。そのため0番から4番までの<code>North</code>になります。<code>len()</code>は文字数を返します。範囲外の位置を一文字として読むと<code>IndexError</code>になりますが、スライスは利用できる範囲で結果を返します。</p>'
        . '<h3>文字列は変更不能なので、処理結果は新しい値になる</h3>'
        . '<p>文字列の一文字だけを<code>centre[0] = "n"</code>のように置き換えることはできません。文字列は変更不能です。代わりに、整えた新しい文字列を作り、必要なら名前へ再代入します。</p>'
        . v15_code("raw_name = \"  North Centre  \"\nclean_name = raw_name.strip()\nprint(clean_name)\nprint(clean_name.lower())\nprint(clean_name.upper())\nprint(clean_name.replace(\"Centre\", \"Learning Centre\"))\nprint(\"元の値:\", raw_name)")
        . '<p><code>strip()</code>は前後の空白、<code>lower()</code>と<code>upper()</code>は大文字・小文字、<code>replace()</code>は指定した部分を置き換えた新しい文字列を返します。元の<code>raw_name</code>は変化しません。この性質は、データクリーニングで元データと処理後データを区別するときにも役立ちます。</p>'
        . '<h3>連結するときは、型を揃える</h3>'
        . '<p>文字列同士の<code>+</code>は連結、文字列と整数の直接加算は<code>TypeError</code>になります。数値を文字列へ連結するなら<code>str()</code>で意図を明示します。<code>*</code>は文字列を指定回数繰り返すので、区切り線などを作れます。</p>'
        . v15_code("completed = 29\nprint(\"修了者: \" + str(completed))\nprint(\"-\" * 20)")
        . '<h3><code>input()</code>は必ず文字列を返す</h3>'
        . '<p><code>input()</code>は画面に問いを表示し、利用者が入力してEnterを押すまで待ちます。数字の36を入力しても、戻り値は文字列<code>"36"</code>です。計算する前に<code>int()</code>または<code>float()</code>で変換します。</p>'
        . v15_code("registered_text = input(\"登録者数: \")\nregistered = int(registered_text)\nprint(registered + 4)")
        . '<p>Notebookでこのセルを実行すると入力待ちになります。セル左側が<code>[*]</code>のままなら、入力欄へ値を入れてEnterを押します。自動実行用Notebookでは停止を避けるため、この対話部分をコメントにし、同じ文字列を代入した例も用意しています。入力が空、または数字以外なら変換時に<code>ValueError</code>になるため、条件分岐と例外を学んだ後に安全な入力処理へ発展させます。</p>'
        . '<h3>f文字列で、説明と値を書式付きで組み合わせる</h3>'
        . '<p>文字列の先頭に<code>f</code>を付けると、波括弧の中に名前や式を書けます。<code>.1f</code>は小数第1位、<code>.2f</code>は小数第2位、<code>,</code>は桁区切りを指定します。</p>'
        . v15_code("centre = \"North Centre\"\nregistered = 36\ncompleted = 29\nrate = completed / registered * 100\ncost = 12345.5\nprint(f\"{centre}: {completed}/{registered}人、修了率 {rate:.1f}%\")\nprint(f\"教材費: {cost:,.2f}\")")
        . '<p>書式は表示の形を整えるもので、変数<code>rate</code>や<code>cost</code>の元の値を変更しません。計算用の値と、人に見せる表現を分けて考えます。</p>'
        . '<h3>区切られた文字列を分ける</h3>'
        . '<p><code>split()</code>は区切り文字で文字列を分け、複数の文字列をリストとして返します。たとえば<code>"North,36,29"</code>をカンマで分けると、名称と二つの人数を取り出せます。リスト自体は後の章で体系的に学びます。</p>'
        . v15_code("record = \"North,36,29\"\nparts = record.split(\",\")\ncentre = parts[0]\nregistered = int(parts[1])\ncompleted = int(parts[2])\nprint(f\"{centre}: {completed}/{registered}人\")")
        . '<h3>例題から応用へ</h3>'
        . '<p>前後に空白を含むセンター名、登録者数の文字列<code>"36"</code>、修了者数の文字列<code>"29"</code>を用意します。センター名を<code>strip()</code>で整え、人数を整数へ変換し、修了率を計算して、<code>North Centre: 29/36人、修了率 80.6%</code>と表示してください。センター名と人数を別の値へ変え、同じコードで正しく表示できることも確認します。</p>'
        . '<p>このレッスンを終えると、引用符とエスケープ、添字とスライス、文字列の変更不能性、主なメソッド、<code>input()</code>の戻り型、数値変換、f文字列の書式を説明できます。これで値を受け取り、処理し、人へ伝える基礎が揃い、次の条件分岐へ進めます。</p>'
        . '<p><strong>学習時間の目安：</strong>約2.5時間</p><p style="display:none">PYAI-V15-LESSON14-FLOW</p></div>';
    $questions = [
        v15_question('L14R-01', '<p>何が表示されますか。</p>' . v15_code('print("Python"[0])'), [['P', '正解です。最初の位置は0です。'], ['y', 'これは1番の文字です。'], ['0', '添字そのものではなく文字を返します。'], ['IndexError', '0番は存在します。']], 0, '文字列の添字は0から始まります。'),
        v15_question('L14R-02', '<p>何が表示されますか。</p>' . v15_code('print("North Centre"[0:5])'), [['North', '正解です。終了位置5は含みません。'], ['North ', '空白は5番です。'], ['Nort', '終了位置の直前4番まででは5文字です。'], ['Centre', '後半の部分ではありません。']], 0, 'スライスは開始位置を含み、終了位置を含みません。'),
        v15_question('L14R-03', '<p>何が表示されますか。</p>' . v15_code('print(len("Python"))'), [['5', '文字を数え直してください。'], ['6', '正解です。6文字あります。'], ['7', '引用符は文字列に含まれません。'], ['str', 'lenは長さを整数で返します。']], 1, 'len(str)は文字数をintで返します。'),
        v15_question('L14R-04', '<p>何が表示されますか。</p>' . v15_code('print("  North Centre  ".strip().upper())'), [['  NORTH CENTRE  ', 'stripで前後の空白が消えます。'], ['NORTH CENTRE', '正解です。空白を除き大文字化します。'], ['north centre', 'upperは大文字化します。'], ['TypeError', '有効なメソッド連結です。']], 1, '文字列メソッドは新しい文字列を返すため、続けて適用できます。'),
        v15_question('L14R-05', '<p>何が表示されますか。</p>' . v15_code('print("3" + "4")'), [['7', '数値へ変換していません。'], ['34', '正解です。二つの文字列を連結します。'], ['3 4', '空白は追加していません。'], ['TypeError', '同じstr同士の連結です。']], 1, 'str同士の+は数値加算ではなく連結です。'),
        v15_question('L14R-06', '<p><code>answer = input("人数: ")</code>へ利用者が<code>36</code>と入力しました。<code>answer</code>の型は何ですか。</p>', [['int', 'inputは自動で整数化しません。'], ['float', '小数型にも変換しません。'], ['str', '正解です。inputは常に文字列を返します。'], ['bool', '真偽値ではありません。']], 2, 'input()の戻り値は入力内容にかかわらずstrです。'),
        v15_question('L14R-07', '<p>何が表示されますか。</p>' . v15_code('registered_text = "36"\nprint(int(registered_text) + 4)'), [['364', 'int変換後なので連結ではありません。'], ['40', '正解です。整数36へ4を加えます。'], ['36', '4も加算されます。'], ['ValueError', '"36"は有効な整数文字列です。']], 1, '計算前に外部入力の文字列を必要な数値型へ変換します。'),
        v15_question('L14R-08', '<p>何が表示されますか。</p>' . v15_code('rate = 80.555\nprint(f"{rate:.1f}%")'), [['80.555%', '書式で小数第1位へ丸めます。'], ['80.5%', '次の桁が5なので丸められます。'], ['80.6%', '正解です。小数第1位表示です。'], ['{rate:.1f}%', 'f文字列なので式が評価されます。']], 2, 'f文字列の.1fは小数第1位へ書式化します。'),
        v15_question('L14R-09', '<p>最初の行で何が起こりますか。</p>' . v15_code('name = "North"\nname[0] = "n"'), [['northになる', '文字列の一文字は直接変更できません。'], ['Northのまま静かに無視される', '代入はエラーになります。'], ['TypeErrorになる', '正解です。文字列は変更不能です。'], ['IndexErrorになる', '0番は存在しますが変更できません。']], 2, '文字列を変更する操作は、新しい文字列を作って再代入します。'),
        v15_question('L14R-10', '<p>何が表示されますか。</p>' . v15_code('parts = "North,36,29".split(",")\nprint(parts[0], int(parts[2]))'), [['North 29', '正解です。0番は名称、2番を整数化します。'], ['North 36', '36は1番です。'], ['36 29', '最初に名称を表示します。'], ['TypeError', '有効な分割と変換です。']], 0, 'splitは文字列のリストを返すため、必要な要素を選んで変換します。'),
    ];
    $renames = [
        ['page', ['レッスン1：プログラム・値・式・出力'], 'レッスン1.1：プログラム・値・式・出力'],
        ['lti', ['Python Lab 01：プログラム・値・式・出力'], 'Python Lab 1.1：プログラム・値・式・出力'],
        ['quiz', ['理解度チェック：レッスン1 プログラム・値・式・出力'], '理解度チェック：1.1 プログラム・値・式・出力'],
        ['page', ['レッスン2：変数・代入・プログラムの状態'], 'レッスン1.2：変数・代入・プログラムの状態'],
        ['lti', ['Python Lab 02：変数・代入・状態'], 'Python Lab 1.2：変数・代入・状態'],
        ['quiz', ['理解度チェック：レッスン2 変数・代入・プログラムの状態'], '理解度チェック：1.2 変数・代入・プログラムの状態'],
        ['page', ['レッスン3：条件による判断'], 'レッスン1.5：条件による判断'],
        ['lti', ['Python Lab 03：条件と境界値'], 'Python Lab 1.5：条件と境界値'],
        ['quiz', ['理解度チェック：レッスン3 条件による判断'], '理解度チェック：1.5 条件による判断'],
        ['page', ['レッスン4：ループによる繰り返し'], 'レッスン1.6：ループによる繰り返し'],
        ['lti', ['Python Lab 04：ループと累積処理'], 'Python Lab 1.6：ループと累積処理'],
        ['quiz', ['理解度チェック：レッスン4 ループによる繰り返し'], '理解度チェック：1.6 ループによる繰り返し'],
        ['lti', ['Python Labプロジェクト：週間サポート報告'], 'Python Labプロジェクト1.7：週間サポート報告'],
        ['assign', ['ミニプロジェクト：学習センター週間サポート報告'], 'プロジェクト1.7：学習センター週間サポート報告'],
    ];
} else {
    $parentname = 'Chapter 1 — Programming Foundations and Scalar Values';
    $parentsummary = '<p>Progress through execution, variables, scalar types, strings and input, decisions, and repetition, then integrate them in a weekly support report.</p><ol><li>1.1 Programs, values, expressions, and output</li><li>1.2 Variables, assignment, and state</li><li>1.3 Basic scalar types, conversion, and arithmetic</li><li>1.4 Strings, input, and formatted output</li><li>1.5 Decisions with conditions</li><li>1.6 Repetition with loops</li><li>1.7 Applied project</li></ol>';
    $topicname = '1.4 Strings, input, and formatted output';
    $topicsummary = '<p>Select and clean text, convert external input to suitable types, and produce meaningful output with f-strings.</p>';
    $shift = [
        ['1.6 Applied project: Weekly support report', '1.7 Applied project: Weekly support report'],
        ['1.5 Repetition with loops', '1.6 Repetition with loops'],
        ['1.4 Decisions with conditions', '1.5 Decisions with conditions'],
    ];
    $conditiontopic = '1.5 Decisions with conditions';
    $pagename = 'Lesson 1.4: Strings, input, and formatted output';
    $quizname = 'Knowledge check: 1.4 Strings, input, and formatted output';
    $ltiname = 'Python Lab 1.4: Strings, input, and formatted output';
    $intro = '<p>Treat strings as sequences, convert input, and produce output whose meaning is clear to another reader.</p>';
    $body = '<div class="python-sample-lesson"><h2>A number alone does not communicate its work meaning</h2>'
        . '<p>The previous lesson calculated the value 80.6. Displayed alone, it could be a completion rate, a cost, or something else. Programs must combine calculated values with names, descriptions, and units for human readers. Python represents this text with type <code>str</code>.</p>'
        . '<h3>Quotes mark the extent of a string</h3>'
        . '<p>Single and double quotes both create strings. Choose outer quotes that keep quoted content readable. A newline is written <code>\n</code>, a tab <code>\t</code>, and a literal backslash <code>\\</code>.</p>'
        . v15_code("course = \"Introduction to Python\"\nmessage = 'The teacher said, \"Run and check.\"'\nprint(course)\nprint(message)\nprint(\"Line 1\\nLine 2\")")
        . '<p>A missing closing quote usually raises <code>SyntaxError</code> because Python cannot identify where the string ends. Quotes and escapes distinguish string content from Python syntax.</p>'
        . '<h3>A string is a sequence whose positions begin at zero</h3>'
        . '<p>Each character has a position. The first is position zero and the next is one. Negative positions count from the end, so <code>-1</code> selects the last character.</p>'
        . v15_code("centre = \"North Centre\"\nprint(centre[0])\nprint(centre[-1])\nprint(centre[0:5])\nprint(len(centre))")
        . '<p>A slice such as <code>centre[0:5]</code> includes the start and excludes the end, returning <code>North</code>. <code>len()</code> returns the character count. Reading one character outside the available range raises <code>IndexError</code>, while a slice returns the available portion.</p>'
        . '<h3>Strings are immutable, so processing produces a new value</h3>'
        . '<p>A character cannot be replaced with <code>centre[0] = "n"</code>. Strings are immutable. Instead, create a processed string and assign it when it should become current.</p>'
        . v15_code("raw_name = \"  North Centre  \"\nclean_name = raw_name.strip()\nprint(clean_name)\nprint(clean_name.lower())\nprint(clean_name.upper())\nprint(clean_name.replace(\"Centre\", \"Learning Centre\"))\nprint(\"Original:\", raw_name)")
        . '<p><code>strip()</code> removes surrounding whitespace; <code>lower()</code> and <code>upper()</code> change case in the returned value; <code>replace()</code> returns text with the requested replacement. The original <code>raw_name</code> remains unchanged, a useful distinction when cleaning data without losing its source.</p>'
        . '<h3>Match types before concatenation</h3>'
        . '<p><code>+</code> joins two strings. Directly adding a string and integer raises <code>TypeError</code>. Convert a number with <code>str()</code> when concatenation is truly required. <code>*</code> repeats a string and can create a separator.</p>'
        . v15_code("completed = 29\nprint(\"Completed: \" + str(completed))\nprint(\"-\" * 20)")
        . '<h3><code>input()</code> always returns a string</h3>'
        . '<p><code>input()</code> displays a prompt and waits until the user enters a value. Even if the user types 36, the returned value is text <code>"36"</code>. Convert with <code>int()</code> or <code>float()</code> before numeric calculation.</p>'
        . v15_code("registered_text = input(\"Registered learners: \")\nregistered = int(registered_text)\nprint(registered + 4)")
        . '<p>A Notebook cell containing <code>input()</code> waits for entry; a persistent <code>[*]</code> can mean the kernel is waiting. The supplied Notebook comments the interactive lines for automatic verification and provides equivalent assigned text. Empty or non-numeric input can raise <code>ValueError</code> during conversion. Conditions and exceptions later extend this into safe input handling.</p>'
        . '<h3>f-strings combine meaning and values with a display format</h3>'
        . '<p>Prefix a string with <code>f</code> and Python evaluates names or expressions inside braces. <code>.1f</code> displays one decimal place, <code>.2f</code> two, and <code>,</code> adds a thousands separator.</p>'
        . v15_code("centre = \"North Centre\"\nregistered = 36\ncompleted = 29\nrate = completed / registered * 100\ncost = 12345.5\nprint(f\"{centre}: {completed}/{registered} learners, completion {rate:.1f}%\")\nprint(f\"Materials cost: {cost:,.2f}\")")
        . '<p>Formatting changes the displayed representation, not the underlying values in <code>rate</code> or <code>cost</code>. Keep calculation values distinct from presentation.</p>'
        . '<h3>Split delimited text into fields</h3>'
        . '<p><code>split()</code> separates a string at a delimiter and returns a list of strings. Splitting <code>"North,36,29"</code> at commas makes the name and two counts available. Lists are developed systematically in their own chapter.</p>'
        . v15_code("record = \"North,36,29\"\nparts = record.split(\",\")\ncentre = parts[0]\nregistered = int(parts[1])\ncompleted = int(parts[2])\nprint(f\"{centre}: {completed}/{registered} learners\")")
        . '<h3>From guided example to transfer</h3>'
        . '<p>Start with a centre name containing surrounding spaces, registration text <code>"36"</code>, and completion text <code>"29"</code>. Clean the name, convert the counts, calculate completion rate, and display <code>North Centre: 29/36 learners, completion 80.6%</code>. Change the centre and counts and confirm the same code still works.</p>'
        . '<p>After this lesson, you can explain quotes and escapes, indexing and slicing, string immutability, common methods, the return type of <code>input()</code>, numeric conversion, and f-string formatting. The foundations for receiving, processing, and communicating values are now ready for decisions with conditions.</p>'
        . '<p><strong>Estimated study time:</strong> about 2.5 hours</p><p style="display:none">PYAI-V15-LESSON14-FLOW</p></div>';
    $questions = [
        v15_question('L14R-01', '<p>What is displayed?</p>' . v15_code('print("Python"[0])'), [['P', 'Correct: the first position is zero.'], ['y', 'That is position one.'], ['0', 'Indexing returns the character, not the index.'], ['IndexError', 'Position zero exists.']], 0, 'String indexing starts at zero.'),
        v15_question('L14R-02', '<p>What is displayed?</p>' . v15_code('print("North Centre"[0:5])'), [['North', 'Correct: end position five is excluded.'], ['North ', 'The space is position five.'], ['Nort', 'The slice includes five characters, positions zero to four.'], ['Centre', 'That is the later portion.']], 0, 'A slice includes its start and excludes its end.'),
        v15_question('L14R-03', '<p>What is displayed?</p>' . v15_code('print(len("Python"))'), [['5', 'Count the characters again.'], ['6', 'Correct: the string has six characters.'], ['7', 'Quotes are not part of the value.'], ['str', 'len returns an integer count.']], 1, 'len(str) returns the character count as int.'),
        v15_question('L14R-04', '<p>What is displayed?</p>' . v15_code('print("  North Centre  ".strip().upper())'), [['  NORTH CENTRE  ', 'strip removes surrounding spaces.'], ['NORTH CENTRE', 'Correct: trim first, then uppercase.'], ['north centre', 'upper returns uppercase.'], ['TypeError', 'This method chain is valid.']], 1, 'String methods return new strings that can be processed by another method.'),
        v15_question('L14R-05', '<p>What is displayed?</p>' . v15_code('print("3" + "4")'), [['7', 'No numeric conversion occurred.'], ['34', 'Correct: two strings are concatenated.'], ['3 4', 'No space is inserted.'], ['TypeError', 'Both operands are str.']], 1, '+ concatenates str values rather than adding numeric meanings.'),
        v15_question('L14R-06', '<p>The user types <code>36</code> for <code>answer = input("Learners: ")</code>. What is the type of <code>answer</code>?</p>', [['int', 'input does not infer integer type.'], ['float', 'It does not infer float either.'], ['str', 'Correct: input always returns text.'], ['bool', 'It is not a truth value.']], 2, 'input() returns str regardless of typed content.'),
        v15_question('L14R-07', '<p>What is displayed?</p>' . v15_code('registered_text = "36"\nprint(int(registered_text) + 4)'), [['364', 'The text was converted before addition.'], ['40', 'Correct: integer 36 plus integer 4.'], ['36', 'Four is also added.'], ['ValueError', '"36" is valid integer text.']], 1, 'Convert external text to the required numeric type before arithmetic.'),
        v15_question('L14R-08', '<p>What is displayed?</p>' . v15_code('rate = 80.555\nprint(f"{rate:.1f}%")'), [['80.555%', 'The format displays one decimal place.'], ['80.5%', 'The following digit causes rounding.'], ['80.6%', 'Correct: one decimal place is displayed.'], ['{rate:.1f}%', 'The f-string evaluates its expression.']], 2, '.1f formats a numeric value to one decimal place.'),
        v15_question('L14R-09', '<p>What happens on the second line?</p>' . v15_code('name = "North"\nname[0] = "n"'), [['name becomes north', 'A character cannot be replaced directly.'], ['The assignment is ignored', 'Python raises an error.'], ['TypeError is raised', 'Correct: strings are immutable.'], ['IndexError is raised', 'Position zero exists; mutation is the problem.']], 2, 'Create a new string and reassign the name instead of mutating a character.'),
        v15_question('L14R-10', '<p>What is displayed?</p>' . v15_code('parts = "North,36,29".split(",")\nprint(parts[0], int(parts[2]))'), [['North 29', 'Correct: position zero is the name and position two is converted.'], ['North 36', '36 is at position one.'], ['36 29', 'The name is displayed first.'], ['TypeError', 'The split, indexing, and conversion are valid.']], 0, 'split returns strings in a list; select and convert the required field.'),
    ];
    $renames = [
        ['page', ['Lesson 1: Programs, values, expressions, and output'], 'Lesson 1.1: Programs, values, expressions, and output'],
        ['lti', ['Python Lab 01: Programs, values, expressions, and output'], 'Python Lab 1.1: Programs, values, expressions, and output'],
        ['quiz', ['Knowledge check: Lesson 1: Programs, values, expressions, and output'], 'Knowledge check: 1.1 Programs, values, expressions, and output'],
        ['page', ['Lesson 2: Variables, assignment, and program state'], 'Lesson 1.2: Variables, assignment, and program state'],
        ['lti', ['Python Lab 02: Variables, assignment, and state'], 'Python Lab 1.2: Variables, assignment, and state'],
        ['quiz', ['Knowledge check: Lesson 2: Variables, assignment, and program state'], 'Knowledge check: 1.2 Variables, assignment, and program state'],
        ['page', ['Lesson 3: Decisions with conditions'], 'Lesson 1.5: Decisions with conditions'],
        ['lti', ['Python Lab 03: Conditions and boundaries'], 'Python Lab 1.5: Conditions and boundaries'],
        ['quiz', ['Knowledge check: Lesson 3: Decisions with conditions'], 'Knowledge check: 1.5 Decisions with conditions'],
        ['page', ['Lesson 4: Repetition with loops'], 'Lesson 1.6: Repetition with loops'],
        ['lti', ['Python Lab 04: Loops and accumulators'], 'Python Lab 1.6: Loops and accumulators'],
        ['quiz', ['Knowledge check: Lesson 4: Repetition with loops'], 'Knowledge check: 1.6 Repetition with loops'],
        ['lti', ['Python Lab project: Weekly support report'], 'Python Lab project 1.7: Weekly support report'],
        ['assign', ['Mini-project: Weekly learning-centre support report'], 'Project 1.7: Weekly learning-centre support report'],
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
course_update_section($course, $parent, ['summary' => $parentsummary, 'summaryformat' => FORMAT_HTML]);

$shifted = [];
foreach ($shift as [$oldname, $newname]) {
    $shifted[] = v15_rename_subsection($course, $oldname, $newname);
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
v15_place_before($course, $parent->id, $subsectioncm->id, $conditioncm->id);
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
            ? '<p>文字列、入力、変換、書式付き出力を短いコードで確認します。何度でも挑戦でき、最高点が記録されます。</p>'
            : '<p>Check strings, input, conversion, and formatted output with short code. Attempts are unlimited and the highest score is retained.</p>',
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
        $saved = v15_save_question($category->id, $context->id, $shortname . ' v5: ', $data, $language);
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
        'name' => $language === 'ja'
            ? 'Python Lab 1.3：基本データ型・型変換・算術'
            : 'Python Lab 1.3: Basic scalar types, conversion, and arithmetic',
    ], '*', MUST_EXIST);
    $toolurl = preg_replace(
        '~/hub/user-redirect/lab/tree/.*$~',
        '/hub/user-redirect/lab/tree/' . ($language === 'ja' ? 'ja/' : '') . '04_strings_input_formatting.ipynb',
        $prototype->toolurl
    );
    $created = add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST),
        'modulename' => 'lti',
        'section' => $delegated->section,
        'name' => $ltiname,
        'intro' => $language === 'ja'
            ? '<p>文字列・入力・書式付き出力のNotebookを開き、予想、実行、変更、説明を行います。</p><p><strong>Notebook:</strong> <code>ja/04_strings_input_formatting.ipynb</code></p>'
            : '<p>Open the strings, input, and formatting Notebook; predict, run, change, and explain.</p><p><strong>Notebook:</strong> <code>04_strings_input_formatting.ipynb</code></p>',
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

$renamed = [];
foreach ($renames as [$table, $oldnames, $newname]) {
    $record = v15_rename_activity($course->id, $table, $oldnames, $newname);
    if ($record) {
        $renamed[] = $newname;
    }
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
    'chapter1_activities_renamed' => count($renamed),
    'marker' => 'PYAI-V15-LESSON14-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
