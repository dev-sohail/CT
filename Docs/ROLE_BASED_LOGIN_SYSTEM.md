# Role-Based Login System with Database Integration

## Overview
The CyberTirah Framework now features a complete role-based authentication system that fetches user roles and credentials dynamically from the database.

## Features Implemented

### ✅ Dynamic Role Fetching
- Roles are loaded from `roles` table in the database
- Fallback to default roles if database is empty
- Supports custom role icons and colors
- Easy to add/remove roles via database

### ✅ Enhanced Authentication
- Login with **username OR email**
- Password verification (supports both hashed and plain text)
- Account status checking (active, inactive, suspended)
- Last login timestamp tracking
- Complete user data stored in session

### ✅ Security Features
- Password hashing using PHP's `password_hash()`
- SQL injection protection via prepared statements
- Account status validation
- Role-based access control
- Session management with complete user data

### ✅ User Session Data
When a user logs in, the following data is stored in `$_SESSION`:
- `logged_in` - Login status (boolean)
- `user_id` - Unique user ID
- `username` - Username
- `email` - Email address
- `first_name` - First name
- `last_name` - Last name
- `full_name` - Full name (first + last)
- `role` - User role (student, teacher, admin, etc.)
- `status` - Account status
- `last_login` - Previous login timestamp
- `login_time` - Current login timestamp

## Database Setup

### Step 1: Import Database Schema

Run the SQL file to create necessary tables:

```bash
mysql -u root -p ct_frame < database_schema.sql
```

Or import manually via phpMyAdmin:
1. Open phpMyAdmin
2. Select `ct_frame` database
3. Click "Import" tab
4. Choose `database_schema.sql`
5. Click "Go"

### Step 2: Verify Tables Created

Two tables should be created:
1. **`user_info`** - Stores user accounts
2. **`roles`** - Stores available roles

### Step 3: Test Accounts

The schema includes sample test accounts:

| Role | Username | Email | Password |
|------|----------|-------|----------|
| Admin | admin | admin@frame.ct.com | admin123 |
| Student | john.student | john@student.com | admin123 |
| Teacher | jane.teacher | jane@teacher.com | admin123 |
| Parent | bob.parent | bob@parent.com | admin123 |
| Staff | alice.staff | alice@staff.com | admin123 |

## File Structure

### Controllers
- **`Body/public/Auth/Controllers/auth.php`** - Authentication controller
  - `login()` - Display login form with dynamic roles
  - `processLogin()` - Handle login submission
  - `redirectToPortal()` - Redirect based on role

### Models
- **`Body/public/Auth/Models/AuthModel.php`** - Authentication model
  - `getRoles()` - Fetch active roles from database
  - `authenticate()` - Validate credentials and return user data
  - `updateLastLogin()` - Update last login timestamp
  - `register()` - Register new user
  - `resetPassword()` - Reset user password

### Views
- **`Body/public/Auth/Views/login.ct`** - Login page with dynamic role buttons

### Routes
- **`Body/public/Auth/routes.json`** - Authentication routes
  - `GET /login` - Show login form
  - `POST /login` - Process login
  - `GET /logout` - Logout
  - `GET /register` - Show registration form
  - `POST /register` - Process registration

## How It Works

### 1. Role Selection
```php
// Controller fetches roles from database
$this->load->model('public/Auth/Auth');
$roles = $this->model_auth->getRoles();

// Roles are passed to view
$data = ['roles' => $roles];
```

### 2. Dynamic Role Display
```php
// View loops through roles and creates buttons
<?php foreach ($roles as $role): ?>
    <button name="role" value="<?= $role['role_slug'] ?>">
        <?= $role['icon'] ?> <?= $role['display_name'] ?>
    </button>
<?php endforeach; ?>
```

### 3. Authentication Process
```php
// Model fetches user from database
$query = "SELECT * FROM user_info WHERE username = ? AND role = ?";
// Or by email:
$query = "SELECT * FROM user_info WHERE email = ? AND role = ?";

// Verify password
if (password_verify($password, $storedPassword)) {
    // Update last login
    $this->updateLastLogin($userId);
    
    // Return user data
    return ['success' => true, 'user' => $userData];
}
```

### 4. Session Management
```php
// Controller stores complete user data in session
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['role'] = $user['role'];
```

### 5. Portal Redirection
```php
// User is redirected based on their role
$url = match($role) {
    'student' => '/student/dashboard',
    'teacher' => '/teacher/dashboard',
    'admin' => '/admin',
    'parent' => '/parent/dashboard',
    'staff' => '/staff/dashboard',
    default => '/'
};
```

## Usage Examples

### Check if User is Logged In
```php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo "Welcome " . $_SESSION['full_name'];
}
```

### Check User Role
```php
if ($_SESSION['role'] === 'admin') {
    // Admin-specific code
} elseif ($_SESSION['role'] === 'teacher') {
    // Teacher-specific code
}
```

### Access User Data
```php
$userId = $_SESSION['user_id'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];
```

## Adding New Roles

### Via Database
```sql
INSERT INTO `roles` 
(`role_name`, `role_slug`, `display_name`, `icon`, `color`, `is_active`) 
VALUES 
('Manager', 'manager', 'Manager Login', '👨‍💼', '#6c757d', 1);
```

### Update Portal Redirection
Edit `Body/public/Auth/Controllers/auth.php`:
```php
private function redirectToPortal(string $role): void
{
    $url = match($role) {
        'student' => '/student/dashboard',
        'teacher' => '/teacher/dashboard',
        'admin' => '/admin',
        'parent' => '/parent/dashboard',
        'staff' => '/staff/dashboard',
        'manager' => '/manager/dashboard', // Add new role
        default => '/'
    };
    
    header('Location: ' . $url);
    exit;
}
```

## Security Best Practices

### 1. Always Use Hashed Passwords
```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
```

### 2. Validate User Input
```php
$username = trim($_POST['username']);
$role = trim($_POST['role']);
```

### 3. Use Prepared Statements
```php
$stmt = $this->db->prepare("SELECT * FROM user_info WHERE username = ?");
$stmt->execute([$username]);
```

### 4. Check Account Status
```php
if ($user['status'] !== 'active') {
    return ['success' => false, 'message' => 'Account is inactive'];
}
```

### 5. Log Authentication Attempts
```php
error_log("Authentication attempt for: " . $username);
```

## Troubleshooting

### Issue: "No roles available"
**Solution:** 
1. Check if `roles` table exists
2. Run `database_schema.sql` to populate roles
3. Check database connection in `.env`

### Issue: "Invalid username or role"
**Solution:**
1. Verify user exists in `user_info` table
2. Check role matches exactly
3. Verify account status is 'active'

### Issue: "Invalid password"
**Solution:**
1. Check if password is hashed
2. For testing, use sample password: `admin123`
3. Reset password using forgot password feature

### Issue: Session data not persisting
**Solution:**
1. Check if session is started in `Brain/ct_brain.php`
2. Verify `session.save_path` is writable
3. Check session configuration in `.env`

## Testing

### Test Login Flow
1. Go to `http://frame.ct.com/login`
2. Select a role (e.g., Admin)
3. Enter credentials:
   - Username: `admin` or Email: `admin@frame.ct.com`
   - Password: `admin123`
4. Click "Login"
5. Should redirect to role-specific portal

### Verify Session Data
Create `test_session.php` in root:
```php
<?php
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
```

Access: `http://frame.ct.com/test_session.php`

## API Reference

### AuthModel Methods

#### `getRoles(): array`
Fetches all active roles from database
```php
$roles = $this->model_auth->getRoles();
// Returns: [['role_slug' => 'admin', 'display_name' => 'Admin Login', ...], ...]
```

#### `authenticate(string $username, string $password, string $role): array`
Authenticates user and returns user data
```php
$result = $this->model_auth->authenticate('admin', 'admin123', 'admin');
// Returns: ['success' => true, 'user' => [...user data...], 'message' => '...']
```

#### `register(array $data): array`
Registers new user
```php
$result = $this->model_auth->register([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'password' => 'password123',
    'role' => 'student'
]);
```

## Related Documentation
- [Local HTTP Setup](LOCAL_HTTP_SETUP.md) - Configure HTTP for local development
- [Double Slash URL Fix](DOUBLE_SLASH_URL_FIX.md) - URL generation fixes
- [Authentication System](AUTH_AND_404_SYSTEM.md) - Complete auth documentation

## Date
October 12, 2025

## Version
2.0.0 - Enhanced with database role fetching

