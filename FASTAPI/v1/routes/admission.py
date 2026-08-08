from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/admission/applications")
async def list_applications(
    status: Optional[str] = None,
    program_id: Optional[int] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT a.*, p.name as program_name FROM admission_applications a LEFT JOIN institute_programs p ON p.id=a.program_id WHERE 1=1"
        params = {}
        if status:
            sql += " AND a.status=:status"
            params["status"] = status
        if program_id is not None:
            sql += " AND a.program_id=:pid"
            params["pid"] = program_id
        if search:
            sql += " AND (a.first_name LIKE :q OR a.last_name LIKE :q OR a.email LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY a.applied_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"applications": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/admission/applications/{application_id}")
async def get_application(
    application_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT a.*, p.name as program_name FROM admission_applications a LEFT JOIN institute_programs p ON p.id=a.program_id WHERE a.id=:id"), {"id": application_id})
        row = result.fetchone()
        if not row:
            raise HTTPException(status_code=404, detail="Application not found")
        return {"application": dict(row._mapping)}
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/admission/applications/{application_id}/documents")
async def get_documents(
    application_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT * FROM admission_documents WHERE application_id=:aid ORDER BY uploaded_at DESC"), {"aid": application_id})
        rows = result.fetchall()
        return {"documents": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/admission/applications/{application_id}/documents")
async def add_document(
    application_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO admission_documents (application_id, doc_type, file_path, file_size) VALUES (:aid, :type, :path, :size)"), {
            "aid": application_id,
            "type": payload.get("doc_type", ""),
            "path": payload.get("file_path", ""),
            "size": payload.get("file_size", 0),
        })
        await db.commit()
        return {"inserted": True, "id": stmt.lastrowid}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/admission/tests")
async def list_tests(
    program_id: Optional[int] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM admission_tests WHERE 1=1"
        params = {}
        if program_id is not None:
            sql += " AND program_id=:pid"
            params["pid"] = program_id
        sql += " ORDER BY test_date DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"tests": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/admission/tests")
async def create_test(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["title", "program_id", "test_date", "start_time", "end_time", "duration_minutes", "total_marks", "pass_marks", "is_published", "instructions"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO admission_tests ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/admission/tests/{test_id}/results")
async def get_test_results(
    test_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT r.*, a.first_name, a.last_name, a.email FROM admission_test_results r JOIN admission_applications a ON a.id=r.application_id WHERE r.test_id=:tid ORDER BY r.percentage DESC"), {"tid": test_id})
        rows = result.fetchall()
        return {"results": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/admission/tests/{test_id}/results")
async def save_test_result(
    test_id: int,
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO admission_test_results (test_id, application_id, obtained_marks, percentage, grade, remarks) VALUES (:tid, :aid, :om, :pct, :grade, :rmk) ON DUPLICATE KEY UPDATE obtained_marks=VALUES(obtained_marks), percentage=VALUES(percentage), grade=VALUES(grade), remarks=VALUES(remarks)"), {
            "tid": test_id,
            "aid": payload.get("application_id"),
            "om": payload.get("obtained_marks", 0),
            "pct": payload.get("percentage", 0),
            "grade": payload.get("grade", ""),
            "rmk": payload.get("remarks", ""),
        })
        await db.commit()
        return {"saved": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/admission/merit/{program_id}")
async def build_merit_list(
    program_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("DELETE FROM admission_merit_lists WHERE program_id=:pid"), {"pid": program_id})
        result = await db.execute(text("SELECT a.id, (a.gpa * 100) + IFNULL(AVG(r.percentage),0) AS score FROM admission_applications a LEFT JOIN admission_test_results r ON r.application_id=a.id WHERE a.program_id=:pid GROUP BY a.id ORDER BY score DESC"), {"pid": program_id})
        rows = result.fetchall()
        rank = 1
        insert = await db.prepare(text("INSERT INTO admission_merit_lists (program_id, application_id, rank_position, calculated_score) VALUES (:pid, :aid, :rank, :score)"))
        for row in rows:
            await db.execute(insert, {"pid": program_id, "aid": row._mapping["id"], "rank": rank, "score": row._mapping["score"]})
            rank += 1
        await db.commit()
        return {"built": True, "count": len(rows)}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/admission/merit/{program_id}")
async def get_merit_list(
    program_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        result = await db.execute(text("SELECT m.*, a.first_name, a.last_name, a.email, a.gpa FROM admission_merit_lists m JOIN admission_applications a ON a.id=m.application_id WHERE m.program_id=:pid ORDER BY m.rank_position ASC"), {"pid": program_id})
        rows = result.fetchall()
        return {"merit_list": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/admission/applications/{application_id}/offer")
async def issue_offer(
    application_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        import secrets
        code = 'OFFER-' + secrets.token_hex(3).upper()
        await db.execute(text("INSERT INTO admission_offer_letters (application_id, offer_code) VALUES (:aid, :code)"), {"aid": application_id, "code": code})
        await db.execute(text("UPDATE admission_applications SET status='offered' WHERE id=:aid"), {"aid": application_id})
        await db.commit()
        return {"offer_code": code}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/admission/applications/{application_id}/accept-offer")
async def accept_offer(
    application_id: int,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        await db.execute(text("UPDATE admission_offer_letters SET status='accepted', accepted_at=NOW() WHERE application_id=:aid"), {"aid": application_id})
        await db.execute(text("UPDATE admission_applications SET status='enrolled' WHERE id=:aid"), {"aid": application_id})
        await db.commit()
        return {"accepted": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
