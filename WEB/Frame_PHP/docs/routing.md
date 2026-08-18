# Routing

The routing system is powered by [nikic/fast-route](https://github.com/nikic/FastRoute), embedded in `brain/router.php`. Routes are defined in `brain/routes/web.php` and dispatched via `brain/config.php`.

---

## 1. Route Definition Syntax

Routes are registered using the global helper function `add_app_route()`:

```php
add_app_route($r, $method, $uri, $handler);
```

- `$r` — The `FastRoute\RouteCollector` instance (injected into the route file).
- `$method` — A string or array of HTTP methods.
- `$uri` — The route pattern (e.g. `/users/{id}`).
- `$handler` — A controller string, closure, or file path.

All routes are collected in `$app_routes_list` for debugging via `get_routing_table()`.

---

## 2. HTTP Methods

Pass a single method string or an array of methods:

```php
// Single method
add_app_route($r, 'GET', '/about', 'Public\PageController@about');

// Multiple methods
add_app_route($r, ['GET', 'POST'], '/login', 'Auth\AuthController@login');
```

Supported method strings: `GET`, `POST`, `PUT`, `DELETE`, `PATCH`, `HEAD`, `OPTIONS`.

The `any` matcher (registered via `$r->any()`) accepts all HTTP methods using the `*` wildcard.

---

## 3. Route Parameters

Dynamic segments are enclosed in `{}`:

```php
add_app_route($r, 'GET', '/users/{id}', 'Api\UserController@show');
add_app_route($r, 'GET', '/posts/{slug}', 'Public\PostController@show');
```

### Regex Constraints

Append `:regex` after the parameter name to constrain matches:

```php
// Only numeric IDs
add_app_route($r, 'GET', '/users/{id:\d+}', 'Api\UserController@show');

// Only alphanumeric slugs
add_app_route($r, 'GET', '/posts/{slug:[a-z0-9\-]+}', 'Public\PostController@show');
```

Parameters without a constraint match any characters except `/` by default. Use `.+` to match path segments containing `/`:

```php
add_app_route($r, ['GET'], '/storage/{path:.+}', 'Public\AssetsController@serve');
```

Extracted parameters are passed as an associative array to the handler.

---

## 4. Route Groups

Group routes under a common prefix using `$r->addGroup()`:

```php
$r->addGroup('/admin', function ($r) {
    add_app_route($r, 'GET', '/dashboard', 'Admin\DashboardController@index');
    add_app_route($r, ['GET', 'POST'], '/users', 'Admin\UserController@index');
});
```

This registers:
- `GET /admin/dashboard`
- `GET|POST /admin/users`

Groups can be nested:

```php
$r->addGroup('/api', function ($r) {
    $r->addGroup('/v1', function ($r) {
        add_app_route($r, 'GET', '/users/{id:\d+}', 'Api\V1\UserController@show');
    });
});
```

---

## 5. Closure Handlers

Closures receive the extracted route parameters as an array:

```php
add_app_route($r, 'GET', '/hello', function ($vars) {
    return 'Hello, World!';
});

add_app_route($r, 'GET', '/greet/{name}', function ($vars) {
    return 'Hello, ' . htmlspecialchars($vars['name']);
});
```

The closure must return a string (echoed to the response). Route parameters are not automatically unpacked as arguments — they arrive as the `$vars` array.

---

## 6. File Handlers

Pass an absolute or relative file path. Route parameters are available via `extract()`:

```php
add_app_route($r, 'GET', '/page', '/path/to/file.php');
```

Inside `file.php`, variables from the route are extracted into the local scope:

```php
<?php
// /path/to/file.php
// If route is /page/{id}, $id is available here
echo "Page ID: " . ($id ?? 'N/A');
```

---

## 7. Controller@method Handlers

The most common handler type. Specify `Namespace\ClassName@methodName`:

```php
add_app_route($r, 'GET', '/', 'Public\HomeController@index');
add_app_route($r, ['GET', 'POST'], '/login', 'Auth\AuthController@login');
```

### Auto-prefix

The dispatcher automatically prepends `Controllers\` if the string does not already start with it:

```php
// These are equivalent:
add_app_route($r, 'GET', '/users', 'Api\UserController@index');
add_app_route($r, 'GET', '/users', 'Controllers\Api\UserController@index');
```

Route parameters are passed as method arguments:

```php
// Route: /users/{id:\d+}
public function show(int $id): string
{
    // $id is automatically provided
}
```

---

## 8. Route Caching

FastRoute caches the parsed route data to a PHP file for performance:

```
storage/cache/fast_route.cache.php
```

- **Production**: Cache is enabled. Routes are compiled once and loaded from cache on subsequent requests.
- **Debug mode**: When `$app_debug === true`, the cache is disabled and routes are re-parsed on every request.

To clear the cache, delete `storage/cache/fast_route.cache.php`. It will be regenerated on the next request.

---

## 9. Named Routes

FastRoute supports named routes via the `_name` extra parameter:

```php
$r->get('/users/{id:\d+}', 'Api\UserController@show', [
    FastRoute\RouteCollector::ROUTE_NAME => 'user.show',
]);
```

Named routes enable URI generation via the `GenerateUri` interface:

```php
$uri = $fastRoute->uriGenerator()->forRoute('user.show', ['id' => 42]);
// Returns: GeneratedUri("/users/42")
```

---

## 10. Common Patterns

### Resource CRUD

```php
add_app_route($r, 'GET',    '/posts',          'PostController@index');
add_app_route($r, 'GET',    '/posts/create',   'PostController@create');
add_app_route($r, 'POST',   '/posts',          'PostController@store');
add_app_route($r, 'GET',    '/posts/{id:\d+}', 'PostController@show');
add_app_route($r, 'GET',    '/posts/{id:\d+}/edit', 'PostController@edit');
add_app_route($r, ['PUT', 'PATCH'], '/posts/{id:\d+}', 'PostController@update');
add_app_route($r, 'DELETE', '/posts/{id:\d+}', 'PostController@destroy');
```

### API Endpoint

```php
add_app_route($r, ['GET', 'POST'], '/api/health', 'Api\ApiController@health');
add_app_route($r, 'GET',  '/api/users/{id:\d+}', 'Api\UserController@show');
add_app_route($r, 'POST', '/api/users',           'Api\UserController@store');
```

### Authentication

```php
add_app_route($r, ['GET', 'POST'], '/login',  'Auth\AuthController@login');
add_app_route($r, 'GET',            '/logout', 'Auth\AuthController@logout');
```

### Admin Group

```php
$r->addGroup('/admin', function ($r) {
    add_app_route($r, ['GET', 'POST'], '/login', 'Admin\LoginController@index');
    add_app_route($r, 'GET',            '/dashboard', 'Admin\DashboardController@index');
    add_app_route($r, ['GET', 'POST'], '/users',     'Admin\UserController@index');
});
```

### Catch-all / Static Assets

```php
add_app_route($r, ['GET'], '/storage/{path:.+}', 'Public\AssetsController@serve');
```

### Inline Closure

```php
add_app_route($r, 'GET', '/status', function ($vars) {
    return json_encode(['status' => 'ok']);
});
```

---

## Handler Resolution Priority

When a route matches, the dispatcher resolves the handler in this order:

1. **Closure** — If the handler is a `Closure`, it is called with `$vars` and the return value is echoed.
2. **Controller@method** — If the handler is a string containing `@`, it is resolved to `Controllers\{class}@{method}` (auto-prefixed if needed), instantiated, and the method is called with route parameters.
3. **File path** — If the handler is a string pointing to an existing file, route parameters are extracted into the local scope and the file is included.
4. **Otherwise** — A 500 error is returned.
