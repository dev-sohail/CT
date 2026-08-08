import smtplib
import ssl
import os
import logging
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.base import MIMEBase
from email import encoders
from imaplib import IMAP4_SSL
from email.header import decode_header
from email.utils import parseaddr
import json

try:
    from oauth2client.service_account import ServiceAccountCredentials
except Exception:
    ServiceAccountCredentials = None

# Set up logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Email Configuration
class EmailConfig:
    def __init__(self, config_file="config.json"):
        with open(config_file, "r") as f:
            self.config = json.load(f)
        self.smtp_server = self.config["smtp_server"]
        self.smtp_port = self.config["smtp_port"]
        self.imap_server = self.config["imap_server"]
        self.imap_port = self.config["imap_port"]
        self.username = self.config["username"]
        self.password = self.config["password"]
        self.oauth2_credentials_file = self.config.get("oauth2_credentials_file", None)

    def get_oauth2_credentials(self):
        if not ServiceAccountCredentials:
            logger.error("oauth2client not installed")
            return None
        if self.oauth2_credentials_file:
            credentials = ServiceAccountCredentials.from_json_keyfile_name(
                self.oauth2_credentials_file, scopes=["https://www.googleapis.com/auth/gmail.readonly"]
            )
            return credentials
        else:
            logger.error("OAuth2 credentials file is not specified in the configuration.")
            return None


# Email Sending Class
class EmailSender:
    def __init__(self, config: EmailConfig):
        self.config = config

    def send_email(self, to_address, subject, body, attachments=None, is_html=False):
        try:
            msg = MIMEMultipart()
            msg['From'] = self.config.username
            msg['To'] = to_address
            msg['Subject'] = subject

            if is_html:
                msg.attach(MIMEText(body, 'html'))
            else:
                msg.attach(MIMEText(body, 'plain'))

            # Add attachments
            if attachments:
                for attachment in attachments:
                    part = MIMEBase('application', 'octet-stream')
                    with open(attachment, 'rb') as file:
                        part.set_payload(file.read())
                    encoders.encode_base64(part)
                    part.add_header('Content-Disposition', f'attachment; filename={os.path.basename(attachment)}')
                    msg.attach(part)

            # Establish SMTP connection with SSL
            context = ssl.create_default_context()
            with smtplib.SMTP_SSL(self.config.smtp_server, self.config.smtp_port, context=context) as server:
                server.login(self.config.username, self.config.password)
                server.sendmail(self.config.username, to_address, msg.as_string())

            logger.info(f"Email sent to {to_address} successfully.")
        except Exception as e:
            logger.error(f"Failed to send email: {e}")


# Email Reading Class
class EmailReader:
    def __init__(self, config: EmailConfig):
        self.config = config

    def read_emails(self, number_of_emails=5):
        try:
            # Connect to the IMAP server
            with IMAP4_SSL(self.config.imap_server, self.config.imap_port) as server:
                server.login(self.config.username, self.config.password)
                server.select('inbox')

                # Search for all emails
                status, messages = server.search(None, 'ALL')

                # Get email IDs
                email_ids = messages[0].split()[-number_of_emails:]

                emails = []
                for email_id in email_ids:
                    status, msg_data = server.fetch(email_id, '(RFC822)')
                    for response_part in msg_data:
                        if isinstance(response_part, tuple):
                            msg = email.message_from_bytes(response_part[1])
                            subject, encoding = decode_header(msg['Subject'])[0]
                            if isinstance(subject, bytes):
                                subject = subject.decode(encoding or 'utf-8')
                            from_ = parseaddr(msg['From'])[1]

                            email_info = {
                                "subject": subject,
                                "from": from_,
                                "body": self.extract_body(msg),
                                "attachments": self.extract_attachments(msg),
                            }
                            emails.append(email_info)
                logger.info(f"Fetched {len(emails)} emails.")
                return emails
        except Exception as e:
            logger.error(f"Failed to read emails: {e}")
            return []

    def extract_body(self, msg):
        if msg.is_multipart():
            for part in msg.walk():
                content_type = part.get_content_type()
                if content_type == "text/plain":
                    return part.get_payload(decode=True).decode()
                elif content_type == "text/html":
                    return part.get_payload(decode=True).decode()
        else:
            return msg.get_payload(decode=True).decode()

    def extract_attachments(self, msg):
        attachments = []
        if msg.is_multipart():
            for part in msg.walk():
                content_type = part.get_content_type()
                if part.get('Content-Disposition'):
                    file_name = part.get_filename()
                    if file_name:
                        file_path = os.path.join("attachments", file_name)
                        with open(file_path, 'wb') as file:
                            file.write(part.get_payload(decode=True))
                        attachments.append(file_path)
        return attachments


# Email service orchestration
class EmailService:
    def __init__(self, config_file="config.json"):
        self.config = EmailConfig(config_file)
        self.sender = EmailSender(self.config)
        self.reader = EmailReader(self.config)

    def send(self, to_address, subject, body, attachments=None, is_html=False):
        self.sender.send_email(to_address, subject, body, attachments, is_html)

    def receive(self, number_of_emails=5):
        return self.reader.read_emails(number_of_emails)


# Example Usage
if __name__ == "__main__":
    # Initialize email service
    email_service = EmailService()

    # Sending an email
    subject = "Test Email from JARVIS"
    body = "<h1>Hello from JARVIS!</h1><p>This is a test email sent from the AI assistant.</p>"
    to_address = "recipient@example.com"
    email_service.send(to_address, subject, body, is_html=True)

    # Reading emails
    emails = email_service.receive(3)
    for email in emails:
        print(f"Subject: {email['subject']}, From: {email['from']}")
        print(f"Body: {email['body']}")
        print(f"Attachments: {email['attachments']}")
