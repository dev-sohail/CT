"""
Validation utilities for TirahAi
Check dependencies and system requirements
"""

import sys
import importlib
from typing import List, Tuple
from utils.logger import get_logger

logger = get_logger(__name__)


def check_python_version() -> bool:
    """
    Check if Python version is 3.8 or higher.
    
    Returns:
        True if version is compatible
    """
    version_info = sys.version_info
    if version_info.major < 3 or (version_info.major == 3 and version_info.minor < 8):
        logger.error(f"Python 3.8+ required, found {sys.version}")
        return False
    return True


def check_required_packages() -> Tuple[List[str], List[str]]:
    """
    Check if required packages are installed.
    
    Returns:
        Tuple of (installed packages, missing packages)
    """
    required_packages = [
        'flet',
        'speech_recognition',
        'pyttsx3',
        'psutil',
        'cryptography',
        'dotenv'
    ]
    
    optional_packages = [
        'openai',
        'ollama',
        'pyserial',
        'cv2',
        'pyperclip'
    ]
    
    installed = []
    missing = []
    
    for package in required_packages:
        try:
            if package == 'speech_recognition':
                importlib.import_module('speech_recognition')
            elif package == 'dotenv':
                importlib.import_module('dotenv')
            elif package == 'flet':
                importlib.import_module('flet')
            else:
                importlib.import_module(package.lower().replace('-', '_'))
            installed.append(package)
        except ImportError:
            missing.append(package)
            logger.warning(f"Required package missing: {package}")
    
    # Check optional packages
    for package in optional_packages:
        try:
            if package == 'cv2':
                importlib.import_module('cv2')
            elif package == 'pyserial':
                importlib.import_module('serial')
            else:
                importlib.import_module(package)
        except ImportError:
            logger.info(f"Optional package not installed: {package}")
    
    return installed, missing


def check_system_requirements() -> dict:
    """
    Check system requirements and capabilities.
    
    Returns:
        Dictionary with system check results
    """
    results = {
        'python_version': check_python_version(),
        'microphone_available': False,
        'camera_available': False,
        'serial_ports_available': False
    }
    
    # Check microphone
    try:
        import speech_recognition as sr
        recognizer = sr.Recognizer()
        mic_list = sr.Microphone.list_microphone_names()
        results['microphone_available'] = len(mic_list) > 0
        logger.info(f"Found {len(mic_list)} microphone(s)")
    except Exception as e:
        logger.warning(f"Microphone check failed: {e}")
    
    # Check camera
    try:
        import cv2
        cap = cv2.VideoCapture(0)
        if cap.isOpened():
            results['camera_available'] = True
            cap.release()
            logger.info("Camera available")
    except Exception as e:
        logger.info(f"Camera not available: {e}")
    
    # Check serial ports
    try:
        import serial.tools.list_ports
        ports = list(serial.tools.list_ports.comports())
        results['serial_ports_available'] = len(ports) > 0
        logger.info(f"Found {len(ports)} serial port(s)")
    except Exception as e:
        logger.info(f"Serial port check failed: {e}")
    
    return results


def validate_configuration() -> bool:
    """
    Validate configuration file.
    
    Returns:
        True if configuration is valid
    """
    try:
        from config.settings import (
            VOICE_CONFIG, GUI_CONFIG, AI_CONFIG,
            FEATURES, LOGGING_CONFIG
        )
        
        # Check required config keys
        assert 'engine' in VOICE_CONFIG
        assert GUI_CONFIG.get('theme_mode') is not None
        assert 'primary_model' in AI_CONFIG
        assert 'level' in LOGGING_CONFIG
        
        logger.info("Configuration validated successfully")
        return True
        
    except Exception as e:
        logger.error(f"Configuration validation failed: {e}")
        return False


def run_startup_checks() -> bool:
    """
    Run all startup validation checks.
    
    Returns:
        True if all critical checks pass
    """
    logger.info("Running startup validation checks...")
    
    all_passed = True
    
    # Python version
    if not check_python_version():
        logger.critical("Python version check failed!")
        all_passed = False
    else:
        logger.info("✓ Python version OK")
    
    # Required packages
    installed, missing = check_required_packages()
    if missing:
        logger.critical(f"Missing required packages: {', '.join(missing)}")
        logger.info("Run: pip install -r requirements.txt")
        all_passed = False
    else:
        logger.info(f"✓ All required packages installed ({len(installed)} packages)")
    
    # Configuration
    if not validate_configuration():
        logger.error("Configuration validation failed!")
        all_passed = False
    else:
        logger.info("✓ Configuration valid")
    
    # System requirements (informational)
    sys_results = check_system_requirements()
    if not sys_results['microphone_available']:
        logger.warning("⚠ No microphone detected - voice features may not work")
    
    if all_passed:
        logger.info("✓ All startup checks passed!")
    else:
        logger.error("✗ Some startup checks failed")
    
    return all_passed

