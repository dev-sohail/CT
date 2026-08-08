import os
from pydantic_settings import BaseSettings
from pydantic import Field


class Settings(BaseSettings):
    app_name: str = "TirahAi API v1"
    api_prefix: str = "/v1"
    debug: bool = True
    database_url: str = Field(default=os.getenv("DATABASE_URL", "sqlite:///./tirahai_v1.db"))
    secret_key: str = Field(default=os.getenv("SECRET_KEY", "change-me-in-production"))
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 60
    refresh_token_expire_days: int = 30
    cors_origins: str = Field(default=os.getenv("CORS_ORIGINS", "*"))
    tirahai_core_url: str = Field(default=os.getenv("TIRAH_CORE_URL", "http://localhost:8000"))

    class Config:
        env_file = ".env"


settings = Settings()
