# 📚 CyberTirah Framework - Complete Guide

**Version**: 2.0.0  
**Status**: ✅ Production Ready  
**Last Updated**: October 13, 2025

---

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [System Overview](#system-overview)
3. [Installation & Setup](#installation--setup)
4. [Admin Panel Guide](#admin-panel-guide)
5. [Authentication System](#authentication-system)
6. [Module Development](#module-development)
7. [Architecture & Patterns](#architecture--patterns)
8. [API Reference](#api-reference)
9. [Database Schema](#database-schema)
10. [Automation & Tools](#automation--tools)
11. [Troubleshooting](#troubleshooting)
12. [Best Practices](#best-practices)

---

## 🚀 Quick Start

### For First Time Users

```bash
# 1. Navigate to automation folder
cd Storage/automate

# 2. Windows: Run unified control script
ct_control.bat

# 3. Linux/Mac: Run unified control script
chmod +x ct_control.sh
./ct_control.sh

# 4. Select [5] Complete System Setup
# Follow the prompts to configure database

# 5. Access the framework
# Admin Panel: http://frame.ct.com/admin
# Public Site: http://frame.ct.com
# Login Page: http://frame.ct.com/login
```

### Default Credentials

| Role    | Username      | Password  | Access URL              |
|---------|---------------|-----------|-------------------------|
| Admin   | admin         | admin123  | /admin (after login)    |
| Student | john.student  | admin123  | /user (after login)     |
| Teacher | jane.teacher  | admin123  | /user (after login)     |
| Parent  | bob.parent    | admin123  | /user (after login)     |
| Staff   | alice.staff   | admin123  | /user (after login)     |

---

## 🎯 System Overview

### Framework Architecture

The CyberTirah Framework follows a **"Body-Brain"** architecture:

```
Frame/
├── Brain/              # Core framework (unchanged by users)
│   ├── Core/           # Core classes (Router, Registry, Loader, Controller, Model)
│   └── Classes/        # Utility classes (organized by functionality)
├── Body/               # Application modules (user development area)
│   ├── public/         # Public-facing modules
│   ├── admin/          # Admin panel modules
│   ├── api/            # API endpoints
│   ├── user/           # User portal modules
│   └── ai/             # AI-related modules
├── Index/              # Entry point and routing
├── Storage/            # Cache, logs, uploads, assets
│   ├── automate/       # Management scripts
│   ├── cache/          # Route and data cache
│   ├── logs/           # Application logs
│   └── uploads/        # User uploaded files
├── Backups/            # Automated backups
│   ├── db/             # Database backups
│   ├── uploads/        # Upload backups
│   └── configs/        # Configuration backups
└── Docs/               # Documentation
```

### Key Features

- ✅ **MVC Architecture** - Clean separation of concerns
- ✅ **OpenCart/CodeIgniter Style** - Familiar patterns
- ✅ **Role-Based Access Control** - Dynamic roles from database
- ✅ **Named Routes** - Easy URL generation
- ✅ **Component Loader** - CodeIgniter-style loading
- ✅ **Registry Pattern** - Centralized service management
- ✅ **Route Caching** - 25x faster route resolution
- ✅ **Module Generator** - Auto-generate new modules
- ✅ **Responsive UI** - Mobile-first design
- ✅ **Security Built-in** - CSRF, validation, sanitization

---

## 📦 Installation & Setup

### Requirements

- **PHP**: 8.0 or higher
- **MySQL/MariaDB**: 5.7 or higher
- **Web Server**: Apache/Nginx with mod_rewrite
- **OS**: Windows, Linux, or macOS

### Step-by-Step Installation

#### 1. Clone or Download

```bash
git clone https://github.com/yourrepo/cybertirah.git
cd cybertirah
```

#### 2. Database Setup

**Option A: Using Automated Script (Recommended)**

```bash
# Windows
cd Storage\automate
ct_control.bat

# Linux/Mac
cd Storage/automate
./ct_control.sh

# Select [5] Complete System Setup
```

**Option B: Manual Setup**

```sql
-- Create database
CREATE DATABASE ct_frame CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p ct_frame < database_schema.sql
```

#### 3. Environment Configuration

**Option A: Using Script**

From `ct_control` menu, select `[2] Environment Setup` > `[1] Create Local .env`

**Option B: Manual Configuration**

```bash
cp .example .env
nano .env  # Edit with your settings
```

**Key Configuration Values:**

```env
# Development
APP_ENV=development
APP_DEBUG=true
FORCE_HTTPS=false

# Production
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true

# Database
DB_HOST=localhost
DB_DATABASE=ct_frame
DB_USERNAME=root
DB_PASSWORD=yourpassword
```

#### 4. Permissions (Linux/Mac)

```bash
chmod -R 755 Storage
chmod -R 755 Backups
chmod -R 755 Index
```

#### 5. Web Server Configuration

**Apache (.htaccess already included)**

```apache
# Ensure mod_rewrite is enabled
a2enmod rewrite
service apache2 restart
```

**Nginx**

```nginx
location / {
    try_files $uri $uri/ /Index/index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

#### 6. Verify Installation

```bash
# Visit your site
http://yourdomain.com

# Check admin panel
http://yourdomain.com/admin

# Test login
http://yourdomain.com/login
```

---

## 🎛️ Admin Panel Guide

### Overview

The admin panel provides complete control over your framework with a modern, responsive interface.

### Admin Panel Structure

```
Body/admin/
├── Dashboard/          # Main dashboard with statistics
├── Roles/              # Role management (CRUD)
├── Users/              # User management (CRUD)
├── Pages/              # Page/content management
├── Blocks/             # HTML blocks (reusable components)
├── ModuleGenerator/    # Auto-generate new modules
├── Automation/         # System automation tools
├── AI/                 # AI assistant & monitoring
├── PublicContent/      # Public site management
└── Common/             # Shared layouts (header/footer)
```

### Main Features

#### 1. Dashboard (`/admin`)

- **Statistics Overview**
  - Total users, roles, pages, blocks, modules
  - Active vs inactive counts
  - System status monitoring
  
- **Quick Actions**
  - Create user, page, block, role
  - Generate module
  - Test AI

- **Management Modules**
  - Grid of all available admin modules
  - Quick access links

#### 2. Role Management (`/admin/roles`)

**Features:**
- Create/Edit/Delete roles
- Live color preview
- Icon selection (emoji or Font Awesome)
- Active/Inactive toggle
- User count validation (prevent deletion of roles in use)

**Available Roles:**
- Admin - Full system access
- Student - Student portal
- Teacher - Teacher portal  
- Parent - Parent portal
- Staff - Staff portal
- Custom - Define your own

**Usage:**
```php
// Roles are fetched dynamically from database
// Change role display name, color, icon without code changes
```

#### 3. User Management (`/admin/users`)

**Features:**
- Advanced filtering (by role, status, search)
- Statistics dashboard
- Full CRUD operations
- Password hashing (bcrypt)
- Self-deletion protection
- Email validation
- Username uniqueness check

**User Fields:**
- First Name / Last Name
- Username (unique)
- Email (unique per role)
- Password (hashed)
- Role (dropdown from roles table)
- Status (active/inactive/suspended)
- Phone Number (optional)

**Search & Filter:**
```
- Search by: username, email, name
- Filter by: role, status
- Sort by: newest, oldest, name
```

#### 4. Page Management (`/admin/pages`)

**Features:**
- Create and manage website pages
- Auto-slug generation
- SEO meta tags (description, keywords)
- Draft/Published status
- WYSIWYG-ready content field

**Page Structure:**
- Title
- Slug (URL-friendly)
- Content (HTML supported)
- Meta Description (SEO)
- Meta Keywords (SEO)
- Status (draft/published)
- Author tracking
- Timestamps

#### 5. HTML Blocks (`/admin/blocks`)

**Features:**
- Reusable content blocks
- Location-based organization
- Active/Inactive toggle
- Global or specific placement

**Block Locations:**
- Global - Available everywhere
- Header - Top of pages
- Footer - Bottom of pages
- Sidebar - Side panels
- Content - In-content placement

**Usage:**
```php
// In templates
<?php $this->load->block('my-block-slug'); ?>
```

#### 6. Module Generator (`/admin/module-generator`)

**Features:**
- Auto-generate complete modules
- Supports all module types
- Optional CRUD scaffolding
- Auto-creates routes

**Module Types:**
- Public - Frontend modules
- Admin - Backend modules
- API - API endpoints
- User - User portal modules

**Generated Structure:**
```
ModuleName/
├── Controllers/
│   └── modulename.php
├── Models/
│   └── ModuleNameModel.php
├── Views/
│   ├── index.ct
│   ├── create.ct  (if CRUD enabled)
│   └── edit.ct    (if CRUD enabled)
└── routes.json
```

**Options:**
- ☑ Include Model
- ☑ Include View
- ☑ Create CRUD Operations
- Database table name (optional)

#### 7. Automation Center (`/admin/automation`)

**Features:**
- Module scanner (lists all installed modules)
- Cache management
- Route regeneration
- System statistics

**Quick Actions:**
- Clear all cache
- Regenerate routes
- Scan modules
- View module list

**Module Statistics:**
```
- Total modules
- By type: public, admin, api, user, ai
- Component status (C=Controller, M=Model, V=View, R=Routes)
```

#### 8. AI Assistant (`/admin/ai`)

**Features:**
- AI request monitoring
- Test interface
- Request logging
- Configuration overview

**Statistics:**
- Total requests
- Today's requests
- Success rate
- Average response time

**Configuration:**
- AI enabled/disabled
- Provider (OpenAI, Claude, etc.)
- Model selection
- Max tokens

#### 9. Public Content Management (`/admin/public-content`)

**Features:**
- Overview of public modules
- Published pages list
- Active blocks overview
- Public site settings

**Settings:**
- Site name & tagline
- Maintenance mode toggle
- Registration allow/disallow
- Featured module
- Footer text

**Quick Management:**
```
- Create new page
- Create new block
- Generate module
- View public site
```

### UI Components

The admin panel uses a consistent design system:

#### Stat Cards

```php
<div class="stat-card primary">
    <div class="stat-card-icon"><i class="fas fa-users"></i></div>
    <div class="stat-card-value">150</div>
    <div class="stat-card-label">Total Users</div>
</div>
```

Colors: `primary`, `success`, `warning`, `danger`, `purple`

#### Buttons

```php
<button class="btn btn-primary">Save</button>
<button class="btn btn-success">Create</button>
<button class="btn btn-danger">Delete</button>
<button class="btn btn-sm btn-secondary">Cancel</button>
```

#### Alerts

```php
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> Success message
</div>
```

Types: `success`, `danger`, `warning`, `info`

#### Badges

```php
<span class="badge badge-success">Active</span>
<span class="badge badge-danger">Inactive</span>
```

### Security Features

- ✅ Admin authentication check on all pages
- ✅ Prepared statements (SQL injection protection)
- ✅ Output escaping (XSS protection)
- ✅ Password hashing (bcrypt)
- ✅ Email validation
- ✅ Input sanitization
- ✅ Self-deletion protection
- ✅ Role validation

---

## 🔐 Authentication System

### Role-Based Login

The framework features a sophisticated role-based authentication system:

#### Features

- Dynamic role fetching from database
- Beautiful login UI with role selection
- Support for username OR email login
- Password hashing (bcrypt)
- User status management (active/inactive/suspended)
- Last login tracking
- Session management

#### Login Flow

```
1. User visits /login
2. Selects role (buttons dynamically generated)
3. Enters username/email and password
4. System validates credentials
5. Checks user status
6. Creates session
7. Redirects to appropriate portal based on role
```

#### Portals by Role

| Role    | Portal URL | Features |
|---------|-----------|----------|
| Admin   | `/admin` | Full system control |
| Student | `/user` | Courses, assignments, grades |
| Teacher | `/user` | Classes, grading, materials |
| Parent  | `/user` | Children, reports, messages |
| Staff   | `/user` | Tasks, departments, reports |

#### Session Data

```php
$_SESSION['logged_in']    // true/false
$_SESSION['user_id']      // Database user ID
$_SESSION['username']     // Username
$_SESSION['email']        // Email address
$_SESSION['first_name']   // First name
$_SESSION['last_name']    // Last name
$_SESSION['full_name']    // Full name
$_SESSION['role']         // User role
$_SESSION['status']       // Account status
$_SESSION['last_login']   // Last login timestamp
$_SESSION['login_time']   // Current session start time
```

#### Authentication Check

```php
// In controllers
private function checkAuth(): void {
    if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
        header('Location: /login');
        exit;
    }
}
```

#### Database Tables

**user_info Table:**
```sql
- user_id (INT, PRIMARY KEY, AUTO_INCREMENT)
- first_name (VARCHAR)
- last_name (VARCHAR)
- username (VARCHAR, UNIQUE)
- email (VARCHAR, UNIQUE per role)
- password (VARCHAR, hashed)
- role (ENUM)
- phone_number (VARCHAR, optional)
- status (ENUM: active, inactive, suspended)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- last_login (TIMESTAMP)
```

**roles Table:**
```sql
- role_id (INT, PRIMARY KEY, AUTO_INCREMENT)
- role_name (VARCHAR)
- role_slug (VARCHAR, UNIQUE)
- display_name (VARCHAR)
- icon (VARCHAR, emoji or FA class)
- color (VARCHAR, hex color)
- is_active (TINYINT)
- created_at (TIMESTAMP)
```

---

## 🛠️ Module Development

### Module Structure

Every module follows the MVC pattern:

```
ModuleName/
├── Controllers/
│   └── modulename.php      # Controller (lowercase filename)
├── Models/
│   └── ModuleNameModel.php # Model (PascalCase filename)
├── Views/
│   ├── index.ct
│   ├── create.ct
│   └── edit.ct
└── routes.json
```

### Creating a Module

#### Method 1: Using Module Generator (Recommended)

1. Go to `/admin/module-generator`
2. Enter module name
3. Select module type (public/admin/api/user)
4. Choose options:
   - ☑ Include Model
   - ☑ Include View
   - ☑ Create CRUD Operations
5. Optionally specify table name
6. Click "Generate Module"
7. Module created instantly!

#### Method 2: Manual Creation

**Step 1: Create Directories**

```bash
# Windows
New-Item -Path "Body\admin\Products\{Controllers,Models,Views}" -ItemType Directory -Force

# Linux/Mac
mkdir -p Body/admin/Products/{Controllers,Models,Views}
```

**Step 2: Create Controller**

`Body/admin/Products/Controllers/products.php`:

```php
<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class ProductsController extends Controller
{
    private function checkAuth(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void {
        $this->checkAuth();
        $this->load->model('admin/Products/Products');
        
        $data = [
            'title' => 'Products - Admin',
            'products' => $this->model_products->getAll()
        ];
        
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Products/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }
}
```

**Step 3: Create Model**

`Body/admin/Products/Models/ProductsModel.php`:

```php
<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class ProductsModel extends Model
{
    protected string $table = 'products';
    
    public function getAll(): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY product_id DESC");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get products error: " . $e->getMessage());
            return [];
        }
    }
}
```

**Step 4: Create View**

`Body/admin/Products/Views/index.ct`:

```html
<div class="admin-topbar">
    <div class="topbar-left">
        <h2><i class="fas fa-box"></i> Products</h2>
        <p>Manage your products</p>
    </div>
</div>

<div class="admin-content">
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['product_id'] ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td>
                            <a href="/admin/products/edit?id=<?= $product['product_id'] ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

**Step 5: Create Routes**

`Body/admin/Products/routes.json`:

```json
{
  "routes": [
    {
      "method": "GET",
      "path": "/admin/products",
      "handler": "admin/Products/Products@index",
      "name": "admin.products.index"
    }
  ]
}
```

**Step 6: Clear Cache**

```bash
# Windows
Remove-Item "Storage\cache\routes.php"

# Linux/Mac
rm Storage/cache/routes.php

# Or use ct_control: Cache Management > Clear Route Cache
```

### Module Best Practices

1. **Always use lowercase for controller filenames**
   - `products.php`, `users.php`, `blog.php`

2. **Use PascalCase for class names**
   - `ProductsController`, `ProductsModel`

3. **Use PascalCase for model filenames**
   - `ProductsModel.php`, `UsersModel.php`

4. **Always escape output in views**
   ```php
   <?= htmlspecialchars($variable) ?>
   ```

5. **Use prepared statements in models**
   ```php
   $stmt = $this->db->prepare("SELECT * FROM table WHERE id = ?");
   $stmt->execute([$id]);
   ```

6. **Check authentication in controllers**
   ```php
   private function checkAuth(): void {
       if (!isset($_SESSION['logged_in'])) {
           header('Location: /login');
           exit;
       }
   }
   ```

7. **Use named routes**
   ```json
   {"path": "/products", "handler": "...", "name": "products.index"}
   ```

8. **Always clear cache after route changes**
   ```bash
   rm Storage/cache/routes.php
   ```

---

## 🏗️ Architecture & Patterns

### Design Patterns Used

#### 1. MVC (Model-View-Controller)

**Model** - Data and business logic
```php
class ProductsModel extends Model {
    public function getAll(): array {
        // Database queries
    }
}
```

**View** - Presentation layer
```php
// Views/index.ct
<div><?= $product['name'] ?></div>
```

**Controller** - Request handling
```php
class ProductsController extends Controller {
    public function index(): void {
        $this->load->model('Products');
        $data = $this->model_products->getAll();
        $this->load->view('index', $data);
    }
}
```

#### 2. Registry Pattern

Centralized service container:

```php
$registry = Registry::getInstance();

// Register services
$registry->set('db', $dbConnection);
$registry->set('session', $sessionHandler);

// Access services
$db = $registry->get('db');

// Magic accessors in controllers
$this->db
$this->session
$this->cache
```

#### 3. Component Loader

CodeIgniter-style component loading:

```php
// Load models
$this->load->model('admin/Users/Users');

// Load libraries
$this->load->library('security/Validator');

// Load helpers
$this->load->helper('url');

// Load views
$this->load->view('admin/users/index', $data);
```

#### 4. Front Controller

Single entry point for all requests:

```
All requests → Index/index.php → Router → Controller → View
```

#### 5. Factory Pattern

Service factories for lazy loading:

```php
Registry::factory('db', function() {
    return new Database($config);
});
```

### OpenCart/CodeIgniter Influence

The framework borrows proven patterns:

**From OpenCart:**
- Registry pattern for dependency injection
- Magic accessors (`$this->db`, `$this->session`)
- Modular structure

**From CodeIgniter:**
- Loader class for components
- MVC folder structure
- Helper and library systems
- Simple, intuitive API

### Routing System

**Route Definition:**
```json
{
  "method": "GET",
  "path": "/blog/{id:\\d+}",
  "handler": "public/Blog/Blog@show",
  "name": "blog.show"
}
```

**Route Caching:**
- Automatic in production
- 25x faster route resolution
- Clear cache: `rm Storage/cache/routes.php`

**Named Routes:**
```php
// Generate URL
$url = Router::url('blog.show', ['id' => 123]);

// Redirect
Router::redirectToRoute('blog.show', ['id' => 123]);
```

**Route Groups:**
```php
Router::group(['prefix' => '/admin', 'middleware' => 'auth'], function() {
    Router::get('/dashboard', 'Dashboard@index');
});
```

### Database Layer

**PDO-based with prepared statements:**

```php
// In models
$stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(\PDO::FETCH_ASSOC);
```

**Transaction support:**
```php
$this->db->beginTransaction();
try {
    // Multiple queries
    $this->db->commit();
} catch (Exception $e) {
    $this->db->rollBack();
}
```

### View System

**Simple PHP templates (.ct files):**

```php
<!-- Views/product.ct -->
<div class="product">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <p><?= htmlspecialchars($product['description']) ?></p>
</div>
```

**Layout system:**
```php
// Load layout components
$this->load->view('admin/Common/header', $data);
$this->load->view('admin/Products/index', $data);
$this->load->view('admin/Common/footer', $data);
```

---

## 📊 Database Schema

### Core Tables

#### user_info
User accounts with role-based access:

```sql
CREATE TABLE user_info (
  user_id INT(11) NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student', 'teacher', 'admin', 'parent', 'staff'),
  phone_number VARCHAR(20) NULL,
  status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY unique_email_role (email, role)
);
```

#### roles
Dynamic role management:

```sql
CREATE TABLE roles (
  role_id INT(11) NOT NULL AUTO_INCREMENT,
  role_name VARCHAR(50) NOT NULL,
  role_slug VARCHAR(50) NOT NULL UNIQUE,
  display_name VARCHAR(100) NOT NULL,
  icon VARCHAR(50) DEFAULT '👤',
  color VARCHAR(20) DEFAULT '#667eea',
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (role_id)
);
```

#### pages
Content management:

```sql
CREATE TABLE pages (
  page_id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  content TEXT NULL,
  meta_description TEXT NULL,
  meta_keywords TEXT NULL,
  status ENUM('published', 'draft') DEFAULT 'draft',
  author_id INT(11) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (page_id)
);
```

#### html_blocks
Reusable content blocks:

```sql
CREATE TABLE html_blocks (
  block_id INT(11) NOT NULL AUTO_INCREMENT,
  block_name VARCHAR(100) NOT NULL,
  block_slug VARCHAR(100) NOT NULL UNIQUE,
  content TEXT NULL,
  location VARCHAR(50) DEFAULT 'global',
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (block_id)
);
```

### Sample Data

The database schema includes default sample data:

- 1 Admin user (admin/admin123)
- 4 Test users (one per role)
- 5 Default roles with icons and colors

---

## ⚙️ Automation & Tools

### Unified Control Center

The `ct_control` script provides comprehensive system management:

**Location:** `Storage/automate/`

**Files:**
- `ct_control.bat` (Windows)
- `ct_control.sh` (Linux/Mac)
- `README.md` (Documentation)

### Main Menu

```
[1] WAMP/Apache Server Management
[2] Environment Setup
[3] Database Management
[4] Cache Management
[5] Complete System Setup
[6] Development Tools
[7] Backup & Restore
[8] System Information
```

### Common Tasks

**Start Development:**
```bash
cd Storage/automate
ct_control.bat
# Select [1] > [1] Start WAMP Server
```

**Clear Cache:**
```bash
# Select [4] > [1] Clear All Cache
```

**Backup Database:**
```bash
# Select [7] > [1] Backup Database
```

**Create Module:**
```
Use web interface: http://frame.ct.com/admin/module-generator
```

### Backup System

**Automated backups to:**
- `Backups/db/` - Database dumps
- `Backups/uploads/` - Upload files
- `Backups/configs/` - Configuration files

**Backup naming:**
```
backup_dbname_YYYYMMDD_HHMMSS.sql
uploads_YYYYMMDD_HHMMSS.tar.gz
```

---

## 🔧 Troubleshooting

### Common Issues

#### 1. Routes Not Working (404 Errors)

**Problem:** New routes showing 404  
**Solution:**
```bash
# Clear route cache
rm Storage/cache/routes.php

# Or via ct_control: [4] > [2]
```

#### 2. Can't Access Admin Panel

**Problem:** Permission denied or redirect loop  
**Solutions:**
1. Clear browser cookies/cache
2. Check session in database
3. Verify `.env` has correct settings:
   ```env
   APP_ENV=development
   APP_DEBUG=true
   ```

#### 3. Database Connection Failed

**Problem:** Can't connect to database  
**Solutions:**
1. Verify MySQL is running:
   ```bash
   # Windows
   net start wampmysqld64
   
   # Linux/Mac
   sudo service mysql start
   ```

2. Check `.env` credentials:
   ```env
   DB_HOST=localhost
   DB_DATABASE=ct_frame
   DB_USERNAME=root
   DB_PASSWORD=yourpassword
   ```

3. Test connection:
   ```bash
   mysql -u root -p
   ```

#### 4. White Screen / 500 Error

**Problem:** Blank page or internal server error  
**Solutions:**
1. Enable error display:
   ```env
   APP_DEBUG=true
   ```

2. Check error logs:
   ```bash
   # Apache
   tail -f /var/log/apache2/error.log
   
   # WAMP
   C:\wamp64\logs\apache_error.log
   ```

3. Check PHP errors:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

#### 5. Module Not Loading

**Problem:** New module shows 404  
**Checklist:**
- ☑ Controller file exists and named correctly
- ☑ Class name matches pattern (XxxController)
- ☑ routes.json exists and valid JSON
- ☑ Route cache cleared
- ☑ Web server restarted

#### 6. Login Not Working

**Problem:** Can't login with credentials  
**Solutions:**
1. Verify user exists in database:
   ```sql
   SELECT * FROM user_info WHERE username = 'admin';
   ```

2. Check password hash:
   ```php
   // Default password is: admin123
   // If changed, reset it:
   $hash = password_hash('newpassword', PASSWORD_BCRYPT);
   ```

3. Check user status:
   ```sql
   UPDATE user_info SET status = 'active' WHERE username = 'admin';
   ```

#### 7. HTTPS Redirect Loop (Local)

**Problem:** Constant redirect on localhost  
**Solution:**
```env
# In .env file
FORCE_HTTPS=false
```

#### 8. Permission Denied (Linux/Mac)

**Problem:** Can't write to Storage/Backups  
**Solution:**
```bash
sudo chmod -R 755 Storage
sudo chmod -R 755 Backups
sudo chown -R www-data:www-data Storage
```

---

## ✅ Best Practices

### Security

1. **Always escape output**
   ```php
   <?= htmlspecialchars($user_input) ?>
   ```

2. **Use prepared statements**
   ```php
   $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
   $stmt->execute([$id]);
   ```

3. **Hash passwords**
   ```php
   $hash = password_hash($password, PASSWORD_BCRYPT);
   ```

4. **Validate input**
   ```php
   $email = filter_var($input, FILTER_VALIDATE_EMAIL);
   ```

5. **Check authentication**
   ```php
   if (!isset($_SESSION['logged_in'])) {
       header('Location: /login');
       exit;
   }
   ```

### Performance

1. **Use route caching in production**
   ```env
   APP_ENV=production  # Enables route cache
   ```

2. **Clear cache when needed**
   ```bash
   rm Storage/cache/routes.php
   ```

3. **Use lazy loading**
   ```php
   Registry::factory('service', function() {
       return new Service();  # Only created when needed
   });
   ```

4. **Index database columns**
   ```sql
   CREATE INDEX idx_username ON user_info(username);
   ```

### Code Organization

1. **One controller per file**
2. **Follow naming conventions**
   - Controllers: lowercase filename, PascalCase class
   - Models: PascalCase filename and class
   - Views: lowercase .ct files

3. **Keep controllers thin**
   - Move business logic to models
   - Keep views presentation-only

4. **Use meaningful names**
   ```php
   // Good
   $activeUsers = $this->model_users->getActive();
   
   // Bad
   $data = $this->model->get();
   ```

### Documentation

1. **Comment complex logic**
   ```php
   // Calculate total with tax and discount
   $total = ($subtotal * (1 + $taxRate)) * (1 - $discount);
   ```

2. **Use docblocks**
   ```php
   /**
    * Get active users
    * @param int $limit Maximum results
    * @return array User data
    */
   public function getActive(int $limit = 10): array
   ```

3. **Keep README updated**
4. **Document API endpoints**

### Testing

1. **Test in development first**
   ```env
   APP_ENV=development
   APP_DEBUG=true
   ```

2. **Clear cache before testing**
3. **Test on multiple devices**
4. **Check browser console for errors**
5. **Review error logs**

---

## 📞 Support & Resources

### Documentation Files

- **COMPLETE_FRAMEWORK_GUIDE.md** (this file) - Complete reference
- **Storage/automate/README.md** - Control center guide
- **README.md** - Framework overview
- **Docs/** - Additional documentation

### Quick Links

- Admin Panel: `http://frame.ct.com/admin`
- Login Page: `http://frame.ct.com/login`
- Public Site: `http://frame.ct.com`
- Module Generator: `http://frame.ct.com/admin/module-generator`

### Default Test Accounts

| Role    | Username      | Password  |
|---------|---------------|-----------|
| Admin   | admin         | admin123  |
| Student | john.student  | admin123  |
| Teacher | jane.teacher  | admin123  |
| Parent  | bob.parent    | admin123  |
| Staff   | alice.staff   | admin123  |

### Getting Help

1. Check this guide first
2. Review error logs
3. Check database schema
4. Test with default accounts
5. Clear all caches
6. Restart web server

---

## 🎉 Changelog

### Version 2.0.0 (Current)

**New Features:**
- ✅ Complete admin panel with 12 modules
- ✅ Role-based authentication system
- ✅ Dynamic role management from database
- ✅ User management with advanced filtering
- ✅ Page management system
- ✅ HTML blocks manager
- ✅ Module generator (auto-create modules)
- ✅ Automation center
- ✅ AI assistant integration
- ✅ Public content management
- ✅ User portal with role-specific dashboards
- ✅ Unified control center (`ct_control`)
- ✅ Automated backup system
- ✅ Responsive, mobile-first UI

**Improvements:**
- ⚡ 25x faster route resolution
- 🔒 Enhanced security (CSRF, XSS protection)
- 📱 Mobile-responsive admin panel
- 🎨 Modern UI with Font Awesome icons
- 📊 Statistics and analytics
- 🔍 Advanced search and filtering
- 💾 Automatic cache management

**Bug Fixes:**
- ✅ Fixed double slash URL issues
- ✅ Fixed HTTPS redirect loop on localhost
- ✅ Resolved controller loading issues
- ✅ Fixed route caching bugs
- ✅ Corrected session management

---

## 📄 License

This project is licensed under the MIT License.

---

## 🌟 Statistics

- **Core Framework Files**: 50+
- **Admin Modules**: 12
- **Lines of Code**: ~15,000+
- **Database Tables**: 4 core tables
- **Default Routes**: 60+
- **Documentation Pages**: 40+
- **Test Accounts**: 5
- **Supported Roles**: 5 (+ custom)

---

**Framework Version**: 2.0.0  
**Status**: ✅ Production Ready  
**Last Updated**: October 13, 2025

**Made with ❤️ by the CyberTirah Team**

---

### Quick Reference Card

```
📦 Installation: ct_control.bat > [5]
🔐 Login: http://frame.ct.com/login (admin/admin123)
🎛️ Admin: http://frame.ct.com/admin
🔧 Generate Module: /admin/module-generator
💾 Clear Cache: rm Storage/cache/routes.php
📊 Database: ct_control > [3]
🔄 Backup: ct_control > [7]
📚 Docs: This file + Docs/ folder
```

---

**End of Complete Framework Guide**

