# 02. SharedRESTApiGateway

**Purpose:** Versioned API conventions, response envelope, rate limiting

## Implemented
- `ApiController` base class — every domain controller extends this; wraps responses in the
  `{ data, meta, errors }` envelope via `CTLabs\Support\Foundation\ApiResponse`
- `Exceptions\Handler` — global handler producing a consistent JSON:API-ish envelope for
  validation, auth, model-not-found, and HTTP exceptions (no stack traces in production)
- Middleware: `ForceJsonResponse`, `ResolveApiVersion` (enforces `/api/{version}`, 406 on unknown)
- `Support\ApiGateway` — version resolution + envelope contract documentation
- `Http\Resources\ApiResource` — base API Resource marker extending `CTLabs\Support` ones

Conventions: `/api/v1/...`, `page` + `per_page` pagination, `filter[field]=value` filtering,
Bearer auth via Sanctum. See `/api/v1/info`.
