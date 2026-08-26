# Curriculum v1 to v2 content audit

This is the working migration map from canonical release 1.0.0 to the approved
v2 curriculum. The v1 English and Japanese `.mbz` files remain immutable
release artifacts.

## High-level decision

| Existing area | Decision | Reason |
|---|---|---|
| Course guide | Rewrite | Must introduce the full Python map, course boundary, environment, and learning method. |
| Responsible-AI page | Merge and reduce | Keep one short permission and verification statement in the guide. |
| Naledi story page | Remove | The character does not clarify the work or a Python concept. |
| Lessons 1–6 | Retain code, substantially rewrite teaching | Core material is useful but prerequisite explanation and systematic references are incomplete. |
| Lessons 7–12 | Retain and reorganise | The analysis progression remains the practical destination. |
| Learning checks | Re-map question by question | Keep mastery mechanics; remove AI-policy questions and anything not taught first. |
| Python Lab deep links | Retain, rename, and add | Existing persistence model is sound; orientation, files, and classes need new notebooks. |
| Projects | Retain skills, rewrite scenarios | State the task directly and remove character-dependent wording. |
| Teacher material | Rewrite after learner path | Answers and grading must reflect the final learner-facing version. |
| AI-use declaration | Remove from required submissions | It is not a Python or data-analysis outcome. |

## Coverage gaps that require new teaching

| Required subject | Current state | v2 action |
|---|---|---|
| Python purpose and learning map | Brief or implicit | Add Chapter 0 orientation. |
| Notebook, Console, and script execution | Lab link exists, model unclear | Add guided environment lesson and recovery steps. |
| Numeric operators and precedence | Used without a complete reference | Add table, examples, boundaries, and practice. |
| Strings | Used but not taught systematically | Add core operations, methods, formatting, and conversion. |
| `None` | Not part of a coherent type map | Teach with scalar types and later missing-data comparison. |
| Tuples | Missing | Add to collection selection. |
| Sets | Toolkit/check material exists | Move teaching before assessment and connect to deduplication. |
| Function scope and defaults | Partial | Expand Chapter 4. |
| Text file input/output | Insufficient | Add before CSV/pandas. |
| JSON | Missing | Add simple structured-file example. |
| Paths and working directory | Appears as an operational failure | Teach explicitly with path-independent course data access. |
| Exceptions | Combined too briefly with functions | Give explicit syntax, traceback, recovery, and practice. |
| Modules and standard library | Insufficient | Add module and import model. |
| Packages, libraries, and `pip` | pandas appears without ecosystem model | Explain managed environment and dependency boundaries. |
| Classes and objects | Missing | Add required Chapter 6 with a small domain class. |
| Algorithmic thinking | Implicit in exercises | Name and practise decomposition, tracing, search, aggregation, and validation. |
| Learning routes after completion | Generic reflection | Replace with five explicit pathways. |

## Existing activity migration

### Remove or merge

- `Responsible AI: Ask, Read, Run, Check, Modify, Explain`: merge a short policy
  into the course guide; remove the standalone required page.
- `Meet Naledi: One reporting task that grows with the course`: remove.
- AI-policy and improper-AI-use quiz items: remove, replacing them with Python
  or data reasoning mapped to the same lesson.
- AI-use declaration prompts in assignments and reflection: remove from the
  required assessment.

### Rewrite first

- `Start here: course guide` becomes Chapter 0 and the full learning map.
- `Lesson 1: Your first Python program` becomes **Programs, values,
  expressions, and output**. It establishes top-to-bottom execution, literal
  values, simple expressions, and explicit output without teaching the type
  catalogue or variables prematurely.
- The former combined Lesson 2 is split into **Variables, assignment, and
  program state**, **Basic scalar types, conversion, and arithmetic**, and
  **Strings, input, and formatted output**. This removes the duplicated
  "data types" introduction and makes `input()` understandable only after the learner knows that it returns a string.
- `Lesson 6: Functions, errors, and testing` is split across functions and
  reliability; file and exception material moves to Chapter 5.
- `Reflection and next steps` becomes the explicit pathway guide.

### Retain and reposition

- Conditions and loops move to Chapter 2.
- Lists and dictionaries expand into Chapter 3 with tuples and sets.
- Existing Boolean logic moves before filtering and is taught before it is
  assessed.
- CSV and pandas begin only after standard-library file handling and paths.
- Cleaning, grouping, statistics, visualisation, and scale-up material remain in
  the data-analysis sequence.
- The fictional datasets remain; narrative wording is removed.

### Add

- Chapter 0 learner orientation page and orientation Notebook.
- Strings and built-in-types reference.
- Operators and precedence reference.
- Files, paths, and encodings lesson and Notebook.
- Exceptions and traceback lesson and Notebook.
- Modules, libraries, packages, and managed-environment lesson.
- Classes and objects lesson, Notebook, practice, and mastery check.
- Final next-pathways page.

## Project spine

The v2 project spine uses one accumulating work product without a named
character:

1. calculate and format one centre's figures;
2. classify several records with conditions;
3. aggregate an in-memory collection;
4. refactor the calculation into tested functions;
5. read records from files and report invalid rows;
6. model one record as an object and explain when a table is preferable;
7. analyse the complete table with pandas;
8. submit a reproducible evidence Notebook.

Each stage states its input, output, validation rule, and connection to the
final project. Transfer exercises use a comparable but different context.

## Implementation order

1. Freeze this specification and keep both 1.0.0 distributions unchanged.
2. Build Chapter 0 and the rewritten first Chapter 1 lesson as the quality
   template.
3. Review the template as a first-time learner in Moodle and Python Lab.
4. Expand Chapters 1–6 and replace unmatched quiz items.
5. Reorganise Chapters 7–8 around the unchanged analysis destination.
6. Regenerate notebooks and teacher material from the completed learner path.
7. Export new canonical and Japanese catalogues, then create version 2 backups.
