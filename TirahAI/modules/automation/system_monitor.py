import psutil
import time
import logging
from plyer import notification
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

# Configure logging
logging.basicConfig(filename="system_monitor.log", level=logging.INFO,
                    format="%(asctime)s - %(levelname)s - %(message)s")

# Threshold values
CPU_THRESHOLD = 80  # CPU usage threshold in percentage
RAM_THRESHOLD = 80  # RAM usage threshold in percentage
DISK_THRESHOLD = 90  # Disk usage threshold in percentage
BATTERY_THRESHOLD = 20  # Battery health threshold for laptops
NETWORK_THRESHOLD = 1  # Threshold for network speed (in Mbps)

# Email settings for alerts (optional)
SENDER_EMAIL = "your_email@example.com"
RECEIVER_EMAIL = "receiver_email@example.com"
EMAIL_PASSWORD = "your_email_password"
SMTP_SERVER = "smtp.example.com"
SMTP_PORT = 587


# Function to send email alerts
def send_email_alert(subject, body):
    try:
        msg = MIMEMultipart()
        msg['From'] = SENDER_EMAIL
        msg['To'] = RECEIVER_EMAIL
        msg['Subject'] = subject
        msg.attach(MIMEText(body, 'plain'))
        
        server = smtplib.SMTP(SMTP_SERVER, SMTP_PORT)
        server.starttls()
        server.login(SENDER_EMAIL, EMAIL_PASSWORD)
        text = msg.as_string()
        server.sendmail(SENDER_EMAIL, RECEIVER_EMAIL, text)
        server.quit()
        logging.info(f"Email sent to {RECEIVER_EMAIL}")
    except Exception as e:
        logging.error(f"Error sending email: {e}")


# Function to check CPU usage
def check_cpu_usage():
    cpu_usage = psutil.cpu_percent(interval=1)
    logging.info(f"CPU Usage: {cpu_usage}%")
    if cpu_usage > CPU_THRESHOLD:
        alert_message = f"High CPU usage detected: {cpu_usage}%"
        logging.warning(alert_message)
        send_email_alert("CPU Usage Alert", alert_message)
        notification.notify(title="CPU Usage Alert", message=alert_message)


# Function to check RAM usage
def check_ram_usage():
    ram_usage = psutil.virtual_memory
