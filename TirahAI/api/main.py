"""
TirahAi API - FastAPI application entry point.
Exposes agent intelligence as a secure REST API.
"""

from fastapi import FastAPI, Depends, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import Optional, Dict, Any
import os

from config.settings import DEBUG_MODE, AI_CONFIG
from core.agent import AgenticModel
from utils.logger import get_logger

logger = get_logger(__name__)

app = FastAPI(
    title="TirahAi API",
    description="TirahAi assistant intelligence as a service",
    version="0.1.0",
    docs_url="/docs" if DEBUG_MODE else None,
    redoc_url="/redoc" if DEBUG_MODE else None,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=os.getenv("CORS_ORIGINS", "*").split(","),
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

security = HTTPBearer(auto_error=False)

_agent = AgenticModel()


class ChatRequest(BaseModel):
    message: str = Field(..., min_length=1, max_length=4000)
    session_id: Optional[str] = None
    context: Optional[str] = None


class ChatResponse(BaseModel):
    response: str
    intent: str
    confidence: float
    tool_results: list
    session_id: str
    success: bool
    timestamp: str


class ToolRequest(BaseModel):
    tool: str
    kwargs: Dict[str, Any] = Field(default_factory=dict)


def get_current_user(credentials: Optional[HTTPAuthorizationCredentials] = Depends(security)):
    if not credentials:
        if DEBUG_MODE:
            return {"sub": "debug", "role": "admin"}
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Not authenticated")
    token = credentials.credentials
    if DEBUG_MODE and token == "debug":
        return {"sub": "debug", "role": "admin"}
    raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Invalid token")


@app.get("/health")
def health():
    return {"status": "ok", "service": "tirahai-api", "version": "0.1.0"}


@app.get("/")
def root():
    return {"message": "TirahAi API", "status": "ok", "version": "0.1.0", "docs": "/docs"}


@app.get("/health/ready")
def readiness():
    try:
        _agent.get_status()
        return {"status": "ready"}
    except Exception as e:
        raise HTTPException(status_code=503, detail=f"Not ready: {e}")


@app.post("/v1/assistant/chat", response_model=ChatResponse)
def chat(request: ChatRequest, user=Depends(get_current_user)):
    result = _agent.process(
        command=request.message,
        context=request.context,
        session_id=request.session_id,
    )
    return ChatResponse(**result)


@app.post("/v1/tools/execute")
def execute_tool(request: ToolRequest, user=Depends(get_current_user)):
    from core.tools import ToolRegistry
    tools = ToolRegistry()
    result = tools.execute(request.tool, **request.kwargs)
    return {"tool": request.tool, "result": result}


@app.get("/v1/capabilities")
def capabilities(user=Depends(get_current_user)):
    return {
        "intents": _agent.get_available_intents(),
        "tools": _agent.get_available_tools(),
        "status": _agent.get_status(),
    }
