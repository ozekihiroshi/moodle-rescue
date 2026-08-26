<?php
// Rewrite Chapter 2.1 and normalise its activity numbering.
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

function v19_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v19_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v19_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
            ? '<p>正解です。構造が保持するものと、操作後に変化するものを説明してから次へ進みましょう。</p>'
            : '<p>Correct. Explain what the structure preserves and what the operation changes.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>型、添字またはキー、変更前後の値をNotebookで表示し、もう一度確認しましょう。</p>'
            : '<p>Print the type, index or key, and values before and after the operation in the Notebook, then try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v19_find_and_rename(int $courseid, string $table, string $oldname, string $newname): stdClass {
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
    $chaptersummary = '<p>複数の値を目的に合うデータ構造へ整理し、次に再利用可能で検証しやすい関数へ発展させます。</p><ol><li>2.1 リスト・辞書・レコード</li><li>2.2 関数・エラー・テスト</li><li>2.3 実践プロジェクト：学習センター月次実績報告</li></ol>';
    $topicname = '2.1 リスト・辞書・レコード';
    $topicsummary = '<p>順序、名前、重複、変更可能性に応じて、リスト、タプル、辞書、集合を選び、複数のレコードを表します。</p>';
    $oldpage = 'レッスン5：リストと辞書';
    $oldlti = 'Python Lab 05：リスト・辞書・レコード';
    $oldquiz = '理解度チェック：レッスン5 リストと辞書';
    $pagename = 'レッスン2.1：リスト・辞書・レコード';
    $ltiname = 'Python Lab 2.1：リスト・辞書・レコード';
    $quizname = '理解度チェック：2.1 リスト・辞書・レコード';
    $pageintro = '<p>複数の値を、順序付きの値、固定された組、名前付き項目、重複のない所属として表します。</p>';
    $quizintro = '<p>リスト、タプル、辞書、集合の値と操作結果を短いコードで確認します。何度でも挑戦でき、最高点が記録されます。</p>';
    $body = '<div class="python-sample-lesson"><h2>関連する値を一つの構造へまとめる</h2>'
        . '<p>Chapter 1では、複数週の出席者数をリストとして与え、ループで処理しました。ここでは、そのリスト自体を体系的に学びます。さらに、名前、登録者数、修了者数のように意味の異なる項目を一件のレコードへまとめ、複数のレコードを扱える形へ進みます。</p>'
        . '<h3>リストは順序を持ち、内容を変更できる</h3>'
        . '<p>リストは角括弧で作ります。文字列と同じく添字は0から始まり、負の添字は末尾から数え、スライスは終了位置を含みません。<code>len()</code>は要素数、<code>in</code>は値が含まれるかを返します。Pythonは異なる型も同じリストへ入れられますが、実務では同じ意味の値をそろえる方が安全です。</p>'
        . v19_code("attendance = [28, 31, 30, 33]\nprint(attendance[0])\nprint(attendance[-1])\nprint(attendance[1:3])\nprint(len(attendance))\nprint(31 in attendance)")
        . '<h3>リストの変更には、位置の代入と変更メソッドを使う</h3>'
        . '<p>添字への代入は既存要素を置き換えます。<code>append()</code>は一つ、<code>extend()</code>は複数、<code>insert()</code>は指定位置へ追加します。<code>remove()</code>は最初に一致した値、<code>pop()</code>は位置の値を取り除いて返します。</p>'
        . v19_code("attendance = [28, 31, 30]\nattendance[0] = 29\nattendance.append(33)\nattendance.extend([32, 34])\nremoved = attendance.pop()\nprint(attendance)\nprint(\"取り出した値:\", removed)")
        . '<p><code>append()</code>など多くの変更メソッドは、既存のリスト自身を変更し、戻り値は<code>None</code>です。<code>attendance = attendance.append(33)</code>と書くと、名前がNoneを指してしまう典型的な誤りになります。</p>'
        . '<h3>代入はリストを複製せず、同じオブジェクトを共有する</h3>'
        . '<p><code>shared = original</code>では、二つの名前が同じリストを参照します。一方から追加すると両方から見えます。独立した浅いコピーには<code>copy()</code>または全体スライスを使います。</p>'
        . v19_code("original = [28, 31, 30]\nshared = original\nseparate = original.copy()\nshared.append(33)\nprint(\"original:\", original)\nprint(\"shared:\", shared)\nprint(\"separate:\", separate)")
        . '<p>浅いコピーは外側のリストだけを複製します。内側に別のリストや辞書がある場合、その内側のオブジェクトは共有されます。入れ子構造を変更するときは、どこまで独立させる必要があるかを確認します。</p>'
        . '<h3>タプルは順序を持つが、作成後に要素を置き換えられない</h3>'
        . '<p>タプルは丸括弧で作り、添字やループで読めます。年と月、座標など、固定された一組として扱い、途中で要素を変更させたくない値に向きます。要素数に合わせて複数の名前へ展開することもできます。</p>'
        . v19_code("report_period = (2026, 8)\nyear, month = report_period\nprint(year, month)\n# report_period[0] = 2027  # TypeError")
        . '<h3>辞書はキーによって各値の意味を表す</h3>'
        . '<p>辞書は<code>キー: 値</code>の組を波括弧で作ります。数値の位置ではなく、<code>centre[\"registered\"]</code>のようなキーで読みます。キーは一つの辞書内で一意で、同じキーへ代入すると値を更新し、新しいキーへ代入すると項目を追加します。</p>'
        . v19_code("centre = {\n    \"name\": \"North Learning Centre\",\n    \"district\": \"North\",\n    \"registered\": 40,\n    \"completed\": 32,\n}\ncentre[\"registered\"] = 42\ncentre[\"phone\"] = \"000-000\"\nprint(centre)")
        . '<h3>存在しないキーをどう扱うかは、項目の意味で決める</h3>'
        . '<p>角括弧で存在しないキーを読むと<code>KeyError</code>になります。電話番号のような任意項目なら<code>get(\"phone\", \"未登録\")</code>で既定値を返せます。しかし登録者数のような必須項目にgetを使って0を補うと、欠落と本当の0を区別できません。<code>in</code>で存在を確認し、必須項目の欠落は無効データとして扱います。</p>'
        . v19_code("centre = {\"name\": \"North\", \"registered\": 40}\nprint(centre.get(\"phone\", \"未登録\"))\nprint(\"completed\" in centre)")
        . '<h3>辞書を反復するときは、必要な部分を選ぶ</h3>'
        . '<p>辞書を直接for文へ渡すとキーを反復します。<code>keys()</code>もキー、<code>values()</code>は値、<code>items()</code>はキーと値の組を返します。</p>'
        . v19_code("centre = {\"registered\": 40, \"attended\": 34, \"completed\": 32}\nfor field, value in centre.items():\n    print(field, value)")
        . '<h3>リストの中に辞書を置くと、複数のレコードになる</h3>'
        . '<p>一つの辞書を一件のレコード、各キーを項目名として扱います。同じキー構成の辞書をリストへ並べると、小さな表のようなデータになります。これは後でCSVをDataFrameとして扱うための橋です。</p>'
        . v19_code("centres = [\n    {\"name\": \"North\", \"district\": \"A\", \"registered\": 40, \"completed\": 32},\n    {\"name\": \"South\", \"district\": \"B\", \"registered\": 35, \"completed\": 24},\n]\nfor centre in centres:\n    rate = centre[\"completed\"] / centre[\"registered\"] * 100\n    print(f\"{centre['name']}: {rate:.1f}%\")")
        . '<h3>集合は重複のない所属と集合演算を表す</h3>'
        . '<p>集合は値の順序や重複ではなく、「含まれるか」を扱います。和集合<code>|</code>、積集合<code>&amp;</code>、差集合<code>-</code>を使うと、提供コースと希望コースのようなカテゴリを比較できます。集合の表示順には依存しません。空集合は<code>set()</code>で作ります。</p>'
        . v19_code("offered = {\"Python\", \"Data\", \"Office\"}\nrequested = {\"Python\", \"Web\", \"Data\"}\nprint(\"共通:\", offered & requested)\nprint(\"未提供:\", requested - offered)\nprint(\"すべて:\", offered | requested)")
        . '<table class="generaltable"><thead><tr><th>構造</th><th>順序</th><th>変更</th><th>主な用途</th></tr></thead><tbody><tr><td>list</td><td>あり</td><td>可</td><td>同じ意味の値の並び</td></tr><tr><td>tuple</td><td>あり</td><td>不可</td><td>固定された組</td></tr><tr><td>dict</td><td>挿入順を保持</td><td>可</td><td>名前付き項目</td></tr><tr><td>set</td><td>順序に依存しない</td><td>可</td><td>重複のない所属</td></tr></tbody></table>'
        . '<h3>例題から応用へ</h3><p>3センターを、<code>name</code>、<code>district</code>、<code>registered</code>、<code>completed</code>を持つ辞書としてリストへ格納します。ループで各修了率を表示し、75%未満のセンター名を別のリストへ追加し、地区の集合を作ってください。登録者数0や必須キー欠落を通常の修了率として処理しないことも確認します。</p>'
        . '<p>このレッスンを終えると、順序、名前、重複、変更可能性を基準に構造を選び、リストと辞書を組み合わせてレコードを処理できます。次は、この処理を関数へ分け、エラーとテストを扱います。</p>'
        . '<p><strong>学習時間の目安：</strong>約4時間</p><p style="display:none">PYAI-V19-LESSON21-FLOW</p></div>';
    $questions = [
        v19_question('L21R-01', '<p>何が表示されますか。</p>' . v19_code('values = [10, 20, 30, 40]\nprint(values[-1], values[1:3])'), [['40 [20, 30]', '正解です。-1は最後、スライスは終了位置を含みません。'], ['10 [20, 30, 40]', '負の添字とスライス終端を確認してください。'], ['40 [20, 30, 40]', '位置3は含みません。'], ['IndexError', 'どちらの参照も有効です。']], 0, 'リストの添字とスライスは文字列と同じ境界規則を使います。'),
        v19_question('L21R-02', '<p>何が表示されますか。</p>' . v19_code('values = [1, 2]\nresult = values.append(3)\nprint(values, result)'), [['[1, 2] 3', 'appendは追加値を返しません。'], ['[1, 2, 3] None', '正解です。既存リストを変更しNoneを返します。'], ['[1, 2] None', 'リスト自身は変更されます。'], ['TypeError', '有効な操作です。']], 1, '変更メソッドの戻り値を、変更後のリストと取り違えないようにします。'),
        v19_question('L21R-03', '<p>何が表示されますか。</p>' . v19_code('original = [1, 2]\nshared = original\ncopy = original.copy()\nshared.append(3)\nprint(original, copy)'), [['[1, 2] [1, 2, 3]', 'sharedはoriginalと同じリストです。'], ['[1, 2, 3] [1, 2]', '正解です。copyだけが独立しています。'], ['[1, 2, 3] [1, 2, 3]', '浅いコピーの外側は独立しています。'], ['NameError', 'すべての名前は定義済みです。']], 1, '単純代入は同じオブジェクトを共有し、copy()は別の外側リストを作ります。'),
        v19_question('L21R-04', '<p>二行目で何が起こりますか。</p>' . v19_code('period = (2026, 8)\nperiod[1] = 9'), [['(2026, 9)になる', 'タプルの要素は置き換えられません。'], ['TypeErrorになる', '正解です。タプルは変更不能です。'], ['IndexErrorになる', '位置1は存在します。'], ['何も起こらない', '代入はエラーになります。']], 1, 'タプルは順序を持ちますが、作成後に要素を変更できません。'),
        v19_question('L21R-05', '<p>何が表示されますか。</p>' . v19_code('centre = {"name": "North", "registered": 40}\nprint(centre.get("phone", "未登録"))'), [['None', '既定値を指定しています。'], ['未登録', '正解です。キーがないため既定値を返します。'], ['KeyError', 'getは欠落キーで例外を出しません。'], ['phone', 'キー名そのものは返しません。']], 1, 'get()は任意項目の欠落に明示した既定値を返せます。'),
        v19_question('L21R-06', '<p>何が表示されますか。</p>' . v19_code('record = {"count": 3}\nrecord["count"] = 4\nrecord["status"] = "ready"\nprint(len(record), record["count"])'), [['1 3', '更新と追加の両方が行われます。'], ['1 4', 'statusという新しいキーが増えます。'], ['2 4', '正解です。既存キーを更新し、新規キーを追加します。'], ['2 3', 'countは4へ更新されます。']], 2, '既存キーへの代入は更新、新規キーへの代入は項目追加です。'),
        v19_question('L21R-07', '<p>最終的なtotalはいくつですか。</p>' . v19_code('record = {"registered": 40, "completed": 32}\ntotal = 0\nfor field, value in record.items():\n    total += value\nprint(total)'), [['2', '項目数ではなく値を加えています。'], ['32', 'registeredも加えます。'], ['72', '正解です。items()から二つの値を受け取ります。'], ['KeyError', 'すべての項目を反復しています。']], 2, 'items()は各キーと値の組を返します。'),
        v19_question('L21R-08', '<p>何が表示されますか。</p>' . v19_code('centres = [{"name": "A", "registered": 40, "completed": 30}, {"name": "B", "registered": 20, "completed": 18}]\nfor centre in centres:\n    if centre["completed"] / centre["registered"] < 0.8:\n        print(centre["name"])'), [['Aだけ', '正解です。Aは75%、Bは90%です。'], ['Bだけ', 'Bは80%以上です。'], ['AとB', 'Bは条件に一致しません。'], ['何も表示しない', 'Aは条件に一致します。']], 0, 'リストから一件ずつ辞書を取り出し、キーで必要な項目を読みます。'),
        v19_question('L21R-09', '<p>何が表示されますか。順序は問いません。</p>' . v19_code('offered = {"Python", "Data"}\nrequested = {"Python", "Web"}\nprint(requested - offered)'), [['Pythonだけ', 'Pythonは両方にあります。'], ['Webだけ', '正解です。希望にあり提供にない要素です。'], ['PythonとWeb', '差集合は共通要素を除きます。'], ['空集合', 'Webはofferedにありません。']], 1, '集合の差は左側にあり右側にない要素を返します。'),
        v19_question('L21R-10', '<p>「複数センターについて、名前付き項目を保持し、順に処理する」構造として最も適切なのはどれですか。</p>', [['一つのset', '名前付き項目とレコードのまとまりを表しにくくなります。'], ['一つのtupleだけ', '複数の名前付きレコードには不足します。'], ['辞書を要素とするリスト', '正解です。一件を辞書、複数件をリストで表します。'], ['互いに無関係な変数だけ', '件数が増えたときに一括処理できません。']], 2, '複数レコードは、名前付き項目を持つ辞書をリストへ並べて表せます。'),
    ];
} else {
    $chaptername = 'Chapter 2 — Data Structures and Reliable Programs';
    $chaptersummary = '<p>Organise related values with suitable data structures, then develop the work into reusable, testable functions.</p><ol><li>2.1 Lists, dictionaries, and records</li><li>2.2 Functions, errors, and testing</li><li>2.3 Applied project: Monthly centre performance report</li></ol>';
    $topicname = '2.1 Lists, dictionaries, and records';
    $topicsummary = '<p>Choose lists, tuples, dictionaries, and sets according to order, names, duplicates, and mutability, then represent several records.</p>';
    $oldpage = 'Lesson 5: Lists and dictionaries';
    $oldlti = 'Python Lab 05: Lists, dictionaries, and records';
    $oldquiz = 'Knowledge check: Lesson 5: Lists and dictionaries';
    $pagename = 'Lesson 2.1: Lists, dictionaries, and records';
    $ltiname = 'Python Lab 2.1: Lists, dictionaries, and records';
    $quizname = 'Knowledge check: 2.1 Lists, dictionaries, and records';
    $pageintro = '<p>Represent several values as ordered values, fixed groups, named fields, and unique membership.</p>';
    $quizintro = '<p>Check the values and effects of list, tuple, dictionary, and set operations in short code. Attempts are unlimited and the highest score is retained.</p>';
    $body = '<div class="python-sample-lesson"><h2>Organise related values as one structure</h2>'
        . '<p>Chapter 1 supplied weekly attendance in a list so a loop could process it. This lesson studies that list systematically, then combines fields with different meanings—name, registration, completion—into one record and several records.</p>'
        . '<h3>A list is ordered and mutable</h3>'
        . '<p>Create a list with brackets. Indexes begin at zero, negative indexes count from the end, and a slice excludes its end. <code>len()</code> returns the item count and <code>in</code> tests membership. Python permits mixed types, but operational lists are safer when their items share one meaning.</p>'
        . v19_code("attendance = [28, 31, 30, 33]\nprint(attendance[0])\nprint(attendance[-1])\nprint(attendance[1:3])\nprint(len(attendance))\nprint(31 in attendance)")
        . '<h3>Modify a list through positions and mutation methods</h3>'
        . '<p>Index assignment replaces an item. <code>append()</code> adds one item, <code>extend()</code> adds several, and <code>insert()</code> adds at a position. <code>remove()</code> deletes the first matching value; <code>pop()</code> removes and returns the value at a position.</p>'
        . v19_code("attendance = [28, 31, 30]\nattendance[0] = 29\nattendance.append(33)\nattendance.extend([32, 34])\nremoved = attendance.pop()\nprint(attendance)\nprint(\"Removed:\", removed)")
        . '<p>Most mutation methods, including <code>append()</code>, change the existing list and return <code>None</code>. Writing <code>attendance = attendance.append(33)</code> therefore replaces the useful name with None.</p>'
        . '<h3>Assignment shares one list rather than duplicating it</h3>'
        . '<p>After <code>shared = original</code>, both names refer to the same list. A mutation through either name is visible through both. Use <code>copy()</code> or a full slice for an independent shallow copy.</p>'
        . v19_code("original = [28, 31, 30]\nshared = original\nseparate = original.copy()\nshared.append(33)\nprint(\"original:\", original)\nprint(\"shared:\", shared)\nprint(\"separate:\", separate)")
        . '<p>A shallow copy duplicates only the outer list. If it contains an inner list or dictionary, that inner object remains shared. When modifying nested data, identify which levels must be independent.</p>'
        . '<h3>A tuple is ordered but immutable</h3>'
        . '<p>Create a tuple with parentheses and read it by index or iteration. A fixed group such as report year and month should not have individual elements replaced. A tuple can also unpack into the same number of names.</p>'
        . v19_code("report_period = (2026, 8)\nyear, month = report_period\nprint(year, month)\n# report_period[0] = 2027  # TypeError")
        . '<h3>A dictionary names each field with a key</h3>'
        . '<p>A dictionary stores <code>key: value</code> pairs in braces. Read by a meaningful key such as <code>centre[\"registered\"]</code>, not a numeric position. Keys are unique: assignment to an existing key updates it, while assignment to a new key adds a field.</p>'
        . v19_code("centre = {\n    \"name\": \"North Learning Centre\",\n    \"district\": \"North\",\n    \"registered\": 40,\n    \"completed\": 32,\n}\ncentre[\"registered\"] = 42\ncentre[\"phone\"] = \"000-000\"\nprint(centre)")
        . '<h3>Handle a missing key according to the field meaning</h3>'
        . '<p>Brackets raise <code>KeyError</code> for a missing key. For an optional phone field, <code>get(\"phone\", \"not recorded\")</code> can return a default. Do not silently replace a missing required registration with zero; missing and genuine zero mean different things. Use <code>in</code> and treat a missing required field as invalid data.</p>'
        . v19_code("centre = {\"name\": \"North\", \"registered\": 40}\nprint(centre.get(\"phone\", \"not recorded\"))\nprint(\"completed\" in centre)")
        . '<h3>Select the dictionary view needed by the loop</h3>'
        . '<p>A direct for loop over a dictionary yields keys. <code>keys()</code> also provides keys, <code>values()</code> provides values, and <code>items()</code> provides key-value pairs.</p>'
        . v19_code("centre = {\"registered\": 40, \"attended\": 34, \"completed\": 32}\nfor field, value in centre.items():\n    print(field, value)")
        . '<h3>A list of dictionaries represents several records</h3>'
        . '<p>Treat one dictionary as one record and each key as a field name. A list of dictionaries with consistent keys behaves like a small table and forms a conceptual bridge to CSV files and DataFrames.</p>'
        . v19_code("centres = [\n    {\"name\": \"North\", \"district\": \"A\", \"registered\": 40, \"completed\": 32},\n    {\"name\": \"South\", \"district\": \"B\", \"registered\": 35, \"completed\": 24},\n]\nfor centre in centres:\n    rate = centre[\"completed\"] / centre[\"registered\"] * 100\n    print(f\"{centre['name']}: {rate:.1f}%\")")
        . '<h3>A set represents unique membership and supports set operations</h3>'
        . '<p>A set focuses on presence rather than order or duplicates. Union <code>|</code>, intersection <code>&amp;</code>, and difference <code>-</code> compare categories such as offered and requested courses. Never depend on set display order. Create an empty set with <code>set()</code>.</p>'
        . v19_code("offered = {\"Python\", \"Data\", \"Office\"}\nrequested = {\"Python\", \"Web\", \"Data\"}\nprint(\"Common:\", offered & requested)\nprint(\"Not offered:\", requested - offered)\nprint(\"All:\", offered | requested)")
        . '<table class="generaltable"><thead><tr><th>Structure</th><th>Ordered</th><th>Mutable</th><th>Main use</th></tr></thead><tbody><tr><td>list</td><td>yes</td><td>yes</td><td>sequence of similarly meaningful values</td></tr><tr><td>tuple</td><td>yes</td><td>no</td><td>fixed group</td></tr><tr><td>dict</td><td>insertion order</td><td>yes</td><td>named fields</td></tr><tr><td>set</td><td>do not depend on order</td><td>yes</td><td>unique membership</td></tr></tbody></table>'
        . '<h3>From guided example to transfer</h3><p>Store three centres as dictionaries containing <code>name</code>, <code>district</code>, <code>registered</code>, and <code>completed</code>, inside one list. Display every completion rate, append names below 75% to a separate list, and create a set of districts. Confirm that zero registration or a missing required key does not produce an ordinary rate.</p>'
        . '<p>After this lesson, you can choose structures using order, names, duplicates, and mutability, then combine lists and dictionaries to process records. The next lesson separates that processing into functions and introduces errors and tests.</p>'
        . '<p><strong>Estimated study time:</strong> about 4 hours</p><p style="display:none">PYAI-V19-LESSON21-FLOW</p></div>';
    $questions = [
        v19_question('L21R-01', '<p>What is displayed?</p>' . v19_code('values = [10, 20, 30, 40]\nprint(values[-1], values[1:3])'), [['40 [20, 30]', 'Correct: -1 is last and the slice excludes its end.'], ['10 [20, 30, 40]', 'Recheck the negative index and slice endpoint.'], ['40 [20, 30, 40]', 'Position three is excluded.'], ['IndexError', 'Both references are valid.']], 0, 'List indexes and slices use the same boundary rules as strings.'),
        v19_question('L21R-02', '<p>What is displayed?</p>' . v19_code('values = [1, 2]\nresult = values.append(3)\nprint(values, result)'), [['[1, 2] 3', 'append does not return the added value.'], ['[1, 2, 3] None', 'Correct: it mutates the list and returns None.'], ['[1, 2] None', 'The list itself changes.'], ['TypeError', 'The operation is valid.']], 1, 'Do not confuse a mutation method return value with the changed list.'),
        v19_question('L21R-03', '<p>What is displayed?</p>' . v19_code('original = [1, 2]\nshared = original\ncopy = original.copy()\nshared.append(3)\nprint(original, copy)'), [['[1, 2] [1, 2, 3]', 'shared refers to original.'], ['[1, 2, 3] [1, 2]', 'Correct: only copy has an independent outer list.'], ['[1, 2, 3] [1, 2, 3]', 'The copied outer list is independent.'], ['NameError', 'All names are defined.']], 1, 'Simple assignment shares an object; copy() creates a separate outer list.'),
        v19_question('L21R-04', '<p>What happens on the second line?</p>' . v19_code('period = (2026, 8)\nperiod[1] = 9'), [['It becomes (2026, 9)', 'Tuple items cannot be replaced.'], ['TypeError is raised', 'Correct: tuples are immutable.'], ['IndexError is raised', 'Position one exists.'], ['Nothing happens', 'The assignment raises an error.']], 1, 'A tuple is ordered but its elements cannot be replaced after creation.'),
        v19_question('L21R-05', '<p>What is displayed?</p>' . v19_code('centre = {"name": "North", "registered": 40}\nprint(centre.get("phone", "not recorded"))'), [['None', 'A default was supplied.'], ['not recorded', 'Correct: the key is missing, so get returns the default.'], ['KeyError', 'get does not raise for a missing key.'], ['phone', 'It does not return the key name.']], 1, 'get() can return an explicit default for a missing optional field.'),
        v19_question('L21R-06', '<p>What is displayed?</p>' . v19_code('record = {"count": 3}\nrecord["count"] = 4\nrecord["status"] = "ready"\nprint(len(record), record["count"])'), [['1 3', 'Both an update and addition occur.'], ['1 4', 'status adds another key.'], ['2 4', 'Correct: one key is updated and one is added.'], ['2 3', 'count changes to four.']], 2, 'Assignment to an existing key updates; assignment to a new key adds a field.'),
        v19_question('L21R-07', '<p>What is the final total?</p>' . v19_code('record = {"registered": 40, "completed": 32}\ntotal = 0\nfor field, value in record.items():\n    total += value\nprint(total)'), [['2', 'The code adds values, not field count.'], ['32', 'registered is also added.'], ['72', 'Correct: items supplies both values.'], ['KeyError', 'Every item is iterated.']], 2, 'items() supplies each key-value pair.'),
        v19_question('L21R-08', '<p>What is displayed?</p>' . v19_code('centres = [{"name": "A", "registered": 40, "completed": 30}, {"name": "B", "registered": 20, "completed": 18}]\nfor centre in centres:\n    if centre["completed"] / centre["registered"] < 0.8:\n        print(centre["name"])'), [['A only', 'Correct: A is 75% and B is 90%.'], ['B only', 'B is not below 80%.'], ['A and B', 'B does not match.'], ['Nothing', 'A matches.']], 0, 'Take one dictionary from the list and read its fields by key.'),
        v19_question('L21R-09', '<p>What is displayed? Order does not matter.</p>' . v19_code('offered = {"Python", "Data"}\nrequested = {"Python", "Web"}\nprint(requested - offered)'), [['Python only', 'Python is present in both.'], ['Web only', 'Correct: it is requested but not offered.'], ['Python and Web', 'Difference removes common items.'], ['An empty set', 'Web is not in offered.']], 1, 'Set difference returns items in the left set but not the right.'),
        v19_question('L21R-10', '<p>Which structure best represents several centres with named fields and processes them in order?</p>', [['One set', 'Named fields and record grouping are unclear.'], ['One tuple only', 'It does not adequately represent several named records.'], ['A list containing dictionaries', 'Correct: a dictionary is one record and the list holds several.'], ['Only unrelated variables', 'They cannot be processed uniformly as records grow.']], 2, 'Represent several records as a list of dictionaries with consistent keys.'),
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
course_update_section($course, $chapter, ['summary' => $chaptersummary, 'summaryformat' => FORMAT_HTML]);
$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
$delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $delegated, ['summary' => $topicsummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$page = v19_find_and_rename($course->id, 'page', $oldpage, $pagename);
$lti = v19_find_and_rename($course->id, 'lti', $oldlti, $ltiname);
$quiz = v19_find_and_rename($course->id, 'quiz', $oldquiz, $quizname);

$page->intro = $pageintro;
$page->introformat = FORMAT_HTML;
$page->content = $body;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);

$expectedpath = $language === 'ja' ? '/ja/05_lists_dictionaries_records.ipynb' : '/05_lists_dictionaries_records.ipynb';
$newurl = preg_replace('~/(?:ja/)?05_lists_dictionaries_records\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || ($newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath))) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $language === 'ja'
    ? '<p>構造を予測し、操作前後を表示し、別のデータで変更・共有・欠落を確認します。</p>'
    : '<p>Predict structures, print values before and after operations, and test mutation, sharing, and missing data.</p>';
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
    $saved = v19_save_question($category->id, $context->id, $shortname . ' v19: ', $question, $language);
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
    'marker' => 'PYAI-V19-LESSON21-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
