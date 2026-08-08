"""
IoT Device Integration Module for TirahAi
Handles smart home device control via MQTT protocol
"""

import json
import logging
import os
import time
import threading
from typing import Dict, List, Optional, Any

try:
    import paho.mqtt.client as mqtt
except ImportError:
    mqtt = None

try:
    from config.settings import IOT_CONFIG as _IOT
except Exception:
    _IOT = {}
from utils.logger import get_logger

logger = get_logger(__name__)

_DEVICE_REGISTRY_PATH = os.path.expanduser("~/.tirahai/iot_devices.json")


class IoTDevice:
    """Base class for IoT devices."""

    def __init__(self, device_id: str, name: str):
        """
        Initialize IoT device.

        Args:
            device_id (str): Unique device identifier
            name (str): Device name
        """
        self.device_id = device_id
        self.name = name
        self.status = "unknown"

    def get_status(self) -> str:
        """Get device status."""
        return self.status


class SmartBulb(IoTDevice):
    """Smart bulb/light device."""

    def __init__(self, device_id: str, name: str):
        """Initialize smart bulb."""
        super().__init__(device_id, name)
        self.state = "off"
        self.brightness = 100

    def toggle(self) -> None:
        """Toggle light on/off."""
        self.state = "on" if self.state == "off" else "off"
        logger.info(f"Bulb {self.name} toggled to {self.state}")

    def set_brightness(self, brightness: int) -> None:
        """
        Set bulb brightness.

        Args:
            brightness (int): Brightness level (0-100)
        """
        self.brightness = max(0, min(100, brightness))
        logger.info(f"Bulb {self.name} brightness set to {self.brightness}%")

    def get_status(self) -> str:
        """Get bulb status."""
        return f"{self.state} (brightness: {self.brightness}%)"


class SmartThermostat(IoTDevice):
    """Smart thermostat device."""

    def __init__(self, device_id: str, name: str):
        """Initialize thermostat."""
        super().__init__(device_id, name)
        self.temperature = 20.0
        self.mode = "auto"

    def set_temperature(self, temperature: float) -> None:
        """
        Set target temperature.

        Args:
            temperature (float): Target temperature in Celsius
        """
        self.temperature = max(10.0, min(35.0, temperature))
        logger.info(f"Thermostat {self.name} set to {self.temperature}°C")

    def set_mode(self, mode: str) -> None:
        """
        Set thermostat mode.

        Args:
            mode (str): Operating mode (auto, heat, cool, off)
        """
        valid_modes = ["auto", "heat", "cool", "off"]
        if mode.lower() in valid_modes:
            self.mode = mode.lower()
            logger.info(f"Thermostat {self.name} mode set to {self.mode}")

    def toggle(self) -> None:
        """Toggle thermostat on/off (switches between 'off' and 'auto')."""
        if self.mode == 'off':
            self.mode = 'auto'
        else:
            self.mode = 'off'
        logger.info(f"Thermostat {self.name} toggled to {self.mode}")

    def get_status(self) -> str:
        """Get thermostat status."""
        return f"{self.temperature}°C (mode: {self.mode})"


class SmartFan(IoTDevice):
    """Smart fan device."""

    def __init__(self, device_id: str, name: str):
        """Initialize smart fan."""
        super().__init__(device_id, name)
        self.state = "off"
        self.speed = 1

    def toggle(self) -> None:
        """Toggle fan on/off."""
        self.state = "on" if self.state == "off" else "off"
        logger.info(f"Fan {self.name} toggled to {self.state}")

    def set_speed(self, speed: int) -> None:
        """
        Set fan speed.

        Args:
            speed (int): Speed level (1-5)
        """
        self.speed = max(1, min(5, speed))
        logger.info(f"Fan {self.name} speed set to {self.speed}")

    def get_status(self) -> str:
        """Get fan status."""
        return f"{self.state} (speed: {self.speed})"


class IoTManager:
    """Manages IoT devices and MQTT communication."""

    def __init__(self):
        """Initialize IoT manager."""
        self.devices: Dict[str, IoTDevice] = {}
        self.mqtt_client = None
        self.connected = False
        self._discover_event = threading.Event()

        # MQTT Configuration
        self.broker = _IOT.get('broker_host', 'broker.hivemq.com')
        self.port = _IOT.get('broker_port', 1883)
        self.topic = _IOT.get('topic', 'tirahai/iot')
        self.device_id = _IOT.get('device_id', 'tirah_main_unit')

        if mqtt:
            self.setup_mqtt()
        else:
            logger.warning("paho-mqtt not installed. IoT functionality limited.")

        self._restore_devices()
        logger.info("IoT Manager initialized")

    def setup_mqtt(self):
        """Setup MQTT client."""
        try:
            self.mqtt_client = mqtt.Client(client_id=self.device_id)
            self.mqtt_client.on_connect = self.on_connect
            self.mqtt_client.on_message = self.on_message

            self.mqtt_client.connect(self.broker, self.port, 60)
            self.mqtt_client.loop_start()
        except Exception as e:
            logger.error(f"MQTT setup failed: {e}")

    def on_connect(self, client, userdata, flags, rc):
        """Callback for MQTT connection."""
        logger.info(f"Connected to MQTT broker with result code {rc}")
        self.connected = True
        client.subscribe(f"{self.topic}/#", qos=1)
        client.subscribe(f"{self.topic}/discovery", qos=1)
        client.subscribe(f"{self.topic}/status/#", qos=1)

    def on_message(self, client, userdata, msg):
        """Callback for MQTT messages."""
        logger.info(f"MQTT message: {msg.topic} {str(msg.payload)}")
        topic = msg.topic
        payload = msg.payload.decode("utf-8", errors="replace").strip()

        if not payload:
            return

        discovery_topic = f"{self.topic}/discovery"
        status_prefix = f"{self.topic}/status/"

        if topic == discovery_topic:
            try:
                announcement = json.loads(payload)
                device_id = announcement.get("device_id") or announcement.get("id") or announcement.get("deviceId")
                if not device_id:
                    return
                self._handle_discovery_announcement(device_id, announcement)
            except json.JSONDecodeError:
                logger.warning(f"Received malformed discovery payload on {topic}")

        elif topic.startswith(status_prefix):
            status_device_id = topic[len(status_prefix):]
            self.get_device(status_device_id)

        if not self._discover_event.is_set():
            self._discover_event.set()

    def _handle_discovery_announcement(self, device_id: str, announcement: Dict[str, Any]) -> None:
        """Process a discovery announcement payload."""
        device_type = announcement.get("device_type") or announcement.get("type", "unknown")
        name = announcement.get("name") or announcement.get("device_name") or device_id

        if device_id not in self.devices:
            try:
                self.auto_register_device(device_id, device_type, name)
            except ValueError:
                logger.warning(f"Unknown device_type '{device_type}' in discovery from {device_id}")
        else:
            device = self.devices[device_id]
            if hasattr(device, "status"):
                device.status = announcement.get("status", "online")

    def add_device(self, device: IoTDevice) -> None:
        """
        Add device to manager.

        Args:
            device (IoTDevice): Device to add
        """
        self.devices[device.device_id] = device
        self._persist_devices()
        logger.info(f"Device added: {device.name} ({device.device_id})")

    def remove_device(self, device_id: str) -> None:
        """
        Remove device from manager.

        Args:
            device_id (str): Device ID to remove
        """
        if device_id in self.devices:
            device = self.devices.pop(device_id)
            self._persist_devices()
            logger.info(f"Device removed: {device.name}")

    def get_device(self, device_id: str) -> Optional[IoTDevice]:
        """
        Get device by ID.

        Args:
            device_id (str): Device ID

        Returns:
            Optional[IoTDevice]: Device object or None
        """
        return self.devices.get(device_id)

    def get_device_status(self, device_id: str) -> Optional[str]:
        """
        Get device status.

        Args:
            device_id (str): Device ID

        Returns:
            Optional[str]: Device status or None
        """
        device = self.get_device(device_id)
        return device.get_status() if device else None

    def discover_devices(self, timeout: float = 5.0) -> List[IoTDevice]:
        """
        Send a discovery message to the MQTT broker and wait for responses.

        Args:
            timeout (float): Number of seconds to wait for discovery responses

        Returns:
            List[IoTDevice]: List of newly discovered devices
        """
        discovered: List[IoTDevice] = []
        if not self.mqtt_client or not self.connected:
            logger.warning("MQTT client not connected; cannot discover devices")
            return discovered

        self._discover_event.clear()
        discovery_payload = json.dumps({
            "command": "discover",
            "requester": self.device_id,
        })
        try:
            self.mqtt_client.publish(f"{self.topic}/discovery", discovery_payload, qos=1)
        except Exception as e:
            logger.error(f"Failed to publish discovery message: {e}")
            return discovered

        if self._discover_event.wait(timeout=timeout):
            discovered = [d for d in self.devices.values() if getattr(d, "_auto_registered", False)]
        else:
            logger.info("Discovery timeout reached")

        self._persist_devices()
        return discovered

    def auto_register_device(self, device_id: str, device_type: str, name: str) -> IoTDevice:
        """
        Create the appropriate IoTDevice subclass and add it to the manager.

        Args:
            device_id (str): Unique device identifier
            device_type (str): Type of device (smart_bulb, smart_thermostat, smart_fan)
            name (str): Human-readable device name

        Returns:
            IoTDevice: The created device instance

        Raises:
            ValueError: If an unsupported device_type is provided
        """
        device_type_lower = device_type.lower().replace(" ", "_").replace("-", "_")
        if device_type_lower in ("smart_bulb", "bulb", "light"):
            device: IoTDevice = SmartBulb(device_id, name)
        elif device_type_lower in ("smart_thermostat", "thermostat"):
            device = SmartThermostat(device_id, name)
        elif device_type_lower in ("smart_fan", "fan"):
            device = SmartFan(device_id, name)
        else:
            raise ValueError(f"Unsupported device_type: {device_type}")

        device._auto_registered = True  # type: ignore[attr-defined]
        self.add_device(device)
        return device

    def list_devices(self) -> List[Dict[str, Any]]:
        """
        Return all registered devices with their statuses.

        Returns:
            List[Dict[str, Any]]: List of device info dicts with status
        """
        return [
            {
                "device_id": device.device_id,
                "name": device.name,
                "type": device.__class__.__name__,
                "status": device.get_status(),
            }
            for device in self.devices.values()
        ]

    def _persist_devices(self) -> None:
        """Save current device registry to local JSON file."""
        try:
            directory = os.path.dirname(_DEVICE_REGISTRY_PATH)
            if directory and not os.path.exists(directory):
                os.makedirs(directory, exist_ok=True)

            registry = []
            for device in self.devices.values():
                entry: Dict[str, Any] = {
                    "device_id": device.device_id,
                    "name": device.name,
                    "device_type": device.__class__.__name__,
                    "status": device.get_status(),
                }
                if isinstance(device, SmartBulb):
                    entry["state"] = device.state
                    entry["brightness"] = device.brightness
                elif isinstance(device, SmartThermostat):
                    entry["temperature"] = device.temperature
                    entry["mode"] = device.mode
                elif isinstance(device, SmartFan):
                    entry["state"] = device.state
                    entry["speed"] = device.speed
                registry.append(entry)

            with open(_DEVICE_REGISTRY_PATH, "w", encoding="utf-8") as f:
                json.dump(registry, f, indent=2)
        except Exception as e:
            logger.error(f"Failed to persist device registry: {e}")

    def _restore_devices(self) -> None:
        """Restore devices from the local JSON registry if available."""
        if not os.path.exists(_DEVICE_REGISTRY_PATH):
            return

        try:
            with open(_DEVICE_REGISTRY_PATH, "r", encoding="utf-8") as f:
                registry = json.load(f)

            if not isinstance(registry, list):
                return

            for entry in registry:
                device_id = entry.get("device_id")
                device_type = entry.get("device_type", "unknown")
                name = entry.get("name", device_id or "Unknown")
                if not device_id:
                    continue
                if device_id in self.devices:
                    continue
                try:
                    device = self.auto_register_device(device_id, device_type, name)
                    if isinstance(device, SmartBulb) and "brightness" in entry:
                        device.brightness = int(entry.get("brightness", 100))
                        device.state = entry.get("state", device.state)
                    elif isinstance(device, SmartThermostat):
                        device.temperature = float(entry.get("temperature", 20.0))
                        device.mode = entry.get("mode", device.mode)
                    elif isinstance(device, SmartFan) and "speed" in entry:
                        device.speed = int(entry.get("speed", 1))
                        device.state = entry.get("state", device.state)
                except ValueError:
                    logger.warning(f"Skipping unsupported persisted device_type '{device_type}' for {device_id}")
        except Exception as e:
            logger.error(f"Failed to restore device registry: {e}")
