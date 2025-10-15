# CyberTirah Framework - Quick Reference

## Controller Quick Reference

### Basic Controller Structure

```php
<?php

class MyController extends Controller
{
    public function index()
    {
        // Your code here
    }
}
```

### Accessing Services

```php
// Database
$this->db->query("SELECT * FROM users");

// Session
$this->session->get('user_id');
$this->session->set('user_id', 123);

// Request
$this->request->get('page');
$this->request->post('username');

// Response
$this->response->setOutput($html);

// Load service
$this->load->model('admin/User/User');
$this->load->library('security/Validator');
$this->load->helper('url');
$this->load->view('public/Blog/index', $data);
```

---

## Router Quick Reference

### Define Routes (routes.json)

```json
{
  "routes": [
    {"method": "GET", "path": "/", "handler": "public/Home/HomeController@index", "name": "home"},
    {"method": "GET", "path": "/blog/{id:\\d+}", "handler": "public/Blog/BlogController@show", "name": "blog.show"},
    {"method": "POST", "path": "/blog", "handler": "public/Blog/BlogController@store", "middleware": ["auth"]}
  ]
}
```

### Programmatic Routes

```php
// Basic
Router::get('/path', 'Controller@method');
Router::post('/path', 'Controller@method');
Router::put('/path', 'Controller@method');
Router::delete('/path', 'Controller@method');

// With parameters
Router::get('/blog/{id}', 'BlogController@show');
Router::get('/blog/{id:\\d+}', 'BlogController@show'); // Only digits

// Named route
Router::get('/contact', 'ContactController@index', ['name' => 'contact']);

// With middleware
Router::get('/admin', 'AdminController@index', ['middleware' => 'auth']);

// Route group
Router::group(['prefix' => '/admin', 'middleware' => 'auth'], function() {
    Router::get('/dashboard', 'DashboardController@index');
    Router::get('/users', 'UserController@index');
});
```

### URL Generation

```php
// Generate URL
$url = Router::url('blog.show', ['id' => 123]); // /blog/123

// Redirect
Router::redirect('/login');
Router::redirectToRoute('home');
Router::redirectToRoute('blog.show', ['id' => 123]);

// Check route
if (Router::hasRoute('admin.dashboard')) {
    // Route exists
}

// Get stats
$stats = Router::getStats();
```

---

## Model Quick Reference

### Basic Model

```php
<?php

class UserModel extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    // Your custom methods
    public function getActiveUsers()
    {
        return $this->findAll(['status' => 'active']);
    }
}
```

### Built-in Methods

```php
// Find by ID
$user = $model->findById(1);

// Find all
$users = $model->findAll();
$users = $model->findAll(['status' => 'active'], 10, 0); // conditions, limit, offset

// Insert
$id = $model->insert(['name' => 'John', 'email' => 'john@example.com']);

// Update
$model->update(1, ['name' => 'Jane']);

// Delete
$model->delete(1);

// Count
$count = $model->count(['status' => 'active']);

// Custom query
$result = $model->query("SELECT * FROM users WHERE age > ?", [18]);
```

---

## Loader Quick Reference

### Load Model

```php
// Load
$this->load->model('admin/User/User');

// Access (automatically registered as model_user)
$users = $this->model_user->findAll();

// Load with custom alias
$this->load->model('admin/User/User', 'userModel');
$users = $this->userModel->findAll();
```

### Load Library

```php
// Load library
$this->load->library('security/Validator');

// Use
$this->validator->validate($data, $rules);

// Load with parameters
$this->load->library('Cache/Redis', ['host' => 'localhost', 'port' => 6379]);
```

### Load Helper

```php
// Load one
$this->load->helper('url');

// Load multiple
$this->load->helper(['url', 'string', 'array']);

// Use helper functions
$url = site_url('/blog');
$slug = url_title('My Post Title');
```

### Load View

```php
// Load and display
$this->load->view('public/Blog/index', ['posts' => $posts]);

// Load and return
$html = $this->load->view('public/Blog/index', ['posts' => $posts], true);

// Set variables
$this->load->vars('title', 'My Page');
$this->load->vars(['title' => 'My Page', 'description' => 'Description']);
```

---

## Registry Quick Reference

### Access Registry

```php
$registry = Registry::getInstance();

// Get service
$db = $registry->get('db');
$db = $registry->db; // Magic getter

// Set service
$registry->set('myservice', $serviceInstance);
$registry->myservice = $serviceInstance; // Magic setter

// Check if exists
if ($registry->has('db')) {
    // Service exists
}

// Register with factory
$registry->register('cache', function() {
    return new CacheService();
});

// Register as singleton
$registry->singleton('db', function() {
    return new Database();
});
```

---

## Middleware Quick Reference

### Global Middleware

```php
Router::middleware(function() {
    if (!isAuthenticated()) {
        Router::redirect('/login');
        return false; // Stop execution
    }
    return true; // Continue
});
```

### Middleware Class

```php
class AuthMiddleware
{
    public function handle()
    {
        if (!isset($_SESSION['user'])) {
            Router::redirect('/login');
            return false;
        }
        return true;
    }
}

// Register
Router::middleware(AuthMiddleware::class);
```

### Route-Specific Middleware

```php
Router::get('/admin', 'AdminController@index', [
    'middleware' => ['auth', 'admin']
]);
```

---

## Common Patterns

### Controller with Model and View

```php
public function index()
{
    $this->load->model('public/Blog/Blog');
    $posts = $this->model_blog->getAllPosts();
    $this->load->view('public/Blog/index', ['posts' => $posts]);
}
```

### API Controller with JSON Response

```php
public function api()
{
    $this->load->model('public/User/User');
    $users = $this->model_user->findAll();
    
    $this->jsonResponse([
        'success' => true,
        'data' => $users
    ]);
}
```

### Form Handling with Validation

```php
public function store()
{
    $this->load->library('security/Validator');
    
    $rules = [
        'title' => 'required|min:3',
        'content' => 'required'
    ];
    
    if (!$this->validator->validate($_POST, $rules)) {
        $this->jsonResponse([
            'error' => true,
            'errors' => $this->validator->getErrors()
        ], 400);
        return;
    }
    
    $this->load->model('public/Blog/Blog');
    $id = $this->model_blog->create($_POST);
    
    Router::redirectToRoute('blog.show', ['id' => $id]);
}
```

### Redirect Patterns

```php
// Simple redirect
$this->redirect('/login');

// Named route redirect
Router::redirectToRoute('home');
Router::redirectToRoute('blog.show', ['id' => 123]);

// Redirect with status code
Router::redirect('/login', 301); // Permanent redirect
```

---

## Route File Structure

```
Body/
├── admin/
│   └── Blog/
│       ├── Controllers/
│       │   └── BlogController.php
│       ├── Models/
│       │   └── Blog.php
│       ├── Views/
│       │   ├── index.ct
│       │   └── form.ct
│       └── routes.json
├── public/
│   └── Blog/
│       ├── Controllers/
│       ├── Models/
│       ├── Views/
│       └── routes.json
└── api/
    └── ...
```

---

## Environment Check

```php
// Check environment
$isDev = (defined('APP_DEBUG') && APP_DEBUG) || 
         (defined('APP_ENV') && APP_ENV === 'development');

if ($isDev) {
    // Development-specific code
}
```

---

## Debugging

```php
// Route stats
print_r(Router::getStats());

// Registry contents
print_r($registry->all());

// Loaded models
print_r($this->load->getLoadedModels());

// Loaded libraries
print_r($this->load->getLoadedLibraries());
```

---

## Cache Management

```php
// Enable cache
Router::enableCache();

// Disable cache
Router::disableCache();

// Clear cache
Router::clearCache();

// Save cache
Router::saveCache();
```

---

## Tips

1. Use named routes for easier URL management
2. Group related routes with common prefixes
3. Use middleware for authentication/authorization
4. Load components only when needed
5. Cache routes in production
6. Use route parameters with regex for validation
7. Follow MVC pattern strictly
8. Use the loader for all component loading

---

**Quick Help:**
- Full Guide: [ROUTING_AND_REGISTRY_GUIDE.md](ROUTING_AND_REGISTRY_GUIDE.md)
- Summary: [IMPROVEMENTS_SUMMARY.md](IMPROVEMENTS_SUMMARY.md)

