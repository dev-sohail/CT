import os
import subprocess
import platform
import logging
import sys
import json
import shutil
from datetime import datetime
import speech_recognition as sr

# Setup logging
logging.basicConfig(
    filename="app_launcher.log",
    level=logging.DEBUG,
    format="%(asctime)s - %(levelname)s - %(message)s",
)
logger = logging.getLogger()

# Load configuration (app list from JSON file)
def load_app_config(config_file="app_config.json"):
    """Load app configurations from a JSON file."""
    if not os.path.exists(config_file):
        logger.error("Config file not found, creating default config.")
        return {}
    
    with open(config_file, "r") as f:
        return json.load(f)

# Save configuration (user-defined apps and paths)
def save_app_config(config_file="app_config.json", app_dict={}):
    """Save app configurations to a JSON file."""
    with open(config_file, "w") as f:
        json.dump(app_dict, f, indent=4)
    logger.info(f"Saved app configuration to {config_file}.")

# Dynamic path resolver based on system platform
def resolve_app_path(app_name):
    """Try to resolve the path of the app dynamically based on the system platform."""
    system_platform = platform.system().lower()

    if system_platform == "windows":
        return find_app_windows(app_name)
    elif system_platform == "darwin":  # macOS
        return find_app_mac(app_name)
    elif system_platform == "linux":
        return find_app_linux(app_name)
    else:
        logger.error(f"Unsupported platform: {system_platform}")
        return None

# Windows-specific app search
def find_app_windows(app_name):
    """Search for an app in common Windows directories."""
    possible_dirs = [
        "C:/Program Files/",
        "C:/Program Files (x86)/",
        "C:/Users/Public/AppData/",
    ]
    for directory in possible_dirs:
        for root, dirs, files in os.walk(directory):
            if app_name.lower() in (file.lower() for file in files):
                return os.path.join(root, [file for file in files if app_name.lower() in file.lower()][0])
    return None

# macOS-specific app search
def find_app_mac(app_name):
    """Search for an app in common macOS directories."""
    possible_dirs = ["/Applications"]
    for directory in possible_dirs:
        for root, dirs, files in os.walk(directory):
            if app_name.lower() in (file.lower() for file in files):
                return os.path.join(root, [file for file in files if app_name.lower() in file.lower()][0])
    return None

# Linux-specific app search
def find_app_linux(app_name):
    """Search for an app in common Linux directories."""
    possible_dirs = ["/usr/bin/", "/usr/local/bin/", "/opt/"]
    for directory in possible_dirs:
        if shutil.which(app_name):
            return shutil.which(app_name)
    return None

# Open an application with a valid path
def open_application(app_name):
    """Open an application based on resolved path."""
    app_path = resolve_app_path(app_name)
    
    if app_path and os.path.exists(app_path):
        try:
            subprocess.run([app_path], check=True)
            logger.info(f"Opened {app_name} successfully at {app_path}.")
            return f"Successfully opened {app_name}."
        except Exception as e:
            logger.error(f"Error opening {app_name}: {str(e)}")
            return f"Error opening {app_name}. Please check logs for details."
    else:
        logger.error(f"{app_name} not found.")
        return f"Error: {app_name} could not be found on your system."

# Voice recognition for dynamic app opening (optional)
def listen_for_voice_command():
    """Listen for voice input to open an app."""
    recognizer = sr.Recognizer()
    with sr.Microphone() as source:
        print("Listening for app command...")
        audio = recognizer.listen(source)
        try:
            command = recognizer.recognize_google(audio).lower()
            print(f"Voice Command: {command}")
            return command
        except sr.UnknownValueError:
            print("Could not understand the audio.")
            return None
        except sr.RequestError:
            print("Could not request results.")
            return None

# Handle user input for opening apps (both voice and command line)
def process_user_input(command):
    """Process the command and attempt to open the requested application."""
    try:
        command = command.lower()
        if "open" in command:
            app_name = command.replace("open", "").strip()
            response = open_application(app_name)
            return response
        else:
            return "Sorry, I only understand commands like 'open <app_name>'."
    except Exception as e:
        logger.error(f"Error processing command: {str(e)}")
        return "Sorry, I couldn't process the command."

# Command-line interface to test the functionality
if __name__ == "__main__":
    # Load app configuration from file
    app_config = load_app_config()

    # Test based on command-line or voice
    if len(sys.argv) > 1:
        user_command = " ".join(sys.argv[1:])
        print(process_user_input(user_command))
    else:
        # You can enable voice input here by calling listen_for_voice_command()
        user_command = listen_for_voice_command()
        if user_command:
            print(process_user_input(user_command))
        else:
            print("Usage: python open_app.py 'open <app_name>'")
