from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/community/tickets")
async def list_tickets(
    status: Optional[str] = None,
    priority: Optional[str] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM community_tickets WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        if priority:
            sql += " AND priority=:priority"
            params["priority"] = priority
        if search:
            sql += " AND (subject LIKE :q OR message LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"tickets": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/community/tickets")
async def create_ticket(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["subject", "category", "priority", "status", "created_by", "assigned_to", "message", "resolution"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO community_tickets ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/community/tickets/{ticket_id}")
async def update_ticket(
    ticket_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("UPDATE community_tickets SET status=:status, resolution=COALESCE(:resolution, resolution) WHERE id=:tid"), {
            "status": payload.get("status", "open"),
            "resolution": payload.get("resolution"),
            "tid": ticket_id,
        })
        await db.commit()
        return {"updated": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/community/forums")
async def list_forums(
    category: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM community_forums WHERE 1=1"
        params = {}
        if category:
            sql += " AND category=:category"
            params["category"] = category
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"forums": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/community/forums")
async def create_forum(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "category", "description", "is_public"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO community_forums ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/community/forums/{forum_id}/posts")
async def get_forum_posts(
    forum_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM community_posts WHERE forum_id=:fid ORDER BY created_at ASC"), {"fid": forum_id})
        rows = result.fetchall()
        return {"posts": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/community/forums/{forum_id}/posts")
async def create_post(
    forum_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["parent_post_id", "username", "title", "content", "likes"]
        data = {k: payload.get(k) for k in fields if k in payload}
        data["forum_id"] = forum_id
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO community_posts ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/community/blog")
async def list_blog_posts(
    status: Optional[str] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM community_blog WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        if search:
            sql += " AND (title LIKE :q OR content LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"posts": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/community/blog")
async def create_blog_post(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "slug", "excerpt", "content", "author", "status", "published_at"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO community_blog ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/community/surveys")
async def list_surveys(
    is_active: Optional[int] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM community_surveys WHERE 1=1"
        params = {}
        if is_active is not None:
            sql += " AND is_active=:is_active"
            params["is_active"] = is_active
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"surveys": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/community/surveys")
async def create_survey(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "description", "is_anonymous", "is_active"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO community_surveys ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/community/polls")
async def list_polls(
    is_active: Optional[int] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT p.*, o.id as option_id, o.option_text, o.votes FROM community_polls p LEFT JOIN community_poll_options o ON o.poll_id=p.id WHERE 1=1"
        params = {}
        if is_active is not None:
            sql += " AND p.is_active=:is_active"
            params["is_active"] = is_active
        sql += " ORDER BY p.created_at DESC, o.id ASC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"polls": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/community/polls")
async def create_poll(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("INSERT INTO community_polls (question, is_active) VALUES (:question, :is_active)"), {
            "question": payload.get("question", ""),
            "is_active": payload.get("is_active", 1),
        })
        poll_id = await db.execute(text("SELECT LAST_INSERT_ID()"))
        poll_id = poll_id.scalar_one()
        options = payload.get("options", [])
        if isinstance(options, list):
            for opt in options:
                await db.execute(text("INSERT INTO community_poll_options (poll_id, option_text) VALUES (:pid, :opt)"), {"pid": poll_id, "opt": opt})
        await db.commit()
        return {"created": True, "poll_id": poll_id}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
