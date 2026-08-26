"""Reference solution for Project 4.5."""
from __future__ import annotations
import csv
from pathlib import Path

FIELDS = ["item_id", "name", "category", "borrower_id"]
RESULT_FIELDS = ["request_id", "action", "item_id", "status", "message"]

def required(value: str, label: str) -> str:
    cleaned = value.strip()
    if not cleaned:
        raise ValueError(f"{label} must not be empty")
    return cleaned

class EquipmentItem:
    def __init__(self, item_id: str, name: str, category: str, borrower_id: str = ""):
        self.item_id = required(item_id, "item_id")
        self.name = required(name, "name")
        self.category = required(category, "category")
        self.borrower_id = borrower_id.strip() or None
    def is_available(self) -> bool:
        return self.borrower_id is None
    def loan_to(self, borrower_id: str) -> None:
        borrower_id = required(borrower_id, "borrower_id")
        if not self.is_available():
            raise ValueError("item is already on loan")
        self.borrower_id = borrower_id
    def return_item(self) -> None:
        if self.is_available():
            raise ValueError("item is not on loan")
        self.borrower_id = None
    def to_record(self) -> dict[str, str]:
        return {"item_id": self.item_id, "name": self.name, "category": self.category,
                "borrower_id": self.borrower_id or ""}

class LendingDesk:
    def __init__(self):
        self.items: dict[str, EquipmentItem] = {}
    def add_item(self, item: EquipmentItem) -> None:
        if not isinstance(item, EquipmentItem):
            raise TypeError("item must be an EquipmentItem")
        if item.item_id in self.items:
            raise ValueError(f"duplicate item ID: {item.item_id}")
        self.items[item.item_id] = item
    def find_item(self, item_id: str) -> EquipmentItem | None:
        return self.items.get(item_id.strip())
    def _required_item(self, item_id: str) -> EquipmentItem:
        item = self.find_item(item_id)
        if item is None:
            raise KeyError(item_id)
        return item
    def loan_item(self, item_id: str, borrower_id: str) -> None:
        self._required_item(item_id).loan_to(borrower_id)
    def return_item(self, item_id: str) -> None:
        self._required_item(item_id).return_item()
    def summary(self) -> dict[str, int]:
        available = sum(item.is_available() for item in self.items.values())
        return {"total_items": len(self.items), "available_items": available,
                "loaned_items": len(self.items) - available}
    def save_inventory(self, path: str | Path) -> None:
        path = Path(path); path.parent.mkdir(parents=True, exist_ok=True)
        with path.open("w", newline="", encoding="utf-8") as handle:
            writer = csv.DictWriter(handle, fieldnames=FIELDS)
            writer.writeheader()
            for item_id in sorted(self.items):
                writer.writerow(self.items[item_id].to_record())

def load_inventory(path: str | Path) -> LendingDesk:
    desk = LendingDesk()
    with Path(path).open(newline="", encoding="utf-8") as handle:
        for row in csv.DictReader(handle):
            desk.add_item(EquipmentItem(row["item_id"], row["name"], row["category"], row["borrower_id"]))
    return desk

def process_requests(desk: LendingDesk, path: str | Path) -> list[dict[str, str]]:
    results = []
    with Path(path).open(newline="", encoding="utf-8") as handle:
        for row in csv.DictReader(handle):
            request_id = required(row["request_id"], "request_id")
            action = required(row["action"], "action").upper()
            item_id = required(row["item_id"], "item_id")
            status, message = "ACCEPTED", "state updated"
            try:
                if action == "LOAN":
                    desk.loan_item(item_id, row["borrower_id"])
                elif action == "RETURN":
                    desk.return_item(item_id)
                else:
                    raise ValueError("unknown action")
            except (ValueError, KeyError) as error:
                status, message = "REJECTED", str(error)
            results.append({"request_id": request_id, "action": action, "item_id": item_id,
                            "status": status, "message": message})
    return results

def save_results(results: list[dict[str, str]], path: str | Path) -> None:
    path = Path(path); path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", newline="", encoding="utf-8") as handle:
        writer = csv.DictWriter(handle, fieldnames=RESULT_FIELDS)
        writer.writeheader(); writer.writerows(results)

def run_project(inventory_path: str | Path, requests_path: str | Path,
                inventory_output: str | Path, results_output: str | Path) -> dict[str, int]:
    desk = load_inventory(inventory_path)
    results = process_requests(desk, requests_path)
    desk.save_inventory(inventory_output); save_results(results, results_output)
    summary = desk.summary()
    summary.update({"requests": len(results),
                    "accepted": sum(r["status"] == "ACCEPTED" for r in results),
                    "rejected": sum(r["status"] == "REJECTED" for r in results)})
    return {"requests": summary["requests"], "accepted": summary["accepted"],
            "rejected": summary["rejected"], **desk.summary()}

def main() -> None:
    project = Path(__file__).resolve().parent
    result = run_project(project/"data"/"equipment_inventory.csv",
        project/"data"/"lending_requests.csv",
        project/"output"/"equipment_inventory_after.csv",
        project/"output"/"lending_results.csv")
    print("EQUIPMENT LENDING REPORT")
    print(f"REQUESTS: {result['requests']}")
    print(f"ACCEPTED: {result['accepted']}")
    print(f"REJECTED: {result['rejected']}")
    print(f"TOTAL ITEMS: {result['total_items']}")
    print(f"AVAILABLE ITEMS: {result['available_items']}")
    print(f"LOANED ITEMS: {result['loaned_items']}")

if __name__ == "__main__":
    main()
