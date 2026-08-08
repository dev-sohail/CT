from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/lms/courses")
async def list_courses(
    grade: Optional[str] = None,
    section: Optional[str] = None,
    teacher_username: Optional[str] = None,
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
        if teacher_username:
            sql += " AND lc.teacher_username=:tuser"
            params["tuser"] = teacher_username
        sql += " GROUP BY lc.id ORDER BY lc.created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"courses": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/lms/courses")
async def create_course(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "description", "grade", "section", "teacher_username", "start_date", "end_date"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO lms_courses ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/lms/courses/{course_id}/modules")
async def get_modules(
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


@router.post("/lms/courses/{course_id}/modules")
async def create_module(
    course_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "content", "sort_order"]
        data = {k: payload.get(k) for k in fields if k in payload}
        data["course_id"] = course_id
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO lms_modules ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/lms/courses/{course_id}/resources")
async def get_resources(
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


@router.post("/lms/courses/{course_id}/resources")
async def create_resource(
    course_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["module_id", "type", "title", "url"]
        data = {k: payload.get(k) for k in fields if k in payload}
        data["course_id"] = course_id
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO lms_resources ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/lms/courses/{course_id}/enrollments")
async def get_enrollments(
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


@router.post("/lms/courses/{course_id}/enrollments")
async def enroll_student(
    course_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("INSERT IGNORE INTO lms_enrollments (course_id, student_username) VALUES (:cid, :suser)"), {
            "cid": course_id,
            "suser": payload.get("student_username", ""),
        })
        await db.commit()
        return {"enrolled": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/lms/courses/{course_id}/assignments")
async def get_assignments(
    course_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM lms_assignments WHERE course_id=:cid ORDER BY due_date DESC, id DESC"), {"cid": course_id})
        rows = result.fetchall()
        return {"assignments": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/lms/courses/{course_id}/assignments")
async def create_assignment(
    course_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["module_id", "title", "description", "due_date", "total_marks"]
        data = {k: payload.get(k) for k in fields if k in payload}
        data["course_id"] = course_id
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO lms_assignments ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/lms/assignments/{assignment_id}/submissions")
async def get_submissions(
    assignment_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM lms_submissions WHERE assignment_id=:aid ORDER BY submitted_at DESC"), {"aid": assignment_id})
        rows = result.fetchall()
        return {"submissions": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/lms/submissions/{submission_id}/grade")
async def grade_submission(
    submission_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("UPDATE lms_submissions SET obtained_marks=:marks, feedback=:fb, graded_at=NOW() WHERE id=:sid"), {
            "marks": payload.get("obtained_marks", 0),
            "fb": payload.get("feedback", ""),
            "sid": submission_id,
        })
        await db.commit()
        return {"graded": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/lms/courses/{course_id}/quizzes")
async def get_quizzes(
    course_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM lms_quizzes WHERE course_id=:cid ORDER BY created_at DESC"), {"cid": course_id})
        rows = result.fetchall()
        return {"quizzes": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/lms/courses/{course_id}/quizzes")
async def create_quiz(
    course_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "duration_minutes", "total_marks", "pass_marks", "is_published", "anti_cheating"]
        data = {k: payload.get(k) for k in fields if k in payload}
        data["course_id"] = course_id
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO lms_quizzes ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/lms/courses/{course_id}/forums")
async def get_forums(
    course_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM lms_forums WHERE course_id=:cid ORDER BY created_at DESC"), {"cid": course_id})
        rows = result.fetchall()
        return {"forums": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/lms/courses/{course_id}/forums")
async def create_forum(
    course_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "description"]
        data = {k: payload.get(k) for k in fields if k in payload}
        data["course_id"] = course_id
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO lms_forums ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/lms/library")
async def list_library(
    category: Optional[str] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM lms_library WHERE 1=1"
        params = {}
        if category:
            sql += " AND category=:category"
            params["category"] = category
        if search:
            sql += " AND (title LIKE :q OR author LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"items": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/lms/library")
async def add_library_item(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "author", "category", "file_path", "file_type", "file_size"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO lms_library ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
