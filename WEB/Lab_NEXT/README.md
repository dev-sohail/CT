# CTLabs — Personal Software Ecosystem

Modular monolith monorepo. See `apps/api` (Laravel), `apps/web` (Next.js),
`packages`, `infra`, and `docs/adr`. Full plan in `../final_plan_final.md`.

## Boot order (foundation)

| # | Piece | Status |
|---|-------|--------|
| 0.1 | `packages/php/ctlab-support` — DDD base classes | done |
| 0.2 | `apps/api/app/Domains/01_CoreIdentityAndAccessKernel` | done |
| 0.3 | `apps/api/app/Domains/02_SharedRESTApiGateway` | done |
| 0.4 | `packages/js/ctlab-ui` + `ctlab-theme` | done |
| 0.5 | `infra/docker` + `.github/workflows/ci.yml` | done |

## Run the API

```bash
docker compose -f infra/docker/docker-compose.yml up -d --build
cp apps/api/.env.example apps/api/.env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
curl http://localhost:8000/api/v1/info
```

## Layout

- `apps/api` — one Laravel app, one schema, domain modules under `app/Domains/*`
- `apps/web` — one Next.js app, route-grouped by domain
- `packages/php/ctlab-support` — base `Repository`/`Service`/`Action`/`DTO`/Resource/Policies
- `packages/js/{ctlab-ui,ctlab-theme}` — shared React + Tailwind design system
- `infra` — `docker`, `nginx`, `ci`
- `docs/adr` — architecture decision records
