<?php
// Close the Lesson 2.1 and 2.2 prerequisite gaps identified from Project 2.4.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';
require_once $CFG->libdir . '/gradelib.php';

use core_question\local\bank\question_version_status;

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';

function v23_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v23_q(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v23_save_question(int $categoryid, int $contextid, string $prefix, array $data, bool $ja): stdClass {
    $question = (object)['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['choices'] as $index => [$answer, $why]) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => '<p>' . s($why) . '</p>', 'format' => FORMAT_HTML];
        $fractions[] = $index === $data['correct'] ? 1.0 : 0.0;
    }
    $form = (object)[
        'name' => $prefix . $data['id'], 'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => $data['prompt'], 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p><strong>' . ($ja ? '確認ポイント：' : 'Check:') . '</strong> ' . s($data['explanation']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 10, 'penalty' => 0.3333333, 'status' => question_version_status::QUESTION_STATUS_READY,
        'idnumber' => null, 'single' => 1, 'shuffleanswers' => 1, 'answernumbering' => 'abc', 'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => $ja ? '<p>正解です。変更前後または関数契約を説明してから次へ進みましょう。</p>' : '<p>Correct. Explain the before-and-after state or function contract before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $ja ? '<p>NotebookでID、検索結果、件数、順序、例外名を表示して確認しましょう。</p>' : '<p>Use the Notebook to display the ID, search result, count, order, or exception name.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions, 'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v23_questions(string $lesson, bool $ja): array {
    if ($lesson === '2.1' && $ja) return [
        v23_q('L21P-01', '<p>何が表示されますか。</p>' . v23_code('values = [10, 20, 30]\nprint(values[-1])'), [['10', '最初の値です。'], ['20', '中央です。'], ['30', '正解です。-1は末尾です。'], ['IndexError', '有効な添字です。']], 2, '順序付きリストは位置で読めます。'),
        v23_q('L21P-02', '<p>最後に何が表示されますか。</p>' . v23_code('books = []\nresult = books.append({"id": "B001"})\nprint(len(books), result)'), [['0 None', '一件追加されます。'], ['1 None', '正解です。appendはリストを変更してNoneを返します。'], ['1 B001', '追加値は返しません。'], ['TypeError', '有効です。']], 1, '変更結果とメソッドの戻り値を分けます。'),
        v23_q('L21P-03', '<p>何が表示されますか。</p>' . v23_code('books = [{"id": "B001"}]\ncopy = books.copy()\nbooks.append({"id": "B002"})\nprint(len(books), len(copy))'), [['1 1', 'booksへ追加されます。'], ['2 1', '正解です。外側のコピーは独立しています。'], ['2 2', 'copyの外側は増えません。'], ['0 0', '初期レコードがあります。']], 1, '浅いコピーは外側のリストを分けます。'),
        v23_q('L21P-04', '<p>一件の図書レコードとして最も適切なのはどれですか。</p>', [['{"id": "B001", "title": "Python", "read": False}', '正解です。項目名が意味を表します。'], ['{"B001", "Python", False}', '集合は項目名を持ちません。'], ['["id", "title", "read"]', '値がありません。'], ['("B001")', '文字列です。']], 0, '一件を辞書、複数件をリストで表します。'),
        v23_q('L21P-05', '<p>何が表示されますか。</p>' . v23_code('books = [{"id":"B001"}, {"id":"B002"}]\nfound = None\nfor book in books:\n    if book["id"] == "B002":\n        found = book\n        break\nprint(found["id"])'), [['B001', '照合対象ではありません。'], ['B002', '正解です。'], ['None', '一致があります。'], ['KeyError', 'idキーがあります。']], 1, 'ID一致で保存中の辞書を取得します。'),
        v23_q('L21P-06', '<p>検索IDを<code>B009</code>へ変えたとき、最後の<code>found</code>は何ですか。</p>', [['空の辞書', '自動では作られません。'], ['None', '正解です。該当なしの通常結果です。'], ['B009', 'ID文字列を代入していません。'], ['必ず例外', 'このループだけなら例外ではありません。']], 1, '該当なしと異常処理を分けます。'),
        v23_q('L21P-07', '<p>何が表示されますか。</p>' . v23_code('books = [{"id":"B001", "read":False}]\nfound = books[0]\nfound["read"] = True\nprint(books[0]["read"])'), [['False', '同じ辞書を参照しています。'], ['True', '正解です。保存中のレコードが変わります。'], ['None', 'Trueを代入しています。'], ['KeyError', 'キーは存在します。']], 1, '検索結果が保存中の辞書なら変更はリストからも見えます。'),
        v23_q('L21P-08', '<p>追加前の重複確認として正しい条件はどれですか。</p>' . v23_code('existing_ids = {"B001", "B002"}\nnew_id = "B002"'), [['new_id in existing_ids', '正解です。重複しています。'], ['new_id not in existing_ids', '新規の場合の条件です。'], ['len(existing_ids) == 0', '集合は空ではありません。'], ['new_id == None', '文字列です。']], 0, '一意性は既存IDへの所属で確認できます。'),
        v23_q('L21P-09', '<p>削除後のID順はどれですか。</p>' . v23_code('books = [{"id":"B001"},{"id":"B002"},{"id":"B003"}]\nfor index, book in enumerate(books):\n    if book["id"] == "B002":\n        books.pop(index)\n        break'), [['B002, B003', 'B001は残ります。'], ['B001, B003', '正解です。'], ['B003, B001', '残る順序は変わりません。'], ['B001, B002', 'B002を削除します。']], 1, '位置を特定してpopし、残る順序を保持します。'),
        v23_q('L21P-10', '<p>CRUDのUpdateに当たる操作はどれですか。</p>', [['新しい辞書をappendする', 'Createです。'], ['IDでレコードを探す', 'Readです。'], ['見つかった辞書のreadを書き換える', '正解です。'], ['位置をpopする', 'Deleteです。']], 2, '同じレコード集合への基本操作を区別します。'),
    ];
    if ($lesson === '2.1') return [
        v23_q('L21P-01', '<p>What is displayed?</p>' . v23_code('values = [10, 20, 30]\nprint(values[-1])'), [['10', 'That is first.'], ['20', 'That is middle.'], ['30', 'Correct: -1 is last.'], ['IndexError', 'The index is valid.']], 2, 'Read an ordered list by position.'),
        v23_q('L21P-02', '<p>What is displayed last?</p>' . v23_code('books = []\nresult = books.append({"id": "B001"})\nprint(len(books), result)'), [['0 None', 'One record was added.'], ['1 None', 'Correct: append mutates and returns None.'], ['1 B001', 'It does not return the item.'], ['TypeError', 'The operation is valid.']], 1, 'Separate mutation from a method return value.'),
        v23_q('L21P-03', '<p>What is displayed?</p>' . v23_code('books = [{"id": "B001"}]\ncopy = books.copy()\nbooks.append({"id": "B002"})\nprint(len(books), len(copy))'), [['1 1', 'books receives another record.'], ['2 1', 'Correct: the outer copy is independent.'], ['2 2', 'The copied outer list does not grow.'], ['0 0', 'There is an initial record.']], 1, 'A shallow copy separates the outer list.'),
        v23_q('L21P-04', '<p>Which best represents one book record?</p>', [['{"id": "B001", "title": "Python", "read": False}', 'Correct: field names state meaning.'], ['{"B001", "Python", False}', 'A set has no field names.'], ['["id", "title", "read"]', 'There are no values.'], ['("B001")', 'That is a string.']], 0, 'Use one dictionary per record and a list for several.'),
        v23_q('L21P-05', '<p>What is displayed?</p>' . v23_code('books = [{"id":"B001"}, {"id":"B002"}]\nfound = None\nfor book in books:\n    if book["id"] == "B002":\n        found = book\n        break\nprint(found["id"])'), [['B001', 'It does not match.'], ['B002', 'Correct.'], ['None', 'A match exists.'], ['KeyError', 'The key exists.']], 1, 'ID matching obtains the stored dictionary.'),
        v23_q('L21P-06', '<p>If the search ID changes to <code>B009</code>, what is <code>found</code> at the end?</p>', [['An empty dictionary', 'It is not created automatically.'], ['None', 'Correct: normal absence.'], ['B009', 'The string is not assigned.'], ['Always an exception', 'This loop need not raise.']], 1, 'Separate absence from invalid operations.'),
        v23_q('L21P-07', '<p>What is displayed?</p>' . v23_code('books = [{"id":"B001", "read":False}]\nfound = books[0]\nfound["read"] = True\nprint(books[0]["read"])'), [['False', 'Both names refer to the same dictionary.'], ['True', 'Correct: the stored record changes.'], ['None', 'True was assigned.'], ['KeyError', 'The key exists.']], 1, 'A found stored dictionary can be updated in place.'),
        v23_q('L21P-08', '<p>Which condition detects a duplicate before adding?</p>' . v23_code('existing_ids = {"B001", "B002"}\nnew_id = "B002"'), [['new_id in existing_ids', 'Correct: it already exists.'], ['new_id not in existing_ids', 'That detects a new ID.'], ['len(existing_ids) == 0', 'It is not empty.'], ['new_id == None', 'It is a string.']], 0, 'Use set membership to enforce uniqueness.'),
        v23_q('L21P-09', '<p>What ID order remains?</p>' . v23_code('books = [{"id":"B001"},{"id":"B002"},{"id":"B003"}]\nfor index, book in enumerate(books):\n    if book["id"] == "B002":\n        books.pop(index)\n        break'), [['B002, B003', 'B001 remains.'], ['B001, B003', 'Correct.'], ['B003, B001', 'Remaining order is preserved.'], ['B001, B002', 'B002 is removed.']], 1, 'Find the position, pop once, and preserve remaining order.'),
        v23_q('L21P-10', '<p>Which operation is CRUD Update?</p>', [['Append a new dictionary', 'Create.'], ['Find a record by ID', 'Read.'], ['Change read on the found dictionary', 'Correct.'], ['Pop a position', 'Delete.']], 2, 'Distinguish the four basic record operations.'),
    ];
    if ($ja) return [
        v23_q('L22P-01', '<p>最後に何が表示されますか。</p>' . v23_code('def show(value):\n    print(value)\nresult = show(3)\nprint(result)'), [['3だけ', '次のprintもあります。'], ['3、次にNone', '正解です。'], ['Noneだけ', '関数内で3を表示します。'], ['例外', '有効です。']], 1, 'printとreturnを区別します。'),
        v23_q('L22P-02', '<p>検索関数で一致がないときの戻り値として適切なのはどれですか。</p>', [['0', '有効なレコードと混同します。'], ['空文字', 'ID値と混同します。'], ['None', '正解です。'], ['必ずValueError', '検索の不一致自体は通常結果です。']], 2, '該当なしを明示します。'),
        v23_q('L22P-03', '<p>何が表示されますか。</p>' . v23_code('def find(items):\n    return items[0]\nbooks=[{"read":False}]\nfound=find(books)\nfound["read"]=True\nprint(books[0]["read"])'), [['False', '保存中辞書と同じです。'], ['True', '正解です。'], ['None', 'Trueを代入します。'], ['KeyError', 'キーがあります。']], 1, '保存中のオブジェクトを返す契約です。'),
        v23_q('L22P-04', '<p>変更関数が返す値として最も確認しやすいものはどれですか。</p>', [['printの結果', '再利用できません。'], ['変更した保存中の辞書', '正解です。'], ['常にNone', '変更対象を確認できません。'], ['リストの長さだけ', '対象レコードが分かりません。']], 1, '変更結果を呼び出し側が確認できます。'),
        v23_q('L22P-05', '<p>空のIDや重複IDの追加で送出する例外はどれですか。</p>', [['ValueError', '正解です。値の追加規則違反です。'], ['KeyError', '更新対象欠落ではありません。'], ['FileNotFoundError', 'ファイルではありません。'], ['例外なし', '一意性が壊れます。']], 0, '新しい値の妥当性を表します。'),
        v23_q('L22P-06', '<p>存在しないIDを読了済みに変更する操作で送出する例外はどれですか。</p>', [['ValueError', '値の形式ではありません。'], ['KeyError', '正解です。対象IDがありません。'], ['ZeroDivisionError', '除算しません。'], ['例外なし', '依頼した更新が実行されません。']], 1, '欠落した更新対象を区別します。'),
        v23_q('L22P-07', '<p>追加関数の状態変化を確認する最も直接的なテストはどれですか。</p>', [['呼出前後のlenが一つ増える', '正解です。'], ['関数名の長さを測る', '状態とは無関係です。'], ['printが一行ある', '保存状態を保証しません。'], ['同じ入力を読まない', 'テストではありません。']], 0, '戻り値とコレクション状態を両方確認します。'),
        v23_q('L22P-08', '<p><code>assert added is books[-1]</code>が確認するものは何ですか。</p>', [['値が似ているだけ', 'isは同一性です。'], ['返された辞書が実際に格納された同じオブジェクト', '正解です。'], ['最後のIDが最大', '大小は比較しません。'], ['リストが空', '末尾要素があります。']], 1, '変更関数のオブジェクト同一性を確認します。'),
        v23_q('L22P-09', '<p>提供された確認プログラムについて正しい説明はどれですか。</p>', [['学習者が必ず一から作る', 'この段階では不要です。'], ['公開された関数契約を別のプログラムとして検査する', '正解です。'], ['CSVを手で採点するだけ', '自動で関数を呼びます。'], ['模範解答と文字列完全一致だけを調べる', '観測可能な動作を調べます。']], 1, 'チェッカーは契約の利用者です。'),
        v23_q('L22P-10', '<p>確認でNGになったときの適切な対応はどれですか。</p>', [['確認プログラムを書き換える', '検査基準を変えてしまいます。'], ['自分の関数の戻り値・状態変化・例外を契約へ合わせる', '正解です。'], ['エラーをexcept Exceptionで隠す', '原因が消えます。'], ['ファイル名を変更する', '読み込めなくなります。']], 1, '公開契約を保ち、自分の実装だけを修正します。'),
    ];
    return [
        v23_q('L22P-01', '<p>What is displayed last?</p>' . v23_code('def show(value):\n    print(value)\nresult = show(3)\nprint(result)'), [['Only 3', 'Another print follows.'], ['3, then None', 'Correct.'], ['Only None', 'The function prints 3.'], ['An exception', 'It is valid.']], 1, 'Distinguish print from return.'),
        v23_q('L22P-02', '<p>What should a search function return when no record matches?</p>', [['0', 'It can be confused with data.'], ['An empty string', 'It can be confused with an ID.'], ['None', 'Correct.'], ['Always ValueError', 'Absence is a normal search result.']], 2, 'Represent normal absence explicitly.'),
        v23_q('L22P-03', '<p>What is displayed?</p>' . v23_code('def find(items):\n    return items[0]\nbooks=[{"read":False}]\nfound=find(books)\nfound["read"]=True\nprint(books[0]["read"])'), [['False', 'It is the stored dictionary.'], ['True', 'Correct.'], ['None', 'True is assigned.'], ['KeyError', 'The key exists.']], 1, 'The contract returns the stored object.'),
        v23_q('L22P-04', '<p>Which return value is most useful from a mutation function?</p>', [['The print result', 'It cannot be reused.'], ['The changed stored dictionary', 'Correct.'], ['Always None', 'The target cannot be inspected.'], ['Only list length', 'It does not identify the record.']], 1, 'The caller can inspect the changed record.'),
        v23_q('L22P-05', '<p>Which exception fits a blank or duplicate ID during addition?</p>', [['ValueError', 'Correct: the new value violates a rule.'], ['KeyError', 'No update target is missing.'], ['FileNotFoundError', 'No file is involved.'], ['No exception', 'Uniqueness would break.']], 0, 'Report invalid newly supplied values.'),
        v23_q('L22P-06', '<p>Which exception fits marking an absent ID as read?</p>', [['ValueError', 'Its text format is not the issue.'], ['KeyError', 'Correct: the target ID is absent.'], ['ZeroDivisionError', 'There is no division.'], ['No exception', 'The requested update did not occur.']], 1, 'Distinguish a missing operation target.'),
        v23_q('L22P-07', '<p>Which directly tests an add function state change?</p>', [['Length increases by one', 'Correct.'], ['Measure the function name', 'Unrelated.'], ['A print line exists', 'It does not prove storage.'], ['Never read the input', 'That is not a test.']], 0, 'Test return and collection state.'),
        v23_q('L22P-08', '<p>What does <code>assert added is books[-1]</code> check?</p>', [['Only similar values', 'is checks identity.'], ['The return is the exact stored object', 'Correct.'], ['The last ID is largest', 'No ordering comparison.'], ['The list is empty', 'A last item exists.']], 1, 'Test object identity in a mutation contract.'),
        v23_q('L22P-09', '<p>Which statement about the supplied checker is correct?</p>', [['The learner must author it from scratch', 'Not at this stage.'], ['It is another program that consumes the public function contract', 'Correct.'], ['It only grades CSV by hand', 'It calls functions automatically.'], ['It only compares source text with the model', 'It tests observable behaviour.']], 1, 'A checker is an independent contract consumer.'),
        v23_q('L22P-10', '<p>What should you do after an NG result?</p>', [['Edit the checker', 'That changes the standard.'], ['Align your return, mutation, or exception with the contract', 'Correct.'], ['Hide errors with except Exception', 'That hides the cause.'], ['Rename the submission file', 'The checker cannot import it.']], 1, 'Keep the contract and change only your implementation.'),
    ];
}

function v23_add_feedback_bands(int $quizid, bool $ja): void {
    global $DB;
    $bands = $ja ? [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">&#127881;</span><h3>100%達成です！</h3><p>すべての考え方を確認できました。難しかった一問を自分の言葉で説明して定着させましょう。</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">&#127942;</span><h3>合格です。おめでとうございます！</h3><p>この理解度チェックは完了です。残りの解説も確認し、100%へ再挑戦できます。</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">&#128640;</span><h3>あと少しです。</h3><p>誤った項目をPython Labで確かめ、90%以上を目指して再挑戦しましょう。</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">&#127793;</span><h3>理解を積み上げています。</h3><p>解説から二つ選んでNotebookで実行し、もう一度確認しましょう。</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">&#128269;</span><h3>次に学ぶ場所が分かりました。</h3><p>これは罰点ではありません。Notebookで動作を確かめ、再挑戦しましょう。</p></div>'],
    ] : [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">&#127881;</span><h3>Mastered — 100%!</h3><p>You checked every idea. Explain one difficult answer in your own words to make it stick.</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">&#127942;</span><h3>Completed — congratulations!</h3><p>This learning check is complete. Review remaining feedback and try again for 100% if useful.</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">&#128640;</span><h3>You are close.</h3><p>Practise the missed item in Python Lab and try again; 90% completes the check.</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">&#127793;</span><h3>Keep building.</h3><p>Choose two explanations, run the related Notebook code, and check again.</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">&#128269;</span><h3>You found what to learn next.</h3><p>This is guidance, not a penalty. Check the behaviour in the Notebook, then retry.</p></div>'],
    ];
    foreach ($bands as [$min, $max, $html]) {
        $DB->insert_record('quiz_feedback', (object)['quizid' => $quizid, 'feedbacktext' => $html, 'feedbacktextformat' => FORMAT_HTML, 'mingrade' => $min, 'maxgrade' => $max]);
    }
}

function v23_prepare_quiz_without_losing_attempts(stdClass $course, stdClass $quiz): stdClass {
    global $DB;
    $attemptcount = $DB->count_records('quiz_attempts', ['quiz' => $quiz->id]);
    if ($attemptcount === 0) {
        return $quiz;
    }

    $oldcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $section = $DB->get_record('course_sections', ['id' => $oldcm->section], '*', MUST_EXIST);
    $created = add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'modulename' => 'quiz', 'section' => $section->section, 'name' => $quiz->name,
        'intro' => $quiz->intro, 'introformat' => FORMAT_HTML,
        'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0,
        'overduehandling' => 'autosubmit', 'graceperiod' => 0,
        'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'attemptonlast' => 0,
        'grademethod' => QUIZ_GRADEHIGHEST, 'decimalpoints' => 0, 'questiondecimalpoints' => -1,
        'questionsperpage' => 10, 'navmethod' => QUIZ_NAVMETHOD_FREE, 'shuffleanswers' => 1, 'grade' => 100,
        'reviewattempt' => 69888, 'reviewcorrectness' => 4352, 'reviewmarks' => 4352,
        'reviewspecificfeedback' => 4352, 'reviewgeneralfeedback' => 4352,
        'reviewrightanswer' => 4352, 'reviewoverallfeedback' => 4352,
        'password' => '', 'quizpassword' => '', 'subnet' => '', 'browsersecurity' => '-',
        'delay1' => 0, 'delay2' => 0, 'visible' => 1, 'visibleoncoursepage' => 1,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
    $newquiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
    $newcm = get_coursemodule_from_instance('quiz', $newquiz->id, $course->id, false, MUST_EXIST);
    moveto_module($newcm, $section, $oldcm);

    $originalname = $quiz->name;
    $quiz->name = $originalname . ' [archived before v23; ' . $attemptcount . ' attempt(s)]';
    $quiz->timemodified = time();
    $DB->update_record('quiz', $quiz);
    $DB->set_field('course_modules', 'visible', 0, ['id' => $oldcm->id]);
    $DB->set_field('course_modules', 'visibleold', 0, ['id' => $oldcm->id]);
    $oldgrade = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0]);
    if ($oldgrade) {
        $oldgrade->set_hidden(1);
    }
    $newquiz->_v23created = true;
    return $newquiz;
}

function v23_apply_quiz(stdClass $course, stdClass $quiz, array $questions, bool $ja, string $shortname, string $intro): void {
    global $DB;
    $quiz = v23_prepare_quiz_without_losing_attempts($course, $quiz);
    $structure = \mod_quiz\structure::create_for_quiz(\mod_quiz\quiz_settings::create($quiz->id));
    foreach (array_reverse($structure->get_slots()) as $slot) $structure->remove_slot($slot->slot);
    $quiz->intro = $intro; $quiz->introformat = FORMAT_HTML; $quiz->attempts = 0; $quiz->grademethod = QUIZ_GRADEHIGHEST; $quiz->grade = 100; $quiz->questionsperpage = 10; $quiz->timemodified = time(); $DB->update_record('quiz', $quiz);
    $context = context_course::instance($course->id);
    $category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
    if (!$category) { $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC'); $category = reset($categories); }
    foreach ($questions as $data) { $saved = v23_save_question($category->id, $context->id, $shortname . ' v23: ', $data, $ja); quiz_add_quiz_question($saved->id, $quiz, 0, 10); }
    $DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    $gradeitem = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0]);
    if (!$gradeitem) throw new RuntimeException('Grade item missing');
    $gradeitem->gradepass = 90; $gradeitem->grademax = 100; $gradeitem->update();
    if (!empty($quiz->_v23created)) v23_add_feedback_bands($quiz->id, $ja);
}

$lessons = $ja ? [
    '2.1' => ['page' => 'レッスン2.1：リスト・辞書・レコード', 'quiz' => '理解度チェック：2.1 リスト・辞書・レコード', 'lti' => 'Python Lab 2.1：リスト・辞書・レコード', 'anchor' => '<h3>例題から応用へ</h3>', 'replacement' => '<h3>追加練習：センターレコードへ応用する</h3>', 'intro' => '<p>ID検索、重複確認、追加・更新・削除と、変更前後のレコードを短いコードで確認します。</p>'],
    '2.2' => ['page' => 'レッスン2.2：関数・エラー・テスト', 'quiz' => '理解度チェック：2.2 関数・エラー・テスト', 'lti' => 'Python Lab 2.2：関数・エラー・テスト', 'anchor' => '<h3>例題から応用へ</h3>', 'replacement' => '<h3>追加練習：センター処理を関数へ分ける</h3>', 'intro' => '<p>検索のNone、変更関数、ValueErrorとKeyError、状態の前後、自動確認との契約を確認します。</p>'],
] : [
    '2.1' => ['page' => 'Lesson 2.1: Lists, dictionaries, and records', 'quiz' => 'Knowledge check: 2.1 Lists, dictionaries, and records', 'lti' => 'Python Lab 2.1: Lists, dictionaries, and records', 'anchor' => '<h3>From guided example to transfer</h3>', 'replacement' => '<h3>Additional practice: transfer to centre records</h3>', 'intro' => '<p>Check ID search, duplicate prevention, add, update, removal, and before-and-after records in short code.</p>'],
    '2.2' => ['page' => 'Lesson 2.2: Functions, errors, and testing', 'quiz' => 'Knowledge check: 2.2 Functions, errors, and testing', 'lti' => 'Python Lab 2.2: Functions, errors, and testing', 'anchor' => '<h3>From guided example to transfer</h3>', 'replacement' => '<h3>Additional practice: separate centre processing into functions</h3>', 'intro' => '<p>Check search None, mutation functions, ValueError versus KeyError, state before and after, and the checker contract.</p>'],
];

foreach ($lessons as $lesson => $names) {
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $names['page']], '*', MUST_EXIST);
    $marker = "PYAI-V23-{$lesson}-PROJECT24-READY";
    if (!str_contains($page->content, $marker)) {
        if ($lesson === '2.1') {
            $addition = $ja
                ? '<h3>IDで一件を探し、該当なしを区別する</h3><p>位置ではなく一意なIDを照合します。結果を最初は<code>None</code>とし、一致した辞書を保存して<code>break</code>します。最後まで見つからない<code>None</code>は「該当なし」という通常の検索結果です。</p>' . v23_code("found = None\nfor book in books:\n    if book[\"id\"] == target_id:\n        found = book\n        break") . '<h3>一つの集合で追加・参照・更新・削除する</h3><p>追加前にID集合で重複を確認します。検索結果の辞書はリストに保存された辞書そのものなので、項目を変更するとレコードも変わります。削除は<code>enumerate()</code>で位置を得て一度だけ<code>pop()</code>し、残る順序を保ちます。これがCRUDです。</p><h3>応用練習：備品台帳</h3><p><code>asset_id</code>、<code>name</code>、<code>available</code>を持つ三件から始め、A004の追加、A002の更新、A003の削除、存在しないIDの検索を行います。</p>'
                : '<h3>Find one record by ID and distinguish absence</h3><p>Match a unique ID rather than a position. Begin with <code>None</code>, store the matching dictionary, and <code>break</code>. If no match exists, <code>None</code> is the normal “not found” result.</p>' . v23_code("found = None\nfor book in books:\n    if book[\"id\"] == target_id:\n        found = book\n        break") . '<h3>Add, read, update, and delete in one collection</h3><p>Check an ID set before adding. A found dictionary is the dictionary stored in the list, so field assignment updates the record. Use <code>enumerate()</code> to find a removal position and <code>pop()</code> once, preserving remaining order. These operations form CRUD.</p><h3>Transfer exercise: equipment register</h3><p>Begin with three records containing <code>asset_id</code>, <code>name</code>, and <code>available</code>; add A004, update A002, remove A003, and search for an absent ID.</p>';
        } else {
            $addition = $ja
                ? '<h3>検索・変更関数の契約を決める</h3><p>検索の該当なしは<code>None</code>です。変更関数は見つかった保存中の辞書を変更して返し、対象がなければ<code>KeyError</code>を送出します。空欄や重複追加は新しい値の規則違反なので<code>ValueError</code>です。</p>' . v23_code("def mark_as_read(books, book_id):\n    book = find_book(books, book_id)\n    if book is None:\n        raise KeyError(book_id)\n    book[\"read\"] = True\n    return book") . '<h3>状態の前後をテストする</h3><p>戻り値だけでなく、件数、順序、格納されたオブジェクトを呼出前後で確認します。提供された確認プログラムは、この公開契約を利用する別のプログラムです。内部を作れなくても、ファイル名、関数名、戻り値、状態変化、例外を保つ必要があります。</p><h3>応用練習：備品台帳の関数</h3><p>2.1の操作を検索・追加・更新・削除関数へ分け、通常、該当なし、空欄、重複、変更前後を<code>assert</code>で確認します。</p>'
                : '<h3>Define search and mutation contracts</h3><p>Search absence returns <code>None</code>. A mutation changes and returns the stored dictionary, raising <code>KeyError</code> when the target is absent. Blank or duplicate additions violate a new-value rule and raise <code>ValueError</code>.</p>' . v23_code("def mark_as_read(books, book_id):\n    book = find_book(books, book_id)\n    if book is None:\n        raise KeyError(book_id)\n    book[\"read\"] = True\n    return book") . '<h3>Test state before and after</h3><p>Check count, order, and stored-object identity as well as the return. The supplied checker is another program that consumes this public contract. You need not author it yet, but must preserve filename, function names, returns, mutations, and exceptions.</p><h3>Transfer exercise: equipment-register functions</h3><p>Separate the 2.1 operations into search, add, update, and remove functions, then assert normal, absent, blank, duplicate, and before-and-after cases.</p>';
        }
        if (!str_contains($page->content, $names['anchor'])) throw new RuntimeException("Missing insertion anchor: {$names['page']}");
        $page->content = str_replace($names['anchor'], $addition . $names['replacement'], $page->content);
        $page->content .= '<p style="display:none">' . $marker . '</p>';
        $page->timemodified = time(); $DB->update_record('page', $page);
    }
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $names['lti']], '*', MUST_EXIST);
    $lti->intro = $names['intro']; $lti->introformat = FORMAT_HTML; $lti->timemodified = time(); $DB->update_record('lti', $lti);
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $names['quiz']], '*', MUST_EXIST);
    v23_apply_quiz($course, $quiz, v23_questions($lesson, $ja), $ja, $shortname, $names['intro']);
    $archiveprefix = $names['quiz'] . ' [archived before v23;';
    $archivearea = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
    foreach ($DB->get_records('quiz', ['course' => $course->id]) as $archivedquiz) {
        if (!str_starts_with($archivedquiz->name, $archiveprefix)) continue;
        $archivedcm = get_coursemodule_from_instance('quiz', $archivedquiz->id, $course->id, false, MUST_EXIST);
        if ((int)$archivedcm->section !== (int)$archivearea->id) moveto_module($archivedcm, $archivearea);
        $DB->set_field('course_modules', 'visible', 0, ['id' => $archivedcm->id]);
        $DB->set_field('course_modules', 'visibleold', 0, ['id' => $archivedcm->id]);
    }
}
rebuild_course_cache($course->id, true);
echo json_encode(['courseid' => (int)$course->id, 'shortname' => $shortname, 'lessons' => ['2.1', '2.2'], 'questions_per_lesson' => 10, 'marker_revision' => 23], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
