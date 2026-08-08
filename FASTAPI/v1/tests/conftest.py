import pytest
import models.base as base_module
from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker, AsyncSession
from sqlalchemy.pool import StaticPool

from tests.seeder import create_test_user


@pytest.fixture(scope="session")
def async_engine():
    engine = create_async_engine(
        "sqlite+aiosqlite:///:memory:",
        connect_args={"check_same_thread": False},
        poolclass=StaticPool,
    )
    return engine


@pytest.fixture(scope="session", autouse=True)
def setup_async_db(async_engine):
    base_module.async_engine = async_engine
    base_module.async_session_maker = async_sessionmaker(async_engine, expire_on_commit=False, class_=AsyncSession)


@pytest.fixture(autouse=True)
async def _create_tables(async_engine):
    async with async_engine.begin() as conn:
        await conn.run_sync(base_module.Base.metadata.create_all)
    yield
    async with async_engine.begin() as conn:
        await conn.run_sync(base_module.Base.metadata.drop_all)


@pytest.fixture()
async def db_session():
    session = base_module.async_session_maker()
    yield session
    await session.close()


@pytest.fixture()
async def client():
    from app import app
    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as ac:
        yield ac


@pytest.fixture()
async def admin_user(db_session):
    return await create_test_user(db_session, role="admin")


@pytest.fixture()
async def auth_headers(admin_user):
    from core.security import create_access_token
    token = create_access_token({"sub": str(admin_user.id), "role": admin_user.role})
    return {"Authorization": f"Bearer {token}"}
