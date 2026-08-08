import pytest
from unittest.mock import AsyncMock, patch


@pytest.mark.asyncio
async def test_assistant_chat_unauthorized(client):
    resp = await client.post("/v1/assistant/chat", json={"message": "hello"})
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_tools_execute_unauthorized(client):
    resp = await client.post("/v1/tools/execute", json={"tool": "weather", "kwargs": {}})
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_capabilities_unauthorized(client):
    resp = await client.get("/v1/capabilities")
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_assistant_chat_with_token(client, auth_headers):
    mock_response = AsyncMock()
    mock_response.status_code = 200
    mock_response.json.return_value = {
        "response": "mocked response",
        "intent": "information_request",
        "confidence": 0.95,
        "tool_results": [],
        "session_id": "test-session",
        "success": True,
        "timestamp": "2024-01-01T00:00:00",
    }
    mock_response.raise_for_status = AsyncMock()

    with patch("httpx.AsyncClient.post", return_value=mock_response):
        resp = await client.post(
            "/v1/assistant/chat",
            json={"message": "hello", "session_id": "test"},
            headers=auth_headers,
        )
    assert resp.status_code == 200
    data = resp.json()
    assert data["response"] == "mocked response"
    assert data["session_id"] == "test-session"


@pytest.mark.asyncio
async def test_capabilities_with_token(client, auth_headers):
    mock_response = AsyncMock()
    mock_response.status_code = 200
    mock_response.json.return_value = {
        "intents": [],
        "tools": [],
        "status": {},
    }
    mock_response.raise_for_status = AsyncMock()

    with patch("httpx.AsyncClient.get", return_value=mock_response):
        resp = await client.get("/v1/capabilities", headers=auth_headers)
    assert resp.status_code == 200
    data = resp.json()
    assert "intents" in data
    assert "tools" in data
