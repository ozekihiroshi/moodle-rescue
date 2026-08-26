<?php
// Align Chapter 1.7 with the completed content of Lessons 1.1-1.6.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';

if ($language === 'ja') {
    $topicname = '1.7 実践プロジェクト：週間サポート報告';
    $topicsummary = '<p>1.1〜1.6の値、変数、計算、文字列、条件分岐、ループを統合し、検証可能な週間サポート報告を作成します。</p>';
    $ltiname = 'Python Labプロジェクト1.7：週間サポート報告';
    $assignname = 'プロジェクト1.7：学習センター週間サポート報告';
    $expectedpath = '/ja/P1_weekly_support_report.ipynb';
    $ltiintro = '<p>スターターNotebookを開き、入力の検証、週次集計、支援区分、報告、境界値テストを完成させます。</p>';
    $assignment = '<div class="python-project"><h2>Chapter 1 実践プロジェクト：週間サポート報告</h2>'
        . '<p>この課題は1.1〜1.6のまとめです。未習の関数、辞書、クラス、pandasを必要としません。値、変数、算術、文字列、条件分岐、ループだけで、別の担当者が利用できる報告を完成させます。</p>'
        . '<h3>作成するもの</h3><p>一つの学習センターについて4週間の出席者数を検証・集計し、月間出席率、重点支援週数、支援区分、推奨対応を表示するNotebookを作成します。</p>'
        . '<h3>入力データ</h3><pre><code>centre_name = "North Learning Centre"\nregistered_per_week = 40\nweekly_attendance = [28, 31, 30, 33]</code></pre>'
        . '<h3>業務ルール</h3><ol><li>登録者数が0以下、週次データが空、出席者数が負、または登録者数を超える場合は無効です。</li><li>有効な場合、一つの<code>for</code>文で合計出席者数、処理週数、出席率75%未満の週数を更新します。</li><li>月間出席率75%未満は「重点支援」、85%未満は「経過観察」、それ以外は「順調」です。</li><li>支援区分に合う短い推奨対応を表示します。</li><li>無効値では通常の支援区分や割合を表示しません。</li></ol>'
        . '<h3>初期データの自己確認</h3><p>延べ登録者数160、延べ出席者数122、出席率76.2%、重点支援週1、支援区分「経過観察」になります。コードをコピーする答えではなく、自分の処理結果を照合するための値です。</p>'
        . '<h3>必須テスト</h3><table class="generaltable"><thead><tr><th>登録者数</th><th>週次出席者数</th><th>期待値</th></tr></thead><tbody>'
        . '<tr><td>20</td><td><code>[15, 15, 15, 14]</code></td><td>73.8%、重点支援</td></tr>'
        . '<tr><td>20</td><td><code>[15, 15, 15, 15]</code></td><td>75.0%、経過観察</td></tr>'
        . '<tr><td>20</td><td><code>[17, 17, 17, 16]</code></td><td>83.8%、経過観察</td></tr>'
        . '<tr><td>20</td><td><code>[17, 17, 17, 17]</code></td><td>85.0%、順調</td></tr>'
        . '<tr><td>20</td><td><code>[18, 21, 17, 16]</code></td><td>無効</td></tr>'
        . '<tr><td>20</td><td><code>[]</code></td><td>データなし。割り算をしない</td></tr></tbody></table>'
        . '<h3>提出物</h3><ul><li>上から全セルを実行して保存した<code>.ipynb</code>ファイル</li><li>初期データの報告出力</li><li>6件のテストについて、入力・期待値・実際の結果</li><li>判定結果、重点支援週の意味、境界値テストで確認したことを説明する100〜150字程度の文章</li></ul>'
        . '<h3>採点基準（100点）</h3><table class="generaltable"><thead><tr><th>観点</th><th>点</th></tr></thead><tbody>'
        . '<tr><td>入力値と意味の分かる変数名</td><td>10</td></tr><tr><td>計算前の完全な妥当性確認</td><td>15</td></tr>'
        . '<tr><td>一つのループによる正しい状態更新と週次出力</td><td>20</td></tr><tr><td>延べ人数と出席率の計算</td><td>10</td></tr>'
        . '<tr><td>75%・85%の順序と境界を含む支援区分</td><td>15</td></tr><tr><td>読みやすい書式付き報告と推奨対応</td><td>10</td></tr>'
        . '<tr><td>75%・85%の境界テスト</td><td>5</td></tr><tr><td>無効値テスト</td><td>5</td></tr><tr><td>空データテスト</td><td>5</td></tr><tr><td>結果を根拠と結び付けた説明</td><td>5</td></tr></tbody></table>'
        . '<p><strong>注意：</strong>模範コードとの一致ではなく、業務ルールを満たし、実行結果で検証できることを評価します。</p><p style="display:none">PYAI-V18-PROJECT17</p></div>';
} else {
    $topicname = '1.7 Applied project: Weekly support report';
    $topicsummary = '<p>Integrate values, variables, arithmetic, strings, decisions, and loops from 1.1-1.6 into a tested weekly support report.</p>';
    $ltiname = 'Python Lab project 1.7: Weekly support report';
    $assignname = 'Project 1.7: Weekly learning-centre support report';
    $expectedpath = '/P1_weekly_support_report.ipynb';
    $ltiintro = '<p>Complete the starter Notebook: validate inputs, aggregate weeks, classify support, report results, and test boundaries.</p>';
    $assignment = '<div class="python-project"><h2>Chapter 1 applied project: Weekly support report</h2>'
        . '<p>This project completes Lessons 1.1-1.6. It does not require functions, dictionaries, classes, or pandas. Use values, variables, arithmetic, strings, decisions, and loops to produce a report another staff member can use.</p>'
        . '<h3>What to build</h3><p>For one learning centre, validate and aggregate four weekly attendance values, then display monthly attendance rate, priority-week count, support category, and a recommended action.</p>'
        . '<h3>Input data</h3><pre><code>centre_name = "North Learning Centre"\nregistered_per_week = 40\nweekly_attendance = [28, 31, 30, 33]</code></pre>'
        . '<h3>Operational rules</h3><ol><li>Data is invalid when registration is zero or negative, weekly data is empty, attendance is negative, or attendance exceeds registration.</li><li>For valid data, use one explicit <code>for</code> loop to update total attendance, weeks processed, and weeks below 75%.</li><li>A monthly rate below 75% is <code>priority support</code>; below 85% is <code>monitor</code>; otherwise it is <code>on track</code>.</li><li>Display one short action matching the category.</li><li>Do not display an ordinary rate or category for invalid data.</li></ol>'
        . '<h3>Self-check for the initial data</h3><p>The result is 160 total registrations, 122 total attendance, 76.2%, one priority week, and <code>monitor</code>. These values let you reconcile your output; they are not a model implementation to copy.</p>'
        . '<h3>Required tests</h3><table class="generaltable"><thead><tr><th>Registration</th><th>Weekly attendance</th><th>Expected</th></tr></thead><tbody>'
        . '<tr><td>20</td><td><code>[15, 15, 15, 14]</code></td><td>73.8%, priority support</td></tr>'
        . '<tr><td>20</td><td><code>[15, 15, 15, 15]</code></td><td>75.0%, monitor</td></tr>'
        . '<tr><td>20</td><td><code>[17, 17, 17, 16]</code></td><td>83.8%, monitor</td></tr>'
        . '<tr><td>20</td><td><code>[17, 17, 17, 17]</code></td><td>85.0%, on track</td></tr>'
        . '<tr><td>20</td><td><code>[18, 21, 17, 16]</code></td><td>invalid</td></tr>'
        . '<tr><td>20</td><td><code>[]</code></td><td>no data and no division</td></tr></tbody></table>'
        . '<h3>Submit</h3><ul><li>The saved <code>.ipynb</code> file after running all cells from the top</li><li>Report output for the initial data</li><li>Input, expected result, and actual result for all six tests</li><li>About 80-120 words explaining the category, the meaning of priority weeks, and what the boundary tests confirmed</li></ul>'
        . '<h3>Marking criteria (100 points)</h3><table class="generaltable"><thead><tr><th>Criterion</th><th>Points</th></tr></thead><tbody>'
        . '<tr><td>Clear inputs and meaningful names</td><td>10</td></tr><tr><td>Complete validation before calculation</td><td>15</td></tr>'
        . '<tr><td>Correct state updates and weekly output in one loop</td><td>20</td></tr><tr><td>Correct combined counts and rate</td><td>10</td></tr>'
        . '<tr><td>Correct ordered classification at 75% and 85%</td><td>15</td></tr><tr><td>Readable formatted report and recommendation</td><td>10</td></tr>'
        . '<tr><td>75% and 85% boundary tests</td><td>5</td></tr><tr><td>Invalid-value test</td><td>5</td></tr><tr><td>Empty-data test</td><td>5</td></tr><tr><td>Evidence-based explanation</td><td>5</td></tr></tbody></table>'
        . '<p><strong>Note:</strong> Assessment is based on satisfying and testing the operational rules, not matching one model program.</p><p style="display:none">PYAI-V18-PROJECT17</p></div>';
}

$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
$delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $delegated, ['name' => $topicname, 'summary' => $topicsummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
$newurl = preg_replace('~/(?:ja/)?P1_weekly_support_report\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || ($newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath))) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $ltiintro;
$lti->introformat = FORMAT_HTML;
$lti->timemodified = time();
$DB->update_record('lti', $lti);

$assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
$assign->intro = $assignment;
$assign->introformat = FORMAT_HTML;
$assign->grade = 100;
$assign->timemodified = time();
$DB->update_record('assign', $assign);

$lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
$assigncm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
$delegated->sequence = implode(',', [$lticm->id, $assigncm->id]);
$DB->update_record('course_sections', $delegated);
foreach ([$lticm->id, $assigncm->id] as $cmid) {
    $DB->set_field('course_modules', 'section', $delegated->id, ['id' => $cmid]);
}
rebuild_course_cache($course->id, true);

echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $shortname,
    'topic' => $topicname,
    'ltiid' => (int) $lti->id,
    'assignid' => (int) $assign->id,
    'grade' => (float) $assign->grade,
    'activities' => [$ltiname, $assignname],
    'lti_path' => $expectedpath,
    'marker' => 'PYAI-V18-PROJECT17',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
