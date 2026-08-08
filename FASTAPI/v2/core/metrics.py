import time
from collections import defaultdict
from typing import Any
from fastapi import APIRouter, Request
from fastapi.responses import PlainTextResponse

_requests_total: dict[str, int] = defaultdict(int)
_request_duration_sum: dict[str, float] = defaultdict(float)
_request_duration_count: dict[str, int] = defaultdict(int)
_start_time = time.time()

router = APIRouter()


@router.get("/metrics", response_class=PlainTextResponse)
async def metrics(request: Request):
    key = f"{request.method}:{request.url.path}"
    _requests_total[key] += 1
    _request_duration_sum[key] += 0.0
    _request_duration_count[key] += 1

    lines = [
        "# HELP http_requests_total Total HTTP requests",
        "# TYPE http_requests_total counter",
    ]
    for k, v in sorted(_requests_total.items()):
        lines.append(f'http_requests_total{{method_path="{k}"}} {v}')

    lines.append("# HELP http_request_duration_seconds Total request duration")
    lines.append("# TYPE http_request_duration_seconds counter")
    for k in sorted(_request_duration_sum.keys()):
        lines.append(f'http_request_duration_seconds{{method_path="{k}"}} {_request_duration_sum[k]:.3f}')

    lines.append("# HELP app_uptime_seconds Uptime in seconds")
    lines.append("# TYPE app_uptime_seconds gauge")
    lines.append(f"app_uptime_seconds {time.time() - _start_time:.2f}")

    return "\n".join(lines)


class MetricsMiddleware:
    def __init__(self, app: Any):
        self.app = app

    async def __call__(self, scope: Any, receive: Any, send: Any):
        if scope["type"] != "http":
            await self.app(scope, receive, send)
            return

        method = scope.get("method", "")
        path = scope.get("path", "")
        key = f"{method}:{path}"

        async def send_wrapper(message: Any):
            if message["type"] == "http.response.start":
                status_code = message.get("status", 0)
                if status_code >= 400:
                    _requests_total[f"{method}:{path}:error"] += 1
            await send(message)

        try:
            start = time.time()
            await self.app(scope, receive, send_wrapper)
            duration = time.time() - start
            _requests_total[key] += 1
            _request_duration_sum[key] += duration
            _request_duration_count[key] += 1
        except Exception:
            duration = time.time() - start
            _requests_total[f"{key}:error"] += 1
            _request_duration_sum[f"{key}:error"] += duration
            _request_duration_count[f"{key}:error"] += 1
            raise


def render_metrics_text() -> str:
    lines = [
        "# HELP http_requests_total Total HTTP requests",
        "# TYPE http_requests_total counter",
    ]
    for k, v in sorted(_requests_total.items()):
        lines.append(f'http_requests_total{{method_path="{k}"}} {v}')

    lines.append("# HELP http_request_duration_seconds Total request duration")
    lines.append("# TYPE http_request_duration_seconds counter")
    for k in sorted(_request_duration_sum.keys()):
        lines.append(f'http_request_duration_seconds{{method_path="{k}"}} {_request_duration_sum[k]:.3f}')

    lines.append("# HELP app_uptime_seconds Uptime in seconds")
    lines.append("# TYPE app_uptime_seconds gauge")
    lines.append(f"app_uptime_seconds {time.time() - _start_time:.2f}")
    return "\n".join(lines)

