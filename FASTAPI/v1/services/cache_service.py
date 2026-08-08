import json
import time
from typing import Any, Optional, Callable
from functools import wraps
from dataclasses import dataclass, field

_redis_client = None
_memory_cache: dict[str, tuple[float, Any]] = field(default_factory=dict)

try:
    import redis as _redis_module
    _redis_available = True
except ImportError:
    _redis_available = False


def _init_redis():
    global _redis_client
    if _redis_client is not None:
        return _redis_client
    if not _redis_available:
        return None
    try:
        _redis_client = _redis_module.Redis.from_url(
            "redis://redis:6379/0",
            decode_responses=False,
            socket_connect_timeout=2,
            socket_timeout=2,
        )
        _redis_client.ping()
        return _redis_client
    except Exception:
        return None


class CacheService:
    def __init__(self):
        self._client = None
        self._enabled = True
        self._ttl = 300
        self._cache: dict[str, tuple[float, Any]] = {}

    def _get_client(self):
        if self._client is not None:
            return self._client
        self._client = _init_redis()
        return self._client

    def get(self, key: str) -> Optional[Any]:
        if not self._enabled:
            return None
        
        client = self._get_client()
        if client:
            try:
                data = client.get(f"cache:{key}")
                if data:
                    result = json.loads(data)
                    self._cache[key] = (time.time() + self._ttl, result)
                    return result
            except Exception:
                pass
        
        entry = self._cache.get(key)
        if not entry:
            return None
        expires_at, value = entry
        if time.time() > expires_at:
            self._cache.pop(key, None)
            return None
        return value

    def set(self, key: str, value: Any, ttl: int = None) -> None:
        if not self._enabled:
            return
        
        ttl = ttl or self._ttl
        self._cache[key] = (time.time() + ttl, value)
        
        client = self._get_client()
        if client:
            try:
                client.setex(f"cache:{key}", ttl, json.dumps(value))
            except Exception:
                pass

    def delete(self, key: str) -> None:
        self._cache.pop(key, None)
        client = self._get_client()
        if client:
            try:
                client.delete(f"cache:{key}")
            except Exception:
                pass

    def invalidate_pattern(self, pattern: str) -> None:
        client = self._get_client()
        if client:
            try:
                keys = client.keys(f"cache:{pattern}*")
                if keys:
                    client.delete(*keys)
            except Exception:
                pass


_cache_service = CacheService()


cache_service = _cache_service


def get(key: str) -> Optional[Any]:
    return _cache_service.get(key)


def set(key: str, value: Any, ttl: int = 60) -> None:
    _cache_service.set(key, value, ttl)


def delete(key: str) -> None:
    _cache_service.delete(key)


def invalidate(key: str) -> None:
    delete(key)


def cached(ttl: int = None, key_prefix: str = None):
    def decorator(func: Callable) -> Callable:
        @wraps(func)
        async def wrapper(*args, **kwargs):
            cache_key = None
            if key_prefix:
                cache_key = f"{key_prefix}:{json.dumps(args)}:{json.dumps(kwargs, sort_keys=True)}"
            
            cached_result = get(cache_key) if cache_key else None
            if cached_result is not None:
                return cached_result
            
            result = await func(*args, **kwargs)
            if cache_key and not isinstance(result, Exception):
                set(cache_key, result, ttl or 300)
            return result
        
        return wrapper
    return decorator