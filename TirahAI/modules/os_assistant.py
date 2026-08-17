"""
OS Assistant Module for TirahAi
System automation, file operations, and OS-level tasks
"""

import os
import subprocess
import platform
import psutil
try:
    import pyautogui
except (ImportError, KeyError, OSError):
    # KeyError: 'DISPLAY' happens in headless environments
    pyautogui = None
from typing import Optional, List, Dict
from pathlib import Path
from utils.logger import get_logger
from config.settings import OS_CONFIG
from utils.helpers import get_time, get_date, format_size

logger = get_logger(__name__)


class OSAssistant:
    """
    Operating System assistant for automation and system tasks.
    Handles file operations, app launching, system info, etc.
    """
    
    def __init__(self):
        """Initialize OS assistant."""
        self.system = platform.system()
        self._last_info = None
        self._last_info_ts = 0
        logger.info(f"OS Assistant initialized on {self.system}")
    
    def open_application(self, app_name: str) -> str:
        """
        Open an application.
        
        Args:
            app_name: Name of application to open
            
        Returns:
            Status message
        """
        try:
            app_name_lower = app_name.lower()
            
            if self.system == 'Windows':
                apps = {
                    'notepad': 'notepad.exe',
                    'calculator': 'calc.exe',
                    'paint': 'mspaint.exe',
                    'chrome': 'chrome.exe',
                    'edge': 'msedge.exe',
                    'firefox': 'firefox.exe',
                    'explorer': 'explorer.exe',
                    'cmd': 'cmd.exe',
                    'powershell': 'powershell.exe'
                }
                
                exe = apps.get(app_name_lower, app_name)
                subprocess.Popen(exe, shell=True)
                logger.info(f"Opened {app_name}")
                return f"Opened {app_name}"
                
            elif self.system == 'Darwin':  # macOS
                subprocess.Popen(['open', '-a', app_name])
                return f"Opened {app_name}"
                
            else:  # Linux
                subprocess.Popen([app_name])
                return f"Opened {app_name}"
                
        except Exception as e:
            logger.error(f"Failed to open {app_name}: {e}")
            return f"Could not open {app_name}"
    
    def close_application(self, app_name: str) -> str:
        """
        Close an application.
        
        Args:
            app_name: Name of application to close
            
        Returns:
            Status message
        """
        try:
            app_name_lower = app_name.lower()
            closed = False
            
            for proc in psutil.process_iter(['name']):
                if app_name_lower in proc.info['name'].lower():
                    proc.kill()
                    closed = True
                    logger.info(f"Closed {app_name}")
            
            if closed:
                return f"Closed {app_name}"
            else:
                return f"{app_name} is not running"
                
        except Exception as e:
            logger.error(f"Failed to close {app_name}: {e}")
            return f"Could not close {app_name}"
    
    def get_system_info(self) -> Dict:
        """
        Get system information.
        
        Returns:
            Dictionary with system information
        """
        try:
            import time
            now = time.time()
            if self._last_info and (now - self._last_info_ts) < 2:
                return self._last_info
            cpu_percent = psutil.cpu_percent(interval=0)
            memory = psutil.virtual_memory()
            disk = psutil.disk_usage('/')
            
            info = {
                'os': f"{platform.system()} {platform.release()}",
                'cpu_usage': f"{cpu_percent}%",
                'memory_total': format_size(memory.total),
                'memory_used': format_size(memory.used),
                'memory_percent': f"{memory.percent}%",
                'disk_total': format_size(disk.total),
                'disk_used': format_size(disk.used),
                'disk_percent': f"{disk.percent}%",
                'boot_time': get_time()
            }
            self._last_info = info
            self._last_info_ts = now
            return info
            
        except Exception as e:
            logger.error(f"System info error: {e}")
            return {}
    
    def get_battery_status(self) -> Dict:
        """
        Get battery status.
        
        Returns:
            Battery information
        """
        try:
            battery = psutil.sensors_battery()
            
            if battery:
                return {
                    'percent': battery.percent,
                    'plugged_in': battery.power_plugged,
                    'time_left': battery.secsleft if battery.secsleft != psutil.POWER_TIME_UNLIMITED else 'Charging'
                }
            else:
                return {'error': 'No battery found'}
                
        except Exception as e:
            logger.error(f"Battery status error: {e}")
            return {}
    
    def take_screenshot(self, save_path: Optional[str] = None) -> str:
        """
        Take a screenshot.
        
        Args:
            save_path: Path to save screenshot
            
        Returns:
            Path to saved screenshot
        """
        try:
            from datetime import datetime
            
            if save_path is None:
                timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                save_path = f"data/screenshot_{timestamp}.png"
            
            screenshot = pyautogui.screenshot()
            screenshot.save(save_path)
            
            logger.info(f"Screenshot saved to {save_path}")
            return save_path
            
        except Exception as e:
            logger.error(f"Screenshot error: {e}")
            return ""
    
    def type_text(self, text: str, interval: float = 0.05):
        """
        Type text automatically.
        
        Args:
            text: Text to type
            interval: Delay between keystrokes
        """
        try:
            pyautogui.write(text, interval=interval)
            logger.info("Text typed successfully")
        except Exception as e:
            logger.error(f"Type text error: {e}")
    
    def press_key(self, key: str):
        """
        Press a keyboard key.
        
        Args:
            key: Key name (e.g., 'enter', 'tab', 'ctrl')
        """
        try:
            pyautogui.press(key)
            logger.info(f"Pressed key: {key}")
        except Exception as e:
            logger.error(f"Press key error: {e}")

    def shutdown_system(self) -> str:
        """
        Shutdown the computer.
        
        Returns:
            Status message
        """
        try:
            if self.system == "Windows":
                os.system("shutdown /s /f /t 0")
            elif self.system == "Darwin":  # macOS
                os.system("sudo shutdown -h now")
            else:  # Linux
                os.system("sudo shutdown now")
            return "Shutting down system..."
        except Exception as e:
            logger.error(f"Shutdown error: {e}")
            return f"Failed to shutdown: {e}"

    def restart_system(self) -> str:
        """
        Restart the computer.
        
        Returns:
            Status message
        """
        try:
            if self.system == "Windows":
                os.system("shutdown /r /f /t 0")
            elif self.system == "Darwin":  # macOS
                os.system("sudo shutdown -r now")
            else:  # Linux
                os.system("sudo reboot")
            return "Restarting system..."
        except Exception as e:
            logger.error(f"Restart error: {e}")
            return f"Failed to restart: {e}"

    def play_music_youtube(self, song_name: str) -> str:
        """
        Play music on YouTube.
        
        Args:
            song_name: Name of the song to search
            
        Returns:
            Status message
        """
        try:
            import webbrowser
            url = f"https://www.youtube.com/results?search_query={song_name}"
            webbrowser.open(url)
            logger.info(f"Playing {song_name} on YouTube")
            return f"Playing {song_name} on YouTube"
        except Exception as e:
            logger.error(f"Music error: {e}")
            return f"Failed to play music: {e}"
        

    
    def move_mouse(self, x: int, y: int):
        """
        Move mouse to coordinates.
        
        Args:
            x: X coordinate
            y: Y coordinate
        """
        try:
            pyautogui.moveTo(x, y)
            logger.debug(f"Mouse moved to ({x}, {y})")
        except Exception as e:
            logger.error(f"Mouse move error: {e}")
    
    def click_mouse(self, button: str = 'left'):
        """
        Click mouse button.
        
        Args:
            button: 'left', 'right', or 'middle'
        """
        try:
            pyautogui.click(button=button)
            logger.debug(f"{button} mouse button clicked")
        except Exception as e:
            logger.error(f"Mouse click error: {e}")
    
    def list_files(self, directory: str = '.') -> List[str]:
        """
        List files in directory.
        
        Args:
            directory: Directory path
            
        Returns:
            List of file paths
        """
        try:
            path = Path(directory)
            if path.is_dir():
                return [str(f) for f in path.iterdir()]
            else:
                return []
        except Exception as e:
            logger.error(f"List files error: {e}")
            return []
    
    def create_file(self, file_path: str, content: str = '') -> bool:
        """
        Create a new file.
        
        Args:
            file_path: Path for new file
            content: Initial content
            
        Returns:
            True if successful
        """
        try:
            Path(file_path).parent.mkdir(parents=True, exist_ok=True)
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            logger.info(f"File created: {file_path}")
            return True
        except Exception as e:
            logger.error(f"Create file error: {e}")
            return False
    
    def delete_file(self, file_path: str, confirm: bool = True) -> bool:
        """
        Delete a file.
        
        Args:
            file_path: Path to file
            confirm: Require confirmation
            
        Returns:
            True if deleted
        """
        try:
            if confirm and OS_CONFIG['require_confirmation']['file_deletion']:
                # In GUI, this would show a dialog
                logger.warning(f"File deletion requires confirmation: {file_path}")
                return False
            
            if Path(file_path).exists():
                Path(file_path).unlink()
                logger.info(f"File deleted: {file_path}")
                return True
            else:
                logger.warning(f"File not found: {file_path}")
                return False
                
        except Exception as e:
            logger.error(f"Delete file error: {e}")
            return False
    
    def copy_to_clipboard(self, text: str):
        """
        Copy text to clipboard.
        
        Args:
            text: Text to copy
        """
        try:
            import pyperclip
            pyperclip.copy(text)
            logger.info("Text copied to clipboard")
        except:
            # Fallback using pyautogui
            try:
                # Type into a temporary field
                pass
            except Exception as e:
                logger.error(f"Clipboard error: {e}")
    
    def get_clipboard(self) -> str:
        """
        Get text from clipboard.
        
        Returns:
            Clipboard text
        """
        try:
            import pyperclip
            return pyperclip.paste()
        except Exception as e:
            logger.error(f"Clipboard read error: {e}")
            return ""
    
    def run_command(self, command: str, shell: bool = True) -> str:
        """
        Run system command.
        
        Args:
            command: Command to run
            shell: Run in shell
            
        Returns:
            Command output
        """
        try:
            if not OS_CONFIG['allow_system_commands']:
                return "System commands are disabled"
            
            result = subprocess.run(
                command,
                shell=shell,
                capture_output=True,
                text=True
            )
            
            logger.info(f"Command executed: {command}")
            return result.stdout + result.stderr
            
        except Exception as e:
            logger.error(f"Command error: {e}")
            return f"Error: {e}"
    
    def search_files(self, query: str, directory: str = '.', 
                    max_results: int = 50) -> List[str]:
        """
        Search for files matching query.
        
        Args:
            query: Search query
            directory: Directory to search in
            max_results: Maximum results
            
        Returns:
            List of matching file paths
        """
        try:
            results = []
            path = Path(directory)
            
            for file_path in path.rglob(f"*{query}*"):
                results.append(str(file_path))
                if len(results) >= max_results:
                    break
            
            logger.info(f"Found {len(results)} files matching '{query}'")
            return results
            
        except Exception as e:
            logger.error(f"File search error: {e}")
            return []

