from sqlalchemy.ext.asyncio import AsyncSession
from repos.user import UserRepo
from core.security import hash_password


async def create_test_user(db: AsyncSession, email="test@test.local", password="testpass", role="user", **kwargs) -> object:
    repo = UserRepo(db)
    user = await repo.create(email=email, password=password, role=role, **kwargs)
    await db.commit()
    await db.refresh(user)
    return user
