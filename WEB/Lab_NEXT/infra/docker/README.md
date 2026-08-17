# Docker

Single `docker-compose` orchestrates the whole platform:

| Service | Image | Purpose |
|---------|-------|---------|
| `app` | built from `Dockerfile` (php:8.2-fpm) | Laravel modular monolith |
| `nginx` | nginx:1.25-alpine | Reverse proxy → app on :9000, published :8000 |
| `db` | mysql:8.0 | Single shared schema `ctlab` |
| `redis` | redis:7-alpine | Cache / queues / rate-limit store |

## Run

```bash
docker compose -f infra/docker/docker-compose.yml up -d --build
cp apps/api/.env.example apps/api/.env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

API is then reachable at http://localhost:8000/api/v1/info.
