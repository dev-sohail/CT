import pytest


@pytest.mark.asyncio
async def test_health(client):
    resp = await client.get("/v1/health")
    assert resp.status_code == 200
    assert resp.json()["status"] == "ok"


@pytest.mark.asyncio
async def test_health_ready(client):
    resp = await client.get("/v1/health/ready")
    assert resp.status_code == 200
    assert resp.json()["status"] == "ok"
