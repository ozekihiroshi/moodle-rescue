#!/usr/bin/env python3
"""Add the shared mastery-check policy to the new Lesson 2.3 quiz."""

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
upgrade = ROOT / "scripts/upgrade-python-lesson23-v22.php"
text = upgrade.read_text(encoding="utf-8")

old = "require_once $CFG->dirroot . '/mod/lti/locallib.php';\n"
new = old + "require_once $CFG->libdir . '/gradelib.php';\n"
if new not in text:
    if text.count(old) != 1:
        raise RuntimeError("gradelib insertion point missing")
    text = text.replace(old, new)

anchor = "function v22_parent(stdClass $course, array $names): section_info {\n"
feedback = r'''function v22_feedback_bands(int $quizid, bool $ja): void {
    global $DB;
    $DB->delete_records('quiz_feedback', ['quizid' => $quizid]);
    $bands = $ja ? [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">&#127881;</span><h3>100%達成です！</h3><p>すべての考え方を確認できました。難しかった一問を自分の言葉で説明して定着させましょう。</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">&#127942;</span><h3>合格です。おめでとうございます！</h3><p>この理解度チェックは完了です。残りの解説も確認し、100%へ再挑戦できます。</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">&#128640;</span><h3>あと少しです。</h3><p>誤った項目をPython Labで確かめ、90%以上を目指して再挑戦しましょう。</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">&#127793;</span><h3>理解を積み上げています。</h3><p>解説から二つ選んでNotebookで実行し、もう一度確認しましょう。</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">&#128269;</span><h3>次に学ぶ場所が分かりました。</h3><p>これは罰点ではありません。パス、型、保存先を表示して確かめ、再挑戦しましょう。</p></div>'],
    ] : [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">&#127881;</span><h3>Mastered — 100%!</h3><p>You checked every idea. Explain one difficult answer in your own words to make it stick.</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">&#127942;</span><h3>Completed — congratulations!</h3><p>This learning check is complete. Review remaining feedback and try again for 100% if useful.</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">&#128640;</span><h3>You are close.</h3><p>Practise the missed item in Python Lab and try again; 90% completes the check.</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">&#127793;</span><h3>Keep building.</h3><p>Choose two explanations, run the related Notebook code, and check again.</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">&#128269;</span><h3>You found what to learn next.</h3><p>This is guidance, not a penalty. Display the path, type, and output, then retry.</p></div>'],
    ];
    foreach ($bands as [$min, $max, $html]) {
        $DB->insert_record('quiz_feedback', (object)['quizid' => $quizid, 'feedbacktext' => $html, 'feedbacktextformat' => FORMAT_HTML, 'mingrade' => $min, 'maxgrade' => $max]);
    }
}

'''
if feedback not in text:
    if text.count(anchor) != 1:
        raise RuntimeError("feedback insertion point missing")
    text = text.replace(anchor, feedback + anchor)

old_policy = "$quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);\n\n$actions ="
new_policy = r'''$quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
$gradeitem = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0]);
if (!$gradeitem) throw new RuntimeException('Lesson 2.3 quiz grade item missing');
$gradeitem->gradepass = 90;
$gradeitem->grademax = 100;
$gradeitem->update();
v22_feedback_bands($quiz->id, $ja);
$DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_AUTOMATIC, ['id' => $quizcm->id]);
$DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $quizcm->id]);
$DB->set_field('course_modules', 'completionpassgrade', 1, ['id' => $quizcm->id]);
$DB->set_field('course_modules', 'completionview', 0, ['id' => $quizcm->id]);

$actions ='''
if new_policy not in text:
    if text.count(old_policy) != 1:
        raise RuntimeError("quiz policy insertion point missing")
    text = text.replace(old_policy, new_policy)
upgrade.write_text(text, encoding="utf-8")

verify = ROOT / "scripts/verify-python-lesson23-v22.php"
check = verify.read_text(encoding="utf-8")
old_check = "    if ($slots !== 10 || abs((float)$quiz->sumgrades - 100.0) > 0.001 || (int)$quiz->attempts !== 0 || $quiz->grademethod != QUIZ_GRADEHIGHEST) throw new RuntimeException(\"$shortname quiz contract\");\n"
new_check = old_check + r'''    $gradeitem = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0]);
    if (!$gradeitem || abs((float)$gradeitem->gradepass - 90.0) > 0.001 || (int)$DB->count_records('quiz_feedback', ['quizid' => $quiz->id]) !== 5) throw new RuntimeException("$shortname mastery policy");
'''
if new_check not in check:
    if check.count(old_check) != 1:
        raise RuntimeError("verifier policy insertion point missing")
    check = check.replace(old_check, new_check)
verify.write_text(check, encoding="utf-8")
print(upgrade)
print(verify)
