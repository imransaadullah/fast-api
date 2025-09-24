# Fluent API Guide

The Fluent API provides a clean, method-chaining approach to route definition with middleware in FastAPI. This guide covers all aspects of the Fluent API, from basic usage to advanced patterns.

## 📋 Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Method Chaining](#method-chaining)
- [Middleware Integration](#middleware-integration)
- [Route Naming](#route-naming)
- [Route Constraints](#route-constraints)
- [Advanced Patterns](#advanced-patterns)
- [Integration with Groups](#integration-with-groups)
- [Best Practices](#best-practices)
- [Migration Guide](#migration-guide)

## 🎯 Overview

The Fluent API enables you to define routes with method chaining, making your code more readable and maintainable:

```php
// Instead of nested groups for individual middleware
$app->group(['middleware' => ['auth']], function($app) {
    $app->group(['middleware' => ['role:admin']], function($app) {
        $app->get('/admin/users', 'AdminController@users');
    });
});

// Use fluent chaining
$app->route('GET', '/admin/users', 'AdminController@users')
    ->middleware(['auth', 'role:admin']);
```

### Key Benefits

- ✅ **Clean Syntax**: Method chaining for readable code
- ✅ **Individual Control**: Per-route middleware management
- ✅ **Backward Compatible**: Works alongside existing group-based routes
- ✅ **Flexible**: Supports all middleware types and patterns
- ✅ **Extensible**: Easy to add new fluent methods

## 🚀 Basic Usage

### Creating Routes

#### `route($method, $uri, $handler)`
```php
use FASTAPI\App;

$app = App::getInstance();

// Basic route creation
$app->route('GET', '/users', 'UserController@index');
$app->route('POST', '/users', 'UserController@store');
$app->route('PUT', '/users/{id}', 'UserController@update');
$app->route('DELETE', '/users/{id}', 'UserController@destroy');
```

#### Supported HTTP Methods
```php
$app->route('GET', '/users', 'UserController@index');
$app->route('POST', '/users', 'UserController@store');
$app->route('PUT', '/users/{id}', 'UserController@update');
$app->route('PATCH', '/users/{id}', 'UserController@patch');
$app->route('DELETE', '/users/{id}', 'UserController@destroy');
$app->route('OPTIONS', '/users', 'UserController@options');
```

### Handler Types

#### Controller@Method Syntax
```php
$app->route('GET', '/users', 'UserController@index');
$app->route('POST', '/users', 'UserController@store');
```

#### Closure Handlers
```php
$app->route('GET', '/status', function($request) {
    return (new Response())->setJsonResponse(['status' => 'online']);
});
```

#### Array Handlers
```php
$controller = new UserController();
$app->route('GET', '/users', [$controller, 'index']);
```

## 🔗 Method Chaining

### Middleware Chaining

#### `middleware($middleware)`
```php
// Single middleware
$app->route('GET', '/profile', 'UserController@profile')
    ->middleware('auth');

// Multiple middleware
$app->route('POST', '/admin/users', 'AdminController@createUser')
    ->middleware(['auth', 'role:admin', 'throttle']);

// Mixed middleware types
$app->route('PUT', '/users/{id}', 'UserController@update')
    ->middleware([
        'auth',
        new CustomMiddleware(),
        $rbac->withPermissions('users.update')
    ]);
```

### Route Naming

#### `name($name)`
```php
$app->route('GET', '/users/{id}', 'UserController@show')
    ->middleware(['auth'])
    ->name('users.show');

$app->route('POST', '/users', 'UserController@store')
    ->middleware(['auth', 'role:admin'])
    ->name('users.store');
```

### Route Constraints

#### `where($constraints)`
```php
$app->route('GET', '/users/{id}', 'UserController@show')
    ->middleware(['auth'])
    ->name('users.show')
    ->where(['id' => '\d+']);

$app->route('GET', '/posts/{slug}', 'PostController@show')
    ->where(['slug' => '[a-z0-9-]+']);
```

### Complete Chain Example
```php
$app->route('POST', '/api/upload', 'UploadController@store')
    ->middleware([
        'auth',
        'throttle:10,60',
        $rbac->withPermissions('files.upload')
    ])
    ->name('upload.store')
    ->where(['file' => '\.(jpg|png|pdf)$']);
```

## 🔧 Middleware Integration

### String Aliases
```php
// Register middleware aliases
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);
$app->registerMiddleware('throttle', ThrottleMiddleware::class);

// Use in fluent API
$app->route('GET', '/admin', 'AdminController@index')
    ->middleware(['auth', 'role:admin']);
```

### Parameterized Middleware
```php
$app->route('GET', '/admin/users', 'AdminController@users')
    ->middleware(['auth', 'role:admin']);

$app->route('POST', '/api/upload', 'UploadController@store')
    ->middleware(['auth', 'throttle:10,60']);
```

### Custom Middleware Instances
```php
$rbac = new RBAC();

$app->route('GET', '/claims', 'ClaimsController@index')
    ->middleware($rbac->withPermissions('claims.read'));

$app->route('POST', '/claims/{id}', 'ClaimsController@update')
    ->middleware($rbac->withPermissions('claims.update'));
```

### Mixed Middleware Types
```php
$app->route('POST', '/api/upload', 'UploadController@store')
    ->middleware([
        'auth',                                    // String alias
        'throttle:10,60',                         // Parameterized
        new FileSizeMiddleware(1024 * 1024),      // Direct instance
        $rbac->withPermissions('files.upload')    // Custom instance
    ]);
```

## 🏷 Route Naming

### Purpose
Route naming enables URL generation and reverse routing:

```php
$app->route('GET', '/users/{id}', 'UserController@show')
    ->name('users.show');

$app->route('GET', '/posts/{slug}', 'PostController@show')
    ->name('posts.show');
```

### Naming Conventions
```php
// Resource routes
$app->route('GET', '/users', 'UserController@index')->name('users.index');
$app->route('GET', '/users/create', 'UserController@create')->name('users.create');
$app->route('POST', '/users', 'UserController@store')->name('users.store');
$app->route('GET', '/users/{id}', 'UserController@show')->name('users.show');
$app->route('GET', '/users/{id}/edit', 'UserController@edit')->name('users.edit');
$app->route('PUT', '/users/{id}', 'UserController@update')->name('users.update');
$app->route('DELETE', '/users/{id}', 'UserController@destroy')->name('users.destroy');

// API routes
$app->route('GET', '/api/v1/users', 'Api\UserController@index')->name('api.users.index');
$app->route('POST', '/api/v1/users', 'Api\UserController@store')->name('api.users.store');
```

## 🎯 Route Constraints

### Parameter Constraints
```php
// Numeric ID
$app->route('GET', '/users/{id}', 'UserController@show')
    ->where(['id' => '\d+']);

// Alphanumeric slug
$app->route('GET', '/posts/{slug}', 'PostController@show')
    ->where(['slug' => '[a-z0-9-]+']);

// Multiple constraints
$app->route('GET', '/users/{id}/posts/{slug}', 'UserController@post')
    ->where([
        'id' => '\d+',
        'slug' => '[a-z0-9-]+'
    ]);
```

### File Extension Constraints
```php
$app->route('GET', '/files/{filename}', 'FileController@download')
    ->where(['filename' => '.*\.(jpg|png|pdf|doc)$']);
```

## 🚀 Advanced Patterns

### RBAC Integration
```php
class RBAC {
    public function withPermissions($permissions) {
        return new PermissionMiddleware($permissions);
    }
}

$rbac = new RBAC();

// Your exact desired syntax
$app->group(['prefix' => '/v2/facilities/{facility_id}', 'middleware' => ['auth']], function($app) use ($rbac) {
    $app->route('GET', '/claims', 'ClaimsController@index')
        ->middleware($rbac->withPermissions('claims.read'));
    
    $app->route('POST', '/claims/{id}', 'ClaimsController@update')
        ->middleware($rbac->withPermissions('claims.update'));
});
```

### Conditional Middleware
```php
$middleware = $app->environment('production') ? ['auth', 'throttle'] : ['auth'];

$app->route('GET', '/api/data', 'DataController@index')
    ->middleware($middleware);
```

### Dynamic Route Building
```php
$routes = [
    ['GET', '/users', 'UserController@index', ['auth']],
    ['POST', '/users', 'UserController@store', ['auth', 'role:admin']],
    ['PUT', '/users/{id}', 'UserController@update', ['auth', 'role:admin']],
];

foreach ($routes as [$method, $uri, $handler, $middleware]) {
    $app->route($method, $uri, $handler)
        ->middleware($middleware);
}
```

## 🔄 Integration with Groups

### Hybrid Approach
```php
$app->group(['prefix' => '/api/v1', 'middleware' => ['cors']], function($app) {
    // Group middleware applies to all routes
    
    // Public routes
    $app->route('GET', '/status', 'StatusController@index');
    
    // Protected routes with additional middleware
    $app->route('GET', '/users', 'UserController@index')
        ->middleware(['auth', 'throttle:100,60']);
    
    $app->route('POST', '/users', 'UserController@store')
        ->middleware(['auth', 'role:admin']);
});
```

### Nested Groups with Fluent API
```php
$app->group(['prefix' => '/api/v1'], function($app) {
    $app->group(['middleware' => ['auth']], function($app) {
        $app->route('GET', '/profile', 'UserController@profile')
            ->middleware(['throttle:60,60']);
        
        $app->group(['middleware' => ['role:admin']], function($app) {
            $app->route('GET', '/admin/users', 'AdminController@users')
                ->middleware(['audit:user_access']);
        });
    });
});
```

## 🏆 Best Practices

### 1. Use Fluent API For Individual Routes
```php
// ✅ Good - Use fluent API for route-specific middleware
$app->route('GET', '/admin/users', 'AdminController@users')
    ->middleware(['auth', 'role:admin', 'audit:user_access']);

// ❌ Avoid - Don't create groups for single routes
$app->group(['middleware' => ['auth', 'role:admin', 'audit:user_access']], function($app) {
    $app->get('/admin/users', 'AdminController@users');
});
```

### 2. Use Groups For Common Attributes
```php
// ✅ Good - Use groups for common prefixes and base middleware
$app->group(['prefix' => '/api/v1', 'middleware' => ['auth', 'cors']], function($app) {
    $app->route('GET', '/users', 'UserController@index')
        ->middleware(['throttle:100,60']);
    
    $app->route('POST', '/users', 'UserController@store')
        ->middleware(['role:admin']);
});
```

### 3. Consistent Naming
```php
// ✅ Good - Consistent naming convention
$app->route('GET', '/users', 'UserController@index')->name('users.index');
$app->route('POST', '/users', 'UserController@store')->name('users.store');
$app->route('GET', '/users/{id}', 'UserController@show')->name('users.show');
```

### 4. Middleware Organization
```php
// ✅ Good - Logical middleware order
$app->route('POST', '/api/upload', 'UploadController@store')
    ->middleware([
        'auth',                    // Authentication first
        'throttle:10,60',         // Rate limiting
        'role:admin',             // Authorization
        new FileSizeMiddleware()  // Business logic last
    ]);
```

### 5. Route Constraints
```php
// ✅ Good - Use constraints for validation
$app->route('GET', '/users/{id}', 'UserController@show')
    ->where(['id' => '\d+'])
    ->name('users.show');
```

## 📚 Migration Guide

### From Group-Based to Fluent API

#### Before (Group-Based)
```php
$app->group(['prefix' => '/api/v1', 'middleware' => ['auth']], function($app) {
    $app->group(['middleware' => ['role:admin']], function($app) {
        $app->get('/admin/users', 'AdminController@users');
    });
    
    $app->group(['middleware' => ['throttle']], function($app) {
        $app->get('/public/data', 'DataController@index');
    });
});
```

#### After (Fluent API)
```php
$app->group(['prefix' => '/api/v1', 'middleware' => ['auth']], function($app) {
    $app->route('GET', '/admin/users', 'AdminController@users')
        ->middleware(['role:admin']);
    
    $app->route('GET', '/public/data', 'DataController@index')
        ->middleware(['throttle']);
});
```

### Gradual Migration
```php
// Start with new routes using fluent API
$app->route('GET', '/new-feature', 'NewController@index')
    ->middleware(['auth', 'feature:enabled']);

// Keep existing group-based routes unchanged
$app->group(['middleware' => ['auth']], function($app) {
    $app->get('/existing-route', 'ExistingController@index');
});
```

## 🔍 Troubleshooting

### Common Issues

#### 1. Middleware Not Executing
```php
// ❌ Wrong - Middleware not registered
$app->route('GET', '/users', 'UserController@index')
    ->middleware(['unregistered']);

// ✅ Correct - Register middleware first
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->route('GET', '/users', 'UserController@index')
    ->middleware(['auth']);
```

#### 2. Route Not Building
```php
// ❌ Wrong - Route not built
$builder = $app->route('GET', '/users', 'UserController@index');
// Route not registered

// ✅ Correct - Automatic building
$app->route('GET', '/users', 'UserController@index')
    ->middleware(['auth']); // Automatically built

// ✅ Correct - Explicit building
$builder = $app->route('GET', '/users', 'UserController@index');
$builder->middleware(['auth'])->build();
```

#### 3. Middleware Interface Error
```php
// ❌ Wrong - Class doesn't implement interface
class CustomMiddleware {
    public function handle($request, $next) {
        $next();
    }
}

// ✅ Correct - Implements MiddlewareInterface
class CustomMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        $next();
    }
}
```

## 📖 Related Documentation

- **[Middleware Complete Guide](middleware-complete-guide.md)** - Comprehensive middleware documentation
- **[Route Groups Guide](route-groups.md)** - Group-based route organization
- **[Controller Integration](controller-integration.md)** - Laravel-style controllers
- **[API Reference](api-reference.md)** - Complete API documentation

---

**Next**: [Middleware Complete Guide](middleware-complete-guide.md) → [Route Groups Guide](route-groups.md) → [Controller Integration](controller-integration.md)
