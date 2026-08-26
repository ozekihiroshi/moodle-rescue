# Python curriculum specification v2

Status: approved design baseline for the next canonical English release.

The canonical English course is the normative source. Language adaptations may
rewrite explanations and examples for their learners, but must preserve the
outcomes, required practice, assessment intent, and progression defined here.

The companion [concept and defect coverage audit](concept-coverage-v2.md) is a
normative design gate. Its concept IDs and coverage levels define the minimum
technical completeness behind this curriculum map.

## Purpose

This is a systematic first Python course with a practical data-analysis
destination. It must give a beginner a coherent map of Python, teach the common
programming foundation rather than isolated pandas recipes, and make the
boundary between this course and later study explicit.

Completeness means that every major part of beginner Python appears in the
learning map and that the required foundation is taught to a usable depth. It
does not mean cataloguing every language feature in one course.

## Audience and assumptions

- First-time programmers and learners returning after a long break.
- Self-study or teacher-supported group study.
- Shared learning-centre PCs are a primary use case.
- The supported environment is the Moodle-linked Python Lab and an online
  connection. No local Python installation is required.
- Notebook is the required working format. Console and `.py` script execution
  are demonstrated so learners understand the wider Python environment.

## Course outcome

After approximately 50 hours, a learner can:

1. explain how Python source, values, objects, and output relate;
2. use Python's principal scalar and collection data types appropriately;
3. construct programs with sequence, selection, repetition, and functions;
4. read and write text, CSV, and simple JSON files safely;
5. interpret errors and use exceptions, tests, modules, and libraries;
6. define and use a small class and explain attributes, methods, and instances;
7. load, inspect, clean, summarise, and visualise tabular data;
8. produce a reproducible Notebook and explain evidence in their own words;
9. identify the next learning path for algorithms, data, web systems,
   automation, or AI applications.

## Curriculum map

### Chapter 0 — Starting Python and Python Lab

- A computer follows explicit instructions; it does not infer the programmer's intention.
- The transferable execution model is input, processing, output, and storage.
- Source code is executed by the Python interpreter and changes program state in memory.
- Syntax, runtime behaviour, and meaning are three different questions.
- What Python is and where it is used.
- Why Python literacy remains useful when AI assistance is available.
- The complete learning map and the scope of this course.
- Notebook, Console, and `.py` script execution.
- Open, run, edit, rerun, save, close, and reopen a Notebook.
- Kernel state, execution order, output, and the first error message.
- Shared-PC boundaries and the learner's persistent workspace.

Required product: a saved Notebook containing executed Markdown and code cells.

### Chapter 1 — Programming foundations and scalar values

#### 1.1 Programs, values, expressions, and output

- Instructions normally execute from top to bottom.
- Literal values, simple `+`, `-`, and `*` expressions, parentheses, and `print()`.
- Every value has a kind that determines which operations make sense. The complete type reference is deliberately deferred.
- Notebook display versus explicit program output.

#### 1.2 Variables, assignment, and program state

- Names, assignment, reassignment, and reading the current value of a name.
- Program state in memory and Notebook kernel state.
- Tracing a short program line by line without yet introducing input.

#### 1.3 Basic scalar types, conversion, and arithmetic

- Integers, floating-point numbers, Boolean values, strings, and `None`; recognise complex numbers without requiring their use.
- `type()`, `isinstance()`, deliberate conversion, and operations permitted by each scalar type.
- Arithmetic operators: `+`, `-`, `*`, `/`, `//`, `%`, and `**`.
- Precedence, parentheses, floating-point limitations, comparison, and Boolean operators.

#### 1.4 Strings, input, and formatted output

- Strings as sequences of characters, indexing, length, essential methods, concatenation, and repetition.
- `input()` returns text; validate and convert before calculation.
- Formatted strings and clear labelled output.

The word *type* appears as a preview in 1.1, as state attached to a value in 1.2, and as the explicit subject of 1.3. It is not taught as two competing introductions.

Required product: a small calculation and formatted report with validated
sample inputs.

## Type progression across the course

Data types are a course-wide spine, not a single vocabulary lesson:

- Chapter 1: scalar values (`int`, `float`, `bool`, `str`, and `None`);
- Chapter 3: collections (`list`, `tuple`, `dict`, `set`, and `range`);
- Chapter 5: file handles and values represented in CSV and JSON;
- Chapter 6: user-defined classes and their instances;
- Chapter 7: pandas `Series`, `DataFrame`, and column `dtype` values.

Each introduction answers three questions: what the value represents, which operations it supports, and why it is suitable for the work at hand.

### Chapter 2 — Program structure

- Sequence, selection, and repetition.
- Indentation and code blocks.
- `if`, `elif`, and `else`; compound and boundary conditions.
- `for`, `while`, `range()`, accumulators, `break`, and `continue`.
- Tracing execution and turning a written procedure into steps.

Required product: a program that classifies and summarises several records.

### Chapter 3 — Collections and records

- Lists, tuples, dictionaries, and sets.
- Indexing, slicing, membership, iteration, and common methods.
- Adding, changing, removing, searching, and deduplicating values.
- Nested records and choosing an appropriate collection.
- Basic aggregation and linear search as algorithmic patterns.

Required product: an in-memory collection of learning-centre records and a
summary generated without pandas.

### Chapter 4 — Functions and reliable decomposition

- Defining and calling functions.
- Parameters, arguments, return values, default values, and scope.
- Pure calculations and functions that perform input/output.
- Decomposition, naming, docstrings, and small tests.
- Assertions, test cases, normal cases, boundaries, and invalid inputs.

Required product: a previously written program reorganised into tested
functions.

### Chapter 5 — Files, exceptions, modules, and libraries

- Files, folders, relative paths, absolute paths, and the current directory.
- `with open(...)`, encodings, text input/output, CSV, and simple JSON.
- Exceptions and `try`, `except`, `else`, and `finally`.
- Reading a traceback and deciding what to check first.
- Modules, `import`, the standard library, packages, external libraries, and
  the role of `pip` and the managed Python Lab image.
- Safe, path-independent access to course datasets.

Required product: a file-processing program that reports malformed input
without losing valid results.

### Chapter 6 — Classes and objects

- Objects, types, identity, attributes, and methods.
- Defining a class, `__init__`, `self`, instances, and instance methods.
- Representing one small domain record as an object.
- Choosing between a dictionary, a simple class, and a table.
- Recognising strings, lists, file handles, and pandas DataFrames as objects.

Inheritance, abstract base classes, descriptors, metaclasses, and design
patterns are outside this course.

Required product: a small class with two behaviours and tested instances.

### Chapter 7 — Data analysis foundations

- Tables, Series, DataFrames, and CSV loading.
- Inspection, selection, Boolean filtering, sorting, and type conversion.
- Missing, invalid, and duplicate data with an explicit audit trail.
- Grouping, counts, totals, mean, median, minimum, maximum, and proportions.
- Appropriate charts, titles, labels, scales, and evidence statements.
- Validation on a small dataset before processing a larger file.

Required product: a guided analysis of fictional learning-centre operations
data.

### Chapter 8 — From question to evidence

- Define a specific operational question.
- Identify fields and validation rules.
- Load, inspect, clean, and document data.
- Analyse, visualise, and cross-check results.
- State findings, limitations, and recommended next checks.
- Submit a reproducible Notebook and concise report.

The project scenario is the work itself, not a fictional character: analyse
monthly learning-centre operations data and produce decision-sized evidence.

## Algorithmic thinking in this course

Algorithmic thinking is woven through Chapters 2–5: decomposition, tracing,
counting, aggregation, linear search, deduplication, validation, and choosing a
finite procedure. A later algorithms course develops binary search, sorting,
recursion, stacks, queues, trees, graphs, complexity, and performance analysis.

## Standard lesson contract

Every required lesson contains, in this order:

1. the capability the learner will gain;
2. where that capability is used in the final project;
3. prerequisite recall;
4. concise explanation and a complete reference table where applicable;
5. a guided Python Lab example that the learner runs;
6. explanation of the output and state changes;
7. common errors and a recovery procedure;
8. an equivalent transfer exercise without the worked answer beside it;
9. a retryable mastery check covering only taught material;
10. a short connection to the next lesson and project.

A check must never introduce syntax, terminology, or behaviour that the lesson
has not taught or explicitly linked to a prerequisite reference.

## AI assistance policy

AI assistance is permitted. The course guide states once that learners must
run, check, modify, and explain assisted work. Assessment checks Python and
data reasoning, not the learner's ability to classify acceptable and
unacceptable AI use. Repeated AI-policy reminders, AI-use declarations, and
AI-policy quiz questions are not part of the required learning path.

## Narrative and scenario policy

- Do not use a recurring fictional character as the organising device.
- State the work context, input, required output, and reason directly.
- Reuse the learning-centre dataset so skills accumulate, but vary transfer
  exercises so learners demonstrate generalisation.
- Every scenario paragraph must help the learner understand the task or data;
  otherwise remove it.

## Required reference material

Reference pages must cover at least:

- execution modes and Notebook operation;
- built-in types and conversions;
- operators and precedence;
- strings and collections;
- control-flow syntax;
- common built-in functions;
- file modes and path diagnosis;
- common exceptions and traceback reading;
- imports, standard library, packages, and managed dependencies;
- class terminology;
- pandas selection, cleaning, grouping, and chart choice.

## Out of scope and next pathways

The final section presents five explicit continuations:

- Programming strength: algorithms, data structures, complexity, and testing.
- Data: statistics, SQL, visualisation, and machine learning.
- Web systems: HTTP, HTML/CSS, databases, APIs, Django, or FastAPI.
- Automation: files, spreadsheets, APIs, scheduling, and operations.
- AI applications: data preparation, model APIs, evaluation, and application
  design.

Web frameworks, databases, machine learning, advanced object-oriented design,
asynchronous programming, packaging for publication, and production deployment
are not required outcomes of this course.

## Release rule

Version 2 must not replace the version 1 distribution until:

- every Master concept ID in `concept-coverage-v2.md` maps to teaching,
  demonstration, practice, defect diagnosis, tests, assessment, and teacher
  support;
- no lesson, notebook, quiz, or project requires an unmapped or not-yet-taught
  concept;
- every required outcome maps to teaching, guided practice, and assessment;
- every assessment item maps back to taught material;
- a first-time learner walkthrough completes without undocumented setup;
- teacher-only answers remain hidden;
- English and Japanese catalogues pass structural and adaptation checks;
- a user-free `.mbz` is restored and smoke-tested in a disposable course.
