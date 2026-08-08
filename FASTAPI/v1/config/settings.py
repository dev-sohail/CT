from pydantic_settings import BaseSettings
from typing import List, Optional


class Settings(BaseSettings):
    app_name: str = "TirahAi API v1"
    debug: bool = False
    api_prefix: str = "/v1"

    database_url: str = "postgresql://tirahai:tirahai@postgres:5432/tirahai"
    secret_key: str = "change-me-in-production"
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 30
    refresh_token_expire_days: int = 7

    cors_origins: str = "*"
    tirahai_core_url: str = "http://localhost:8000"

    redis_url: str = "redis://redis:6379/0"
    redis_ttl_default: int = 300
    cache_enabled: bool = True

    task_queue_enabled: bool = True
    max_tasks_in_flight: int = 100
    task_timeout_seconds: int = 300
    cache_invalidate_on_write: bool = True

    rate_limit_enabled: bool = True
    rate_limit_requests_per_minute: int = 120

    enable_db_pooling: bool = True
    db_pool_size: int = 20
    db_max_overflow: int = 30

    class Config:
        env_prefix = ""
        case_sensitive = False

    @property
    def redis_host(self) -> str:
        if self.redis_url.startswith("redis://"):
            return self.redis_url[10:].split("/")[0].split(":")[0]
        return "redis"

    @property
    def redis_port(self) -> int:
        host_port = self.redis_url[10:].split("/")[0]
        if ":" in host_port:
            return int(host_port.split(":")[1])
        return 6379


settings = Settings()
