# CLI Commands

The Frame PHP framework includes a command-line interface via `cli.php` for common development tasks: migrations, code generation, cache management, database operations, and more.

## 1. Running CLI Commands

From the project root:

```bash
php cli.php <command> [arguments] [options]
```

### Examples

```bash
php cli.php migrate
php cli.php make:controller UserController
php cli.php cache:clear
php cli.php db:backup backup_2026.sql
php cli.php list:routes
```

## 2. Command Reference

### `migrate`

Run all pending database migrations.

```bash
php cli.php migrate
```

Applies every migration in `brain/migrations/` that hasn't been executed yet. Migrations run in filename-sorted order.

### `migrate:fresh`

Drop all tables and re-run every migration from scratch.

```bash
php cli.php migrate:fresh
```

**Warning:** This destroys all data. Use only in development.

### `make:module <name>`

Generate a new module with controllers and models directories.

```bash
php cli.php make:module Blog
```

Creates:
- `brain/controllers/Blog/` — Module controller directory
- `brain/models/Blog/` — Module model directory

### `make:controller <name>`

Generate a new controller file.

```bash
php cli.php make:controller UserController
```

Creates `brain/controllers/UserController.php` with a skeleton class.

### `make:model <name>`

Generate a new model file.

```bash
php cli.php make:model User
```

Creates `brain/models/User.php` with a skeleton class.

### `make:migration <name>`

Generate a new migration file.

```bash
php cli.php make:migration create_users_table
```

Creates a timestamped migration file in `brain/migrations/` (e.g., `20260818120000_create_users_table.php`) with `up()` and `down()` method stubs.

### `cache:clear`

Clear the application cache and flush Redis.

```bash
php cli.php cache:clear
```

Removes all cached values from `CacheService` and resets Redis keys.

### `db:seed`

Seed the database with initial data.

```bash
php cli.php db:seed
```

Runs seeder classes to populate tables with default/fixture data.

### `user:create <username> <password> [role]`

Create a new user account.

```bash
php cli.php user:create admin secret123 admin
php cli.php user:create john password123 viewer
```

- `username` — Required. The login username.
- `password` — Required. Plain text (will be hashed).
- `role` — Optional. Defaults to `viewer`.

### `list:routes`

List all registered routes.

```bash
php cli.php list:routes
```

Displays a table of HTTP methods, URI patterns, and their controller actions.

### `db:backup [file]`

Export the database to a SQL file.

```bash
php cli.php db:backup
php cli.php db:backup backups/my_backup.sql
```

If no filename is given, a default timestamped file is created in the storage directory.

### `db:restore <file>`

Restore the database from a SQL dump file.

```bash
php cli.php db:restore backups/my_backup.sql
```

**Warning:** This overwrites existing data.

## 3. Creating Custom Commands

Custom commands can be added by extending the CLI system. Each command is a class in `brain/classes/Commands/` (or `brain/classes/Utils/`).

### Structure

```php
namespace Commands;

class MyCommand
{
    public function handle(array $args): void
    {
        // Command logic here
        echo "Running my command...\n";

        // Access arguments
        $name = $args[0] ?? 'default';
        echo "Hello, {$name}\n";
    }
}
```

### Registering the command

Add the command to the CLI router in `cli.php` or the CLI bootstrap file:

```php
$command = $argv[1] ?? '';

match ($command) {
    'my:command' => (new \Commands\MyCommand())->handle(array_slice($argv, 2)),
    // ... other commands
};
```

### Running it

```bash
php cli.php my:command world
```

## 4. Migration System

Migrations are PHP files in `brain/migrations/` with timestamped filenames. Each migration has `up()` and `down()` methods.

### Migration file format

```php
<?php

class CreateUserTable_20260818120000
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'viewer',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS users");
    }
}
```

### Workflow

1. **Create:** `php cli.php make:migration create_users_table`
2. **Edit:** Open the generated file and define `up()` / `down()`.
3. **Run:** `php cli.php migrate`
4. **Rollback:** `php cli.php migrate:fresh` (drops all and re-runs)

### Conventions

- Filename: `YYYYMMDDHHMMSS_description.php`
- Class name: `Description_YYYYMMDDHHMMSS`
- Migrations run in filename order (timestamps enforce ordering)
- `up()` creates/modifies tables; `down()` reverses the change

## 5. Module Generator

The `make:module` command scaffolds a new feature module.

### What it creates

```bash
php cli.php make:module Blog
```

```
brain/
├── controllers/
│   └── Blog/          ← Controller directory
│       └── BlogController.php
├── models/
│   └── Blog/          ← Model directory
│       └── Blog.php
```

### Using a module

After generating, register routes for the module:

```php
// brain/routes/web.php
Router::get('/blog', 'Blog/BlogController@index');
Router::get('/blog/{id}', 'Blog/BlogController@show');
```

Create the corresponding controller and model with their own logic, then run `make:controller` and `make:model` within the module directory as needed.
