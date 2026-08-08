from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.events import EVENT_JOB_EXECUTED, EVENT_JOB_ERROR
from apscheduler.triggers.interval import IntervalTrigger
from apscheduler.triggers.cron import CronTrigger
import time
import logging
import psutil
import os
import platform
from datetime import datetime
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

# Initialize the logger for task scheduler
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Scheduler instance
scheduler = BackgroundScheduler()

# Simple task to print the current time (for testing purposes)
def print_time():
    logger.info(f"Current time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

# Example task: Open a specific application (like a browser)
def open_application(app_name):
    logger.info(f"Opening application: {app_name}")
    if platform.system() == "Windows":
        os.startfile(app_name)  # On Windows
    else:
        os.system(f"open -a {app_name}")  # For macOS, change for Linux accordingly

# Example task: Check system memory usage and log it
def check_system_memory():
    memory = psutil.virtual_memory()
    logger.info(f"System Memory Usage: {memory.percent}%")

# Email notification on job error or completion
def send_email(subject, body):
    try:
        msg = MIMEMultipart()
        msg['From'] = "your_email@example.com"
        msg['To'] = "recipient_email@example.com"
        msg['Subject'] = subject

        msg.attach(MIMEText(body, 'plain'))

        with smtplib.SMTP('smtp.example.com', 587) as server:
            server.starttls()
            server.login("your_email@example.com", "your_password")
            text = msg.as_string()
            server.sendmail("your_email@example.com", "recipient_email@example.com", text)
        logger.info("Email sent successfully.")
    except Exception as e:
        logger.error(f"Failed to send email: {e}")

# Event listeners for job completion or failure
def job_listener(event):
    if event.exception:
        logger.error(f"Job {event.job_id} failed")
        send_email("Job Failed", f"Job {event.job_id} failed with error: {event.exception}")
    else:
        logger.info(f"Job {event.job_id} completed successfully")
        send_email("Job Completed", f"Job {event.job_id} completed successfully")

# Add job to scheduler with different triggers (Interval, Cron)
def add_task_to_scheduler(task_function, trigger_type, trigger_args, job_name):
    if trigger_type == "interval":
        trigger = IntervalTrigger(seconds=trigger_args["seconds"])  # Add more options as needed (minutes, hours)
    elif trigger_type == "cron":
        trigger = CronTrigger(minute=trigger_args["minute"], hour=trigger_args["hour"], day_of_week=trigger_args["day_of_week"])
    else:
        logger.error("Invalid trigger type. Use 'interval' or 'cron'.")
        return

    scheduler.add_job(task_function, trigger, id=job_name, name=job_name, replace_existing=True)
    logger.info(f"Scheduled task {job_name} with {trigger_type} trigger.")

# Add sample tasks to be scheduled
def setup_sample_tasks():
    # Task 1: Print time every 10 seconds
    add_task_to_scheduler(print_time, "interval", {"seconds": 10}, "print_time_task")

    # Task 2: Open 'Google Chrome' every 30 minutes (assuming it's installed)
    add_task_to_scheduler(open_application, "interval", {"seconds": 1800}, "open_chrome_task")

    # Task 3: Check system memory every minute
    add_task_to_scheduler(check_system_memory, "interval", {"seconds": 60}, "check_memory_task")

    # Task 4: Run a cron job to print time every hour at minute 0
    add_task_to_scheduler(print_time, "cron", {"minute": 0, "hour": "*", "day_of_week": "*"}, "cron_time_task")

# Start the scheduler
def start_scheduler():
    logger.info("Starting the task scheduler.")
    scheduler.add_listener(job_listener, EVENT_JOB_EXECUTED | EVENT_JOB_ERROR)
    scheduler.start()

# Stop the scheduler gracefully
def stop_scheduler():
    logger.info("Stopping the task scheduler.")
    scheduler.shutdown()

# Main entry point to test the scheduler
if __name__ == "__main__":
    try:
        setup_sample_tasks()  # Setup sample tasks
        start_scheduler()  # Start the scheduler to execute tasks

        # Keep the program running to allow background jobs to execute
        while True:
            time.sleep(1)

    except (KeyboardInterrupt, SystemExit):
        stop_scheduler()
        logger.info("Scheduler stopped gracefully.")
