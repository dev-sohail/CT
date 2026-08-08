import schedule
import time
import datetime
import pytz
import threading
import os
from playsound import playsound
from datetime import timedelta

# Function to log tasks and events with timestamps
def log_event(message):
    timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with open('task_log.txt', 'a') as log_file:
        log_file.write(f"[{timestamp}] {message}\n")

# Function to display current system time
def show_current_time():
    current_time = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"Current system time: {current_time}")
    return current_time

# Function to handle time-based tasks (reminder, alarms)
def set_reminder(reminder_time, message):
    log_event(f"Reminder set: {message} at {reminder_time}")
    print(f"Reminder: '{message}' set for {reminder_time}")

    # Calculate the difference between now and the reminder time
    while datetime.datetime.now() < reminder_time:
        time.sleep(1)  # Check every second

    print(f"Reminder: {message}")
    playsound('alarm_sound.mp3')  # Play alarm sound (replace with your file path)
    log_event(f"Reminder triggered: {message} at {datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

# Function to schedule tasks at a specific time
def schedule_task(task, time_str):
    # Schedule the task at the specified time
    schedule.every().day.at(time_str).do(task)
    log_event(f"Task scheduled at {time_str}")
    while True:
        schedule.run_pending()
        time.sleep(1)  # Check for pending tasks every second

# Convert current time to different time zones
def convert_to_timezone(timezone_str):
    local_time = datetime.datetime.now(pytz.timezone(timezone_str))
    print(f"Current time in {timezone_str}: {local_time.strftime('%Y-%m-%d %H:%M:%S')}")
    return local_time

# Function to set an alarm at a specific time
def set_alarm(alarm_time, alarm_message="Time's up!"):
    current_time = datetime.datetime.now()
    log_event(f"Alarm set: {alarm_message} at {alarm_time}")
    print(f"Alarm set for {alarm_time}")

    # Calculate the difference between now and the alarm time
    while current_time < alarm_time:
        time.sleep(1)  # Check every second

    print(f"ALERT: {alarm_message}")
    playsound('alarm_sound.mp3')  # Play alarm sound (replace with your file path)
    log_event(f"Alarm triggered: {alarm_message} at {datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

# Function to run time-based tasks asynchronously
def run_in_background(function, *args):
    thread = threading.Thread(target=function, args=args)
    thread.daemon = True  # Ensure the thread exits when the main program ends
    thread.start()

# Example of a task function
def task_example():
    print("Running task example...")
    log_event("Task example executed at " + datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"))

# Main function to initialize time operations
def main():
    # Set up a reminder example
    reminder_time = datetime.datetime.now() + timedelta(seconds=10)  # 10 seconds from now
    run_in_background(set_reminder, reminder_time, "Take a break!")

    # Set up an alarm example (in 20 seconds)
    alarm_time = datetime.datetime.now() + timedelta(seconds=20)  # 20 seconds from now
    run_in_background(set_alarm, alarm_time)

    # Show current time
    show_current_time()

    # Convert time to a different timezone (e.g., UTC)
    convert_to_timezone("UTC")

    # Schedule a daily task (e.g., running task_example every day at 12:00 PM)
    run_in_background(schedule_task, task_example, "12:00")

    # Keep the program running to allow background tasks to work
    while True:
        time.sleep(1)

if __name__ == "__main__":
    main()
