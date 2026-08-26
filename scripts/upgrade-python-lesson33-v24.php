<?php
// Rewrite Chapter 3.3 while preserving existing Moodle activities.
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

function v24_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}

function v24_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v24_save_question(int $categoryid, int $contextid, string $prefix, array $data, string $language): stdClass {
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
            ? '<p>正解です。規則、影響件数、処置、検証を結び付けて次へ進みましょう。</p>'
            : '<p>Correct. Connect the rule, affected count, action, and validation before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $language === 'ja'
            ? '<p>元データを保持したまま、検出規則と処置を分けて考え、もう一度確認しましょう。</p>'
            : '<p>Keep the source unchanged, separate the detection rule from its action, and try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v24_find_and_rename(int $courseid, string $table, string $oldname, string $newname): stdClass {
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
    $oldtopic = '3.3 監査証跡を残すデータクリーニング';
    $topicname = '3.3 データのクリーニングと監査記録';
    $topicsummary = '<p>元データを保持し、欠損、型変換失敗、表記ゆれ、範囲違反、項目間制約、重複を規則に基づいて検出し、件数・処置・検証結果を監査記録へ残します。</p>';
    $oldpage = 'レッスン9：データクリーニング';
    $oldlti = 'Python Lab 09：監査証跡を残すクリーニング';
    $oldquiz = '理解度チェック：レッスン9 データクリーニング';
    $pagename = 'レッスン3.3：データのクリーニングと監査記録';
    $ltiname = 'Python Lab 3.3：データのクリーニングと監査記録';
    $quizname = '理解度チェック：3.3 データのクリーニングと監査記録';
    $pageintro = '<p>元データを上書きせず、問題の検出、処置、件数照合を分離して、再現可能な分析用データを作ります。</p>';
    $quizintro = '<p>短い状況とコードから、元データの保持、型変換、欠損、表記正規化、制約、重複、監査記録、件数照合を確認します。何度でも挑戦でき、最高点が記録されます。</p>';
    $body = '<div class="python-sample-lesson"><h2>データを直す前に、何を問題とするか決める</h2>'
        . '<p>3.2では、必要な列と条件を指定して行を抽出しました。しかし、欠損や表記ゆれ、不可能な値を説明しないまま抽出すると、正しく動くコードから誤った結論が生まれます。データクリーニングは、見栄えを整えたり、都合の悪い行を削除したりする作業ではありません。元データを保持し、規則を先に定義し、問題を検出し、処置と結果を記録する工程です。</p>'
        . '<h3>元データ、作業用データ、分析用データを分ける</h3>'
        . '<p><code>raw</code>は読み込んだ状態のまま保持します。<code>clean = raw.copy()</code>で作業用データを作り、問題の有無を表すフラグを追加します。そのうえで、定義した分析に使える行だけを<code>analysis</code>へ取り出します。三つを分けると、どの値を変え、どの行を分析対象外にしたかを追跡できます。</p>'
        . v24_code("raw = pd.read_csv(data_file)\nclean = raw.copy()\nprint(\"Rows:\", len(raw))\nprint(raw.dtypes)\nprint(raw.isna().sum())")
        . '<h3>修正前に型、欠損、カテゴリ、範囲を観察する</h3>'
        . '<p>最初に<code>dtypes</code>、<code>isna().sum()</code>、カテゴリの<code>unique()</code>、数値列の<code>describe()</code>を確認します。この時点の件数が基準です。問題を見つける前に値を置き換えると、元からあった問題と処理によって生じた問題を区別できません。</p>'
        . '<h3>型変換失敗を、元からある欠損と区別する</h3>'
        . '<p><code>pd.to_numeric(..., errors="coerce")</code>は数値へ変換できない文字を欠損値へ変えます。便利ですが、変換前から空だった値と、新しく変換に失敗した値は意味が違います。変換前の欠損マスクと変換後の欠損マスクの差を取り、列ごとの失敗件数を記録します。</p>'
        . v24_code("before_missing = clean[\"attended\"].isna()\nconverted = pd.to_numeric(clean[\"attended\"], errors=\"coerce\")\nconversion_failed = converted.isna() & ~before_missing\nclean[\"attended\"] = converted\nprint(\"Source missing:\", int(before_missing.sum()))\nprint(\"Conversion failures:\", int(conversion_failed.sum()))")
        . '<h3>欠損は0ではない</h3>'
        . '<p>出席者数の空欄は「未報告」であり、「出席者が0人」とは限りません。根拠なく0や平均値で補完すると、割合や合計の意味を変えます。<code>isna()</code>で欠損を明示し、集計から除くのか、確認待ちとして隔離するのか、信頼できる原資料で修正するのかを決めて記録します。</p>'
        . '<h3>表記を統一しても元の文字列を残す</h3>'
        . '<p>地区名の前後空白や大文字・小文字の違いは、集計時に別カテゴリを作ります。作業列へ<code>str.strip()</code>と<code>str.title()</code>を適用し、元の文字列は<code>district_raw</code>に残します。ただし、似た名前を同じ意味だと推測して統合してはいけません。明示された表記規則または対応表が必要です。</p>'
        . v24_code("clean[\"district_raw\"] = clean[\"district\"]\nclean[\"district\"] = clean[\"district\"].astype(\"string\").str.strip().str.title()\nchanged = clean[\"district_raw\"].astype(\"string\") != clean[\"district\"]\nprint(\"Changed labels:\", int(changed.sum()))")
        . '<h3>単独の範囲と、項目間の関係を別々に検査する</h3>'
        . '<p>人数と費用は0以上という単独の範囲規則に加え、この表では<code>registered &gt;= attended &gt;= completed</code>という項目間の制約があります。欠損を不正値と混同せず、規則ごとに名前付きマスクを作って件数を表示します。どの規則に違反したか分からない一つの巨大な条件式にはしません。</p>'
        . v24_code("missing_attended = clean[\"attended\"].isna()\ncompletion_over_attendance = (\n    clean[\"completed\"].notna()\n    & clean[\"attended\"].notna()\n    & (clean[\"completed\"] > clean[\"attended\"])\n)\nnegative_cost = clean[\"material_cost\"].notna() & (clean[\"material_cost\"] < 0)\nprint(int(missing_attended.sum()), int(completion_over_attendance.sum()), int(negative_cost.sum()))")
        . '<h3>重複は業務キーを決めてから調べる</h3>'
        . '<p>この表の1記録を「センター・月・コース」と定義するなら、その三列が業務キーです。<code>duplicated(subset=business_key, keep=False)</code>は重複グループの全行を示します。同じセンターが異なる月に現れることは正当なので、全列一致やセンターIDだけで判定してはいけません。</p>'
        . v24_code("business_key = [\"centre_id\", \"month\", \"course\"]\nduplicate_key = clean.duplicated(subset=business_key, keep=False)\nprint(\"Duplicate-key rows:\", int(duplicate_key.sum()))")
        . '<h3>検出と処置を分ける</h3>'
        . '<p>問題を検出しただけでは、正しい値は分かりません。原資料で確認できれば修正し、表記規則があれば正規化し、根拠がなければ欠損または確認待ちとして扱います。不可能な修了者数を出席者数へ合わせるといった推測はしません。ここでは行を削除せず<code>analysis_ready</code>フラグを付け、分析用の抽出を別に作ります。</p>'
        . v24_code("clean[\"analysis_ready\"] = ~(missing_attended | completion_over_attendance | negative_cost | duplicate_key)\nanalysis = clean.loc[clean[\"analysis_ready\"]].copy()\nprint(\"Raw:\", len(raw), \"ready:\", len(analysis), \"flagged:\", int((~clean[\"analysis_ready\"]).sum()))")
        . '<h3>監査記録とassertで再現可能にする</h3>'
        . '<p>監査記録には、問題名、検出規則、影響件数、実施した処置、未解決件数を残します。0件だった検査も、確認を実施した証拠です。最後に、元件数が分析可能件数とフラグ件数の合計に一致すること、分析用データが同じ制約を満たすことを<code>assert</code>で確認します。</p>'
        . v24_code("assert len(raw) == len(clean)\nassert len(clean) == int(clean[\"analysis_ready\"].sum()) + int((~clean[\"analysis_ready\"]).sum())\nassert not (analysis[\"completed\"] > analysis[\"attended\"]).any()")
        . '<h3>例題から応用へ</h3><p>練習CSVについて、欠損した出席者数、地区名の表記ゆれ、修了者数が出席者数を上回る行を検出し、各件数を記録します。その応用として、欠損した修了者数、登録者数を上回る出席者数、0以下の研修時間、負の教材費、業務キー重複も検査し、規則・件数・提案する処置を監査表へまとめます。</p>'
        . '<p>このレッスンを終えたら、元データを保持し、型変換失敗と元の欠損を区別し、表記ゆれ、範囲違反、項目間制約、業務キー重複を検出できます。また、処置を推測せず、件数照合と監査記録で分析用データの作成過程を説明できます。</p>'
        . '<p><strong>学習時間の目安：</strong>約3時間</p><p style="display:none">PYAI-V24-LESSON33-FLOW</p></div>';
    $questions = [
        v24_question('L33R-01', '<p>月次CSVを読み込んだ直後の処理として、監査可能性を最もよく保つものはどれですか。</p>', [['欠損行を直ちに削除して同じ変数へ保存する', '変更前の状態が失われます。'], ['rawを保持し、clean = raw.copy()から処理する', '正解です。元と作業後を比較できます。'], ['すべての欠損を0にしてから件数を調べる', '元の欠損件数が失われます。'], ['カテゴリ名を目視で直接上書きする', '変更規則と件数を再現できません。']], 1, '元データと作業用データを分けると、変更内容を追跡できます。'),
        v24_question('L33R-02', '<p>次のコードで<code>failed</code>が表すものは何ですか。</p>' . v24_code('before = s.isna()\nconverted = pd.to_numeric(s, errors="coerce")\nfailed = converted.isna() & ~before'), [['変換前から空だった値', 'それはbeforeです。'], ['数値へ正常に変換できた値', 'convertedが欠損ではない値です。'], ['変換前は欠損でなく、数値変換後に欠損となった値', '正解です。新しい変換失敗を区別しています。'], ['0へ変換された値', 'coerceは変換不能値を欠損にします。']], 2, '前後の欠損マスクの差で変換失敗を検出します。'),
        v24_question('L33R-03', '<p>出席者数が空欄の行を、根拠なく0で埋めない主な理由はどれですか。</p>', [['0は未報告ではなく「出席者なし」という別の意味だから', '正解です。欠損と0を区別します。'], ['pandasが0を保存できないから', 'pandasは0を保存できます。'], ['空欄は必ず文字列だから', '読み込み後は欠損値になることがあります。'], ['平均では0が常に無視されるから', '0は通常平均に含まれます。']], 0, '欠損を0へ変えるとデータの意味と集計結果が変わります。'),
        v24_question('L33R-04', '<p><code>" central "</code>と<code>"Central"</code>を統一するとき、最も監査しやすい方法はどれですか。</p>', [['元列を上書きし変更件数を数えない', '変更を追跡できません。'], ['元文字列を別列に保持し、明示したstripとtitleの規則で正規化して件数を数える', '正解です。規則と影響を説明できます。'], ['似ている地区名をすべて自動で同一視する', '意味が異なるカテゴリを統合する危険があります。'], ['地区列を削除する', '集計に必要な情報を失います。']], 1, '正規化規則、元値、変更件数を残します。'),
        v24_question('L33R-05', '<p>このデータ定義で、品質問題としてフラグすべき行はどれですか。</p>', [['registered=40, attended=34, completed=30', '順序制約を満たします。'], ['registered=40, attendedが欠損, completed=30', '欠損問題ですが、項目間の大小違反とはまだ判定できません。'], ['registered=40, attended=34, completed=39', '正解です。修了者数が出席者数を上回ります。'], ['registered=40, attended=40, completed=40', '等しい値はこの規則で許されます。']], 2, 'registered >= attended >= completedという定義に照らして判定します。'),
        v24_question('L33R-06', '<p>欠損を不正値と混同せず「修了者数が出席者数を上回る行」を調べる式として適切なのはどれですか。</p>', [['completed > attended', '比較だけでは欠損を別問題として明示できません。'], ['completed.notna() & attended.notna() & (completed > attended)', '正解です。既知の値だけで制約違反を判定します。'], ['completed.isna() | attended.isna()', 'これは欠損を検出します。'], ['completed and attended', 'Seriesの行ごとの条件には使えません。']], 1, '欠損マスクと制約違反マスクを分けて数えます。'),
        v24_question('L33R-07', '<p>1記録を「センター・月・コース」と定義したとき、重複検査として最も適切なのはどれですか。</p>', [['df.duplicated(subset=["centre_id", "month", "course"], keep=False)', '正解です。業務キーが重なる全行を示します。'], ['df.duplicated(subset=["centre_id"], keep=False)', '同じセンターの別月まで重複になります。'], ['df.drop_duplicates()だけを直ちに実行する', '検出件数と対象行を確認できません。'], ['month列の値が同じ行をすべて重複とする', '同じ月に複数センターがあります。']], 0, '重複は業務上の一意キーを先に定義して判定します。'),
        v24_question('L33R-08', '<p>不可能な値を検出した直後の処置として最も適切なのはどれですか。</p>', [['もっともらしい値へ推測で置換する', '根拠のない修正です。'], ['元行を消し、削除件数を残さない', '影響を説明できません。'], ['問題をフラグし、原資料で修正できるか確認し、根拠がなければ分析対象外または確認待ちとして記録する', '正解です。検出と処置を分離しています。'], ['制約に合うよう全列を小さくする', '他の値の意味まで変えます。']], 2, '問題を見つけたことと、正しい置換値を知っていることは別です。'),
        v24_question('L33R-09', '<p>監査記録に含める組合せとして最も適切なのはどれですか。</p>', [['問題名、検出規則、影響件数、処置、未解決件数', '正解です。判断と影響を追跡できます。'], ['完成したグラフの色だけ', 'クリーニング判断を説明できません。'], ['削除後のファイル名だけ', '何をなぜ変えたか分かりません。'], ['問題が1件以上あった検査だけ', '0件だった検査も実施した証拠です。']], 0, '監査記録は規則、件数、処置、残件を結び付けます。'),
        v24_question('L33R-10', '<p>元データ24行、analysis_readyが22行、フラグ行が2行のとき、最も直接的な件数照合はどれですか。</p>', [['24 == 22 + 2を確認する', '正解です。全行がどちらかに説明されています。'], ['22 > 24を確認する', '分析用件数は元件数を超えません。'], ['フラグ2行を消して件数を忘れる', '除外の影響を説明できません。'], ['カテゴリ数だけを比較する', '行の取りこぼしを直接確認できません。']], 0, '元件数を分析可能件数とフラグ件数へ分解して照合します。'),
    ];
} else {
    $oldtopic = '3.3 Cleaning data with an audit trail';
    $topicname = '3.3 Data cleaning and audit records';
    $topicsummary = '<p>Preserve the source, detect missingness, conversion failures, inconsistent labels, invalid ranges, cross-field violations, and duplicate keys, then record counts, actions, and validation.</p>';
    $oldpage = 'Lesson 9: Cleaning data';
    $oldlti = 'Python Lab 09: Cleaning with an audit trail';
    $oldquiz = 'Knowledge check: Lesson 9: Cleaning data';
    $pagename = 'Lesson 3.3: Data cleaning and audit records';
    $ltiname = 'Python Lab 3.3: Data cleaning and audit records';
    $quizname = 'Knowledge check: 3.3 Data cleaning and audit records';
    $pageintro = '<p>Preserve the source and separate detection, action, and count reconciliation to create reproducible analysis-ready data.</p>';
    $quizintro = '<p>Use short situations and code to check source preservation, conversion, missingness, normalisation, constraints, duplicates, audit records, and reconciliation. Retry as needed; the highest score is kept.</p>';
    $body = '<div class="python-sample-lesson"><h2>Define the problem before changing the data</h2>'
        . '<p>Lesson 3.2 selected the columns and rows needed for a question. Yet correct code can still produce a false conclusion when missing values, inconsistent labels, or impossible relationships are unexplained. Data cleaning is not cosmetic editing or deleting inconvenient rows. Preserve the source, define rules first, detect violations, choose actions, and record the result.</p>'
        . '<h3>Separate source, working, and analysis-ready data</h3><p>Keep <code>raw</code> as loaded. Begin processing with <code>clean = raw.copy()</code> and add flags that identify quality problems. Create <code>analysis</code> only from rows meeting the documented readiness rules. These three objects show what changed and what was not used.</p>'
        . v24_code("raw = pd.read_csv(data_file)\nclean = raw.copy()\nprint(\"Rows:\", len(raw))\nprint(raw.dtypes)\nprint(raw.isna().sum())")
        . '<h3>Profile types, missingness, categories, and ranges before correction</h3><p>Start with <code>dtypes</code>, <code>isna().sum()</code>, category <code>unique()</code> values, and numeric <code>describe()</code>. Those counts are the baseline. Replacing values first prevents you from distinguishing source problems from problems introduced by processing.</p>'
        . '<h3>Distinguish conversion failure from source missingness</h3><p><code>pd.to_numeric(..., errors="coerce")</code> converts invalid numeric text to missing. Compare the missing mask before and after conversion so an original blank and a newly failed conversion remain different issues.</p>'
        . v24_code("before_missing = clean[\"attended\"].isna()\nconverted = pd.to_numeric(clean[\"attended\"], errors=\"coerce\")\nconversion_failed = converted.isna() & ~before_missing\nclean[\"attended\"] = converted\nprint(\"Source missing:\", int(before_missing.sum()))\nprint(\"Conversion failures:\", int(conversion_failed.sum()))")
        . '<h3>Missing does not mean zero</h3><p>A blank attendance count means “not reported,” not necessarily “nobody attended.” Unsupported filling changes totals, averages, and rates. Flag missingness with <code>isna()</code>, then document whether the row is excluded from a calculation, quarantined for review, or corrected from an authoritative source.</p>'
        . '<h3>Normalise labels without discarding source text</h3><p>Whitespace and case differences can split one district into separate groups. Retain the source as <code>district_raw</code>, apply <code>str.strip()</code> and <code>str.title()</code> to the working value, and count affected rows. Do not merge similar-looking words without an agreed rule or mapping.</p>'
        . v24_code("clean[\"district_raw\"] = clean[\"district\"]\nclean[\"district\"] = clean[\"district\"].astype(\"string\").str.strip().str.title()\nchanged = clean[\"district_raw\"].astype(\"string\") != clean[\"district\"]\nprint(\"Changed labels:\", int(changed.sum()))")
        . '<h3>Test individual ranges and cross-field relationships separately</h3><p>Counts and costs have non-negative range rules. This table also defines <code>registered &gt;= attended &gt;= completed</code>. Give each rule a named Boolean mask and display its count. Keep missingness separate from invalidity instead of hiding all problems in one expression.</p>'
        . v24_code("missing_attended = clean[\"attended\"].isna()\ncompletion_over_attendance = (clean[\"completed\"].notna() & clean[\"attended\"].notna() & (clean[\"completed\"] > clean[\"attended\"]))\nnegative_cost = clean[\"material_cost\"].notna() & (clean[\"material_cost\"] < 0)\nprint(int(missing_attended.sum()), int(completion_over_attendance.sum()), int(negative_cost.sum()))")
        . '<h3>Define a business key before checking duplicates</h3><p>If one record means one centre, month, and course, those three columns form the business key. <code>duplicated(subset=business_key, keep=False)</code> displays every row in a duplicate group. Repeated centres in different months are legitimate, so the definition must precede the test.</p>'
        . v24_code("business_key = [\"centre_id\", \"month\", \"course\"]\nduplicate_key = clean.duplicated(subset=business_key, keep=False)\nprint(\"Duplicate-key rows:\", int(duplicate_key.sum()))")
        . '<h3>Separate detection from action</h3><p>Detection does not reveal the correct replacement. Correct from an authoritative source, normalise under an explicit rule, or mark a value missing or pending review when evidence is insufficient. Do not guess an impossible completion count. Retain rows and create an <code>analysis_ready</code> flag, then derive the analysis subset separately.</p>'
        . v24_code("clean[\"analysis_ready\"] = ~(missing_attended | completion_over_attendance | negative_cost | duplicate_key)\nanalysis = clean.loc[clean[\"analysis_ready\"]].copy()\nprint(\"Raw:\", len(raw), \"ready:\", len(analysis), \"flagged:\", int((~clean[\"analysis_ready\"]).sum()))")
        . '<h3>Make the workflow reproducible with an audit record and assertions</h3><p>Record the issue, detection rule, affected count, action, and unresolved count. A zero count documents that the check ran. Finally, assert that source rows equal analysis-ready plus flagged rows and that the analysis subset satisfies the same constraints.</p>'
        . v24_code("assert len(raw) == len(clean)\nassert len(clean) == int(clean[\"analysis_ready\"].sum()) + int((~clean[\"analysis_ready\"]).sum())\nassert not (analysis[\"completed\"] > analysis[\"attended\"]).any()")
        . '<h3>From worked example to transfer</h3><p>Use the practice CSV to detect missing attendance, district-label variation, and completion above attendance, recording each count. Then check missing completion, attendance above registration, non-positive training hours, negative material cost, and duplicate business keys. Create an audit table containing each rule, count, and proposed action without guessing corrections.</p>'
        . '<p>After this lesson, you can preserve a source, separate conversion failures from source missingness, detect label, range, cross-field, and business-key problems, separate actions from detection, reconcile counts, and explain an analysis-ready dataset through audit records.</p>'
        . '<p><strong>Estimated study time:</strong> about 3 hours</p><p style="display:none">PYAI-V24-LESSON33-FLOW</p></div>';
    $questions = [
        v24_question('L33R-01', '<p>Which first step best preserves auditability after loading a monthly CSV?</p>', [['Delete missing rows and save over the same variable', 'The baseline is lost.'], ['Keep raw unchanged and begin with clean = raw.copy()', 'Correct. Source and working data remain comparable.'], ['Fill all missing values before counting them', 'The source missing count is lost.'], ['Edit category labels manually in raw', 'The rule and affected count are not reproducible.']], 1, 'Separate source and working data so every change can be traced.'),
        v24_question('L33R-02', '<p>What does <code>failed</code> identify?</p>' . v24_code('before = s.isna()\nconverted = pd.to_numeric(s, errors="coerce")\nfailed = converted.isna() & ~before'), [['Values already blank in the source', 'That is before.'], ['Values converted successfully', 'Those are not missing after conversion.'], ['Values not previously missing that became missing during conversion', 'Correct. These are new conversion failures.'], ['Values converted to zero', 'coerce produces missing for invalid text.']], 2, 'Comparing before and after masks separates conversion failure from source missingness.'),
        v24_question('L33R-03', '<p>Why should blank attendance not be filled with zero without evidence?</p>', [['Zero means no attendance, which differs from not reported', 'Correct. They have different meanings.'], ['pandas cannot store zero', 'pandas stores zero normally.'], ['Every blank is text', 'It may be parsed as missing.'], ['Zero is always ignored by averages', 'Zero is normally included.']], 0, 'Changing missing to zero changes meaning and calculations.'),
        v24_question('L33R-04', '<p>What is the most auditable way to normalise <code>" central "</code> and <code>"Central"</code>?</p>', [['Overwrite the source and do not count changes', 'The change cannot be traced.'], ['Preserve raw text, apply documented strip and title rules, and count affected rows', 'Correct.'], ['Merge every similar-looking district automatically', 'Different categories may be combined incorrectly.'], ['Remove the district column', 'Useful grouping information is lost.']], 1, 'Retain the source value, explicit rule, and affected count.'),
        v24_question('L33R-05', '<p>Which row violates the defined relationship?</p>', [['registered=40, attended=34, completed=30', 'The relationship holds.'], ['registered=40, attended missing, completed=30', 'This is missing, but the relationship cannot yet be judged.'], ['registered=40, attended=34, completed=39', 'Correct. Completion exceeds attendance.'], ['registered=40, attended=40, completed=40', 'Equality is permitted.']], 2, 'Apply registered >= attended >= completed under the stated definition.'),
        v24_question('L33R-06', '<p>Which expression tests completion above attendance without mixing missingness into invalidity?</p>', [['completed > attended', 'Missingness is not made explicit.'], ['completed.notna() & attended.notna() & (completed > attended)', 'Correct. Only known values are compared.'], ['completed.isna() | attended.isna()', 'This tests missingness.'], ['completed and attended', 'Python and does not combine Series row by row.']], 1, 'Use separate missing and constraint-violation masks.'),
        v24_question('L33R-07', '<p>One record is defined as centre, month, and course. Which duplicate test matches that definition?</p>', [['df.duplicated(subset=["centre_id", "month", "course"], keep=False)', 'Correct. It displays every row sharing the business key.'], ['df.duplicated(subset=["centre_id"], keep=False)', 'Legitimate later months become duplicates.'], ['Run drop_duplicates immediately', 'Affected rows and rules are not inspected.'], ['Treat every row in the same month as duplicate', 'Many centres legitimately share a month.']], 0, 'Define the business key before detecting duplicates.'),
        v24_question('L33R-08', '<p>What is the best action immediately after detecting an impossible value?</p>', [['Guess a plausible replacement', 'The correction lacks evidence.'], ['Delete the row without recording a count', 'The impact is hidden.'], ['Flag it, seek an authoritative correction, otherwise record it as excluded or pending review', 'Correct. Detection and action remain separate.'], ['Reduce every column until the constraint passes', 'Other meanings are changed.']], 2, 'Detecting a problem does not reveal the correct replacement.'),
        v24_question('L33R-09', '<p>Which fields belong in a useful cleaning audit record?</p>', [['Issue, detection rule, affected count, action, and unresolved count', 'Correct.'], ['Only the final chart colour', 'It does not explain cleaning.'], ['Only the output filename', 'It omits what changed and why.'], ['Only checks with at least one failure', 'Zero-count checks also prove validation occurred.']], 0, 'Connect each rule to its impact, action, and remaining issue.'),
        v24_question('L33R-10', '<p>Source has 24 rows, analysis-ready has 22, and flagged has 2. Which is the direct reconciliation?</p>', [['Confirm 24 == 22 + 2', 'Correct. Every source row is accounted for.'], ['Confirm 22 > 24', 'The subset should not exceed the source.'], ['Delete the two flags and forget their count', 'The exclusion cannot be explained.'], ['Compare only the number of categories', 'That does not reconcile rows.']], 0, 'Reconcile source count with analysis-ready and flagged counts.'),
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

$page = v24_find_and_rename($course->id, 'page', $oldpage, $pagename);
$lti = v24_find_and_rename($course->id, 'lti', $oldlti, $ltiname);
$quiz = v24_find_and_rename($course->id, 'quiz', $oldquiz, $quizname);
$page->intro = $pageintro;
$page->introformat = FORMAT_HTML;
$page->content = $body;
$page->contentformat = FORMAT_HTML;
$page->timemodified = time();
$DB->update_record('page', $page);

$expectedpath = $language === 'ja' ? '/ja/09_cleaning_audit_trail.ipynb' : '/09_cleaning_audit_trail.ipynb';
$newurl = preg_replace('~/(?:ja/)?09_cleaning_audit_trail\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || ($newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath))) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $language === 'ja'
    ? '<p>元データを保持し、各品質規則の件数、処置、件数照合をNotebookへ記録します。</p>'
    : '<p>Preserve the source and record each quality-rule count, action, and reconciliation in the notebook.</p>';
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
    $saved = v24_save_question($category->id, $context->id, $shortname . ' v24: ', $question, $language);
    quiz_add_quiz_question($saved->id, $quiz, 0, 10);
}
$DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();

$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
$quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
$mainids = [(int)$pagecm->id, (int)$lticm->id, (int)$quizcm->id];
$extras = array_values(array_filter(
    array_map('intval', explode(',', (string)$delegated->sequence)),
    fn(int $cmid): bool => $cmid > 0 && !in_array($cmid, $mainids, true)
));
$delegated->sequence = implode(',', array_merge($mainids, $extras));
$DB->update_record('course_sections', $delegated);
foreach ($mainids as $cmid) {
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
    'preserved_extra_activities' => count($extras),
    'lti_path' => $expectedpath,
    'marker' => 'PYAI-V24-LESSON33-FLOW',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
