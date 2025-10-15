# 🎨 CyberTirah Framework - Footer System

## Overview

A comprehensive, modern footer component for the CyberTirah Framework with responsive design, social media integration, and dynamic portal links.

---

## ✨ Features

### 1. **Company Information**
- Company name and branding
- Description/tagline
- Copyright notice
- Framework version display

### 2. **Navigation Sections**
- **Quick Links** - Home, About, Team, Contact
- **Resources** - Documentation, API, Privacy, Terms
- **Connect** - Social media and portal access

### 3. **Social Media Integration**
- Facebook
- LinkedIn
- YouTube
- GitHub
- Hover effects with brand colors

### 4. **User Authentication Links**
- Conditional display based on login status
- Login/Register buttons (logged out)
- Profile/Logout buttons (logged in)
- Future portal links support

### 5. **Interactive Features**
- Smooth scroll to top button
- Hover effects on all links
- Optional WhatsApp chat widget
- Responsive grid layout

---

## 📁 File Location

**Path**: `Body/public/Common/Views/footer.ct`

This footer is automatically loaded by controllers using:
```php
$this->load->view('public/Common/footer', $data);
```

---

## 🎨 Design Features

### Color Scheme
- **Background**: Dark gray (`#2d3748`)
- **Text**: Light gray (`#e2e8f0`)
- **Primary Links**: Purple-blue (`#667eea`)
- **Hover Effects**: Brand-specific colors

### Layout
- Responsive 4-column grid (auto-fit)
- Mobile-friendly (stacks on small screens)
- Max-width container (1200px)
- Proper spacing and padding

### Typography
- Clear hierarchy
- Readable font sizes
- Proper line heights
- Consistent styling

---

## 🔧 Customization

### 1. Update Company Information

Edit the `$footerData` array in footer.ct:

```php
$footerData = [
    'company_name' => 'Your Company Name',
    'description' => 'Your company description...',
    'social' => [
        'facebook' => 'your-facebook-handle',
        'linkedin' => 'your-linkedin-handle',
        'youtube' => 'your-youtube-handle',
        'github' => 'your-github-handle'
    ]
];
```

### 2. Add/Remove Navigation Links

**Quick Links Section**:
```php
<li style="margin-bottom:0.5rem;">
    <a href="<?= Router::url('your.route') ?>" 
       style="color:#cbd5e0;text-decoration:none;font-size:0.875rem;">
        Your Link
    </a>
</li>
```

**Resources Section**:
```php
<li style="margin-bottom:0.5rem;">
    <a href="/your-page" 
       style="color:#cbd5e0;text-decoration:none;font-size:0.875rem;">
        Your Resource
    </a>
</li>
```

### 3. Customize Colors

Change the footer background:
```php
<footer id="footer" style="background:#your-color;">
```

Change link colors:
```php
style="color:#your-color;"
onmouseover="this.style.color='#your-hover-color'"
```

### 4. Add More Social Networks

```php
<a href="https://twitter.com/<?= htmlspecialchars($footerData['social']['twitter']) ?>" 
   target="_blank" 
   style="color:#cbd5e0;font-size:1.5rem;"
   onmouseover="this.style.color='#1da1f2'"
   onmouseout="this.style.color='#cbd5e0'"
   title="Twitter">
    <i class="fa-brands fa-twitter"></i>
</a>
```

---

## 📊 Layout Structure

```
Footer
├── Company Info & Logo
│   ├── Company name
│   ├── Description
│   ├── Copyright
│   └── Version
├── Quick Links
│   ├── Home
│   ├── About
│   ├── Team
│   └── Contact
├── Resources
│   ├── Documentation
│   ├── API Reference
│   ├── Privacy Policy
│   └── Terms of Service
└── Connect
    ├── Social Icons
    ├── Portal Links
    └── Scroll to Top
```

---

## 🔗 Router Integration

The footer uses the Router for URL generation:

```php
// Named routes
<a href="<?= Router::url('home') ?>">Home</a>
<a href="<?= Router::url('about') ?>">About</a>

// Direct URLs
<a href="/about/team">Team</a>
<a href="/privacy-policy">Privacy</a>
```

---

## 👤 User Authentication

### Logged Out State
```php
<?php if (!$isLoggedIn): ?>
    <a href="/login">Login</a>
    <a href="/register">Register</a>
<?php endif; ?>
```

### Logged In State
```php
<?php if ($isLoggedIn): ?>
    <a href="/profile">My Profile</a>
    <a href="/logout">Logout</a>
<?php endif; ?>
```

### Portal Links (Future)
```php
// Add portal links based on role
<?php if ($userRole === 'admin'): ?>
    <a href="/admin/dashboard">Admin Panel</a>
<?php elseif ($userRole === 'teacher'): ?>
    <a href="/teacher/portal">Teacher Portal</a>
<?php endif; ?>
```

---

## 🌐 Social Media Integration

### Current Implementation
```php
$footerData['social'] = [
    'facebook' => 'cybertirah',
    'linkedin' => 'cybertirah',
    'youtube' => 'cybertirah',
    'github' => 'cybertirah'
];
```

### Links Generated
- Facebook: `https://www.facebook.com/cybertirah`
- LinkedIn: `https://pk.linkedin.com/in/cybertirah`
- YouTube: `https://www.youtube.com/@cybertirah`
- GitHub: `https://github.com/cybertirah`

### Hover Effects
Each icon has brand-specific hover colors:
- Facebook → Blue (`#1877f2`)
- LinkedIn → Blue (`#0077b5`)
- YouTube → Red (`#ff0000`)
- GitHub → White (`#ffffff`)

---

## 💬 WhatsApp Chat Widget (Optional)

### Enable WhatsApp Widget

Add to your `.env` file:
```env
WHATSAPP_NUMBER=+1234567890
```

Define constant in bootstrap:
```php
define('WHATSAPP_NUMBER', getenv('WHATSAPP_NUMBER') ?: false);
```

The widget will automatically load if the constant is set.

### Customize Widget
```javascript
var options = {
    whatsapp: "<?= WHATSAPP_NUMBER ?>",
    message: "Your custom message",
    call_to_action: "Your CTA text",
    position: "right", // or "left"
};
```

---

## 📱 Responsive Design

### Desktop (1200px+)
- 4-column grid layout
- Full social icons visible
- All links displayed

### Tablet (768px - 1199px)
- 2-column grid (auto-fit)
- Stacked sections
- Maintained spacing

### Mobile (< 768px)
- Single column
- Stacked vertically
- Optimized touch targets

---

## ⬆️ Scroll to Top

### Functionality
Smooth scroll back to the header when clicked.

### Implementation
```php
<a href="#header" 
   style="display:inline-flex;width:2.5rem;height:2.5rem;border-radius:50%;">
    <i class="fas fa-chevron-up"></i>
</a>
```

### JavaScript
```javascript
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});
```

---

## 🔒 Security Features

### XSS Prevention
All user data is escaped:
```php
<?= htmlspecialchars($footerData['company_name']) ?>
<?= htmlspecialchars($footerData['social']['facebook']) ?>
```

### External Links
All external links include security attributes:
```php
target="_blank" 
rel="noopener noreferrer"
```

---

## 🎯 Usage in Controllers

### Basic Usage
```php
class HomeController extends Controller
{
    public function index(): void
    {
        $data = ['title' => 'Home'];
        
        // Load header
        $this->load->view('public/Common/header', $data);
        
        // Load content
        $this->load->view('public/Home/home', $data);
        
        // Load footer
        $this->load->view('public/Common/footer', $data);
    }
}
```

### Custom Footer Data
```php
$data = [
    'footer_custom' => [
        'company_name' => 'Custom Name',
        'description' => 'Custom description'
    ]
];

$this->load->view('public/Common/footer', $data);
```

---

## 🎨 Styling Options

### Option 1: Inline Styles (Current)
Advantages:
- No external CSS dependencies
- Fast loading
- Self-contained component

### Option 2: CSS Classes (Alternative)
For production, consider extracting to CSS:

```css
.footer {
    background: #2d3748;
    color: #e2e8f0;
    padding: 3rem 0;
}

.footer-link {
    color: #cbd5e0;
    text-decoration: none;
}

.footer-link:hover {
    color: #667eea;
}
```

---

## 📊 Performance

### Optimization Features
- ✅ Minimal JavaScript (only for smooth scroll)
- ✅ Inline styles (no external CSS)
- ✅ Lazy-loaded WhatsApp widget (optional)
- ✅ Optimized icon fonts (Font Awesome)

### Load Time
- **Footer HTML**: < 2KB
- **JavaScript**: < 1KB
- **Total Impact**: Negligible

---

## 🔄 Version History

### Version 1.0.0 (Current)
- Initial footer implementation
- 4-column responsive grid
- Social media integration
- Portal links support
- Smooth scroll feature
- WhatsApp widget support

---

## 📚 Related Documentation

- **[ENHANCED_HEADER_SYSTEM.md](./ENHANCED_HEADER_SYSTEM.md)** - Header documentation
- **[ABOUT_MODULE.md](./ABOUT_MODULE.md)** - About module
- **[ROUTING_AND_REGISTRY_GUIDE.md](./ROUTING_AND_REGISTRY_GUIDE.md)** - Routing guide

---

## 🎊 Summary

The CyberTirah Framework footer provides:

✅ **Modern Design** - Dark theme with brand colors  
✅ **Responsive Layout** - Works on all devices  
✅ **Social Integration** - Multiple social networks  
✅ **User-Aware** - Conditional portal links  
✅ **Interactive** - Smooth scroll, hover effects  
✅ **Extensible** - Easy to customize  
✅ **Secure** - XSS prevention built-in  
✅ **Performance** - Minimal overhead  

---

**Footer Location**: `Body/public/Common/Views/footer.ct`  
**Status**: ✅ Implemented and Tested  
**Version**: 1.0.0

*Created: October 12, 2025*  
*Framework Version: 2.0.0*  
*Status: ✅ Production Ready*

