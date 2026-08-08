from fastapi import APIRouter, WebSocket, WebSocketDisconnect
from typing import List
import json

router = APIRouter()


class ConnectionManager:
    def __init__(self):
        self.active_connections: List[WebSocket] = []

    async def connect(self, websocket: WebSocket):
        await websocket.accept()
        self.active_connections.append(websocket)

    def disconnect(self, websocket: WebSocket):
        self.active_connections.remove(websocket)

    async def send_personal(self, message: str, websocket: WebSocket):
        await websocket.send_text(message)

    async def broadcast(self, message: str):
        for connection in self.active_connections:
            await connection.send_text(message)


manager = ConnectionManager()


@router.websocket("/ws/assistant")
async def websocket_assistant(websocket: WebSocket):
    await manager.connect(websocket)
    try:
        while True:
            data = await websocket.receive_text()
            payload = json.loads(data) if data else {}
            message = payload.get("message", "")
            session_id = payload.get("session_id", "ws-session")

            if not message:
                await manager.send_personal(json.dumps({"error": "Empty message"}), websocket)
                continue

            try:
                import httpx
                from config import settings
                async with httpx.AsyncClient(timeout=60) as client:
                    resp = await client.post(
                        f"{settings.tirahai_core_url.rstrip('/')}/v1/assistant/chat",
                        json={"message": message, "session_id": session_id},
                        headers={"Content-Type": "application/json"},
                    )
                    resp.raise_for_status()
                    result = resp.json()
            except Exception as exc:
                result = {"error": str(exc), "success": False}

            await manager.send_personal(json.dumps(result), websocket)
    except WebSocketDisconnect:
        manager.disconnect(websocket)
