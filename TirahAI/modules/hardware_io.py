"""
Hardware I/O Module for TirahAi
Manages hardware interfaces including Arduino, sensors, and other I/O devices
"""

import time
from typing import Optional, Dict, List, Any
from utils.logger import get_logger
from config.settings import HARDWARE_CONFIG

try:
    import serial
except ImportError:
    serial = None

logger = get_logger(__name__)


class HardwareManager:
    """
    Manages hardware I/O operations.
    Supports Arduino, sensors, cameras, and other hardware devices.
    """
    
    def __init__(self):
        """Initialize hardware manager."""
        self.arduino_connection: Optional[Any] = None
        self.camera = None
        self.connected_devices = {}
        logger.info("Hardware Manager initialized")
    
    def connect_arduino(self, port: str = None, baud_rate: int = None) -> bool:
        if not serial:
            logger.warning("pyserial not available")
            return False
        try:
            hw = HARDWARE_CONFIG.get('arduino', {}) if isinstance(HARDWARE_CONFIG, dict) else {}
            port = port or hw.get('port', 'COM3')
            baud_rate = baud_rate or hw.get('baud_rate', 9600)
            timeout = hw.get('timeout', 1)
            
            self.arduino_connection = serial.Serial(
                port=port,
                baudrate=baud_rate,
                timeout=timeout
            )
            
            time.sleep(2)
            
            logger.info(f"Arduino connected on {port}")
            self.connected_devices['arduino'] = port
            return True
            
        except Exception as e:
            logger.error(f"Arduino connection error: {e}")
            return False
    
    def disconnect_arduino(self):
        """Disconnect from Arduino."""
        if self.arduino_connection and self.arduino_connection.is_open:
            self.arduino_connection.close()
            logger.info("Arduino disconnected")
            self.connected_devices.pop('arduino', None)
    
    def send_to_arduino(self, data: str) -> bool:
        """
        Send data to Arduino.
        
        Args:
            data: Data to send
            
        Returns:
            True if sent successfully
        """
        try:
            if not self.arduino_connection or not self.arduino_connection.is_open:
                logger.error("Arduino not connected")
                return False
            
            self.arduino_connection.write(f"{data}\n".encode())
            logger.debug(f"Sent to Arduino: {data}")
            return True
            
        except Exception as e:
            logger.error(f"Send error: {e}")
            return False
    
    def read_from_arduino(self) -> Optional[str]:
        """
        Read data from Arduino.
        
        Returns:
            Data received or None
        """
        try:
            if not self.arduino_connection or not self.arduino_connection.is_open:
                return None
            
            if self.arduino_connection.in_waiting > 0:
                data = self.arduino_connection.readline().decode().strip()
                logger.debug(f"Received from Arduino: {data}")
                return data
            
            return None
            
        except Exception as e:
            logger.error(f"Read error: {e}")
            return None
    
    def set_pin(self, pin: int, value: int) -> bool:
        """
        Set digital pin value.
        
        Args:
            pin: Pin number
            value: 0 or 1
            
        Returns:
            True if successful
        """
        return self.send_to_arduino(f"SET,{pin},{value}")
    
    def read_pin(self, pin: int, pin_type: str = 'digital') -> Optional[int]:
        """
        Read pin value.
        
        Args:
            pin: Pin number
            pin_type: 'digital' or 'analog'
            
        Returns:
            Pin value or None
        """
        cmd = 'READD' if pin_type == 'digital' else 'READA'
        if self.send_to_arduino(f"{cmd},{pin}"):
            time.sleep(0.1)
            response = self.read_from_arduino()
            if response:
                try:
                    return int(response)
                except ValueError:
                    pass
        return None
    
    def pwm_write(self, pin: int, value: int) -> bool:
        """
        Write PWM value to pin.
        
        Args:
            pin: Pin number
            value: PWM value (0-255)
            
        Returns:
            True if successful
        """
        if 0 <= value <= 255:
            return self.send_to_arduino(f"PWM,{pin},{value}")
        return False
    
    def connect_camera(self, device_id: int = 0) -> bool:
        """
        Connect to camera.
        
        Args:
            device_id: Camera device ID
            
        Returns:
            True if connected
        """
        try:
            import cv2
            
            self.camera = cv2.VideoCapture(device_id)
            
            if self.camera.isOpened():
                logger.info(f"Camera {device_id} connected")
                self.connected_devices['camera'] = device_id
                return True
            
            return False
            
        except Exception as e:
            logger.error(f"Camera connection error: {e}")
            return False
    
    def capture_image(self, save_path: Optional[str] = None) -> Optional[str]:
        """
        Capture image from camera.
        
        Args:
            save_path: Path to save image
            
        Returns:
            Path to saved image or None
        """
        try:
            import cv2
            from datetime import datetime
            
            if not self.camera or not self.camera.isOpened():
                logger.error("Camera not connected")
                return None
            
            ret, frame = self.camera.read()
            
            if ret:
                if save_path is None:
                    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                    save_path = f"data/camera_{timestamp}.jpg"
                
                cv2.imwrite(save_path, frame)
                logger.info(f"Image saved to {save_path}")
                return save_path
            
            return None
            
        except Exception as e:
            logger.error(f"Image capture error: {e}")
            return None
    
    def detect_objects(self) -> List[Dict]:
        """
        Detect objects using camera and MediaPipe.
        
        Returns:
            List of detected objects
        """
        try:
            import cv2
            import mediapipe as mp
            
            if not self.camera or not self.camera.isOpened():
                return []
            
            mp_hands = mp.solutions.hands
            hands = mp_hands.Hands()
            
            ret, frame = self.camera.read()
            if not ret:
                return []
            
            # Convert to RGB
            rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            
            # Process
            results = hands.process(rgb_frame)
            
            detections = []
            if results.multi_hand_landmarks:
                for hand_landmarks in results.multi_hand_landmarks:
                    detections.append({
                        'type': 'hand',
                        'landmarks': len(hand_landmarks.landmark)
                    })
            
            return detections
            
        except Exception as e:
            logger.error(f"Object detection error: {e}")
            return []
    
    def disconnect_camera(self):
        """Disconnect camera."""
        if self.camera:
            self.camera.release()
            logger.info("Camera disconnected")
            self.connected_devices.pop('camera', None)
    
    def list_available_ports(self) -> List[str]:
        """
        List available serial ports.
        
        Returns:
            List of port names
        """
        try:
            import serial.tools.list_ports
            ports = serial.tools.list_ports.comports()
            return [port.device for port in ports]
        except Exception as e:
            logger.error(f"Port listing error: {e}")
            return []
    
    def get_device_status(self) -> Dict:
        """
        Get status of all connected devices.
        
        Returns:
            Device status dictionary
        """
        status = {
            'arduino': {
                'connected': self.arduino_connection is not None and 
                           self.arduino_connection.is_open if self.arduino_connection else False,
                'port': self.connected_devices.get('arduino', 'None')
            },
            'camera': {
                'connected': self.camera is not None and 
                           self.camera.isOpened() if self.camera else False,
                'device_id': self.connected_devices.get('camera', 'None')
            }
        }
        
        return status
    
    def cleanup(self):
        """Clean up all hardware connections."""
        self.disconnect_arduino()
        self.disconnect_camera()
        logger.info("Hardware manager cleaned up")

