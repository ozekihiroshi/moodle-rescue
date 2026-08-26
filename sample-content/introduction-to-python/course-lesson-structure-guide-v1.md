# Course lesson structure guide v1

## Purpose

Use hierarchy to show the relationship between ideas and the learner's current position. Do not add headings merely to create visual breaks.

## Semantic hierarchy

- The Moodle activity title is the lesson-level heading.
- `h2` identifies introduction, outcomes, each numbered learning stage, summary, and the connection forward.
- `h3` identifies a concept, comparison, procedure, caution, or example that belongs to the preceding `h2` stage.
- A single method or minor operation normally belongs in prose or code under a parent concept; it does not receive a numbered heading.

## Standard lesson route

1. Introduction: connect prior work to the present problem and explain why it matters.
2. Learning outcomes: three to five observable capabilities.
3. Route note: distinguish required learning, supporting detail, and integrated practice.
4. Numbered stages: normally four to seven conceptual steps.
5. Integrated practice: combine only operations already introduced in the lesson or earlier lessons.
6. Summary: correspond directly to the learning outcomes.
7. Connection: name the product carried into the next lesson or project.

## Inside a numbered stage

Prefer this reading order where it helps:

```text
problem or question
→ concept
→ code example
→ how to read the result
→ caution
→ small check
```

Do not force all six labels into every stage. The prose should remain readable from top to bottom.

## Content priority

- **Required:** needed for the lesson outcome, the next lesson, or an assessed project.
- **Supporting:** clarifies an error-prone distinction but is not a new branch in the main route.
- **Further study:** optional material that can be skipped without breaking the route.
- **Integrated practice:** combines required knowledge; it must not introduce many new APIs.

A compact route note near the beginning is preferable to repeating a large badge on every paragraph.

## Data lessons

Where feasible, establish this working habit without repeatedly lecturing about it:

```text
read the data
→ display it as a table
→ identify the meaning of a row and each column
→ order it for comparison
→ decide the needed processing
→ process with Python
→ inspect the result again
```

## Translation and adaptation

English is canonical, but Japanese is an instructional adaptation rather than a word-for-word substitution. Both variants must preserve the same concept hierarchy, observable outcomes, practice contract, and transition to the next lesson.

## Chapter-specific adjustment

Apply the same principles at different granularity:

- Chapter 1: smaller steps and more explicit execution guidance for first-time programmers.
- Chapter 2: group operations around data structures, functions, reliability, and file handling.
- Chapter 3: group operations around inspection, selection, quality, aggregation, and decisions.

Do not mechanically copy Chapter 3 numbering or paragraph templates into Chapters 1 and 2.
