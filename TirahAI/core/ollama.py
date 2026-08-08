"""
Ollama Integration for TirahAi
Configure, query, and manage local Ollama models
"""

import json
import os
from pathlib import Path
from typing import Optional, Dict, List, Any
from utils.logger import get_logger

logger = get_logger(__name__)

BASE_DIR = Path(__file__).parent.parent
OLLAMA_CONFIG_FILE = BASE_DIR / "data" / "ollama_config.json"


class OllamaManager:
    def __init__(self):
        self.config = self._load_config()
        self._client = None

    def _load_config(self) -> Dict:
        default = {
            "enabled": False,
            "host": "http://localhost:11434",
            "default_model": "",
            "temperature": 0.7,
            "max_tokens": 2048,
            "timeout": 60,
        }
        if OLLAMA_CONFIG_FILE.exists():
            try:
                loaded = json.loads(OLLAMA_CONFIG_FILE.read_text(encoding="utf-8"))
                default.update(loaded)
            except Exception as e:
                logger.error(f"Failed to load Ollama config: {e}")
        return default

    def _save_config(self):
        try:
            OLLAMA_CONFIG_FILE.parent.mkdir(parents=True, exist_ok=True)
            OLLAMA_CONFIG_FILE.write_text(json.dumps(self.config, indent=2), encoding="utf-8")
        except Exception as e:
            logger.error(f"Failed to save Ollama config: {e}")

    def get_config(self) -> Dict:
        return {
            "enabled": self.config.get("enabled", False),
            "host": self.config.get("host", "http://localhost:11434"),
            "default_model": self.config.get("default_model", ""),
            "temperature": self.config.get("temperature", 0.7),
            "max_tokens": self.config.get("max_tokens", 2048),
            "timeout": self.config.get("timeout", 60),
        }

    def update_config(self, updates: Dict):
        for key in ["enabled", "host", "default_model", "temperature", "max_tokens", "timeout"]:
            if key in updates:
                self.config[key] = updates[key]
        self._save_config()

    def check_status(self) -> Dict:
        import requests
        host = self.config.get("host", "http://localhost:11434").rstrip("/")
        try:
            resp = requests.get(f"{host}/api/tags", timeout=5)
            if resp.status_code == 200:
                data = resp.json()
                models = data.get("models", [])
                version = ""
                try:
                    version_resp = requests.get(f"{host}/api/version", timeout=5)
                    if version_resp.status_code == 200:
                        version = version_resp.json().get("version", "")
                except Exception:
                    version = ""
                return {
                    "connected": True,
                    "status": "connected",
                    "host": host,
                    "models_count": len(models),
                    "version": version,
                    "response_time": "ok",
                }
            return {"connected": False, "status": "error", "host": host, "message": f"HTTP {resp.status_code}"}
        except requests.exceptions.ConnectionError:
            return {"connected": False, "status": "disconnected", "host": host, "message": "Connection refused"}
        except Exception as e:
            return {"connected": False, "status": "error", "host": host, "message": str(e)}

    def list_models(self) -> List[Dict]:
        import requests
        host = self.config.get("host", "http://localhost:11434").rstrip("/")
        try:
            resp = requests.get(f"{host}/api/tags", timeout=10)
            if resp.status_code == 200:
                data = resp.json()
                models = []
                for m in data.get("models", []):
                    models.append({
                        "name": m.get("name", "unknown"),
                        "size": m.get("size", 0),
                        "modified": m.get("modified_at", ""),
                        "details": m.get("details", {}),
                    })
                return models
            return []
        except Exception as e:
            logger.error(f"Failed to list Ollama models: {e}")
            return []

    def query(self, prompt: str, model: Optional[str] = None) -> Dict:
        import requests
        host = self.config.get("host", "http://localhost:11434").rstrip("/")
        model = model or self.config.get("default_model", "llama3.2")
        if not model:
            return {"success": False, "error": "No model specified", "response": ""}
        try:
            resp = requests.post(
                f"{host}/api/generate",
                json={"model": model, "prompt": prompt, "stream": False,
                      "options": {"temperature": self.config.get("temperature", 0.7),
                                  "num_predict": self.config.get("max_tokens", 2048)}},
                timeout=self.config.get("timeout", 60),
            )
            if resp.status_code == 200:
                data = resp.json()
                return {"success": True, "response": data.get("response", ""), "model": model, "eval_count": data.get("eval_count", 0)}
            return {"success": False, "error": f"HTTP {resp.status_code}", "response": ""}
        except Exception as e:
            return {"success": False, "error": str(e), "response": ""}

    def is_available(self) -> bool:
        return self.config.get("enabled", False)

    def chat(self, messages: List[Dict], model: Optional[str] = None) -> Dict:
        import requests
        host = self.config.get("host", "http://localhost:11434").rstrip("/")
        model = model or self.config.get("default_model", "llama3.2")
        if not model:
            return {"success": False, "error": "No model specified", "response": ""}
        try:
            resp = requests.post(
                f"{host}/api/chat",
                json={"model": model, "messages": messages, "stream": False,
                      "options": {"temperature": self.config.get("temperature", 0.7)}},
                timeout=self.config.get("timeout", 60),
            )
            if resp.status_code == 200:
                data = resp.json()
                return {"success": True, "response": data.get("message", {}).get("content", ""), "model": model}
            return {"success": False, "error": f"HTTP {resp.status_code}", "response": ""}
        except Exception as e:
            return {"success": False, "error": str(e), "response": ""}
