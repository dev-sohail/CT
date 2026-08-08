from pydantic import BaseModel, Field
from typing import Optional, Any, Dict, List


class ChatRequest(BaseModel):
    message: str = Field(..., min_length=1, max_length=4000)
    session_id: Optional[str] = None
    context: Optional[str] = None


class ChatResponse(BaseModel):
    response: str
    intent: str
    confidence: float
    tool_results: List[Any]
    session_id: str
    success: bool
    timestamp: str


class ToolRequest(BaseModel):
    tool: str
    kwargs: Dict[str, Any] = Field(default_factory=dict)


class ToolResponse(BaseModel):
    tool: str
    result: Dict[str, Any]
    success: bool


class CapabilitiesResponse(BaseModel):
    intents: List[Dict[str, Any]]
    tools: List[Dict[str, Any]]
    status: Dict[str, Any]


class ChatStatusPost(BaseModel):
    status_text: Optional[str] = Field(None, max_length=500)
    status_type: str = Field("text", max_length=20)
    media_url: Optional[str] = Field(None, max_length=500)
    is_public: int = Field(1, ge=0, le=1)
    expires_at: Optional[str] = None

    model_config = {"extra": "forbid"}
