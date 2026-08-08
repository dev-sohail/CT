"""
Tool Registry for TirahAi Agent
Registers and manages all available tools/functions the agent can call
"""

import os
import sys
import json
import re
import subprocess
import time
from datetime import datetime
from pathlib import Path
from typing import Optional, Dict, List, Any, Callable
from utils.logger import get_logger
from utils.helpers import get_time, get_date

logger = get_logger(__name__)

BASE_DIR = Path(__file__).parent.parent


class _ToolCache:
    def __init__(self, ttl: int = 300):
        self._store: Dict[str, Any] = {}
        self._ttl = ttl

    def get(self, key: str) -> Optional[Any]:
        item = self._store.get(key)
        if not item:
            return None
        if time.time() - item[0] > self._ttl:
            self._store.pop(key, None)
            return None
        return item[1]

    def set(self, key: str, value: Any) -> None:
        self._store[key] = (time.time(), value)

    def invalidate(self, key: str) -> None:
        self._store.pop(key, None)


tool_cache = _ToolCache(ttl=180)


class ToolRegistry:
    _instance = None

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
            cls._instance._initialized = False
        return cls._instance

    def __init__(self):
        if self._initialized:
            return
        self._initialized = True
        self.tools: Dict[str, Dict] = {}
        self._register_builtin_tools()

    def register(self, name: str, description: str, handler: Callable, category: str = "general"):
        self.tools[name] = {
            "name": name,
            "description": description,
            "handler": handler,
            "category": category,
        }
        logger.debug(f"Tool registered: {name} ({category})")

    def execute(self, name: str, **kwargs) -> Dict:
        tool = self.tools.get(name)
        if not tool:
            return {"success": False, "error": f"Tool '{name}' not found", "tool": name}
        try:
            result = tool["handler"](**kwargs)
            return {"success": True, "result": result, "tool": name}
        except Exception as e:
            logger.error(f"Tool '{name}' error: {e}")
            return {"success": False, "error": str(e), "tool": name}

    def get_tools(self, category: Optional[str] = None) -> List[Dict]:
        tools_list = []
        for t in self.tools.values():
            if category is None or t["category"] == category:
                tools_list.append({"name": t["name"], "description": t["description"], "category": t["category"]})
        return tools_list

    def get_categories(self) -> List[str]:
        cats = set()
        for t in self.tools.values():
            cats.add(t["category"])
        return sorted(cats)

    def _register_builtin_tools(self):
        self.register("get_time", "Get current time", lambda: {"time": get_time(), "formatted": f"The current time is {get_time()}"}, "utility")
        self.register("get_date", "Get current date", lambda: {"date": get_date(), "formatted": f"Today is {get_date()}"}, "utility")
        self.register("calculate", "Perform mathematical calculation", self._tool_calculate, "utility")
        self.register("get_help", "List available commands and tools", self._tool_help, "utility")
        self.register("search_memory", "Search past conversations and stored information", self._tool_search_memory, "memory")
        self.register("remember", "Store information in long-term memory", self._tool_remember, "memory")
        self.register("get_preferences", "Get user preferences", self._tool_get_preferences, "memory")
        self.register("set_preference", "Set user preference", self._tool_set_preference, "memory")
        self.register("get_system_info", "Get system information", self._tool_system_info, "system")
        self.register("get_battery", "Get battery status", self._tool_battery, "system")
        self.register("open_application", "Open an application by name", self._tool_open_app, "system")
        self.register("list_files", "List files in a directory", self._tool_list_files, "file")
        self.register("weather", "Get weather information for a location", self._tool_weather, "utility")
        self.register("translate", "Translate text from one language to another", self._tool_translate, "utility")
        self.register("play_music", "Play music from local files, YouTube, or Spotify", self._tool_play_music, "utility")
        self.register("send_email", "Send an email", self._tool_send_email, "utility")
        self.register("list_calendar", "List calendar events", self._tool_list_calendar, "utility")
        self.register("add_calendar", "Add a calendar event", self._tool_add_calendar, "utility")
        self.register("remind_me", "Set a reminder", self._tool_remind_me, "utility")

    def _tool_calculate(self, expression: str = "") -> Dict:
        try:
            expr = re.sub(r'[^0-9+\-*/().% ]', '', expression)
            if not expr:
                return {"error": "No expression provided", "result": None}
            result = eval(expr)
            return {"expression": expression, "result": result, "formatted": f"{expression} = {result}"}
        except Exception as e:
            return {"error": str(e), "expression": expression, "result": None}

    def _tool_help(self) -> Dict:
        tools_by_cat = {}
        for t in self.tools.values():
            cat = t["category"]
            if cat not in tools_by_cat:
                tools_by_cat[cat] = []
            tools_by_cat[cat].append({"name": t["name"], "description": t["description"]})
        return {"categories": tools_by_cat}

    def _tool_search_memory(self, query: str = "") -> Dict:
        try:
            from core.memory import MemoryStore
            mem = MemoryStore()
            results = mem.search_long_term(query, limit=5)
            return {"query": query, "results": results, "count": len(results)}
        except Exception as e:
            return {"error": str(e), "results": []}

    def _tool_remember(self, key: str = "", content: str = "", tags: str = "") -> Dict:
        try:
            from core.memory import MemoryStore
            mem = MemoryStore()
            tag_list = [t.strip() for t in tags.split(",")] if tags else []
            mem.add_to_long_term(key, content, tag_list)
            return {"status": "stored", "key": key}
        except Exception as e:
            return {"error": str(e)}

    def _tool_get_preferences(self) -> Dict:
        try:
            from core.memory import MemoryStore
            mem = MemoryStore()
            return {"preferences": mem.get_preferences()}
        except Exception as e:
            return {"error": str(e)}

    def _tool_set_preference(self, key: str = "", value: str = "") -> Dict:
        try:
            from core.memory import MemoryStore
            mem = MemoryStore()
            mem.set_preference(key, value)
            return {"status": "set", "key": key, "value": value}
        except Exception as e:
            return {"error": str(e)}

    def _tool_system_info(self) -> Dict:
        try:
            import psutil
            import platform
            cpu = psutil.cpu_percent(interval=0)
            mem = psutil.virtual_memory()
            disk = psutil.disk_usage("/")
            return {
                "os": f"{platform.system()} {platform.release()}",
                "cpu_percent": cpu,
                "memory": {"total_gb": round(mem.total / (1024**3), 1), "used_gb": round(mem.used / (1024**3), 1), "percent": mem.percent},
                "disk": {"total_gb": round(disk.total / (1024**3), 1), "used_gb": round(disk.used / (1024**3), 1), "percent": disk.percent},
            }
        except Exception as e:
            return {"error": str(e)}

    def _tool_battery(self) -> Dict:
        try:
            import psutil
            bat = psutil.sensors_battery()
            if bat:
                return {"percent": bat.percent, "plugged_in": bat.power_plugged}
            return {"error": "No battery found"}
        except Exception as e:
            return {"error": str(e)}

    def _tool_open_app(self, app_name: str = "") -> Dict:
        try:
            from modules.os_assistant import OSAssistant
            osa = OSAssistant()
            result = osa.open_application(app_name)
            return {"status": result}
        except Exception as e:
            return {"error": str(e)}

    def _tool_list_files(self, directory: str = ".") -> Dict:
        try:
            path = Path(directory)
            if not path.exists():
                path = BASE_DIR / directory
            files = []
            for f in path.iterdir():
                files.append({"name": f.name, "type": "directory" if f.is_dir() else "file", "size": f.stat().st_size if f.is_file() else 0})
            return {"directory": str(path), "files": files[:50], "count": len(files)}
        except Exception as e:
            return {"error": str(e)}

    def _tool_weather(self, location: str = "") -> Dict:
        cache_key = f"weather:{location.lower()}"
        cached = tool_cache.get(cache_key)
        if cached is not None:
            return cached
        try:
            from modules.automation.weather import WeatherTool
            wt = WeatherTool()
            result = wt.get_weather(location)
            payload = {"location": location, "weather": result}
            tool_cache.set(cache_key, payload)
            return payload
        except Exception as e:
            return {"error": str(e), "note": "Weather API key may be required"}

    def _tool_translate(self, text: str = "", target_lang: str = "en", source_lang: str = "") -> Dict:
        if not text:
            return {"error": "No text provided"}
        cache_key = f"translate:{source_lang}:{target_lang}:{text.lower()}"
        cached = tool_cache.get(cache_key)
        if cached is not None:
            return cached
        try:
            from modules.integrations.translation_api import TranslationService
            ts = TranslationService()
            result = ts.translate_text(text, source_lang or "auto", target_lang)
            payload = {"original": text, "translated": result, "source_lang": source_lang or "auto", "target_lang": target_lang}
            tool_cache.set(cache_key, payload)
            return payload
        except Exception as e:
            return {"error": str(e), "note": "Translation service may not be available"}

    def _tool_play_music(self, query: str = "", source: str = "youtube") -> Dict:
        try:
            from modules.automation.play_music import MusicAssistant
            ma = MusicAssistant()
            if source == "spotify":
                return {"result": ma.play_spotify_music(query)}
            if source == "local":
                return {"result": ma.play_local_music(query)}
            return {"result": ma.play_youtube_music(query)}
        except Exception as e:
            return {"error": str(e)}

    def _tool_send_email(self, to_address: str = "", subject: str = "", body: str = "") -> Dict:
        try:
            from modules.integrations.email_service import EmailService
            service = EmailService()
            service.send(to_address, subject, body)
            return {"status": "sent", "to": to_address, "subject": subject}
        except Exception as e:
            return {"error": str(e)}

    def _tool_list_calendar(self) -> Dict:
        try:
            from modules.integrations.calendar_sync import CalendarSync
            cs = CalendarSync()
            return {"events": cs.list_events()}
        except Exception as e:
            return {"error": str(e)}

    def _tool_add_calendar(self, title: str = "", start: str = "", end: str = "") -> Dict:
        try:
            from modules.integrations.calendar_sync import CalendarSync, CalendarEvent
            cs = CalendarSync()
            cs.add_event(CalendarEvent(title=title, start=start, end=end))
            return {"status": "added", "title": title}
        except Exception as e:
            return {"error": str(e)}

    def _tool_remind_me(self, task: str = "", amount: int = 1, unit: str = "minutes") -> Dict:
        try:
            from modules.automation.task_scheduler import add_task_to_scheduler
            seconds = amount
            if unit.startswith("minute"):
                seconds *= 60
            elif unit.startswith("hour"):
                seconds *= 3600
            def reminder_task():
                logger.info(f"REMINDER: {task}")
            job_name = f"reminder_{int(time.time())}"
            add_task_to_scheduler(reminder_task, "interval", {"seconds": seconds}, job_name)
            return {"status": "scheduled", "task": task, "in": f"{amount} {unit}"}
        except Exception as e:
            return {"error": str(e)}
