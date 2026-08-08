from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/institute/profile")
async def get_institute_profile(db: AsyncSession = Depends(get_async_db), _: User = Depends(get_current_active_superuser)):
    from models.user import Base
    from sqlalchemy import select, text
    try:
        result = await db.execute(text("SELECT * FROM institute_profile LIMIT 1"))
        row = result.fetchone()
        if not row:
            return {"profile": {}}
        return {"profile": dict(row._mapping)}
    except Exception:
        return {"profile": {}}


@router.get("/institute/campuses")
async def get_campuses(db: AsyncSession = Depends(get_async_db), _: User = Depends(get_current_active_superuser)):
    from sqlalchemy import text
    try:
        result = await db.execute(text("SELECT * FROM institute_campuses WHERE status=1 ORDER BY name ASC"))
        rows = result.fetchall()
        return {"campuses": [dict(r._mapping) for r in rows]}
    except Exception:
        return {"campuses": []}


@router.get("/institute/departments")
async def get_departments(db: AsyncSession = Depends(get_async_db), _: User = Depends(get_current_active_superuser)):
    from sqlalchemy import text
    try:
        result = await db.execute(text("SELECT * FROM institute_departments WHERE status=1 ORDER BY name ASC"))
        rows = result.fetchall()
        return {"departments": [dict(r._mapping) for r in rows]}
    except Exception:
        return {"departments": []}


@router.get("/institute/programs")
async def get_programs(db: AsyncSession = Depends(get_async_db), _: User = Depends(get_current_active_superuser)):
    from sqlalchemy import text
    try:
        result = await db.execute(text("SELECT p.*, d.name as department_name FROM institute_programs p LEFT JOIN institute_departments d ON p.department_id=d.id WHERE p.status=1 ORDER BY p.name ASC"))
        rows = result.fetchall()
        return {"programs": [dict(r._mapping) for r in rows]}
    except Exception:
        return {"programs": []}


@router.get("/institute/classes")
async def get_classes(db: AsyncSession = Depends(get_async_db), _: User = Depends(get_current_active_superuser)):
    from sqlalchemy import text
    try:
        result = await db.execute(text("SELECT c.*, p.name as program_name FROM institute_classes c LEFT JOIN institute_programs p ON c.program_id=p.id WHERE c.status=1 ORDER BY c.name ASC, c.section ASC"))
        rows = result.fetchall()
        return {"classes": [dict(r._mapping) for r in rows]}
    except Exception:
        return {"classes": []}


@router.get("/institute/audit-logs")
async def get_audit_logs(limit: int = 100, db: AsyncSession = Depends(get_async_db), _: User = Depends(get_current_active_superuser)):
    from sqlalchemy import text
    try:
        result = await db.execute(text("SELECT * FROM institute_audit_logs ORDER BY created_at DESC LIMIT :limit"), {"limit": limit})
        rows = result.fetchall()
        return {"audit_logs": [dict(r._mapping) for r in rows]}
    except Exception:
        return {"audit_logs": []}
