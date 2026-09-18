<?php
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/assign/locallib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$chapter = (int)$argv[1];
if ($chapter < 0 || $chapter > 6) { throw new RuntimeException('Invalid chapter'); }
$root = $argv[2] ?? '/tmp/duallearning-guided';
$mapping = json_decode(file_get_contents($CFG->dataroot . '/temp/python-guided-full/mapping.json'), true, 512, JSON_THROW_ON_ERROR);
$course = $DB->get_record('course', ['id' => $mapping['courseid'], 'shortname' => 'PYAI-INTRO-JA-DUAL-GUIDED'], '*', MUST_EXIST);
$source = get_course($mapping['sourcecourse']);
$map = $mapping['map'];
$units = json_decode(file_get_contents($root . '/units.json'), true, 512, JSON_THROW_ON_ERROR);
\core\session\manager::set_user(get_admin());
function guided_add($course, $sectionnum, $key, $name, $module, $fields) {
    global $DB;
    $idnumber = 'python-guided-' . $key;
    $existing = $DB->get_record('course_modules', ['course' => $course->id, 'idnumber' => $idnumber]);
    if ($existing) { return $existing->id; }
    $result = add_moduleinfo((object)array_merge([
        'module' => $DB->get_field('modules', 'id', ['name' => $module], MUST_EXIST),
        'modulename' => $module, 'section' => $sectionnum, 'name' => $name,
        'intro' => '', 'introformat' => FORMAT_HTML, 'visible' => 1, 'visibleoncoursepage' => 1,
        'completion' => COMPLETION_TRACKING_MANUAL, 'groupmode' => 0, 'groupingid' => 0,
        'cmidnumber' => $idnumber,
    ], $fields), $course);
    return $result->coursemodule;
}
$chaptersection = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $chapter], '*', MUST_EXIST);
$forum = guided_add($course, $chapter, 'help-' . $chapter, '第' . $chapter . '章 質問と相談', 'forum', [
    'type' => 'general', 'intro' => '<p>単元番号、試したコード、表示された結果と期待した結果を書いてください。授業中は画面を教師に見せても構いません。教師からの返信を同じ話題で確認します。個人情報・パスワードは載せません。</p>',
    'completion' => COMPLETION_TRACKING_NONE, 'forcesubscribe' => 0, 'trackingtype' => 1,
    'assessed' => 0, 'scale' => 0, 'maxbytes' => 0, 'maxattachments' => 0,
]);
$helpurl = (new moodle_url('/mod/forum/view.php', ['id' => $forum]))->out(false);
$summary = '<h3>授業前 → 授業 → 授業後</h3><p>教師が指定した単元の「授業前」を開いて予想を残します。授業では本文とLabを使って予想を確かめ、授業後は復習・理解度チェックへ進みます。予定は教師の案内に従ってください。締切は各提出画面で確認します。</p>'
    . '<p><a href="' . $helpurl . '">この章の質問と相談</a>。授業中に進めなければ、表示を残して教師へ伝えます。Labのファイルは自学自習版と共有しますが、提出・評定はこのコースで独立しています。</p>';
if ($chapter === 0) {
    $summary = '<h3>教師伴走版の使い方</h3><p>まず全体地図とPython Labの使い方を確認します。授業の開始位置は教師が案内します。予習の予想が外れていても構いません。教師と実行結果を確かめ、授業後に一人で再現します。</p>' . $summary;
}
if ($chapter === 3) {
    $summary .= '<p>章末はA/B/Cから一つ選びます。選んだ課題を提出して共通確認を完了します。他の二つは追加練習であり、必須ではありません。</p>';
}
course_update_section($course, $chaptersection, ['summary' => $summary, 'summaryformat' => FORMAT_HTML, 'visible' => 1]);
$sourceinfo = get_fast_modinfo($source);
$cms = $sourceinfo->get_cms(); $sourceinfo->sort_cm_array($cms);
$report = [];
foreach ($cms as $old) {
    if (!$old->visible || $old->modname !== 'lessonmark') { continue; }
    $oldsection = $sourceinfo->get_section_info($old->sectionnum);
    if ($old->sectionnum != $chapter && !str_starts_with($oldsection->name ?? '', $chapter . '.')) { continue; }
    $cm = get_coursemodule_from_id('lessonmark', $map[$old->id], $course->id, false, MUST_EXIST);
    $section = $DB->get_record('course_sections', ['id' => $cm->section], '*', MUST_EXIST);
    $record = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
    // Start from the shared source each time, remapping only navigation links.
    $text = $DB->get_field('lessonmark', 'markdownsource', ['id' => $old->instance], MUST_EXIST);
    $text = preg_replace_callback('~(/mod/[a-z]+/(?:view|launch)\.php\?id=)(\d+)~',
        fn($m) => $m[1] . ($map[(int)$m[2]] ?? $m[2]), $text);
    if (preg_match('/^レッスン(\d+\.\d+)/u', $old->name, $match)) {
        $unit = $match[1]; $spec = $units[$unit];
        $peers = array_filter($cms, fn($p) => $p->sectionnum == $old->sectionnum && $p->visible);
        $labs = array_values(array_filter($peers, fn($p) => $p->modname === 'lti'));
        $quizzes = array_values(array_filter($peers, fn($p) => $p->modname === 'quiz'));
        if (count($labs) !== 1 || count($quizzes) !== 1) { throw new RuntimeException('Expected Lab and quiz: ' . $unit); }
        $lab = $map[$labs[0]->id]; $quiz = $map[$quizzes[0]->id];
        $links = "\n\n[授業の本文](/mod/lessonmark/view.php?id={$cm->id}) · [Python Lab](/mod/lti/view.php?id=$lab) · [理解度チェック](/mod/quiz/view.php?id=$quiz) · [質問と相談]($helpurl)\n";
        $prepare = guided_add($course, $section->section, $unit . '-prepare', $unit . ' 授業前：準備と予想', 'lessonmark',
            ['markdownsource' => file_get_contents($root . '/' . $unit . '-prepare.md') . $links]);
        $review = guided_add($course, $section->section, $unit . '-review', $unit . ' 授業後：復習と確認', 'lessonmark',
            ['markdownsource' => file_get_contents($root . '/' . $unit . '-review.md') . $links]);
        foreach ([$prepare => 'prepare', $review => 'review'] as $id => $kind) {
            $instance = $DB->get_field('course_modules', 'instance', ['id' => $id], MUST_EXIST);
            $DB->set_field('lessonmark', 'markdownsource', file_get_contents($root . '/' . $unit . '-' . $kind . '.md') . $links, ['id' => $instance]);
        }
        $text = preg_replace('/\n## この単元を終えるには\n.*\z/su', '', $text);
        $intro = "\n\n## 授業で確かめること\n\n" . $spec['classroom']
            . " 教師の説明を聞くだけでなく、実行前の予想と結果を比べましょう。\n\n[授業前の準備](/mod/lessonmark/view.php?id=$prepare) · [Python Lab](/mod/lti/view.php?id=$lab)\n";
        $pos = strpos($text, "\n");
        $text = substr($text, 0, $pos) . $intro . substr($text, $pos);
        $text .= "\n\n## 授業後へ\n\nLabで保存し、[この単元の復習](/mod/lessonmark/view.php?id=$review)へ進みます。理解度チェックはその後です。未解決の疑問は[質問と相談]($helpurl)で教師へ伝えてください。\n";
        \core_courseformat\formatactions::cm($course)->move_before($prepare, $cm->id);
        \core_courseformat\formatactions::cm($course)->move_before($review, $quiz);
        $report[] = ['unit' => $unit, 'prepare' => $prepare, 'lesson' => $cm->id, 'lab' => $lab, 'review' => $review, 'quiz' => $quiz];
    } else if (str_contains($old->name, '課題仕様')) {
        $intro = "\n\n## 授業での進め方\n\n最初に入力・成果物・完成条件を確認し、教師へ作業の見通しを説明します。次に自分で実装し、途中で動作結果を見せて相談します。最後に自動確認と保存を行い、このコースの提出先へ指定ファイルを送ります。教師の評定とコメントは提出画面で確認します。\n\n[質問と相談]($helpurl)\n";
        $pos = strpos($text, "\n"); $text = substr($text, 0, $pos) . $intro . substr($text, $pos);
    }
    $DB->update_record('lessonmark', (object)['id' => $record->id, 'markdownsource' => $text, 'timemodified' => time()]);
}
foreach (get_fast_modinfo($course)->get_instances_of('assign') as $cm) {
    if (!$cm->visible) { continue; }
    $assignment = new assign($cm->context, $cm, $course);
    $assignment->get_feedback_plugin_by_type('comments')->set_config('enabled', 1);
}
rebuild_course_cache($course->id, true);
echo json_encode(['courseid' => $course->id, 'chapter' => $chapter, 'forum' => $forum, 'units' => $report], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
