import json
import time
import asyncio
from typing import Optional, Any, Union

try:
    import redis
    REDIS_AVAILABLE = True
except ImportError:
    REDIS_AVAILABLE = False
    redis = None

from v1.config import settings


class RedisCache:
    _instance = None
    _lock = asyncio.Lock()

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
            cls._instance._client = None
            cls._instance._fallback_cache: dict[str, tuple[float, Any]] = {}
        return cls._instance

    def _get_client(self):
        if self._client is not None:
            return self._client
        if not REDIS_AVAILABLE:
            return None
        try:
            self._client = redis.Redis.from_url(
                settings.redis_url,
                decode_responses=False,
                socket_connect_timeout=5,
                socket_timeout=5,
            )
            self._client.ping()
            return self._client
        except Exception:
            return None

    def get(self, key: str) -> Optional[Any]:
        if not settings.cache_enabled:
            return self._get_fallback(key)

        client = self._get_client()
        if client:
            try:
                data = client.get(f"cache:{key}")
                if data:
                    return json.loads(data)
            except Exception:
                pass
        return self._get_fallback(key)

    def set(self, key: str, value: Any, ttl: int = 60) -> None:
        if not settings.cache_enabled:
            self._set_fallback(key, value, ttl)
            return

        self._set_fallback(key, value, ttl)

        client = self._get_client()
        if client:
            try:
                client.setex(f"cache:{key}", ttl, json.dumps(value))
            except Exception:
                pass

    def delete(self, key: str) -> None:
        self._delete_fallback(key)

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

    def _get_fallback(self, key: str) -> Optional[Any]:
        entry = self._fallback_cache.get(key)
        if not entry:
            return None
        expires_at, value = entry
        if time.time() > expires_at:
            self._fallback_cache.pop(key, None)
            return None
        return value

    def _set_fallback(self, key: str, value: Any, ttl: int = 60) -> None:
        self._fallback_cache[key] = (time.time() + ttl, value)

    def _delete_fallback(self, key: str) -> None:
        self._fallback_cache.pop(key, None)


_cache_instance = RedisCache()


def get(key: str) -> Optional[Any]:
    return _cache_instance.get(key)


def set(key: str, value: Any, ttl: int = 60) -> None:
    _cache_instance.set(key, value, ttl)


def delete(key: str) -> None:
    _cache_instance.delete(key)


def invalidate(key: str) -> None:
    _cache_instance.delete(key)


def invalidate_pattern(pattern: str) -> None:
    _cache_instance.invalidate_pattern(pattern)