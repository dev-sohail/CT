import time
import logging
from typing import Callable
from sqlalchemy.ext.asyncio import AsyncSession
from fastapi import Request, Response
from starlette.middleware.base import BaseHTTPMiddleware

logger = logging.getLogger("fastapi_v1")


class CorrelationIdMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable):
        cid = request.headers.get("X-Correlation-ID") or f"{time.time():.0f}-{id(request)}"
        request.state.correlation_id = cid
        old_factory = logging.getLogRecordFactory()

        def record_factory(*args, **kwargs):
            record = old_factory(*args, **kwargs)
            record.correlation_id = cid
            return record

        logging.setLogRecordFactory(record_factory)
        try:
            response: Response = await call_next(request)
            response.headers["X-Correlation-ID"] = cid
            return response
        finally:
            logging.setLogRecordFactory(old_factory)


class RequestLoggingMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable):
        start = time.time()
        try:
            response: Response = await call_next(request)
            duration_ms = (time.time() - start) * 1000
            logger.info(
                "%s %s %s %s %s",
                request.client.host if request.client else "-",
                request.method,
                request.url.path,
                response.status_code,
                f"{duration_ms:.1f}ms",
            )
            response.headers["X-Process-Time"] = f"{duration_ms:.1f}"
            return response
        except Exception as exc:
            logger.error("Unhandled exception on %s %s: %s", request.method, request.url.path, exc)
            raise


class DBSessionMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable):
        from models.base import SessionLocal
        request.state.db = SessionLocal()
        try:
            return await call_next(request)
        finally:
            await request.state.db.close()
