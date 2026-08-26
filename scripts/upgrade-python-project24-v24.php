<?php
// Replace the provisional Project 2.4 with the CSV library record manager.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';

function v24_find_named(string $table, int $courseid, array $names): stdClass {
    global $DB;
    foreach ($names as $name) {
        if ($record = $DB->get_record($table, ['course' => $courseid, 'name' => $name])) return $record;
    }
    throw new RuntimeException("Could not find $table: " . implode(' | ', $names));
}

function v24_plugin_config(int $assignmentid, string $plugin, string $subtype, string $name, string $value): void {
    global $DB;
    $params = ['assignment' => $assignmentid, 'plugin' => $plugin, 'subtype' => $subtype, 'name' => $name];
    if ($record = $DB->get_record('assign_plugin_config', $params)) {
        $record->value = $value;
        $DB->update_record('assign_plugin_config', $record);
    } else {
        $DB->insert_record('assign_plugin_config', (object)($params + ['value' => $value]));
    }
}

if ($ja) {
    $oldtopic = '2.4 実践プロジェクト：学習センター月次実績報告';
    $topic = '2.4 実践プロジェクト：CSV図書記録管理';
    $summary = '<p>2.1〜2.3のリスト・辞書、関数契約、例外、テスト、CSV入出力を統合し、図書台帳の更新プログラムを完成させます。</p>';
    $pagename = '2.4 課題仕様と完成条件';
    $ltinames = ['Python Lab 2.4：学習センター月次実績報告', 'Python Lab 2.4：CSV図書記録管理'];
    $ltiname = 'Python Lab 2.4：CSV図書記録管理';
    $assignnames = ['プロジェクト2.4：学習センター月次実績報告', 'プロジェクト2.4：CSV図書記録管理'];
    $assignname = 'プロジェクト2.4：CSV図書記録管理';
    $ltipath = '/ja/P2_csv_library_manager.ipynb';
    $ltiintro = '<p>課題案内Notebookを開き、専用CSVを確認して<code>library_manager.py</code>を実装します。自分で実行後、提供された確認プログラムの全10項目を通してください。</p>';
    $body = <<<'HTML'
<div class="python-project-brief">
<h2>2.4 実践プロジェクト — CSV図書記録管理</h2>
<h3>1. 課題の状況</h3>
<p>小規模な学習センターでは、図書台帳を<code>data/books.csv</code>に保存しています。担当者は 台帳に対する4件の更新依頼を受け取りました。元のCSVを読み込み、更新を適用し、 更新後の冊数を集計して画面へ表示し、結果を別のCSVへ保存するプログラムを作成します。</p>
<h3>2. この課題で行うこと</h3>
<p>この課題では、Pythonプログラムを一から作成しません。Python Labに用意された <code>projects/library-manager/library_manager.py</code>を開き、スターターコードの未完成の 10関数を実装してプログラムを完成させます。このPythonプログラムがCSVを読み込み、 次の処理を行います。編集するファイルは<code>library_manager.py</code>一つだけです。</p>
<ol>
<li>Pythonプログラムで配布済みの<code>books.csv</code>を読み込む（今回のサンプルは4冊）。</li>
<li>指定された4件の更新を順番に適用する。</li>
<li>更新後の冊数を集計する。</li>
<li>更新結果を<code>books_updated.csv</code>へ保存する。</li>
<li>集計結果を画面に表示する。</li>
</ol>
<p><code>load_books()</code>などの個別関数は、データ件数を4冊に固定せず処理します。ただし、 今回の<code>run_project()</code>は、配布された<code>books.csv</code>へ指定の4件を適用する専用処理です。</p>
<h3>3. 入力CSVと原本の保護</h3>
<p>配布済みの<code>projects/library-manager/data/books.csv</code>には次の内容があります。</p>
<pre><code>id,title,read
B001,Python Basics,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,false
B004,"Writing, Presenting, and Learning",true</code></pre>
<p>上記は、ファイルを開く前にも入力内容を理解できるように示したものです。Python ソースへ書き写さず、配布済みのCSVを<code>csv.DictReader</code>で読み込みます。</p>
<p>1行目はヘッダーです。<code>DictReader</code>で読み込んだ直後は、<code>id</code>、<code>title</code>、<code>read</code>を キーとする文字列の辞書になります。<code>read</code>の文字列は<code>parse_read()</code>でboolへ変換します。 B004のようにカンマを含む項目は引用符で囲まれます。自分で行を分割したり引用符を 取り除いたりせず、<code>csv</code>モジュールに処理させてください。</p>
<p><code>data/books.csv</code>は更新前の原本です。このファイルを編集または上書きしてはいけません。 更新結果は必ず<code>output/books_updated.csv</code>へ保存します。</p>
<h3>4. 4件の更新依頼を適用する方法</h3>
<p>実務では、更新依頼を別ファイルや入力画面から受け取ることもあります。今回は更新を 行う関数の作り方と組み合わせ方を学ぶため、更新依頼を別ファイルやキーボードからは 読み込みません。次の4件を<code>run_project()</code>内へ、記載された順番の関数呼出しとして 直接実装します。</p>
<pre><code>add_book(books, "B005", "Algorithms Made Clear")
mark_as_read(books, "B003")
rename_book(books, "B001", "Python Foundations")
remove_book(books, "B004")</code></pre>
<h3>5. 更新前と更新後</h3>
<table class="generaltable"><thead><tr><th>ID</th><th>更新前</th><th>操作</th><th>更新後</th></tr></thead><tbody>
<tr><td>B001</td><td>Python Basics／未読</td><td>書名変更</td><td>Python Foundations／未読</td></tr>
<tr><td>B002</td><td>Data Skills for Beginners／読了</td><td>変更なし</td><td>そのまま</td></tr>
<tr><td>B003</td><td>Networks in Practice／未読</td><td>読了へ変更</td><td>Networks in Practice／読了</td></tr>
<tr><td>B004</td><td>Writing, Presenting, and Learning／読了</td><td>削除</td><td>出力しない</td></tr>
<tr><td>B005</td><td>存在しない</td><td>未読で追加</td><td>Algorithms Made Clear／未読</td></tr>
</tbody></table>
<p>B004は最後に削除されるため、確認プログラムは<code>load_books()</code>単体でも、カンマを含む 書名を正しく読み込めたか検査します。</p>
<h3>6. 10関数の公開仕様</h3>
<p>スターターコードには、次の10関数とは別に完成済みの<code>main()</code>があります。<code>main()</code>は 既定パスを使って<code>run_project()</code>を呼び出し、返された集計結果を画面に表示します。 <code>main()</code>の名前や処理は変更しません。IDと書名は、検証・検索・保存の前に前後の空白を 取り除きます。</p>
<table class="generaltable"><thead><tr><th>関数</th><th>引数と役割</th><th>戻り値・状態変化・例外</th></tr></thead><tbody>
<tr><td><code>parse_read(value)</code></td><td>CSVの真偽値文字列を変換</td><td>前後空白と大文字小文字を無視して<code>True</code>/<code>False</code>。それ以外は<code>ValueError</code></td></tr>
<tr><td><code>load_books(path)</code></td><td>UTF-8 CSVを読む</td><td>入力順を保った本の辞書リスト。必須列不足、空欄、重複ID、不正な真偽値は<code>ValueError</code></td></tr>
<tr><td><code>find_book(books, book_id)</code></td><td>IDで線形検索</td><td>リストに保存された辞書そのもの。該当なしは<code>None</code></td></tr>
<tr><td><code>add_book(books, book_id, title)</code></td><td>未読の本を末尾へ追加</td><td>追加した保存中の辞書。IDまたは書名の空欄、ID重複は<code>ValueError</code></td></tr>
<tr><td><code>rename_book(books, book_id, new_title)</code></td><td>保存中の書名を変更</td><td>変更した辞書。空の新書名は<code>ValueError</code>、対象なしは<code>KeyError</code></td></tr>
<tr><td><code>mark_as_read(books, book_id)</code></td><td>保存中の本を読了済みに変更</td><td>変更した辞書。対象なしは<code>KeyError</code></td></tr>
<tr><td><code>remove_book(books, book_id)</code></td><td>一件を削除し、残りの順序を維持</td><td>削除した辞書。対象なしは<code>KeyError</code></td></tr>
<tr><td><code>summarise_books(books)</code></td><td>合計、読了、未読を数える</td><td><code>{"total": n, "read": n, "unread": n}</code>形式の辞書</td></tr>
<tr><td><code>save_books(books, path)</code></td><td>親フォルダを作りUTF-8 CSVを保存</td><td>戻り値は<code>None</code>。現在のリスト順、列順<code>id,title,read</code>、小文字<code>true</code>/<code>false</code>で書く</td></tr>
<tr><td><code>run_project(input_path, output_path)</code></td><td>読込、固定更新4件、集計、保存、返却を接続</td><td>集計辞書を返す。完成済みの<code>main()</code>が表示する</td></tr>
</tbody></table>
<p>CSVの余分な列は無視します。完全に空のファイルは必須列不足として<code>ValueError</code>、 正しいヘッダーだけでデータ行がないCSVは空リストとして扱います。保存時にID順へ 並べ替えず、読み込みと更新で生じた現在のリスト順を維持します。</p>
<h3>7. パスの基準</h3>
<p>入出力パスを作るコードはスターターに用意されています。ターミナルの現在位置に かかわらず、スクリプト自身の場所を基準にファイルを見つけます。定数名と既定パスは 変更しません。</p>
<h3>8. 段階的な実装順</h3>
<ol>
<li><code>parse_read()</code>と<code>load_books()</code>を完成させ、4件とbool型を確認する。</li>
<li><code>find_book()</code>を完成させ、存在するIDと存在しないIDを確認する。</li>
<li>追加、書名変更、読了変更、削除の4関数を完成させる。</li>
<li><code>summarise_books()</code>で件数を計算する。</li>
<li><code>save_books()</code>で別の出力CSVを作り、再読込する。</li>
<li><code>run_project()</code>で読込、固定更新4件、集計、保存、返却を順番につなぐ。</li>
<li>すべてのTODOを完成させ、最後の<code>print("PROGRAM INCOMPLETE")</code>行を削除する。</li>
</ol>
<h3>9. 手動確認</h3>
<p><strong>Ctrl+S</strong>で保存してから実行します。</p>
<pre><code>python projects/library-manager/library_manager.py</code></pre>
<p>画面表示は次です。</p>
<pre><code>LIBRARY UPDATE REPORT
TOTAL BOOKS: 4
READ BOOKS: 2
UNREAD BOOKS: 2
OUTPUT FILE: books_updated.csv</code></pre>
<p>生成CSVは次の内容になります。</p>
<pre><code>id,title,read
B001,Python Foundations,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,true
B005,Algorithms Made Clear,false</code></pre>
<p>この文字列を直接書くのではなく、本の辞書リストから<code>csv.DictWriter</code>で作成します。</p>
<h3>10. 自動確認と提出</h3>
<p><code>python projects/library-manager/check_library_manager.py</code>を実行します。変更するのは <code>library_manager.py</code>だけです。全10項目が<code>[OK]</code>となり、最後に<code>ALL TESTS PASSED</code>が 表示されるまで修正します。元CSVが変更されていないことも、もう一度確認します。</p>
<p>Python Labのファイル一覧で<code>library_manager.py</code>を右クリックしてダウンロードし、 Moodleの提出課題へその一つだけをアップロードします。</p>
<p style="display:none">PYAI-V25-PROJECT24-LEARNER-BRIEF</p>
<p style="display:none">PYAI-V24-PROJECT24-LIBRARY</p>
</div>
HTML;
} else {
    $oldtopic = '2.4 Applied project: Monthly centre performance report';
    $topic = '2.4 Applied project: CSV library record manager';
    $summary = '<p>Integrate the Chapter 2.1–2.3 list/dictionary, function contract, exception, test, and CSV skills in a small catalogue-update program.</p>';
    $pagename = '2.4 Project brief and completion criteria';
    $ltinames = ['Python Lab 2.4: Monthly centre performance report', 'Python Lab 2.4: CSV library record manager'];
    $ltiname = 'Python Lab 2.4: CSV library record manager';
    $assignnames = ['Project 2.4: Monthly learning-centre performance report', 'Project 2.4: CSV library record manager'];
    $assignname = 'Project 2.4: CSV library record manager';
    $ltipath = '/P2_csv_library_manager.ipynb';
    $ltiintro = '<p>Open the project guide, inspect the dedicated CSV, implement <code>library_manager.py</code>, run it yourself, and pass all ten supplied checker areas.</p>';
    $body = <<<'HTML'
<div class="python-project-brief">
<h2>Project 2.4 — CSV library record manager</h2>
<h3>1. Situation</h3>
<p>A small learning centre stores its book catalogue in <code>data/books.csv</code>. A staff member has received four requests to update the catalogue. Build a program that loads the source CSV, applies those requests, counts the updated records, displays a report, and saves the result as a separate CSV.</p>
<h3>2. What you will do</h3>
<p>You do not create the program from an empty file. In Python Lab, open the supplied starter at <code>projects/library-manager/library_manager.py</code> and complete its ten unfinished functions. This Python program will read and process the CSV. Edit only <code>library_manager.py</code>.</p>
<ol>
<li>Make the Python program load the supplied <code>books.csv</code> (the sample contains four books).</li>
<li>Apply four specified updates in order.</li>
<li>Count the books after the updates.</li>
<li>Save the updated records to <code>books_updated.csv</code>.</li>
<li>Display the summary on screen.</li>
</ol>
<p>Functions such as <code>load_books()</code> process any valid number of records rather than fixing the input at four books. However, this project's <code>run_project()</code> is specific to the supplied <code>books.csv</code> and applies the four update requests shown below.</p>
<h3>3. Input CSV and source protection</h3>
<p>The supplied <code>projects/library-manager/data/books.csv</code> contains:</p>
<pre><code>id,title,read
B001,Python Basics,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,false
B004,"Writing, Presenting, and Learning",true</code></pre>
<p>This listing is provided so you can understand the input before opening the file. Do not copy it into the Python source. Read the supplied file with <code>csv.DictReader</code>.</p>
<p>The first row is the header. <code>DictReader</code> initially produces string values with the keys <code>id</code>, <code>title</code>, and <code>read</code>; <code>parse_read()</code> converts the <code>read</code> text to a bool. B004 shows that a field containing commas is quoted. Do not split lines yourself or remove the quotes yourself—the <code>csv</code> module handles them.</p>
<p><code>data/books.csv</code> is the unchanged source record. Do not edit or overwrite it. Always save the result to <code>output/books_updated.csv</code>.</p>
<h3>4. How to apply the four update requests</h3>
<p>In a larger system, update requests might come from another file or a user interface. This project focuses on writing and combining update functions, so the four requests are not another file and are not keyboard input. Implement these fixed function calls directly inside <code>run_project()</code>, in this order:</p>
<pre><code>add_book(books, "B005", "Algorithms Made Clear")
mark_as_read(books, "B003")
rename_book(books, "B001", "Python Foundations")
remove_book(books, "B004")</code></pre>
<h3>5. Before and after</h3>
<table class="generaltable"><thead><tr><th>ID</th><th>Before</th><th>Operation</th><th>After</th></tr></thead><tbody>
<tr><td>B001</td><td>Python Basics / unread</td><td>rename</td><td>Python Foundations / unread</td></tr>
<tr><td>B002</td><td>Data Skills for Beginners / read</td><td>none</td><td>unchanged</td></tr>
<tr><td>B003</td><td>Networks in Practice / unread</td><td>mark read</td><td>Networks in Practice / read</td></tr>
<tr><td>B004</td><td>Writing, Presenting, and Learning / read</td><td>remove</td><td>not written</td></tr>
<tr><td>B005</td><td>absent</td><td>add unread</td><td>Algorithms Made Clear / unread</td></tr>
</tbody></table>
<p>Because B004 is later removed, the checker tests <code>load_books()</code> independently to confirm that its comma-containing title was read correctly.</p>
<h3>6. Public contract for all ten functions</h3>
<p>The starter also contains a completed <code>main()</code> in addition to the ten functions below. <code>main()</code> calls <code>run_project()</code> with the default paths and displays the returned summary. Do not rename or change <code>main()</code>. IDs and titles are stripped of surrounding whitespace before validation, search, or storage.</p>
<table class="generaltable"><thead><tr><th>Function</th><th>Inputs and responsibility</th><th>Return, mutation, and exceptions</th></tr></thead><tbody>
<tr><td><code>parse_read(value)</code></td><td>convert CSV Boolean text</td><td>ignore surrounding space and case; return bool; otherwise <code>ValueError</code></td></tr>
<tr><td><code>load_books(path)</code></td><td>read UTF-8 CSV</td><td>dictionaries in input order; invalid columns, blanks, duplicates, or Booleans raise <code>ValueError</code></td></tr>
<tr><td><code>find_book(books, book_id)</code></td><td>linear ID search</td><td>stored dictionary or <code>None</code></td></tr>
<tr><td><code>add_book(books, book_id, title)</code></td><td>append an unread record</td><td>stored new dictionary; blank ID/title or duplicate ID raises <code>ValueError</code></td></tr>
<tr><td><code>rename_book(books, book_id, new_title)</code></td><td>mutate stored title</td><td>stored changed dictionary; blank title <code>ValueError</code>; absent ID <code>KeyError</code></td></tr>
<tr><td><code>mark_as_read(books, book_id)</code></td><td>mutate stored read state</td><td>stored changed dictionary; absent ID <code>KeyError</code></td></tr>
<tr><td><code>remove_book(books, book_id)</code></td><td>remove one while preserving remaining order</td><td>removed dictionary; absent ID <code>KeyError</code></td></tr>
<tr><td><code>summarise_books(books)</code></td><td>count total/read/unread</td><td><code>{"total": n, "read": n, "unread": n}</code></td></tr>
<tr><td><code>save_books(books, path)</code></td><td>create parent and write UTF-8 CSV</td><td><code>None</code>; current list order, <code>id,title,read</code>, lower-case Booleans</td></tr>
<tr><td><code>run_project(input_path, output_path)</code></td><td>load the input, apply four fixed updates, summarise, save, return</td><td>summary dictionary; completed <code>main()</code> prints it</td></tr>
</tbody></table>
<p>Ignore extra CSV columns. A completely empty file raises <code>ValueError</code> for missing columns; a correct header with no data rows returns an empty list. Do not sort during saving; preserve the current list order.</p>
<h3>7. Path basis</h3>
<p>The starter already constructs the input and output paths from the script's own folder. The program therefore finds its files regardless of the terminal's current directory. Do not change the constant names or default paths.</p>
<h3>8. Implementation stages</h3>
<ol>
<li>Complete <code>parse_read()</code> and <code>load_books()</code>; confirm four records and bool values.</li>
<li>Complete <code>find_book()</code>; check a present and an absent ID.</li>
<li>Complete add, rename, mark-read, and remove.</li>
<li>Complete <code>summarise_books()</code>.</li>
<li>Complete <code>save_books()</code> and reload its output.</li>
<li>In <code>run_project()</code>, connect load, updates, summary, save, and return in that order.</li>
<li>Finish every TODO and delete the final <code>print("PROGRAM INCOMPLETE")</code> line.</li>
</ol>
<h3>9. Manual check</h3>
<p>Save with <strong>Ctrl+S</strong>, then run:</p>
<pre><code>python projects/library-manager/library_manager.py</code></pre>
<p>The report must be:</p>
<pre><code>LIBRARY UPDATE REPORT
TOTAL BOOKS: 4
READ BOOKS: 2
UNREAD BOOKS: 2
OUTPUT FILE: books_updated.csv</code></pre>
<p>The generated CSV must be:</p>
<pre><code>id,title,read
B001,Python Foundations,false
B002,Data Skills for Beginners,true
B003,Networks in Practice,true
B005,Algorithms Made Clear,false</code></pre>
<p>Create this CSV from the record list with <code>csv.DictWriter</code>; do not write the shown CSV as one fixed string.</p>
<h3>10. Automatic check and submission</h3>
<p>Run <code>python projects/library-manager/check_library_manager.py</code>. Change only <code>library_manager.py</code> until all ten areas show <code>[OK]</code> and the last line is <code>ALL TESTS PASSED</code>. Confirm again that the source CSV is unchanged.</p>
<p>Right-click <code>library_manager.py</code> in the Python Lab file browser, download it, and upload that one file to the Moodle assignment.</p>
<p style="display:none">PYAI-V25-PROJECT24-LEARNER-BRIEF</p>
<p style="display:none">PYAI-V24-PROJECT24-LIBRARY</p>
</div>
HTML;
}

$subsection = v24_find_named('subsection', $course->id, [$topic, $oldtopic]);
$subsection->name = $topic;
$subsection->timemodified = time();
$DB->update_record('subsection', $subsection);
$section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $section, ['summary' => $summary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename]);
if (!$page) {
    $created = add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'modulename' => 'page', 'section' => $section->section, 'name' => $pagename,
        'intro' => $summary, 'introformat' => FORMAT_HTML, 'content' => $body, 'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_OPEN, 'printintro' => 0, 'printlastmodified' => 0,
        'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
        'completion' => 0, 'showdescription' => 1,
    ], $course);
    $page = $DB->get_record('page', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $page->intro = $summary; $page->introformat = FORMAT_HTML;
    $page->content = $body; $page->contentformat = FORMAT_HTML; $page->timemodified = time();
    $DB->update_record('page', $page);
}

$lti = v24_find_named('lti', $course->id, $ltinames);
$lti->name = $ltiname; $lti->intro = $ltiintro; $lti->introformat = FORMAT_HTML;
$newurl = preg_replace('~/hub/user-redirect/lab/tree/.*$~', '/hub/user-redirect/lab/tree/' . ltrim($ltipath, '/'), $lti->toolurl);
if (!$newurl || !str_ends_with($newurl, $ltipath)) throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
$lti->toolurl = $newurl; $lti->timemodified = time(); $DB->update_record('lti', $lti);

$assign = v24_find_named('assign', $course->id, $assignnames);
$assign->name = $assignname; $assign->intro = $body; $assign->introformat = FORMAT_HTML;
$assign->grade = 100; $assign->timemodified = time(); $DB->update_record('assign', $assign);
v24_plugin_config($assign->id, 'file', 'assignsubmission', 'enabled', '1');
v24_plugin_config($assign->id, 'file', 'assignsubmission', 'maxfilesubmissions', '1');
v24_plugin_config($assign->id, 'file', 'assignsubmission', 'allowedfiletypes', '.py');

$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
$assigncm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
$section->sequence = implode(',', [$pagecm->id, $lticm->id, $assigncm->id]);
$DB->update_record('course_sections', $section);
rebuild_course_cache($course->id, true);

echo json_encode(['courseid' => (int)$course->id, 'shortname' => $shortname, 'topic' => $topic, 'activities' => [$pagename, $ltiname, $assignname], 'lti_path' => $ltipath], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
