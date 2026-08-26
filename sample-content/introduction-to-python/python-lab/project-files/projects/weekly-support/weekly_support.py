"""Project 1.7: complete this weekly support report program.

Keep the filename, input order, and uppercase output labels unchanged.
Do not change check_weekly_support.py.
"""

total_received = 0
total_resolved = 0
busiest_received = -1
busiest_day = ""
valid_data = True

for day_number in range(1, 6):
    if day_number == 1:
        day_name = "Monday"
    elif day_number == 2:
        day_name = "Tuesday"
    elif day_number == 3:
        day_name = "Wednesday"
    elif day_number == 4:
        day_name = "Thursday"
    else:
        day_name = "Friday"

    received = int(input(f"{day_name} received: "))
    resolved = int(input(f"{day_name} resolved: "))

    # TODO 1: Mark invalid data using the operational rules.
    # TODO 2: Add the two counts to their weekly totals.
    # TODO 3: Update the busiest count and day. Keep the first day on a tie.

# TODO 4: For invalid data, output RESULT: INVALID.
# TODO 5: Handle a week with zero received requests.
# TODO 6: Otherwise calculate unresolved, rate, and status.
# TODO 7: Print every required uppercase output label exactly as specified.

print("\nPROGRAM INCOMPLETE")
