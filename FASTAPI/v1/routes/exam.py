from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/exam/schedules")
async def list_schedules(
    exam_type: Optional[str] = None,
    grade_id: Optional[int] = None,
    section_id: Optional[int] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT s.*, sub.subject_name FROM exam_schedules s LEFT JOIN exam_subjects sub ON s.subject_id=sub.id WHERE 1=1"
        params = {}
        if exam_type:
            sql += " AND s.exam_type=:exam_type"
            params["exam_type"] = exam_type
        if grade_id is not None:
            sql += " AND s.grade_id=:grade_id"
            params["grade_id"] = grade_id
        if section_id is not None:
            sql += " AND s.section_id=section_id"
            params["section_id"] = section_id
        sql += " ORDER BY s.exam_date ASC, s.start_time ASC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"schedules": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/exam/schedules/{schedule_id}")
async def get_schedule(
    schedule_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM exam_schedules WHERE id=:id"), {"id": schedule_id})
        row = result.fetchone()
        if not row:
            raise HTTPException(status_code=404, detail="Schedule not found")
        return {"schedule": dict(row._mapping)}
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/exam/schedules/{schedule_id}/seating")
async def get_seating(
    schedule_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM exam_seating WHERE exam_id=:eid ORDER BY seat_number ASC"), {"eid": schedule_id})
        rows = result.fetchall()
        return {"seating": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/exam/schedules/{schedule_id}/invigilators")
async def get_invigilators(
    schedule_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM exam_invigilators WHERE exam_id=:eid ORDER BY room ASC, shift ASC"), {"eid": schedule_id})
        rows = result.fetchall()
        return {"invigilators": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/exam/grade/{paper_id}")
async def grade_exam(
    paper_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT answer_key_json FROM exam_papers WHERE id=:pid"), {"pid": paper_id})
        row = result.fetchone()
        if not row or not row._mapping.get("answer_key_json"):
            raise HTTPException(status_code=404, detail="Answer key not found")
        answer_key = row._mapping["answer_key_json"]
        if isinstance(answer_key, str):
            import json
            answer_key = json.loads(answer_key)
        student_answers = payload.get("answers", {})
        total = len(answer_key)
        obtained = 0
        for idx, correct in answer_key.items():
            given = str(student_answers.get(idx, "")).upper()
            if given and given == str(correct).upper():
                obtained += 1
        percent = round((obtained / total) * 100, 2) if total > 0 else 0.0
        return {"total": total, "obtained": obtained, "percentage": percent}
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/exam/publish/{paper_id}")
async def publish_results(
    paper_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("UPDATE exam_papers SET is_published=1 WHERE id=:pid"), {"pid": paper_id})
        await db.commit()
        return {"published": True, "paper_id": paper_id}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
