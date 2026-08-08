from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
from typing import Optional

from models.base import get_async_db
from models.user import User
from core.security import get_current_active_superuser

router = APIRouter()


@router.get("/business/orders")
async def list_orders(
    status: Optional[str] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM business_orders WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        if search:
            sql += " AND (order_number LIKE :q OR customer_name LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"orders": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/business/orders")
async def create_order(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["order_number", "customer_name", "customer_email", "amount", "currency", "status", "payment_method"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO business_orders ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/business/purchases")
async def list_purchases(
    status: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM business_purchases WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        sql += " ORDER BY purchase_date DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"purchases": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/business/purchases")
async def create_purchase(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["vendor_name", "item_name", "quantity", "unit_price", "total_amount", "status", "purchase_date"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO business_purchases ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/business/crm")
async def list_crm(
    lead_status: Optional[str] = None,
    search: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM business_crm WHERE 1=1"
        params = {}
        if lead_status:
            sql += " AND lead_status=:lead_status"
            params["lead_status"] = lead_status
        if search:
            sql += " AND (name LIKE :q OR email LIKE :q OR company LIKE :q)"
            params["q"] = f"%{search}%"
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"contacts": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/business/crm")
async def create_crm_contact(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["name", "email", "phone", "company", "lead_status", "notes"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO business_crm ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/business/inventory")
async def list_inventory(
    category: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM business_inventory WHERE 1=1"
        params = {}
        if category:
            sql += " AND category=:category"
            params["category"] = category
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"items": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/business/inventory")
async def create_inventory_item(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["item_name", "sku", "category", "quantity", "unit_price", "reorder_level"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO business_inventory ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/business/assets")
async def list_assets(
    status: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM business_assets WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"assets": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/business/assets")
async def create_asset(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["asset_name", "asset_type", "assigned_to", "status", "purchase_date", "value"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO business_assets ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/business/placements")
async def list_placements(
    status: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM business_placements WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        sql += " ORDER BY placement_date DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"placements": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/business/placements")
async def create_placement(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["student_name", "company_name", "job_title", "package", "placement_date", "status"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO business_placements ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/business/transcripts")
async def list_transcripts(
    status: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM business_transcripts WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        sql += " ORDER BY issue_date DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"transcripts": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/business/transcripts")
async def create_transcript(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["student_name", "transcript_number", "issue_date", "status", "file_path"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO business_transcripts ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.get("/business/campaigns")
async def list_campaigns(
    status: Optional[str] = None,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        sql = "SELECT * FROM business_email_campaigns WHERE 1=1"
        params = {}
        if status:
            sql += " AND status=:status"
            params["status"] = status
        sql += " ORDER BY created_at DESC"
        result = await db.execute(text(sql), params)
        rows = result.fetchall()
        return {"campaigns": [dict(r._mapping) for r in rows]}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@router.post("/business/campaigns")
async def create_campaign(
    payload: dict,
    db: AsyncSession = Depends(get_async_db),
    _: User = Depends(get_current_active_superuser),
):
    try:
        fields = ["campaign_name", "subject", "body", "recipients", "status"]
        data = {k: payload.get(k) for k in fields if k in payload}
        cols = list(data.keys())
        placeholders = [f":{c}" for c in cols]
        sql = f"INSERT INTO business_email_campaigns ({', '.join(cols)}) VALUES ({', '.join(placeholders)})"
        await db.execute(text(sql), data)
        await db.commit()
        return {"created": True}
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
