# Project 4.5 — Community equipment lending desk

Complete the supplied `equipment_lending.py`; do not start from an empty file.

The program reads:

- `data/equipment_inventory.csv`: five opening inventory records
- `data/lending_requests.csv`: six ordered LOAN/RETURN requests

It must preserve both source files, process every request in order, reject invalid transitions without changing state, and create:

- `output/equipment_inventory_after.csv`
- `output/lending_results.csv`

Expected counts are 6 requests, 3 accepted, 3 rejected, 5 total items, 2 available, and 3 on loan.

Implement TODOs in order: EquipmentItem (1–6), LendingDesk (7–12), file/request functions (13–16). Save with Ctrl+S, run the program, inspect both outputs, then run:

```text
python projects/equipment-lending/check_equipment_lending.py
```

Completion requires `ALL TESTS PASSED`. Submit only `equipment_lending.py`.
