import paho.mqtt.client as mqtt
import time
import json
from datetime import datetime
from threading import Thread
import speech_recognition as sr
import pyttsx3
import random

# Configuration: MQTT, Smart Home Devices, etc.
MQTT_BROKER = "mqtt.eclipse.org"  # Can replace with your local broker
MQTT_PORT = 1883
MQTT_TOPIC_PREFIX = "smart_home/"
SMART_DEVICES = {
    "living_room_light": {"status": "off", "type": "light", "pin": 12},
    "kitchen_thermostat": {"status": "off", "type": "thermostat", "temperature": 22},
    "security_camera": {"status": "inactive", "type": "camera"},
    "smart_door_lock": {"status": "locked", "type": "lock"},
}

# Smart home system status logging
LOG_FILE = "smart_home_log.txt"

# MQTT Client Setup
client = mqtt.Client()

# Initialize Text-to-Speech Engine
engine = pyttsx3.init()

def log_to_file(message):
    """Logs activities to a file with timestamp"""
    with open(LOG_FILE, "a") as log:
        log.write(f"{datetime.now()} - {message}\n")
    print(message)  # Also print to console for real-time feedback

def on_connect(client, userdata, flags, rc):
    """Callback when connected to the MQTT broker"""
    if rc == 0:
        log_to_file("Connected to MQTT broker successfully")
        client.subscribe(MQTT_TOPIC_PREFIX + "#")  # Subscribe to all devices topics
    else:
        log_to_file(f"Failed to connect to MQTT Broker. Error Code: {rc}")

def on_message(client, userdata, msg):
    """Callback when a message is received from MQTT"""
    device = msg.topic.replace(MQTT_TOPIC_PREFIX, "")
    payload = json.loads(msg.payload.decode())

    if device in SMART_DEVICES:
        if payload.get("action") == "toggle":
            toggle_device(device)
        elif payload.get("action") == "set":
            set_device_value(device, payload)
    else:
        log_to_file(f"Unknown device: {device} tried to send a message")

def toggle_device(device):
    """Toggle the device on/off or perform related action"""
    if device not in SMART_DEVICES:
        log_to_file(f"Device {device} not found in smart home system.")
        return

    dev_info = SMART_DEVICES[device]
    if dev_info["type"] == "light":
        new_status = "on" if dev_info["status"] == "off" else "off"
        dev_info["status"] = new_status
        log_to_file(f"Toggled {device}: {new_status}")
        # Add any additional actions here, like sending a signal to a physical device

    elif dev_info["type"] == "thermostat":
        new_temp = dev_info["temperature"] + 1 if dev_info["status"] == "off" else 22
        dev_info["status"] = "on" if dev_info["status"] == "off" else "off"
        dev_info["temperature"] = new_temp
        log_to_file(f"Set {device}: {new_temp}°C")
        # Adjust temperature logic here

    elif dev_info["type"] == "camera":
        new_status = "active" if dev_info["status"] == "inactive" else "inactive"
        dev_info["status"] = new_status
        log_to_file(f"Toggled {device}: {new_status}")
        # Add camera on/off logic here

    elif dev_info["type"] == "lock":
        new_status = "unlocked" if dev_info["status"] == "locked" else "locked"
        dev_info["status"] = new_status
        log_to_file(f"Toggled {device}: {new_status}")
        # Add lock/unlock logic here

    # Publish status to MQTT
    publish_device_status(device)

def set_device_value(device, payload):
    """Set a specific value for a device, like temperature or brightness"""
    if device not in SMART_DEVICES:
        log_to_file(f"Device {device} not found in smart home system.")
        return

    dev_info = SMART_DEVICES[device]
    if device == "living_room_light" and "brightness" in payload:
        log_to_file(f"Setting brightness of {device} to {payload['brightness']}")
        # Adjust brightness here (e.g., via PWM or API call)
    elif device == "kitchen_thermostat" and "temperature" in payload:
        dev_info["temperature"] = payload["temperature"]
        log_to_file(f"Set {device} temperature to {payload['temperature']}°C")
    elif device == "security_camera" and "status" in payload:
        dev_info["status"] = payload["status"]
        log_to_file(f"Set {device} status to {payload['status']}")
    elif device == "smart_door_lock" and "status" in payload:
        dev_info["status"] = payload["status"]
        log_to_file(f"Set {device} status to {payload['status']}")

    # Publish status to MQTT
    publish_device_status(device)

def publish_device_status(device):
    """Publish the status of a device to MQTT"""
    if device not in SMART_DEVICES:
        log_to_file(f"Device {device} does not exist.")
        return

    status_message = SMART_DEVICES[device]
    client.publish(MQTT_TOPIC_PREFIX + device, json.dumps(status_message))
    log_to_file(f"Published {device} status: {status_message}")

def scheduler_task():
    """Scheduler for tasks like daily routines or auto-control"""
    while True:
        current_time = datetime.now().strftime("%H:%M")
        if current_time == "08:00":  # Example: Set temperature at 8 AM
            set_device_value("kitchen_thermostat", {"temperature": 22})
            time.sleep(60)  # Wait to avoid repetitive actions in the same minute
        time.sleep(60)

def voice_control(command):
    """Handle voice commands for home automation"""
    log_to_file(f"Voice Command: {command}")

    if "turn on" in command:
        if "light" in command:
            toggle_device("living_room_light")
        elif "camera" in command:
            toggle_device("security_camera")
        elif "lock" in command:
            toggle_device("smart_door_lock")
    elif "turn off" in command:
        if "light" in command:
            toggle_device("living_room_light")
        elif "camera" in command:
            toggle_device("security_camera")
        elif "lock" in command:
            toggle_device("smart_door_lock")
    elif "set temperature" in command:
        temperature = int(command.split()[-2])
        set_device_value("kitchen_thermostat", {"temperature": temperature})
    elif "status" in command:
        check_status()

def check_status():
    """Check and report the status of all devices"""
    for device, details in SMART_DEVICES.items():
        log_to_file(f"{device} is {details['status']}")

def listen_for_commands():
    """Listen for voice commands via Speech-to-Text"""
    recognizer = sr.Recognizer()
    mic = sr.Microphone()

    while True:
        with mic as source:
            recognizer.adjust_for_ambient_noise(source)
            print("Listening for command...")
            audio = recognizer.listen(source)

        try:
            command = recognizer.recognize_google(audio).lower()
            voice_control(command)
        except sr.UnknownValueError:
            print("Sorry, I couldn't understand. Please try again.")
        except sr.RequestError:
            print("Could not request results; check your internet connection.")

def start_smart_home_system():
    """Initialize smart home system"""
    client.on_connect = on_connect
    client.on_message = on_message
    client.connect(MQTT_BROKER, MQTT_PORT, 60)
    client.loop_start()  # Start the MQTT client in a non-blocking way

    # Start scheduler task for daily automation
    scheduler_thread = Thread(target=scheduler_task)
    scheduler_thread.daemon = True  # Run as background thread
    scheduler_thread.start()

    # Start listening for voice commands
    voice_thread = Thread(target=listen_for_commands)
    voice_thread.daemon = True  # Run as background thread
    voice_thread.start()

    log_to_file("Smart Home System Started")

    # Keep the main thread running
    while True:
        time.sleep(1)

if __name__ == "__main__":
    start_smart_home_system()
