# Python curriculum concept and defect coverage audit

Status: **Normative design gate for curriculum v2**

This document prevents important foundations from disappearing when lessons are shortened, and prevents the introductory course from becoming a catalogue of every Python feature. It governs lesson text, notebooks, quizzes, projects, teacher notes, adaptations, and release backups.

The English course is canonical. Language adaptations may change examples and explanations, but must preserve concept IDs, coverage level, required evidence, and assessed outcome.

## Coverage levels

| Level | Meaning | Required treatment |
|---|---|---|
| **M — Master** | Use and explain independently in this course | Explanation, reference, worked example, transfer practice, common defect, feedback, and assessment |
| **I — Introduce** | Recognise and use with support | Concise explanation and working example |
| **D — Defer** | Belongs to a named later pathway | Mention only to clarify the boundary or next step |
| **X — Exclude** | Outside this course | Do not teach, require, or assess |

## Audit rules

1. No activity may require a concept before it is mastered or introduced.
2. Every **M** item needs a normal case, boundary case, and failure or misconception case.
3. Syntax correctness, runtime behaviour, and fitness for the task are separate checks.
4. “The code ran” is not sufficient evidence of understanding.
5. Quiz questions test taught concepts or explicitly taught misconceptions.
6. Repeated labels such as “Basic idea” are optional; the explanation should follow the subject naturally.
7. Open with a motivating problem. Keep “By the end” compact; put estimated time in the overview or closing.
8. Notebooks must run from a clean kernel in the pinned Python Lab image.
9. English is canonical; adaptations preserve behaviour and assessment meaning rather than translating word for word.
10. Release requires this audit, the 120-question mapping, a first-time learner walkthrough, notebook execution, and backup/restore verification.

## Chapter 0 — Orientation and execution

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| E01 | M | A program is an ordered set of explicit instructions; predict and run a short sequence | The computer will infer an unstated intention |
| E02 | I | CPU executes instructions, memory holds current values, files preserve data | Memory, a cell, and a saved file are the same |
| E03 | M | Distinguish source code from output | Output is pasted into a code cell |
| E04 | M | Distinguish syntax error, runtime error, and wrong-but-running result | Every failure is called a syntax error |
| E05 | M | Distinguish code cells and Markdown cells | Explanation text is run as Python |
| E06 | I | Console, notebook, and script run the same language in different workflows | Notebook Python is a different language |
| E07 | M | Explain kernel state and cell order; restart and run all | Work succeeds only because of hidden old state |
| E08 | M | Save, reopen, rename, and locate work on a shared computer | Closing the browser is confused with submission or deletion |
| E09 | M | Read the final exception line, then find the relevant source line | A traceback is treated as meaningless noise |
| E10 | M | Use the managed Python Lab and supplied course files | Environment changes mask a lesson or deployment defect |

## Chapter 1 — Values, names, types, and communication

### 1.1 Programs, values, expressions, output

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| F01 | M | Distinguish instruction, literal, expression, and displayed output | `2 + 3` and `5` are treated as identical kinds of thing |
| F02 | M | Trace normal top-to-bottom execution | Output is explained without considering order |
| F03 | M | Recognise numeric and string literals; Boolean literals are mastered with scalar types | Quotation marks around text are omitted |
| F04 | M | Use basic arithmetic and parentheses | Expressions are read strictly left to right |
| F05 | M | Use `print()` to communicate selected results | A calculated value is assumed to be displayed or saved |
| F06 | I | Repeated literals motivate names in the next lesson | Variables appear as unexplained ceremony |

### 1.2 Variables, assignment, and state

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| V01 | M | A name refers to an object/value; inspect state changes | A variable is only a physical “box” |
| V02 | M | Distinguish assignment `=` from comparison `==` | One is written where the other is needed |
| V03 | M | The right side is evaluated before assignment | `total = total + amount` is read as algebra |
| V04 | M | Reassignment changes program state | A name is expected to retain every prior value |
| V05 | M | Apply identifier rules, meaningful names, and case sensitivity | `Score` and `score` are mixed |
| V06 | M | Diagnose undefined and misspelled names | `NameError` is patched with an unrelated value |
| V07 | I | Assignment can alias a mutable object; master in Chapter 3 | Assignment is assumed always to copy |
| V08 | M | Make a notebook reproducible without hidden earlier state | Variables depend on an unrecorded run order |
| V09 | I | Upper-case constant names are convention, not enforcement | Reassignment is believed impossible |
| V10 | I | Unpack matching structures | Length mismatch is ignored |

### 1.3 Scalar types, conversion, arithmetic

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| T01 | M | Choose and explain `int`, `float`, `bool`, `str`, and `None` | Raw values are treated as interchangeable |
| T02 | M | Inspect with `type()`; use `isinstance()` when justified | A type is compared to text |
| T03 | M | Convert deliberately; test `bool("False")` | Conversion is assumed to follow everyday wording |
| T04 | M | Use `/ // % **` and explain their results | Division, floor division, and remainder are confused |
| T05 | M | Apply precedence and parentheses | A plausible but wrong total is accepted |
| T06 | M | Detect and handle a zero denominator | Ratios fail only on real data |
| T07 | M | Explain floating-point approximation | Measured floats are tested for exact equality |
| T08 | M | Distinguish display rounding from stored value | Early rounding accumulates error |
| T09 | I | Python integers differ from common fixed-width C/Java integers | Numeric limits are assumed identical across languages |
| T10 | M | `None` is absence, not zero or empty text | Missingness is silently converted to zero |
| T11 | M | Use value equality normally and `is None` for absence | `is` is used for strings or numbers |
| T12 | I | `Decimal` exists for exact decimal domains | Binary float is claimed suitable for every domain |
| T13 | D | Complex numbers and bitwise operations go to domain/systems study | Extra syntax displaces essential reasoning |

### 1.4 Strings, input, and formatted output

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| S01 | M | Create strings with consistent quotes and common escapes | Delimiters are mismatched |
| S02 | M | Concatenate/repeat text and convert other values explicitly | Text and numbers are added directly |
| S03 | M | Use `len`, zero-based/negative indexes, and half-open slices | Off-by-one access |
| S04 | M | Explain string immutability | A method result is not stored |
| S05 | M | Use basic cleaning methods with an explicit rule | Cleaning destroys meaningful text |
| S06 | M | Use `in` and `not in` for containment | Containment is confused with equality |
| S07 | M | `input()` returns text; validate before conversion | Arithmetic is attempted on raw input |
| S08 | M | Use f-strings while separating display from stored data | Formatting changes are mistaken for data changes |
| S09 | I | Python text is Unicode and files encode it as bytes | Characters and encoded bytes are identical |
| S10 | D | Regular expressions belong to advanced text processing | A complex regex replaces a clear string method |

## Chapter 2 — Decisions and repetition

### Conditions and Boolean reasoning

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| C01 | M | A condition produces a Boolean used to select behaviour | The decision is confused with the resulting action |
| C02 | M | Use all comparison operators and test equality boundaries | `>` is used when the rule includes equality |
| C03 | M | Read chained comparisons such as `0 <= score <= 100` | They are translated into unrelated comparisons |
| C04 | M | Build truth tables for `and`, `or`, and `not` | Results are guessed only from everyday language |
| C05 | M | Apply comparison, `not`, `and`, `or` precedence; parenthesise intent | Valid code selects the wrong branch |
| C06 | M | Explain short-circuit evaluation and guard unsafe work | Division/indexing occurs before the safety check |
| C07 | M | Recognise truthy/falsy values but keep important rules explicit | Zero, empty, missing, and invalid become equivalent |
| C08 | M | Write `if/elif/else` with indentation-defined suites | Required cases are uncovered or code escapes the branch |
| C09 | M | Order overlapping rules from appropriate specific cases | A broad condition shadows a later rule |
| C10 | M | Distinguish independent `if` statements from one exclusive chain | Only one independent branch is expected to run |
| C11 | M | Use membership; diagnose `x == "A" or "B"` | A non-empty string makes the condition truthy |
| C12 | M | Test absence with `is None` when allowed | Missing, zero, false, and empty are mixed |
| C13 | M | Derive boundary tests from rule wording | Only comfortable middle values are tested |
| C14 | I | Apply De Morgan’s laws to negation | `not` moves without exchanging `and/or` |
| C15 | I | Read a simple conditional expression | Multi-step rules are compressed unreadably |
| C16 | D | Structural pattern matching belongs to later language study | It appears before ordinary decisions are secure |

### Loops and finite processes

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| L01 | M | State the start, update, and stopping argument of repetition | No termination reasoning exists |
| L02 | M | Use `for` over an iterable | The current item is confused with the collection |
| L03 | M | Predict half-open `range()` values | Off-by-one counts |
| L04 | M | Distinguish current item, counter, and accumulator | The accumulator resets inside the loop |
| L05 | M | Use `while` only with visible progress toward stopping | Missing update causes an infinite loop |
| L06 | M | Explain `break` and `continue` control flow | A necessary update/finalisation is skipped |
| L07 | M | Define behaviour for empty input | Index, minimum, or division fails |
| L08 | I | Read simple nested loops and relate work to input size | Work is multiplied accidentally |
| L09 | M | Avoid structural mutation during iteration without a strategy | Items are skipped or processed twice |
| L10 | M | Trace loop state in a small table | Code is changed at random |
| L11 | I | Recognise one-pass versus repeated-pass work | Inefficient code is labelled “big data” |
| L12 | D | Loop `else` belongs to later language study | Lesser-known syntax adds no learning value |

## Chapter 3 — Collections and small algorithms

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| D01 | M | Choose list, tuple, dictionary, or set from order, mutability, lookup, uniqueness | Selection is based only on familiar syntax |
| D02 | M | Index, slice, append, replace, and remove list items safely | Off-by-one or wrong-duplicate removal |
| D03 | M | Mutating methods such as `list.sort()` commonly return `None` | The list is replaced with the method result |
| D04 | M | Distinguish aliasing from a shallow copy | Editing the “copy” changes the original |
| D05 | I | Shallow copying does not recursively copy nested mutable objects | Nested data is assumed independent |
| D06 | M | Use tuples for fixed records and matching unpacking | Immutability is assumed deep |
| D07 | M | Use dictionary keys, `get`, membership, and iteration | Key membership is confused with value membership |
| D08 | M | Do not change dictionary size during iteration | Processing fails or skips entries |
| D09 | M | Use sets for uniqueness and set operations | Stable positional order is expected |
| D10 | M | Use collection truthiness only for an intended empty/non-empty rule | Missing and empty are collapsed |
| D11 | I | Read/write simple comprehensions | Complex branching is hidden in one line |
| D12 | M | Distinguish `sorted(x)` from `x.sort()` and use a key | Original order is lost or numbers sort as text |
| D13 | M | Implement search, count, total, min, max, average with empty cases | Initial values or denominators are wrong |
| D14 | I | Recognise membership/cost implications of list versus set/dict | Performance claims are made without context |
| D15 | I | Understand hashable keys and one-pass iterators/generators | A generator is reused after exhaustion |
| D16 | D | Custom iterators and advanced generators go to algorithms | Syntax expands before collection reasoning is stable |

## Chapter 4 — Functions, decomposition, testing

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| G01 | M | Define/call a function with one responsibility | The definition is never called |
| G02 | M | Distinguish parameters from arguments | Concrete call values are called definition parameters |
| G03 | M | Distinguish `return` from `print` | A result is visible but cannot be reused |
| G04 | M | Missing executed return produces `None` | One branch silently returns absence |
| G05 | M | Explain local/global scope at the working level | A function depends on an accidental global |
| G06 | M | Separate calculation from input/output where practical | Testing requires interaction |
| G07 | M | Use stable defaults and diagnose mutable-default retained state | Results depend on earlier calls |
| G08 | M | Use positional/keyword arguments clearly | Values are duplicated or misordered |
| G09 | I | Docstrings and type hints communicate; they do not prove runtime validity | Hints are treated as automatic validation |
| G10 | M | Decompose validation, calculation, and reporting | One function owns every responsibility |
| G11 | M | Use assertions for internal assumptions, not user validation | Required checks disappear when assertions are unavailable |
| G12 | M | Derive normal, boundary, empty, and invalid tests | One happy path is called a test suite |
| G13 | I | Read a simple `pytest` failure | Tooling overwhelms the introductory objective |
| G14 | D | Recursion, lambdas, decorators, closures go to later study | Compactness is mistaken for mastery |

## Chapter 5 — Files, exceptions, libraries

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| I01 | M | Inspect working directory and explain relative paths | A path is assumed relative to the visible notebook |
| I02 | M | Use portable `pathlib.Path` paths, not a learner’s absolute home | Code works on only one machine |
| I03 | M | Distinguish text, bytes, and UTF-8 encoding | Characters are corrupted |
| I04 | M | Use `with open` and choose read/write/append deliberately | A handle leaks or a file is overwritten |
| I05 | M | Handle line endings without deleting meaningful whitespace | Unrestricted `strip()` damages data |
| I06 | M | Read/write CSV with the `csv` module, including quoted commas | Lines are split manually on commas |
| I07 | M | Read/write simple JSON and preserve supported types | Every Python object is assumed JSON-compatible |
| I08 | M | Read common exceptions and locate the failing operation | Everything is caught before it is understood |
| I09 | M | Use narrow `try/except`; add `else/finally` when justified | Broad handling hides programming defects |
| I10 | M | Preserve valid rows and audit rejected rows/reasons | Cleaning silently loses data |
| I11 | I | Raise an exception when a function contract cannot be met | An ambiguous sentinel is returned |
| I12 | M | Distinguish module, function, attribute, standard library, external package | A local name shadows an import |
| I13 | I | Recognise the main guard and managed package environment | Arbitrary `pip install` changes a shared lab |
| I14 | D | Packaging, dependency management, virtual environments go to developer study | Setup becomes a barrier to this course |

## Chapter 6 — Objects and classes

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| O01 | M | Relate object, type, identity, state, and behaviour | Object is equated only with a class definition |
| O02 | M | Distinguish a class from instances | Per-instance state is stored on the class |
| O03 | M | Initialise valid state with `__init__` and `self` | `self` is omitted or objects are partly valid |
| O04 | M | Read/update attributes and define state-using methods | A misspelling silently creates an attribute |
| O05 | I | Use `__repr__` for inspection | Developer representation is confused with user output |
| O06 | I | Explain non-public naming conventions | Underscores are claimed to enforce access control |
| O07 | M | Choose dictionary, class, or DataFrame for a stated task | A class is used merely because it appears advanced |
| O08 | I | Prefer simple composition where responsibilities combine | Deep inheritance appears prematurely |
| O09 | D | Inheritance-heavy design, abstract classes, descriptors, metaclasses go later | Feature coverage replaces usable design |

## Chapter 7 — Tabular data, analysis, evidence

| ID | Level | Required concept/evidence | Common defect or misconception |
|---|---|---|---|
| A01 | M | Identify observation/row, variable/column, value, and table | A formatted report is assumed analysis-ready |
| A02 | M | Explain CSV as text conventions, not a typed database | Types/relationships are assumed preserved |
| A03 | M | Use Series, DataFrame, index, columns, dtypes | Index is treated as an ordinary field accidentally |
| A04 | M | Load course data robustly from every supplied notebook | Hidden working-directory assumptions cause missing files |
| A05 | M | Inspect `head/tail/shape/info/describe` before analysis | Calculation starts before data understanding |
| A06 | M | Select with `loc`/`iloc` and explain label/position | Wrong rows or off-by-one positions |
| A07 | M | Build Series masks with `& | ~` and parentheses; distinguish scalar Boolean operators | Ambiguous truth value or precedence errors |
| A08 | M | Sort typed values and retain intended output | Numeric text sorts lexicographically |
| A09 | M | Convert types while auditing invalid values | Coercion silently becomes missing data |
| A10 | M | Detect and measure missingness with `isna` | Values are compared directly with NaN |
| A11 | M | Justify drop/fill/retain rules | All missing values are treated alike |
| A12 | M | Distinguish duplicate rows from duplicate IDs/events | Legitimate repeated events are removed |
| A13 | M | Normalise text with a reviewable rule | Distinct categories are merged |
| A14 | M | Assign explicitly with `.loc` and avoid chained-assignment ambiguity | A change is not applied reliably |
| A15 | M | Compute/cross-check counts, totals, min, max | Aggregation uses the wrong filtered rows |
| A16 | M | Interpret mean/median and choose appropriately | Outlier-sensitive mean is reported without comment |
| A17 | I | Interpret quartiles, spread, standard deviation | Spread is treated as decoration |
| A18 | M | Compute proportions with a named, non-zero denominator | Percentage base is unclear |
| A19 | M | Use `groupby` as split–apply–combine and inspect sizes | Wrong columns/groups are aggregated |
| A20 | M | Distinguish weighted and unweighted means | Group averages are averaged despite unequal sizes |
| A21 | I | Distinguish sample from population | Claims exceed observed data |
| A22 | M | Distinguish association from causation | A chart becomes a causal claim |
| A23 | M | Choose chart from question/data type; label units/scope | Chart is decorative or uninterpretable |
| A24 | M | Use honest scales and state filters/aggregation | Visual exaggeration or hidden exclusions |
| A25 | M | Aggregate to the intended analytical unit before plotting | Event rows are presented as centres |
| A26 | I | Read large CSVs in chunks and aggregate incrementally | Everything is loaded merely because pandas can |
| A27 | I | Use `usecols`/dtypes with verification to reduce memory | Optimisation changes meaning or precision |
| A28 | M | Record source, cleaning decisions, checks, outputs | Results cannot be recreated or audited |
| A29 | I | Join with key uniqueness and row-count checks | Many-to-many joins multiply rows |
| A30 | D | Time series, SQL, Parquet, distributed processing, pipelines go later | One ordinary CSV is called “big data” without qualification |

## Chapter 8 — Integrated project

| ID | Level | Required evidence | Common failure |
|---|---|---|---|
| P01 | M | State an answerable operational question | Start with a favourite chart |
| P02 | M | Define observation unit, fields, units, scope | Mix centre-level and event-level data |
| P03 | M | Write validity/cleaning rules before desired results are known | Rules change to produce a preferred answer |
| P04 | M | Validate the method on a small known example | Untested logic runs on the full dataset |
| P05 | M | Inspect source and record limitations | Provided data is assumed correct |
| P06 | M | Produce a cleaning audit | Corrections are silent |
| P07 | M | Cross-check one important result independently | Trust one code path |
| P08 | M | Provide a suitable labelled visual supported by calculation | Decorative chart without evidence |
| P09 | M | Separate finding, limitation, and next step | Claims exceed the data |
| P10 | M | Restart kernel and run all top-to-bottom | Submission depends on hidden state |
| P11 | M | Submit specified files with stable names and no sensitive data | Source or portable path is missing |
| P12 | M | Explain and modify selected work regardless of AI assistance | Submitted code cannot be verified or adapted |

## Deliberate pathway boundaries

| Later course | Preparation here | Deferred work |
|---|---|---|
| Algorithms/foundations | Conditions, loops, collections, functions, tests, cost awareness | Recursion, formal complexity, trees, graphs, advanced algorithms |
| Applied data analysis | Cleaning, grouping, descriptive statistics, evidence | Inference, experiments, regression, time series, domain methods |
| Data engineering/large scale | Files, chunks, schemas, audit trails | SQL systems, Parquet, orchestration, distributed/cloud pipelines |
| Web development | Functions, modules, objects, validation, exceptions | HTTP, frameworks, databases, authentication, deployment |
| Advanced Python/OOP | Objects, classes, composition, modules | Advanced inheritance, protocols, decorators, async, packaging |
| AI/machine learning | Data quality, reproducibility, evaluation habits | Models, features, model risk, deployment |

## Required evidence for each Master item

| Evidence | Minimum |
|---|---|
| Teach | Learner-facing explanation in context |
| Demonstrate | Executable example with its result explained |
| Practise | Guided change/completion |
| Diagnose | Deliberate common defect to identify and repair |
| Test | Normal, boundary, and failure checks where applicable |
| Assess | Quiz, project criterion, or observable explanation |
| Support | Teacher note with likely difficulty and useful prompt |

Every **M** ID must map to all seven columns. An **I** item maps at least to Teach and Demonstrate. A **D** item cannot become a prerequisite or assessed detail.

## Release procedure

1. Map every lesson paragraph, notebook cell, quiz item, and rubric criterion to IDs.
2. Mark required evidence present, partial, or missing.
3. Repair missing **M** evidence before adding enrichment.
4. Complete the course as one fictional first-time learner from a clean Python Lab account.
5. Audit the Japanese adaptation while retaining the English behavioural specification.
6. Export a Moodle backup only after semantic, execution, learner-flow, and restore checks pass.

## Technical baseline

Check detailed wording and examples against documentation matching the pinned runtime: Python 3.13 and the pandas version in Python Lab. The baseline includes the Python execution model, expressions, compound statements, input/output tutorial, and the pandas user guide for indexing, missing data, grouping, assignment semantics, and scaling.

This audit is stricter than a chapter outline. A title may exist while the learner still lacks the explanation, defect diagnosis, boundary reasoning, or transfer practice needed to use the concept safely.
