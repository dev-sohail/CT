# 📚 CyberTirah Framework - Cursor Rules Guide

## Overview

Comprehensive Cursor Rules have been generated to help AI assistants understand and work with the CyberTirah Framework effectively.

---

## 📋 Generated Rules

### 1. **cybertirah-architecture.mdc**
**Type**: Always Applied  
**Size**: 3.2 KB  
**Purpose**: Core framework architecture and patterns

**Covers**:
- Body-Brain architecture structure
- OpenCart/CodeIgniter-style patterns
- Named routes and URL generation
- Module structure (MVC)
- Route caching
- File naming conventions
- Best practices

**Key Sections**:
- Framework structure (Brain, Body, Index)
- Registry and Loader patterns
- Route definitions
- File naming flexibility

---

### 2. **module-development.mdc**
**Type**: Context-Aware (applies to Body/**/*.php, Body/**/*.ct, Body/**/*.json)  
**Size**: 5.6 KB  
**Purpose**: Complete guide for creating new modules

**Covers**:
- Directory structure creation
- Controller development
- Model development
- View templates
- Route definitions
- Component loading patterns
- Service access
- Route parameters
- Middleware usage

**Example Workflow**:
```bash
# 1. Create directories
mkdir -p Body/public/Blog/{Controllers,Models,Views}

# 2. Create controller (Body/public/Blog/Controllers/blog.php)
# 3. Create model (Body/public/Blog/Models/BlogModel.php)
# 4. Create views (Body/public/Blog/Views/*.ct)
# 5. Define routes (Body/public/Blog/routes.json)
```

---

### 3. **routing-system.mdc**
**Type**: Context-Aware (applies to **/routes.json, **/*Controller.php)  
**Size**: 5.9 KB  
**Purpose**: Complete routing system documentation

**Covers**:
- Route definition syntax
- Handler format
- Dynamic parameters with regex
- Named routes and URL generation
- Route groups and nesting
- Middleware (global and route-specific)
- HTTP method shortcuts
- Route caching
- Route information methods
- Common routing patterns

**Key Features**:
- RESTful resource routes
- API route patterns
- Parameter validation with regex
- Named route URL generation
- Route groups with prefixes

---

### 4. **views-templates.mdc**
**Type**: Context-Aware (applies to **/Views/*.ct, **/views/*.ct)  
**Size**: 6.2 KB  
**Purpose**: View and template development guide

**Covers**:
- View file structure (.ct files)
- Loading views via Loader
- Data passing from controllers
- Common views (header, footer)
- Breadcrumb system
- URL generation in views
- HTML output escaping (XSS prevention)
- Conditional rendering
- Styling patterns
- Common view patterns (list, detail, form)

**Security Focus**:
- Always use `htmlspecialchars()` for user data
- Proper escaping techniques
- XSS prevention

---

### 5. **registry-loader.mdc**
**Type**: Context-Aware (applies to **/Controller.php, **/Model.php)  
**Size**: 8.6 KB  
**Purpose**: Registry and Loader pattern documentation

**Covers**:
- Registry pattern (OpenCart/CodeIgniter style)
- Magic accessors for services
- Available services (db, session, cache, load)
- Registering custom services
- Loader pattern
- Loading models, views, libraries, helpers
- File naming conventions
- Dependency injection
- Service resolution
- Common patterns

**Service Access**:
```php
// Database
$this->db->query("SELECT * FROM users");

// Session
$this->session->get('user_id');

// Cache
$this->cache->get('cache_key');

// Loader
$this->load->model('public/Blog/Blog');
$this->load->view('public/Blog/index', $data);
```

---

## 🎯 Rule Application Strategy

### Always Applied Rules
- **cybertirah-architecture.mdc** - Applied to every AI request for context

### Context-Aware Rules
- **module-development.mdc** - When working with module files
- **routing-system.mdc** - When working with routes or controllers
- **views-templates.mdc** - When working with view templates
- **registry-loader.mdc** - When working with controllers or models

### On-Demand Rules
Can be manually referenced when needed for specific tasks

---

## 📊 Rules Statistics

| Rule | Size | Type | Files Covered |
|------|------|------|---------------|
| cybertirah-architecture | 3.2 KB | Always Applied | All |
| module-development | 5.6 KB | Context-Aware | Body/**/*.php, *.ct, *.json |
| routing-system | 5.9 KB | Context-Aware | **/routes.json, **/*Controller.php |
| views-templates | 6.2 KB | Context-Aware | **/Views/*.ct, **/views/*.ct |
| registry-loader | 8.6 KB | Context-Aware | **/Controller.php, **/Model.php |

**Total**: 5 rules, ~29 KB of documentation

---

## 🔍 What Each Rule Helps With

### Architecture Rule
**Helps when**:
- Understanding project structure
- Learning framework patterns
- Finding core files
- Understanding naming conventions

### Module Development Rule
**Helps when**:
- Creating new modules
- Building controllers
- Designing models
- Setting up routes

### Routing System Rule
**Helps when**:
- Defining routes
- Using named routes
- Adding middleware
- Generating URLs

### Views Templates Rule
**Helps when**:
- Creating views
- Passing data to views
- Using breadcrumbs
- Preventing XSS attacks

### Registry Loader Rule
**Helps when**:
- Accessing services
- Loading components
- Using dependency injection
- Understanding magic accessors

---

## 💡 How AI Assistants Use These Rules

### 1. **Automatic Context**
- `cybertirah-architecture.mdc` is always loaded
- Provides baseline understanding of framework

### 2. **File-Specific Context**
- When editing a Controller, `registry-loader.mdc` and `routing-system.mdc` are loaded
- When editing a View, `views-templates.mdc` is loaded
- When editing routes.json, `routing-system.mdc` is loaded

### 3. **Pattern Recognition**
- AI knows to use `$this->load->model()` instead of manual requires
- AI knows to use `Router::url()` for URL generation
- AI knows proper file naming conventions

### 4. **Code Generation**
- AI generates code following framework patterns
- AI includes proper security measures (htmlspecialchars)
- AI uses correct directory structures

---

## 🚀 Benefits

### For Developers
- ✅ **Consistent code generation** - AI follows framework patterns
- ✅ **Faster development** - AI knows where files go
- ✅ **Better suggestions** - Context-aware recommendations
- ✅ **Security by default** - AI includes security measures

### For AI Assistants
- ✅ **Framework understanding** - Knows CyberTirah patterns
- ✅ **Context-aware** - Loads relevant rules per file type
- ✅ **Pattern matching** - Recognizes and follows conventions
- ✅ **Code quality** - Generates framework-compliant code

---

## 📖 Usage Examples

### Example 1: Creating a Blog Module
**AI receives**:
- Architecture rule (always)
- Module development rule (*.php files)
- Routing system rule (routes.json)

**AI generates**:
1. Proper directory structure
2. Controller with correct naming
3. Model with database access
4. Views with security measures
5. Routes with named routes

### Example 2: Adding a Route
**AI receives**:
- Architecture rule (always)
- Routing system rule (routes.json file)

**AI suggests**:
- Proper route format
- Named route definition
- Handler format
- Middleware options

### Example 3: Creating a View
**AI receives**:
- Architecture rule (always)
- Views templates rule (*.ct file)

**AI generates**:
- Properly escaped output
- Breadcrumb integration
- Named route URLs
- Conditional rendering

---

## 🔧 Maintaining Rules

### Adding New Rules
1. Create `.mdc` file in `.cursor/rules/`
2. Add frontmatter with metadata
3. Use `[filename](mdc:path)` for file references
4. Include examples and patterns

### Updating Existing Rules
1. Edit the `.mdc` file
2. Add new patterns or examples
3. Update examples to reflect changes
4. Test with AI assistant

### Rule Best Practices
- Keep rules focused on specific topics
- Include practical examples
- Reference actual framework files
- Update when framework changes
- Test rules with real queries

---

## 📚 Related Documentation

- **[README.md](../README.md)** - Framework overview
- **[GETTING_STARTED.md](../GETTING_STARTED.md)** - Getting started guide
- **[ROUTING_AND_REGISTRY_GUIDE.md](./ROUTING_AND_REGISTRY_GUIDE.md)** - Detailed routing guide
- **[ABOUT_MODULE.md](./ABOUT_MODULE.md)** - About module example
- **[ENHANCED_HEADER_SYSTEM.md](./ENHANCED_HEADER_SYSTEM.md)** - Header system guide

---

## 🎊 Summary

The CyberTirah Framework now has **comprehensive Cursor Rules** that enable AI assistants to:

✅ **Understand the framework** - Complete architectural knowledge  
✅ **Generate correct code** - Follows all framework patterns  
✅ **Create modules** - Knows the proper structure  
✅ **Define routes** - Uses named routes and proper format  
✅ **Build views** - Includes security and breadcrumbs  
✅ **Use services** - Knows Registry and Loader patterns  
✅ **Follow conventions** - Proper file naming and structure  
✅ **Apply best practices** - Security, performance, maintainability  

---

**Rules Location**: `.cursor/rules/*.mdc`  
**Total Rules**: 5  
**Total Size**: ~29 KB  
**Status**: ✅ Complete and Active

*Last Updated: October 12, 2025*  
*Framework Version: 2.0.0*  
*Rules Version: 1.0.0*

