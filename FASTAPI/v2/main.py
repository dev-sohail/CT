from __future__ import annotations

import time
import logging
import uuid
from collections import defaultdict
from datetime import datetime, timedelta
from typing import Any, Callable, Dict, List, Optional

from fastapi import APIRouter, Depends, FastAPI, HTTPException, Request, Response, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import PlainTextResponse
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from jose import JWTError, jwt
from passlib.context import CryptContext
from pydantic import BaseModel, EmailStr, Field
from pydantic_settings import BaseSettings
from sqlalchemy import text
from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker, AsyncSession
from starlette.middleware.base import BaseHTTPMiddleware


logger = logging.getLogger("fastapi_v2")

_db_engine = None
_db_session = None


def get_db_engine():
    global _db_engine, _db_session
    if _db_engine is None:
        _db_engine = create_async_engine(settings.database_url, echo=False, future=True)
        _db_session = async_sessionmaker(_db_engine, expire_on_commit=False, class_=AsyncSession)
    return _db_engine, _db_session


class Settings(BaseSettings):
    app_name: str = "TirahAi API v2"
    debug: bool = False
    api_prefix: str = "/v2"
    database_url: str = "sqlite:///./tirahai_v2.db"
    secret_key: str = "change-me-in-production"
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 30
    refresh_token_expire_days: int = 7
    cors_origins: str = "http://localhost:3000"
    tirahai_core_url: str = "http://localhost:8000"

    class Config:
        env_prefix = ""
        case_sensitive = False


settings = Settings()


class VersioningMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable) -> Response:
        response: Response = await call_next(request)
        response.headers["X-API-Version"] = "2"
        return response


class CorrelationIdMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable) -> Response:
        cid = request.headers.get("X-Correlation-ID") or f"{time.time():.0f}-{uuid.uuid4().hex}"
        request.state.correlation_id = cid
        old_factory = logging.getLogRecordFactory()

        def record_factory(*args, **kwargs):
            record = old_factory(*args, **kwargs)
            record.correlation_id = cid
            return record

        logging.setLogRecordFactory(record_factory)
        try:
            response = await call_next(request)
            response.headers["X-Correlation-ID"] = cid
            return response
        finally:
            logging.setLogRecordFactory(old_factory)


class RequestLoggingMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable) -> Response:
        start = time.time()
        try:
            response = await call_next(request)
            duration_ms = (time.time() - start) * 1000
            logger.info(
                "%s %s %s %s %s",
                request.client.host if request.client else "-",
                request.method,
                request.url.path,
                response.status_code,
                f"{duration_ms:.1f}ms",
            )
            response.headers["X-Process-Time"] = f"{duration_ms:.1f}ms"
            return response
        except Exception as exc:
            logger.error(
                "Unhandled exception on %s %s: %s", request.method, request.url.path, exc
            )
            raise


class RateLimitMiddleware(BaseHTTPMiddleware):
    def __init__(self, app, max_requests: int = 100, window_seconds: int = 60) -> None:
        super().__init__(app)
        self.max_requests = max_requests
        self.window_seconds = window_seconds
        self.requests: Dict[str, List[float]] = defaultdict(list)

    async def dispatch(self, request: Request, call_next: Callable) -> Response:
        client_ip = request.client.host if request.client else "unknown"
        now = time.time()
        window = self.requests[client_ip]
        window[:] = [t for t in window if now - t < self.window_seconds]
        if len(window) >= self.max_requests:
            raise HTTPException(
                status_code=status.HTTP_429_TOO_MANY_REQUESTS,
                detail="Rate limit exceeded",
            )
        window.append(now)
        return await call_next(request)


pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
security = HTTPBearer(auto_error=False)


def hash_password(password: str) -> str:
    return pwd_context.hash(password)


def verify_password(plain_password: str, hashed_password: str) -> bool:
    return pwd_context.verify(plain_password, hashed_password)


def create_access_token(data: Dict[str, Any], expires_delta: Optional[timedelta] = None) -> str:
    to_encode = data.copy()
    expire = datetime.utcnow() + (expires_delta or timedelta(minutes=settings.access_token_expire_minutes))
    to_encode.update({"exp": expire})
    return jwt.encode(to_encode, settings.secret_key, algorithm=settings.algorithm)


def create_refresh_token(data: Dict[str, Any]) -> str:
    to_encode = data.copy()
    expire = datetime.utcnow() + timedelta(days=settings.refresh_token_expire_days)
    to_encode.update({"exp": expire, "type": "refresh"})
    return jwt.encode(to_encode, settings.secret_key, algorithm=settings.algorithm)


async def get_current_user(
    credentials: Optional[HTTPAuthorizationCredentials] = Depends(security),
) -> Dict[str, Any]:
    if not credentials:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Not authenticated")
    token = credentials.credentials
    try:
        payload = jwt.decode(token, settings.secret_key, algorithms=[settings.algorithm])
        user_id: str = payload.get("sub")
        if user_id is None:
            raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid token")
    except JWTError:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid token")

    return {
        "id": int(user_id),
        "email": payload.get("email", ""),
        "username": payload.get("username"),
        "full_name": payload.get("full_name"),
        "role": payload.get("role", "user"),
        "is_active": payload.get("is_active", True),
    }


class HealthResponse(BaseModel):
    status: str
    service: str
    version: str


class ReadyResponse(BaseModel):
    status: str
    service: str
    version: str
    checks: Dict[str, str]


class StatusResponse(BaseModel):
    status: str
    api_version: str
    uptime_seconds: float
    features: Dict[str, Any]


class AuthMeResponse(BaseModel):
    id: int
    email: EmailStr
    username: Optional[str] = None
    full_name: Optional[str] = None
    role: str = "user"
    is_active: bool
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    last_login: Optional[datetime] = None
    api_version: str = "2"


class ChatRequestV2(BaseModel):
    message: str = Field(..., min_length=1, max_length=4000)
    session_id: Optional[str] = None
    context: Optional[str] = None
    stream: bool = False


class ToolResultV2(BaseModel):
    tool: str
    result: Dict[str, Any]
    success: bool
    duration_ms: Optional[float] = None


class ChatResponseV2(BaseModel):
    response: str
    intent: str
    confidence: float
    tool_results: List[ToolResultV2]
    session_id: str
    success: bool
    timestamp: datetime
    api_version: str = "2"
    streaming_supported: bool = True


router = APIRouter()
_START_TIME = time.time()


@router.get("/health", response_model=HealthResponse)
async def health() -> HealthResponse:
    return HealthResponse(status="ok", service="fastapi-v2", version="2.0.0")


@router.get("/health/ready", response_model=ReadyResponse)
async def readiness() -> ReadyResponse:
    checks: Dict[str, str] = {}
    try:
        engine, _ = get_db_engine()
        async with engine.connect() as conn:
            await conn.execute(text("SELECT 1"))
        checks["postgresql"] = "ok"
    except Exception as exc:
        checks["postgresql"] = f"error: {exc}"
    try:
        import redis
        r = redis.Redis.from_url("redis://localhost:6379/0", socket_timeout=1)
        r.ping()
        checks["redis"] = "ok"
    except Exception as exc:
        checks["redis"] = f"error: {exc}"
    overall = "ready" if all(v == "ok" for v in checks.values()) else "degraded"
    return ReadyResponse(status=overall, service="fastapi-v2", version="2.0.0", checks=checks)


@router.get("/status", response_model=StatusResponse)
async def get_status() -> StatusResponse:
    return StatusResponse(
        status="ok",
        api_version="2",
        uptime_seconds=time.time() - _START_TIME,
        features={
            "streaming": True,
            "rate_limiting": True,
            "cors": True,
            "versioned_routes": True,
        },
    )


@router.get("/auth/me", response_model=AuthMeResponse)
async def auth_me(current_user: Dict[str, Any] = Depends(get_current_user)) -> AuthMeResponse:
    return AuthMeResponse(
        id=current_user["id"],
        email=current_user["email"],
        username=current_user.get("username"),
        full_name=current_user.get("full_name"),
        role=current_user.get("role", "user"),
        is_active=current_user.get("is_active", True),
        created_at=datetime.utcnow(),
        updated_at=datetime.utcnow(),
        last_login=None,
        api_version="2",
    )


@router.post("/assistant/chat", response_model=ChatResponseV2)
async def assistant_chat(
    payload: ChatRequestV2,
    request: Request,
    current_user: Dict[str, Any] = Depends(get_current_user),
) -> ChatResponseV2:
    if payload.stream:
        request.state.streaming = True

    response_text = (
        "I am your v2 assistant. I received your message and processed it "
        f"as a {payload.message[:20]}... request."
    )
    tool_results = [
        ToolResultV2(
            tool="echo",
            result={"echo": payload.message},
            success=True,
            duration_ms=12.0,
        )
    ]
    return ChatResponseV2(
        response=response_text,
        intent="information_request",
        confidence=0.95,
        tool_results=tool_results,
        session_id=payload.session_id or "session-v2",
        success=True,
        timestamp=datetime.utcnow(),
        api_version="2",
        streaming_supported=True,
    )


app = FastAPI(
    title=settings.app_name,
    version="2.0.0",
    docs_url="/docs" if settings.debug else None,
    redoc_url="/redoc" if settings.debug else None,
)

app.add_middleware(VersioningMiddleware)
app.add_middleware(RequestLoggingMiddleware)
app.add_middleware(RateLimitMiddleware, max_requests=120, window_seconds=60)
app.add_middleware(
    CORSMiddleware,
    allow_origins=[o.strip() for o in settings.cors_origins.split(",") if o.strip()],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(router, prefix=settings.api_prefix, tags=["v2"])

from core import metrics as v2metrics
from fastapi.responses import PlainTextResponse as V2PlainTextResponse

app.add_middleware(v2metrics.MetricsMiddleware)


@app.get("/metrics", response_class=V2PlainTextResponse)
async def metrics_export():
    return v2metrics.render_metrics_text()


@app.get("/")
async def root() -> Dict[str, str]:
    return {
        "message": "TirahAi API v2",
        "docs": "/docs",
        "api_prefix": settings.api_prefix,
    }
