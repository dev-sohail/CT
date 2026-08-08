import smtplib
import imaplib
import email
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.base import MIMEBase
from email import encoders
import logging
import os
import schedule
import time
import json
import oauthlib
from oauth2client.service_account import ServiceAccountCredentials
import ssl
import imaplib
from email.header import decode_header
from typing import Optional

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Email service credentials and OAuth2 setup
GMAIL_USER = 'your_email@gmail.com'
GMAIL_PASSWORD = 'your_app_password'  # Replace with OAuth2 token or app password
IMAP_SERVER = 'imap.gmail.com'
SMTP_SERVER = 'smtp.gmail.com'
IMAP_PORT = 993
SMTP_PORT = 465

# OAuth2 Credentials Setup (Gmail Example)
def get_oauth2_credentials():
    try:
        credentials = ServiceAccountCredentials.from_json_keyfile_name(
            'path_to_your_google_service_account.json', 
            ['https://www.googleapis.com/auth/gmail.send', 'https://www.googleapis.com/auth/gmail.readonly']
        )
        return credentials
    except Exception as e:
        logger.error("Failed to authenticate with OAuth2: %s", e)
        raise

# Email Sending Function with Attachments
def send_email(subject: str, body: str, to_email: str, attachment: Optional[str] = None):
    try:
        msg = MIMEMultipart()
        msg['From'] = GMAIL_USER
        msg['To'] = to_email
        msg['Subject'] = subject

        # Add body text
        msg.attach(MIMEText(body, 'plain'))

        # Attach a file if provided
        if attachment:
            filename = os.path.basename(attachment)
            attachment_part = MIMEBase('application', 'octet-stream')
            with open(attachment, 'rb') as file:
                attachment_part.set_payload(file.read())
            encoders.encode_base64(attachment_part)
            attachment_part.add_header('Content-Disposition', f'attachment; filename={filename}')
            msg.attach(attachment_part)

        # Connect to Gmail SMTP server using SSL
        context = ssl.create_default_context()
        with smtplib.SMTP_SSL(SMTP_SERVER, SMTP_PORT, context=context) as server:
            server.login(GMAIL_USER, GMAIL_PASSWORD)
            text = msg.as_string()
            server.sendmail(GMAIL_USER, to_email, text)
            logger.info(f"Email sent successfully to {to_email}")
    except Exception as e:
        logger.error(f"Failed to send email: {e}")

# Read and Process Emails (Mark as read, display subject, sender, body)
def read_email():
    try:
        mail = imaplib.IMAP4_SSL(IMAP_SERVER)
        mail.login(GMAIL_USER, GMAIL_PASSWORD)
        mail.select('inbox')

        # Search for unseen emails
        status, messages = mail.search(None, 'UNSEEN')
        if status == "OK":
            email_ids = messages[0].split()
            for email_id in email_ids:
                status, data = mail.fetch(email_id, '(RFC822)')
                for response_part in data:
                    if isinstance(response_part, tuple):
                        msg = email.message_from_bytes(response_part[1])
                        subject, encoding = decode_header(msg['subject'])[0]
                        if isinstance(subject, bytes):
                            subject = subject.decode(encoding or 'utf-8')
                        sender = msg['from']
                        logger.info(f"New email from {sender}, Subject: {subject}")
                        
                        # Process email body
                        if msg.is_multipart():
                            for part in msg.walk():
                                content_type = part.get_content_type()
                                content_disposition = str(part.get('Content-Disposition'))
                                if 'attachment' not in content_disposition:
                                    body = part.get_payload(decode=True).decode()
                                    logger.info(f"Email body: {body}")
                        else:
                            body = msg.get_payload(decode=True).decode()
                            logger.info(f"Email body: {body}")
    except Exception as e:
        logger.error(f"Failed to read emails: {e}")

# Email Deletion Function
def delete_email(email_id: str):
    try:
        mail = imaplib.IMAP4_SSL(IMAP_SERVER)
        mail.login(GMAIL_USER, GMAIL_PASSWORD)
        mail.select('inbox')
        mail.store(email_id, '+FLAGS', '\\Deleted')
        mail.expunge()
        logger.info(f"Email with ID {email_id} deleted.")
    except Exception as e:
        logger.error(f"Failed to delete email: {e}")

# Reply to an Email
def reply_to_email(original_email_id: str, reply_body: str):
    try:
        mail = imaplib.IMAP4_SSL(IMAP_SERVER)
        mail.login(GMAIL_USER, GMAIL_PASSWORD)
        mail.select('inbox')
        
        # Fetch the email
        status, data = mail.fetch(original_email_id, '(RFC822)')
        original_msg = email.message_from_bytes(data[0][1])

        # Prepare the reply
        msg = MIMEMultipart()
        msg['From'] = GMAIL_USER
        msg['To'] = original_msg['From']
        msg['Subject'] = 'Re: ' + original_msg['Subject']
        msg.attach(MIMEText(reply_body, 'plain'))
        
        # Connect to SMTP server and send the reply
        context = ssl.create_default_context()
        with smtplib.SMTP_SSL(SMTP_SERVER, SMTP_PORT, context=context) as server:
            server.login(GMAIL_USER, GMAIL_PASSWORD)
            server.sendmail(GMAIL_USER, original_msg['From'], msg.as_string())
            logger.info("Reply sent successfully.")
    except Exception as e:
        logger.error(f"Failed to reply to email: {e}")

# Email Scheduler - Automatically checks emails every 5 minutes
def email_scheduler():
    schedule.every(5).minutes.do(read_email)
    while True:
        schedule.run_pending()
        time.sleep(1)

# Main Entry Point
def main():
    try:
        logger.info("Starting Email Manager")
        # Start Email Scheduler
        email_scheduler()
    except KeyboardInterrupt:
        logger.info("Email Manager stopped by user.")
    except Exception as e:
        logger.error(f"Error: {e}")

if __name__ == "__main__":
    main()
