#!/usr/bin/env python3
"""Generate the Moodle upgrade and verifier for Chapter 3 project readiness."""
from __future__ import annotations

import base64
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "sample-content/introduction-to-python"


def b64(path: Path) -> str:
    return base64.b64encode(path.read_bytes()).decode("ascii")


EN_BRIEF = b64(BASE / "project-3a-brief-en.md")
JA_BRIEF = b64(BASE / "project-3a-brief-ja.md")


UPGRADE = r'''<?php
// Close the Chapter 3 project-readiness gaps and add midterm choice A.
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

function v35_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>' . s($code) . '</code></pre>';
}
function v35_q(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}
function v35_save_question(int $categoryid, int $contextid, string $prefix, array $data, bool $ja): stdClass {
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
        'correctfeedback' => ['text' => $ja ? '<p>正解です。コードで同じ判定を確認してから進みましょう。</p>' : '<p>Correct. Confirm the same decision in code before continuing.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $ja ? '<p>Notebookで部分マスク、集計表、保存後の表を表示して再確認しましょう。</p>' : '<p>Display the partial mask, summary, or reloaded output in the Notebook and check again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions, 'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}
function v35_apply_quiz(stdClass $course, stdClass $quiz, array $questions, bool $ja, string $shortname, string $intro): void {
    global $DB;
    $attemptcount = (int)$DB->count_records('quiz_attempts', ['quiz' => $quiz->id]);
    if ($attemptcount > 0) throw new RuntimeException("Refusing to replace quiz questions: {$shortname} has {$attemptcount} saved attempts");
    $structure = \mod_quiz\structure::create_for_quiz(\mod_quiz\quiz_settings::create($quiz->id));
    foreach (array_reverse($structure->get_slots()) as $slot) $structure->remove_slot($slot->slot);
    $quiz->intro = $intro; $quiz->introformat = FORMAT_HTML; $quiz->attempts = 0;
    $quiz->grademethod = QUIZ_GRADEHIGHEST; $quiz->grade = 100; $quiz->questionsperpage = 10;
    $quiz->timemodified = time(); $DB->update_record('quiz', $quiz);
    $context = context_course::instance($course->id);
    $category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
    if (!$category) { $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC'); $category = reset($categories); }
    foreach ($questions as $data) {
        $saved = v35_save_question($category->id, $context->id, $shortname . ' v35: ', $data, $ja);
        quiz_add_quiz_question($saved->id, $quiz, 0, 10);
    }
    $DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    $gradeitem = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0]);
    if ($gradeitem) { $gradeitem->gradepass = 90; $gradeitem->grademax = 100; $gradeitem->update(); }
}
function v35_plugin_config(int $assignment, string $plugin, string $name, string $value): void {
    global $DB;
    $where = ['assignment' => $assignment, 'plugin' => $plugin, 'subtype' => 'assignsubmission', 'name' => $name];
    if ($record = $DB->get_record('assign_plugin_config', $where)) { $record->value = $value; $DB->update_record('assign_plugin_config', $record); }
    else $DB->insert_record('assign_plugin_config', (object)($where + ['value' => $value]));
}

function v35_l33_questions(bool $ja): array {
    if ($ja) return [
        v35_q('L33P-01','<p>元CSVを読み込んだ直後の最も監査可能な開始方法はどれですか。</p>',[['欠損行をrawから削除する','元の状態を失います。'],['rawを保持しclean = raw.copy()を作る','正解です。'],['すべて0で埋める','欠損の意味を変えます。'],['最終表だけ作る','変更過程が残りません。']],1,'元データと作業用データを分けます。'),
        v35_q('L33P-02','<p><code>converted.isna() & ~before_missing</code>が検出するものは何ですか。</p>',[['元から空だった値','before_missingです。'],['変換前は欠損でなく、数値変換後に欠損になった値','正解です。'],['負数だけ','別の条件です。'],['重複行','duplicatedで調べます。']],1,'元欠損と変換失敗を区別します。'),
        v35_q('L33P-03','<p>一行が欠損と重複の両方に該当します。確認対象表の理由として最も適切なのはどれですか。</p>',[['最初の欠損理由だけ','重複理由が失われます。'],['最後の重複理由だけ','欠損理由が失われます。'],['公開した順序で両方の理由を残す','正解です。'],['理由列を作らない','確認担当者が判断できません。']],2,'個別フラグを保ち、全該当理由を一定順で記録します。'),
        v35_q('L33P-04','<p>4個の品質フラグを持つ表で、有効行を表す式はどれですか。</p>',[['flags.any(axis=1)','問題が一つ以上ある行です。'],['~flags.any(axis=1)','正解です。すべてFalseの行です。'],['flags.all(axis=0)','列単位です。'],['flags.sum()','表全体の集計です。']],1,'行単位anyを反転して有効判定を作ります。'),
        v35_q('L33P-05','<p>確認対象行と識別列を同時に選ぶものはどれですか。</p>',[['clean.loc[~clean["analysis_ready"], verification_columns]','正解です。'],['clean[verification_columns]だけ','有効行も含みます。'],['clean.iloc[verification_columns]','列名は位置ではありません。'],['raw.dropna()','公開規則を反映しません。']],0,'locで行条件と列名を同時に指定します。'),
        v35_q('L33P-06','<p>業務キーが重なる全行を確認対象にする指定はどれですか。</p>',[['duplicated(key)','最初の行を残します。'],['duplicated(key, keep=False)','正解です。'],['drop_duplicates(key)','確認前に消します。'],['nunique(key)','重複行を示しません。']],1,'keep=Falseで重複グループ全体を示します。'),
        v35_q('L33P-07','<p>地区名の前後空白を除く処理について正しいものはどれですか。</p>',[['必ず無効行にする','明示した表記規則なら正規化できます。'],['元文字列を残し、規則と変更件数を記録する','正解です。'],['似た名前をすべて同一視する','意味が違う可能性があります。'],['元CSVを上書きする','原本を失います。']],1,'正規化と除外を分けます。'),
        v35_q('L33P-08','<p>確認対象4行、分析対象33行のとき元件数の直接照合はどれですか。</p>',[['37 == 4 + 33','正解です。'],['33 > 37','成立しません。'],['4 == 33','異なる集合です。'],['件数照合は不要','取りこぼしを検出できません。']],0,'すべての元行がどちらかに説明されることを確認します。'),
        v35_q('L33P-09','<p>品質問題を検出した直後の適切な処置はどれですか。</p>',[['推測値で直す','根拠がありません。'],['検出フラグと原値を残し、確認または除外規則を適用する','正解です。'],['行を無記録で削除する','影響が説明できません。'],['最大値へ置換する','業務根拠がありません。']],1,'検出と処置を分けます。'),
        v35_q('L33P-10','<p>行単位の確認対象表に必要な組合せはどれですか。</p>',[['問題件数だけ','対象行が分かりません。'],['識別列、元行との対応、理由','正解です。'],['グラフ色だけ','確認できません。'],['平均だけ','原記録へ戻れません。']],1,'確認担当者が原資料へ戻れる情報を残します。'),
    ];
    return [
        v35_q('L33P-01','<p>What is the most auditable start after loading a source CSV?</p>',[['Delete missing rows from raw','The baseline is lost.'],['Keep raw and create clean = raw.copy()','Correct.'],['Fill every blank with zero','Meaning changes.'],['Keep only the final table','The process is lost.']],1,'Separate source and working data.'),
        v35_q('L33P-02','<p>What does <code>converted.isna() & ~before_missing</code> detect?</p>',[['Original blanks','Those are before_missing.'],['Values newly missing after numeric conversion','Correct.'],['Only negative values','A different mask.'],['Duplicate rows','Use duplicated.']],1,'Distinguish conversion failure from source missingness.'),
        v35_q('L33P-03','<p>A row is both missing and duplicated. What should its verification reason contain?</p>',[['Only the first reason','Information is lost.'],['Only the last reason','Information is lost.'],['Both reasons in the published order','Correct.'],['No reason column','The reviewer lacks context.']],2,'Retain every matching reason deterministically.'),
        v35_q('L33P-04','<p>Which expression identifies valid rows from several flag columns?</p>',[['flags.any(axis=1)','That identifies a problem.'],['~flags.any(axis=1)','Correct.'],['flags.all(axis=0)','Wrong axis.'],['flags.sum()','A total, not a row mask.']],1,'Invert row-wise any.'),
        v35_q('L33P-05','<p>Which selects invalid rows and identifying columns together?</p>',[['clean.loc[~clean["analysis_ready"], verification_columns]','Correct.'],['clean[verification_columns]','Includes valid rows.'],['clean.iloc[verification_columns]','Names are not positions.'],['raw.dropna()','Does not apply all rules.']],0,'Use loc with row mask and column names.'),
        v35_q('L33P-06','<p>Which flags every row in a duplicate business-key group?</p>',[['duplicated(key)','Keeps the first unflagged.'],['duplicated(key, keep=False)','Correct.'],['drop_duplicates(key)','Deletes before review.'],['nunique(key)','Does not return rows.']],1,'Use keep=False.'),
        v35_q('L33P-07','<p>What is correct for documented district whitespace normalisation?</p>',[['Always invalidate the row','A documented format rule can normalise it.'],['Preserve raw text and record rule and changed count','Correct.'],['Merge every similar name','Meanings may differ.'],['Overwrite the source CSV','The baseline is lost.']],1,'Separate normalisation from exclusion.'),
        v35_q('L33P-08','<p>Four verification rows and 33 analysis rows reconcile to what source count?</p>',[['37','Correct.'],['29','Subtracting twice.'],['33','Omits flagged rows.'],['No count is needed','Loss cannot be detected.']],0,'Every source row must be accounted for.'),
        v35_q('L33P-09','<p>What should follow detection of an impossible value?</p>',[['Guess a replacement','No evidence.'],['Retain flag and source value, then apply review/exclusion rule','Correct.'],['Delete without count','Impact is hidden.'],['Replace with the maximum','No business basis.']],1,'Separate detection from action.'),
        v35_q('L33P-10','<p>What must a row-level verification report contain?</p>',[['Only issue totals','No source row is identified.'],['Identifiers, source correspondence, and reason','Correct.'],['Only chart colour','Not reviewable.'],['Only an average','Cannot return to the record.']],1,'Allow a reviewer to locate and understand each record.'),
    ];
}

function v35_l34_questions(bool $ja): array {
    if ($ja) return [
        v35_q('L34P-01','<p>学校別集計の一行が表すものは何ですか。</p>',[['元CSVの一行','複数日を集計します。'],['一校の有効記録の集計','正解です。'],['必ず一日','日をまとめます。'],['一つの列','行の粒度です。']],1,'groupbyキーが結果の粒度を決めます。'),
        v35_q('L34P-02','<p>ブール列<code>shortage</code>のTrue件数をグループ別に数える方法はどれですか。</p>',[['shortageのsum','正解です。Trueを1として合計します。'],['shortageのmeanだけ','割合です。'],['列を削除する','件数を失います。'],['文字列へ変換する','不要です。']],0,'条件をブール列にして合計します。'),
        v35_q('L34P-03','<p>全体提供率として適切な式はどれですか。</p>',[['各行提供率の単純平均だけ','行の規模を同じ重みで扱います。'],['提供数合計 ÷ 対象人数合計','正解です。'],['提供数合計 ÷ 行数','単位が違います。'],['最大率','全体ではありません。']],1,'分子と分母を同じ粒度で合計します。'),
        v35_q('L34P-04','<p>平均不足数降順、不足日数降順、ID昇順の指定はどれですか。</p>',[['ascending=True','全列昇順です。'],['ascending=[False, False, True]','正解です。'],['ascending=[True, True, False]','逆です。'],['ascendingは不要','既定では全列昇順です。']],1,'列ごとに方向を指定します。'),
        v35_q('L34P-05','<p>最後にID昇順を加える理由は何ですか。</p>',[['平均を変えるため','値は変えません。'],['同順位でも毎回同じ順序を得るため','正解です。'],['欠損を0にするため','無関係です。'],['列を減らすため','列は減りません。']],1,'安定した識別列で同順位を解決します。'),
        v35_q('L34P-06','<p>表示時に小数1桁へ丸める値で順位を決める時期はいつですか。</p>',[['先に丸めてから','近い別値が同値になります。'],['丸める前の値で並べ、後で表示用に丸める','正解です。'],['整数へ変換してから','精度を失います。'],['順序はランダムでよい','再現できません。']],1,'順位計算と表示形式を分けます。'),
        v35_q('L34P-07','<p>業務CSVへpandasのindexを余分な列として書かない指定はどれですか。</p>',[['index=True','indexを書きます。'],['index=False','正解です。'],['header=False','列名を消します。'],['mode="a"','追記指定です。']],1,'業務列だけを保存します。'),
        v35_q('L34P-08','<p>保存後に列順を直接確認する方法はどれですか。</p>',[['再読込してcolumnsを期待列と比較する','正解です。'],['ファイル名だけ見る','中身は分かりません。'],['保存前変数だけ見る','境界後を確認していません。'],['行数だけ見る','列は分かりません。']],0,'成果物を再読込してスキーマを照合します。'),
        v35_q('L34P-09','<p>保存した確認対象表で取りこぼしを検出する照合はどれですか。</p>',[['再読込行数と保存前対象行数を比較する','正解です。'],['平均だけ比較する','件数を保証しません。'],['先頭値だけ見る','全行を保証しません。'],['色を付ける','照合ではありません。']],0,'境界前後の件数を一致させます。'),
        v35_q('L34P-10','<p>確認対象CSVと順位CSVを分ける理由として適切なのはどれですか。</p>',[['同じ用途だから','用途が違います。'],['原記録確認と意思決定集計で一行の意味が違うから','正解です。'],['ファイル数を増やすため','目的ではありません。'],['pandasが一表しか扱えないから','複数表を扱えます。']],1,'成果物ごとの粒度と利用者を明確にします。'),
    ];
    return [
        v35_q('L34P-01','<p>What does one school-summary row represent?</p>',[['One source row','It aggregates days.'],['One school over its valid records','Correct.'],['Always one day','Days are grouped.'],['One column','This asks row grain.']],1,'Group keys define result grain.'),
        v35_q('L34P-02','<p>How can True values in Boolean <code>shortage</code> be counted by group?</p>',[['Sum shortage','Correct: True contributes one.'],['Only mean shortage','That gives a proportion.'],['Delete the column','The condition is lost.'],['Convert it to text','Unnecessary.']],0,'Create a Boolean condition and sum it.'),
        v35_q('L34P-03','<p>Which is the overall coverage rate?</p>',[['Only the mean of row rates','It weights rows equally.'],['Total served divided by total population','Correct.'],['Total served divided by row count','Wrong units.'],['Maximum rate','Not overall.']],1,'Aggregate compatible numerator and denominator.'),
        v35_q('L34P-04','<p>Which sorts average shortage descending, shortage days descending, and ID ascending?</p>',[['ascending=True','All ascending.'],['ascending=[False, False, True]','Correct.'],['ascending=[True, True, False]','Reversed.'],['No ascending argument','Defaults all ascending.']],1,'Specify one direction per key.'),
        v35_q('L34P-05','<p>Why add ascending ID as the final sort key?</p>',[['To change the average','Values do not change.'],['To make ties reproducible','Correct.'],['To fill missing values','Unrelated.'],['To remove columns','No.']],1,'Use a stable identifier as tie-breaker.'),
        v35_q('L34P-06','<p>When ranking values later shown to one decimal, what is correct?</p>',[['Round before sorting','Distinct values may become ties.'],['Sort unrounded values, then round for display','Correct.'],['Convert to integers first','Precision is lost.'],['Random tie order is fine','Not reproducible.']],1,'Separate decision precision from display precision.'),
        v35_q('L34P-07','<p>Which prevents the pandas index becoming an output CSV column?</p>',[['index=True','Writes it.'],['index=False','Correct.'],['header=False','Removes names.'],['mode="a"','Append mode.']],1,'Save only intended business columns.'),
        v35_q('L34P-08','<p>How do you directly validate saved column order?</p>',[['Re-read and compare columns with the contract','Correct.'],['Inspect only filename','No schema.'],['Inspect only the pre-save variable','Not the boundary.'],['Compare only row count','No columns.']],0,'Re-read the product and reconcile its schema.'),
        v35_q('L34P-09','<p>Which detects lost rows in a saved verification report?</p>',[['Compare reloaded and pre-save row counts','Correct.'],['Compare only averages','Does not guarantee rows.'],['Inspect only the first value','Does not cover all rows.'],['Change colour','Not validation.']],0,'Reconcile counts across the output boundary.'),
        v35_q('L34P-10','<p>Why use separate verification and priority CSV files?</p>',[['They have the same purpose','No.'],['Source review and decision summary have different grains and users','Correct.'],['Only to increase file count','No.'],['pandas supports only one table','False.']],1,'Make each product grain and purpose explicit.'),
    ];
}

$l33name = $ja ? 'レッスン3.3：データのクリーニングと監査記録' : 'Lesson 3.3: Data cleaning and audit records';
$l34name = $ja ? 'レッスン3.4：グループ化と要約統計' : 'Lesson 3.4: Grouping and summary statistics';
$l33quizname = $ja ? '理解度チェック：3.3 データのクリーニングと監査記録' : 'Knowledge check: 3.3 Data cleaning and audit records';
$l34quizname = $ja ? '理解度チェック：3.4 グループ化と要約統計' : 'Knowledge check: 3.4 Grouping and summary statistics';
$l33 = $DB->get_record('page', ['course' => $course->id, 'name' => $l33name], '*', MUST_EXIST);
$l34 = $DB->get_record('page', ['course' => $course->id, 'name' => $l34name], '*', MUST_EXIST);

$l33marker = 'PYAI-V35-L33-VERIFY-ROWS';
if (!str_contains($l33->content, $l33marker)) {
    $anchor = $ja ? '<h3>例題から応用へ</h3>' : '<h3>From worked example to transfer</h3>';
    $addition = $ja
        ? '<h3>個別フラグから、行単位の確認対象表を作る</h3><p>規則ごとの件数だけでは原資料のどの行を確認すべきか分かりません。個別フラグを残し、公開した順序ですべての該当理由を連結し、識別列とともに確認対象表へ取り出します。一行が複数規則へ違反した場合も理由を一つに絞りません。</p>' . v35_code("issue_rules = [(missing_attended, 'missing attended'), (duplicate_key, 'duplicate business key')]\nclean['issue'] = ''\nfor mask, label in issue_rules:\n    clean.loc[mask, 'issue'] += label + '; '\nrecords_to_verify = clean.loc[~clean['analysis_ready'], ['month','centre_id','course','issue']]")
        : '<h3>Build a row-level verification report from individual flags</h3><p>Rule counts do not identify the source rows a reviewer must check. Retain individual flags, append every matching reason in the published order, and select identifying columns into a verification table. Do not discard additional reasons when one row violates several rules.</p>' . v35_code("issue_rules = [(missing_attended, 'missing attended'), (duplicate_key, 'duplicate business key')]\nclean['issue'] = ''\nfor mask, label in issue_rules:\n    clean.loc[mask, 'issue'] += label + '; '\nrecords_to_verify = clean.loc[~clean['analysis_ready'], ['month','centre_id','course','issue']]");
    if (!str_contains($l33->content, $anchor)) throw new RuntimeException('Lesson 3.3 anchor missing');
    $l33->content = str_replace($anchor, $addition . $anchor, $l33->content) . '<p style="display:none">' . $l33marker . '</p>';
    $l33->timemodified = time(); $DB->update_record('page', $l33);
}
$l33v5marker = 'PYAI-V35-L33-PROJECT-BOUNDARIES';
if (!str_contains($l33->content, $l33v5marker)) {
    $anchor = $ja ? '<h3>例題から応用へ</h3>' : '<h3>From worked example to transfer</h3>';
    $addition = $ja
        ? '<h3>課題へ進む前の境界規則</h3><p>原本は<code>raw</code>として保持し、作業表は<code>raw.copy(deep=True)</code>で分けます。数値は<code>pd.to_numeric(..., errors="coerce")</code>で変換してから、欠損、負数、比較可能な項目間制約を個別に判定します。重複キーは文字列の前後空白を除いた後、<code>duplicated(..., keep=False)</code>でグループ全行を示します。</p>'
        : '<h3>Boundary rules before the project</h3><p>Keep the source as <code>raw</code> and create the working table with <code>raw.copy(deep=True)</code>. Convert numeric columns with <code>pd.to_numeric(..., errors="coerce")</code>, then evaluate missingness, negatives, and each comparable cross-field constraint separately. Strip business-key whitespace before using <code>duplicated(..., keep=False)</code> to flag every row in a duplicate group.</p>';
    if (!str_contains($l33->content, $anchor)) throw new RuntimeException('Lesson 3.3 v5 anchor missing');
    $l33->content = str_replace($anchor, $addition . $anchor, $l33->content) . '<p style="display:none">' . $l33v5marker . '</p>';
    $l33->timemodified = time(); $DB->update_record('page', $l33);
}

$l34marker = 'PYAI-V35-L34-PROJECT-BOUNDARY';
if (!str_contains($l34->content, $l34marker)) {
    $anchor = $ja ? '<h3>例題から応用へ</h3>' : '<h3>From worked example to transfer</h3>';
    $addition = $ja
        ? '<h3>条件付き件数を名前付き集計へ入れる</h3><p>まず明細へ条件のブール列を作り、グループ内でTrueを合計します。判定式と件数を別々に確認できます。</p>' . v35_code("operational = analysis.assign(low_completion=analysis['completion_rate'] < 75)\nsummary = operational.groupby('course').agg(low_records=('low_completion','sum'))") . '<h3>複数方向の並べ替えで順位を確定する</h3><p>主要指標を降順、次の指標も降順、最後のIDを昇順にします。最後の安定したキーが同順位を再現可能にし、丸める前の値で順位を決めます。</p>' . v35_code("ranked = summary.sort_values(['average','days','id'], ascending=[False,False,True])") . '<h3>二つのCSVを保存後に再読込する</h3><p>確認対象表と順位表を別々に保存し、再読込した列順、件数、先頭の優先対象を照合します。保存前の変数だけで成果物を検証したことにはなりません。</p>'
        : '<h3>Put a conditional count into named aggregation</h3><p>Create a row-level Boolean condition, then sum True values within each group. The decision and its count remain separately inspectable.</p>' . v35_code("operational = analysis.assign(low_completion=analysis['completion_rate'] < 75)\nsummary = operational.groupby('course').agg(low_records=('low_completion','sum'))") . '<h3>Make ranking deterministic with mixed sort directions</h3><p>Sort the primary and secondary measures descending, then a stable ID ascending. The final key resolves ties reproducibly; rank before rounding display values.</p>' . v35_code("ranked = summary.sort_values(['average','days','id'], ascending=[False,False,True])") . '<h3>Re-read two CSV products after saving</h3><p>Save a verification table and a priority summary separately, then re-read and reconcile column order, row counts, and the first priority. Inspecting only the pre-save variable does not validate the submitted product.</p>';
    if (!str_contains($l34->content, $anchor)) throw new RuntimeException('Lesson 3.4 anchor missing');
    $l34->content = str_replace($anchor, $addition . $anchor, $l34->content) . '<p style="display:none">' . $l34marker . '</p>';
    $l34->timemodified = time(); $DB->update_record('page', $l34);
}

$l33quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $l33quizname], '*', MUST_EXIST);
$l34quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $l34quizname], '*', MUST_EXIST);
v35_apply_quiz($course, $l33quiz, v35_l33_questions($ja), $ja, $shortname, $ja ? '<p>元データ保持から行単位の確認対象表まで、短いコードと状況で確認します。何度でも挑戦でき、最高点が記録されます。</p>' : '<p>Check source preservation through row-level verification reports using short code and situations. Retry as needed; the highest score is kept.</p>');
v35_apply_quiz($course, $l34quiz, v35_l34_questions($ja), $ja, $shortname, $ja ? '<p>条件付き件数、比率、決定的な順位、二つの保存成果物の再照合を確認します。何度でも挑戦でき、最高点が記録されます。</p>' : '<p>Check conditional counts, ratios, deterministic ranking, and reconciliation of two saved products. Retry as needed; the highest score is kept.</p>');

$projecttopic = $ja ? '3.5A 中間実践課題：学校給食の追加配送' : '3.5A Midterm practical project: School meal delivery';
$projectsummary = $ja ? '<p>第3章の選択課題Aです。要確認記録を分離し、有効な記録から明日の追加配送先を決めます。</p>' : '<p>Chapter 3 midterm choice A: separate records requiring verification and decide tomorrow\'s additional delivery from valid records.</p>';
$sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $projecttopic]);
if (!$sub) {
    $l34subname = $ja ? '3.4 グループ化と要約統計' : '3.4 Grouping and summary statistics';
    $l34sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $l34subname], '*', MUST_EXIST);
    $l34cm = get_coursemodule_from_instance('subsection', $l34sub->id, $course->id, false, MUST_EXIST);
    $parent = $DB->get_record('course_sections', ['id' => $l34cm->section], '*', MUST_EXIST);
    $created = add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'], MUST_EXIST),
        'modulename' => 'subsection', 'section' => $parent->section, 'name' => $projecttopic,
        'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0, 'completion' => 0,
    ], $course);
    $sub = $DB->get_record('subsection', ['id' => $created->instance], '*', MUST_EXIST);
}
$projectsection = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
course_update_section($course, $projectsection, ['name' => $projecttopic, 'summary' => $projectsummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$pagename = $ja ? '3.5A 課題仕様と完成条件' : '3.5A Project brief and completion criteria';
$ltiname = $ja ? 'Python Lab 3.5A：学校給食の追加配送' : 'Python Lab 3.5A: School meal delivery review';
$assignname = $ja ? '提出 3.5A：学校給食の追加配送' : 'Submit 3.5A: School meal delivery review';
$brief = base64_decode($ja ? '__JA_BRIEF__' : '__EN_BRIEF__');
$page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename]);
if (!$page) {
    $created = add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST), 'modulename' => 'page',
        'section' => $projectsection->section, 'name' => $pagename, 'intro' => $projectsummary, 'introformat' => FORMAT_HTML,
        'content' => $brief, 'contentformat' => FORMAT_MARKDOWN, 'display' => RESOURCELIB_DISPLAY_OPEN,
        'printintro' => 0, 'printlastmodified' => 0, 'visible' => 1, 'visibleoncoursepage' => 1,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
    $page = $DB->get_record('page', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $page->intro = $projectsummary; $page->introformat = FORMAT_HTML; $page->content = $brief;
    $page->contentformat = FORMAT_MARKDOWN; $page->timemodified = time(); $DB->update_record('page', $page);
}

$lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname]);
$prototype = $DB->get_record('lti', ['course' => $course->id, 'name' => $ja ? 'Python Lab 3.4：グループ化と要約統計' : 'Python Lab 3.4: Grouping and summary statistics'], '*', MUST_EXIST);
$ltipath = $ja ? '/ja/P3A_school_meal_delivery_review.ipynb' : '/P3A_school_meal_delivery_review.ipynb';
$toolurl = preg_replace('~/hub/user-redirect/lab/tree/.*$~', '/hub/user-redirect/lab/tree/' . ltrim($ltipath, '/'), $prototype->toolurl);
$ltiintro = $ja ? '<p>作業案内Notebookから、原資料確認用<code>inspect_school_meals.py</code>、次に本番用<code>meal_delivery_review.py</code>を完成させます。Notebook自体は提出しません。</p>' : '<p>Use the work-guide Notebook to complete <code>inspect_school_meals.py</code> and then <code>meal_delivery_review.py</code>. The Notebook itself is not submitted.</p>';
if (!$lti) {
    $created = add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST), 'modulename' => 'lti',
        'section' => $projectsection->section, 'name' => $ltiname, 'intro' => $ltiintro, 'introformat' => FORMAT_HTML,
        'typeid' => $prototype->typeid, 'toolurl' => $toolurl, 'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
        'instructorchoicesendname' => LTI_SETTING_NEVER, 'instructorchoicesendemailaddr' => LTI_SETTING_NEVER,
        'instructorchoiceacceptgrades' => LTI_SETTING_NEVER, 'grade' => 0, 'visible' => 1, 'visibleoncoursepage' => 1,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
    $lti = $DB->get_record('lti', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $lti->intro = $ltiintro; $lti->introformat = FORMAT_HTML; $lti->toolurl = $toolurl;
    $lti->timemodified = time(); $DB->update_record('lti', $lti);
}

$assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname]);
if (!$assign) {
    $created = add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST), 'modulename' => 'assign',
        'section' => $projectsection->section, 'name' => $assignname, 'intro' => $brief, 'introformat' => FORMAT_MARKDOWN,
        'alwaysshowdescription' => 1, 'submissiondrafts' => 0, 'requiresubmissionstatement' => 0,
        'sendnotifications' => 0, 'sendlatenotifications' => 0, 'sendstudentnotifications' => 1,
        'duedate' => 0, 'cutoffdate' => 0, 'gradingduedate' => 0, 'allowsubmissionsfromdate' => 0,
        'grade' => 100, 'attemptreopenmethod' => 'manual', 'maxattempts' => -1,
        'teamsubmission' => 0, 'requireallteammemberssubmit' => 0, 'blindmarking' => 0,
        'markingworkflow' => 0, 'markingallocation' => 0, 'assignsubmission_onlinetext_enabled' => 0,
        'assignsubmission_file_enabled' => 1, 'assignsubmission_file_maxfiles' => 2,
        'assignsubmission_file_maxsizebytes' => 0, 'assignfeedback_comments_enabled' => 1,
        'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
        'completion' => 0, 'showdescription' => 1,
    ], $course);
    $assign = $DB->get_record('assign', ['id' => $created->instance], '*', MUST_EXIST);
} else {
    $assign->intro = $brief; $assign->introformat = FORMAT_MARKDOWN; $assign->grade = 100;
    $assign->submissiondrafts = 0; $assign->timemodified = time(); $DB->update_record('assign', $assign);
}
v35_plugin_config($assign->id, 'file', 'enabled', '1');
v35_plugin_config($assign->id, 'file', 'maxfilesubmissions', '2');
v35_plugin_config($assign->id, 'file', 'allowedfiletypes', '.py');
v35_plugin_config($assign->id, 'onlinetext', 'enabled', '0');

$pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
$assigncm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
$projectsection->sequence = implode(',', [$pagecm->id, $lticm->id, $assigncm->id]);
$DB->update_record('course_sections', $projectsection);
foreach ([$pagecm->id, $lticm->id, $assigncm->id] as $cmid) $DB->set_field('course_modules', 'section', $projectsection->id, ['id' => $cmid]);
rebuild_course_cache($course->id, true);

echo json_encode([
    'courseid' => (int)$course->id, 'shortname' => $shortname,
    'lesson_markers' => [$l33marker, $l34marker], 'quiz_questions' => [10, 10],
    'project_topic' => $projecttopic, 'activities' => [$pagename, $ltiname, $assignname],
    'lti_path' => $ltipath, 'page_cmid' => (int)$pagecm->id, 'lti_cmid' => (int)$lticm->id,
    'assign_cmid' => (int)$assigncm->id,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
'''


VERIFY = r'''<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $l33name = $ja ? 'レッスン3.3：データのクリーニングと監査記録' : 'Lesson 3.3: Data cleaning and audit records';
    $l34name = $ja ? 'レッスン3.4：グループ化と要約統計' : 'Lesson 3.4: Grouping and summary statistics';
    $l33 = $DB->get_record('page', ['course' => $course->id, 'name' => $l33name], '*', MUST_EXIST);
    $l34 = $DB->get_record('page', ['course' => $course->id, 'name' => $l34name], '*', MUST_EXIST);
    foreach (['PYAI-V35-L33-VERIFY-ROWS', 'PYAI-V35-L33-PROJECT-BOUNDARIES', 'copy(deep=True)', 'pd.to_numeric', 'keep=False', 'records_to_verify'] as $token) if (!str_contains($l33->content, $token)) throw new RuntimeException("$shortname 3.3 missing $token");
    foreach (['PYAI-V35-L34-PROJECT-BOUNDARY', 'ascending=[False,False,True]', '再読込', 'Re-read'] as $token) {
        if (($ja && $token === 'Re-read') || (!$ja && $token === '再読込')) continue;
        if (!str_contains($l34->content, $token)) throw new RuntimeException("$shortname 3.4 missing $token");
    }
    $quiznames = $ja
        ? ['理解度チェック：3.3 データのクリーニングと監査記録', '理解度チェック：3.4 グループ化と要約統計']
        : ['Knowledge check: 3.3 Data cleaning and audit records', 'Knowledge check: 3.4 Grouping and summary statistics'];
    foreach ($quiznames as $name) {
        $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);
        if ((int)$DB->count_records('quiz_slots', ['quizid' => $quiz->id]) !== 10 || abs((float)$quiz->sumgrades - 100) > .001 || (int)$quiz->attempts !== 0) throw new RuntimeException("$shortname quiz $name");
    }
    $topic = $ja ? '3.5A 中間実践課題：学校給食の追加配送' : '3.5A Midterm practical project: School meal delivery';
    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topic], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $pagename = $ja ? '3.5A 課題仕様と完成条件' : '3.5A Project brief and completion criteria';
    $ltiname = $ja ? 'Python Lab 3.5A：学校給食の追加配送' : 'Python Lab 3.5A: School meal delivery review';
    $assignname = $ja ? '提出 3.5A：学校給食の追加配送' : 'Submit 3.5A: School meal delivery review';
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    foreach (['meal_delivery_review.py', 'records_to_verify.csv', 'school_delivery_summary.csv', 'check_meal_delivery_review.py', 'SOURCE RECORDS: 37', 'RECORDS TO VERIFY: 4', 'ANALYSIS RECORDS: 33', 'S004', 'pd.to_numeric', '0.0', 'inspect_school_meals.py', 'check_inspect_school_meals.py'] as $token) if (!str_contains($page->content, $token)) throw new RuntimeException("$shortname brief missing $token");
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $path = $ja ? '/ja/P3A_school_meal_delivery_review.ipynb' : '/P3A_school_meal_delivery_review.ipynb';
    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI path");
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
    $configs = [];
    foreach ($DB->get_records('assign_plugin_config', ['assignment' => $assign->id, 'subtype' => 'assignsubmission']) as $config) $configs[$config->plugin . ':' . $config->name] = $config->value;
    foreach (['file:enabled' => '1', 'file:maxfilesubmissions' => '2', 'file:allowedfiletypes' => '.py', 'onlinetext:enabled' => '0'] as $key => $value) if (($configs[$key] ?? null) !== $value) throw new RuntimeException("$shortname assign config $key");
    $modinfo = get_fast_modinfo($course);
    $names = [];
    foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $names[] = $modinfo->get_cm($cmid)->name;
    if ($names !== [$pagename, $ltiname, $assignname]) throw new RuntimeException("$shortname project order");
    $results[] = ['courseid' => (int)$course->id, 'shortname' => $shortname, 'lesson_quizzes' => [10, 10], 'topic' => $topic, 'activities' => $names, 'lti_path' => $path, 'assignment' => ['files' => 2, 'types' => ['.py'], 'online_text' => false]];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
'''


APPLY = '''#!/usr/bin/env bash
set -euo pipefail
root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="${PYAI_MOODLE_COMPOSE_FILE:-docker-compose.local.yml}"
cd "$root_dir"
for shortname in PYAI-INTRO PYAI-INTRO-JA; do
  docker compose -f "$compose_file" exec -T -e PYTHON_COURSE_SHORTNAME="$shortname" moodle \
    runuser -u www-data -- php < scripts/upgrade-python-chapter3-midterm-v35.php
done
docker compose -f "$compose_file" exec -T moodle runuser -u www-data -- php \
  < scripts/verify-python-chapter3-midterm-v35.php
'''


def write(path: Path, text: str) -> None:
    with path.open("w", encoding="utf-8", newline="\n") as stream:
        stream.write(text)


upgrade = UPGRADE.replace('__EN_BRIEF__', EN_BRIEF).replace('__JA_BRIEF__', JA_BRIEF)
write(ROOT / 'scripts/upgrade-python-chapter3-midterm-v35.php', upgrade)
write(ROOT / 'scripts/verify-python-chapter3-midterm-v35.php', VERIFY)
write(ROOT / 'scripts/apply-python-chapter3-midterm-v35.sh', APPLY)
print('Generated Chapter 3 Moodle upgrade, verifier, and apply wrapper.')
