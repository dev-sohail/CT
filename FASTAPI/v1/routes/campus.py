from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/campus/transport")
async def list_transport(
    status: Optional[str] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM campus_transport WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        if search:
            sql += " AND (vehicle_number LIKE :q OR driver_name LIKE :q OR route_name LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"transport": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/campus/transport")
async def create_vehicle(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["vehicle_number", "driver_name", "route_name", "capacity", "fee", "status"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO campus_transport ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/campus/hostel")
async def list_hostels(
    type: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM campus_hostel WHERE 1=1"
        params = {}
        if type:
            sql += " AND type=:type"
            params["type"] = type
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"hostels": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/campus/hostel")
async def create_hostel(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["name", "type", "capacity", "warden_name", "contact_number", "address", "status"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO campus_hostel ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/campus/canteen")
async def list_canteen(
    category: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM campus_canteen WHERE 1=1"
        params = {}
        if category:
            sql += " AND category=:category"
            params["category"] = category
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"items": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/campus/canteen")
async def create_canteen_item(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["item_name", "category", "price", "quantity", "is_available"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO campus_canteen ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/campus/convocation")
async def list_convocation(
    status: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM campus_convocation WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        sql += " ORDER BY event_date DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"events": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/campus/convocation")
async def create_convocation_event(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["session_name", "event_date", "venue", "chief_guest", "status"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO campus_convocation ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
