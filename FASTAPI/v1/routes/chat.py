from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional, List

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser
from schemas.assistant import ChatStatusPost

router = APIRouter()


@router.get("/chat/status")
async def get_my_status(
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        username = _.username
        result = await db.execute(text("SELECT * FROM chat_statuses WHERE username=:u AND is_active=1 AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC LIMIT 1"), {"u": username})
        row = result.fetchone()
        return {"status": dict(row._mapping) if row else None}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/chat/status")
async def post_status(
    payload: ChatStatusPost,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        data = payload.model_dump(exclude_none=True)
        data["username"] = _.username
        data["role"] = _.role
        await db.execute(text("UPDATE chat_statuses SET is_active=0 WHERE username=:u"), {"u": _.username})
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO chat_statuses ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        result = await db.execute(text("SELECT LAST_INSERT_ID()"))
        return {"ok": True, "id": result.scalar_one()}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/chat/status/viewers")
async def get_status_viewers(
    status_id: int = Query(...),
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT v.viewer_username, u.first_name, u.last_name, v.viewed_at FROM chat_status_views v JOIN users u ON u.username = v.viewer_username WHERE v.status_id=:sid ORDER BY v.viewed_at DESC"), {"sid": status_id})
        rows = result.fetchall()
        return {"viewers": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/chat/status/{status_id}/view")
async def view_status(
    status_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("INSERT IGNORE INTO chat_status_views (status_id, viewer_username) VALUES (:sid, :viewer)"), {"sid": status_id, "viewer": _.username})
        await db.commit()
        return {"ok": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/chat/acknowledged")
async def get_acknowledged_peers(
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("""
            SELECT DISTINCT CASE WHEN m.sender = :me THEN m.receiver ELSE m.sender END AS peer
            FROM user_messages m
            WHERE (m.sender = :me OR m.receiver = :me)
              AND m.group_id IS NULL
              AND (CASE WHEN m.sender = :me THEN m.receiver ELSE m.sender END) <> ''
            ORDER BY MAX(m.created_at) DESC
        """), {"me": _.username})
        rows = result.fetchall()
        return {"acknowledged": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/chat/messages/search")
async def search_messages(
    q: str = Query(...),
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        like = f"%{q}%"
        result = await db.execute(text("""
            SELECT m.*, u.first_name, u.last_name
            FROM user_messages m
            JOIN users u ON u.username = m.sender
            WHERE m.message LIKE :q
              AND (m.sender = :me OR m.receiver = :me)
            ORDER BY m.created_at DESC
            LIMIT 50
        """), {"q": like, "me": _.username})
        rows = result.fetchall()
        return {"messages": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.delete("/chat/messages/{message_id}")
async def delete_message(
    message_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("DELETE FROM user_messages WHERE id=:mid AND (sender=:me OR receiver=:me)"), {"mid": message_id, "me": _.username})
        await db.commit()
        return {"deleted": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
