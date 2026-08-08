from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/people/students")
async def list_students(
    grade: Optional[str] = None,
    section: Optional[str] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.student_id, u.guardian_name, u.guardian_contact, u.grade, u.status FROM users u WHERE u.role='student'"
        params = {}
        if grade:
            sql += " AND u.grade=:grade"
            params["grade"] = grade
        if section:
            sql += " AND u.section=:section"
            params["section"] = section
        if search:
            sql += " AND (u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q OR u.username LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY u.first_name ASC, u.last_name ASC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"students": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/people/parents")
async def list_parents(
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone_number FROM users u WHERE u.role='parent'"
        params = {}
        if search:
            sql += " AND (u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY u.first_name ASC, u.last_name ASC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"parents": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/people/teachers")
async def list_teachers(
    grade: Optional[str] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.grade, u.status FROM users u WHERE u.role='teacher'"
        params = {}
        if grade:
            sql += " AND u.grade=:grade"
            params["grade"] = grade
        if search:
            sql += " AND (u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY u.first_name ASC, u.last_name ASC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"teachers": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/people/parent-student/links")
async def link_parent_student(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("INSERT IGNORE INTO parent_student_links (parent_id, student_id) VALUES (:pid, :sid)"), {
            "pid": payload.get("parent_id"),
            "sid": payload.get("student_id"),
        })
        await db.commit()
        return {"linked": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.delete("/people/parent-student/links")
async def unlink_parent_student(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("DELETE FROM parent_student_links WHERE parent_id=:pid AND student_id=:sid"), {
            "pid": payload.get("parent_id"),
            "sid": payload.get("student_id"),
        })
        await db.commit()
        return {"unlinked": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/people/students/{student_id}/progress")
async def get_progress_reports(
    student_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM progress_reports WHERE student_id=:sid ORDER BY report_date DESC"), {"sid": student_id})
        rows = result.fetchall()
        return {"reports": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/people/students/{student_id}/progress")
async def save_progress_report(
    student_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO progress_reports (student_id, title, content, report_date, created_by) VALUES (:sid, :title, :content, :dt, :by)"), {
            "sid": student_id,
            "title": payload.get("title", ""),
            "content": payload.get("content", ""),
            "dt": payload.get("report_date", ""),
            "by": payload.get("created_by", "admin"),
        })
        await db.commit()
        return {"saved": True, "id": stmt.lastrowid}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
