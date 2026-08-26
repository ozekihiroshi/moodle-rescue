#!/usr/bin/env python3
"""Add Project 3A v5 public-boundary checks to the Moodle generator."""
from pathlib import Path


path = Path(__file__).resolve().parent / "build-python-chapter3-moodle-v35.py"
text = path.read_text(encoding="utf-8")

anchor = """    $l33->timemodified = time(); $DB->update_record('page', $l33);
}

$l34marker"""
addition = """    $l33->timemodified = time(); $DB->update_record('page', $l33);
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

$l34marker"""
if text.count(anchor) != 1:
    raise RuntimeError("Lesson 3.3 insertion anchor changed")
text = text.replace(anchor, addition)

old = "foreach (['PYAI-V35-L33-VERIFY-ROWS', 'issue_rules', 'records_to_verify'] as $token)"
new = "foreach (['PYAI-V35-L33-VERIFY-ROWS', 'PYAI-V35-L33-PROJECT-BOUNDARIES', 'copy(deep=True)', 'pd.to_numeric', 'keep=False', 'records_to_verify'] as $token)"
if text.count(old) != 1:
    raise RuntimeError("Lesson verifier token list changed")
text = text.replace(old, new)

old = "foreach (['meal_delivery_review.py', 'records_to_verify.csv', 'school_delivery_summary.csv', 'check_meal_delivery_review.py'] as $token)"
new = "foreach (['meal_delivery_review.py', 'records_to_verify.csv', 'school_delivery_summary.csv', 'check_meal_delivery_review.py', 'SOURCE RECORDS: 37', 'RECORDS TO VERIFY: 4', 'ANALYSIS RECORDS: 33', 'S004', 'pd.to_numeric', '0.0'] as $token)"
if text.count(old) != 1:
    raise RuntimeError("Project brief verifier token list changed")
text = text.replace(old, new)

with path.open("w", encoding="utf-8", newline="\n") as stream:
    stream.write(text)
print("Moodle generator aligned with Project 3A v5 boundaries.")
