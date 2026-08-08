from fastapi import APIRouter, Depends, HTTPException, Query
from fastapi.security import HTTPBearer
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional, List
from datetime import datetime, timedelta
from v1.config import settings
from v1.core.cache import get as cache_get
from v1.core.security import get_current_active_superuser
from v1.models.base import get_async_db
from v1.services.cache_service import cache_service, cached

router = APIRouter()
security = HTTPBearer()


@router.get("/settings/system")
async def get_system_settings(
    db: AsyncSession = Depends(get_async_db),
    user=Depends(get_current_active_superuser),
    cache: bool = Query(True, description="Cache the response")
):
    cache_key = "admin:system_settings"
    
    if cache:
        cached_settings = cache_service.get(cache_key)
        if cached_settings:
            return cached_settings
    
    stats = {
        "app_name": settings.app_name,
        "version": "1.0.0",
        "debug_mode": settings.debug,
        "api_prefix": settings.api_prefix,
        "database": {
            "url_host": settings.database_url.split("@")[1].split(":")[0] if "@" in settings.database_url else "localhost",
            "pool_size": settings.db_pool_size,
            "max_overflow": settings.db_max_overflow,
        },
        "cache": {
            "enabled": settings.cache_enabled,
            "ttl_default": settings.redis_ttl_default,
            "redis_host": settings.redis_host,
            "redis_port": settings.redis_port,
        },
        "queue": {
            "enabled": settings.task_queue_enabled,
            "max_in_flight": settings.max_tasks_in_flight,
            "timeout_seconds": settings.task_timeout_seconds,
        },
        "rate_limiting": {
            "enabled": settings.rate_limit_enabled,
            "requests_per_minute": settings.rate_limit_requests_per_minute,
        },
        "timestamp": datetime.utcnow().isoformat(),
    }
    
    cache_service.set(cache_key, stats, ttl=300)
    return stats


@router.get("/settings/views")
async def get_admin_views(
    db: AsyncSession = Depends(get_async_db),
    user=Depends(get_current_active_superuser)
):
    views_data = {}
    
    try:
        result = await db.execute("SELECT 1 as id, COUNT(*) as total FROM users")
        row = result.fetchone()
        views_data["total_users"] = row[1] if row else 0
    except Exception:
        views_data["total_users"] = None

    try:
        result = await db.execute("SELECT 1 as id, COUNT(*) as total FROM audit_logs")
        row = result.fetchone()
        views_data["total_interactions"] = row[1] if row else 0
    except Exception:
        views_data["total_interactions"] = None

    views_data["cache_status"] = {
        "enabled": settings.cache_enabled,
        "backend": "redis" if cache_service._enabled else "memory",
    }
    
    return views_data


@router.post("/settings/cache/invalidate")
async def invalidate_cache(
    pattern: str = Query(..., description="Cache key pattern to invalidate"),
    user=Depends(get_current_active_superuser)
):
    cache_service.invalidate_pattern(pattern)
    return {"message": f"Cache pattern '{pattern}' invalidated successfully"}


@router.get("/settings/health")
async def get_health_status(
    db: AsyncSession = Depends(get_async_db),
    user=Depends(get_current_active_superuser)
):
    import redis
    
    redis_status = "unknown"
    try:
        r = redis.Redis.from_url(settings.redis_url, socket_connect_timeout=5)
        r.ping()
        redis_status = "healthy"
        r.close()
    except Exception:
        redis_status = "unhealthy"

    db_status = "unknown"
    try:
        result = await db.execute("SELECT 1")
        if result:
            db_status = "healthy"
    except Exception:
        db_status = "unhealthy"

    return {
        "database": db_status,
        "cache": redis_status,
        "rate_limiting": "enabled" if settings.rate_limit_enabled else "disabled",
        "task_queue": "enabled" if settings.task_queue_enabled else "disabled",
    }