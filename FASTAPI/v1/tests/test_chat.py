import pytest


@pytest.mark.asyncio
async def test_chat_status_requires_auth(client):
    resp = await client.get("/v1/chat/status")
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_chat_status_viewers_requires_auth(client):
    resp = await client.get("/v1/chat/status/viewers?status_id=1")
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_chat_acknowledged_requires_auth(client):
    resp = await client.get("/v1/chat/acknowledged")
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_chat_search_requires_auth(client):
    resp = await client.get("/v1/chat/messages/search?q=hello")
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_chat_delete_requires_auth(client):
    resp = await client.delete("/v1/chat/messages/1")
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_chat_status_post_requires_auth(client):
    resp = await client.post("/v1/chat/status", json={"status_text": "hello"})
    assert resp.status_code == 401
