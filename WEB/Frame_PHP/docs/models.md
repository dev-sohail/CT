# Models

## BaseModel Usage

`BaseModel` lives in `backend/BaseModel.php` with namespace `Models`. Extend it for raw query access.

```php
namespace Models;

use Models\BaseModel;

class UserRepository extends BaseModel
{
    public function findByEmail($email)
    {
        return $this->queryOne("SELECT * FROM users WHERE email = ?", [$email]);
    }
}
```

### Available Methods

| Method | Returns | Description |
|---|---|---|
| `queryAll($sql, $params)` | array | Fetch all rows |
| `queryOne($sql, $params)` | ?array | Fetch single row |
| `queryValue($sql, $params)` | mixed | Fetch single scalar value |
| `execute($sql, $params)` | bool | Execute statement (INSERT/UPDATE/DELETE) |
| `insertGetId($sql, $params)` | int | Insert and return last insert ID |

All queries are auto-logged via `QueryLogger`.

## Model Active Record

Extend `Models\Model` for an Active Record pattern.

```php
namespace Models;

use Models\Model;

class User extends Model
{
    protected static $table = 'users';
    protected static $primaryKey = 'id';
}
```

### Static Methods

| Method | Description |
|---|---|
| `User::find($id)` | Fetch row by primary key |
| `User::where($conditions)` | Fetch matching rows |
| `User::all()` | Fetch all rows |
| `User::create($data)` | Insert and return model instance |

### Instance Methods

| Method | Description |
|---|---|
| `$model->update($data)` | Update row |
| `$model->delete()` | Delete row |
| `$model->toArray()` | Convert to array |
| `$model->get($key)` | Get attribute |
| `$model->set($key, $value)` | Set attribute |

## CRUD Operations

```php
// Create
$user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);

// Read
$user = User::find(1);
$admins = User::where(['role' => 'admin']);
$all = User::all();

// Update
$user->update(['name' => 'Bob']);

// Delete
$user->delete();
```

## Query Builder Patterns

```php
// Chain conditions
$posts = Post::where(['status' => 'published', 'author_id' => 5]);

// Use BaseModel for complex queries
class StatsRepository extends BaseModel
{
    public function countByRole()
    {
        return $this->queryAll(
            "SELECT role, COUNT(*) as total FROM users GROUP BY role"
        );
    }

    public function totalUsers()
    {
        return $this->queryValue("SELECT COUNT(*) FROM users");
    }

    public function createPost($title, $body)
    {
        return $this->insertGetId(
            "INSERT INTO posts (title, body, created_at) VALUES (?, ?, NOW())",
            [$title, $body]
        );
    }
}
```

## Module Model Convention

Place models in `backend/module/{modulename}/models/` with namespace `Models\Module\{ModuleName}`.

```php
// backend/module/blog/models/PostModel.php
namespace Models\Module\Blog;

use Models\Model;

class PostModel extends Model
{
    protected static $table = 'posts';
    protected static $primaryKey = 'id';
}
```

## Example Model

```php
namespace Models\Module\Blog;

use Models\Model;

class PostModel extends Model
{
    protected static $table = 'posts';
    protected static $primaryKey = 'id';

    public function published()
    {
        return $this->where(['status' => 'published']);
    }

    public function author()
    {
        return User::find($this->get('author_id'));
    }
}
```

```php
// Usage in controller
$posts = PostModel::where(['status' => 'published']);
$post = PostModel::find(1);
$post->update(['title' => 'Updated']);
$post->delete();
```
