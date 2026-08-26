<?php
// Rewrite Chapter 3.1 while preserving its existing Moodle activities.
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

function v22_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v22_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v22_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
            ? '<p>正解です。表の形、列の意味、読み込み条件を確認してから次へ進みましょう。</p>'
            : '<p>Correct. Confirm the table shape, column meaning, and loading assumptions before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>Notebookで現在位置、読込パス、shape、columns、dtypesを表示して、もう一度確かめましょう。</p>'
            : '<p>Print the working directory, loaded path, shape, columns, and dtypes in the Notebook, then try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v22_find_and_rename(int $courseid, string $table, string $oldname, string $newname): stdClass {
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
    $oldtopic = '3.1 表・CSV・pandas';
    $topicname = '3.1 表形式データ・CSV・pandas';
    $topicsummary = '<p>Pythonのレコードを表へ移し、CSVの構造、パス、文字コード、DataFrameの形・列・型・欠損を確認してから列計算へ進みます。</p>';
    $oldpage = 'レッスン7：表・CSV・pandas';
    $oldlti = 'Python Lab 07：表・CSV・pandas';
    $oldquiz = '理解度チェック：レッスン7 表・CSV・pandas';
    $olddataset = 'データセット集：24行から25万件の架空レコードへ';
    $pagename = 'レッスン3.1：表形式データ・CSV・pandas';
    $ltiname = 'Python Lab 3.1：表形式データ・CSV・pandas';
    $quizname = '理解度チェック：3.1 表形式データ・CSV・pandas';
    $datasetname = 'データセットの発展：24行から25万件の架空レコードへ';
    $pageintro = '<p>辞書のリストを表形式へ移し、CSVを正しい場所と条件で読み、スキーマを確認してからpandasで計算します。</p>';
    $quizintro = '<p>CSVの読み込み条件と、DataFrameの形・列・型・欠損・計算結果を短いコードで確認します。何度でも挑戦でき、最高点が記録されます。</p>';
    $body = '<div class="python-sample-lesson"><h2>レコードの集まりを、確認可能な表として扱う</h2>'
        . '<p>2.3では一件のセンターを辞書、複数件をリストとして処理しました。件数と項目が増えると、すべてのレコードで同じ列をそろえ、列単位で確認・計算できる表形式が有効になります。pandasはこの表を<code>DataFrame</code>として扱います。</p>'
        . '<h3>一行を一観測、一列を一変数としてそろえる</h3>'
        . '<p>この教材では一行を一つのセンター・月、一列を同じ意味の変数、一つのセルを一観測の一変数の値とします。列名だけでなく、各列の期待型、単位、欠損規則までをスキーマとして確認します。同じ列へ人数と文字を混ぜると、後の計算が不安定になります。</p>'
        . '<h3>CSVは表を記録したテキスト形式である</h3>'
        . '<p>CSVでは通常、一行目がヘッダー、後続行がレコード、カンマがフィールドの区切りです。値にカンマや改行を含む場合は引用符が必要です。CSV自体はセルの色、数式、複数シートを保持しません。文字コード、区切り文字、空欄、識別子の先頭0をどのように読むかを決める必要があります。</p>'
        . v22_code("month,centre_id,centre_name,registered,completed\n2026-01,C001,Gaborone Learning Centre,32,24\n2026-01,C002,\"North, Main Centre\",27,18")
        . '<h3>相対パスは現在の作業フォルダから解釈される</h3>'
        . '<p><code>data/learning-centres-practice.csv</code>はNotebookファイルの位置ではなく、カーネルの現在の作業フォルダを基準にします。そのためPython Labを別の入口から開くと、同じ相対パスでも見つからないことがあります。教材Notebookは現在位置と親フォルダ、サーバー上の学習領域、配布元を調べ、実際に読み込んだ絶対パスを表示します。<code>FileNotFoundError</code>では、まず現在位置と確認済みパスを読みます。</p>'
        . v22_code("from pathlib import Path\nprint(\"Working directory:\", Path.cwd())\ndata_file = find_course_data(\"learning-centres-practice.csv\")\nprint(\"Loading:\", data_file.resolve())")
        . '<h3>read_csvの前提をコードへ明示する</h3>'
        . '<p><code>import pandas as pd</code>は一般的な読み込み方です。<code>pd.read_csv()</code>はCSVからDataFrameを作ります。ここではUTF-8を指定し、<code>centre_id</code>と<code>month</code>を計算対象ではない文字列として保持します。別のファイルでは<code>sep</code>や<code>encoding</code>が異なる可能性があります。</p>'
        . v22_code("df = pd.read_csv(\n    data_file,\n    encoding=\"utf-8\",\n    dtype={\"centre_id\": \"string\", \"month\": \"string\"},\n)")
        . '<h3>計算の前に形、列名、型、欠損を確認する</h3>'
        . '<p><code>head()</code>で値の並び、<code>shape</code>で行数と列数、<code>columns</code>で正確な列名、<code>dtypes</code>と<code>info()</code>で推定型、<code>isna().sum()</code>で欠損数を確認します。練習CSVは24行10列で、品質問題を意図的に含みます。読み込めたことと、正しく読めたことは同じではありません。</p>'
        . v22_code("print(df.head(3))\nprint(\"Shape:\", df.shape)\nprint(df.columns.tolist())\nprint(df.dtypes)\nprint(df.isna().sum())\ndf.info()")
        . '<h3>一列はSeries、複数列の表はDataFrameになる</h3>'
        . '<p><code>df[\"registered\"]</code>は一次元のSeries、<code>df[[\"registered\"]]</code>は一列を持つ二次元のDataFrameです。列名は完全一致で指定します。Seriesどうしの演算は、対応する行へまとめて適用されます。</p>'
        . '<h3>計算列は確認後に追加する</h3>'
        . '<p><code>assign()</code>を使うと、元の<code>df</code>を直接変更せず、計算列を持つ新しいDataFrameを作れます。分母0、欠損、不正値をどう扱うか決めないまま最終結果として解釈してはいけません。これらの品質判断は3.3で詳しく扱います。</p>'
        . v22_code("report = df.assign(\n    completion_rate=df[\"completed\"] / df[\"registered\"] * 100\n)\nprint(report[[\"centre_name\", \"completion_rate\"]].head())")
        . '<h3>indexと業務上の識別子を区別する</h3>'
        . '<p>DataFrame左端のindexはpandasが行へ付けるラベルで、<code>centre_id</code>ではありません。CSVへ保存するときに<code>index=False</code>を指定すると、業務データに不要なindex列を書き出しません。保存後はパスとヘッダーを確認します。</p>'
        . '<h3>読込失敗を原因別に調べる</h3>'
        . '<p><code>FileNotFoundError</code>では作業フォルダと探索先、列が一列だけなら区切り文字、文字化けや<code>UnicodeDecodeError</code>なら文字コード、数値列が文字列なら空白・単位記号・不正値を確認します。推測で値を直す前に、入力と読込条件を記録します。</p>'
        . '<h3>例題から応用へ</h3><p>2.3のセンターレコードからDataFrameを作り、CSVへ保存して再読込します。前後のshape、列名、識別子、人数合計を比較し、<code>attendance_rate</code>と<code>completion_rate</code>を追加してください。次の3.2では、分析の問いに合う行と列を選びます。</p>'
        . '<p><strong>学習時間の目安：</strong>約4時間</p><p style="display:none">PYAI-V22-LESSON31-FLOW</p></div>';
    $datasetcontent = '<div class="python-dataset-progression"><h2>学習センターデータの発展</h2><p>このコースでは、個人情報を含まない架空の運営データを使います。小さな既知のデータで処理を確認してから、同じスキーマの大きなデータへ進みます。</p>'
        . '<h3>24行の練習用ファイル</h3><p><code>data/learning-centres-practice.csv</code>には24件のセンター・月レコードと10列があります。空欄、地区名の表記ゆれ、出席者数を上回る修了者数を意図的に含みます。3.1では存在を確認し、3.2で選択し、3.3で品質問題として扱います。</p>'
        . v22_code("month,centre_id,centre_name,district,course,registered,attended,completed,training_hours,material_cost\n2026-01,C001,Gaborone Learning Centre,South,Python Foundations,32,28,24,24,410.50")
        . '<h3>10,000行から250,000行へ</h3><p>付属の生成スクリプトは同じシードから同じ架空データを作ります。まず10,000行で処理と件数を検証し、後のスケーリング課題で250,000行以上へ進みます。</p>'
        . v22_code("python data/generate-learning-centre-data.py --rows 10000 --output learning-centres-10000.csv\npython data/generate-learning-centre-data.py --rows 250000 --output learning-centres-large.csv")
        . '<p>大きなデータでも、読み込んだパス、shape、列名、型、欠損数を最初に記録する手順は変わりません。</p><p style="display:none">PYAI-V22-DATASET-PROGRESSION</p></div>';
    $questions = [
        v22_question('L31R-01', '<p>「一行が一つのセンター・月」を表す表で、<code>registered</code>列は何を表しますか。</p>', [['一つの観測', '一行全体が一観測です。'], ['すべての観測で同じ意味を持つ変数', '正解です。列は同じ意味の変数をそろえます。'], ['CSVの文字コード', '列の意味とは別です。'], ['DataFrameのファイル名', 'ファイル名ではありません。']], 1, '行は観測、列は変数、セルは一観測の一変数の値です。'),
        v22_question('L31R-02', '<p>次のCSVレコードはいくつのフィールドとして読まれるべきですか。</p>' . v22_code('C001,"North, Main Centre",32'), [['2', '引用符内のカンマは区切りではありません。'], ['3', '正解です。引用符内のカンマは値の一部です。'], ['4', '引用符内を二つへ分けません。'], ['CSVとして無効', '引用符により有効です。']], 1, 'カンマを含む値は引用符で囲めます。'),
        v22_question('L31R-03', '<p>Notebookが<code>/home/jovyan/work/ja</code>から実行されるとき、<code>data/file.csv</code>は最初にどこを基準として解釈されますか。</p>', [['Notebookテンプレートの保存場所', '相対パスはNotebookファイル位置とは限りません。'], ['現在の作業フォルダ', '正解です。Path.cwd()で確認できます。'], ['Moodleサーバーのルート', 'Pythonカーネルの現在位置が基準です。'], ['常に/home/jovyan/work/data', '補助関数はそこも探しますが、単純な相対パスの最初の基準ではありません。']], 1, '相対パスはカーネルの現在の作業フォルダから解釈されます。'),
        v22_question('L31R-04', '<p><code>df.shape</code>が<code>(24, 10)</code>のとき、何を意味しますか。</p>', [['24列、10行', '順序が逆です。'], ['24行、10列', '正解です。shapeは行数、列数の順です。'], ['合計34セル', 'セル数は24×10です。'], ['indexが24種類', 'index種類の表示ではありません。']], 1, 'DataFrame.shapeは(rows, columns)です。'),
        v22_question('L31R-05', '<p>新しいCSVを読み込んだ直後、計算より先に行う確認として最も適切なのはどれですか。</p>', [['head、shape、columns、dtypes、欠損数を確認する', '正解です。実際のスキーマと品質を先に確認します。'], ['最終グラフを作る', '列や型が正しいか未確認です。'], ['空欄をすべて0へ変える', '空欄の意味を確認していません。'], ['全列を整数へ変換する', '識別子や名称列があります。']], 0, '計算前に値の並び、形、列名、型、欠損を確認します。'),
        v22_question('L31R-06', '<p><code>centre_id</code>を<code>dtype="string"</code>として読む主な理由は何ですか。</p>', [['平均を計算しやすくするため', '識別子は数量ではありません。'], ['コードを数量として扱わず、表記を保持するため', '正解です。先頭0なども意味を持つ場合があります。'], ['欠損値を必ず0にするため', '文字列指定は0補完ではありません。'], ['行数を減らすため', 'dtypeは行を削除しません。']], 1, '識別子と数量を区別し、コード表記を保持します。'),
        v22_question('L31R-07', '<p>次の二つの結果の組合せとして正しいものはどれですか。</p>' . v22_code('a = df["registered"]\nb = df[["registered"]]'), [['aもbもSeries', '二組の角括弧ではDataFrameです。'], ['aはSeries、bはDataFrame', '正解です。一次元と二次元の違いがあります。'], ['aはDataFrame、bはSeries', '逆です。'], ['どちらも文字列', 'pandasオブジェクトです。']], 1, '単一列名はSeries、列名リストはDataFrameを返します。'),
        v22_question('L31R-08', '<p>最初の行の<code>completion_rate</code>はいくつですか。</p>' . v22_code('df = pd.DataFrame({"registered": [10, 20], "completed": [8, 10]})\ndf["completion_rate"] = df["completed"] / df["registered"] * 100'), [['50', 'これは二行目の率です。'], ['80', '正解です。8÷10×100です。'], ['125', '分子と分母が逆です。'], ['Series全体で一つの65', '演算は各行へ適用されます。']], 1, 'Series演算は対応する各行で計算されます。'),
        v22_question('L31R-09', '<p>CSVの<code>attended</code>欄が空で、通常の<code>read_csv()</code>で読んだ場合、最初に取るべき扱いはどれですか。</p>', [['自動的に0人と断定する', '空欄と本当の0は意味が違います。'], ['欠損として検出し、意味を確認する', '正解です。isna()などで確認します。'], ['その行を無条件に削除する', '削除判断の根拠がありません。'], ['前の行の値を必ず使う', '補完規則が定義されていません。']], 1, '空欄を欠損として把握し、業務上の意味を確認してから処理します。'),
        v22_question('L31R-10', '<p><code>report.to_csv("report.csv", index=False)</code>の<code>index=False</code>は何を防ぎますか。</p>', [['列名が保存されること', '列名は既定で保存されます。'], ['pandasの行indexが余分な列として書かれること', '正解です。業務列だけを書き出します。'], ['UTF-8で保存されること', 'encodingとは別の指定です。'], ['欠損値が存在すること', '欠損処理ではありません。']], 1, 'DataFrameのindexが業務データでなければ、書出し列から除外します。'),
    ];
} else {
    $oldtopic = '3.1 Tables, CSV, and pandas';
    $topicname = '3.1 Tabular data, CSV, and pandas';
    $topicsummary = '<p>Move Python records into a table, then inspect CSV structure, paths, encoding, DataFrame shape, columns, types, and missingness before column calculations.</p>';
    $oldpage = 'Lesson 7: Tables, CSV, and pandas';
    $oldlti = 'Python Lab 07: Tables, CSV, and pandas';
    $oldquiz = 'Knowledge check: Lesson 7: Tables, CSV, and pandas';
    $olddataset = 'Dataset pack: From 24 rows to 250,000 fictional records';
    $pagename = 'Lesson 3.1: Tabular data, CSV, and pandas';
    $ltiname = 'Python Lab 3.1: Tabular data, CSV, and pandas';
    $quizname = 'Knowledge check: 3.1 Tabular data, CSV, and pandas';
    $datasetname = 'Dataset progression: 24 to 250,000 fictional records';
    $pageintro = '<p>Move a list of dictionaries into tabular form, load CSV from a verified path and with explicit assumptions, then inspect its schema before calculating with pandas.</p>';
    $quizintro = '<p>Use short code to check CSV loading assumptions and DataFrame shape, columns, types, missingness, and calculations. Attempts are unlimited and the highest score is retained.</p>';
    $body = '<div class="python-sample-lesson"><h2>Treat a collection of records as an inspectable table</h2>'
        . '<p>Lesson 2.3 processed one centre as a dictionary and several as a list. As records and fields grow, tabular data aligns the same columns across every record and supports column-level inspection and calculation. pandas represents this table as a <code>DataFrame</code>.</p>'
        . '<h3>Align one observation per row and one variable per column</h3><p>Here one row is one centre-month observation, one column is a variable with consistent meaning, and one cell is one variable value for one observation. Treat expected types, units, and missing-value rules together with column names as the schema. Mixing counts and text in one column makes later calculations unreliable.</p>'
        . '<h3>CSV is a text representation of a table</h3><p>A CSV commonly has a header on its first line, records on later lines, and commas between fields. Values containing commas or newlines need quoting. CSV does not retain cell colours, formulas, or multiple sheets. Decide how to read encoding, delimiter, empty fields, and identifiers with meaningful leading zeros.</p>'
        . v22_code("month,centre_id,centre_name,registered,completed\n2026-01,C001,Gaborone Learning Centre,32,24\n2026-01,C002,\"North, Main Centre\",27,18")
        . '<h3>A relative path is interpreted from the current working directory</h3><p><code>data/learning-centres-practice.csv</code> starts from the kernel working directory, not necessarily from the Notebook file. Different Python Lab entry points can therefore change what the same relative path means. The course helper checks the current location and parents, the server work area, and distributed materials, then prints the absolute path actually loaded. For <code>FileNotFoundError</code>, first read the working directory and checked paths.</p>'
        . v22_code("from pathlib import Path\nprint(\"Working directory:\", Path.cwd())\ndata_file = find_course_data(\"learning-centres-practice.csv\")\nprint(\"Loading:\", data_file.resolve())")
        . '<h3>Express read_csv assumptions in code</h3><p><code>import pandas as pd</code> is the conventional import. <code>pd.read_csv()</code> creates a DataFrame. Here UTF-8 is explicit, while <code>centre_id</code> and <code>month</code> remain strings rather than quantities. Another file may require a different <code>sep</code> or <code>encoding</code>.</p>'
        . v22_code("df = pd.read_csv(\n    data_file,\n    encoding=\"utf-8\",\n    dtype={\"centre_id\": \"string\", \"month\": \"string\"},\n)")
        . '<h3>Inspect shape, names, types, and missingness before calculation</h3><p>Use <code>head()</code> for value layout, <code>shape</code> for row and column counts, <code>columns</code> for exact names, <code>dtypes</code> and <code>info()</code> for inferred types, and <code>isna().sum()</code> for missing counts. The practice CSV has 24 rows and 10 columns with deliberate quality issues. Loading successfully is not the same as loading correctly.</p>'
        . v22_code("print(df.head(3))\nprint(\"Shape:\", df.shape)\nprint(df.columns.tolist())\nprint(df.dtypes)\nprint(df.isna().sum())\ndf.info()")
        . '<h3>One column is a Series; a multi-column table is a DataFrame</h3><p><code>df[\"registered\"]</code> is a one-dimensional Series. <code>df[[\"registered\"]]</code> is a two-dimensional DataFrame with one column. Column names must match exactly. Arithmetic between Series applies across corresponding rows.</p>'
        . '<h3>Add derived columns only after inspection</h3><p><code>assign()</code> creates a new DataFrame with a calculated column without directly changing the source <code>df</code>. Do not interpret a final result before deciding how zero denominators, missing values, and invalid values should behave. Lesson 3.3 examines those quality decisions.</p>'
        . v22_code("report = df.assign(\n    completion_rate=df[\"completed\"] / df[\"registered\"] * 100\n)\nprint(report[[\"centre_name\", \"completion_rate\"]].head())")
        . '<h3>Distinguish the index from an operational identifier</h3><p>The index displayed at the left is a pandas row label, not <code>centre_id</code>. With <code>index=False</code>, exporting to CSV does not add that non-business index as another column. Inspect the saved path and header afterward.</p>'
        . '<h3>Diagnose loading failures by cause</h3><p>For <code>FileNotFoundError</code>, inspect the working directory and candidate paths. If everything becomes one column, inspect the delimiter. For mojibake or <code>UnicodeDecodeError</code>, inspect encoding. If a numeric column becomes text, inspect spaces, units, and invalid values. Record the input and loading assumptions before changing values.</p>'
        . '<h3>From guided example to transfer</h3><p>Create a DataFrame from the centre records in 2.3, save it to CSV, and load it again. Compare shape, column names, identifiers, and count totals before and after, then add <code>attendance_rate</code> and <code>completion_rate</code>. Lesson 3.2 selects the rows and columns that answer a question.</p>'
        . '<p><strong>Estimated study time:</strong> about 4 hours</p><p style="display:none">PYAI-V22-LESSON31-FLOW</p></div>';
    $datasetcontent = '<div class="python-dataset-progression"><h2>Learning-centre dataset progression</h2><p>The course uses fictional operational data with no personal information. Confirm a process on a small, known dataset before applying the same schema to larger data.</p>'
        . '<h3>24-row practice file</h3><p><code>data/learning-centres-practice.csv</code> contains 24 centre-month records and 10 columns. It deliberately includes a blank, inconsistent district text, and completion above attendance. Lesson 3.1 establishes what is present, 3.2 selects data, and 3.3 treats these as quality issues.</p>'
        . v22_code("month,centre_id,centre_name,district,course,registered,attended,completed,training_hours,material_cost\n2026-01,C001,Gaborone Learning Centre,South,Python Foundations,32,28,24,24,410.50")
        . '<h3>From 10,000 to 250,000 rows</h3><p>The supplied generator creates the same fictional data from the same seed. Start with 10,000 rows to verify processing and counts, then move to 250,000 or more in the later scaling project.</p>'
        . v22_code("python data/generate-learning-centre-data.py --rows 10000 --output learning-centres-10000.csv\npython data/generate-learning-centre-data.py --rows 250000 --output learning-centres-large.csv")
        . '<p>The first checks remain the loaded path, shape, columns, dtypes, and missing counts even when the data becomes large.</p><p style="display:none">PYAI-V22-DATASET-PROGRESSION</p></div>';
    $questions = [
        v22_question('L31R-01', '<p>In a table where one row is one centre-month, what does the <code>registered</code> column represent?</p>', [['One observation', 'The entire row is one observation.'], ['One variable with the same meaning across observations', 'Correct: a column aligns one variable.'], ['The CSV encoding', 'Encoding is separate from column meaning.'], ['The DataFrame filename', 'It is not a filename.']], 1, 'Rows are observations, columns are variables, and cells hold one variable value for one observation.'),
        v22_question('L31R-02', '<p>How many fields should this CSV record contain?</p>' . v22_code('C001,"North, Main Centre",32'), [['2', 'The comma inside quotes is not a delimiter.'], ['3', 'Correct: the quoted comma belongs to one value.'], ['4', 'Do not split inside the quotes.'], ['It is invalid CSV', 'Quoting makes this valid.']], 1, 'Quotes allow a field to contain a comma.'),
        v22_question('L31R-03', '<p>If the Notebook runs from <code>/home/jovyan/work/ja</code>, what is the first base for <code>data/file.csv</code>?</p>', [['The template storage folder', 'A relative path does not necessarily start beside the Notebook.'], ['The current working directory', 'Correct: inspect it with Path.cwd().'], ['The Moodle server root', 'The Python kernel working directory is used.'], ['Always /home/jovyan/work/data', 'The helper also checks there, but it is not the initial base of a plain relative path.']], 1, 'A relative path is interpreted from the kernel current working directory.'),
        v22_question('L31R-04', '<p>What does <code>df.shape == (24, 10)</code> mean?</p>', [['24 columns and 10 rows', 'The order is reversed.'], ['24 rows and 10 columns', 'Correct: shape reports rows, then columns.'], ['34 cells', 'There are 24×10 cells.'], ['24 different indexes', 'It does not report index categories.']], 1, 'DataFrame.shape is (rows, columns).'),
        v22_question('L31R-05', '<p>What is the best first action after loading a new CSV?</p>', [['Inspect head, shape, columns, dtypes, and missing counts', 'Correct: establish actual schema and quality first.'], ['Build the final chart', 'Columns and types are not confirmed.'], ['Replace every blank with zero', 'The blank meaning is unknown.'], ['Convert every column to integers', 'Identifiers and names are present.']], 0, 'Inspect layout, shape, names, types, and missingness before calculation.'),
        v22_question('L31R-06', '<p>Why read <code>centre_id</code> with <code>dtype="string"</code>?</p>', [['To calculate its mean', 'An identifier is not a quantity.'], ['To preserve it as a code rather than a quantity', 'Correct; leading zeros may also be meaningful.'], ['To replace missing values with zero', 'A string dtype does not perform that replacement.'], ['To reduce the row count', 'A dtype does not remove rows.']], 1, 'Distinguish identifiers from quantities and preserve their representation.'),
        v22_question('L31R-07', '<p>Which result types are correct?</p>' . v22_code('a = df["registered"]\nb = df[["registered"]]'), [['Both are Series', 'A column-name list returns a DataFrame.'], ['a is a Series; b is a DataFrame', 'Correct: they are one- and two-dimensional.'], ['a is a DataFrame; b is a Series', 'This is reversed.'], ['Both are strings', 'They are pandas objects.']], 1, 'A single column name returns Series; a list of column names returns DataFrame.'),
        v22_question('L31R-08', '<p>What is the first row completion rate?</p>' . v22_code('df = pd.DataFrame({"registered": [10, 20], "completed": [8, 10]})\ndf["completion_rate"] = df["completed"] / df["registered"] * 100'), [['50', 'That is the second row.'], ['80', 'Correct: 8 divided by 10, times 100.'], ['125', 'The numerator and denominator are reversed.'], ['One value 65 for the entire Series', 'The operation applies per row.']], 1, 'Series arithmetic calculates corresponding rows.'),
        v22_question('L31R-09', '<p>If an <code>attended</code> CSV field is blank under ordinary <code>read_csv()</code>, what should happen first?</p>', [['Assume it means zero people', 'Blank and genuine zero are different.'], ['Detect it as missing and investigate its meaning', 'Correct: inspect it with isna() or related checks.'], ['Delete the row unconditionally', 'No deletion rule has been justified.'], ['Always copy the previous row', 'No fill rule has been established.']], 1, 'Identify missingness and its operational meaning before handling it.'),
        v22_question('L31R-10', '<p>What does <code>index=False</code> prevent in <code>report.to_csv("report.csv", index=False)</code>?</p>', [['Saving column names', 'Headers are saved by default.'], ['Writing the pandas row index as an extra column', 'Correct: only intended data columns are written.'], ['Using UTF-8', 'Encoding is a separate option.'], ['Having missing values', 'It does not clean missing values.']], 1, 'Exclude the DataFrame index when it is not an operational field.'),
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

$page = v22_find_and_rename($course->id, 'page', $oldpage, $pagename);
$lti = v22_find_and_rename($course->id, 'lti', $oldlti, $ltiname);
$quiz = v22_find_and_rename($course->id, 'quiz', $oldquiz, $quizname);
$dataset = v22_find_and_rename($course->id, 'page', $olddataset, $datasetname);

$page->intro = $pageintro;
$page->introformat = FORMAT_HTML;
$page->content = $body;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);
$dataset->content = $datasetcontent;
$dataset->contentformat = FORMAT_HTML;
$dataset->timemodified = time();
$DB->update_record('page', $dataset);

$expectedpath = $language === 'ja' ? '/ja/07_tables_csv_pandas.ipynb' : '/07_tables_csv_pandas.ipynb';
$newurl = preg_replace('~/(?:ja/)?07_tables_csv_pandas\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || ($newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath))) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $language === 'ja'
    ? '<p>実際の作業フォルダと読込パスを確認し、CSVのshape、列名、型、欠損を表示してから列計算を行います。</p>'
    : '<p>Confirm the working directory and loaded path, then inspect CSV shape, columns, dtypes, and missingness before column calculations.</p>';
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
    $saved = v22_save_question($category->id, $context->id, $shortname . ' v22: ', $question, $language);
    quiz_add_quiz_question($saved->id, $quiz, 0, 10);
}
$DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();

$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
$quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
$datasetcm = get_coursemodule_from_instance('page', $dataset->id, $course->id, false, MUST_EXIST);
$delegated->sequence = implode(',', [$pagecm->id, $lticm->id, $quizcm->id, $datasetcm->id]);
$DB->update_record('course_sections', $delegated);
foreach ([$pagecm->id, $lticm->id, $quizcm->id, $datasetcm->id] as $cmid) {
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
    'datasetpageid' => (int)$dataset->id,
    'questions' => count($questions),
    'attempts_removed' => $attemptsremoved,
    'lti_path' => $expectedpath,
    'marker' => 'PYAI-V22-LESSON31-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
