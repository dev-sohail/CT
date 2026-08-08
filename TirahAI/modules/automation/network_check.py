import os
import socket
import subprocess
import platform
import requests
import time
import logging

# Set up logging for detailed debugging and monitoring
logging.basicConfig(level=logging.DEBUG, format='%(asctime)s - %(levelname)s - %(message)s')

# Constants
PING_TARGET = "8.8.8.8"  # Google's DNS server as a reliable test target
TIMEOUT = 3  # Timeout for network checks in seconds
DNS_SERVER = "8.8.8.8"  # Google DNS server

class NetworkCheck:
    def __init__(self):
        self.is_connected = False
        self.ip_address = None
        self.hostname = None
        self.local_ip = None

    def check_network_connection(self):
        """Check if the system is connected to the network by pinging a reliable server."""
        logging.info("Checking network connectivity...")

        # Depending on the OS, we use different ping commands
        if platform.system().lower() == "windows":
            command = ["ping", "-n", "1", PING_TARGET]
        else:
            command = ["ping", "-c", "1", PING_TARGET]

        try:
            subprocess.check_call(command, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            self.is_connected = True
            logging.info("Network is reachable!")
        except subprocess.CalledProcessError:
            self.is_connected = False
            logging.error("Network is not reachable!")
        
        return self.is_connected

    def check_dns_resolution(self):
        """Check if DNS resolution is working by querying a known domain (e.g., google.com)."""
        logging.info("Checking DNS resolution...")
        try:
            socket.gethostbyname("google.com")
            logging.info("DNS resolution is working!")
            return True
        except socket.gaierror:
            logging.error("DNS resolution failed!")
            return False

    def get_local_ip(self):
        """Get the local machine's IP address."""
        logging.info("Fetching local IP address...")
        try:
            self.local_ip = socket.gethostbyname(socket.gethostname())
            logging.info(f"Local IP Address: {self.local_ip}")
            return self.local_ip
        except socket.error:
            logging.error("Failed to retrieve local IP address.")
            return None

    def check_internet_speed(self):
        """Check internet speed using a public API or a known service."""
        logging.info("Checking internet speed...")
        try:
            response = requests.get("https://www.google.com", timeout=TIMEOUT)
            if response.status_code == 200:
                logging.info(f"Internet is working, Status Code: {response.status_code}")
                return True
            else:
                logging.warning(f"Non-200 status code received: {response.status_code}")
                return False
        except requests.exceptions.RequestException as e:
            logging.error(f"Error while checking internet speed: {e}")
            return False

    def ping_test(self, target=PING_TARGET):
        """Ping a given target and return the results."""
        logging.info(f"Pinging {target}...")
        response = os.system(f"ping -c 1 {target}")
        if response == 0:
            logging.info(f"Ping to {target} successful!")
            return True
        else:
            logging.error(f"Ping to {target} failed!")
            return False

    def check_router_connection(self):
        """Check if the device can connect to the router (local network)."""
        logging.info("Checking router connection...")
        router_ip = "192.168.1.1"  # Default router IP for most home routers
        return self.ping_test(router_ip)

    def log_network_details(self):
        """Log detailed network information."""
        logging.info("Logging detailed network information...")
        self.get_local_ip()
        if self.check_network_connection():
            self.check_dns_resolution()
            self.check_internet_speed()
            self.ping_test()
        else:
            logging.error("Network is down. Cannot log detailed network info.")
    
    def troubleshoot_network(self):
        """Try to troubleshoot the network by checking basic aspects."""
        logging.info("Troubleshooting network...")
        
        if not self.check_network_connection():
            logging.warning("Network is down. Attempting to troubleshoot.")
            if not self.check_router_connection():
                logging.error("Router is unreachable. Please check your router.")
            if not self.check_dns_resolution():
                logging.error("DNS resolution failed. Check your DNS settings.")
            if not self.check_internet_speed():
                logging.error("No internet access. Please check your connection.")
        else:
            logging.info("Network seems fine.")
    
    def get_network_info(self):
        """Return the current network status and other relevant info."""
        network_info = {
            "Connected": self.is_connected,
            "Local IP": self.local_ip,
            "Hostname": socket.gethostname(),
            "DNS Resolution": self.check_dns_resolution(),
            "Internet Speed": self.check_internet_speed()
        }
        return network_info


if __name__ == "__main__":
    network = NetworkCheck()

    # Log network details and troubleshoot
    network.log_network_details()
    network.troubleshoot_network()

    # Print out network info for the user
    info = network.get_network_info()
    print("Network Status:", info)
