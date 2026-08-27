# Chapter 3 midterm options B and C

This document extends the approved three-option design. Option A remains the
school-meal delivery review. A learner completes any one option; the other two
are optional transfer projects. All options use a 20-point source-inspection
program followed by an 80-point production program.

## Option B — Public bus service review

The transport team can investigate one route first. The route with the longest
average delay is not necessarily the route causing the greatest passenger
impact. Learners separate unreliable records, aggregate the usable records,
and rank routes by estimated passenger-delay minutes.

- Input: `projects/bus-service-review/data/bus-service-practice.csv`
- One row: one route's daily operating record
- Checkpoint: 31 source rows, 4 rows to verify, 27 analysis rows
- Naive longest delay: `R003 — Hillside Connector`
- Passenger-impact priority: `R002 — Market Loop`
- Submitted files: `inspect_bus_service.py`, `bus_service_review.py`

Quality rules cover missing/non-numeric values, negative values, completed
trips exceeding scheduled trips, passengers with zero completed trips, and all
rows in a duplicated date/route key. The route summary contains valid days,
scheduled and completed trips, cancellations, passengers, delay minutes,
average delay per completed trip, cancellation rate, and estimated
passenger-delay minutes. Ranking uses passenger-delay minutes descending,
cancellation rate descending, then route ID ascending.

## Option C — Community water-point inspection

The maintenance team can add one site visit. A failed sensor can make one raw
reading look worst, while the actual priority is a facility with repeated valid
stoppages and low-output days. Learners separate sensor and record problems,
then rank the remaining facilities.

- Input: `projects/water-point-review/data/water-points-practice.csv`
- One row: one facility's daily operating record
- Checkpoint: 31 source rows, 5 rows to verify, 26 analysis rows
- Raw minimum includes a faulty-sensor record
- Valid-record priority: `W004 — East Market Water Point`
- Submitted files: `inspect_water_points.py`, `water_point_review.py`

Quality rules cover missing/non-numeric values, negative values, delivery above
105% of rated capacity, a sensor status other than `ok`, and all rows in a
duplicated date/facility key. A valid row is a stopped day when both operating
hours and delivered litres are zero. It is a low-output day when operating
hours are positive and delivery is below 70% of rated capacity. Ranking uses
stopped days descending, low-output days descending, households served
descending, then facility ID ascending.

## Shared implementation contract

Each option supplies a completed `main()` and asks learners to implement three
small inspection functions plus eight production functions:

1. load records;
2. add quality flags without mutating the source;
3. build the verification report;
4. build analysis-ready data;
5. summarise the operational unit;
6. select the priority unit;
7. save the audit and summary CSV files; and
8. connect the pipeline in `run_project()`.

The checkers test both the published sample and a second small dataset. They do
not require any condition absent from the public project brief.
