<?php
// Rewrite Chapter 3.2 while preserving existing Moodle activities.
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

function v23_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v23_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v23_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
            ? '<p>正解です。各部分マスクの件数と最終抽出件数を確認してから次へ進みましょう。</p>'
            : '<p>Correct. Check each partial-mask count and the final selected count before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>各比較を括弧で囲み、部分マスクを表示し、True件数を数えてからもう一度確かめましょう。</p>'
            : '<p>Parenthesise comparisons, display each partial mask, and count True values before trying again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v23_find_and_rename(int $courseid, string $table, string $oldname, string $newname): stdClass {
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
    $oldtopic = '3.2 絞り込みとブール論理';
    $topicname = '3.2 データの選択・抽出とブール論理';
    $topicsummary = '<p>分析の問いを必要な列と行条件へ翻訳し、比較、AND・OR・NOT、所属、範囲、欠損を検証可能なブールマスクとして表します。</p>';
    $oldpage = 'レッスン8：データの確認と選択';
    $oldlti = 'Python Lab 08：絞り込みとブール論理';
    $oldquiz = '理解度チェック：レッスン8 データの確認と選択';
    $pagename = 'レッスン3.2：データの選択・抽出とブール論理';
    $ltiname = 'Python Lab 3.2：データの選択・抽出とブール論理';
    $quizname = '理解度チェック：3.2 データの選択・抽出とブール論理';
    $pageintro = '<p>分析の問いを表示列と行条件へ分け、pandasのブールマスクとして組み立て、該当件数と欠損を確認します。</p>';
    $quizintro = '<p>短い表とコードから、列選択、loc・iloc、比較マスク、AND・OR・NOT、所属、範囲、欠損、index対応を確認します。何度でも挑戦でき、最高点が記録されます。</p>';
    $body = '<div class="python-sample-lesson"><h2>分析の問いを、表示列と行条件へ翻訳する</h2>'
        . '<p>3.1ではCSVを読み、表の形、列名、型、欠損を確認しました。次は「どの観測を、どの項目で見るか」をコードにします。抽出は都合のよい行だけを隠れて選ぶ操作ではありません。問い、条件、元件数、該当件数を対応させ、同じ条件を再現できるようにします。</p>'
        . '<h3>問いを表示列と部分条件へ分ける</h3>'
        . '<p>「2026年2月と3月について、登録者30人以上で出席率80%未満のセンター名を確認する」なら、表示する列、月の所属条件、登録者数の下限、出席率の上限へ分解します。処理前に必要列と<code>df.columns</code>の差集合を取り、欠けた列を明示します。</p>'
        . v23_code("required = {\"month\", \"centre_name\", \"registered\", \"attended\"}\nmissing = required - set(df.columns)\nif missing:\n    raise KeyError(sorted(missing))")
        . '<h3>列名のリストで必要な項目だけを選ぶ</h3>'
        . '<p><code>df[\"registered\"]</code>は一列のSeries、<code>df[[\"month\", \"registered\"]]</code>は複数列のDataFrameです。列名は完全一致で指定します。結果に必要な列と条件計算だけに必要な列を区別し、表示列を選びます。</p>'
        . '<h3>locはラベル、ilocは位置で選ぶ</h3>'
        . '<p><code>loc[行条件, 列名]</code>はindexラベルと列名を使い、分析上の意味をコードへ残せます。<code>iloc[行位置, 列位置]</code>は0から始まる位置を使い、先頭行の確認などに向きます。indexラベルは常に0, 1, 2とは限らないため、ラベルと位置を混同してはいけません。</p>'
        . '<h3>比較式は各行のTrue・Falseを持つマスクを作る</h3>'
        . '<p><code>df[\"registered\"] &gt;= 30</code>は、元のindexと対応するブールSeriesを返します。<code>True</code>の行が条件に一致します。<code>mask.sum()</code>で該当件数を数え、抽出前後の件数とともに記録すると、条件の向きや境界の誤りを発見しやすくなります。</p>'
        . v23_code("large = df[\"registered\"] >= 30\nprint(large.head())\nprint(\"該当件数:\", int(large.sum()))\nresult = df.loc[large, [\"month\", \"centre_name\", \"registered\"]]")
        . '<h3>pandasの条件には&・|・~と括弧を使う</h3>'
        . '<p>Pythonの単一の真偽値には<code>and</code>、<code>or</code>、<code>not</code>を使います。Seriesを行ごとに組み合わせるpandasでは<code>&amp;</code>、<code>|</code>、<code>~</code>を使い、各比較を括弧で囲みます。Seriesへ<code>and</code>を使うと、Series全体を一つの真偽値へ決められずエラーになります。</p>'
        . v23_code("large = report[\"registered\"] >= 30\nlow_rate = report[\"attendance_rate\"] < 80\nprint(\"AND:\", int((large & low_rate).sum()))\nprint(\"OR:\", int((large | low_rate).sum()))\nprint(\"NOT large:\", int((~large).sum()))")
        . '<h3>AND、OR、NOTの意味を件数で確かめる</h3>'
        . '<p>ANDは両方を満たすため通常は狭くなり、ORはいずれかを満たすため通常は広くなります。NOTは真偽を反転します。ド・モルガンの法則により<code>~(A | B)</code>と<code>(~A) &amp; (~B)</code>は同じです。ただし、業務上の意味が読みやすい形を選び、部分マスクの件数でも確認します。</p>'
        . '<h3>isinで所属、betweenで範囲を表す</h3>'
        . '<p>複数候補のいずれかに一致するなら<code>isin()</code>、上下限を持つなら<code>between()</code>を使えます。<code>between()</code>は既定で両端を含みます。「以上」「以下」「未満」の違いを問題文と一致させます。</p>'
        . v23_code("months = report[\"month\"].isin([\"2026-02\", \"2026-03\"])\nsize = report[\"registered\"].between(25, 35, inclusive=\"both\")\nsubset = report.loc[months & size]")
        . '<h3>欠損を偶然条件外へ落とさず、明示する</h3>'
        . '<p>欠損値との大小比較は通常Falseになり、理由を示さないまま抽出から消えることがあります。値が必要なら<code>notna()</code>、欠損を調べるなら<code>isna()</code>を条件へ加え、件数を別に記録します。ここでは補完や削除をせず、3.3で扱う品質問題として保持します。</p>'
        . v23_code("known = report[\"attended\"].notna()\nlow_known = known & (report[\"attendance_rate\"] < 80)\nprint(int(low_known.sum()))\nprint(\"欠損:\", int(report[\"attended\"].isna().sum()))")
        . '<h3>名前を付けたマスクをlocで組み合わせる</h3>'
        . '<p>一つの長い式へ埋め込まず、月、規模、率、欠損の条件へ名前を付けると、それぞれの件数を確認できます。最後に<code>loc[最終マスク, 表示列]</code>で抽出し、必要なら<code>sort_values()</code>で再現可能な順序にします。結果には元件数、該当件数、条件を添えます。</p>'
        . '<h3>ブールマスクはindexラベルで行へ対応する</h3>'
        . '<p>pandasはマスクを物理的な位置だけでなくindexラベルで対応させます。別のDataFrameから作ったマスクや、index変更前の古いマスクを流用すると、ずれやエラーの原因になります。原則として抽出対象と同じDataFrameからマスクを作ります。</p>'
        . '<h3>例題から応用へ</h3><p>「2026年2月または3月、Python Foundations、登録者25～40人、修了率75%未満、修了値が欠損していないセンター」を抽出します。必要列、各部分マスク、最終件数を表示し、月・センターID順に並べてください。否定を一つ含む別の問いも作り、件数を比較します。</p>'
        . '<p>このレッスンでは値を選びますが、まだ値を直しません。次の3.3で、欠損、表記ゆれ、不可能な値を監査記録とともに扱います。</p>'
        . '<p><strong>学習時間の目安：</strong>約4時間</p><p style="display:none">PYAI-V23-LESSON32-FLOW</p></div>';
    $questions = [
        v23_question('L32R-01', '<p>「3月のセンター名と修了率を表示する」を表す最初の分解として最も適切なのはどれですか。</p>', [['monthを表示列、centre_nameを行条件にする', '役割が逆です。'], ['month=="2026-03"を行条件、centre_nameとcompletion_rateを表示列にする', '正解です。条件と表示項目を分けます。'], ['全列を表示して目で探す', '再現可能な抽出条件になりません。'], ['3月以外を削除して元データを上書きする', '抽出のために元データを変更しません。']], 1, '分析の問いを行条件と表示列へ翻訳します。'),
        v23_question('L32R-02', '<p>何が表示されますか。</p>' . v23_code('required = {"month", "registered", "completed"}\ncolumns = {"month", "registered"}\nprint(required - columns)'), [['空集合', 'completedが不足しています。'], ['{"completed"}', '正解です。必要列から存在列を引きます。'], ['{"month", "registered"}', 'これは存在する列です。'], ['KeyError', '集合の差は有効です。']], 1, '必要列と存在列の差集合で不足を検出できます。'),
        v23_question('L32R-03', '<p>indexが<code>[10, 20, 30]</code>のDataFrameで、indexラベル20の行を選ぶ式はどれですか。</p>', [['df.iloc[20]', 'ilocは位置20を意味し、範囲外です。'], ['df.loc[20]', '正解です。locはラベル20を選びます。'], ['df.loc[1]', 'ラベル1はありません。'], ['df.iloc[20:21]', '位置20からのスライスです。']], 1, 'locはラベル、ilocは0始まりの位置です。'),
        v23_question('L32R-04', '<p>何が表示されますか。</p>' . v23_code('s = pd.Series([20, 30, 40])\nmask = s >= 30\nprint(int(mask.sum()))'), [['1', '30と40の二つです。'], ['2', '正解です。Trueは数値として二つ数えられます。'], ['3', '20は条件を満たしません。'], ['60', '値の合計ではなくTrue件数です。']], 1, 'ブールSeriesのsumはTrue件数を返します。'),
        v23_question('L32R-05', '<p>二つのpandas Series条件を行ごとに両方満たすよう結ぶ正しい式はどれですか。</p>', [['condition_a and condition_b', 'Series全体の真偽が曖昧になりエラーです。'], ['condition_a & condition_b', '正解ですが、比較式を直接書く場合は各比較を括弧で囲みます。'], ['condition_a + condition_b == True', '件数的な加算になり意図が不明瞭です。'], ['and(condition_a, condition_b)', 'andは関数ではありません。']], 1, 'pandasの要素ごとのANDには&を使います。'),
        v23_question('L32R-06', '<p>どの行が残りますか。</p>' . v23_code('df = pd.DataFrame({"registered": [20, 30, 40], "rate": [70, 85, 75]}, index=["A", "B", "C"])\nmask = (df["registered"] >= 30) & (df["rate"] < 80)'), [['Aだけ', 'Aは登録者30未満です。'], ['Bだけ', 'Bは率80以上です。'], ['Cだけ', '正解です。Cだけが両条件を満たします。'], ['BとC', 'Bは二つ目を満たしません。']], 2, 'ANDでは各行が両方の条件を満たす必要があります。'),
        v23_question('L32R-07', '<p>月が2月または3月で、登録者数が25以上35以下の条件はどれですか。</p>', [['df["month"].isin(["2026-02", "2026-03"]) & df["registered"].between(25, 35)', '正解です。isinは所属、betweenは両端を含む範囲です。'], ['df["month"] == ["2026-02", "2026-03"] and df["registered"] > 25', 'リストとの比較とandが不適切で、境界25も除外します。'], ['df["month"].between("2026-02", "2026-03") | df["registered"] < 35', 'ORでは条件が広がり、下限もありません。'], ['df["registered"].isin([25, 35])', '25と35だけになり中間を含みません。']], 0, '候補集合にはisin、両端を含む範囲にはbetweenを使います。'),
        v23_question('L32R-08', '<p>出席率80%未満を抽出するとき、出席者数が欠損した行を別に把握する最も明示的な方法はどれですか。</p>', [['比較結果がFalseになるので何も記録しない', '欠損で除外された理由が残りません。'], ['notna()を率条件へ加え、isna().sum()も別に記録する', '正解です。有効値と欠損件数を分けます。'], ['欠損を無条件に0へ変える', '0人と未記録を混同します。'], ['欠損行を元DataFrameから削除する', 'このレッスンでは値を直しません。']], 1, '選択条件に欠損の扱いを明示し、件数を記録します。'),
        v23_question('L32R-09', '<p>ド・モルガンの法則で<code>~(A | B)</code>と同じものはどれですか。</p>', [['(~A) | (~B)', 'これは両方でない場合以外も含みます。'], ['(~A) & (~B)', '正解です。AでもBでもない条件です。'], ['A & B', '両方である条件です。'], ['A | B', '否定されていません。']], 1, 'OR全体の否定は、各条件の否定をANDで結んだものです。'),
        v23_question('L32R-10', '<p>抽出結果の妥当性を確認する方法として最も適切なのはどれですか。</p>', [['最終表だけを表示する', '条件のどこで件数が変わったか分かりません。'], ['元件数、各部分マスクのTrue件数、最終件数を記録する', '正解です。条件の段階を追跡できます。'], ['期待した行だけになるまで条件を変える', '結論に合わせた選択になります。'], ['indexをすべて削除する', '件数検証の代わりではありません。']], 1, '部分条件と最終結果の件数を記録し、抽出過程を検証します。'),
    ];
} else {
    $oldtopic = '3.2 Filtering and Boolean logic';
    $topicname = '3.2 Data selection, filtering, and Boolean logic';
    $topicsummary = '<p>Translate an analysis question into columns and row conditions, then express comparisons, AND, OR, NOT, membership, ranges, and missingness as verifiable Boolean masks.</p>';
    $oldpage = 'Lesson 8: Inspecting and selecting data';
    $oldlti = 'Python Lab 08: Filtering and Boolean logic';
    $oldquiz = 'Knowledge check: Lesson 8: Inspecting and selecting data';
    $pagename = 'Lesson 3.2: Data selection, filtering, and Boolean logic';
    $ltiname = 'Python Lab 3.2: Data selection, filtering, and Boolean logic';
    $quizname = 'Knowledge check: 3.2 Data selection, filtering, and Boolean logic';
    $pageintro = '<p>Separate an analysis question into displayed columns and row conditions, build pandas Boolean masks, and verify matching counts and missingness.</p>';
    $quizintro = '<p>Use short tables and code to check column selection, loc and iloc, comparison masks, AND, OR, NOT, membership, ranges, missingness, and index alignment. Attempts are unlimited and the highest score is retained.</p>';
    $body = '<div class="python-sample-lesson"><h2>Translate an analysis question into displayed columns and row conditions</h2>'
        . '<p>Lesson 3.1 loaded the CSV and established its shape, names, types, and missingness. Now express “which observations, viewed through which fields?” in code. Filtering must not silently select convenient rows. Keep the question, conditions, source count, and matching count connected so another person can reproduce the same subset.</p>'
        . '<h3>Separate displayed columns from partial conditions</h3><p>For “show centre names in February and March with at least 30 registrations and attendance below 80%,” identify display columns, month membership, a registration lower bound, and an attendance-rate upper bound. Before processing, compare required names with <code>df.columns</code> and report any missing columns.</p>'
        . v23_code("required = {\"month\", \"centre_name\", \"registered\", \"attended\"}\nmissing = required - set(df.columns)\nif missing:\n    raise KeyError(sorted(missing))")
        . '<h3>Select needed fields with a list of column names</h3><p><code>df[\"registered\"]</code> is a one-column Series; <code>df[[\"month\", \"registered\"]]</code> is a DataFrame. Names must match exactly. Distinguish columns needed to calculate a condition from those that must appear in the result.</p>'
        . '<h3>loc uses labels; iloc uses positions</h3><p><code>loc[row condition, column names]</code> uses index labels and named columns, leaving analytical meaning visible. <code>iloc[row positions, column positions]</code> uses zero-based positions and is useful for checks such as first rows. Index labels are not guaranteed to be 0, 1, 2, so do not confuse labels with positions.</p>'
        . '<h3>A comparison creates one True/False value per row</h3><p><code>df[\"registered\"] &gt;= 30</code> returns a Boolean Series aligned with the source index. A `True` row matches. <code>mask.sum()</code> counts matches. Recording source and result counts helps detect a reversed comparison or incorrect boundary.</p>'
        . v23_code("large = df[\"registered\"] >= 30\nprint(large.head())\nprint(\"Matching rows:\", int(large.sum()))\nresult = df.loc[large, [\"month\", \"centre_name\", \"registered\"]]")
        . '<h3>Use & | ~ and parentheses for pandas conditions</h3><p>Use Python <code>and</code>, <code>or</code>, and <code>not</code> for individual Boolean values. For row-wise pandas Series use <code>&amp;</code>, <code>|</code>, and <code>~</code>, with each comparison in parentheses. Using <code>and</code> with a Series asks for one truth value for the whole Series and raises an error.</p>'
        . v23_code("large = report[\"registered\"] >= 30\nlow_rate = report[\"attendance_rate\"] < 80\nprint(\"AND:\", int((large & low_rate).sum()))\nprint(\"OR:\", int((large | low_rate).sum()))\nprint(\"NOT large:\", int((~large).sum()))")
        . '<h3>Verify AND, OR, and NOT through counts</h3><p>AND requires both and normally narrows a selection. OR requires either and normally broadens it. NOT reverses truth values. De Morgan’s law says <code>~(A | B)</code> equals <code>(~A) &amp; (~B)</code>. Prefer the form that communicates operational meaning and confirm each partial-mask count.</p>'
        . '<h3>Use isin for membership and between for a range</h3><p>Use <code>isin()</code> for one of several candidates and <code>between()</code> for lower and upper bounds. <code>between()</code> includes both endpoints by default. Match inclusive and exclusive boundaries to words such as “at least,” “at most,” and “below.”</p>'
        . v23_code("months = report[\"month\"].isin([\"2026-02\", \"2026-03\"])\nsize = report[\"registered\"].between(25, 35, inclusive=\"both\")\nsubset = report.loc[months & size]")
        . '<h3>Make missingness explicit instead of losing rows accidentally</h3><p>A comparison with a missing value is usually False, so a row can disappear without an explanation. Add <code>notna()</code> when a value is required or <code>isna()</code> when investigating missingness, and record the missing count separately. Do not fill or delete values here; retain them as quality issues for Lesson 3.3.</p>'
        . v23_code("known = report[\"attended\"].notna()\nlow_known = known & (report[\"attendance_rate\"] < 80)\nprint(int(low_known.sum()))\nprint(\"Missing:\", int(report[\"attended\"].isna().sum()))")
        . '<h3>Combine named masks in loc</h3><p>Give month, size, rate, and missingness conditions separate names instead of hiding all logic in one expression. Verify their counts, then use <code>loc[final mask, displayed columns]</code>. Use <code>sort_values()</code> when a reproducible result order helps. Report source count, selected count, and condition.</p>'
        . '<h3>A Boolean mask aligns by index label</h3><p>pandas aligns a mask with rows by index label, not only physical position. Reusing a mask from another DataFrame or from before an index change can cause misalignment or errors. Normally create the mask from the same DataFrame being selected.</p>'
        . '<h3>From guided example to transfer</h3><p>Select February or March centres for Python Foundations with 25–40 registrations, completion below 75%, and a known completion value. Validate columns, display each partial-mask and final count, and sort by month and centre ID. Create a second question with one negated condition and compare counts.</p>'
        . '<p>This lesson selects values but does not correct them. Lesson 3.3 handles missingness, inconsistent labels, and impossible values with an audit trail.</p>'
        . '<p><strong>Estimated study time:</strong> about 4 hours</p><p style="display:none">PYAI-V23-LESSON32-FLOW</p></div>';
    $questions = [
        v23_question('L32R-01', '<p>What is the best first translation of “display centre name and completion rate for March”?</p>', [['Display month and use centre_name as the row condition', 'The roles are reversed.'], ['Use month=="2026-03" as the row condition and display centre_name and completion_rate', 'Correct: separate condition and displayed fields.'], ['Display all columns and search visually', 'That is not a reproducible condition.'], ['Delete non-March rows from the source', 'Selection does not require mutating the source.']], 1, 'Translate the question into row conditions and displayed columns.'),
        v23_question('L32R-02', '<p>What is displayed?</p>' . v23_code('required = {"month", "registered", "completed"}\ncolumns = {"month", "registered"}\nprint(required - columns)'), [['An empty set', 'completed is missing.'], ['{"completed"}', 'Correct: subtract existing columns from required columns.'], ['{"month", "registered"}', 'Those columns exist.'], ['KeyError', 'Set difference is valid.']], 1, 'Set difference identifies missing required columns.'),
        v23_question('L32R-03', '<p>For a DataFrame whose index is <code>[10, 20, 30]</code>, which selects the row labelled 20?</p>', [['df.iloc[20]', 'iloc means position 20 and is out of range.'], ['df.loc[20]', 'Correct: loc selects the label 20.'], ['df.loc[1]', 'There is no label 1.'], ['df.iloc[20:21]', 'That is a slice from position 20.']], 1, 'loc uses labels; iloc uses zero-based positions.'),
        v23_question('L32R-04', '<p>What is displayed?</p>' . v23_code('s = pd.Series([20, 30, 40])\nmask = s >= 30\nprint(int(mask.sum()))'), [['1', 'Both 30 and 40 match.'], ['2', 'Correct: two True values are counted.'], ['3', '20 does not match.'], ['60', 'This counts True, not the matching values.']], 1, 'Summing a Boolean Series counts True values.'),
        v23_question('L32R-05', '<p>Which correctly requires two pandas Series conditions to be true row by row?</p>', [['condition_a and condition_b', 'The truth value of a whole Series is ambiguous.'], ['condition_a & condition_b', 'Correct; parenthesise each comparison when written directly.'], ['condition_a + condition_b == True', 'This adds values and obscures intent.'], ['and(condition_a, condition_b)', 'and is not a function.']], 1, 'Use & for element-wise pandas AND.'),
        v23_question('L32R-06', '<p>Which row remains?</p>' . v23_code('df = pd.DataFrame({"registered": [20, 30, 40], "rate": [70, 85, 75]}, index=["A", "B", "C"])\nmask = (df["registered"] >= 30) & (df["rate"] < 80)'), [['A only', 'A has fewer than 30 registrations.'], ['B only', 'B has rate 85.'], ['C only', 'Correct: C satisfies both conditions.'], ['B and C', 'B fails the second condition.']], 2, 'AND requires each selected row to satisfy both conditions.'),
        v23_question('L32R-07', '<p>Which selects February or March with registration from 25 through 35 inclusive?</p>', [['df["month"].isin(["2026-02", "2026-03"]) & df["registered"].between(25, 35)', 'Correct: isin handles membership and between includes both endpoints.'], ['df["month"] == ["2026-02", "2026-03"] and df["registered"] > 25', 'The list comparison and and are inappropriate, and 25 is excluded.'], ['df["month"].between("2026-02", "2026-03") | df["registered"] < 35', 'OR broadens the selection and there is no lower bound.'], ['df["registered"].isin([25, 35])', 'That includes only 25 and 35, not values between.']], 0, 'Use isin for candidate membership and between for an inclusive range.'),
        v23_question('L32R-08', '<p>When selecting attendance below 80%, how should rows with missing attendance be tracked explicitly?</p>', [['Let the comparison become False and record nothing', 'The exclusion reason is lost.'], ['Add notna() to the rate condition and record isna().sum() separately', 'Correct: separate known values and missing counts.'], ['Replace missing values with zero unconditionally', 'Zero and not recorded are different.'], ['Delete missing rows from the source', 'This lesson does not correct the data.']], 1, 'State missingness in the selection and record its count.'),
        v23_question('L32R-09', '<p>By De Morgan’s law, which equals <code>~(A | B)</code>?</p>', [['(~A) | (~B)', 'This includes cases where only one is false.'], ['(~A) & (~B)', 'Correct: neither A nor B.'], ['A & B', 'This means both A and B.'], ['A | B', 'It is not negated.']], 1, 'The negation of OR is the AND of both negations.'),
        v23_question('L32R-10', '<p>What is the strongest way to verify a filtering result?</p>', [['Display only the final table', 'It does not show where counts changed.'], ['Record source count, each partial-mask True count, and final count', 'Correct: the selection stages can be traced.'], ['Change conditions until expected rows appear', 'That risks selecting for a desired conclusion.'], ['Remove every index', 'That does not verify conditions.']], 1, 'Record partial and final counts to make filtering verifiable.'),
    ];
}

$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname]);
if (!$subsection) {
    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $oldtopic], '*', MUST_EXIST);
    $subsection->name = $topicname;
    $subsection->timemodified = time();
    $DB->update_record('subsection', $subsection);
}
$delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $delegated, ['name' => $topicname, 'summary' => $topicsummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$page = v23_find_and_rename($course->id, 'page', $oldpage, $pagename);
$lti = v23_find_and_rename($course->id, 'lti', $oldlti, $ltiname);
$quiz = v23_find_and_rename($course->id, 'quiz', $oldquiz, $quizname);
$page->intro = $pageintro;
$page->introformat = FORMAT_HTML;
$page->content = $body;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);

$expectedpath = $language === 'ja' ? '/ja/08_filtering_boolean_logic.ipynb' : '/08_filtering_boolean_logic.ipynb';
$newurl = preg_replace('~/(?:ja/)?08_filtering_boolean_logic\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || ($newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath))) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $language === 'ja'
    ? '<p>必要列を確認し、部分マスクと最終マスクのTrue件数を表示して、locで抽出します。値の修正は次のレッスンで行います。</p>'
    : '<p>Validate columns, display True counts for partial and final masks, and select with loc. Leave value correction for the next lesson.</p>';
$lti->introformat = FORMAT_HTML;
$lti->timemodified = time();
$DB->update_record('lti', $lti);

$attemptsremoved = (int)$DB->count_records('quiz_attempts', ['quiz' => $quiz->id]);
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
    $saved = v23_save_question($category->id, $context->id, $shortname . ' v23: ', $question, $language);
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
    'courseid' => (int)$course->id,
    'shortname' => $shortname,
    'topic' => $topicname,
    'pageid' => (int)$page->id,
    'quizid' => (int)$quiz->id,
    'ltiid' => (int)$lti->id,
    'questions' => count($questions),
    'attempts_removed' => $attemptsremoved,
    'lti_path' => $expectedpath,
    'marker' => 'PYAI-V23-LESSON32-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
