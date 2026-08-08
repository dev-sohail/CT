from fastapi import APIRouter

router = APIRouter()


@router.get("/health")
def health():
    return {"status": "ok", "service": "fastapi-v1", "version": "0.1.0"}


@router.get("/health/ready")
def readiness():
    return {"status": "ready"}
