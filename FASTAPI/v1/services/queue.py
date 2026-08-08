import asyncio
import time
import uuid
from enum import Enum
from typing import Any, Callable, Dict, List, Optional, Awaitable, TypeVar
from dataclasses import dataclass, field
from datetime import datetime, timedelta
from concurrent.futures import ThreadPoolExecutor
import json
from v1.config import settings
from v1.core.cache import get as cache_get, set as cache_set, delete

T = TypeVar('T')


class TaskStatus(Enum):
    PENDING = "pending"
    RUNNING = "running"
    SUCCESS = "success"
    FAILED = "failed"
    RETRIED = "retried"
    CANCELLED = "cancelled"


@dataclass
class Task:
    id: str
    name: str
    func: Callable
    args: tuple = field(default_factory=tuple)
    kwargs: dict = field(default_factory=dict)
    status: TaskStatus = TaskStatus.PENDING
    result: Any = None
    error: Optional[str] = None
    created_at: datetime = field(default_factory=datetime.utcnow)
    started_at: Optional[datetime] = None
    completed_at: Optional[datetime] = None
    retry_count: int = 0
    max_retries: int = 3
    retry_delay: float = 1.0

    def to_dict(self) -> dict:
        return {
            "id": self.id,
            "name": self.name,
            "status": self.status.value,
            "result": self.result,
            "error": self.error,
            "created_at": self.created_at.isoformat() if self.created_at else None,
            "started_at": self.started_at.isoformat() if self.started_at else None,
            "completed_at": self.completed_at.isoformat() if self.completed_at else None,
            "retry_count": self.retry_count,
            "max_retries": self.max_retries,
        }


class TaskQueueManager:
    _instance = None
    _lock = asyncio.Lock()

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
            cls._instance._tasks: Dict[str, Task] = {}
            cls._instance._queue: asyncio.Queue = asyncio.Queue()
            cls._instance._running = False
            cls._instance._workers: List[asyncio.Task] = []
            cls._instance._executor = ThreadPoolExecutor(max_workers=settings.db_pool_size)
        return cls._instance

    async def submit(self, name: str, func: Callable, *args, max_retries: int = 3, retry_delay: float = 1.0, **kwargs) -> str:
        task_id = str(uuid.uuid4())
        task = Task(
            id=task_id,
            name=name,
            func=func,
            args=args,
            kwargs=kwargs,
            max_retries=max_retries,
            retry_delay=retry_delay
        )
        self._tasks[task_id] = task
        
        if self._running:
            await self._queue.put(task)
        
        cache_set(f"task:{task_id}", task.to_dict(), ttl=86400)
        return task_id

    async def submit_async(self, name: str, coro: Awaitable, max_retries: int = 3, retry_delay: float = 1.0) -> str:
        async def wrapper():
            return await coro
        return await self.submit(name, wrapper, max_retries=max_retries, retry_delay=retry_delay)

    def get_task(self, task_id: str) -> Optional[Task]:
        task = self._tasks.get(task_id)
        if task:
            cached = cache_get(f"task:{task_id}")
            if cached:
                pass
        return task

    async def cancel(self, task_id: str) -> bool:
        task = self._tasks.get(task_id)
        if task and task.status == TaskStatus.PENDING:
            task.status = TaskStatus.CANCELLED
            task.completed_at = datetime.utcnow()
            cache_set(f"task:{task_id}", task.to_dict(), ttl=3600)
            return True
        return False

    async def start(self, num_workers: int = 4):
        if self._running:
            return
        
        self._running = True
        
        for i in range(num_workers):
            worker = asyncio.create_task(self._worker(f"worker-{i}"))
            self._workers.append(worker)

    async def stop(self):
        if not self._running:
            return
        
        self._running = False
        
        for worker in self._workers:
            try:
                worker.cancel()
                await worker
            except asyncio.CancelledError:
                pass
        
        self._workers.clear()

    async def _worker(self, name: str):
        while self._running:
            try:
                task = await asyncio.wait_for(self._queue.get(), timeout=1.0)
                await self._process_task(task)
                self._queue.task_done()
            except asyncio.TimeoutError:
                continue
            except asyncio.CancelledError:
                break
            except Exception as e:
                continue

    async def _process_task(self, task: Task):
        task.status = TaskStatus.RUNNING
        task.started_at = datetime.utcnow()
        
        try:
            if asyncio.iscoroutinefunction(task.func):
                result = await task.func(*task.args, **task.kwargs)
            else:
                result = await asyncio.get_event_loop().run_in_executor(
                    self._executor, task.func, *task.args, **task.kwargs
                )
            task.result = result
            task.status = TaskStatus.SUCCESS
        except Exception as e:
            task.error = str(e)
            if task.retry_count < task.max_retries:
                task.retry_count += 1
                task.status = TaskStatus.RETRIED
                await asyncio.sleep(task.retry_delay * task.retry_count)
                if self._running:
                    await self._queue.put(task)
            else:
                task.status = TaskStatus.FAILED
        finally:
            task.completed_at = datetime.utcnow()
            cache_set(f"task:{task.id}", task.to_dict(), ttl=3600)

    def get_task_status(self, task_id: str) -> Optional[dict]:
        task = self._tasks.get(task_id)
        if task:
            return task.to_dict()
        cached = cache_get(f"task:{task_id}")
        if cached:
            return cached if isinstance(cached, dict) else json.loads(cached)
        return None


queue_manager = TaskQueueManager()


async def run_task(name: str, func: Callable, *args, max_retries: int = 3, **kwargs) -> str:
    return await queue_manager.submit(name, func, *args, max_retries=max_retries, **kwargs)


async def run_async_task(name: str, coro: Awaitable, max_retries: int = 3) -> str:
    return await queue_manager.submit_async(name, coro, max_retries=max_retries)