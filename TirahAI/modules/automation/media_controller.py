"""
Media Controller Module
Controls system volume and media playback
"""

import pyautogui
import logging
from typing import Optional

logger = logging.getLogger(__name__)

class MediaController:
    """Controls media playback and volume."""
    
    def __init__(self):
        """Initialize media controller."""
        # PyAutoGUI handles media keys on Windows/macOS/Linux
        pass
        
    def play_pause(self):
        """Toggle play/pause."""
        try:
            pyautogui.press('playpause')
            logger.info("Media: Play/Pause toggled")
        except Exception as e:
            logger.error(f"Error toggling play/pause: {e}")
            
    def volume_up(self):
        """Increase volume."""
        try:
            pyautogui.press('volumeup')
            logger.info("Media: Volume Up")
        except Exception as e:
            logger.error(f"Error increasing volume: {e}")
            
    def volume_down(self):
        """Decrease volume."""
        try:
            pyautogui.press('volumedown')
            logger.info("Media: Volume Down")
        except Exception as e:
            logger.error(f"Error decreasing volume: {e}")
            
    def mute(self):
        """Mute/Unmute volume."""
        try:
            pyautogui.press('volumemute')
            logger.info("Media: Mute toggled")
        except Exception as e:
            logger.error(f"Error toggling mute: {e}")
            
    def next_track(self):
        """Skip to next track."""
        try:
            pyautogui.press('nexttrack')
            logger.info("Media: Next Track")
        except Exception as e:
            logger.error(f"Error skipping track: {e}")
            
    def prev_track(self):
        """Go to previous track."""
        try:
            pyautogui.press('prevtrack')
            logger.info("Media: Previous Track")
        except Exception as e:
            logger.error(f"Error previous track: {e}")
