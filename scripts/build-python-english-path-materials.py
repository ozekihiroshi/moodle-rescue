"""Export the canonical English copy and add the reviewed Japanese-edition navigation."""
import json
from pathlib import Path
import re
import runpy
import hashlib

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'sample-content/introduction-to-python/duallearning-path'
EXPORT = runpy.run_path(str(ROOT / 'scripts/export-python-path-markdown.py'))
REVIEWS = {
'1.1': ('Read, predict, check',
'Run and save the integrated practice in Python Lab. Before running them, predict the outputs of `print(2 + 3 * 4)` and `print("2 + 3 * 4")`.',
'The first prints `14`; the second prints `2 + 3 * 4`. Outside quotation marks the expression is evaluated; inside them it is text. Both use `print()` to produce output.',
'If unsure, revisit values, expressions and output, change one number and try again.'),
'1.2': ('Trace assignment and state',
'Predict `total` and `price` after these lines, then check in Python Lab.\n\n```python\nprice = 120\ntotal = price * 3\nprice = 150\n```',
'`total` is `360` and `price` is `150`. Assignment stores the result at that point. Changing `price` later does not automatically recalculate `total`.',
'If this is unclear, write the value of each variable after every line.'),
'1.3': ('Check types and conversions',
'Predict `"12" + "3"` and `int("12") + int("3")`. Then find the number of full boxes and remaining items when packing 13 items into boxes of 5.',
'The first result is the string `"123"`; the second is the integer `15`. `13 // 5` gives `2` full boxes and `13 % 5` gives `3` remaining items. `13 / 5` gives `2.6`, which answers a different question.',
'Revisit conversion and division if you cannot explain why these results differ.'),
'1.4': ('Connect input to output',
'An item costs 120 units of currency. The user enters `"3"` through `input()`. How can you display `TOTAL: 360`? Assume the input represents an integer.',
'```python\ncount = int(input("Count: "))\ntotal = count * 120\nprint(f"TOTAL: {total}")\n```\n\nInput is initially text. Convert it to an integer before calculating and use an f-string to display the result.',
'Try inputs 3 and 5, then save. Run cells that wait for input one at a time.'),
'1.5': ('Check boundaries and branch order',
'Trace this code for scores 79, 80 and 90. At 90, does it also print `REVIEW`?\n\n```python\nif score >= 90:\n    print("ON TRACK")\nelif score >= 80:\n    print("REVIEW")\nelse:\n    print("SUPPORT")\n```',
'79 prints `SUPPORT`, 80 prints `REVIEW`, and 90 prints only `ON TRACK`. An `if / elif / else` chain runs only the first branch whose condition is true.',
'Set `score = 79` before running the example. Predict what changes if the conditions are reordered.'),
'1.6': ('Trace each iteration',
'Write the value of `total` after each iteration. Explain which values `range(1, 4)` produces.\n\n```python\ntotal = 0\nfor number in range(1, 4):\n    total = total + number\nprint(total)\n```',
'`number` takes 1, 2 and 3. `total` changes from 0 to 1, 3 and 6, so the final output is `6`. The stop value 4 is excluded. Moving the initialization inside the loop would discard the accumulated total each time.',
'Check and save your work. Project 1.7 uses the same idea to update weekly totals after each day.'),
'2.1': ('Change a record deliberately',
'Why does changing a dictionary assigned to `found` also change the record in the original list? Revisit assignment, copying and searching in 2.1.',
'Both refer to the same dictionary; assignment has not created a new one. Changing the dictionary found by ID changes that record in the list. Check that the search result is not `None` before changing it.',
'Explain the difference between finding a record and making an independent copy.'),
'2.2': ('Distinguish return values from exceptions',
'Should a search that returns `None` for a missing record be handled like an update that raises `KeyError`? Revisit the function contracts and tests in 2.2.',
'Their contracts differ. After searching, check for `None` and choose what to do next. An update requires an existing target and raises the specified exception if none exists. Test missing targets and state changes as well as successful return values.',
'Compare the expected behaviour with the actual function contract before changing your code.'),
'2.3': ('Verify saved data',
'Does the existence of an output CSV prove that saving was correct? Consider a book title containing a comma and revisit writing and rereading CSV files.',
'Existence alone does not prove that columns and values are correct. Save to a separate file with `DictWriter`, reread with `DictReader`, and compare column names, record counts and values. Check that the title is still one value and that the original input was not changed.',
'Save, reread and compare before treating the task as complete.'),
}

def main():
    manifest = json.loads((OUT / 'source-manifest.json').read_text())
    sections = {int(s['number']):s for s in manifest['sections']}
    for chapter in range(7):
        pages = []
        for activity in manifest['activities']:
            if activity['module'] != 'page': continue
            section = sections[int(activity['section'])]
            if int(activity['section']) != chapter and not (section['name'] or '').startswith(f'{chapter}.'): continue
            source = activity['html']
            markdown = EXPORT['normalize_markdown_answers'](source) if activity['format'] == 4 else EXPORT['convert'](source)
            filename = f"page-{activity['id']}.md"
            path = OUT / filename
            if not path.exists(): path.write_text(markdown, encoding='utf-8')
            pages.append({'pagecmid':activity['id'],'name':activity['name'],'section':activity['section'],
                'visible':activity['visible'],'file':filename,'source_sha256':hashlib.sha256(source.encode()).hexdigest()})
        (OUT / f'chapter-{chapter}.json').write_text(json.dumps({'courseid':manifest['courseid'],'chapter':chapter,'pages':pages},indent=2)+'\n')
    for unit, (title, question, answer, retry) in REVIEWS.items():
        quoted = '\n'.join('> '+line if line else '>' for line in answer.splitlines())
        (OUT/f'review-{unit}.md').write_text(f'## Review: {title}\n\n{question}\n\n> [!ANSWER]\n{quoted}\n\n{retry}\n',encoding='utf-8')
    labroot = ROOT.parent / 'python-lab-rescue/course-materials'
    book = json.loads((labroot/'P1_weekly_support_report.ipynb').read_text())
    book['cells'] = [c for c in book['cells'] if c.get('id') != 'p1-submit-moodle-command']
    changed = False
    for cell in book['cells']:
        if cell.get('id') == 'p1-submit-moodle':
            cell['source'] = ['## Submit to this Markdown edition\n','\n',
                'Save with Ctrl+S. Run your program, inspect its output, and run the checker. '
                'Download `projects/weekly-support/weekly_support.py` from the file browser.\n',
                'Return to the **1.7 assignment in the Markdown edition you are studying**, upload the file and save. '
                'Check the submission status and attached filename. Do not submit the Notebook or checker.\n',
                'Do not use the direct-submission script: it may target the original course rather than this edition.\n']
            changed = True
        if cell['cell_type']=='code': cell['outputs']=[]; cell['execution_count']=None
    assert changed, 'Submission guidance cell not found'
    assert 'submit_weekly_support.py' not in json.dumps(book)
    book['metadata']['pyai']['variant']='duallearning-self-paced-en'
    for path in [OUT/'P1_weekly_support_report_dual_path.ipynb',labroot/'P1_weekly_support_report_dual_path.ipynb']:
        path.write_text(json.dumps(book,ensure_ascii=False,indent=1)+'\n',encoding='utf-8')
    print('Seven chapter manifests, nine reviews and the English submission guide prepared.')

if __name__=='__main__': main()
