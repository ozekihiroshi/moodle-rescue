# Chapter 4 objects/classes implementation report v40

## Implemented course order

1. Programming foundations and scalar/basic values
2. Data structures, functions, and file processing
3. Analysing tabular data
4. Objects and classes
5. Communicating evidence
6. Scaling up

The former Chapters 4 and 5 were moved to 5 and 6 without recreating their
activities. All 24 existing subsection/activity CMIDs in those chapters were
preserved in both course variants.

## New Chapter 4

- 4.1 From records and functions to objects
- 4.2 State, methods, and valid objects
- 4.3 Collections, composition, and responsibility
- 4.4 Persistence and testing class-based programs
- 4.5 Applied project: Community equipment lending desk

English and Japanese each contain five native Moodle subsections and fifteen
activities. Lessons 4.1–4.4 each contain a page, Python Lab link, and a
repeatable ten-question check. Project 4.5 contains a brief, project Notebook,
and file-submission assignment.

Lesson 4.1 was refined after insertion so that the learner sees the same lending
operation first as a dictionary plus function and then as a class. It has nine
semantic `h2` groups and two complete worked code blocks in each language.

## Python Lab assets

Ten Notebook sources were added: five English and five Japanese. The reviewed
distribution now contains 53 notebooks. The equipment-lending project includes
one editable starter, one checker, bilingual README files, and a separate
reference solution.

The reference solution passes seven public checker areas and ends with
`ALL TESTS PASSED`. All three local learner volumes contain the new bilingual
notebooks and project files. Existing learner files were not overwritten.

The rebuilt single-user image is:

```text
sha256:9c879a4fb8eab56bc56474418dcf61bcdb9ec717585a2731f6872215c427720b
```

## Verification evidence

- Both Moodle variants have six top-level chapters in the intended order.
- Both Chapter 4 variants have five subsections and fifteen activities.
- Four quizzes per language have ten slots, unlimited attempts, and a 100-point
  maximum.
- All ten LTI targets end in the corresponding English or Japanese Notebook.
- The former later chapters retained every pre-v39 CMID.
- All 53 Notebook documents passed the Python Lab image verification run.
- The Project 4.5 reference solution passed all published checks.
- Pre/post live outlines are stored under `backups/moodle-outline/`.

The in-app browser could not be controlled because its Windows helper was
blocked by the workspace ACL. Moodle database hierarchy, HTML structure, LTI
paths, activity order, quiz slots, Python Lab files, and executable results were
verified directly. A human visual pass in Moodle remains appropriate.

## Next content review

This is the working first edition of the chapter, not the final prose review.
The next pass should:

1. deepen 4.2–4.4 with additional connected worked examples while keeping the
   existing hierarchy;
2. replace the current broad ten-question object set with four lesson-specific
   ten-question sets;
3. have one fictional learner complete the starter using only the page,
   Notebook, README, and published checker;
4. visually review both languages in Moodle;
5. then design the remaining 3.5B and 3.5C choices and improve Chapters 5–6.

