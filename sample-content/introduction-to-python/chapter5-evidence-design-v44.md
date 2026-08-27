# Chapter 5 design — Communicating evidence

English remains the canonical edition. The Japanese course is an adaptation
with the same public contracts, data, checkpoints, and assessment.

## Chapter outcome

Learners turn a verified table into an honest visual comparison and a short,
bounded evidence statement. They must be able to explain why the chosen grain,
metric, chart, scale, and wording answer the operational question.

## Learning path

### 5.1 From a question to a chart

- define the decision question and the plotted grain;
- distinguish category comparison, ordered change, relationship, and distribution;
- build bar, line, scatter, and histogram examples from an inspected plot table;
- label title, axes, units, count, and source period.

### 5.2 Honest comparisons

- distinguish total burden, average experience, and percentage affected;
- calculate rates from compatible totals rather than averaging row percentages;
- use zero baselines for length comparisons and disclose restricted ranges;
- preserve missing periods, stable category order, and relevant group counts;
- compare what changes when grain or metric changes.

### 5.3 From a chart to an evidence statement

- separate observation, interpretation, and unsupported causal claims;
- name the values, comparison, population, and period;
- state one material limitation without weakening the verified observation;
- annotate the decision-relevant value and save reproducible chart evidence;
- verify the saved figure and the table used to draw it.

### 5.4 Applied project — Clinic waiting-time evidence

A district health coordinator has one temporary support team for the following
week. Thirty-six fictional weekly service records cover three clinics and two
time slots over six weeks.

The obvious answer depends on the metric:

- total waiting minutes identify Central Clinic because it serves more people;
- average waiting time and the over-60-minute rate identify Riverside Clinic's
  Evening service as the strongest candidate for targeted support.

The learner must make both views visible. The discovery is not stated as a
lesson slogan; it appears when the two verified plot tables and two-panel chart
are completed.

## Project deliverables

The learner edits one supplied starter, `clinic_wait_evidence.py`. It must:

1. load and validate the supplied CSV without changing it;
2. build a clinic-level total-burden table;
3. build a clinic-and-time-slot experience table;
4. select the total-burden clinic and the targeted-support clinic/time slot;
5. create one two-panel PNG with honest axes, labels, units, period, and direct
   identification of the selected values;
6. save the summary CSV and PNG;
7. print a three-sentence evidence note containing observation, numbers, scope,
   and limitation.

Submission consists of:

- `clinic_wait_evidence.py`
- `clinic_wait_evidence.png`

The generated CSV remains in Python Lab as inspectable working evidence.

## Published checkpoints

The exact values will be generated and locked with the reference solution.
At minimum the brief publishes source row count, total-burden clinic, support
target, target average wait, and target over-60 rate. These values allow manual
checking without revealing the implementation.

## Assessment boundary

The checker tests only published requirements. It uses an alternate compatible
table to reject solutions that hard-code clinic IDs, row counts, or results.
It verifies source preservation, summaries, selection, evidence text, and saved
outputs. Visual style is constrained only where meaning is affected; decorative
choices are not graded.
