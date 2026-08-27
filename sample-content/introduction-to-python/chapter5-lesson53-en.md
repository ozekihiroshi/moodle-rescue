# Lesson 5.3: From chart to evidence statement

## Introduction

A correct chart does not speak for itself. The reader still needs to know the observed values, compared population, period, and limits of the data. Evidence writing is concise precisely because each sentence has a distinct job.

## What you will be able to do

- separate observation, interpretation, and causal claim;
- write a numerical statement with comparison, scope, and period;
- state one material limitation without discarding the observation;
- annotate, save, reopen, and verify reproducible visual evidence.

> **Required path:** 5.3.1–5.3.5. Complete 5.3.6 as the integrated practice.

## 5.3.1 Start with an observable comparison

“Riverside was bad” is neither measured nor bounded. A useful first sentence identifies the service and value: “Riverside Clinic — Evening had the highest average wait at 48.1 minutes.” If comparison with another value matters, name both values or their absolute difference.

## 5.3.2 State population, period, and denominator

The finding covers the records actually analysed, not every patient in every year. State that the comparison used 36 clinic–time-slot–week records from 2026-W01 through W06. For the over-60 rate, name patients seen as the denominator.

## 5.3.3 Separate observation from cause and action

The records show where waiting was concentrated. They do not show whether the cause was staffing, appointment scheduling, an emergency, data entry, or something else. A support-team recommendation is a decision based on the stated criterion, not proof that the selected service caused the district's waiting problem.

| Wording | Status |
|---|---|
| Riverside Evening averaged 48.1 minutes | observed calculation |
| waiting became longer across these six records | observed pattern if the weekly table supports it |
| staff shortage caused the waiting | unsupported causal claim |
| investigate staffing and appointment mix next | bounded next question |

## 5.3.4 Direct the reader to the decision value

Use restrained emphasis: a direct label, one contrasting colour, or a reference line. Keep all other categories visible so the selected value retains context. Do not use 3D area, decorative icons, or colour intensity to encode an unmeasured judgment.

## 5.3.5 Save and verify the actual deliverable

```python
output = project / "output" / "clinic_wait_evidence.png"
output.parent.mkdir(parents=True, exist_ok=True)
figure.savefig(output, dpi=150, bbox_inches="tight")
assert output.is_file() and output.stat().st_size > 0
```

Open the saved PNG. Confirm that title, labels, units, period, and highlighted target remain visible outside the Notebook. Keep the aggregation and plotting code so the figure can be regenerated from the source.

## 5.3.6 Integrated practice

For the clinic/time-slot comparison, write exactly three sentences: a numerical observation, a sentence giving period and denominator, and a limitation that does not claim cause. Save the figure, reopen it, and compare its category order with the saved summary table.

## Summary

- A strong evidence statement names observed values and the comparison scope.
- Description, interpretation, decision, and cause are not interchangeable.
- Saved visual evidence remains useful when its table, code, labels, and limitations are traceable.

## Chapter project

In 5.4 you will create both the total-burden and patient-experience views, then submit the program and the PNG that makes their different answers visible.
