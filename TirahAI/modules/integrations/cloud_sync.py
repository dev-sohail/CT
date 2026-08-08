"""
Cloud Sync Module for TirahAi
Syncs local data files to cloud storage providers.
"""

import json
import logging
import hashlib
from pathlib import Path
from typing import Optional, Dict, List

from utils.logger import get_logger

logger = get_logger(__name__)

DATA_DIR = Path(__file__).resolve().parent.parent.parent / "data"
SYNC_STATE_FILE = DATA_DIR / "cloud_sync_state.json"


class CloudSync:
    def __init__(self):
        self.state: Dict[str, Dict] = {}
        self._load_state()

    def _load_state(self):
        try:
            if SYNC_STATE_FILE.exists():
                self.state = json.loads(SYNC_STATE_FILE.read_text(encoding="utf-8"))
        except Exception as e:
            logger.error(f"Failed to load cloud sync state: {e}")
            self.state = {}

    def _save_state(self):
        try:
            DATA_DIR.mkdir(exist_ok=True)
            SYNC_STATE_FILE.write_text(json.dumps(self.state, indent=2), encoding="utf-8")
        except Exception as e:
            logger.error(f"Failed to save cloud sync state: {e}")

    def _file_hash(self, path: Path) -> str:
        h = hashlib.sha256()
        with open(path, "rb") as f:
            for chunk in iter(lambda: f.read(8192), b""):
                h.update(chunk)
        return h.hexdigest()

    def enqueue(self, file_path: str, provider: str = "local") -> None:
        path = Path(file_path)
        if not path.exists():
            logger.warning(f"Cloud sync skipped missing file: {file_path}")
            return
        file_hash = self._file_hash(path)
        self.state[str(path)] = {
            "provider": provider,
            "hash": file_hash,
            "size": path.stat().st_size,
        }
        self._save_state()
        logger.info(f"Cloud sync enqueued: {path.name}")

    def pending(self) -> List[Dict]:
        return list(self.state.values())

    def sync_to_provider(self, provider: str = "google") -> None:
        logger.info(f"Cloud sync to {provider} requested")
        return None
