## CyberTirah Framework - Frame

### Run the PHP app
- Windows (WAMP): Place this repo under your web root and point your virtual host to `Index/`. Ensure `installed.lock` exists. Access `http://localhost/`.
- PHP built-in server:
```bash
php -S 127.0.0.1:8000 -t Index
```

### Environment
- Create `.env` at repo root. Example keys:
```ini
APP_ENV=development
DEV_MODE=true
TIMEZONE=UTC
USE_SESSION=true
CORS_ALLOWED_ORIGINS=*
```

### Routing
- Module routes are declared in each module's `routes.json` and loaded at boot.
- Handlers support `role/Module/controller@method`. Example:
```json
{ "method": "GET", "path": "/admin/blog/table", "handler": "admin/Blog/table@index" }
```

### Project structure
- Core framework: `Brain/`
- Application modules: `Body/{admin|public|api|ai}/{Module}/`
- Entry point: `Index/index.php`

### Frontend/Services
- See `Services/` for optional microservices. Start them with your usual Node/Python flows if needed.

### Development notes
- PHP 8.0+ required.
- Errors show friendly output in development; set `APP_ENV=production` to hide details.


