from sqlalchemy import create_engine
from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker, AsyncSession
from sqlalchemy.orm import sessionmaker, declarative_base
from v1.config import settings

Base = declarative_base()


def _resolve_async_url(url: str) -> str:
    if url.startswith("sqlite://"):
        return url.replace("sqlite://", "sqlite+aiosqlite://", 1)
    if url.startswith("postgresql://"):
        return url.replace("postgresql://", "postgresql+asyncpg://", 1)
    if url.startswith("postgres://"):
        return url.replace("postgres://", "postgresql+asyncpg://", 1)
    return url


engine = create_engine(settings.database_url, echo=settings.debug)
async_engine = create_async_engine(_resolve_async_url(settings.database_url), echo=settings.debug)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
async_session_maker = async_sessionmaker(async_engine, expire_on_commit=False, class_=AsyncSession)


def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


async def get_async_db():
    async with async_session_maker() as session:
        yield session


def init_db():
    Base.metadata.create_all(bind=engine)
