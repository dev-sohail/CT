from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.responses import StreamingResponse
from core.security import get_current_user
from core.cache import get as cache_get, set as cache_set
from schemas.assistant import ChatRequest, ChatResponse, ToolRequest, ToolResponse, CapabilitiesResponse
from services.assistant import AssistantService
from models.user import User

router = APIRouter()


def get_assistant() -> AssistantService:
    return AssistantService()


async def _chat_stream(payload: ChatRequest, assistant: AssistantService):
    try:
        result = await assistant.chat(payload)
        text = result.response or ""
        for i in range(0, len(text), 5):
            yield f"data: {text[i:i+5]}\n\n"
    except Exception as exc:
        yield f"data: [error] {exc}\n\n"
    yield "data: [DONE]\n\n"


@router.post("/assistant/chat", response_model=ChatResponse)
async def chat(
    payload: ChatRequest,
    current_user: User = Depends(get_current_user),
    assistant: AssistantService = Depends(get_assistant),
):
    try:
        return await assistant.chat(payload)
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=str(e))


@router.post("/assistant/chat/stream")
async def chat_stream(
    payload: ChatRequest,
    current_user: User = Depends(get_current_user),
    assistant: AssistantService = Depends(get_assistant),
):
    return StreamingResponse(
        _chat_stream(payload, assistant),
        media_type="text/event-stream",
        headers={"Cache-Control": "no-cache", "Connection": "keep-alive"},
    )


@router.post("/tools/execute", response_model=ToolResponse)
async def execute_tool(
    payload: ToolRequest,
    current_user: User = Depends(get_current_user),
    assistant: AssistantService = Depends(get_assistant),
):
    try:
        return await assistant.execute_tool(payload)
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=str(e))


@router.get("/capabilities", response_model=CapabilitiesResponse)
async def capabilities(
    current_user: User = Depends(get_current_user),
    assistant: AssistantService = Depends(get_assistant),
):
    cached = cache_get("capabilities")
    if cached is not None:
        return cached
    try:
        data = await assistant.capabilities()
        cache_set("capabilities", data, ttl=120)
        return data
    except Exception as e:
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=str(e))
