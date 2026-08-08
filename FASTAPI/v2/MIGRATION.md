# Migration Guide: v1 to v2

## Overview

v2 is a backwards-compatible, parallel release of the TirahAi API. It runs under the `/v2` prefix while v1 remains available under `/v1`. This guide explains how to migrate clients, what changed, and how to validate the move.

## Key Improvements in v2

- **API version header**: every response carries `X-API-Version: 2`
- **Enhanced schemas**: v2 responses include `api_version` and additional metadata fields
- **Streaming support**: `/v2/assistant/chat` advertises and supports streaming via the `stream` request field
- **Stricter middleware stack**: CORS, rate limiting (120 req/min), and structured request logging are applied uniformly
- **Cleaner token handling**: `/auth/me` now resolves the user directly from the JWT payload without a DB round-trip for the v2 stub

## Endpoint Mapping

| v1 Endpoint | v2 Endpoint | Notes |
|-------------|-------------|-------|
| `GET /v1/health` | `GET /v2/health` | Response schema unchanged, version embedded in `service` field |
| `GET /v1/status` | `GET /GET /v2/status` | Added `features` map with capability flags |
| `GET /v1/auth/me` | `GET /v2/auth/me` | Enhanced response model (`AuthMeResponse`) adds `api_version` and relaxed DB dependency |
| `POST /v1/assistant/chat` | `POST /v2/assistant/chat` | Accepts `stream` boolean; returns `streaming_supported` and `api_version` fields |

## Schema Changes

### AuthMeResponse
- Added: `api_version: str = "2"`
- Backwards-compatible for clients that ignore unknown fields.

### ChatResponseV2
- Renamed and expanded from `ChatResponse`.
- Added: `api_version`, `streaming_supported`, `duration_ms` on tool results.
- `stream: bool = False` in request triggers `X-Streaming-Supported` behavior.

## Migration Steps

1. **Update the base URL**
   - Change the client API base from `/v1` to `/v2`.

2. **Update auth headers**
   - v2 uses the same bearer token format. Existing v1 tokens remain valid unless explicitly rotated.

3. **Adjust client parsers**
   - If your client strictly validates response schemas, update models to accept the new fields (`api_version`, `streaming_supported`, `tool_results[].duration_ms`).

4. **Enable streaming (optional)**
   - Send `{"stream": true}` in the chat payload. The server sets `request.state.streaming = True` and returns streaming headers in future iterations.

5. **Test with parallel deployment**
   - Run both v1 and v2 during the transition. Compare responses using the `X-API-Version` header to assert correctness.

6. **Rollback**
   - Revert the base URL to `/v1`. No data migration is required because v2 uses a separate DB URL config (`tirahai_v2.db`) by default.

## Breaking Changes

None in this release. v2 is additive and runs on a separate router prefix.

## Environment Variables

v2 reads the same environment variable names as v1 because `Settings` uses `env_prefix = ""`. Override as needed:

```bash
export API_PREFIX=/v2
export DATABASE_URL=sqlite:///./tirahai_v2.db
export SECRET_KEY=your-v2-secret
```

## Rate Limits & CORS

- Rate limits: 120 requests per 60-second window per client IP.
- CORS origins are loaded from `settings.cors_origins` (comma-separated).

## Verification Checklist

- [ ] `GET /v2/health` returns `200 OK` with `X-API-Version: 2`
- [ ] `GET /v2/status` includes `features.streaming: true`
- [ ] `GET /v2/auth/me` returns `AuthMeResponse` with `api_version: "2"`
- [ ] `POST /v2/assistant/chat` with `stream: true` returns `streaming_supported: true`
- [ ] All legacy v1 endpoints remain functional
- [ ] CORS preflight succeeds against configured origins
- [ ] Rate limiting kicks in after 120 requests in a minute
