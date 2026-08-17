# ctlab-support (PHP)

DDD building blocks consumed by every CTLabs domain module.

## What's included

| Class | Namespace | Purpose |
|-------|-----------|---------|
| `Repository` / `RepositoryContract` | `Ctlab\Support` | Eloquent-backed base repository with generic CRUD + pagination |
| `Service` | `Ctlab\Support` | Base class for application services |
| `Action` / `ActionContract` | `Ctlab\Support` | Invokable action base (`__invoke` → `handle()`) |
| `DataTransferObject` | `Ctlab\Support` | `fromArray()` / `toArray()` / `toJson()` DTO base |
| `ApiResponse` | `Ctlab\Support\Foundation` | Consistent `{ data, meta, errors }` envelope |
| `ApiResource` / `ApiCollection` | `Ctlab\Support\Http\Resources` | Base API Resources |
| `Policy` | `Ctlab\Support\Policies` | Base policy with `allow`/`deny` helpers |
| `HasTags` / `HasAudit` / `Taggable` | `Ctlab\Support\Traits` | Reusable polymorphic traits |

## Install (path repository in `apps/api/composer.json`)

```json
"require": { "ctlab/ctlab-support": "*" },
"repositories": [
  { "type": "path", "url": "../../packages/php/ctlab-support" }
]
```

Every domain module extends these instead of reinventing CRUD scaffolding.
