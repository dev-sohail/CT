from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/gradebook/entries")
async def list_gradebook(
    student_username: Optional[str] = None,
    subject: Optional[str] = None,
    exam_type: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM gradebook_entries WHERE 1=1"
        params = {}
        if student_username:
            sql += " AND student_username=:student_username"
            params["student_username"] = student_username
        if subject:
            sql += " AND subject=:subject"
            params["subject"] = subject
        if exam_type:
            sql += " AND exam_type=:exam_type"
            params["exam_type"] = exam_type
        sql += " ORDER BY exam_date DESC, id DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"entries": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/gradebook/entries")
async def create_gradebook_entry(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["student_username", "subject", "grade", "marks_obtained", "total_marks", "percentage", "exam_type", "exam_date", "teacher_username", "remarks"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO gradebook_entries ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/gradebook/self-assessments")
async def list_self_assessments(
    student_username: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM self_assessments WHERE 1=1"
        params = {}
        if student_username:
            sql += " AND student_username=:student_username"
            params["student_username"] = student_username
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"assessments": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/gradebook/self-assessments")
async def create_self_assessment(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["student_username", "subject", "self_grade", "confidence_level", "reflection"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO self_assessments ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/gradebook/journals")
async def list_journals(
    student_username: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM reflection_journals WHERE 1=1"
        params = {}
        if student_username:
            sql += " AND student_username=:student_username"
            params["student_username"] = student_username
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"journals": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/gradebook/journals")
async def create_journal(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["student_username", "title", "content", "mood", "tags"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO reflection_journals ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/gradebook/goals")
async def list_goals(
    student_username: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM goal_settings WHERE 1=1"
        params = {}
        if student_username:
            sql += " AND student_username=:student_username"
            params["student_username"] = student_username
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"goals": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/gradebook/goals")
async def create_goal(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["student_username", "title", "description", "target_date", "status", "progress_percent"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO goal_settings ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/gradebook/skills")
async def list_skills(
    student_username: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM skill_tracking WHERE 1=1"
        params = {}
        if student_username:
            sql += " AND student_username=:student_username"
            params["student_username"] = student_username
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"skills": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/gradebook/skills")
async def create_skill(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["student_username", "skill_name", "category", "level", "target_level", "evidence"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO skill_tracking ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/gradebook/analysis")
async def list_analysis(
    student_username: Optional[str] = None,
    type: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM strength_weakness_analysis WHERE 1=1"
        params = {}
        if student_username:
            sql += " AND student_username=:student_username"
            params["student_username"] = student_username
        if type:
            sql += " AND type=:type"
            params["type"] = type
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"analysis": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/gradebook/analysis")
async def create_analysis(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["student_username", "type", "area", "score", "recommendation"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO strength_weakness_analysis ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
