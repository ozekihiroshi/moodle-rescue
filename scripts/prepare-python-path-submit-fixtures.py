"""Copy published model artifacts to an isolated local acceptance-test directory."""
import json
from pathlib import Path
import shutil
import tempfile

root = Path(__file__).resolve().parents[1]
refs = root / 'sample-content/introduction-to-python/reference-solutions'
specs = {
    614: [root / 'scripts/fixtures/weekly_support_reference_v2.py'],
    631: [refs / 'project-2-4/library_manager.py'],
    654: [refs / 'project-3a/inspect_school_meals.py', refs / 'project-3a/meal_delivery_review.py'],
    658: [refs / 'project-3b/inspect_bus_service.py', refs / 'project-3b/bus_service_review.py'],
    662: [refs / 'project-3c/inspect_water_points.py', refs / 'project-3c/water_point_review.py'],
    682: [refs / 'project-4-5/equipment_lending.py'],
    699: [refs / 'project-5/clinic_wait_evidence.py', refs / 'project-5/output/clinic_wait_evidence.png'],
    720: [refs / 'project-6/clinic_stock_scaleup.py', refs / 'project-6/output/clinic_stock_summary.csv',
          refs / 'project-6/output/clinic_stock_evidence.png'],
}
folder = Path(tempfile.mkdtemp(prefix='python-path-submit-'))
for cmid, paths in specs.items():
    target = folder / str(cmid)
    target.mkdir()
    for path in paths:
        shutil.copy2(path, target / ('weekly_support.py' if cmid == 614 else path.name))
print(folder)
