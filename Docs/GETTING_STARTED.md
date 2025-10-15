# 🚀 Getting Started with CyberTirah Framework v2.0.0

Welcome to your enhanced CyberTirah Framework! This guide will help you start building amazing applications.

---

## ✅ Framework Status

**Status**: 🟢 **PRODUCTION READY**

- **Core Files**: 5 (Router, Registry, Loader, Controller, Model)
- **Modules**: 5 (public, admin, api, ai, automate)
- **Documentation**: 11 comprehensive guides
- **Routes**: 8 route files configured

---

## 📚 Quick Links

### Essential Documentation
- **[STATUS.md](STATUS.md)** - Current system status and overview
- **[FINAL_CHECKLIST.md](FINAL_CHECKLIST.md)** - All completed tasks
- **[Docs/ROUTING_AND_REGISTRY_GUIDE.md](Docs/ROUTING_AND_REGISTRY_GUIDE.md)** - Complete usage guide
- **[Docs/QUICK_REFERENCE.md](Docs/QUICK_REFERENCE.md)** - Quick reference cheat sheet

---

## 🎯 Your First Steps

### 1. Create Your First Module

```bash
# Create module structure
mkdir Body/public/MyModule
mkdir Body/public/MyModule/Controllers
mkdir Body/public/MyModule/Models
mkdir Body/public/MyModule/Views
```

### 2. Create a Controller

**File**: `Body/public/MyModule/Controllers/mymodule.php`

```php
<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

class MyModuleController extends Controller
{
    public function index(): void
    {
        // Load model (CodeIgniter style)
        $this->load->model('public/MyModule/MyModule');
        
        // Get data from model
        $data = [
            'title' => 'My Module',
            'items' => $this->model_mymodule->getItems()
        ];
        
        // Load view
        $this->load->view('public/MyModule/index', $data);
    }
    
    public function show(): void
    {
        // Get route parameter
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(404);
            echo 'Item not found';
            return;
        }
        
        // Load model and get item
        $this->load->model('public/MyModule/MyModule');
        $item = $this->model_mymodule->getById($id);
        
        // Load view
        $this->load->view('public/MyModule/show', ['item' => $item]);
    }
}
```

### 3. Create a Model

**File**: `Body/public/MyModule/Models/MyModuleModel.php`

```php
<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

class MyModuleModel extends Model
{
    protected string $table = 'items';
    
    public function getItems(): array
    {
        // Using database directly
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->query($query)->fetchAll();
    }
    
    public function getById(int $id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($query, [$id])->fetch();
    }
    
    public function create(array $data): bool
    {
        $query = "INSERT INTO {$this->table} (name, description) VALUES (?, ?)";
        return $this->db->execute($query, [$data['name'], $data['description']]);
    }
}
```

### 4. Create a View

**File**: `Body/public/MyModule/Views/index.ct`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'My Module') ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($title) ?></h1>
    
    <div class="items">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <div class="item">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p><?= htmlspecialchars($item['description']) ?></p>
                    <a href="<?= Router::url('mymodule.show', ['id' => $item['id']]) ?>">
                        View Details
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No items found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
```

### 5. Define Routes

**File**: `Body/public/MyModule/routes.json`

```json
{
  "routes": [
    {
      "method": "GET",
      "path": "/mymodule",
      "handler": "public/MyModule/MyModule@index",
      "name": "mymodule.index"
    },
    {
      "method": "GET",
      "path": "/mymodule/{id:\\d+}",
      "handler": "public/MyModule/MyModule@show",
      "name": "mymodule.show"
    }
  ]
}
```

---

## 🎨 Using New Features

### Named Routes & URL Generation

```php
// In your views or controllers
$url = Router::url('mymodule.show', ['id' => 123]);
// Output: /mymodule/123

// Redirect to a named route
Router::redirectToRoute('mymodule.index');
```

### Route Groups

```php
// In a route configuration
Router::group(['prefix' => '/admin', 'middleware' => 'auth'], function() {
    Router::get('/dashboard', 'admin/Dashboard@index');
    Router::get('/users', 'admin/User@index');
});
```

### Loading Components (CodeIgniter Style)

```php
class MyController extends Controller
{
    public function index()
    {
        // Load model
        $this->load->model('public/MyModule/MyModule');
        
        // Load library
        $this->load->library('security/Validator');
        
        // Load helper
        $this->load->helper('url');
        
        // Access database
        $users = $this->db->query("SELECT * FROM users")->fetchAll();
        
        // Access session
        $userId = $this->session->get('user_id');
        
        // Access cache
        $data = $this->cache->get('my_key');
    }
}
```

### Magic Accessors (OpenCart Style)

```php
// Instead of:
$db = $registry->get('database');

// You can now use:
$db = $this->db;
$session = $this->session;
$cache = $this->cache;
$load = $this->load;
```

---

## 🔧 Common Tasks

### Task 1: Create an API Endpoint

**File**: `Body/api/MyAPI/Controllers/myapi.php`

```php
<?php

class MyAPIController extends Controller
{
    public function getData(): void
    {
        header('Content-Type: application/json');
        
        $this->load->model('api/MyAPI/MyAPI');
        $data = $this->model_myapi->fetchData();
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    }
}
```

**Routes**: `Body/api/MyAPI/routes.json`

```json
{
  "routes": [
    {
      "method": "GET",
      "path": "/api/data",
      "handler": "api/MyAPI/MyAPI@getData",
      "name": "api.data"
    }
  ]
}
```

### Task 2: Add Authentication Middleware

**File**: `Brain/Classes/middleware/AuthMiddleware.php`

```php
<?php

class AuthMiddleware
{
    public function handle(): bool
    {
        $registry = Registry::getInstance();
        $session = $registry->get('session');
        
        if (!$session->has('user_id')) {
            Router::redirectToRoute('login');
            return false;
        }
        
        return true;
    }
}
```

**Use in routes**:

```json
{
  "method": "GET",
  "path": "/admin/dashboard",
  "handler": "admin/Dashboard@index",
  "middleware": ["AuthMiddleware"]
}
```

### Task 3: Work with Database

```php
class UserModel extends Model
{
    protected string $table = 'users';
    
    public function findByEmail(string $email): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        return $this->db->query($query, [$email])->fetch();
    }
    
    public function create(array $data): int
    {
        $query = "INSERT INTO {$this->table} (name, email, password) 
                  VALUES (?, ?, ?)";
        $this->db->execute($query, [
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
        return $this->db->lastInsertId();
    }
}
```

---

## 📊 Performance Tips

### 1. Use Route Caching (Automatic in Production)
Routes are automatically cached when `APP_ENV` is not `development`.

### 2. Enable Lazy Loading (Already Active)
Services are loaded only when needed through the Registry.

### 3. Optimize Database Queries
```php
// Use prepared statements (automatically used by the framework)
$users = $this->db->query("SELECT * FROM users WHERE status = ?", ['active']);
```

### 4. Cache Frequently Used Data
```php
// Cache for 1 hour
$this->cache->set('popular_posts', $posts, 3600);

// Retrieve from cache
$posts = $this->cache->get('popular_posts');
```

---

## 🐛 Debugging

### Enable Development Mode

In `.env`:
```env
APP_ENV=development
APP_DEBUG=true
DEV_MODE=true
```

### View Errors
When in development mode, detailed errors will be displayed automatically.

### Check Logs
```bash
# View error logs
cat Storage/logs/error.log

# View application logs
cat Storage/logs/app.log
```

---

## 📖 Learn More

### Complete Guides
1. **[ROUTING_AND_REGISTRY_GUIDE.md](Docs/ROUTING_AND_REGISTRY_GUIDE.md)** - Full routing and registry documentation
2. **[QUICK_REFERENCE.md](Docs/QUICK_REFERENCE.md)** - Quick reference for common tasks
3. **[IMPROVEMENTS_SUMMARY.md](Docs/IMPROVEMENTS_SUMMARY.md)** - All framework improvements
4. **[FINAL_FIX_SUMMARY.md](Docs/FINAL_FIX_SUMMARY.md)** - Recent fixes and changes

### Framework Structure
```
Frame/
├── Brain/          # Core framework
│   ├── Core/       # Core classes (Router, Registry, Loader, etc.)
│   └── Classes/    # Utility classes
├── Body/           # Your application modules
│   ├── public/     # Public-facing modules
│   ├── admin/      # Admin modules
│   ├── api/        # API modules
│   ├── ai/         # AI modules
│   └── automate/   # Automation modules
├── Index/          # Entry point
├── Storage/        # Cache, logs, uploads
└── Docs/           # Documentation
```

---

## 🎉 You're Ready!

Your CyberTirah Framework is now:

✅ **Production Ready** - All systems operational  
✅ **Well Documented** - 11+ comprehensive guides  
✅ **Feature Rich** - OpenCart/CodeIgniter patterns  
✅ **High Performance** - Route caching & lazy loading  
✅ **Developer Friendly** - Familiar patterns & easy to use  

### Start Building!

```bash
# Start your development server
php -S localhost:8000 -t Index/

# Or use your WAMP server
# Visit: http://localhost/me/CT/Frame/Index/
```

---

## 💡 Need Help?

- Check **[STATUS.md](STATUS.md)** for system status
- Read **[Docs/QUICK_REFERENCE.md](Docs/QUICK_REFERENCE.md)** for quick answers
- Review **[Docs/ROUTING_AND_REGISTRY_GUIDE.md](Docs/ROUTING_AND_REGISTRY_GUIDE.md)** for detailed examples

---

**Happy Coding!** 🚀

*CyberTirah Framework v2.0.0*  
*Status: ✅ Complete & Ready*

