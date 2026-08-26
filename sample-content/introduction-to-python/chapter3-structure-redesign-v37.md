# Chapter 3 lesson-structure redesign v37

## Scope

This revision changes the instructional structure of Chapter 3 without changing its datasets, project contracts, checker behaviour, Moodle activity IDs, or the meaning of verified programs. English remains the canonical version; Japanese is an adaptation with the same learning structure.

## Current Chapter 3 structure

| Lesson | Current numbered groups | Current ending | Related activities |
|---|---:|---|---|
| 3.1 Tabular data, CSV, and pandas | 6 | transfer exercise and next-lesson sentence are combined | page, Python Lab, 10-question check |
| 3.2 Selection, filtering, and Boolean logic | 7 | transfer exercise, summary, and transition are combined | page, Python Lab, 10-question check |
| 3.3 Data cleaning and audit records | 8 | transfer exercise and summary are combined; no separate transition | page, Python Lab, 10-question check |
| 3.4 Grouping and summary statistics | 7 | transfer exercise and summary are combined; no separate transition | page, Python Lab, 10-question check |
| 3.5A Midterm practical project | two program stages | complete public contract and submission path | brief page, Python Lab, assignment |

The current page and activity snapshots are stored in `structure-audits/chapter3-current-PYAI-INTRO.json` and `structure-audits/chapter3-current-PYAI-INTRO-JA.json`.

## Main structural problems

1. Introduction, outcomes, summary, and transition are not independent navigational units.
2. Numbered headings exist, but some subordinate concepts are attached to the wrong parent after incremental additions.
3. A learner cannot quickly distinguish the required route, supporting detail, and integrated practice.
4. The final transfer paragraph sometimes carries three functions at once: exercise, summary, and next-lesson transition.
5. Lesson 3.3 divides one auditable workflow into too many equal-level steps.
6. Lesson 3.4 currently places CSV re-reading beneath ranking and conditional counts beneath reconciliation, although those concepts belong elsewhere.
7. Notebook headings mostly mirror the numbered route but do not consistently provide outcomes, a summary, and an explicit transition.

## Chapter learning hand-off

| From | Product handed to the next lesson |
|---|---|
| 3.1 | a DataFrame whose rows, columns, types, missingness, and raw category values have been inspected |
| 3.2 | a reproducible row-and-column selection defined by named Boolean conditions and verified counts |
| 3.3 | preserved source data, a flagged working copy, analysis-ready records, and a verification trail |
| 3.4 | grouped indicators, deterministic priority order, reconciled and re-read CSV outputs |
| 3.5A | two working programs that inspect source data, flag quality problems, aggregate valid records, rank schools, and save evidence |

## Common lesson structure adopted for Chapter 3

1. Moodle activity title: `Lesson 3.x: name` / `レッスン3.x：名称`
2. Introduction
3. Learning outcomes (three to five observable capabilities)
4. Compact route note identifying required, supporting, and integrated parts
5. Four to seven numbered learning stages
6. Subheadings for operations, comparisons, cautions, and examples
7. Integrated practice using only previously introduced operations
8. Summary aligned with the outcomes
9. Short connection to the next lesson or project

The Moodle activity title supplies the page-level heading. Within the page, `h2` is used for introduction, outcomes, numbered stages, summary, and transition. `h3` is used only for concepts that belong to the preceding numbered stage.

## Proposed lesson headings

### 3.1 Tabular data, CSV, and pandas

- Introduction
- Learning outcomes
- 3.1.1 Understand rows, columns, cells, and schema
  - one observation per row and one variable per column
- 3.1.2 Understand CSV and loading assumptions
  - CSV as text exchange format
  - relative paths and the file actually loaded
  - explicit `read_csv` assumptions
- 3.1.3 Load and inspect a DataFrame
  - shape, columns, inferred types, and missingness
  - full-table display and raw category counts
  - Series and DataFrame
- 3.1.4 Calculate columns and save a table
  - derived columns without hiding the source
  - index and operational identifiers
- 3.1.5 Diagnose loading problems (supporting detail)
- 3.1.6 Integrated practice: convert records, save, re-read, and reconcile
- Summary
- Connection to 3.2

### 3.2 Selection, filtering, and Boolean logic

- Introduction
- Learning outcomes
- 3.2.1 Translate a question into displayed columns and row conditions
  - validate required columns
  - separate output fields from condition fields
- 3.2.2 Select rows and columns with labels and positions
- 3.2.3 Turn comparisons into Boolean masks
- 3.2.4 Combine conditions with Boolean logic
  - `&`, `|`, `~`, and parentheses
  - verify AND, OR, and NOT through counts
- 3.2.5 Express membership, ranges, and missingness
- 3.2.6 Filter, order, and verify the result
  - named masks
  - index alignment
- 3.2.7 Integrated practice: express and verify a new question
- Summary
- Connection to 3.3

### 3.3 Data cleaning and audit records

- Introduction
- Learning outcomes
- 3.3.1 Preserve the source and define quality problems
- 3.3.2 Separate source, working, and analysis-ready data
  - inspect types, missingness, categories, and ranges first
- 3.3.3 Handle conversion failures and missing values
  - distinguish source missingness from conversion failure
  - do not confuse missingness with zero
- 3.3.4 Normalise labels while retaining source values
- 3.3.5 Test ranges, cross-field constraints, and duplicates
  - independent named rules
  - business keys and complete duplicate groups
- 3.3.6 Build verification and audit evidence
  - analysis-ready flag and verification table
  - ordered issue reasons
  - audit counts, assertions, and boundary checks
- 3.3.7 Integrated practice: apply the quality workflow to another table
- Summary
- Connection to 3.4

### 3.4 Grouping and summary statistics

- Introduction
- Learning outcomes
- 3.4.1 Define the grain of detail and summary rows
- 3.4.2 Group, count, and aggregate
  - named `agg`
  - `size`, `count`, and `nunique`
  - conditional counts
- 3.4.3 Calculate totals, statistics, and rates
  - total, mean, median, range, and spread
  - aggregate numerator and denominator before a rate
  - preserve grain with multiple keys
- 3.4.4 Build indicators used for a decision
  - within-group proportions and the 100% check
  - deterministic mixed-direction ranking
- 3.4.5 Reconcile, save, and re-read the result
  - grouped totals against detail
  - purpose-specific CSV files
  - schema, row count, and first-row validation after re-reading
- 3.4.6 Integrated practice: build and validate another summary
- Summary
- Connection to 3.5A

## Required, supporting, and integrated content

| Lesson | Required route | Supporting detail | Integrated practice |
|---|---|---|---|
| 3.1 | 3.1.1–3.1.4 | 3.1.5 loading diagnosis | 3.1.6 |
| 3.2 | 3.2.1–3.2.6 | label/position and index-alignment cautions inside their parent stages | 3.2.7 |
| 3.3 | 3.3.1–3.3.6 | missing-versus-zero and boundary cautions inside their parent stages | 3.3.7 |
| 3.4 | 3.4.1–3.4.5 | statistical interpretation cautions inside 3.4.3 | 3.4.6 |

## Project 3.5A readiness map

| Project requirement | Prior lesson location |
|---|---|
| read CSV and show all rows | 3.1.2–3.1.3 |
| inspect shape, columns, dtypes, and missingness | 3.1.3 |
| count raw district labels | 3.1.3 |
| sort a copy without changing the source | 3.2.6 and 3.3.2 |
| convert numbers and distinguish missingness | 3.3.3 |
| preserve raw labels and normalise working labels | 3.3.4 |
| flag constraints and duplicate groups | 3.3.5 |
| build ordered issue text and verification data | 3.3.6 |
| aggregate and make conditional counts | 3.4.2 |
| compute rates from compatible totals | 3.4.3 |
| rank with mixed sort directions | 3.4.4 |
| reconcile, save, and re-read two outputs | 3.4.5 |

No Project 3.5A function contract or checker condition is introduced for the first time in the project brief.

## Files and Moodle pages to change

### Source and generated files

- `sample-content/introduction-to-python/python-lab/templates/07_tables_csv_pandas.ipynb`
- `sample-content/introduction-to-python/python-lab/templates/08_filtering_boolean_logic.ipynb`
- `sample-content/introduction-to-python/python-lab/templates/09_cleaning_audit_trail.ipynb`
- `sample-content/introduction-to-python/python-lab/templates/10_grouping_statistics.ipynb`
- the four corresponding files under `python-lab/templates/ja/`
- generated Python Lab course materials for those eight Notebooks
- a v37 Moodle page upgrade and verifier
- `course-lesson-structure-guide-v1.md`

### Moodle pages (IDs retained)

| Language | Lesson | CMID |
|---|---|---:|
| English | 3.1 | 47 |
| English | 3.2 | 49 |
| English | 3.3 | 50 |
| English | 3.4 | 52 |
| Japanese | 3.1 | 193 |
| Japanese | 3.2 | 195 |
| Japanese | 3.3 | 196 |
| Japanese | 3.4 | 198 |

The 3.5A brief, Python Lab links, assignments, quizzes, datasets, programs, and checkers are inspected but not rewritten unless a structural defect prevents navigation.
