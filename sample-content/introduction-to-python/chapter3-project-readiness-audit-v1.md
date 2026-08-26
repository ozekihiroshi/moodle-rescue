# Chapter 3 project-readiness audit v1

## Purpose

This audit works backwards from Project 3A, *School meal delivery review*. It asks whether a learner who completed Lessons 3.1–3.4 has encountered every idea needed to finish the project without an undisclosed technique. It does not require the project to copy lesson examples.

## Decision

The present chapter provides most individual pandas operations, but it does not yet demonstrate the final joins between them. Project 3A should remain unchanged while the four bridge items below are added to the lessons and notebooks. The Moodle course must not be updated until the revised source, notebooks, quizzes, project, and checker agree.

## Coverage matrix

| Project requirement | Existing preparation | Status | Required revision |
|---|---|---:|---|
| Read a UTF-8 CSV and preserve identifiers as strings | 3.1 covers `read_csv()`, encoding, `dtype`, paths, `DataFrame`, and `Series` | Ready | Reuse a different dataset in the project; do not duplicate the lesson example |
| Inspect shape, columns, types, values, and missingness before calculation | 3.1 covers `head()`, `shape`, `columns`, `dtypes`, `info()`, and `isna().sum()` | Ready | Keep the project contract concise |
| Reject a file with missing required columns | 3.2 compares a required-name set with `df.columns` | Ready | Make the project’s seven required columns explicit |
| Preserve source data and work on a copy | 3.3 uses `raw` and `clean = raw.copy()` | Ready | Require this outcome without prescribing variable names |
| Keep missing values distinct from zero | 3.1–3.3 state this repeatedly and use explicit missing masks | Ready | None |
| Detect negative values and impossible relationships | 3.3 separates missingness from range and cross-field constraints | Ready | Adapt the relationship to `served <= present` and `served <= delivered` in the project |
| Detect all rows sharing a duplicate business key | 3.3 uses `duplicated(subset=..., keep=False)` | Ready | Define the project key as date plus school ID |
| Normalise district labels while retaining the source value | 3.3 uses `str.strip().str.title()` and counts changed rows | Ready | None |
| Combine several quality masks into one analysis-ready decision | 3.3 creates an `analysis_ready` mask | Partial | Add one example that preserves individual reason flags and derives one overall decision from them |
| Produce a records-to-verify table with one public reason per row | Audit records exist in 3.3, but the lesson does not build a row-level verification table | Gap | Add a compact example that selects flagged rows, assigns an ordered reason, and preserves the original values |
| Derive unmet meals for valid rows only | 3.1 covers column arithmetic; 3.3 covers analysis-ready extraction | Ready | Add no project-specific formula to the lesson title or introduction |
| Create named school-level totals | 3.4 covers named `groupby().agg()` and grain | Ready | Use school as the project grain |
| Count shortage days inside aggregation | 3.4 covers counts but not a conditional count inside named aggregation | Gap | Add a named-aggregation example using a small helper or Boolean-sum technique |
| Compute coverage as ratio of totals | 3.4 explicitly distinguishes ratio of totals from mean of row rates | Ready | None |
| Rank by descending average unmet, descending shortage days, then ascending ID | 3.2/3.4 sort on multiple columns, but not mixed directions or deterministic tie-breaking | Gap | Teach `sort_values([...], ascending=[False, False, True])` and explain the final tie-break key |
| Save two CSV products without an index | 3.1 covers `to_csv(index=False)` | Partial | Show a short pipeline saving two purpose-specific tables to a supplied output directory |
| Re-read outputs and reconcile row counts, columns, and priority | 3.1 re-reads a CSV; 3.3/3.4 use assertions, but the complete output-boundary check is not assembled | Gap | Add a final notebook checkpoint that re-reads both outputs and verifies schema, counts, and the first ranked school |
| Organise the analysis as functions and connect it in `run_project()` | Chapter 2 covers function contracts and file-processing pipelines | Ready | In the project README, map the eight functions to the data flow; do not reteach functions in Chapter 3 |

## Minimum revisions before release

1. **3.3 — From separate flags to a verification table**  
   Preserve each issue mask, combine them into `analysis_ready`, and create a row-level table for records requiring review. State a deterministic rule when one row violates more than one condition.

2. **3.4 — Conditional counts in a named aggregation**  
   Show how a Boolean condition becomes a count such as the number of shortage days. The example must explain what the resulting row means.

3. **3.4 — Deterministic mixed-direction ranking**  
   Sort a summary by two descending measures and one ascending identifier, explaining why the identifier resolves a tie reproducibly.

4. **3.4 notebook — Output-boundary reconciliation**  
   Save a review table and a summary table, re-read both, and assert their column sets, row counts, and a selected result. This is the bridge from exploratory cells to a submitted program.

## Content to avoid

- Do not reveal that the project is designed to reverse the naive answer.
- Do not describe the project as a lesson about data ethics, auditing, or critical thinking in the learner-facing opening.
- Do not add visualisation, machine learning, optimisation, or a written essay to Project 3A.
- Do not make the lesson example use the same school-meal dataset; learners must transfer the method.
- Do not add checker-only requirements that are absent from the public contract.

## Acceptance gate

Chapter 3 is project-ready only when:

- the English and Japanese lesson sources and notebooks contain the same four bridges;
- the revised examples run from a clean Python Lab home;
- all lesson checks test only taught and published content;
- the Project 3A reference solution still passes every public check;
- the starter gives one clear first failure and contains no hidden completed solution;
- a learner can move from Moodle to Python Lab, produce the two CSV files, run the checker, and identify `S004` without reading the reference solution.

