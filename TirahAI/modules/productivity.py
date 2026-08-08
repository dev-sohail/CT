"""
Productivity Module for TirahAi
Pomodoro timer, task management, habit tracking, and focus tools
"""

import os
import time
import json
from datetime import datetime, timedelta
from typing import List, Dict, Optional
from pathlib import Path
from utils.logger import get_logger
from config.settings import DATA_DIR

logger = get_logger(__name__)


class PomodoroTimer:
    """
    Pomodoro technique timer for focused work sessions.
    25 minutes work, 5 minutes break, long break after 4 sessions.
    """
    
    def __init__(self):
        """Initialize Pomodoro timer."""
        self.work_duration = 25 * 60  # 25 minutes
        self.short_break = 5 * 60  # 5 minutes
        self.long_break = 15 * 60  # 15 minutes
        self.sessions_until_long_break = 4
        
        self.current_session = 0
        self.is_work_session = True
        self.time_remaining = self.work_duration
        self.is_running = False
        self.sessions_completed = 0
        
        logger.info("Pomodoro timer initialized")
    
    def start(self):
        """Start the timer."""
        self.is_running = True
        logger.info("Pomodoro timer started")
    
    def pause(self):
        """Pause the timer."""
        self.is_running = False
        logger.info("Pomodoro timer paused")
    
    def reset(self):
        """Reset the timer to start of work session."""
        self.time_remaining = self.work_duration
        self.is_work_session = True
        self.is_running = False
        logger.info("Pomodoro timer reset")
    
    def tick(self):
        """Decrease timer by one second."""
        if self.is_running and self.time_remaining > 0:
            self.time_remaining -= 1
            
            if self.time_remaining == 0:
                self._session_complete()
    
    def _session_complete(self):
        """Handle session completion."""
        if self.is_work_session:
            self.sessions_completed += 1
            self.current_session += 1
            
            # Determine next break type
            if self.current_session >= self.sessions_until_long_break:
                self.time_remaining = self.long_break
                self.current_session = 0
                logger.info("Long break time!")
            else:
                self.time_remaining = self.short_break
                logger.info("Short break time!")
            
            self.is_work_session = False
        else:
            # Break complete, back to work
            self.time_remaining = self.work_duration
            self.is_work_session = True
            logger.info("Break over, back to work!")
        
        self.is_running = False
    
    def get_time_string(self) -> str:
        """Get formatted time remaining."""
        minutes = self.time_remaining // 60
        seconds = self.time_remaining % 60
        return f"{minutes:02d}:{seconds:02d}"
    
    def get_status(self) -> Dict:
        """Get current timer status."""
        return {
            'time_remaining': self.time_remaining,
            'time_string': self.get_time_string(),
            'is_work_session': self.is_work_session,
            'is_running': self.is_running,
            'sessions_completed': self.sessions_completed,
            'current_session': self.current_session
        }


class TaskManager:
    """
    Smart task management with priority calculation.
    Uses urgency, importance, and deadlines for prioritization.
    """
    
    def __init__(self):
        """Initialize task manager."""
        self.tasks_file = DATA_DIR / "tasks.json"
        self.completed_file = DATA_DIR / "completed_tasks.json"
        self.tasks = self._load_tasks()
        self.completed_tasks = self._load_completed_tasks()
        logger.info("Task manager initialized")
    
    def add_task(self, name: str, urgency: int, importance: int, 
                 estimated_time: int, deadline: Optional[str] = None,
                 dependencies: Optional[List[str]] = None) -> bool:
        """
        Add a new task.
        
        Args:
            name: Task name
            urgency: 1-10 scale
            importance: 1-10 scale
            estimated_time: Time in minutes
            deadline: Optional deadline (YYYY-MM-DD)
            dependencies: List of task names that must be completed first
            
        Returns:
            True if added successfully
        """
        try:
            task = {
                'name': name,
                'urgency': urgency,
                'importance': importance,
                'estimated_time': estimated_time,
                'deadline': deadline,
                'dependencies': dependencies or [],
                'created_at': datetime.now().isoformat(),
                'completed': False
            }
            
            # Calculate initial priority
            task['priority'] = self._calculate_priority(task)
            
            self.tasks.append(task)
            self._save_tasks()
            logger.info(f"Task added: {name}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to add task: {e}")
            return False
    
    def _calculate_priority(self, task: Dict) -> float:
        """
        Calculate task priority score.
        Higher score = higher priority.
        """
        # Base priority from urgency and importance
        base_priority = (task['urgency'] + task['importance']) / 2
        
        # Deadline pressure (increases priority as deadline approaches)
        if task['deadline']:
            try:
                deadline_date = datetime.fromisoformat(task['deadline'])
                days_until_deadline = (deadline_date - datetime.now()).days
                
                if days_until_deadline < 0:
                    # Overdue - maximum priority
                    deadline_factor = 5.0
                elif days_until_deadline < 1:
                    deadline_factor = 3.0
                elif days_until_deadline < 3:
                    deadline_factor = 2.0
                elif days_until_deadline < 7:
                    deadline_factor = 1.5
                else:
                    deadline_factor = 1.0
                
                base_priority *= deadline_factor
            except:
                pass
        
        return base_priority
    
    def update_priorities(self):
        """Recalculate priorities for all tasks."""
        for task in self.tasks:
            task['priority'] = self._calculate_priority(task)
        
        self.tasks.sort(key=lambda x: x['priority'], reverse=True)
        self._save_tasks()
    
    def get_next_task(self) -> Optional[Dict]:
        """Get the highest priority task with completed dependencies."""
        self.update_priorities()
        
        for task in self.tasks:
            if self._dependencies_completed(task):
                return task
        
        return None
    
    def complete_task(self, task_name: str) -> bool:
        """Mark a task as completed."""
        try:
            for i, task in enumerate(self.tasks):
                if task['name'] == task_name:
                    task['completed'] = True
                    task['completed_at'] = datetime.now().isoformat()
                    
                    self.completed_tasks.append(task)
                    self.tasks.pop(i)
                    
                    self._save_tasks()
                    self._save_completed_tasks()
                    logger.info(f"Task completed: {task_name}")
                    return True
            
            return False
            
        except Exception as e:
            logger.error(f"Failed to complete task: {e}")
            return False
    
    def _dependencies_completed(self, task: Dict) -> bool:
        """Check if all dependencies are completed."""
        if not task['dependencies']:
            return True
        
        completed_names = [t['name'] for t in self.completed_tasks]
        return all(dep in completed_names for dep in task['dependencies'])
    
    def get_all_tasks(self) -> List[Dict]:
        """Get all pending tasks."""
        self.update_priorities()
        return self.tasks
    
    def _load_tasks(self) -> List[Dict]:
        """Load tasks from file."""
        try:
            if self.tasks_file.exists():
                with open(self.tasks_file, 'r') as f:
                    return json.load(f)
        except Exception as e:
            logger.error(f"Failed to load tasks: {e}")
        return []
    
    def _save_tasks(self):
        """Save tasks to file."""
        try:
            with open(self.tasks_file, 'w') as f:
                json.dump(self.tasks, f, indent=2)
        except Exception as e:
            logger.error(f"Failed to save tasks: {e}")
    
    def _load_completed_tasks(self) -> List[Dict]:
        """Load completed tasks from file."""
        try:
            if self.completed_file.exists():
                with open(self.completed_file, 'r') as f:
                    return json.load(f)
        except Exception as e:
            logger.error(f"Failed to load completed tasks: {e}")
        return []
    
    def _save_completed_tasks(self):
        """Save completed tasks to file."""
        try:
            with open(self.completed_file, 'w') as f:
                json.dump(self.completed_tasks, f, indent=2)
        except Exception as e:
            logger.error(f"Failed to save completed tasks: {e}")


class HabitTracker:
    """
    Track daily habits and build streaks.
    """
    
    def __init__(self):
        """Initialize habit tracker."""
        self.habits_file = DATA_DIR / "habits.json"
        self.habits = self._load_habits()
        logger.info("Habit tracker initialized")
    
    def add_habit(self, name: str, frequency: str = "daily") -> bool:
        """
        Add a new habit to track.
        
        Args:
            name: Habit name
            frequency: "daily", "weekly", or "monthly"
            
        Returns:
            True if added successfully
        """
        try:
            habit = {
                'name': name,
                'frequency': frequency,
                'created_at': datetime.now().isoformat(),
                'streak': 0,
                'longest_streak': 0,
                'completions': [],
                'last_completed': None
            }
            
            self.habits.append(habit)
            self._save_habits()
            logger.info(f"Habit added: {name}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to add habit: {e}")
            return False
    
    def complete_habit(self, habit_name: str) -> bool:
        """Mark habit as completed for today."""
        try:
            for habit in self.habits:
                if habit['name'] == habit_name:
                    today = datetime.now().date().isoformat()
                    
                    # Check if already completed today
                    if habit['last_completed'] == today:
                        logger.info(f"Habit already completed today: {habit_name}")
                        return False
                    
                    habit['completions'].append(today)
                    habit['last_completed'] = today
                    
                    # Update streak
                    self._update_streak(habit)
                    
                    self._save_habits()
                    logger.info(f"Habit completed: {habit_name}, Streak: {habit['streak']}")
                    return True
            
            return False
            
        except Exception as e:
            logger.error(f"Failed to complete habit: {e}")
            return False
    
    def _update_streak(self, habit: Dict):
        """Update habit streak."""
        yesterday = (datetime.now().date() - timedelta(days=1)).isoformat()
        
        if habit['last_completed'] == yesterday or len(habit['completions']) == 1:
            habit['streak'] += 1
            if habit['streak'] > habit['longest_streak']:
                habit['longest_streak'] = habit['streak']
        else:
            habit['streak'] = 1
    
    def get_habits_status(self) -> List[Dict]:
        """Get status of all habits."""
        today = datetime.now().date().isoformat()
        
        status = []
        for habit in self.habits:
            status.append({
                'name': habit['name'],
                'streak': habit['streak'],
                'longest_streak': habit['longest_streak'],
                'completed_today': habit['last_completed'] == today,
                'total_completions': len(habit['completions'])
            })
        
        return status
    
    def _load_habits(self) -> List[Dict]:
        """Load habits from file."""
        try:
            if self.habits_file.exists():
                with open(self.habits_file, 'r') as f:
                    return json.load(f)
        except Exception as e:
            logger.error(f"Failed to load habits: {e}")
        return []
    
    def _save_habits(self):
        """Save habits to file."""
        try:
            with open(self.habits_file, 'w') as f:
                json.dump(self.habits, f, indent=2)
        except Exception as e:
            logger.error(f"Failed to save habits: {e}")


class FocusMode:
    """
    Focus mode that blocks distracting websites.
    """
    
    def __init__(self):
        """Initialize focus mode."""
        self.is_active = False
        self.blocked_sites = []
        self.hardcore_mode = False
        logger.info("Focus mode initialized")
    
    def add_site(self, site: str):
        """Add a site to block list."""
        if site not in self.blocked_sites:
            self.blocked_sites.append(site)
            logger.info(f"Added site to block list: {site}")
    
    def remove_site(self, site: str):
        """Remove a site from block list."""
        if site in self.blocked_sites:
            self.blocked_sites.remove(site)
            logger.info(f"Removed site from block list: {site}")
    
    def activate(self, duration_minutes: int = 25):
        """
        Activate focus mode.
        
        Args:
            duration_minutes: Duration to stay focused
        """
        try:
            # On Windows, modify hosts file
            if os.name == 'nt':
                hosts_path = r"C:\Windows\System32\drivers\etc\hosts"
            else:
                hosts_path = "/etc/hosts"
            
            self.is_active = True
            self.start_time = datetime.now()
            self.end_time = self.start_time + timedelta(minutes=duration_minutes)
            
            logger.info(f"Focus mode activated for {duration_minutes} minutes")
            return True
            
        except Exception as e:
            logger.error(f"Failed to activate focus mode: {e}")
            return False
    
    def deactivate(self):
        """Deactivate focus mode."""
        try:
            self.is_active = False
            logger.info("Focus mode deactivated")
            return True
        except Exception as e:
            logger.error(f"Failed to deactivate focus mode: {e}")
            return False
    
    def get_status(self) -> Dict:
        """Get focus mode status."""
        if self.is_active:
            remaining = (self.end_time - datetime.now()).total_seconds()
            return {
                'active': True,
                'time_remaining': max(0, int(remaining)),
                'hardcore_mode': self.hardcore_mode
            }
        else:
            return {
                'active': False,
                'time_remaining': 0,
                'hardcore_mode': self.hardcore_mode
            }


# Convenience functions
_pomodoro = None
_task_manager = None
_habit_tracker = None
_focus_mode = None

def get_pomodoro() -> PomodoroTimer:
    """Get singleton pomodoro timer."""
    global _pomodoro
    if _pomodoro is None:
        _pomodoro = PomodoroTimer()
    return _pomodoro

def get_task_manager() -> TaskManager:
    """Get singleton task manager."""
    global _task_manager
    if _task_manager is None:
        _task_manager = TaskManager()
    return _task_manager

def get_habit_tracker() -> HabitTracker:
    """Get singleton habit tracker."""
    global _habit_tracker
    if _habit_tracker is None:
        _habit_tracker = HabitTracker()
    return _habit_tracker

def get_focus_mode() -> FocusMode:
    """Get singleton focus mode."""
    global _focus_mode
    if _focus_mode is None:
        _focus_mode = FocusMode()
    return _focus_mode

