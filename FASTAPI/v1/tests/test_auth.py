import pytest


@pytest.mark.asyncio
async def test_login_missing_fields(client):
    resp = await client.post("/v1/auth/login", json={})
    assert resp.status_code == 422


@pytest.mark.asyncio
async def test_login_invalid_credentials(client):
    resp = await client.post("/v1/auth/login", json={"email": "bad@test.local", "password": "wrong"})
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_me_requires_auth(client):
    resp = await client.get("/v1/auth/me")
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_refresh_requires_token(client):
    resp = await client.post("/v1/auth/refresh", json={"refresh_token": ""})
    assert resp.status_code == 401


@pytest.mark.asyncio
async def test_users_list_requires_admin(client, auth_headers):
    resp = await client.get("/v1/auth/users", headers=auth_headers)
    assert resp.status_code == 200
