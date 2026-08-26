<?php
// Add Chapter 2.3: file and CSV input/output, before the existing project.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';
require_once $CFG->libdir . '/gradelib.php';

use core_question\local\bank\question_version_status;

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';

function v22_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v22_q(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v22_save_question(int $categoryid, int $contextid, string $prefix, array $data, bool $ja): stdClass {
    $question = (object)['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['choices'] as $index => [$answer, $why]) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => '<p>' . s($why) . '</p>', 'format' => FORMAT_HTML];
        $fractions[] = $index === $data['correct'] ? 1.0 : 0.0;
    }
    $form = (object)[
        'name' => $prefix . $data['id'],
        'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => $data['prompt'], 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p><strong>' . ($ja ? '確認ポイント：' : 'Check:') . '</strong> ' . s($data['explanation']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 10, 'penalty' => 0.3333333,
        'status' => question_version_status::QUESTION_STATUS_READY, 'idnumber' => null,
        'single' => 1, 'shuffleanswers' => 1, 'answernumbering' => 'abc', 'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => $ja ? '<p>正解です。コードとデータの対応を説明してから次へ進みましょう。</p>' : '<p>Correct. Explain how the code corresponds to the data before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $ja ? '<p>Notebookで対象のパス、行、型、出力ファイルを表示して確認しましょう。</p>' : '<p>Use the Notebook to display the path, row, type, or output file, then check again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions, 'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v22_feedback_bands(int $quizid, bool $ja): void {
    global $DB;
    $DB->delete_records('quiz_feedback', ['quizid' => $quizid]);
    $bands = $ja ? [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">&#127881;</span><h3>100%達成です！</h3><p>すべての考え方を確認できました。難しかった一問を自分の言葉で説明して定着させましょう。</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">&#127942;</span><h3>合格です。おめでとうございます！</h3><p>この理解度チェックは完了です。残りの解説も確認し、100%へ再挑戦できます。</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">&#128640;</span><h3>あと少しです。</h3><p>誤った項目をPython Labで確かめ、90%以上を目指して再挑戦しましょう。</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">&#127793;</span><h3>理解を積み上げています。</h3><p>解説から二つ選んでNotebookで実行し、もう一度確認しましょう。</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">&#128269;</span><h3>次に学ぶ場所が分かりました。</h3><p>これは罰点ではありません。パス、型、保存先を表示して確かめ、再挑戦しましょう。</p></div>'],
    ] : [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">&#127881;</span><h3>Mastered — 100%!</h3><p>You checked every idea. Explain one difficult answer in your own words to make it stick.</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">&#127942;</span><h3>Completed — congratulations!</h3><p>This learning check is complete. Review remaining feedback and try again for 100% if useful.</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">&#128640;</span><h3>You are close.</h3><p>Practise the missed item in Python Lab and try again; 90% completes the check.</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">&#127793;</span><h3>Keep building.</h3><p>Choose two explanations, run the related Notebook code, and check again.</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">&#128269;</span><h3>You found what to learn next.</h3><p>This is guidance, not a penalty. Display the path, type, and output, then retry.</p></div>'],
    ];
    foreach ($bands as [$min, $max, $html]) {
        $DB->insert_record('quiz_feedback', (object)['quizid' => $quizid, 'feedbacktext' => $html, 'feedbacktextformat' => FORMAT_HTML, 'mingrade' => $min, 'maxgrade' => $max]);
    }
}

function v22_parent(stdClass $course, array $names): section_info {
    foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
        if ($section && empty($section->component) && in_array($section->name, $names, true)) {
            return $section;
        }
    }
    throw new RuntimeException('Chapter 2 not found');
}

function v22_move_before(stdClass $course, int $parentid, int $cmid, int $beforeid): void {
    global $DB;
    foreach ($DB->get_records('course_sections', ['course' => $course->id]) as $section) {
        $ids = array_values(array_filter(array_map('intval', explode(',', (string)$section->sequence)), fn($id) => $id > 0 && $id !== $cmid));
        if (implode(',', $ids) !== (string)$section->sequence) {
            $section->sequence = implode(',', $ids);
            $DB->update_record('course_sections', $section);
        }
    }
    $parent = $DB->get_record('course_sections', ['id' => $parentid, 'course' => $course->id], '*', MUST_EXIST);
    $ids = array_values(array_filter(array_map('intval', explode(',', (string)$parent->sequence))));
    $position = array_search($beforeid, $ids, true);
    $position === false ? $ids[] = $cmid : array_splice($ids, $position, 0, [$cmid]);
    $parent->sequence = implode(',', $ids);
    $DB->update_record('course_sections', $parent);
    $DB->set_field('course_modules', 'section', $parent->id, ['id' => $cmid]);
}

if ($ja) {
    $oldchapter = '第2章 — データ構造と信頼できるプログラム';
    $chaptername = '第2章 — データ構造・関数・ファイル処理';
    $chaptersummary = '<p>複数の値をレコードとして整理し、処理を関数へ分け、外部ファイルから安全に読み書きします。章末では、入力・検証・変更・保存・自動確認を一つのプログラムへ統合します。</p>';
    $oldproject = '2.3 実践プロジェクト：学習センター月次実績報告';
    $projectname = '2.4 実践プロジェクト：学習センター月次実績報告';
    $oldprojectlti = 'Python Lab 2.3：学習センター月次実績報告';
    $projectlti = 'Python Lab 2.4：学習センター月次実績報告';
    $oldprojectassign = 'プロジェクト2.3：学習センター月次実績報告';
    $projectassign = 'プロジェクト2.4：学習センター月次実績報告';
    $topic = '2.3 ファイル・CSVの読み書き';
    $summary = '<p>ファイルの場所を確認し、標準csvモジュールでレコードを読み、型と入力規則を検証して、元データとは別のCSVへ保存・再読込します。</p>';
    $pagename = 'レッスン2.3：ファイル・CSVの読み書き';
    $ltiname = 'Python Lab 2.3：ファイル・CSVの読み書き';
    $quizname = '理解度チェック：2.3 ファイル・CSVの読み書き';
    $intro = '<p>図書記録のCSVを例に、読む場所、データの型、検証、保存先、再読込までを順番に学びます。</p>';
    $quizintro = '<p>短いコードとCSVを対応させ、パス、読込、型変換、検証、保存、再読込を確認します。</p>';
    $body = '<div class="python-sample-lesson"><h2>プログラムの外にある記録を、安全に受け渡す</h2>'
        . '<p>これまで扱った値は、プログラムが終了すると失われました。実務では、前回の記録を読み、処理結果を次回も使える形で保存する必要があります。ここでは、小さな図書一覧をCSVから読み、Pythonのレコードへ変換し、変更後の一覧を別のCSVへ保存します。</p>'
        . '<h3>読む前に、ファイルの場所を確定する</h3><p>相対パスは現在の作業フォルダを基準にします。Notebookでは<code>Path.cwd()</code>を表示し、候補を<code>resolve()</code>して確認します。独立したスクリプトでは<code>Path(__file__).resolve().parent</code>を基準にすると、起動位置が変わっても同じ付属ファイルを参照できます。</p>'
        . v22_code("from pathlib import Path\n\nBASE_DIR = Path(__file__).resolve().parent\nINPUT_PATH = BASE_DIR / \"data\" / \"books.csv\"\nOUTPUT_PATH = BASE_DIR / \"output\" / \"books_updated.csv\"")
        . '<p><code>FileNotFoundError</code>が出たら、ファイル名だけでなく、解決した絶対パスと<code>exists()</code>の結果を表示します。Notebookでは<code>__file__</code>が通常ないため、教材Notebookには現在位置から親フォルダを探す補助関数を用意しています。</p>'
        . '<h3>with、モード、文字コード</h3><p><code>with</code>で開くと、正常終了でも例外でもファイルが閉じられます。<code>r</code>は読込、<code>w</code>は新規作成または上書き、<code>a</code>は末尾追加です。文字コードは<code>encoding=&quot;utf-8&quot;</code>、CSVでは<code>newline=&quot;&quot;</code>を明示します。入力元を<code>w</code>で開くと内容を失うため、入力と出力のパスを分けます。</p>'
        . '<h3>CSVはコンマでsplitしない</h3><p>CSVでは、コンマを含む一つの値を引用符で囲めます。<code>Data, Decisions, and Evidence</code>という書名は一項目です。標準<code>csv</code>モジュールは引用符、区切り、改行の規則を扱います。</p>'
        . v22_code("import csv\n\nwith INPUT_PATH.open(\"r\", encoding=\"utf-8\", newline=\"\") as file:\n    reader = csv.DictReader(file)\n    print(reader.fieldnames)\n    for row in reader:\n        print(row)")
        . '<h3>DictReaderの値は最初は文字列</h3><p>見出しは辞書のキーになりますが、CSVの<code>false</code>はPythonの<code>False</code>へ自動変換されません。さらに<code>bool(&quot;false&quot;)</code>は空でない文字列なので<code>True</code>です。受け入れる表記を決めた関数で明示的に変換します。</p>'
        . v22_code("def parse_read(value):\n    text = value.strip().lower()\n    if text == \"true\":\n        return True\n    if text == \"false\":\n        return False\n    raise ValueError(f\"read must be true or false: {value!r}\")")
        . '<h3>使う前にヘッダーと各行を検証する</h3><p>必要列<code>id</code>、<code>title</code>、<code>read</code>の不足、空のIDや書名、重複ID、不正な真偽値を入力境界で拒否します。黙って0や空文字へ置き換えると、後の処理は動いても結果が誤ります。行番号を例外メッセージに含めると、元データを修正できます。</p>'
        . '<h3>別のCSVへ書き、もう一度読む</h3><p><code>csv.DictWriter</code>へ列順を指定し、<code>writeheader()</code>の後に各レコードを書きます。真偽値は小文字の<code>true</code>または<code>false</code>へ戻します。出力フォルダは必要な時だけ作り、教材の入力CSVは変更しません。</p>'
        . v22_code("OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)\nwith OUTPUT_PATH.open(\"w\", encoding=\"utf-8\", newline=\"\") as file:\n    writer = csv.DictWriter(file, fieldnames=[\"id\", \"title\", \"read\"])\n    writer.writeheader()\n    for book in books:\n        writer.writerow({\"id\": book[\"id\"], \"title\": book[\"title\"], \"read\": \"true\" if book[\"read\"] else \"false\"})")
        . '<p>保存処理が例外なく終わっただけでは十分ではありません。同じ読込関数で出力CSVを再読込し、レコード数、ID順、書名、真偽値を期待値と比較します。さらに、処理前後の入力ファイルのバイト列が同じであることを確認します。</p>'
        . '<h3>例題から応用へ</h3><p>Notebookでは<code>library-books-practice.csv</code>を読み、L003だけを読了済みにしたコピーを出力します。応用ではL001の書名だけを変更し、別名で保存して再読込してください。この段階では追加・検索・削除を一度に実装しません。それらは2.1と2.2の部品と組み合わせ、2.4で一つのプログラムにします。</p>'
        . '<p>このレッスンを終えると、ファイルの位置を説明し、標準csvモジュールで安全に読み書きし、型と入力規則を検証し、元データを保ったまま出力を照合できます。</p><p><strong>学習時間の目安：</strong>約4時間</p><p style="display:none">PYAI-V22-LESSON23-FLOW</p></div>';
    $questions = [
        v22_q('L23R-01', '<p>この<code>BASE_DIR</code>は何を表しますか。</p>' . v22_code('$BASE_DIR = Path(__file__).resolve().parent'), [['プログラムを起動した時の作業フォルダ', 'それは通常Path.cwd()です。'], ['実行中のスクリプトが置かれたフォルダ', '正解です。'], ['利用者のホームフォルダ', 'home()ではありません。'], ['CSVの先頭行', 'ファイルパスの処理です。']], 1, 'スクリプト付属ファイルは__file__基準にすると起動位置へ依存しません。'),
        v22_q('L23R-02', '<p>既存の入力CSVを次のモードで開く主な危険は何ですか。</p>' . v22_code('with path.open("w", encoding="utf-8") as file:'), [['内容が上書きされる', '正解です。wは作成または上書きです。'], ['必ず追記される', '追記はaです。'], ['読込専用になる', '読込はrです。'], ['UTF-8が無効になる', 'encoding指定は有効です。']], 0, '入力はr、生成物は別パスのwと分けます。'),
        v22_q('L23R-03', '<p><code>with</code>でファイルを開く主な利点はどれですか。</p>', [['ブロック終了時に閉じられる', '正解です。例外時も後始末されます。'], ['CSVを自動でDataFrameにする', 'pandasは使用していません。'], ['すべての値を数値へ変換する', '型変換は別途必要です。'], ['ファイルを暗号化する', '暗号化機能ではありません。']], 0, 'withはファイル資源の終了処理を明確にします。'),
        v22_q('L23R-04', '<p>書名が<code>"Data, Decisions, and Evidence"</code>のとき、<code>line.split(",")</code>を避ける理由は何ですか。</p>', [['引用符内のコンマまで区切る', '正解です。CSVの引用規則を扱えません。'], ['文字列はsplitできない', '文字列にはsplitがあります。'], ['DictReaderは数値専用', '辞書として文字列を読みます。'], ['コンマを削除できない', '問題は値の境界です。']], 0, '標準csvモジュールにCSVの構文を任せます。'),
        v22_q('L23R-05', '<p><code>DictReader</code>で<code>read</code>列の<code>false</code>を読んだ直後の型は何ですか。</p>', [['bool', '自動変換されません。'], ['str', '正解です。CSVの値は最初は文字列です。'], ['int', '数値ではありません。'], ['NoneType', '値があります。']], 1, '列の意味に応じた明示的な変換が必要です。'),
        v22_q('L23R-06', '<p>何が表示されますか。</p>' . v22_code('print(bool("false"))'), [['False', '単語の意味は解釈しません。'], ['True', '正解です。空でない文字列です。'], ['ValueError', 'boolへの変換は可能です。'], ['false', 'Pythonの真偽値を表示します。']], 1, 'true/false文字列は比較して変換します。'),
        v22_q('L23R-07', '<p><code>parse_read("yes")</code>で送出すべき例外はどれですか。</p>', [['ValueError', '正解です。値が許可された表記ではありません。'], ['KeyError', '辞書キー欠落ではありません。'], ['FileNotFoundError', 'ファイル探索ではありません。'], ['例外にせずTrue', '誤った意味を受け入れてしまいます。']], 0, '値の規則違反はValueErrorで明示します。'),
        v22_q('L23R-08', '<p>必要列が<code>{"id", "title", "read"}</code>、実際の列が<code>{"id", "title"}</code>のとき、不足集合はどれですか。</p>', [['{"read"}', '正解です。必要列から実際の列を引きます。'], ['{"id", "title"}', 'それらは存在します。'], ['空集合', 'readが不足しています。'], ['{"id", "title", "read"}', '共通列は除かれます。']], 0, 'ヘッダーはデータ行を読む前に検査します。'),
        v22_q('L23R-09', '<p>CSV出力に<code>newline=""</code>を指定する理由として最も適切なのはどれですか。</p>', [['csvモジュールにレコード改行を管理させる', '正解です。環境による余分な空行を避けます。'], ['すべてを一行にする', 'writerはレコードを分けます。'], ['UTF-8へ変換する', 'encodingが担当します。'], ['ヘッダーを削除する', 'writeheaderとは別です。']], 0, 'encodingとnewlineは異なる役割です。'),
        v22_q('L23R-10', '<p>保存後の確認として最も信頼できるものはどれですか。</p>', [['出力ファイルが存在することだけ', '内容の正しさは分かりません。'], ['同じ読込関数で再読込して期待レコードと比較し、元ファイルも不変と確認する', '正解です。'], ['画面にSavedと表示する', '表示だけでは証拠になりません。'], ['入力CSVへ直接上書きして見比べる', '元データを失います。']], 1, '往復確認と入力保全を一緒に検査します。'),
    ];
} else {
    $oldchapter = 'Chapter 2 — Data Structures and Reliable Programs';
    $chaptername = 'Chapter 2 — Data Structures, Functions, and File Processing';
    $chaptersummary = '<p>Organise values as records, separate processing into functions, and read and write external files safely. The chapter project combines input, validation, change, output, and automatic checking.</p>';
    $oldproject = '2.3 Applied project: Monthly centre performance report';
    $projectname = '2.4 Applied project: Monthly centre performance report';
    $oldprojectlti = 'Python Lab 2.3: Monthly centre performance report';
    $projectlti = 'Python Lab 2.4: Monthly centre performance report';
    $oldprojectassign = 'Project 2.3: Monthly learning-centre performance report';
    $projectassign = 'Project 2.4: Monthly learning-centre performance report';
    $topic = '2.3 File and CSV input/output';
    $summary = '<p>Resolve file locations, read records with the standard csv module, validate types and input rules, then save and reload a separate CSV without changing the source.</p>';
    $pagename = 'Lesson 2.3: File and CSV input/output';
    $ltiname = 'Python Lab 2.3: File and CSV input/output';
    $quizname = 'Knowledge check: 2.3 File and CSV input/output';
    $intro = '<p>Use a book-record CSV to connect file location, data types, validation, output paths, and round-trip checking.</p>';
    $quizintro = '<p>Connect short code with CSV data and check paths, reading, conversion, validation, writing, and reloading.</p>';
    $body = '<div class="python-sample-lesson"><h2>Exchange records safely beyond one program run</h2>'
        . '<p>Values used so far disappeared when a program ended. Operational work must read earlier records and save results for a later run. Here a small book catalogue is read from CSV, converted to Python records, and written to a separate CSV after a change.</p>'
        . '<h3>Establish the file location before reading</h3><p>A relative path starts from the current working directory. Display <code>Path.cwd()</code> in a Notebook and resolve candidates before changing a path by guesswork. In an independent script, base bundled files on <code>Path(__file__).resolve().parent</code> so behaviour does not depend on the launch directory.</p>'
        . v22_code("from pathlib import Path\n\nBASE_DIR = Path(__file__).resolve().parent\nINPUT_PATH = BASE_DIR / \"data\" / \"books.csv\"\nOUTPUT_PATH = BASE_DIR / \"output\" / \"books_updated.csv\"")
        . '<p>For <code>FileNotFoundError</code>, print the resolved absolute candidate and <code>exists()</code>. A Notebook normally has no <code>__file__</code>, so the supplied Notebook searches upward from its current directory.</p>'
        . '<h3>Use with, an intentional mode, and an explicit encoding</h3><p>A <code>with</code> block closes its file on success or failure. Mode <code>r</code> reads, <code>w</code> creates or replaces, and <code>a</code> appends. State <code>encoding=&quot;utf-8&quot;</code> and, for CSV, <code>newline=&quot;&quot;</code>. Opening source data with <code>w</code> destroys it, so separate input and output paths.</p>'
        . '<h3>Do not parse CSV with split</h3><p>A CSV field containing a comma can be quoted. The title <code>Data, Decisions, and Evidence</code> is one field. The standard <code>csv</code> module implements quoting, separators, and record endings.</p>'
        . v22_code("import csv\n\nwith INPUT_PATH.open(\"r\", encoding=\"utf-8\", newline=\"\") as file:\n    reader = csv.DictReader(file)\n    print(reader.fieldnames)\n    for row in reader:\n        print(row)")
        . '<h3>DictReader values initially remain strings</h3><p>The header becomes dictionary keys, but CSV text <code>false</code> does not become Python <code>False</code>. In fact <code>bool(&quot;false&quot;)</code> is <code>True</code> because the string is non-empty. Convert by an explicit accepted vocabulary.</p>'
        . v22_code("def parse_read(value):\n    text = value.strip().lower()\n    if text == \"true\":\n        return True\n    if text == \"false\":\n        return False\n    raise ValueError(f\"read must be true or false: {value!r}\")")
        . '<h3>Validate the header and each row before use</h3><p>Reject a missing <code>id</code>, <code>title</code>, or <code>read</code> column, blank required value, duplicate ID, or invalid Boolean at the input boundary. Silent substitution lets later code run on false data. Include a line number in the error so the source can be repaired.</p>'
        . '<h3>Write a separate CSV and load it again</h3><p>Give <code>csv.DictWriter</code> a stable field order, call <code>writeheader()</code>, and convert Booleans back to lower-case text. Create the output folder when needed and leave the supplied input unchanged.</p>'
        . v22_code("OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)\nwith OUTPUT_PATH.open(\"w\", encoding=\"utf-8\", newline=\"\") as file:\n    writer = csv.DictWriter(file, fieldnames=[\"id\", \"title\", \"read\"])\n    writer.writeheader()\n    for book in books:\n        writer.writerow({\"id\": book[\"id\"], \"title\": book[\"title\"], \"read\": \"true\" if book[\"read\"] else \"false\"})")
        . '<p>A save without an exception does not prove correct columns or conversions. Reload with the same loader and compare count, ID order, titles, and Booleans. Also compare the source bytes before and after.</p>'
        . '<h3>From guided example to transfer</h3><p>The Notebook reads <code>library-books-practice.csv</code>, marks only L003 as read in a copy, saves it, and reloads it. For transfer, change only the L001 title and repeat the round trip. Addition, search, and removal are not combined here; Project 2.4 joins these file components with Lessons 2.1 and 2.2.</p>'
        . '<p>After this lesson, you can explain a file location, read and write CSV safely, validate types and input rules, preserve source evidence, and check output by reloading it.</p><p><strong>Estimated study time:</strong> about 4 hours</p><p style="display:none">PYAI-V22-LESSON23-FLOW</p></div>';
    $questions = [
        v22_q('L23R-01', '<p>What does this <code>BASE_DIR</code> represent?</p>' . v22_code('$BASE_DIR = Path(__file__).resolve().parent'), [['The working directory used to launch Python', 'That is normally Path.cwd().'], ['The directory containing the running script', 'Correct.'], ['The user home directory', 'No home() is used.'], ['The first CSV row', 'This is path handling.']], 1, '__file__ makes bundled script resources independent of launch location.'),
        v22_q('L23R-02', '<p>What is the main danger of opening the supplied input CSV this way?</p>' . v22_code('with path.open("w", encoding="utf-8") as file:'), [['Its contents are replaced', 'Correct: w creates or overwrites.'], ['It always appends', 'Append mode is a.'], ['It becomes read-only', 'Read mode is r.'], ['UTF-8 is disabled', 'The encoding is explicit.']], 0, 'Use r for input and w with a separate output path.'),
        v22_q('L23R-03', '<p>What is the main benefit of opening a file with <code>with</code>?</p>', [['It closes when the block ends', 'Correct, including exceptional exits.'], ['It creates a DataFrame', 'No pandas is involved.'], ['It converts every value to a number', 'Conversion remains explicit.'], ['It encrypts the file', 'It does not provide encryption.']], 0, 'with gives the file resource a clear lifetime.'),
        v22_q('L23R-04', '<p>Why avoid <code>line.split(",")</code> for a title such as <code>"Data, Decisions, and Evidence"</code>?</p>', [['It splits commas inside the quoted field', 'Correct: it ignores CSV quoting rules.'], ['Strings have no split method', 'They do.'], ['DictReader accepts only numbers', 'It initially returns strings.'], ['Commas cannot be removed', 'The issue is field boundaries.']], 0, 'Delegate CSV syntax to the standard csv module.'),
        v22_q('L23R-05', '<p>Immediately after <code>DictReader</code> reads <code>false</code> from the <code>read</code> column, what is its type?</p>', [['bool', 'There is no automatic conversion.'], ['str', 'Correct: CSV values initially remain text.'], ['int', 'It is not numeric.'], ['NoneType', 'A value is present.']], 1, 'Convert according to the column meaning.'),
        v22_q('L23R-06', '<p>What is displayed?</p>' . v22_code('print(bool("false"))'), [['False', 'The word meaning is not interpreted.'], ['True', 'Correct: it is a non-empty string.'], ['ValueError', 'bool can accept it.'], ['false', 'A Python Boolean is displayed.']], 1, 'Compare accepted true/false text explicitly.'),
        v22_q('L23R-07', '<p>Which exception should <code>parse_read("yes")</code> raise?</p>', [['ValueError', 'Correct: the value violates the accepted vocabulary.'], ['KeyError', 'No dictionary key is absent.'], ['FileNotFoundError', 'No path is being opened.'], ['No exception; return True', 'That accepts false meaning.']], 0, 'Use ValueError for an invalid field value.'),
        v22_q('L23R-08', '<p>Required columns are <code>{"id", "title", "read"}</code>; actual columns are <code>{"id", "title"}</code>. What is the missing set?</p>', [['{"read"}', 'Correct: required minus actual.'], ['{"id", "title"}', 'Those columns exist.'], ['An empty set', 'read is absent.'], ['{"id", "title", "read"}', 'Common columns are removed.']], 0, 'Validate the header before consuming rows.'),
        v22_q('L23R-09', '<p>Why pass <code>newline=""</code> when writing CSV?</p>', [['To let the csv module manage record endings', 'Correct; it avoids platform-specific blank records.'], ['To put everything on one line', 'The writer separates records.'], ['To select UTF-8', 'encoding does that.'], ['To remove the header', 'writeheader is separate.']], 0, 'Encoding and newline have different responsibilities.'),
        v22_q('L23R-10', '<p>Which is the strongest check after saving?</p>', [['Only confirm that the output file exists', 'That does not check content.'], ['Reload with the same loader, compare expected records, and confirm the source is unchanged', 'Correct.'], ['Print Saved', 'A message is not evidence.'], ['Overwrite the input and inspect it', 'That destroys source evidence.']], 1, 'Combine round-trip equality with source preservation.'),
    ];
}

$parent = v22_parent($course, [$chaptername, $oldchapter]);
course_update_section($course, $parent, ['name' => $chaptername, 'summary' => $chaptersummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);
rebuild_course_cache($course->id, true);
$parent = v22_parent($course, [$chaptername]);

// Renumber the existing project without changing its teaching content in this step.
$project = $DB->get_record('subsection', ['course' => $course->id, 'name' => $projectname]);
if (!$project) {
    $project = $DB->get_record('subsection', ['course' => $course->id, 'name' => $oldproject], '*', MUST_EXIST);
    $project->name = $projectname;
    $project->timemodified = time();
    $DB->update_record('subsection', $project);
}
$projectsection = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $project->id], '*', MUST_EXIST);
course_update_section($course, $projectsection, ['name' => $projectname]);
foreach ([['lti', $oldprojectlti, $projectlti], ['assign', $oldprojectassign, $projectassign]] as [$table, $old, $new]) {
    $record = $DB->get_record($table, ['course' => $course->id, 'name' => $new]);
    if (!$record && ($record = $DB->get_record($table, ['course' => $course->id, 'name' => $old]))) {
        $record->name = $new;
        if (property_exists($record, 'timemodified')) $record->timemodified = time();
        $DB->update_record($table, $record);
    }
}
$projectcm = get_coursemodule_from_instance('subsection', $project->id, $course->id, false, MUST_EXIST);

$sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topic]);
if (!$sub) {
    $created = add_moduleinfo((object)['module' => $DB->get_field('modules', 'id', ['name' => 'subsection'], MUST_EXIST), 'modulename' => 'subsection', 'section' => $parent->section, 'name' => $topic, 'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0, 'completion' => 0], $course);
    $sub = $DB->get_record('subsection', ['id' => $created->instance], '*', MUST_EXIST);
}
$subcm = get_coursemodule_from_instance('subsection', $sub->id, $course->id, false, MUST_EXIST);
$section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
course_update_section($course, $section, ['name' => $topic, 'summary' => $summary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);
v22_move_before($course, $parent->id, $subcm->id, $projectcm->id);
rebuild_course_cache($course->id, true);

$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename]);
if (!$page) {
    $created = add_moduleinfo((object)['module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST), 'modulename' => 'page', 'section' => $section->section, 'name' => $pagename, 'intro' => $intro, 'introformat' => FORMAT_HTML, 'content' => $body, 'contentformat' => FORMAT_HTML, 'display' => RESOURCELIB_DISPLAY_OPEN, 'printintro' => 0, 'printlastmodified' => 0, 'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 0], $course);
    $page = $DB->get_record('page', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $page->intro = $intro; $page->introformat = FORMAT_HTML; $page->content = $body; $page->contentformat = FORMAT_HTML; $page->timemodified = time(); $DB->update_record('page', $page);
}
$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);

$lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname]);
$path = $ja ? '/ja/07_files_csv.ipynb' : '/07_files_csv.ipynb';
if (!$lti) {
    $prototype = $DB->get_record('lti', ['course' => $course->id, 'name' => $ja ? 'Python Lab 2.2：関数・エラー・テスト' : 'Python Lab 2.2: Functions, errors, and testing'], '*', MUST_EXIST);
    $toolurl = preg_replace('~/hub/user-redirect/lab/tree/.*$~', '/hub/user-redirect/lab/tree/' . ltrim($path, '/'), $prototype->toolurl);
    $created = add_moduleinfo((object)['module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST), 'modulename' => 'lti', 'section' => $section->section, 'name' => $ltiname, 'intro' => $intro, 'introformat' => FORMAT_HTML, 'typeid' => $prototype->typeid, 'toolurl' => $toolurl, 'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW, 'instructorchoicesendname' => LTI_SETTING_NEVER, 'instructorchoicesendemailaddr' => LTI_SETTING_NEVER, 'instructorchoiceacceptgrades' => LTI_SETTING_NEVER, 'grade' => 0, 'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1], $course);
    $lti = $DB->get_record('lti', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $lti->intro = $intro; $lti->introformat = FORMAT_HTML; $lti->timemodified = time(); $DB->update_record('lti', $lti);
}
$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);

$quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname]);
if (!$quiz) {
    $created = add_moduleinfo((object)['module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST), 'modulename' => 'quiz', 'section' => $section->section, 'name' => $quizname, 'intro' => $quizintro, 'introformat' => FORMAT_HTML, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0, 'overduehandling' => 'autosubmit', 'graceperiod' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'attemptonlast' => 0, 'grademethod' => QUIZ_GRADEHIGHEST, 'decimalpoints' => 0, 'questiondecimalpoints' => -1, 'questionsperpage' => 10, 'navmethod' => QUIZ_NAVMETHOD_FREE, 'shuffleanswers' => 1, 'grade' => 100, 'reviewattempt' => 69888, 'reviewcorrectness' => 4352, 'reviewmarks' => 4352, 'reviewspecificfeedback' => 4352, 'reviewgeneralfeedback' => 4352, 'reviewrightanswer' => 4352, 'reviewoverallfeedback' => 4352, 'password' => '', 'quizpassword' => '', 'subnet' => '', 'browsersecurity' => '-', 'delay1' => 0, 'delay2' => 0, 'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1], $course);
    $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    if ($DB->count_records('quiz_attempts', ['quiz' => $quiz->id])) quiz_delete_all_attempts($quiz);
    $structure = \mod_quiz\structure::create_for_quiz(\mod_quiz\quiz_settings::create($quiz->id));
    foreach (array_reverse($structure->get_slots()) as $slot) $structure->remove_slot($slot->slot);
}
$quiz->intro = $quizintro; $quiz->introformat = FORMAT_HTML; $quiz->attempts = 0; $quiz->grademethod = QUIZ_GRADEHIGHEST; $quiz->grade = 100; $quiz->questionsperpage = 10; $quiz->timemodified = time(); $DB->update_record('quiz', $quiz);
$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
if (!$category) { $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC'); $category = reset($categories); }
foreach ($questions as $data) { $saved = v22_save_question($category->id, $context->id, $shortname . ' v22: ', $data, $ja); quiz_add_quiz_question($saved->id, $quiz, 0, 10); }
$DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
$quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
$gradeitem = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0]);
if (!$gradeitem) throw new RuntimeException('Lesson 2.3 quiz grade item missing');
$gradeitem->gradepass = 90;
$gradeitem->grademax = 100;
$gradeitem->update();
v22_feedback_bands($quiz->id, $ja);
$DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_AUTOMATIC, ['id' => $quizcm->id]);
$DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $quizcm->id]);
$DB->set_field('course_modules', 'completionpassgrade', 1, ['id' => $quizcm->id]);
$DB->set_field('course_modules', 'completionview', 0, ['id' => $quizcm->id]);

$actions = \core_courseformat\formatactions::cm($course);
foreach ([$pagecm, $lticm, $quizcm] as $cm) $actions->move_end_section($cm->id, $section->id);
rebuild_course_cache($course->id, true);

echo json_encode(['courseid' => (int)$course->id, 'shortname' => $shortname, 'chapter' => $chaptername, 'topic' => $topic, 'activities' => [$pagename, $ltiname, $quizname], 'questions' => count($questions), 'lti_path' => $path, 'marker' => 'PYAI-V22-LESSON23-FLOW'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
