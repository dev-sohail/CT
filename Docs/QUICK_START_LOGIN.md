# Quick Start - Role-Based Login System

## 🚀 Setup in 3 Steps

### Step 1: Import Database
```bash
setup_login_system.bat
```
Enter your MySQL credentials when prompted.

### Step 2: Configure Environment (if not done)
```bash
create_local_env.bat
```
Sets `FORCE_HTTPS=false` for local development.

### Step 3: Start WAMP
```bash
START_WAMP.bat
```

## ✅ Test Login

**URL:** `http://frame.ct.com/login`

**Test Account:**
- **Username:** `admin`
- **Password:** `admin123`

## 📋 What's Available

### All Test Accounts
| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Student | john.student | admin123 |
| Teacher | jane.teacher | admin123 |
| Parent | bob.parent | admin123 |
| Staff | alice.staff | admin123 |

## 🎯 Features

✅ Login with username OR email  
✅ Dynamic role fetching from database  
✅ Account status checking (active/inactive/suspended)  
✅ Last login tracking  
✅ Complete user data in session  
✅ Role-based portal redirection  
✅ Password hashing support  
✅ Custom role icons and colors  

## 🔍 Access User Data

```php
// In any controller or view
$_SESSION['full_name']    // "Admin User"
$_SESSION['email']         // "admin@frame.ct.com"
$_SESSION['role']          // "admin"
$_SESSION['user_id']       // 1
```

## 📚 Documentation

- **Complete Guide:** [Docs/ROLE_BASED_LOGIN_SYSTEM.md](Docs/ROLE_BASED_LOGIN_SYSTEM.md)
- **Summary:** [LOGIN_SYSTEM_IMPROVEMENTS.md](LOGIN_SYSTEM_IMPROVEMENTS.md)
- **HTTP Setup:** [Docs/LOCAL_HTTP_SETUP.md](Docs/LOCAL_HTTP_SETUP.md)

## ⚙️ Adding New Roles

### 1. Add to Database
```sql
INSERT INTO `roles` 
(`role_name`, `role_slug`, `display_name`, `icon`, `color`, `is_active`) 
VALUES 
('Manager', 'manager', 'Manager Login', '👨‍💼', '#6c757d', 1);
```

### 2. Update Portal Redirect
Edit `Body/public/Auth/Controllers/auth.php`:
```php
$url = match($role) {
    'admin' => '/admin',
    'manager' => '/manager/dashboard',  // Add this
    // ... other roles
};
```

### 3. Done! 🎉

## 🛠️ Troubleshooting

### Issue: "No roles available"
**Fix:** Run `setup_login_system.bat` again

### Issue: "Invalid password"  
**Fix:** Use password: `admin123` (for test accounts)

### Issue: Can't connect to database
**Fix:** 
1. Check if WAMP MySQL is running
2. Verify `.env` database credentials
3. Ensure `ct_frame` database exists

## 📞 Need Help?

Check the logs:
- `Storage/logs/` - Error logs
- PHP error log in WAMP

---

**That's it! You're ready to go! 🚀**

