from uuid import uuid4
from datetime import datetime, timedelta
from typing import Optional
import httpx

from v1.config import settings
from schemas.auth import Token, LoginRequest
from schemas.assistant import ChatRequest, ChatResponse, ToolRequest, ToolResponse, CapabilitiesResponse
from repos.user import UserRepo, RefreshTokenRepo
from core.security import create_access_token, create_refresh_token


class AuthService:
    def __init__(self, db):
        self.users = UserRepo(db)
        self.refresh = RefreshTokenRepo(db)
        self.db = db

    async def login(self, payload: LoginRequest) -> Token:
        user = await self.users.authenticate(payload.email, payload.password)
        if not user:
            raise ValueError("Invalid email or password")
        if not user.is_active:
            raise ValueError("Inactive user")

        access = create_access_token({"sub": str(user.id), "role": user.role})
        refresh = create_refresh_token({"sub": str(user.id), "jti": str(uuid4())})
        await self.refresh.create(user.id, refresh, datetime.utcnow() + timedelta(days=settings.refresh_token_expire_days))

        return Token(
            access_token=access,
            refresh_token=refresh,
            expires_in=settings.access_token_expire_minutes * 60,
            user=user,
        )

    async def refresh(self, token: str) -> Token:
        row = await self.refresh.get_valid(token)
        if not row:
            raise ValueError("Invalid or expired refresh token")
        user = await self.users.get_by_id(row.user_id)
        if not user or not user.is_active:
            raise ValueError("User not found or inactive")

        access = create_access_token({"sub": str(user.id), "role": user.role})
        refresh = create_refresh_token({"sub": str(user.id), "jti": str(uuid4())})
        await self.refresh.revoke(token)
        await self.refresh.create(user.id, refresh, datetime.utcnow() + timedelta(days=settings.refresh_token_expire_days))
        return Token(
            access_token=access,
            refresh_token=refresh,
            expires_in=settings.access_token_expire_minutes * 60,
            user=user,
        )


class AssistantService:
    def __init__(self):
        self.core_url = settings.tirahai_core_url.rstrip("/")

    async def chat(self, payload: ChatRequest) -> ChatResponse:
        async with httpx.AsyncClient(timeout=60) as client:
            resp = await client.post(
                f"{self.core_url}/v1/assistant/chat",
                json=payload.model_dump(),
                headers={"Content-Type": "application/json"},
            )
            resp.raise_for_status()
            return ChatResponse(**resp.json())

    async def execute_tool(self, payload: ToolRequest) -> ToolResponse:
        async with httpx.AsyncClient(timeout=60) as client:
            resp = await client.post(
                f"{self.core_url}/v1/tools/execute",
                json=payload.model_dump(),
                headers={"Content-Type": "application/json"},
            )
            resp.raise_for_status()
            data = resp.json()
            return ToolResponse(tool=payload.tool, result=data, success=data.get("success", True))

    async def capabilities(self) -> CapabilitiesResponse:
        async with httpx.AsyncClient(timeout=30) as client:
            resp = await client.get(f"{self.core_url}/v1/capabilities")
            resp.raise_for_status()
            return CapabilitiesResponse(**resp.json())
