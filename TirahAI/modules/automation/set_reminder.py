import time
import datetime
import sched
import pyttsx3
from plyer import notification

# Initialize the scheduler
scheduler = sched.scheduler(time.time, time.sleep)

# Initialize text-to-speech engine
engine = pyttsx3.init()

# Helper function for speaking the reminder
def speak_reminder(message):
    engine.say(message)
    engine.runAndWait()

# Helper function to send a system notification
def send_notification(reminder_time, reminder_message):
    notification.notify(
        title="Reminder",
        message=f"Reminder at {reminder_time}: {reminder_message}",
        timeout=10  # Notification duration in seconds
    )

# Function to validate time input format (HH:MM)
def validate_time_input(time_str):
    try:
        reminder_time = datetime.datetime.strptime(time_str, "%H:%M")
        return reminder_time
    except ValueError:
        print("Invalid time format. Please enter time in HH:MM format.")
        return None

# Function to validate the reminder message input
def validate_message_input(message):
    if not message.strip():
        print("Reminder message cannot be empty.")
        return None
    return message.strip()

# Function to schedule a one-time reminder
def set_one_time_reminder(reminder_time, reminder_message):
    # Convert the scheduled time into a datetime object
    now = datetime.datetime.now()
    reminder_time = datetime.datetime.combine(now.date(), reminder_time)
    
    if reminder_time < now:
        reminder_time += datetime.timedelta(days=1)  # Schedule for the next day if the time is in the past

    # Calculate time difference
    time_diff = (reminder_time - now).total_seconds()

    # Schedule the reminder
    scheduler.enter(time_diff, 1, reminder_callback, (reminder_message, reminder_time))

    print(f"Reminder set for {reminder_time.strftime('%H:%M')} - {reminder_message}")
    speak_reminder(f"Reminder set for {reminder_time.strftime('%H:%M')}")

# Callback function to execute when the reminder time arrives
def reminder_callback(reminder_message, reminder_time):
    print(f"Reminder: {reminder_message} at {reminder_time.strftime('%H:%M')}")
    speak_reminder(f"Reminder: {reminder_message}")
    send_notification(reminder_time.strftime("%H:%M"), reminder_message)

# Function to handle recurring reminders
def set_recurring_reminder(reminder_time, reminder_message, interval_minutes=60):
    # Convert the time to datetime object
    now = datetime.datetime.now()
    reminder_time = datetime.datetime.combine(now.date(), reminder_time)
    
    if reminder_time < now:
        reminder_time += datetime.timedelta(days=1)  # Schedule for the next day if the time is in the past
    
    # Calculate the time difference
    time_diff = (reminder_time - now).total_seconds()

    # Schedule the recurring reminder
    def recurring_callback():
        reminder_callback(reminder_message, reminder_time)
        scheduler.enter(interval_minutes * 60, 1, recurring_callback)  # Re-schedule after the interval

    scheduler.enter(time_diff, 1, recurring_callback)
    print(f"Recurring reminder set for {reminder_time.strftime('%H:%M')} - {reminder_message}")
    speak_reminder(f"Recurring reminder set for {reminder_time.strftime('%H:%M')}")

# Function to cancel a scheduled reminder (if needed)
def cancel_reminder(reminder_time, reminder_message):
    # Find and cancel the reminder based on time and message (requires custom management of reminder ids)
    print(f"Reminder for {reminder_time} with message '{reminder_message}' has been canceled.")
    speak_reminder(f"Reminder for {reminder_time} has been canceled.")

# Main function for setting reminders via user input
def main():
    print("Welcome to the reminder system!")
    
    while True:
        # Ask user for the type of reminder
        reminder_type = input("Enter '1' for one-time reminder or '2' for recurring reminder or 'q' to quit: ").strip()

        if reminder_type == 'q':
            print("Exiting reminder system.")
            break

        # Handle the type of reminder
        if reminder_type == '1':
            # One-time reminder
            time_input = input("Enter the reminder time (HH:MM): ").strip()
            reminder_time = validate_time_input(time_input)
            if reminder_time is None:
                continue

            reminder_message = input("Enter the reminder message: ").strip()
            reminder_message = validate_message_input(reminder_message)
            if reminder_message is None:
                continue

            set_one_time_reminder(reminder_time, reminder_message)

        elif reminder_type == '2':
            # Recurring reminder
            time_input = input("Enter the reminder time (HH:MM): ").strip()
            reminder_time = validate_time_input(time_input)
            if reminder_time is None:
                continue

            reminder_message = input("Enter the reminder message: ").strip()
            reminder_message = validate_message_input(reminder_message)
            if reminder_message is None:
                continue

            interval_input = input("Enter the interval in minutes (default 60): ").strip()
            try:
                interval_minutes = int(interval_input) if interval_input else 60
            except ValueError:
                print("Invalid interval input, defaulting to 60 minutes.")
                interval_minutes = 60

            set_recurring_reminder(reminder_time, reminder_message, interval_minutes)

        else:
            print("Invalid input. Please enter '1' or '2'.")
        
        # Process any scheduled events
        scheduler.run()

if __name__ == "__main__":
    main()
