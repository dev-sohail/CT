# CI (GitHub Actions)

Test → Build → Deploy.

The live workflow lives at `../../.github/workflows/ci.yml` and runs on every push/PR:

1. **php** — `apps/api`: `composer install`, `pint --test` (lint), `phpunit` (sqlite in-memory)
2. **web** — `apps/web`: `npm ci`, `npm run lint`, `npm run build`
3. **deploy** — only on `main` push; plug in your homelab/VPS ship step

Local equivalent:

```bash
cd apps/api && composer install && vendor/bin/pint --test && vendor/bin/phpunit
cd apps/web && npm ci && npm run lint && npm run build
```
