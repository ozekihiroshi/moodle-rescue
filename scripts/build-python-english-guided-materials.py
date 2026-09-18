"""Generate English guided wrappers and language-specific copies of proven local tools."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'sample-content/introduction-to-python/duallearning-guided'
UNITS = {
 '1.1': ('Predict whether printing 3 + 4 and "3 + 4" gives the same output.', 'Distinguish values, expressions and output; compare predictions with both results.', 'Run the short integrated-practice program and explain what each line displays.'),
 '1.2': ('Predict what happens to the old value when a variable is assigned twice.', 'Trace assignments one line at a time, noting the current value before running.', 'Change one initial value and compare the final state with your prediction.'),
 '1.3': ('Predict the differences between 7 / 2, 7 // 2 and 7 % 2.', 'Compare numbers, strings, conversions and arithmetic; choose suitable types and operators.', 'Check types as well as values and explain when to use division or remainder.'),
 '1.4': ('Can a number entered through input() be added immediately?', 'Run the sequence from text input through conversion and calculation to formatted output.', 'Change the practice input and check both the calculated value and its format.'),
 '1.5': ('Predict which branch runs when both an if condition and an elif condition are true.', 'Trace values below, at and above a boundary, including and/or/not conditions.', 'Try inputs for each branch and explain why that branch is selected.'),
 '1.6': ('What changes if a total is reset to zero inside rather than before the loop?', 'Separate repeated work from one-time initialisation and inspect the accumulated value.', 'Change the repetition count or inputs and check the final iteration as well.'),
 '2.1': ('When recording several books, where do you need order and where do you need field names?', 'Trace a list of dictionary records before and after searching and changing one record.', 'Change one practice record and verify that other records remain unchanged.'),
 '2.2': ('How does displaying a value differ from returning it from a function?', 'Read inputs, return values and exceptions as a contract; try valid and invalid inputs.', 'Call the practice functions and check return values and exceptions separately.'),
 '2.3': ('How should CSV preserve a book title containing a comma?', 'Inspect the source, read with DictReader, save to a different file and read it back.', 'Check saved column names, record counts and values without modifying the original.'),
 '3.1': ('What does one row represent, and what does each column mean?', 'Display CSV as a DataFrame and inspect shape, column names and inferred types.', 'Use the practice table to explain the meaning of a row and identify numeric columns.'),
 '3.2': ('How do rows meeting both conditions differ from rows meeting either condition?', 'Display masks separately, then combine them with parentheses and Boolean operators.', 'Reconcile selected rows and counts and explain the filtering condition.'),
 '3.3': ('Does a blank mean zero? Should a suspected duplicate be deleted immediately?', 'Avoid guessed corrections; compare numeric conversion, quality flags and audit records with the source.', 'Distinguish the number of problematic rows from the number of problems when one row has several flags.'),
 '3.4': ('How does the meaning of a row change after aggregation?', 'Choose grouping keys first, calculate rates from totals, then check ranking and overall totals.', 'Reconcile the practice summary with its source and explain each numerator and denominator.'),
 '4.1': ('How could you bring dictionary data and the functions acting on it together?', 'Compare function-based and class-based versions and trace the state of two instances.', 'Create another instance and check that its state is independent.'),
 '4.2': ('Name a state that should be rejected when an object is created.', 'Separate initialisation checks from operation checks; verify state after an invalid operation.', 'Try successful and failing operations and explain when state changes.'),
 '4.3': ('Should an individual item or the lending desk search the entire inventory?', 'Separate an item’s responsibility from the manager’s and trace calls between objects.', 'Use several objects in the practice and explain which object handles each operation.'),
 '4.4': ('Does reading saved text alone restore the original object?', 'Distinguish storage format from live state, rebuild objects and test their operations.', 'Compare values and behaviour before and after saving, including invalid data handling.'),
 '5.1': ('Would you choose the same chart for category comparisons and changes over time?', 'Check the question and plotting table before choosing chart type, axes and units.', 'Reconcile plotted values with the table and explain the chart choice.'),
 '5.2': ('Must the group with the greatest total also have the greatest mean?', 'Compare totals, means and rates from the same data; examine denominators and axes.', 'Change the practice indicator and use the original values to explain a changed ranking.'),
 '5.3': ('Name a fact a chart can show and a possible cause it cannot establish.', 'Check numbers, scope and limitations; write only claims supported by the chart.', 'Compare the practice evidence statement with the chart and revise unsupported certainty.'),
 '6.1': ('What should you know before loading a large CSV into memory?', 'Inspect scale, required columns, types and a small trial before choosing a processing method.', 'Check the small practice input and identify what to verify before scaling up.'),
 '6.2': ('Does averaging chunk means always give the overall mean?', 'Track totals, counts and state carried across chunks, including boundary-spanning records.', 'Change chunk size and verify that the aggregate result stays the same.'),
 '6.3': ('If a program finishes without an error, is its result necessarily correct?', 'Reconcile counts, totals and reloaded output independently and record execution conditions.', 'Rerun the integrated practice and reproduce its result before starting the chapter project.'),
}

def main():
    OUT.mkdir(exist_ok=True)
    for unit, (prepare, classroom, review) in UNITS.items():
        (OUT / f'{unit}-prepare.md').write_text(f'''# {unit} Before class: Prepare and predict

You will work through the lesson with your teacher. You do not need to find the correct answer or finish the project before class.

{prepare}

Note a short prediction and check that this unit's Python Lab opens. If it does not, tell your teacher which step failed. After trying the check, mark this page complete and continue to the lesson. This records preparation, not a correct-answer grade.
''', encoding='utf-8')
        (OUT / f'{unit}-review.md').write_text(f'''# {unit} After class: Check independently

{review}

Try the lesson's integrated practice before opening its model answer. If you completed it in class, do not recreate it: change one value and check whether you can explain the result.

Save in Python Lab with Ctrl + S, then try the knowledge check. You may retry; your highest score is kept. Use feedback to return to the relevant lesson section.

If a question remains, post the unit number, code you tried, and actual output in this chapter's Questions and help forum. Do not include passwords or personal information. Read your teacher's reply and rerun the relevant steps. Marking this review complete is your own confirmation, not a teacher's assessment.
''', encoding='utf-8')
    (OUT / 'units.json').write_text(json.dumps({u: dict(zip(('prepare','classroom','review'),v)) for u,v in UNITS.items()}, indent=2)+'\n',encoding='utf-8')
    # Mechanical adaptation of the already-verified local copy/check tools.
    replacements = {
      'PYAI-INTRO-JA-DUAL-GUIDED':'PYAI-INTRO-EN-DUAL-GUIDED',
      'PYAI-INTRO-JA-DUAL-PATH':'PYAI-INTRO-EN-DUAL-PATH',
      'python-guided-full':'python-english-guided-full',
      '【作成中】Python入門 — 教師伴走・Markdown対応版':'[Draft] Python Foundations — Teacher-guided Markdown edition',
      'Python入門 — 教師伴走・Markdown対応版':'Python Foundations — Teacher-guided Markdown edition',
      '^レッスン':'^Lesson ',
      '<p>予習で予想し、授業で教師と実行結果を確かめ、復習で一人で再現する第0〜6章のPython教材です。章末課題はこのコースへ提出し、教師の評定・コメントを確認します。実際の授業日と締切は担当教師が設定します。</p>': '<p>Chapters 0–6: predict before class, investigate with your teacher, then reproduce independently. Submit chapter projects in this course and read teacher feedback on the assignment page. Your teacher sets class dates and deadlines.</p>',
    }
    for name in ('prepare-python-guided-course.php','check-python-guided-chapter.php','finalize-python-guided-course.php'):
        text = (ROOT/'scripts'/name).read_text(encoding='utf-8')
        for old,new in replacements.items(): text=text.replace(old,new)
        if name.startswith('prepare'):
            text=text.replace("$key = fn($cm) => $cm->sectionnum . '|' . $cm->modname . '|' . $cm->name;", "$key = fn($cm) => get_fast_modinfo($cm->course)->get_section_info($cm->sectionnum)->name . '|' . $cm->modname . '|' . $cm->name;")
        (ROOT/'scripts'/name.replace('python-guided','python-english-guided')).write_text(text,encoding='utf-8')
    print('23 English preparation/review pairs and local copy/check tools generated')

if __name__ == '__main__': main()
