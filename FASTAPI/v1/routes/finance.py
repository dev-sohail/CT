from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/finance/transactions")
async def list_transactions(
    type: Optional[str] = None,
    category_id: Optional[int] = None,
    from_date: Optional[str] = None,
    to_date: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT t.*, c.name as category_name FROM pays_transactions t LEFT JOIN pays_categories c ON c.id=t.category_id WHERE 1=1"
        params = {}
        if type:
            sql += " AND c.type=:type"
            params["type"] = type
        if category_id is not None:
            sql += " AND t.category_id=:cid"
            params["cid"] = category_id
        if from_date:
            sql += " AND t.occurred_at>=:from_date"
            params["from_date"] = from_date
        if to_date:
            sql += " AND t.occurred_at<=:to_date"
            params["to_date"] = to_date
        sql += " ORDER BY t.occurred_at DESC, t.id DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"transactions": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/finance/transactions")
async def create_transaction(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO pays_transactions (category_id, amount, currency, method, reference, note, occurred_at, created_by) VALUES (:cid, :amt, :cur, :mth, :ref, :note, :dt, :by)"), {
            "cid": payload.get("category_id"),
            "amt": payload.get("amount", 0),
            "cur": payload.get("currency", "USD"),
            "mth": payload.get("method", "cash"),
            "ref": payload.get("reference", ""),
            "note": payload.get("note", ""),
            "dt": payload.get("occurred_at", ""),
            "by": payload.get("created_by", "admin"),
        })
        await db.commit()
        return {"created": True, "id": stmt.lastrowid}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/finance/invoices")
async def list_invoices(
    status: Optional[str] = None,
    category_id: Optional[int] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT i.*, c.name as category_name FROM pays_invoices i LEFT JOIN pays_categories c ON c.id=i.category_id WHERE 1=1"
        params = {}
        if status:
            sql += " AND i.status=:status"
            params["status"] = status
        if category_id is not None:
            sql += " AND i.category_id=:cid"
            params["cid"] = category_id
        sql += " ORDER BY i.created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"invoices": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/finance/invoices")
async def create_invoice(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO pays_invoices (invoice_number, category_id, amount, currency, status, due_date, note, created_by) VALUES (:inv, :cid, :amt, :cur, :st, :due, :note, :by) ON DUPLICATE KEY UPDATE category_id=VALUES(category_id), amount=VALUES(amount), status=VALUES(status), due_date=VALUES(due_date), note=VALUES(note)"), {
            "inv": payload.get("invoice_number", ""),
            "cid": payload.get("category_id"),
            "amt": payload.get("amount", 0),
            "cur": payload.get("currency", "USD"),
            "st": payload.get("status", "draft"),
            "due": payload.get("due_date"),
            "note": payload.get("note", ""),
            "by": payload.get("created_by", "admin"),
        })
        await db.commit()
        return {"created": True, "id": stmt.lastrowid}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/finance/categories")
async def list_categories(
    type: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM pays_categories WHERE 1=1"
        params = {}
        if type:
            sql += " AND type=:type"
            params["type"] = type
        sql += " ORDER BY name ASC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"categories": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/finance/categories")
async def create_category(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        stmt = await db.execute(text("INSERT INTO pays_categories (name, type, description, status) VALUES (:name, :type, :desc, :st) ON DUPLICATE KEY UPDATE type=VALUES(type), description=VALUES(description), status=VALUES(status)"), {
            "name": payload.get("name", ""),
            "type": payload.get("type", "income"),
            "desc": payload.get("description", ""),
            "st": payload.get("status", 1),
        })
        await db.commit()
        return {"created": True, "id": stmt.lastrowid}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/finance/reports")
async def get_reports(
    from_date: Optional[str] = None,
    to_date: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT c.type, c.name, COALESCE(SUM(t.amount),0) as total FROM pays_categories c LEFT JOIN pays_transactions t ON t.category_id=c.id"
        params = {}
        if from_date and to_date:
            sql += " WHERE t.occurred_at BETWEEN :from_date AND :to_date"
            params = {"from_date": from_date, "to_date": to_date}
        sql += " GROUP BY c.id, c.type, c.name ORDER BY c.type ASC, total DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"reports": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
