import threading
import queue
import time
from typing import Callable, Any

class TaskRunner:
    def __init__(self, workers: int = 2):
        self._q = queue.Queue()
        self._stop = threading.Event()
        self._threads = [threading.Thread(target=self._loop, daemon=True) for _ in range(max(1, workers))]
        for t in self._threads:
            t.start()

    def submit(self, fn: Callable[..., Any], *args, **kwargs):
        fut = _Future()
        self._q.put((fut, fn, args, kwargs))
        return fut

    def cancel_all(self):
        self._stop.set()
        while not self._q.empty():
            try:
                fut, _, _, _ = self._q.get_nowait()
                fut.cancel()
            except:
                break

    def _loop(self):
        while not self._stop.is_set():
            try:
                fut, fn, args, kwargs = self._q.get(timeout=0.2)
            except queue.Empty:
                continue
            if fut.cancelled:
                continue
            try:
                res = fn(*args, **kwargs)
                fut.set_result(res)
            except Exception as e:
                fut.set_exception(e)

class _Future:
    def __init__(self):
        self._done = threading.Event()
        self._result = None
        self._exc = None
        self.cancelled = False

    def cancel(self):
        self.cancelled = True

    def set_result(self, result):
        self._result = result
        self._done.set()

    def set_exception(self, exc):
        self._exc = exc
        self._done.set()

    def result(self, timeout: float = None):
        if not self._done.wait(timeout):
            raise TimeoutError()
        if self._exc:
            raise self._exc
        return self._result
