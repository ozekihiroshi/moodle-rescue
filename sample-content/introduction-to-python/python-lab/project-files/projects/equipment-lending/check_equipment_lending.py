"""Learner-facing checker for Project 4.5."""
from __future__ import annotations
import csv, hashlib, tempfile
from pathlib import Path
from equipment_lending import EquipmentItem, LendingDesk, load_inventory, process_requests, run_project

PROJECT = Path(__file__).resolve().parent
INVENTORY = PROJECT/"data"/"equipment_inventory.csv"
REQUESTS = PROJECT/"data"/"lending_requests.csv"

def digest(path): return hashlib.sha256(path.read_bytes()).hexdigest()
def expect(kind, operation):
    try: operation()
    except kind: return
    raise AssertionError(f"expected {kind.__name__}")

def check_item():
    item=EquipmentItem(" E010 "," Camera "," Media ")
    assert item.to_record()=={"item_id":"E010","name":"Camera","category":"Media","borrower_id":""}
    item.loan_to(" M007 "); assert item.borrower_id=="M007" and not item.is_available()
    expect(ValueError,lambda:item.loan_to("M008")); assert item.borrower_id=="M007"
    item.return_item(); expect(ValueError,item.return_item)
    expect(ValueError,lambda:EquipmentItem("","Camera","Media"))

def check_desk():
    desk=LendingDesk(); first=EquipmentItem("E001","Laptop","Computer")
    desk.add_item(first); desk.add_item(EquipmentItem("E002","Projector","Presentation","M002"))
    assert desk.find_item(" E001 ") is first and desk.find_item("missing") is None
    expect(ValueError,lambda:desk.add_item(EquipmentItem("E001","Other","Other")))
    desk.loan_item("E001","M001"); desk.return_item("E002")
    assert desk.summary()=={"total_items":2,"available_items":1,"loaned_items":1}
    expect(KeyError,lambda:desk.loan_item("E999","M001"))

def write_csv(path, fields, rows):
    with path.open("w",newline="",encoding="utf-8") as handle:
        writer=csv.DictWriter(handle,fieldnames=fields); writer.writeheader(); writer.writerows(rows)

def check_other_data():
    with tempfile.TemporaryDirectory() as folder:
        root=Path(folder); inventory=root/"i.csv"; requests=root/"r.csv"
        write_csv(inventory,["item_id","name","category","borrower_id"],[
          {"item_id":"X1","name":"Tablet","category":"Computer","borrower_id":""},
          {"item_id":"X2","name":"Camera","category":"Media","borrower_id":"U9"}])
        write_csv(requests,["request_id","action","item_id","borrower_id"],[
          {"request_id":"Q1","action":"LOAN","item_id":"X1","borrower_id":"U1"},
          {"request_id":"Q2","action":"LOAN","item_id":"X2","borrower_id":"U2"},
          {"request_id":"Q3","action":"RETURN","item_id":"X2","borrower_id":""},
          {"request_id":"Q4","action":"RETURN","item_id":"BAD","borrower_id":""}])
        desk=load_inventory(inventory); results=process_requests(desk,requests)
        assert [r["status"] for r in results]==["ACCEPTED","REJECTED","ACCEPTED","REJECTED"]
        assert desk.find_item("X1").borrower_id=="U1" and desk.find_item("X2").is_available()

def check_project():
    before=(digest(INVENTORY),digest(REQUESTS))
    with tempfile.TemporaryDirectory() as folder:
        root=Path(folder); io=root/"nested"/"inventory.csv"; ro=root/"nested"/"results.csv"
        result=run_project(INVENTORY,REQUESTS,io,ro)
        assert result=={"requests":6,"accepted":3,"rejected":3,
          "total_items":5,"available_items":2,"loaned_items":3}
        with io.open(newline="",encoding="utf-8") as h: rows=list(csv.DictReader(h))
        assert [r["borrower_id"] for r in rows]==["M014","M021","","","M018"]
        with ro.open(newline="",encoding="utf-8") as h: results=list(csv.DictReader(h))
        assert [r["status"] for r in results]==["ACCEPTED","REJECTED","ACCEPTED","ACCEPTED","REJECTED","REJECTED"]
    assert before==(digest(INVENTORY),digest(REQUESTS))

def main():
    if "NotImplementedError" in (PROJECT/"equipment_lending.py").read_text(encoding="utf-8"):
        print("[NG] starter is not complete: finish TODOs and remove every NotImplementedError"); return 1
    for label, check in [("item state",check_item),("desk delegation",check_desk),
                          ("different data",check_other_data),("published project",check_project)]:
        try: check()
        except Exception as error:
            print(f"[NG] {label}: {type(error).__name__}: {error}"); return 1
        print(f"[OK] {label}")
    print("ALL TESTS PASSED"); return 0

if __name__=="__main__": raise SystemExit(main())
