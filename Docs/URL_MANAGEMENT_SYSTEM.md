# 🎯 Comprehensive URL Management System

## ✅ What Was Implemented

A complete URL management system for the CyberTirah Framework that handles:
- **Full URLs** (with protocol and domain) like `http://frame.ct.com/`
- **URI Paths** (without domain) like `/`
- **Asset URLs** for images, CSS, JS
- **Dynamic URL generation**
- **Subdirectory installations**
- **HTTPS detection**

---

## 📊 URL Constants Available

### Full URLs (with protocol and domain)

```php
APP_ROOT_URL     = 'http://frame.ct.com/'
APP_URL          = 'http://frame.ct.com/' (alias)
APP_BASE_URL     = 'http://frame.ct.com/' (alias)
APP_STORAGE_URL  = 'http://frame.ct.com/Storage/'
APP_ADMIN_URL    = 'http://frame.ct.com/admin'
APP_API_URL      = 'http://frame.ct.com/api'
APP_ASSETS_URL   = 'http://frame.ct.com/Storage/'
```

### Asset-Specific URLs

```php
APP_CSS_URL      = 'http://frame.ct.com/Storage/css/'
APP_JS_URL       = 'http://frame.ct.com/Storage/js/'
APP_IMAGES_URL   = 'http://frame.ct.com/Storage/images/'
APP_FONTS_URL    = 'http://frame.ct.com/Storage/fonts/'
APP_UPLOADS_URL  = 'http://frame.ct.com/Storage/uploads/'
```

### URI Paths (without domain)

```php
APP_ROOT_URI     = '/'
APP_BASE_URI     = '/'
APP_STORAGE_URI  = '/Storage/'
APP_ADMIN_URI    = '/admin'
APP_API_URI      = '/api'
```

### Protocol & Host Information

```php
APP_PROTOCOL     = 'http://' or 'https://'
APP_HOST         = 'frame.ct.com'
APP_BASE_PATH    = '' (or '/subdirectory' for subdirectory installs)
APP_IS_HTTPS     = true or false
```

### Current Page Information

```php
CURRENT_URL      = 'http://frame.ct.com/blog/post/123'
CURRENT_URI      = '/blog/post/123'
```

---

## 🎯 Usage in Views

### Test in 404.ct (or any .ct file)

```php
<!-- Full URL (what you wanted!) -->
<p>APP_ROOT_URL: <?php echo APP_ROOT_URL; ?></p>
<!-- Output: http://frame.ct.com/ -->

<!-- Other URLs -->
<p>APP_STORAGE_URL: <?php echo APP_STORAGE_URL; ?></p>
<!-- Output: http://frame.ct.com/Storage/ -->

<p>APP_ADMIN_URL: <?php echo APP_ADMIN_URL; ?></p>
<!-- Output: http://frame.ct.com/admin -->

<!-- Current page -->
<p>CURRENT_URL: <?php echo CURRENT_URL; ?></p>
<!-- Output: http://frame.ct.com/404 (current page) -->
```

### In HTML Links

```php
<!-- Home link -->
<a href="<?php echo APP_ROOT_URL; ?>">Home</a>

<!-- Admin link -->
<a href="<?php echo APP_ADMIN_URL; ?>">Admin Panel</a>

<!-- Asset links -->
<img src="<?php echo APP_IMAGES_URL; ?>logo.png" alt="Logo">
<link rel="stylesheet" href="<?php echo APP_CSS_URL; ?>custom.css">
<script src="<?php echo APP_JS_URL; ?>custom.js"></script>

<!-- Blog post -->
<a href="<?php echo APP_ROOT_URL; ?>blog/post/123">Read Post</a>
```

---

## 🚀 Using the Url Helper Class

### In Controllers

```php
class BlogController extends Controller
{
    public function index(): void
    {
        $data = [
            // Full URLs
            'homeUrl' => Url::base(),                    // http://frame.ct.com/
            'adminUrl' => Url::admin(),                  // http://frame.ct.com/admin
            'apiUrl' => Url::api('health'),              // http://frame.ct.com/api/health
            
            // Asset URLs
            'logoUrl' => Url::asset('images/logo.png'),  // http://frame.ct.com/Storage/images/logo.png
            'cssUrl' => Url::asset('css/custom.css'),    // http://frame.ct.com/Storage/css/custom.css
            
            // Build URLs
            'blogUrl' => Url::to('blog', ['page' => 2]), // http://frame.ct.com/blog?page=2
            'currentUrl' => Url::current(),              // Current page URL
        ];
        
        $this->load->view('public/Blog/index', $data);
    }
}
```

### In Views with Helper Functions

```php
<!-- Global helper functions (automatically available) -->

<!-- Generate full URL -->
<a href="<?php echo url('blog/post/123'); ?>">Post</a>
<!-- Output: http://frame.ct.com/blog/post/123 -->

<!-- Generate asset URL -->
<img src="<?php echo asset('images/logo.png'); ?>" alt="Logo">
<!-- Output: http://frame.ct.com/Storage/images/logo.png -->

<!-- Generate admin URL -->
<a href="<?php echo admin_url('dashboard'); ?>">Dashboard</a>
<!-- Output: http://frame.ct.com/admin/dashboard -->

<!-- Generate API URL -->
<script>
    fetch('<?php echo api_url('users'); ?>')
        .then(response => response.json())
        .then(data => console.log(data));
</script>
<!-- Output: http://frame.ct.com/api/users -->

<!-- Get current URL -->
<form action="<?php echo current_url(); ?>" method="POST">
    <!-- Form fields -->
</form>
```

---

## 📋 Available Methods

### Url Class Methods

```php
// Base URLs
Url::base()                              // http://frame.ct.com/
Url::baseUri()                           // /

// Generate URLs
Url::to('path', ['param' => 'value'])    // http://frame.ct.com/path?param=value
Url::uri('path', ['param' => 'value'])   // /path?param=value

// Asset URLs
Url::asset('images/logo.png')            // http://frame.ct.com/Storage/images/logo.png

// Special URLs
Url::admin('dashboard')                  // http://frame.ct.com/admin/dashboard
Url::api('health')                       // http://frame.ct.com/api/health

// Current page
Url::current()                           // http://frame.ct.com/current/page
Url::currentUri()                        // /current/page

// Protocol & Host
Url::isHttps()                           // true/false
Url::protocol()                          // http:// or https://
Url::host()                              // frame.ct.com

// Redirects
Url::redirect('/login')                  // Redirect to URL
Url::redirectToRoute('home')             // Redirect to named route
Url::back()                              // Redirect to previous page

// Utilities
Url::isExternal('http://example.com')    // true/false
Url::getQueryParam('page', 1)            // Get query parameter
Url::withParams('/url', ['a' => 'b'])    // /url?a=b
```

### Global Helper Functions

```php
url('path', ['param' => 'value'])        // Generate full URL
asset('images/logo.png')                 // Generate asset URL
admin_url('dashboard')                   // Generate admin URL
api_url('health')                        // Generate API URL
current_url()                            // Get current URL
redirect_to('/login')                    // Redirect
```

---

## 🎨 Examples

### Example 1: Blog Post Page

```php
<!-- views/Blog/show.ct -->
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($post['title']) ?></title>
    
    <!-- Base URL in meta -->
    <meta property="og:url" content="<?= CURRENT_URL ?>">
    
    <!-- Assets -->
    <link rel="stylesheet" href="<?= APP_CSS_URL ?>custom.css">
    <script src="<?= APP_JS_URL ?>custom.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <a href="<?= APP_ROOT_URL ?>">Home</a>
        <a href="<?= url('blog') ?>">Blog</a>
        <a href="<?= url('about') ?>">About</a>
    </nav>
    
    <!-- Post content -->
    <article>
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        
        <!-- Featured image -->
        <?php if (!empty($post['image'])): ?>
            <img src="<?= APP_IMAGES_URL . htmlspecialchars($post['image']) ?>" alt="Post image">
        <?php endif; ?>
        
        <div class="content">
            <?= $post['content'] ?>
        </div>
        
        <!-- Share buttons -->
        <div class="share">
            <a href="https://facebook.com/sharer.php?u=<?= urlencode(CURRENT_URL) ?>">Share on Facebook</a>
            <a href="https://twitter.com/share?url=<?= urlencode(CURRENT_URL) ?>">Share on Twitter</a>
        </div>
        
        <!-- Back to blog -->
        <a href="<?= url('blog', ['category' => $post['category']]) ?>">Back to <?= $post['category'] ?></a>
    </article>
</body>
</html>
```

### Example 2: Admin Dashboard

```php
<!-- views/admin/Dashboard/index.ct -->
<div class="admin-dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="<?= admin_url('dashboard') ?>">Dashboard</a>
        <a href="<?= admin_url('users') ?>">Users</a>
        <a href="<?= admin_url('settings') ?>">Settings</a>
        <a href="<?= APP_ROOT_URL ?>">View Site</a>
    </aside>
    
    <!-- Main content -->
    <main>
        <h1>Dashboard</h1>
        
        <!-- API data loading -->
        <div id="stats"></div>
        
        <script>
            fetch('<?= api_url('stats') ?>')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('stats').innerHTML = 
                        `<p>Users: ${data.users}</p>
                         <p>Posts: ${data.posts}</p>`;
                });
        </script>
    </main>
</div>
```

### Example 3: API Response with URLs

```php
// Controller
class ApiController extends Controller
{
    public function posts(): void
    {
        header('Content-Type: application/json');
        
        $posts = $this->model->getAllPosts();
        
        // Add full URLs to each post
        foreach ($posts as &$post) {
            $post['url'] = url('blog/post/' . $post['id']);
            $post['image_url'] = asset('images/posts/' . $post['image']);
            $post['api_url'] = api_url('posts/' . $post['id']);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $posts,
            'meta' => [
                'base_url' => APP_ROOT_URL,
                'api_url' => APP_API_URL,
                'current_url' => CURRENT_URL
            ]
        ]);
    }
}
```

---

## ⚙️ Configuration

### In .env file

```env
# Host (auto-detected if not set)
APP_HOST=frame.ct.com

# Base path (for subdirectory installations)
APP_BASE_PATH=

# For subdirectory installation:
# APP_BASE_PATH=/myapp

# Force HTTPS
FORCE_HTTPS=false
```

### Results

#### Root Installation
```
APP_HOST=frame.ct.com
APP_BASE_PATH=

Results:
APP_ROOT_URL = http://frame.ct.com/
APP_ADMIN_URL = http://frame.ct.com/admin
```

#### Subdirectory Installation
```
APP_HOST=example.com
APP_BASE_PATH=/myapp

Results:
APP_ROOT_URL = http://example.com/myapp/
APP_ADMIN_URL = http://example.com/myapp/admin
```

#### HTTPS Enabled
```
FORCE_HTTPS=true

Results:
APP_ROOT_URL = https://frame.ct.com/
APP_PROTOCOL = https://
```

---

## 🧪 Testing

### Test All Constants

Add this to your 404.ct or any view:

```php
<div style="background:#f0f0f0;padding:20px;margin:10px;font-family:monospace;font-size:12px;">
    <h2>URL Management System Test</h2>
    
    <h3>Full URLs (with domain):</h3>
    <ul>
        <li>APP_ROOT_URL: <strong><?= APP_ROOT_URL ?></strong></li>
        <li>APP_STORAGE_URL: <strong><?= APP_STORAGE_URL ?></strong></li>
        <li>APP_ADMIN_URL: <strong><?= APP_ADMIN_URL ?></strong></li>
        <li>APP_API_URL: <strong><?= APP_API_URL ?></strong></li>
        <li>APP_CSS_URL: <strong><?= APP_CSS_URL ?></strong></li>
        <li>APP_JS_URL: <strong><?= APP_JS_URL ?></strong></li>
        <li>APP_IMAGES_URL: <strong><?= APP_IMAGES_URL ?></strong></li>
    </ul>
    
    <h3>URI Paths (without domain):</h3>
    <ul>
        <li>APP_ROOT_URI: <strong><?= APP_ROOT_URI ?></strong></li>
        <li>APP_ADMIN_URI: <strong><?= APP_ADMIN_URI ?></strong></li>
        <li>APP_API_URI: <strong><?= APP_API_URI ?></strong></li>
    </ul>
    
    <h3>Protocol & Host:</h3>
    <ul>
        <li>APP_PROTOCOL: <strong><?= APP_PROTOCOL ?></strong></li>
        <li>APP_HOST: <strong><?= APP_HOST ?></strong></li>
        <li>APP_IS_HTTPS: <strong><?= APP_IS_HTTPS ? 'true' : 'false' ?></strong></li>
    </ul>
    
    <h3>Current Page:</h3>
    <ul>
        <li>CURRENT_URL: <strong><?= CURRENT_URL ?></strong></li>
        <li>CURRENT_URI: <strong><?= CURRENT_URI ?></strong></li>
    </ul>
    
    <h3>Helper Functions:</h3>
    <ul>
        <li>url('blog'): <strong><?= url('blog') ?></strong></li>
        <li>asset('images/logo.png'): <strong><?= asset('images/logo.png') ?></strong></li>
        <li>admin_url('dashboard'): <strong><?= admin_url('dashboard') ?></strong></li>
        <li>api_url('health'): <strong><?= api_url('health') ?></strong></li>
    </ul>
</div>
```

### Expected Output on http://frame.ct.com/404

```
Full URLs (with domain):
  APP_ROOT_URL: http://frame.ct.com/
  APP_STORAGE_URL: http://frame.ct.com/Storage/
  APP_ADMIN_URL: http://frame.ct.com/admin
  APP_API_URL: http://frame.ct.com/api
  APP_CSS_URL: http://frame.ct.com/Storage/css/
  APP_JS_URL: http://frame.ct.com/Storage/js/
  APP_IMAGES_URL: http://frame.ct.com/Storage/images/

URI Paths (without domain):
  APP_ROOT_URI: /
  APP_ADMIN_URI: /admin
  APP_API_URI: /api

Protocol & Host:
  APP_PROTOCOL: http://
  APP_HOST: frame.ct.com
  APP_IS_HTTPS: false

Current Page:
  CURRENT_URL: http://frame.ct.com/404
  CURRENT_URI: /404

Helper Functions:
  url('blog'): http://frame.ct.com/blog
  asset('images/logo.png'): http://frame.ct.com/Storage/images/logo.png
  admin_url('dashboard'): http://frame.ct.com/admin/dashboard
  api_url('health'): http://frame.ct.com/api/health
```

---

## ✅ Summary

| Feature | Status |
|---------|--------|
| **Full URLs** | ✅ `http://frame.ct.com/` |
| **URI Paths** | ✅ `/` |
| **Asset URLs** | ✅ Complete |
| **Protocol Detection** | ✅ HTTP/HTTPS |
| **Host Detection** | ✅ Automatic |
| **Subdirectory Support** | ✅ Yes |
| **Helper Class** | ✅ `Url` class |
| **Global Functions** | ✅ `url()`, `asset()`, etc. |
| **Current Page Info** | ✅ `CURRENT_URL`, `CURRENT_URI` |

---

**🎉 Now `APP_ROOT_URL` outputs the full URL `http://frame.ct.com/` as expected!**

---

**Created**: October 12, 2025  
**Version**: 2.0.0  
**Status**: 🟢 **Complete**

