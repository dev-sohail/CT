"""
Journal and Wellness Module for TirahAi
Digital journaling, mood tracking, and self-reflection tools
"""

import json
import sqlite3
from datetime import datetime
from typing import List, Dict, Optional
from pathlib import Path

try:
    from cryptography.fernet import Fernet
except Exception:
    Fernet = None

try:
    from textblob import TextBlob
except Exception:
    TextBlob = None

from utils.logger import get_logger
from config.settings import DATA_DIR

logger = get_logger(__name__)


class JournalManager:
    """
    Encrypted digital journal with sentiment analysis.
    """
    
    def __init__(self):
        """Initialize journal manager."""
        self.db_path = DATA_DIR / "journal.db"
        self.key_file = DATA_DIR / "journal.key"
        self.encryption_key = self._load_or_create_key()
        self.cipher = Fernet(self.encryption_key) if Fernet and self.encryption_key else None
        self._init_database()
        logger.info("Journal manager initialized")
    
    def _load_or_create_key(self) -> bytes:
        """Load or create encryption key."""
        if Fernet is None:
            logger.warning("cryptography not installed; journal encryption disabled")
            return b""
        try:
            if self.key_file.exists():
                with open(self.key_file, 'rb') as f:
                    return f.read()
            else:
                key = Fernet.generate_key()
                with open(self.key_file, 'wb') as f:
                    f.write(key)
                return key
        except Exception as e:
            logger.error(f"Key management error: {e}")
            return b""
    
    def _init_database(self):
        """Initialize SQLite database."""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS entries (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT,
                    content_encrypted TEXT,
                    sentiment_score REAL,
                    sentiment_label TEXT,
                    created_at TEXT,
                    modified_at TEXT,
                    tags TEXT
                )
            ''')
            
            conn.commit()
            conn.close()
            logger.info("Journal database initialized")
            
        except Exception as e:
            logger.error(f"Database initialization error: {e}")
    
    def _encrypt_text(self, text: str) -> str:
        """Encrypt text."""
        if self.cipher is None:
            return text
        try:
            return self.cipher.encrypt(text.encode()).decode()
        except Exception as e:
            logger.error(f"Encryption error: {e}")
            return text
    
    def _decrypt_text(self, encrypted_text: str) -> str:
        """Decrypt text."""
        if self.cipher is None:
            return encrypted_text
        try:
            return self.cipher.decrypt(encrypted_text.encode()).decode()
        except Exception as e:
            logger.error(f"Decryption error: {e}")
            return encrypted_text
    
    def _analyze_sentiment(self, text: str) -> Dict:
        """
        Analyze sentiment of text.
        
        Returns:
            Dictionary with sentiment score and label
        """
        if TextBlob is None:
            return {'score': 0.0, 'label': 'Neutral'}
        try:
            blob = TextBlob(text)
            polarity = blob.sentiment.polarity
            
            if polarity > 0.3:
                label = "Positive"
            elif polarity < -0.3:
                label = "Negative"
            else:
                label = "Neutral"
            
            return {
                'score': polarity,
                'label': label
            }
        except Exception as e:
            logger.error(f"Sentiment analysis error: {e}")
            return {'score': 0.0, 'label': 'Neutral'}
    
    def create_entry(self, title: str, content: str, tags: List[str] = None) -> int:
        """
        Create a new journal entry.
        
        Args:
            title: Entry title
            content: Entry content
            tags: Optional list of tags
            
        Returns:
            Entry ID
        """
        try:
            # Encrypt content
            encrypted_content = self._encrypt_text(content)
            
            # Analyze sentiment
            sentiment = self._analyze_sentiment(content)
            
            # Store entry
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            now = datetime.now().isoformat()
            tags_str = json.dumps(tags or [])
            
            cursor.execute('''
                INSERT INTO entries 
                (title, content_encrypted, sentiment_score, sentiment_label, 
                 created_at, modified_at, tags)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ''', (title, encrypted_content, sentiment['score'], sentiment['label'],
                  now, now, tags_str))
            
            entry_id = cursor.lastrowid
            conn.commit()
            conn.close()
            
            logger.info(f"Journal entry created: ID {entry_id}")
            return entry_id
            
        except Exception as e:
            logger.error(f"Failed to create entry: {e}")
            return -1
    
    def get_entry(self, entry_id: int) -> Optional[Dict]:
        """Get a specific journal entry."""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            cursor.execute('''
                SELECT id, title, content_encrypted, sentiment_score, 
                       sentiment_label, created_at, modified_at, tags
                FROM entries WHERE id = ?
            ''', (entry_id,))
            
            row = cursor.fetchone()
            conn.close()
            
            if row:
                return {
                    'id': row[0],
                    'title': row[1],
                    'content': self._decrypt_text(row[2]),
                    'sentiment_score': row[3],
                    'sentiment_label': row[4],
                    'created_at': row[5],
                    'modified_at': row[6],
                    'tags': json.loads(row[7])
                }
            
            return None
            
        except Exception as e:
            logger.error(f"Failed to get entry: {e}")
            return None
    
    def get_recent_entries(self, limit: int = 10) -> List[Dict]:
        """Get recent journal entries."""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            cursor.execute('''
                SELECT id, title, sentiment_label, created_at
                FROM entries
                ORDER BY created_at DESC
                LIMIT ?
            ''', (limit,))
            
            rows = cursor.fetchall()
            conn.close()
            
            return [
                {
                    'id': row[0],
                    'title': row[1],
                    'sentiment_label': row[2],
                    'created_at': row[3]
                }
                for row in rows
            ]
            
        except Exception as e:
            logger.error(f"Failed to get recent entries: {e}")
            return []
    
    def search_entries(self, query: str) -> List[Dict]:
        """Search journal entries by title or tags."""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            cursor.execute('''
                SELECT id, title, sentiment_label, created_at
                FROM entries
                WHERE title LIKE ? OR tags LIKE ?
                ORDER BY created_at DESC
            ''', (f'%{query}%', f'%{query}%'))
            
            rows = cursor.fetchall()
            conn.close()
            
            return [
                {
                    'id': row[0],
                    'title': row[1],
                    'sentiment_label': row[2],
                    'created_at': row[3]
                }
                for row in rows
            ]
            
        except Exception as e:
            logger.error(f"Failed to search entries: {e}")
            return []
    
    def delete_entry(self, entry_id: int) -> bool:
        """Delete a journal entry."""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            cursor.execute('DELETE FROM entries WHERE id = ?', (entry_id,))
            
            conn.commit()
            conn.close()
            
            logger.info(f"Journal entry deleted: ID {entry_id}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to delete entry: {e}")
            return False


class MoodTracker:
    """
    Track daily moods and emotions.
    """
    
    def __init__(self):
        """Initialize mood tracker."""
        self.db_path = DATA_DIR / "mood.db"
        self._init_database()
        logger.info("Mood tracker initialized")
    
    def _init_database(self):
        """Initialize mood database."""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS mood_entries (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    mood TEXT,
                    energy_level INTEGER,
                    notes TEXT,
                    timestamp TEXT
                )
            ''')
            
            conn.commit()
            conn.close()
            
        except Exception as e:
            logger.error(f"Mood database initialization error: {e}")
    
    def log_mood(self, mood: str, energy_level: int, notes: str = "") -> bool:
        """
        Log current mood.
        
        Args:
            mood: Mood description (happy, sad, anxious, etc.)
            energy_level: Energy level 1-10
            notes: Optional notes
            
        Returns:
            True if logged successfully
        """
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            timestamp = datetime.now().isoformat()
            
            cursor.execute('''
                INSERT INTO mood_entries (mood, energy_level, notes, timestamp)
                VALUES (?, ?, ?, ?)
            ''', (mood, energy_level, notes, timestamp))
            
            conn.commit()
            conn.close()
            
            logger.info(f"Mood logged: {mood}, Energy: {energy_level}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to log mood: {e}")
            return False
    
    def get_mood_history(self, days: int = 7) -> List[Dict]:
        """Get mood history for the past N days."""
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            cursor.execute('''
                SELECT mood, energy_level, notes, timestamp
                FROM mood_entries
                ORDER BY timestamp DESC
                LIMIT ?
            ''', (days * 5,))  # Assume multiple entries per day
            
            rows = cursor.fetchall()
            conn.close()
            
            return [
                {
                    'mood': row[0],
                    'energy_level': row[1],
                    'notes': row[2],
                    'timestamp': row[3]
                }
                for row in rows
            ]
            
        except Exception as e:
            logger.error(f"Failed to get mood history: {e}")
            return []
    
    def get_mood_summary(self, days: int = 7) -> Dict:
        """Get mood summary statistics."""
        try:
            history = self.get_mood_history(days)
            
            if not history:
                return {
                    'average_energy': 0,
                    'most_common_mood': 'N/A',
                    'total_entries': 0
                }
            
            # Calculate averages
            energy_levels = [entry['energy_level'] for entry in history]
            average_energy = sum(energy_levels) / len(energy_levels)
            
            # Most common mood
            from collections import Counter
            mood_counts = Counter(entry['mood'] for entry in history)
            most_common_mood = mood_counts.most_common(1)[0][0]
            
            return {
                'average_energy': round(average_energy, 1),
                'most_common_mood': most_common_mood,
                'total_entries': len(history)
            }
            
        except Exception as e:
            logger.error(f"Failed to get mood summary: {e}")
            return {}


class GoalTracker:
    """
    Track personal goals and progress.
    """
    
    def __init__(self):
        """Initialize goal tracker."""
        self.goals_file = DATA_DIR / "goals.json"
        self.goals = self._load_goals()
        logger.info("Goal tracker initialized")
    
    def add_goal(self, title: str, description: str, target_date: str, 
                 category: str = "Personal") -> bool:
        """
        Add a new goal.
        
        Args:
            title: Goal title
            description: Goal description
            target_date: Target completion date (YYYY-MM-DD)
            category: Goal category
            
        Returns:
            True if added successfully
        """
        try:
            goal = {
                'id': len(self.goals) + 1,
                'title': title,
                'description': description,
                'target_date': target_date,
                'category': category,
                'progress': 0,
                'created_at': datetime.now().isoformat(),
                'completed': False
            }
            
            self.goals.append(goal)
            self._save_goals()
            logger.info(f"Goal added: {title}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to add goal: {e}")
            return False
    
    def update_progress(self, goal_id: int, progress: int) -> bool:
        """Update goal progress (0-100%)."""
        try:
            for goal in self.goals:
                if goal['id'] == goal_id:
                    goal['progress'] = min(100, max(0, progress))
                    
                    if goal['progress'] >= 100:
                        goal['completed'] = True
                    
                    self._save_goals()
                    logger.info(f"Goal progress updated: {goal['title']} - {progress}%")
                    return True
            
            return False
            
        except Exception as e:
            logger.error(f"Failed to update goal progress: {e}")
            return False
    
    def get_active_goals(self) -> List[Dict]:
        """Get all active (non-completed) goals."""
        return [goal for goal in self.goals if not goal['completed']]
    
    def get_completed_goals(self) -> List[Dict]:
        """Get all completed goals."""
        return [goal for goal in self.goals if goal['completed']]
    
    def _load_goals(self) -> List[Dict]:
        """Load goals from file."""
        try:
            if self.goals_file.exists():
                with open(self.goals_file, 'r') as f:
                    return json.load(f)
        except Exception as e:
            logger.error(f"Failed to load goals: {e}")
        return []
    
    def _save_goals(self):
        """Save goals to file."""
        try:
            with open(self.goals_file, 'w') as f:
                json.dump(self.goals, f, indent=2)
        except Exception as e:
            logger.error(f"Failed to save goals: {e}")


# Convenience functions
_journal = None
_mood_tracker = None
_goal_tracker = None

def get_journal() -> JournalManager:
    """Get singleton journal manager."""
    global _journal
    if _journal is None:
        _journal = JournalManager()
    return _journal

def get_mood_tracker() -> MoodTracker:
    """Get singleton mood tracker."""
    global _mood_tracker
    if _mood_tracker is None:
        _mood_tracker = MoodTracker()
    return _mood_tracker

def get_goal_tracker() -> GoalTracker:
    """Get singleton goal tracker."""
    global _goal_tracker
    if _goal_tracker is None:
        _goal_tracker = GoalTracker()
    return _goal_tracker

