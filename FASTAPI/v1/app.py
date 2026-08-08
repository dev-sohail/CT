from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from v1.config import settings
from v1.core.middleware import CorrelationIdMiddleware, RequestLoggingMiddleware
from v1.core.ratelimit import RateLimitMiddleware
from v1.models.base import init_db
from v1.routes import auth, assistant, health, websocket, institute, exam, course, admission, faculty, finance, people, attendance, lms, community, business, campus, gradebook, chat, admin


app = FastAPI(
    title=settings.app_name,
    version="0.1.0",
    docs_url="/docs" if settings.debug else None,
    redoc_url="/redoc" if settings.debug else None,
)


@app.on_event("startup")
async def startup_event():
    init_db()


@app.get("/")
def root():
    return {"message": "TirahAi API v1", "docs": "/docs"}


app.add_middleware(CorrelationIdMiddleware)
app.add_middleware(RequestLoggingMiddleware)
app.add_middleware(RateLimitMiddleware, max_requests=120, window_seconds=60)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[o.strip() for o in settings.cors_origins.split(",") if o.strip()],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(health.router, prefix=settings.api_prefix, tags=["health"])
app.include_router(auth.router, prefix=settings.api_prefix, tags=["auth"])
app.include_router(assistant.router, prefix=settings.api_prefix, tags=["assistant"])
app.include_router(institute.router, prefix=settings.api_prefix, tags=["institute"])
app.include_router(exam.router, prefix=settings.api_prefix, tags=["exam"])
app.include_router(course.router, prefix=settings.api_prefix, tags=["course"])
app.include_router(admission.router, prefix=settings.api_prefix, tags=["admission"])
app.include_router(faculty.router, prefix=settings.api_prefix, tags=["faculty"])
app.include_router(finance.router, prefix=settings.api_prefix, tags=["finance"])
app.include_router(people.router, prefix=settings.api_prefix, tags=["people"])
app.include_router(attendance.router, prefix=settings.api_prefix, tags=["attendance"])
app.include_router(lms.router, prefix=settings.api_prefix, tags=["lms"])
app.include_router(community.router, prefix=settings.api_prefix, tags=["community"])
app.include_router(business.router, prefix=settings.api_prefix, tags=["business"])
app.include_router(campus.router, prefix=settings.api_prefix, tags=["campus"])
app.include_router(gradebook.router, prefix=settings.api_prefix, tags=["gradebook"])
app.include_router(chat.router, prefix=settings.api_prefix, tags=["chat"])
app.include_router(websocket.router, tags=["websocket"])
app.include_router(admin.router, prefix="/admin", tags=["admin"])