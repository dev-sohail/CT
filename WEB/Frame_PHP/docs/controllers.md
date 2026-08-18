# Controllers

## Creating Controllers

Create a new controller by extending `Controllers\BaseController` and placing it in `backend/module/controllers/`.

```php
// backend/module/blog/controllers/PostController.php
namespace Controllers\Module\Blog;

use Controllers\BaseController;

class PostController extends BaseController
{
    public function index()
    {
        // ...
    }
}
```

## Namespace Convention

```
Controllers\Module\{ModuleName}
```

Maps to `backend/module/{modulename}/controllers/`.

## BaseController Methods

| Method | Description |
|---|---|
| `view($path, $data)` | Render `frontend/$path.ct.php` (or `.php`), extracting `$data` |
| `can($permission)` | Check if current user has a permission |
| `requirePermission($key, $msg)` | Enforce a permission or abort |
| `requireAdmin()` | Enforce admin role from session |
| `requirePost()` | Only allow POST requests |
| `requireCsrf()` | Manually validate CSRF token |
| `getCsrfToken()` | Get the current CSRF token |
| `validateCsrfToken($token)` | Validate a token against the session |
| `getRequestCsrfToken()` | Get token from request body |
| `redirectWith($url, $message, $type)` | Redirect with flash message |

The constructor auto-loads the global `$pdo`, instantiates `PermissionService`, enables `QueryLogger`, and enforces CSRF validation on all non-GET requests except `/api/*` routes.

## View Rendering

```php
// Renders frontend/blog/post/index.ct.php
$this->view('blog/post/index', [
    'posts' => $posts
]);
```

Framework looks for `.ct.php` first, then `.php`.

## CSRF Protection

CSRF is enforced automatically on POST, PUT, DELETE, and PATCH. Exempt routes match `/api/*`.

Manual enforcement:

```php
$this->requireCsrf();
```

## Permission System

```php
// Check permission (returns bool)
if ($this->can('edit_post')) {
    // ...
}

// Enforce permission (halts with message)
$this->requirePermission('edit_post', 'You cannot edit posts.');
```

## Admin Guard

```php
// Checks session for admin role
$this->requireAdmin();
```

## Redirect Helpers

```php
$this->redirectWith('/dashboard', 'Saved!', 'success');
$this->redirectWith('/login', 'Access denied.', 'error');
```

## Example Controller

```php
namespace Controllers\Module\Blog;

use Controllers\BaseController;

class PostController extends BaseController
{
    public function index()
    {
        $this->requirePermission('view_posts');

        $posts = $this->pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();

        $this->view('blog/post/index', [
            'posts' => $posts
        ]);
    }

    public function show($id)
    {
        $post = $this->pdo->prepare("SELECT * FROM posts WHERE id = ?");
        $post->execute([$id]);
        $post = $post->fetch();

        if (!$post) {
            $this->redirectWith('/blog', 'Post not found.', 'error');
            return;
        }

        $this->view('blog/post/show', [
            'post' => $post
        ]);
    }

    public function store()
    {
        $this->requireCsrf();
        $this->requirePermission('create_post');

        $this->pdo->prepare("INSERT INTO posts (title, body) VALUES (?, ?)")
            ->execute([$_POST['title'], $_POST['body']]);

        $this->redirectWith('/blog', 'Post created!', 'success');
    }

    public function admin()
    {
        $this->requireAdmin();

        $this->view('blog/post/admin', [
            'posts' => $this->pdo->query("SELECT * FROM posts")->fetchAll()
        ]);
    }
}
```
