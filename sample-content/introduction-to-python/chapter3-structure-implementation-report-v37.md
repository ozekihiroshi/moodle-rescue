# Chapter 3 textbook structure implementation report (v37)

## Scope and stopping point

This revision restructures Chapter 3 only. It does not rework Chapters 1 or 2, change the project contracts, alter data files, or replace working check programs. The chapter is now ready for human review before the same design principles are adapted to earlier chapters.

## Before and after

Before v37, each lesson contained the necessary explanations and examples, but many adjacent concepts appeared in one continuous flow. Headings often acted as visual separators rather than expressing the parent-child relationship among concepts. Lesson 3.3 and 3.4 also had eight and seven top-level learning groups respectively, which made the main route harder to scan.

After v37, the Moodle activity title is the page-level heading and every lesson uses this internal hierarchy:

1. Introduction
2. Learning outcomes
3. Four to seven numbered learning groups
4. Subordinate concept headings inside each group
5. Integration practice
6. Summary
7. Connection to the next lesson or project

The numbered group counts are now:

| Lesson | Before | After |
|---|---:|---:|
| 3.1 Tables, CSV, and pandas | 6 | 6 |
| 3.2 Selecting, filtering, and Boolean logic | 7 | 7 |
| 3.3 Cleaning and validating data | 8 | 7 |
| 3.4 Grouping and summary statistics | 7 | 6 |

The Japanese course uses the same conceptual hierarchy, with language appropriate to the Japanese adaptation rather than a separate curriculum.

## Implemented lesson headings

### 3.1 Tables, CSV, and pandas

- 3.1.1 Understand tabular data
- 3.1.2 Understand CSV as a storage format
- 3.1.3 Locate and read the source file
- 3.1.4 Inspect a DataFrame
- 3.1.5 Select basic rows and columns
- 3.1.6 Integration practice
- Summary
- Next: selecting records with evidence

### 3.2 Selecting, filtering, and Boolean logic

- 3.2.1 Start from a question
- 3.2.2 Select columns and rows
- 3.2.3 Build Boolean conditions
- 3.2.4 Combine conditions safely
- 3.2.5 Sort and compare records
- 3.2.6 Inspect the result before using it
- 3.2.7 Integration practice
- Summary
- Next: deciding which records are trustworthy

### 3.3 Cleaning and validating data

- 3.3.1 Preserve the source and inspect its condition
- 3.3.2 Convert values without hiding failures
- 3.3.3 Detect missing and invalid values
- 3.3.4 Check relationships between columns
- 3.3.5 Detect duplicated records
- 3.3.6 Create an auditable review table and revalidate
- 3.3.7 Integration practice
- Summary
- Next: aggregate only verified records

### 3.4 Grouping and summary statistics

- 3.4.1 Decide the grain of the result
- 3.4.2 Group and count records
- 3.4.3 Calculate totals, statistics, and rates
- 3.4.4 Build indicators for decisions and ranking
- 3.4.5 Reconcile, save, and re-read the result
- 3.4.6 Integration practice
- Summary
- Next: Chapter 3 intermediate practice

## Learning outcomes

### Lesson 3.1

Learners can explain what a row and column represent, distinguish a table from its CSV representation, read the supplied CSV with pandas, inspect shape/columns/types, and make basic selections without losing sight of the source data.

### Lesson 3.2

Learners can translate a practical question into selected columns and Boolean conditions, combine conditions with `&`, `|`, and `~`, apply parentheses correctly, sort a result, and check that a filtered table still answers the original question.

### Lesson 3.3

Learners can preserve raw data, convert numeric values while exposing failures, create explicit quality flags for missing/invalid/relational/duplicate problems, separate review records from analysis records, and retain an auditable result.

### Lesson 3.4

Learners can choose an appropriate result grain, distinguish `size`, `count`, and `nunique`, aggregate totals and averages, calculate rates with valid denominators, create conditional counts and ranked indicators, and reconcile saved summaries with source totals.

## Lesson-to-lesson handoff

| From | What the learner carries forward | To |
|---|---|---|
| 3.1 | A DataFrame whose rows, columns, source path, and basic types have been inspected | 3.2 asks precise questions of that table |
| 3.2 | A relevant subset produced with explicit Boolean conditions | 3.3 determines whether those records are trustworthy |
| 3.3 | Separate review and analysis records with visible reasons | 3.4 aggregates only the analysis-ready records |
| 3.4 | Reconciled school-level indicators and a defensible ranking | 3.5A applies the full workflow to a delivery decision |

## Required, supporting, and integration material

- **Required:** source inspection, DataFrame basics, selection, Boolean masks, numeric conversion, missing/invalid/duplicate checks, deep copy where needed, `groupby`/named aggregation, calculated indicators, sorting, reconciliation, CSV output, and re-reading.
- **Supporting:** `iloc`, De Morgan's law, comparisons among `size`/`count`/`nunique`, multi-key grain, and explanatory notes that prevent common mistakes.
- **Integration:** each lesson's final practice combines only concepts already introduced in that lesson and its prerequisites. It does not introduce a large new API at the end.

## Mapping from Lessons 3.1–3.4 to project 3.5A

| 3.5A requirement | Prepared in |
|---|---|
| Read the supplied CSV and inspect rows, columns, types, and missing values | 3.1 |
| Display all records and create a school/date ordered view | 3.1, 3.2 |
| Count original `district` values without silently normalising them | 3.1, 3.4 |
| Select relevant columns and records with compound conditions | 3.2 |
| Convert numeric columns with visible conversion failures | 3.3 |
| Flag missing, negative, impossible, and duplicated records | 3.2, 3.3 |
| Preserve source data and produce an auditable review table | 3.3 |
| Separate review records from analysis records | 3.3 |
| Group valid records by school and calculate totals, averages, rates, and conditional counts | 3.4 |
| Rank schools using multiple decision keys | 3.2, 3.4 |
| Reconcile counts/totals and save/re-read CSV outputs | 3.3, 3.4 |

The two-stage project remains unchanged: `inspect_school_meals.py` is the 20-point inspection task and `meal_delivery_review.py` is the 80-point processing task.

## Preservation and execution verification

- All existing Moodle code blocks were retained: 6 / 5 / 8 / 7 for Lessons 3.1–3.4 in both languages.
- All Notebook code cells were retained byte-for-byte by cell ID and source: 9 / 10 / 12 / 12 for Lessons 3.1–3.4 in both languages.
- All 43 distributed notebooks executed successfully in the Python Lab verification run.
- The 3.5A reference solution passed all five Stage 1 checks and all ten Stage 2 checks.
- Each Chapter 3 knowledge check still contains ten question slots in both languages.
- Existing Moodle page IDs, LTI activities, assignments, data files, function contracts, and automatic-check specifications were not changed.

## Moodle display verification

The revised Japanese Lesson 3.1 (CMID 193) and Lesson 3.4 (CMID 198) were rendered from the actual local Moodle pages in a browser engine and visually inspected. The following were confirmed:

- activity and internal heading hierarchy is visually distinct;
- numbered groups and subordinate headings are not conflated;
- tables and code blocks fit within the content column;
- no clipped text or horizontal overflow was found;
- the required/support/integration route note, summary, and next connection are visible;
- Lesson 3.4 proceeds in the intended order: grain, grouping, statistics/rates, decision indicators, reconciliation, integration.

Evidence images:

- `backups/visual-verification/chapter3-v37/lesson31-ja.png`
- `backups/visual-verification/chapter3-v37/lesson34-ja.png`

## Matters for later content review

These are intentionally not changed in this structure revision:

1. Decide whether the supplied `pathlib` path helper in 3.1 should remain infrastructure or become explicit required knowledge.
2. Reassess whether `iloc` and De Morgan's law should remain supporting content or move to an extension box.
3. Check whether the current estimated time for 3.3 is realistic for conversion, missing values, relational validation, duplicates, and audit output.
4. Consider unifying the examples in 3.4 more tightly around one decision question during the later content-quality pass.
5. Define the eventual completion policy when project choices 3.5B and 3.5C are added; only 3.5A is currently implemented.
6. Map all 40 Chapter 3 quiz questions explicitly to the revised learning outcomes in the later assessment audit. Slot counts and existing questions were preserved in this revision.
7. Decide whether Notebook introductions should repeat the fuller Moodle lesson bridge or remain concise operational companions.

## Files and Moodle pages changed

Primary source and generated assets:

- `scripts/build-python-chapter3-structure-v37.py`
- `scripts/upgrade-python-chapter3-structure-v37.php`
- `scripts/verify-python-chapter3-structure-v37.php`
- `scripts/apply-python-chapter3-structure-v37.sh`
- `scripts/verify-generated-python-chapter3-structure-v37.py`
- `scripts/verify-python-chapter3-content-preservation-v37.py`
- `scripts/export-python-chapter3-structure-v37.php`
- `sample-content/introduction-to-python/chapter3-structure-redesign-v37.md`
- `sample-content/introduction-to-python/course-lesson-structure-guide-v1.md`

Moodle page CMIDs:

- English: 47, 49, 50, 52
- Japanese: 193, 195, 196, 198

Python Lab local-volume updater:

- `D:/workspace/python-lab-rescue/scripts/update-local-chapter3-notebooks-v37.sh`

