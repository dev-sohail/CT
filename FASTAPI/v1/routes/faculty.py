from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/faculty")
async def list_faculty(
    role: Optional[str] = None,
    department_id: Optional[int] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT f.*, d.name as department_name FROM faculty_profiles f LEFT JOIN institute_departments d ON d.id=f.department_id WHERE 1=1"
        params = {}
        if role:
            sql += " AND f.role=:role"
            params["role"] = role
        if department_id is not None:
            sql += " AND f.department_id=:did"
            params["did"] = department_id
        if search:
            sql += " AND (f.first_name LIKE :q OR f.last_name LIKE :q OR f.email LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY f.created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"faculty": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/faculty/{faculty_id}")
async def get_faculty(
    faculty_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT f.*, d.name as department_name FROM faculty_profiles f LEFT JOIN institute_departments d ON d.id=f.department_id WHERE f.id=:id"), {"id": faculty_id})
        row = result.fetchone()
        if not row:
            raise HTTPException(status_code=404, detail="Faculty not found")
        return {"faculty": dict(row._mapping)}
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/faculty/{faculty_id}/timesheets")
async def get_timesheets(
    faculty_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM faculty_timesheets WHERE faculty_id=:fid ORDER BY date DESC"), {"fid": faculty_id})
        rows = result.fetchall()
        return {"timesheets": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/faculty/{faculty_id}/timesheets")
async def save_timesheet(
    faculty_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO faculty_timesheets (faculty_id, date, check_in, check_out, hours_worked, remarks) VALUES (:fid, :dt, :ci, :co, :hrs, :rmk) ON DUPLICATE KEY UPDATE check_in=VALUES(check_in), check_out=VALUES(check_out), hours_worked=VALUES(hours_worked), remarks=VALUES(remarks)"), {
            "fid": faculty_id,
            "dt": payload.get("date", ""),
            "ci": payload.get("check_in"),
            "co": payload.get("check_out"),
            "hrs": payload.get("hours_worked", 0),
            "rmk": payload.get("remarks", ""),
        })
        await db.commit()
        return {"saved": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/faculty/{faculty_id}/leaves")
async def get_leaves(
    faculty_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM faculty_leaves WHERE faculty_id=:fid ORDER BY start_date DESC"), {"fid": faculty_id})
        rows = result.fetchall()
        return {"leaves": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/faculty/{faculty_id}/leaves")
async def request_leave(
    faculty_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO faculty_leaves (faculty_id, leave_type, start_date, end_date, reason) VALUES (:fid, :lt, :sd, :ed, :rsn)"), {
            "fid": faculty_id,
            "lt": payload.get("leave_type", "casual"),
            "sd": payload.get("start_date", ""),
            "ed": payload.get("end_date", ""),
            "rsn": payload.get("reason", ""),
        })
        await db.commit()
        return {"requested": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/faculty/leaves/{leave_id}/approve")
async def approve_leave(
    leave_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("UPDATE faculty_leaves SET status='approved', approved_by=:uid WHERE id=:lid"), {"uid": (await db.execute(text("SELECT id FROM users WHERE role='admin' LIMIT 1"))).scalar_one_or_none() or 0, "lid": leave_id})
        await db.commit()
        return {"approved": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/faculty/{faculty_id}/appraisals")
async def get_appraisals(
    faculty_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM faculty_appraisals WHERE faculty_id=:fid ORDER BY created_at DESC"), {"fid": faculty_id})
        rows = result.fetchall()
        return {"appraisals": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/faculty/{faculty_id}/appraisals")
async def save_appraisal(
    faculty_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO faculty_appraisals (faculty_id, appraisal_period, score, remarks, reviewed_by) VALUES (:fid, :period, :score, :rmk, :rb)"), {
            "fid": faculty_id,
            "period": payload.get("appraisal_period", ""),
            "score": payload.get("score", 0),
            "rmk": payload.get("remarks", ""),
            "rb": payload.get("reviewed_by", 0),
        })
        await db.commit()
        return {"saved": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
