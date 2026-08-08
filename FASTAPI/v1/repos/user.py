from typing import Optional
from datetime import datetime
from sqlalchemy import select, insert, update, delete
from sqlalchemy.ext.asyncio import AsyncSession
from models.user import User, RefreshToken, AuditLog
from core.security import hash_password, verify_password, create_access_token, create_refresh_token


class UserRepo:
    def __init__(self, db: AsyncSession):
        self.db = db

    async def get_by_email(self, email: str) -> Optional[User]:
        result = await self.db.execute(select(User).where(User.email == email))
        return result.scalar_one_or_none()

    async def get_by_id(self, user_id: int) -> Optional[User]:
        result = await self.db.execute(select(User).where(User.id == user_id))
        return result.scalar_one_or_none()

    async def create(self, email: str, password: str, role: str = "user", **kwargs) -> User:
        user = User(
            email=email,
            hashed_password=hash_password(password),
            role=role,
            **kwargs,
        )
        self.db.add(user)
        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def update(self, user: User, **kwargs) -> User:
        for key, value in kwargs.items():
            if value is not None:
                setattr(user, key, value)
        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def authenticate(self, email: str, password: str) -> Optional[User]:
        user = await self.get_by_email(email)
        if not user or not verify_password(password, user.hashed_password):
            return None
        return user


class RefreshTokenRepo:
    def __init__(self, db: AsyncSession):
        self.db = db

    async def create(self, user_id: int, token: str, expires_at: datetime) -> RefreshToken:
        row = RefreshToken(user_id=user_id, token=token, expires_at=expires_at)
        self.db.add(row)
        await self.db.flush()
        await self.db.refresh(row)
        return row

    async def get_valid(self, token: str) -> Optional[RefreshToken]:
        result = await self.db.execute(
            select(RefreshToken).where(
                RefreshToken.token == token,
                RefreshToken.revoked.is_(False),
                RefreshToken.expires_at > datetime.utcnow(),
            )
        )
        return result.scalar_one_or_none()

    async def revoke(self, token: str) -> None:
        result = await self.db.execute(select(RefreshToken).where(RefreshToken.token == token))
        row = result.scalar_one_or_none()
        if row:
            row.revoked = True
            await self.db.flush()
