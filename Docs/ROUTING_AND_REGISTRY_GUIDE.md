# CyberTirah Framework - Routing & Registry Guide

## Overview

The CyberTirah Framework now features a powerful OpenCart/CodeIgniter-style registry system and enhanced routing capabilities. This guide will help you understand and use these features effectively.

## Table of Contents

1. [Registry System](#registry-system)
2. [Routing System](#routing-system)
3. [Controller Development](#controller-development)
4. [Model Development](#model-development)
5. [Loading Components](#loading-components)
6. [Best Practices](#best-practices)

---

## Registry System

### What is the Registry?

The Registry is a centralized container for storing and accessing framework services and components. It follows the OpenCart/CodeIgniter pattern for easy access.

### Accessing the Registry

```php
// Get registry instance
$registry = Registry::getInstance();

// Access services using magic getter (OpenCart style)
$db = $registry->db;
$load = $registry->load;
$session = $registry->session;

// Or using get() method
$db = $registry->get('db');
```

### Registering Services

```php
// Register a service
$registry->set('myservice', $serviceInstance);

// Register with lazy loading (factory pattern)
$registry->register('myservice', function() {
    return new MyService();
});

// Register as singleton
$registry->singleton('myservice', function() {
    return new MyService();
});
```

---

## Routing System

### Enhanced Features

- **Named Routes**: Assign names to routes for easy URL generation
- **Route Groups**: Group routes with common prefixes and middleware
- **Route Parameters**: Support for dynamic parameters with regex
- **Middleware Support**: Apply middleware globally or per-route
- **Route Caching**: Automatic caching for better performance

### Basic Routing

#### Defining Routes in routes.json

```json
{
  "routes": [
    {
      "method": "GET",
      "path": "/",
      "handler": "public/Home/HomeController@index",
      "name": "home"
    },
    {
      "method": "GET",
      "path": "/blog/{id:\\d+}",
      "handler": "public/Blog/BlogController@show",
      "name": "blog.show"
    },
    {
      "method": "POST",
      "path": "/blog",
      "handler": "public/Blog/BlogController@store",
      "name": "blog.store",
      "middleware": ["auth", "csrf"]
    }
  ]
}
```

#### Programmatic Route Definition

```php
// Basic routes
Router::get('/users', 'admin/User/UserController@index', ['name' => 'users.index']);
Router::post('/users', 'admin/User/UserController@store');
Router::put('/users/{id}', 'admin/User/UserController@update');
Router::delete('/users/{id}', 'admin/User/UserController@delete');

// Route with parameters and constraints
Router::get('/blog/{slug}', 'public/Blog/BlogController@show');
Router::get('/blog/{id:\\d+}', 'public/Blog/BlogController@showById');

// Route groups
Router::group(['prefix' => '/admin', 'middleware' => 'auth'], function() {
    Router::get('/dashboard', 'admin/Home/DashboardController@index');
    Router::get('/users', 'admin/User/UserController@index');
});

// Named route
Router::get('/contact', 'public/Contact/ContactController@index', [
    'name' => 'contact'
]);
```

### Route Parameters

```php
// Simple parameter
Router::get('/user/{id}', 'UserController@show');

// Parameter with regex constraint
Router::get('/post/{id:\\d+}', 'PostController@show');
Router::get('/category/{slug:[a-z-]+}', 'CategoryController@show');

// Multiple parameters
Router::get('/blog/{year}/{month}/{slug}', 'BlogController@show');
```

### Named Routes & URL Generation

```php
// Generate URL for named route
$url = Router::url('blog.show', ['id' => 123]);
// Result: /blog/123

// Redirect to named route
Router::redirectToRoute('home');
Router::redirectToRoute('blog.show', ['id' => 456]);

// Check if route exists
if (Router::hasRoute('admin.dashboard')) {
    // Route exists
}
```

### Middleware

```php
// Global middleware (applied to all routes)
Router::middleware(function() {
    // Check authentication
    if (!isAuthenticated()) {
        Router::redirect('/login');
        return false; // Stop execution
    }
    return true; // Continue
});

// Route-specific middleware
Router::get('/admin/dashboard', 'admin/DashboardController@index', [
    'middleware' => ['auth', 'admin']
]);

// Middleware class
class AuthMiddleware {
    public function handle() {
        if (!isset($_SESSION['user'])) {
            Router::redirect('/login');
            return false;
        }
        return true;
    }
}

Router::middleware(AuthMiddleware::class);
```

### Route Caching

```php
// Enable caching (automatic in production)
Router::enableCache();

// Disable caching (automatic in development)
Router::disableCache();

// Clear route cache
Router::clearCache();

// Save routes to cache manually
Router::saveCache();
```

---

## Controller Development

### OpenCart/CodeIgniter Style Controllers

```php
<?php

declare(strict_types=1);

class BlogController extends Controller
{
    public function index()
    {
        // Load model using CodeIgniter style
        $this->load->model('public/Blog/Blog');
        
        // Access model (automatically registered in registry)
        $posts = $this->model_blog->getAllPosts();
        
        // Set view data
        $this->set('posts', $posts);
        $this->set('title', 'Blog Posts');
        
        // Load view
        $this->load->view('public/Blog/index', $this->data);
    }
    
    public function show()
    {
        $id = $_GET['id'] ?? 0;
        
        // Load model
        $this->load->model('public/Blog/Blog');
        
        // Get post
        $post = $this->model_blog->findById($id);
        
        if (!$post) {
            // Handle 404
            Router::setNotFound(function() {
                echo "Post not found";
            });
            return;
        }
        
        // Load view
        $this->load->view('public/Blog/show', ['post' => $post]);
    }
    
    public function store()
    {
        // Load library
        $this->load->library('security/Validator');
        
        // Validate input
        $rules = [
            'title' => 'required|min:3',
            'content' => 'required'
        ];
        
        if (!$this->validator->validate($_POST, $rules)) {
            $this->jsonResponse([
                'error' => true,
                'errors' => $this->validator->getErrors()
            ], 400);
        }
        
        // Load model and save
        $this->load->model('public/Blog/Blog');
        $id = $this->model_blog->create($_POST);
        
        // Redirect
        Router::redirectToRoute('blog.show', ['id' => $id]);
    }
}
```

### Accessing Registry Services

```php
class UserController extends Controller
{
    public function index()
    {
        // Access database directly
        $users = $this->db->query("SELECT * FROM users")->rows;
        
        // Access session
        $userId = $this->session->get('user_id');
        
        // Access request
        $this->request->get('page');
        
        // Access cache
        $cached = $this->cache->get('users_list');
        
        // Use loader
        $this->load->model('admin/User/User');
        $this->load->library('Http/Response');
        $this->load->helper('url');
    }
}
```

---

## Model Development

### Basic Model Structure

```php
<?php

declare(strict_types=1);

class BlogModel extends Model
{
    protected string $table = 'blog_posts';
    protected string $primaryKey = 'id';
    
    public function getAllPosts(int $limit = 10)
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ?";
        return $this->query($sql, [$limit]);
    }
    
    public function getPublishedPosts()
    {
        return $this->findAll(['status' => 'published']);
    }
    
    public function create(array $data)
    {
        return $this->insert($data);
    }
    
    public function updatePost(int $id, array $data)
    {
        return $this->update($id, $data);
    }
}
```

### Using Models

```php
// In controller
$this->load->model('public/Blog/Blog');

// Access methods
$posts = $this->model_blog->getAllPosts(20);
$post = $this->model_blog->findById(1);
$id = $this->model_blog->create([
    'title' => 'New Post',
    'content' => 'Content here'
]);
```

---

## Loading Components

### Loading Models

```php
// Load model
$this->load->model('admin/User/User');

// Access loaded model
$users = $this->model_user->findAll();

// Load with custom alias
$this->load->model('admin/User/User', 'userModel');
$users = $this->userModel->findAll();
```

### Loading Libraries

```php
// Load library
$this->load->library('security/Validator');

// Use library
$this->validator->validate($data, $rules);

// Load with parameters
$this->load->library('Cache/Redis', [
    'host' => 'localhost',
    'port' => 6379
]);
```

### Loading Helpers

```php
// Load single helper
$this->load->helper('url');

// Load multiple helpers
$this->load->helper(['url', 'string', 'array']);

// Use helper functions
$url = site_url('/blog/post/123');
$slug = url_title('My Blog Post');
```

### Loading Views

```php
// Load view
$this->load->view('public/Blog/index', ['posts' => $posts]);

// Load view and return output
$output = $this->load->view('public/Blog/index', ['posts' => $posts], true);

// Set variables for views
$this->load->vars('title', 'My Page');
$this->load->vars([
    'title' => 'My Page',
    'description' => 'Page description'
]);
```

---

## Best Practices

### 1. Use Named Routes

```php
// Define
Router::get('/blog/{id}', 'BlogController@show', ['name' => 'blog.show']);

// Use
$url = Router::url('blog.show', ['id' => 123]);
Router::redirectToRoute('blog.show', ['id' => 123]);
```

### 2. Organize Routes in Groups

```php
Router::group(['prefix' => '/api', 'middleware' => 'auth'], function() {
    Router::get('/users', 'api/UserController@index');
    Router::post('/users', 'api/UserController@store');
});
```

### 3. Use Middleware for Common Tasks

```php
// Authentication middleware
Router::middleware(function() {
    if (!isset($_SESSION['user'])) {
        Router::redirect('/login');
        return false;
    }
    return true;
});
```

### 4. Load Components Only When Needed

```php
public function index()
{
    // Good: Load only what you need
    $this->load->model('admin/User/User');
    
    // Bad: Loading everything upfront
    // $this->load->model('admin/*');
}
```

### 5. Use Route Caching in Production

```php
// In production environment
Router::enableCache();

// Routes are cached automatically
// Clear cache when routes change
Router::clearCache();
```

### 6. Follow MVC Pattern

```php
// Controller handles requests
class BlogController extends Controller {
    public function index() {
        $this->load->model('public/Blog/Blog');
        $data = $this->model_blog->getAllPosts();
        $this->load->view('public/Blog/index', $data);
    }
}

// Model handles data
class BlogModel extends Model {
    public function getAllPosts() {
        return $this->findAll();
    }
}

// View handles presentation
// In public/Blog/Views/index.ct
```

---

## Migration from Old System

### Old Way

```php
$model = new BlogModel($registry);
$posts = $model->getAllPosts();

require_once 'views/blog/index.php';
```

### New Way (OpenCart/CodeIgniter Style)

```php
$this->load->model('public/Blog/Blog');
$posts = $this->model_blog->getAllPosts();

$this->load->view('public/Blog/index', ['posts' => $posts]);
```

---

## Route Statistics

```php
// Get route statistics
$stats = Router::getStats();

// Output:
// [
//     'total_routes' => 45,
//     'named_routes' => 32,
//     'methods' => ['GET' => 30, 'POST' => 10, 'PUT' => 3, 'DELETE' => 2],
//     'cache_enabled' => true,
//     'cache_file' => '/path/to/cache/routes.php'
// ]
```

---

## Error Handling

### 404 Not Found

```php
// Custom 404 handler
Router::setNotFound(function() {
    http_response_code(404);
    echo "Page not found!";
});
```

### Error Display

The router automatically shows detailed errors in development mode and generic errors in production mode.

---

## Summary

The CyberTirah Framework now provides:

1. ✅ **OpenCart/CodeIgniter-style registry system**
2. ✅ **Enhanced routing with caching, groups, and middleware**
3. ✅ **Easy component loading** ($this->load->model, library, helper, view)
4. ✅ **Magic accessors** ($this->db, $this->session, etc.)
5. ✅ **Named routes with URL generation**
6. ✅ **Route parameters with regex constraints**
7. ✅ **Middleware support**
8. ✅ **Production-ready caching**

Your existing code will continue to work, and you can gradually adopt the new patterns as needed!

