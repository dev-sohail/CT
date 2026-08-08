from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from datetime import datetime

from v1.config import settings
from core.security import get_current_user, get_current_active_superuser
from core.cache import get as cache_get, set as cache_set
from models.base import get_async_db
from models.user import User
from repos.user import UserRepo
from schemas.auth import LoginRequest, Token, TokenRefresh, UserOut
from services.assistant import AuthService, AssistantService

router = APIRouter()


@router.post("/auth/login", response_model=Token)
async def login(payload: LoginRequest, db: AsyncSession = Depends(get_async_db)):
    svc = AuthService(db)
    try:
        return await svc.login(payload)
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail=str(e))


@router.post("/auth/refresh", response_model=Token)
async def refresh(token: TokenRefresh, db: AsyncSession = Depends(get_async_db)):
    svc = AuthService(db)
    try:
        return await svc.refresh(token.refresh_token)
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail=str(e))


@router.get("/auth/me", response_model=UserOut)
async def me(current_user: User = Depends(get_current_user)):
    cached = cache_get(f"auth:me:{current_user.id}")
    if cached is not None:
        return cached
    data = UserOut.model_validate(current_user)
    cache_set(f"auth:me:{current_user.id}", data, ttl=300)
    return data


@router.get("/auth/users", response_model=list[UserOut])
async def list_users(db: AsyncSession = Depends(get_async_db), _: User = Depends(get_current_active_superuser)):
    repo = UserRepo(db)
    result = await db.execute(select(User).order_by(User.created_at.desc()))
    return result.scalars().all()
