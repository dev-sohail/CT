from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/attendance/daily")
async def list_daily_attendance(
    date: Optional[str] = None,
    from_date: Optional[str] = None,
    to_date: Optional[str] = None,
    username: Optional[str] = None,
    role: Optional[str] = None,
    status: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM attendance_daily WHERE 1=1"
        params = {}
        if date:
            sql += " AND date=:date"
            params["date"] = date
        if from_date:
            sql += " AND date>=:from_date"
            params["from_date"] = from_date
        if to_date:
            sql += " AND date<=:to_date"
            params["to_date"] = to_date
        if username:
            sql += " AND username=:username"
            params["username"] = username
        if role:
            sql += " AND role=:role"
            params["role"] = role
        if status:
            sql += " AND status=:status"
            params["status"] = status
        sql += " ORDER BY date DESC, username ASC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"records": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/attendance/daily")
async def create_daily_attendance(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO attendance_daily (username, role, date, status, method, check_in, check_out, location, device, verified, remarks) VALUES (:username, :role, :date, :status, :method, :ci, :co, :loc, :dev, :ver, :rmk) ON DUPLICATE KEY UPDATE status=VALUES(status), method=VALUES(method), check_in=VALUES(check_in), check_out=VALUES(check_out), location=VALUES(location), device=VALUES(device), verified=VALUES(verified), remarks=VALUES(remarks)"), {
            "username": payload.get("username", ""),
            "role": payload.get("role", "student"),
            "date": payload.get("date", ""),
            "status": payload.get("status", "present"),
            "method": payload.get("method", "manual"),
            "ci": payload.get("check_in"),
            "co": payload.get("check_out"),
            "loc": payload.get("location", ""),
            "dev": payload.get("device", ""),
            "ver": payload.get("verified", 0),
            "rmk": payload.get("remarks", ""),
        })
        await db.commit()
        return {"saved": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/attendance/leaves")
async def list_leave_requests(
    status: Optional[str] = None,
    username: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM attendance_leave_requests WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        if username:
            sql += " AND username=:username"
            params["username"] = username
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"leaves": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/attendance/leaves")
async def create_leave_request(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO attendance_leave_requests (username, role, leave_type, start_date, end_date, reason) VALUES (:username, :role, :lt, :sd, :ed, :rsn)"), {
            "username": payload.get("username", ""),
            "role": payload.get("role", "student"),
            "lt": payload.get("leave_type", "casual"),
            "sd": payload.get("start_date", ""),
            "ed": payload.get("end_date", ""),
            "rsn": payload.get("reason", ""),
        })
        await db.commit()
        return {"requested": True, "id": stmt.lastrowid}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/attendance/leaves/{leave_id}/approve")
async def approve_leave_request(
    leave_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("UPDATE attendance_leave_requests SET status='approved', approved_by=:by WHERE id=:lid"), {
            "by": payload.get("approved_by", "admin"),
            "lid": leave_id,
        })
        await db.commit()
        return {"approved": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/attendance/leaves/{leave_id}/reject")
async def reject_leave_request(
    leave_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("UPDATE attendance_leave_requests SET status='rejected' WHERE id=:lid"), {"lid": leave_id})
        await db.commit()
        return {"rejected": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/attendance/reports")
async def get_attendance_reports(
    from_date: Optional[str] = None,
    to_date: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT username, role, date, status FROM attendance_daily WHERE 1=1"
        params = {}
        if from_date:
            sql += " AND date>=:from_date"
            params["from_date"] = from_date
        if to_date:
            sql += " AND date<=:to_date"
            params["to_date"] = to_date
        sql += " ORDER BY date DESC, username ASC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"reports": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/attendance/alerts")
async def get_attendance_alerts(
    username: Optional[str] = None,
    from_date: Optional[str] = None,
    to_date: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM attendance_alert_logs WHERE 1=1"
        params = {}
        if username:
            sql += " AND username=:username"
            params["username"] = username
        if from_date:
            sql += " AND DATE(created_at)>=:from_date"
            params["from_date"] = from_date
        if to_date:
            sql += " AND DATE(created_at)<=:to_date"
            params["to_date"] = to_date
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"alerts": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/attendance/alerts")
async def log_attendance_alert(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO attendance_alert_logs (username, alert_type, channel, message, status) VALUES (:username, :atype, :channel, :msg, :status)"), {
            "username": payload.get("username", ""),
            "atype": payload.get("alert_type", "absence"),
            "channel": payload.get("channel", "email"),
            "msg": payload.get("message", ""),
            "status": payload.get("status", "sent"),
        })
        await db.commit()
        return {"logged": True, "id": stmt.lastrowid}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
