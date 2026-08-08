import json
import os
from pathlib import Path

# Project Paths
BASE_DIR = Path(__file__).resolve().parent.parent
DATA_DIR = BASE_DIR / "data"
LOGS_DIR = BASE_DIR / "logs"

# Ensure directories exist
DATA_DIR.mkdir(exist_ok=True)
LOGS_DIR.mkdir(exist_ok=True)

class ConfigManager:
    _instance = None
    DEFAULT_CONFIG = {
        "app_name": "TirahAi",
        "theme_mode": "dark",
        "user_name": "User",
        "startup_greeting": True,
        "voice_enabled": False,
        "voice_volume": 1.0,
        "paths": {
            "data_dir": str(DATA_DIR),
            "logs_dir": str(LOGS_DIR)
        },
        "api_keys": {
            "openai": "",
            "weather": ""
        },
        "service_apis": {}
    }

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(ConfigManager, cls).__new__(cls)
            cls._instance.config_file = BASE_DIR / "config.json"
            cls._instance.config = cls._instance.load_config()
        return cls._instance

    def load_config(self):
        cfg = self.DEFAULT_CONFIG.copy()
        if os.path.exists(self.config_file):
            try:
                with open(self.config_file, "r") as f:
                    loaded = json.load(f)
                    if isinstance(loaded, dict):
                        cfg.update(loaded)
            except:
                pass
        cfg = self.validate_config(cfg)
        return cfg

    def save_config(self):
        with open(self.config_file, "w") as f:
            json.dump(self.config, f, indent=4)

    def get(self, key, default=None):
        return self.config.get(key, default)

    def set(self, key, value):
        self.config[key] = value
        self.save_config()

    def get_bool(self, key, default=False):
        v = self.config.get(key, default)
        return bool(v)

    def get_float(self, key, default=0.0, min_val=0.0, max_val=1.0):
        try:
            val = float(self.config.get(key, default))
        except:
            val = float(default)
        if val < min_val:
            val = min_val
        if val > max_val:
            val = max_val
        return val

    def get_path(self, *parts):
        try:
            return Path(self.config.get("paths", {}).get(parts[0])) if len(parts) == 1 else Path(self.config.get("paths", {}).get(parts[0])) / Path(parts[1])
        except:
            return BASE_DIR

    def validate_config(self, cfg):
        tm = cfg.get("theme_mode")
        if tm not in ("dark", "light"):
            cfg["theme_mode"] = "dark"
        vol = cfg.get("voice_volume", 1.0)
        try:
            vol = float(vol)
        except:
            vol = 1.0
        if vol < 0.0:
            vol = 0.0
        if vol > 1.0:
            vol = 1.0
        cfg["voice_volume"] = vol
        paths = cfg.get("paths", {})
        if "data_dir" not in paths:
            paths["data_dir"] = str(DATA_DIR)
        if "logs_dir" not in paths:
            paths["logs_dir"] = str(LOGS_DIR)
        cfg["paths"] = paths
        apis = cfg.get("api_keys", {})
        if "openai" not in apis:
            apis["openai"] = ""
        if "weather" not in apis:
            apis["weather"] = ""
        cfg["api_keys"] = apis
        if not isinstance(cfg.get("service_apis"), dict):
            cfg["service_apis"] = {}
        hardware = cfg.get("hardware", {})
        if not isinstance(hardware, dict):
            hardware = {}
        if "arduino" not in hardware:
            hardware["arduino"] = {"port": "COM3", "baud_rate": 9600, "timeout": 1}
        cfg["hardware"] = hardware
        iot = cfg.get("iot", {})
        if not isinstance(iot, dict):
            iot = {}
        if "broker_host" not in iot:
            iot["broker_host"] = "broker.hivemq.com"
        if "broker_port" not in iot:
            iot["broker_port"] = 1883
        if "topic" not in iot:
            iot["topic"] = "tirahai/iot"
        if "device_id" not in iot:
            iot["device_id"] = "tirah_main_unit"
        cfg["iot"] = iot
        return cfg

GUI_CONFIG = ConfigManager()

# Legacy support constants (Required for modules)
LOGGING_CONFIG = {
    "level": "INFO",
    "file": str(LOGS_DIR / "tirah.log"),
    "max_bytes": 10485760,  # 10 MB
    "backup_count": 5,
    "format": "%(asctime)s - %(name)s - %(levelname)s - %(message)s",
    "json": False
}

AI_CONFIG = {
    "primary_model": "gemini",  # Default to Gemini for smartness
    "fallback_model": "ollama",
    "code_model": "gpt-4-turbo",
    "temperature": 0.7,
    "max_tokens": 1000,
    "context_memory": 5,
    "gemini_model": "gemini-pro"
}

VOICE_CONFIG = {
    "engine": "pyttsx3",
    "rate": 150,
    "volume": 1.0,
    "voice_id": 0,
    "language": "en"
}

CODE_CONFIG = {
    "supported_languages": ["python", "javascript", "html", "css", "cpp", "java"],
    "use_autopep8": True
}

STUDY_CONFIG = {
    "subjects": ["math", "science", "history", "literature", "programming"],
    "quiz_question_count": 5
}

HARDWARE_CONFIG = GUI_CONFIG.get("hardware", {
    "arduino": {
        "port": "COM3",
        "baud_rate": 9600,
        "timeout": 1
    }
})

IOT_CONFIG = GUI_CONFIG.get("iot", {
    "broker_host": "broker.hivemq.com",
    "broker_port": 1883,
    "topic": "tirahai/iot",
    "device_id": "tirah_main_unit"
})

OS_CONFIG = {
    "system": "windows",
    "require_confirmation": {
        "file_deletion": True
    },
    "allow_system_commands": True
}

env_file = BASE_DIR / ".env"
if env_file.exists():
    try:
        for line in env_file.read_text().splitlines():
            if "=" in line and not line.strip().startswith("#"):
                k, v = line.split("=", 1)
                os.environ.setdefault(k.strip(), v.strip())
    except:
        pass

OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "")
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "")
WEATHER_API_KEY = os.getenv("WEATHER_API_KEY", "")

# Legacy support constants
DEBUG_MODE = os.getenv("DEBUG_MODE", "false").lower() == "true"
FEATURES = {
    'voice_control': True,
    'gesture_control': False,
    'on_screen_assistant': True,
    'iot_integration': True,
    'learning_mode': True
}
