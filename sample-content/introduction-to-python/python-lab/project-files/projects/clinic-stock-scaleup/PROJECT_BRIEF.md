# Clinic medicine stock-out response

Complete `clinic_stock_scaleup.py` to process the generated clinic-stock CSV in chunks. Account for every row, exclude invalid records, aggregate by district and medicine, and identify the first resupply.

Start with `data/clinic-stock-fixture.csv`. Generate the 120,000-row source from the project Notebook only after the small workflow is understood.

Run the checker with:

```text
python projects/clinic-stock-scaleup/check_clinic_stock_scaleup.py
```

Submit the completed Python file and the generated summary CSV and evidence PNG. See the Moodle project page for the complete public contract and checkpoints.
