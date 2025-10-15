# Admin Panel - Quick Start Guide

## 🎉 What's Been Completed

### ✅ Modern Admin UI Framework
- **Professional Sidebar Navigation** with dark theme
- **Responsive Design** - works on mobile, tablet, desktop
- **Beautiful Header** with topbar and action buttons
- **Consistent UI Components** - cards, buttons, tables, forms, alerts

### ✅ Complete Role Management System
**Location:** `Body/admin/Roles/`
- ✅ List all roles with icons, colors, status
- ✅ Create new roles with live preview
- ✅ Edit existing roles
- ✅ Delete roles (with validation)
- ✅ Toggle role active/inactive status
- ✅ Full CRUD operations

## 🚀 Testing the Admin Panel

### Step 1: Access Admin Panel
```
URL: http://frame.ct.com/admin
```

### Step 2: Test Role Management
```
URL: http://frame.ct.com/admin/roles
```

**Available Actions:**
1. **View all roles** - See the list of existing roles
2. **Create new role** - Click "Create New Role" button
3. **Edit role** - Click edit icon on any role
4. **Toggle status** - Click toggle icon to activate/deactivate
5. **Delete role** - Click delete icon (with confirmation)

### Step 3: Create a Test Role
1. Go to `/admin/roles/create`
2. Fill in:
   - **Role Name:** `Manager`
   - **Role Slug:** `manager` (auto-generated)
   - **Display Name:** `Manager Login`
   - **Icon:** `👨‍💼`
   - **Color:** Choose any color
   - **Status:** Check "Active"
3. Click "Create Role"
4. Verify role appears in login page

## 📋 Next Modules to Build

### Priority 1: User Management
**Create:** `Body/admin/Users/`

**Quick Implementation:**
```bash
# PowerShell
New-Item -Path "Body\admin\Users\{Controllers,Models,Views}" -ItemType Directory -Force
```

**Required Files:**
1. `Controllers/users.php` - User CRUD operations
2. `Models/UsersModel.php` - Database operations
3. `Views/index.ct` - User list
4. `Views/create.ct` - Create user
5. `Views/edit.ct` - Edit user
6. `routes.json` - User routes

**Features:**
- List users with filtering (by role, status)
- Create/Edit/Delete users
- Assign/Change user roles
- View user details
- Bulk actions (activate, deactivate, delete)

### Priority 2: Page Manager
**Create:** `Body/admin/Pages/`

**Features:**
- List all pages
- Create/Edit/Delete pages
- Rich text editor for content
- URL slug management
- SEO meta tags
- Publish/Draft status
- Page templates

### Priority 3: HTML Blocks Manager
**Create:** `Body/admin/Blocks/`

**Features:**
- Reusable HTML/CSS/JS blocks
- Code editor with syntax highlighting
- Block categories
- Insert into pages via shortcode
- Preview before saving

### Priority 4: Module Generator
**Create:** `Body/admin/ModuleGenerator/`

**Features:**
- Generate complete MVC modules
- Choose module type (admin/public/api/user)
- Auto-create Controllers, Models, Views, Routes
- Custom templates
- Preview generated code
- One-click deploy

## 🔧 How to Add a New Admin Module

### Step 1: Create Directory Structure
```powershell
$ModuleName = "Users"  # Change this
New-Item -Path "Body\admin\$ModuleName\Controllers" -ItemType Directory -Force
New-Item -Path "Body\admin\$ModuleName\Models" -ItemType Directory -Force
New-Item -Path "Body\admin\$ModuleName\Views" -ItemType Directory -Force
```

### Step 2: Create Controller
**File:** `Body/admin/Users/Controllers/users.php`

```php
<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Controller.php';

class UsersController extends Controller
{
    public function index(): void
    {
        // Check admin auth
        if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        // Load model and get data
        $this->load->model('admin/Users/Users');
        $users = $this->model_users->getAllUsers();
        
        // Prepare data for view
        $data = [
            'title' => 'User Management - Admin',
            'users' => $users
        ];
        
        // Load views
        $this->load->view('admin/Common/header', $data);
        $this->load->view('admin/Users/index', $data);
        $this->load->view('admin/Common/footer', $data);
    }
    
    public function create(): void { /* form */ }
    public function store(): void { /* save */ }
    public function edit(): void { /* edit form */ }
    public function update(): void { /* update */ }
    public function delete(): void { /* delete */ }
}
```

### Step 3: Create Model
**File:** `Body/admin/Users/Models/UsersModel.php`

```php
<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class UsersModel extends Model
{
    protected string $table = 'user_info';
    
    public function getAllUsers(): array
    {
        try {
            $query = "SELECT * FROM {$this->table} ORDER BY user_id DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    // Add more methods: createUser(), updateUser(), deleteUser()
}
```

### Step 4: Create View
**File:** `Body/admin/Users/Views/index.ct`

```php
<div class="admin-topbar">
    <div class="topbar-left">
        <h2><i class="fas fa-users"></i> User Management</h2>
        <p>Manage system users</p>
    </div>
    <div class="topbar-right">
        <a href="/admin/users/create" class="topbar-btn">
            <i class="fas fa-plus"></i> Create New User
        </a>
    </div>
</div>

<div class="admin-content">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> All Users</h3>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['user_id'] ?></td>
                        <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($user['role']) ?></span></td>
                        <td><span class="badge badge-success"><?= htmlspecialchars($user['status']) ?></span></td>
                        <td>
                            <a href="/admin/users/edit?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="/admin/users/delete?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-danger confirm-delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
```

### Step 5: Create Routes
**File:** `Body/admin/Users/routes.json`

```json
{
  "routes": [
    {
      "method": "GET",
      "path": "/admin/users",
      "handler": "admin/Users/Users@index",
      "name": "admin.users.index"
    },
    {
      "method": "GET",
      "path": "/admin/users/create",
      "handler": "admin/Users/Users@create",
      "name": "admin.users.create"
    },
    {
      "method": "POST",
      "path": "/admin/users/store",
      "handler": "admin/Users/Users@store",
      "name": "admin.users.store"
    },
    {
      "method": "GET",
      "path": "/admin/users/edit",
      "handler": "admin/Users/Users@edit",
      "name": "admin.users.edit"
    },
    {
      "method": "POST",
      "path": "/admin/users/update",
      "handler": "admin/Users/Users@update",
      "name": "admin.users.update"
    },
    {
      "method": "GET",
      "path": "/admin/users/delete",
      "handler": "admin/Users/Users@delete",
      "name": "admin.users.delete"
    }
  ]
}
```

### Step 6: Clear Route Cache
```powershell
Remove-Item "Storage\cache\routes.php" -ErrorAction SilentlyContinue
```

### Step 7: Test
Visit: `http://frame.ct.com/admin/users`

## 🎨 UI Component Reference

### Stat Cards
```php
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
        <div class="stat-card-value">1,234</div>
        <div class="stat-card-label">Total Users</div>
    </div>
</div>
```

**Available Colors:** `primary`, `success`, `warning`, `danger`, `purple`

### Buttons
```php
<a href="#" class="btn btn-primary"><i class="fas fa-plus"></i> Add New</a>
<button class="btn btn-success">Save</button>
<button class="btn btn-danger">Delete</button>
<button class="btn btn-secondary">Cancel</button>
```

**Sizes:** `btn-sm` for small buttons

### Alerts
```php
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> Success message
</div>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> Error message
</div>
```

### Badges
```php
<span class="badge badge-success">Active</span>
<span class="badge badge-danger">Inactive</span>
<span class="badge badge-warning">Pending</span>
<span class="badge badge-info">Info</span>
```

### Forms
```php
<div class="form-group">
    <label><i class="fas fa-user"></i> Name</label>
    <input type="text" name="name" class="form-control" required>
</div>

<div class="form-group">
    <label>Description</label>
    <textarea name="description" class="form-control" rows="4"></textarea>
</div>

<div class="form-group">
    <label>Status</label>
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
        <input type="checkbox" name="is_active" value="1">
        <span>Active</span>
    </label>
</div>
```

## 📊 Database Tables Needed

### For User Management
Already exists: `user_info` table

### For Page Manager
```sql
CREATE TABLE IF NOT EXISTS `pages` (
    `page_id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` LONGTEXT,
    `status` ENUM('draft', 'published') DEFAULT 'draft',
    `author_id` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### For HTML Blocks
```sql
CREATE TABLE IF NOT EXISTS `html_blocks` (
    `block_id` INT(11) NOT NULL AUTO_INCREMENT,
    `block_name` VARCHAR(100) NOT NULL UNIQUE,
    `block_content` LONGTEXT,
    `category` VARCHAR(50),
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## ⚡ Quick Commands

### Create Module Directory
```powershell
$ModuleName = "ModuleName"
New-Item -Path "Body\admin\$ModuleName\{Controllers,Models,Views}" -ItemType Directory -Force
```

### Clear Cache
```powershell
Remove-Item "Storage\cache\routes.php" -ErrorAction SilentlyContinue
```

### Restart WAMP
```powershell
.\START_WAMP.bat
```

## 🎯 Current Status

### ✅ Completed
- ✅ Modern Admin UI with sidebar
- ✅ Responsive design
- ✅ Role Management (full CRUD)
- ✅ Navigation system
- ✅ UI component library

### 🔄 In Progress / To Do
- ⏳ User Management
- ⏳ Page Manager
- ⏳ HTML Blocks
- ⏳ Module Generator
- ⏳ Merge Automate modules
- ⏳ Merge AI modules
- ⏳ User Portal enhancements
- ⏳ Public Content Management

## 📚 Documentation

- **Complete Guide:** [ADMIN_PANEL_IMPLEMENTATION.md](ADMIN_PANEL_IMPLEMENTATION.md)
- **Role System:** [ROLE_BASED_LOGIN_SYSTEM.md](Docs/ROLE_BASED_LOGIN_SYSTEM.md)
- **Login System:** [LOGIN_SYSTEM_IMPROVEMENTS.md](LOGIN_SYSTEM_IMPROVEMENTS.md)

## 🆘 Troubleshooting

### Routes Not Working
```powershell
Remove-Item "Storage\cache\routes.php"
```

### Admin Access Denied
Make sure you're logged in as admin:
```
Username: admin
Password: admin123
```

### 404 on Admin Pages
Check if routes.json exists and is valid JSON

### Styles Not Showing
Make sure header.ct is being loaded with Font Awesome CDN

## 💡 Tips

1. **Always check admin authentication** in every controller method
2. **Use prepared statements** for all database queries
3. **Escape output** with `htmlspecialchars()` in views
4. **Clear route cache** after adding new routes
5. **Follow the MVC pattern** - keep logic in controllers/models
6. **Use consistent naming** - plural for resources (users, roles, pages)
7. **Add success/error messages** for user feedback
8. **Test on mobile** - responsive design is key

---

**Ready to build! Start with User Management next! 🚀**

