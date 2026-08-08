"""
Calendar Sync Module for TirahAi
Syncs events between local store and external calendar providers.
"""

import json
import logging
from pathlib import Path
from typing import Optional, Dict, List

from utils.logger import get_logger

logger = get_logger(__name__)

DATA_DIR = Path(__file__).resolve().parent.parent.parent / "data"
CALENDAR_FILE = DATA_DIR / "calendar_events.json"


class CalendarEvent:
    def __init__(self, title: str, start: str, end: str, source: str = "local"):
        self.title = title
        self.start = start
        self.end = end
        self.source = source

    def to_dict(self) -> Dict:
        return {
            "title": self.title,
            "start": self.start,
            "end": self.end,
            "source": self.source,
        }

    @staticmethod
    def from_dict(data: Dict) -> "CalendarEvent":
        return CalendarEvent(
            title=data.get("title", ""),
            start=data.get("start", ""),
            end=data.get("end", ""),
            source=data.get("source", "local"),
        )


class CalendarSync:
    def __init__(self):
        self.events: List[CalendarEvent] = []
        self._load()

    def _load(self):
        try:
            if CALENDAR_FILE.exists():
                data = json.loads(CALENDAR_FILE.read_text(encoding="utf-8"))
                self.events = [CalendarEvent.from_dict(e) for e in data]
        except Exception as e:
            logger.error(f"Failed to load calendar events: {e}")
            self.events = []

    def _save(self):
        try:
            DATA_DIR.mkdir(exist_ok=True)
            CALENDAR_FILE.write_text(
                json.dumps([e.to_dict() for e in self.events], indent=2),
                encoding="utf-8",
            )
        except Exception as e:
            logger.error(f"Failed to save calendar events: {e}")

    def add_event(self, event: CalendarEvent) -> None:
        self.events.append(event)
        self._save()
        logger.info(f"Calendar event added: {event.title}")

    def list_events(self) -> List[Dict]:
        return [e.to_dict() for e in self.events]

    def sync_from_provider(self, provider: str = "google") -> None:
        logger.info(f"Calendar sync from {provider} requested")
        return None
