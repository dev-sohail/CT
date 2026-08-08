"""
Memory System for TirahAi
Short-term session memory, long-term persistent memory, and learning
"""

import json
import os
import time
import re
from datetime import datetime
from pathlib import Path
from typing import Optional, Dict, List, Any
from collections import defaultdict
from utils.logger import get_logger

logger = get_logger(__name__)

BASE_DIR = Path(__file__).parent.parent
MEMORY_DIR = BASE_DIR / "data" / "memory"
MEMORY_DIR.mkdir(parents=True, exist_ok=True)


class MemoryStore:
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
        self.session_id = str(int(time.time()))
        self.short_term: List[Dict] = []
        self.max_short_term = 50
        self.long_term_file = MEMORY_DIR / "long_term.json"
        self.learning_file = MEMORY_DIR / "learning.json"
        self.patterns_file = MEMORY_DIR / "patterns.json"
        self.preferences_file = MEMORY_DIR / "preferences.json"
        self._load_long_term()
        self._load_learning()
        self._load_patterns()
        self._load_preferences()
        logger.info(f"MemoryStore initialized (session: {self.session_id})")

    def _load_long_term(self):
        try:
            if self.long_term_file.exists():
                self.long_term = json.loads(self.long_term_file.read_text(encoding="utf-8"))
            else:
                self.long_term = []
        except:
            self.long_term = []

    def _save_long_term(self):
        try:
            self.long_term_file.write_text(json.dumps(self.long_term[-1000:], indent=2), encoding="utf-8")
        except Exception as e:
            logger.error(f"Save long-term memory failed: {e}")

    def _load_learning(self):
        try:
            if self.learning_file.exists():
                self.learning_data = json.loads(self.learning_file.read_text(encoding="utf-8"))
            else:
                self.learning_data = {"routes": {}, "feedback": [], "accuracy": {}}
        except:
            self.learning_data = {"routes": {}, "feedback": [], "accuracy": {}}

    def _save_learning(self):
        try:
            self.learning_file.write_text(json.dumps(self.learning_data, indent=2), encoding="utf-8")
        except Exception as e:
            logger.error(f"Save learning data failed: {e}")

    def _load_patterns(self):
        try:
            if self.patterns_file.exists():
                self.patterns = json.loads(self.patterns_file.read_text(encoding="utf-8"))
            else:
                self.patterns = {"phrases": {}, "intents": {}}
        except:
            self.patterns = {"phrases": {}, "intents": {}}

    def _save_patterns(self):
        try:
            self.patterns_file.write_text(json.dumps(self.patterns, indent=2), encoding="utf-8")
        except Exception as e:
            logger.error(f"Save patterns failed: {e}")

    def _load_preferences(self):
        try:
            if self.preferences_file.exists():
                self.preferences = json.loads(self.preferences_file.read_text(encoding="utf-8"))
            else:
                self.preferences = {}
        except:
            self.preferences = {}

    def _save_preferences(self):
        try:
            self.preferences_file.write_text(json.dumps(self.preferences, indent=2), encoding="utf-8")
        except Exception as e:
            logger.error(f"Save preferences failed: {e}")

    def add_to_short_term(self, role: str, content: str, metadata: Optional[Dict] = None):
        entry = {
            "role": role,
            "content": content,
            "timestamp": datetime.now().isoformat(),
            "session": self.session_id,
        }
        if metadata:
            entry["metadata"] = metadata
        self.short_term.append(entry)
        if len(self.short_term) > self.max_short_term:
            self.short_term = self.short_term[-self.max_short_term:]

    def add_to_long_term(self, key: str, content: Any, tags: Optional[List[str]] = None):
        entry = {
            "key": key,
            "content": content,
            "tags": tags or [],
            "timestamp": datetime.now().isoformat(),
            "access_count": 0,
        }
        for i, existing in enumerate(self.long_term):
            if existing.get("key") == key:
                entry["access_count"] = existing.get("access_count", 0)
                self.long_term[i] = entry
                self._save_long_term()
                return
        self.long_term.append(entry)
        self._save_long_term()

    def get_from_long_term(self, key: str) -> Optional[Any]:
        for entry in self.long_term:
            if entry.get("key") == key:
                entry["access_count"] = entry.get("access_count", 0) + 1
                self._save_long_term()
                return entry["content"]
        return None

    def search_long_term(self, query: str, limit: int = 5) -> List[Dict]:
        query = query.lower()
        scored = []
        for entry in self.long_term:
            score = 0
            key = entry.get("key", "").lower()
            content = str(entry.get("content", "")).lower()
            tags = " ".join(entry.get("tags", [])).lower()
            if query in key:
                score += 10
            if query in content:
                score += 3
            if query in tags:
                score += 5
            words = set(query.split())
            content_words = set(content.split())
            overlap = len(words & content_words)
            score += overlap
            if score > 0:
                scored.append((score, entry))
        scored.sort(key=lambda x: -x[0])
        return [e for _, e in scored[:limit]]

    def get_conversation_context(self, limit: int = 10) -> List[Dict]:
        return self.short_term[-limit:]

    def get_context_text(self, limit: int = 10) -> str:
        recent = self.get_conversation_context(limit)
        lines = []
        for entry in recent:
            role = "User" if entry["role"] == "user" else "Assistant"
            lines.append(f"{role}: {entry['content']}")
        return "\n".join(lines)

    def learn_route(self, command_text: str, intent: str, success: bool):
        key = f"{intent}"
        if key not in self.learning_data["routes"]:
            self.learning_data["routes"][key] = {"attempts": 0, "successes": 0}
        self.learning_data["routes"][key]["attempts"] += 1
        if success:
            self.learning_data["routes"][key]["successes"] += 1
        self._save_learning()

    def learn_from_feedback(self, command: str, response: str, rating: int, intent: str):
        self.learning_data["feedback"].append({
            "command": command,
            "response": response,
            "rating": rating,
            "intent": intent,
            "timestamp": datetime.now().isoformat(),
        })
        accuracy_key = f"intent_{intent}"
        if accuracy_key not in self.learning_data.get("accuracy", {}):
            if "accuracy" not in self.learning_data:
                self.learning_data["accuracy"] = {}
            if accuracy_key not in self.learning_data["accuracy"]:
                self.learning_data["accuracy"][accuracy_key] = {"total": 0, "positive": 0}
        self.learning_data["accuracy"][accuracy_key]["total"] += 1
        if rating >= 3:
            self.learning_data["accuracy"][accuracy_key]["positive"] += 1
        self._save_learning()

    def learn_pattern(self, phrase: str, intent: str):
        words = phrase.lower().split()
        for word in words:
            if word not in self.patterns["phrases"]:
                self.patterns["phrases"][word] = {}
            if intent not in self.patterns["phrases"][word]:
                self.patterns["phrases"][word][intent] = 0
            self.patterns["phrases"][word][intent] += 1
        if intent not in self.patterns["intents"]:
            self.patterns["intents"][intent] = {"count": 0, "examples": []}
        self.patterns["intents"][intent]["count"] += 1
        examples = self.patterns["intents"][intent]["examples"]
        if phrase not in examples:
            examples.append(phrase)
            if len(examples) > 20:
                self.patterns["intents"][intent]["examples"] = examples[-20:]
        self._save_patterns()

    def suggest_intent(self, command: str) -> Optional[str]:
        words = command.lower().split()
        scores = defaultdict(int)
        for word in words:
            if word in self.patterns["phrases"]:
                for intent, count in self.patterns["phrases"][word].items():
                    scores[intent] += count
        if scores:
            return max(scores, key=scores.get)
        return None

    def set_preference(self, key: str, value: Any):
        self.preferences[key] = value
        self._save_preferences()

    def get_preference(self, key: str, default: Any = None) -> Any:
        return self.preferences.get(key, default)

    def get_preferences(self) -> Dict:
        return self.preferences

    def get_accuracy_stats(self) -> Dict:
        return self.learning_data.get("accuracy", {})

    def get_route_stats(self) -> Dict:
        return self.learning_data.get("routes", {})

    def get_stats(self) -> Dict:
        return {
            "short_term_count": len(self.short_term),
            "long_term_count": len(self.long_term),
            "patterns_count": len(self.patterns.get("phrases", {})),
            "preferences_count": len(self.preferences),
            "feedback_count": len(self.learning_data.get("feedback", [])),
            "session_id": self.session_id,
        }

    def clear_session(self):
        self.short_term = []
        logger.info("Session memory cleared")

    def export_memory(self, export_path: Optional[str] = None) -> str:
        export_path = export_path or str(MEMORY_DIR / f"memory_export_{int(time.time())}.json")
        payload = {
            "session_id": self.session_id,
            "short_term": self.short_term[-100:],
            "long_term": self.long_term[-500:],
            "learning_data": self.learning_data,
            "patterns": self.patterns,
            "preferences": self.preferences,
        }
        try:
            Path(export_path).write_text(json.dumps(payload, indent=2), encoding="utf-8")
            logger.info(f"Memory exported to {export_path}")
            return export_path
        except Exception as e:
            logger.error(f"Memory export failed: {e}")
            return ""

    def import_memory(self, import_path: str) -> bool:
        try:
            data = json.loads(Path(import_path).read_text(encoding="utf-8"))
            if "long_term" in data:
                self.long_term.extend(data.get("long_term", []))
                self.long_term = self.long_term[-1000:]
                self._save_long_term()
            if "learning_data" in data:
                self.learning_data.update(data["learning_data"])
                self._save_learning()
            if "patterns" in data:
                self.patterns.update(data["patterns"])
                self._save_patterns()
            if "preferences" in data:
                self.preferences.update(data["preferences"])
                self._save_preferences()
            logger.info(f"Memory imported from {import_path}")
            return True
        except Exception as e:
            logger.error(f"Memory import failed: {e}")
            return False

    def persist_long_term_to_postgres(self) -> bool:
        try:
            from services.db_postgres import get_conn
            with get_conn("memory") as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        """
                        CREATE TABLE IF NOT EXISTS long_term_memory (
                            id SERIAL PRIMARY KEY,
                            key VARCHAR(255) NOT NULL,
                            content TEXT NOT NULL,
                            tags TEXT[],
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                        """
                    )
                    for item in self.long_term[-200:]:
                        cur.execute(
                            """
                            INSERT INTO long_term_memory (key, content, tags)
                            VALUES (%s, %s, %s)
                            ON CONFLICT (key) DO UPDATE SET content = EXCLUDED.content, tags = EXCLUDED.tags
                            """,
                            (item.get("key"), json.dumps(item.get("content")), item.get("tags", [])),
                        )
                conn.commit()
            logger.info("Long-term memory persisted to Postgres")
            return True
        except Exception as e:
            logger.error(f"Postgres memory persistence failed: {e}")
            return False

