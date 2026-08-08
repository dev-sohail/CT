from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/courses")
async def list_courses(
    grade: Optional[str] = None,
    section: Optional[str] = None,
    teacher: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT lc.*, COUNT(le.student_username) AS enrollment_count FROM lms_courses lc LEFT JOIN lms_enrollments le ON le.course_id=lc.id WHERE 1=1"
        params = {}
        if grade:
            sql += " AND lc.grade=:grade"
            params["grade"] = grade
        if section:
            sql += " AND lc.section=:section"
            params["section"] = section
        if teacher:
            sql += " AND lc.teacher_username=:teacher"
            params["teacher"] = teacher
        sql += " GROUP BY lc.id ORDER BY lc.created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"courses": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/courses/{course_id}")
async def get_course(
    course_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM lms_courses WHERE id=:id"), {"id": course_id})
        row = result.fetchone()
        if not row:
            raise HTTPException(status_code=404, detail="Course not found")
        return {"course": dict(row._mapping)}
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/courses/{course_id}/modules")
async def get_course_modules(
    course_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM lms_modules WHERE course_id=:cid ORDER BY sort_order, id"), {"cid": course_id})
        rows = result.fetchall()
        return {"modules": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/courses/{course_id}/resources")
async def get_course_resources(
    course_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM lms_resources WHERE course_id=:cid ORDER BY id DESC"), {"cid": course_id})
        rows = result.fetchall()
        return {"resources": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/courses/{course_id}/enrollments")
async def get_course_enrollments(
    course_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT student_username, enrolled_at FROM lms_enrollments WHERE course_id=:cid ORDER BY enrolled_at DESC"), {"cid": course_id})
        rows = result.fetchall()
        return {"enrollments": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/courses/{course_id}/progress/{student_username}")
async def get_student_progress(
    course_id: int,
    student_username: str,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM lms_progress WHERE course_id=:cid AND student_username=:stu ORDER BY id ASC"), {"cid": course_id, "stu": student_username})
        rows = result.fetchall()
        return {"progress": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/courses/{course_id}/progress/{student_username}")
async def update_student_progress(
    course_id: int,
    student_username: str,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        module_id = payload.get("module_id")
        completed = bool(payload.get("completed", False))
        await db.execute(text("INSERT INTO lms_progress (course_id, student_username, module_id, completed) VALUES (:cid, :stu, :mid, :comp) ON DUPLICATE KEY UPDATE completed=VALUES(completed), updated_at=NOW()"), {"cid": course_id, "stu": student_username, "mid": module_id, "comp": 1 if completed else 0})
        await db.commit()
        return {"updated": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/courses/{course_id}/certificate/{student_username}")
async def issue_certificate(
    course_id: int,
    student_username: str,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        import secrets
        code = secrets.token_hex(4).upper()
        await db.execute(text("INSERT INTO lms_certificates (course_id, student_username, certificate_code) VALUES (:cid, :stu, :code)"), {"cid": course_id, "stu": student_username, "code": code})
        await db.commit()
        return {"certificate_code": code}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
