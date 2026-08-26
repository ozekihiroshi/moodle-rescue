<?php
// Rewrite Chapter 2.3 while preserving its existing LTI and assignment instances.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';

function v21_find_and_rename(int $courseid, string $table, string $oldname, string $newname): stdClass {
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

if ($ja) {
    $topicname = '2.3 実践プロジェクト：学習センター月次実績報告';
    $topicsummary = '<p>第2章のデータ構造、関数、検証、例外、テストを統合し、複数センターの月次実績を安全に集計する業務プログラムを完成させます。</p>';
    $oldlti = 'Python Labプロジェクト：学習センター月次実績報告';
    $oldassign = '基礎プロジェクト：学習センター月次実績報告';
    $ltiname = 'Python Lab 2.3：学習センター月次実績報告';
    $assignname = 'プロジェクト2.3：学習センター月次実績報告';
    $ltiintro = '<p>プロジェクトNotebookを開き、業務規則、関数の雛形、テストケース、提出前確認に沿って実装します。作業内容はPython Labへ保存してください。</p>';
    $assignintro = '<div class="python-project-brief"><h2>プロジェクト2.3：学習センター月次実績報告</h2>'
        . '<p>複数の学習センターから届く月次データを検証し、有効なデータだけからセンター別・全体の実績を計算します。無効なレコードは削除したり0へ置き換えたりせず、元のレコードと理由を別に報告します。</p>'
        . '<h3>入力データ</h3><p>少なくとも三つの有効なセンターを、辞書を要素とするリストで表します。一件には<code>name</code>、<code>district</code>、<code>registered</code>、<code>attended</code>、<code>completed</code>、<code>material_cost</code>を含めます。</p>'
        . '<h3>業務規則</h3><ul><li>必須キーの欠落は<code>KeyError</code>として扱う。</li><li>人数と教材費は0以上とする。</li><li><code>completed &lt;= attended &lt;= registered</code>を満たす。</li><li>登録者数0の率と修了者数0の一人当たり教材費は<code>None</code>とする。</li><li>出席率75%未満または修了率70%未満を「要支援」とする。</li></ul>'
        . '<h3>必須の関数</h3><ul><li><code>validate_centre(centre)</code></li><li><code>safe_percentage(part, whole)</code></li><li><code>safe_unit_cost(cost, completed)</code></li><li><code>centre_metrics(centre)</code></li><li><code>summarise_centres(centres)</code></li></ul>'
        . '<p>検証、計算、全体集計、表示を分けます。入力レコードを直接変更せず、計算結果は新しい辞書として返してください。</p>'
        . '<h3>報告に含める内容</h3><ul><li>センター別の人数、率、一人当たり教材費、要支援判定</li><li>有効データの登録・出席・修了合計と、合計値から計算した全体率</li><li>教材費合計、地区の集合、要支援センター名</li><li>無効レコード件数、元のレコード、除外理由</li></ul>'
        . '<p>センター別率の単純平均を全体率として使用してはいけません。</p>'
        . '<h3>最低限のテスト</h3><ul><li>正常な三件</li><li>登録者数0かつ各人数0</li><li>必須キー欠落</li><li>負数</li><li>出席者数が登録者数を超える値</li><li>修了者数が出席者数を超える値</li><li>75%と70%の直前・一致・直後</li></ul>'
        . '<h3>提出物</h3><ul><li>上から順に再実行できる完成済みNotebook（<code>.ipynb</code>）</li><li>必要に応じて同じ処理の<code>.py</code>ファイル</li><li>300～500字の説明：構造と関数の選択、無効データの扱い、重要な境界テスト、報告から読み取れる事実</li></ul>'
        . '<h3>評価基準（100点）</h3><table class="generaltable"><thead><tr><th>観点</th><th>点</th><th>確認内容</th></tr></thead><tbody>'
        . '<tr><td>データ構造と業務規則</td><td>20</td><td>六つの必須項目、適切なリスト・辞書・集合、規則の実装</td></tr>'
        . '<tr><td>関数への分割</td><td>25</td><td>責務、引数、戻り値、入力を直接変更しない設計</td></tr>'
        . '<tr><td>検証・例外・テスト</td><td>25</td><td>欠落・範囲・分母0・しきい値を確認し、原因を隠さない</td></tr>'
        . '<tr><td>月次報告</td><td>20</td><td>センター別、合計値からの全体率、要支援、無効理由を明示</td></tr>'
        . '<tr><td>再現性と説明</td><td>10</td><td>全セルを再実行でき、結果に基づいて簡潔に説明</td></tr></tbody></table>'
        . '<h3>提出前確認</h3><p>カーネルを再起動して全セルを上から実行し、エラーがないこと、<code>pass</code>やTODOが残っていないこと、同じ結果を再現できることを確認してから提出します。</p>'
        . '<p style="display:none">PYAI-V21-PROJECT23-BRIEF</p></div>';
} else {
    $topicname = '2.3 Applied project: Monthly centre performance report';
    $topicsummary = '<p>Integrate Chapter 2 data structures, functions, validation, exceptions, and tests into a small program that safely reports monthly results for several centres.</p>';
    $oldlti = 'Python Lab project: Monthly centre performance report';
    $oldassign = 'Foundation project: Monthly learning-centre performance report';
    $ltiname = 'Python Lab 2.3: Monthly centre performance report';
    $assignname = 'Project 2.3: Monthly learning-centre performance report';
    $ltiintro = '<p>Open the project Notebook and implement the business rules, function scaffolds, test cases, and pre-submission checks. Save the work in Python Lab.</p>';
    $assignintro = '<div class="python-project-brief"><h2>Project 2.3: Monthly learning-centre performance report</h2>'
        . '<p>Validate monthly data from several learning centres and calculate centre-level and overall results using valid data only. Do not delete invalid records or silently replace them with zero; report the original record and its reason separately.</p>'
        . '<h3>Input data</h3><p>Represent at least three valid centres as a list of dictionaries. Every record contains <code>name</code>, <code>district</code>, <code>registered</code>, <code>attended</code>, <code>completed</code>, and <code>material_cost</code>.</p>'
        . '<h3>Business rules</h3><ul><li>A missing required key raises <code>KeyError</code>.</li><li>Counts and material cost are non-negative.</li><li><code>completed &lt;= attended &lt;= registered</code>.</li><li>A zero registration rate and zero-completion unit cost are <code>None</code>.</li><li>Attendance below 75% or completion below 70% means “support required”.</li></ul>'
        . '<h3>Required functions</h3><ul><li><code>validate_centre(centre)</code></li><li><code>safe_percentage(part, whole)</code></li><li><code>safe_unit_cost(cost, completed)</code></li><li><code>centre_metrics(centre)</code></li><li><code>summarise_centres(centres)</code></li></ul>'
        . '<p>Separate validation, calculation, aggregation, and display. Do not mutate input records; return new dictionaries containing calculated results.</p>'
        . '<h3>Report contents</h3><ul><li>Centre counts, rates, material cost per completion, and support status</li><li>Valid-data totals and overall rates calculated from those totals</li><li>Total material cost, district set, and support-centre names</li><li>Invalid record count, original record, and exclusion reason</li></ul>'
        . '<p>Do not use an unweighted average of centre percentages as the overall rate.</p>'
        . '<h3>Minimum tests</h3><ul><li>Three normal records</li><li>Zero registration with all counts zero</li><li>A missing required key</li><li>A negative value</li><li>Attendance above registration</li><li>Completion above attendance</li><li>Values below, equal to, and above the 75% and 70% thresholds</li></ul>'
        . '<h3>Submit</h3><ul><li>A completed Notebook (<code>.ipynb</code>) that runs from top to bottom</li><li>An equivalent <code>.py</code> file if useful</li><li>A 150–250 word explanation covering structure and function choices, invalid-data handling, the most important boundary test, and one fact supported by the report</li></ul>'
        . '<h3>Assessment criteria (100 points)</h3><table class="generaltable"><thead><tr><th>Criterion</th><th>Points</th><th>Evidence</th></tr></thead><tbody>'
        . '<tr><td>Data structures and business rules</td><td>20</td><td>Six required fields, suitable lists, dictionaries and sets, implemented rules</td></tr>'
        . '<tr><td>Functional decomposition</td><td>25</td><td>Clear responsibilities, inputs and returns, no input mutation</td></tr>'
        . '<tr><td>Validation, exceptions, and tests</td><td>25</td><td>Missing, range, zero denominator and threshold cases; causes remain visible</td></tr>'
        . '<tr><td>Monthly report</td><td>20</td><td>Centre results, rates from totals, support list, and invalid reasons</td></tr>'
        . '<tr><td>Reproducibility and explanation</td><td>10</td><td>All cells rerun and the explanation is concise and evidence-based</td></tr></tbody></table>'
        . '<h3>Before submitting</h3><p>Restart the kernel and run all cells from the top. Confirm there are no errors, unfinished <code>pass</code> statements or TODOs, and that the same results are reproduced.</p>'
        . '<p style="display:none">PYAI-V21-PROJECT23-BRIEF</p></div>';
}

$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
$delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
course_update_section($course, $delegated, ['summary' => $topicsummary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);

$lti = v21_find_and_rename($course->id, 'lti', $oldlti, $ltiname);
$assign = v21_find_and_rename($course->id, 'assign', $oldassign, $assignname);
$expectedpath = $ja ? '/ja/P2_monthly_centre_report.ipynb' : '/P2_monthly_centre_report.ipynb';
$newurl = preg_replace('~/(?:ja/)?P2_monthly_centre_report\.ipynb$~', $expectedpath, $lti->toolurl);
if (!$newurl || ($newurl === $lti->toolurl && !str_ends_with($lti->toolurl, $expectedpath))) {
    throw new RuntimeException("Cannot update LTI path: {$lti->toolurl}");
}
$lti->toolurl = $newurl;
$lti->intro = $ltiintro;
$lti->introformat = FORMAT_HTML;
$lti->timemodified = time();
$DB->update_record('lti', $lti);

$assign->intro = $assignintro;
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
    'courseid' => (int)$course->id,
    'shortname' => $shortname,
    'topic' => $topicname,
    'ltiid' => (int)$lti->id,
    'assignid' => (int)$assign->id,
    'grade' => (float)$assign->grade,
    'lti_path' => $expectedpath,
    'marker' => 'PYAI-V21-PROJECT23-BRIEF',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
