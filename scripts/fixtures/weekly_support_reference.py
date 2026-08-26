"""Reference fixture used only by the direct-submission integration test."""
total_received = 0
total_resolved = 0
busiest_received = -1
busiest_day = "NONE"
invalid = False

for day_number in range(5):
    if day_number == 0:
        day = "Monday"
    elif day_number == 1:
        day = "Tuesday"
    elif day_number == 2:
        day = "Wednesday"
    elif day_number == 3:
        day = "Thursday"
    else:
        day = "Friday"

    received = int(input(f"{day} received: "))
    resolved = int(input(f"{day} resolved: "))
    if received < 0 or resolved < 0 or resolved > received:
        invalid = True
    total_received += received
    total_resolved += resolved
    if received > busiest_received:
        busiest_received = received
        busiest_day = day

if invalid:
    print("RESULT: INVALID")
else:
    print("WEEKLY SUPPORT REPORT")
    print(f"TOTAL RECEIVED: {total_received}")
    print(f"TOTAL RESOLVED: {total_resolved}")
    print(f"UNRESOLVED: {total_received - total_resolved}")
    if total_received == 0:
        print("RESOLUTION RATE: N/A")
        print("STATUS: NO REQUESTS")
        print("BUSIEST DAY: NONE")
    else:
        rate = total_resolved / total_received * 100
        if rate >= 90:
            status = "ON TRACK"
        elif rate >= 80:
            status = "REVIEW"
        else:
            status = "PRIORITY SUPPORT"
        print(f"RESOLUTION RATE: {rate:.1f}%")
        print(f"STATUS: {status}")
        print(f"BUSIEST DAY: {busiest_day}")
