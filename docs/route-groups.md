# Route Groups Guide

Route Groups are a powerful feature in FastAPI that allows you to organize routes with common attributes like prefixes, middleware, and namespaces. This guide covers both App-level and Router-level route grouping.

## 📋 Table of Contents

- [Overview](#overview)
- [App-Level Route Groups](#app-level-route-groups)
- [Router-Level Route Groups](#router-level-route-groups)
- [Group Attributes](#group-attributes)
- [Nested Groups](#nested-groups)
- [Middleware in Groups](#middleware-in-groups)
- [Namespace Management](#namespace-management)
- [Advanced Patterns](#advanced-patterns)
- [Best Practices](#best-practices)

## 🎯 Overview

Route Groups provide:

- **Prefix Management**: Add common URL prefixes to multiple routes
- **Middleware Application**: Apply middleware to groups of routes
- **Namespace Organization**: Organize controllers by namespace
- **Code Organization**: Keep related routes together
- **Inheritance**: Nested groups inherit parent attributes

## 🏗 App-Level Route Groups

### Basic Usage

```php
use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

$app = App::getInstance();

// Basic prefix group
$app->group(['prefix' => 'api/v1'], function($app) {
    $app->get('/users', function($request) {
        return (new Response())->setJsonResponse(['users' => []]);
    });
    
    $app->post('/users', function($request) {
        $data = $request->getData();
        return (new Response())->setJsonResponse(['message' => 'User created']);
    });
});

// Results in:
// GET /api/v1/users
// POST /api/v1/users
```

### Group with Middleware

```php
// Register middleware first
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);

// Group with middleware
$app->group(['middleware' => ['auth']], function($app) {
    $app->get('/profile', function($request) {
        return (new Response())->setJsonResponse(['profile' => 'User data']);
    });
    
    $app->put('/profile', function($request) {
        $data = $request->getData();
        return (new Response())->setJsonResponse(['message' => 'Profile updated']);
    });
});

// Results in:
// GET /profile (with auth middleware)
// PUT /profile (with auth middleware)
```

### Multiple Attributes

```php
$app->group([
    'prefix' => 'admin',
    'middleware' => ['auth', 'role:admin']
], function($app) {
    $app->get('/dashboard', function($request) {
        return (new Response())->setJsonResponse(['dashboard' => 'Admin data']);
    });
    
    $app->get('/users', function($request) {
        return (new Response())->setJsonResponse(['users' => 'All users']);
    });
});

// Results in:
// GET /admin/dashboard (with auth + role:admin middleware)
// GET /admin/users (with auth + role:admin middleware)
```

## 🔧 Router-Level Route Groups

### Basic Usage

```php
use FASTAPI\Router;
use FASTAPI\Request;
use FASTAPI\Response;

$router = new Router();

// Basic prefix group
$router->group(['prefix' => 'api/v2'], function($router) {
    $router->get('/products', function($request) {
        return (new Response())->setJsonResponse(['products' => []]);
    });
    
    $router->post('/products', function($request) {
        $data = $request->getData();
        return (new Response())->setJsonResponse(['message' => 'Product created']);
    });
});

// Results in:
// GET /api/v2/products
// POST /api/v2/products
```

### Group with Controller Namespaces

```php
// Set controller namespaces
$router->setControllerNamespaces(['App\\Controllers\\']);

// Group with namespace
$router->group([
    'prefix' => 'api/v2',
    'namespace' => 'App\\Controllers'
], function($router) {
    $router->get('/products', 'ProductController@index');
    $router->post('/products', 'ProductController@store');
    $router->get('/products/:id', 'ProductController@show');
    $router->put('/products/:id', 'ProductController@update');
    $router->delete('/products/:id', 'ProductController@destroy');
});

// Results in:
// GET /api/v2/products → App\Controllers\ProductController@index
// POST /api/v2/products → App\Controllers\ProductController@store
// etc.
```

## 📋 Group Attributes

### Available Attributes

| Attribute | Type | Description | Example |
|-----------|------|-------------|---------|
| `prefix` | string | URL prefix for all routes | `'api/v1'` |
| `middleware` | array | Middleware to apply | `['auth', 'role:admin']` |
| `namespace` | string | Controller namespace | `'App\\Controllers'` |

### Prefix Attribute

```php
// Simple prefix
$app->group(['prefix' => 'api'], function($app) {
    $app->get('/status', function() {
        return (new Response())->setJsonResponse(['status' => 'online']);
    });
});

// Nested prefixes
$app->group(['prefix' => 'api/v1'], function($app) {
    $app->group(['prefix' => 'users'], function($app) {
        $app->get('/', function() {
            return (new Response())->setJsonResponse(['users' => []]);
        });
    });
});

// Results in: GET /api/v1/users
```

### Middleware Attribute

```php
// Single middleware
$app->group(['middleware' => ['auth']], function($app) {
    $app->get('/protected', function() {
        return (new Response())->setJsonResponse(['data' => 'Protected']);
    });
});

// Multiple middleware
$app->group(['middleware' => ['auth', 'throttle']], function($app) {
    $app->get('/api/data', function() {
        return (new Response())->setJsonResponse(['data' => 'Protected and throttled']);
    });
});

// Parameterized middleware
$app->group(['middleware' => ['role:admin']], function($app) {
    $app->get('/admin/users', function() {
        return (new Response())->setJsonResponse(['users' => 'Admin only']);
    });
});
```

### Namespace Attribute

```php
// Set controller namespaces
$router->setControllerNamespaces([
    'App\\Controllers\\',
    'App\\Admin\\Controllers\\'
]);

// Group with namespace
$router->group(['namespace' => 'App\\Controllers'], function($router) {
    $router->get('/users', 'UserController@index');
    $router->post('/users', 'UserController@store');
});

// Different namespace
$router->group(['namespace' => 'App\\Admin\\Controllers'], function($router) {
    $router->get('/admin/dashboard', 'DashboardController@index');
    $router->get('/admin/users', 'UserController@index');
});
```

## 🔄 Nested Groups

### Basic Nesting

```php
$app->group(['prefix' => 'api/v1'], function($app) {
    // Public routes
    $app->get('/status', function() {
        return (new Response())->setJsonResponse(['status' => 'online']);
    });
    
    // Protected routes
    $app->group(['middleware' => ['auth']], function($app) {
        $app->get('/profile', function() {
            return (new Response())->setJsonResponse(['profile' => 'User data']);
        });
        
        // Admin routes
        $app->group(['middleware' => ['role:admin']], function($app) {
            $app->get('/admin/users', function() {
                return (new Response())->setJsonResponse(['users' => 'All users']);
            });
        });
    });
});

// Results in:
// GET /api/v1/status (no middleware)
// GET /api/v1/profile (auth middleware)
// GET /api/v1/admin/users (auth + role:admin middleware)
```

### Complex Nesting

```php
$app->group(['prefix' => 'api/v1'], function($app) {
    // Public API routes
    $app->group(['prefix' => 'public'], function($app) {
        $app->get('/status', 'StatusController@index');
        $app->get('/products', 'ProductController@index');
    });
    
    // User routes
    $app->group(['prefix' => 'user', 'middleware' => ['auth']], function($app) {
        $app->get('/profile', 'UserController@profile');
        $app->put('/profile', 'UserController@updateProfile');
        
        // User orders
        $app->group(['prefix' => 'orders'], function($app) {
            $app->get('/', 'OrderController@index');
            $app->post('/', 'OrderController@store');
            $app->get('/:id', 'OrderController@show');
        });
    });
    
    // Admin routes
    $app->group(['prefix' => 'admin', 'middleware' => ['auth', 'role:admin']], function($app) {
        $app->get('/dashboard', 'AdminController@dashboard');
        
        // Admin user management
        $app->group(['prefix' => 'users'], function($app) {
            $app->get('/', 'AdminController@users');
            $app->post('/', 'AdminController@createUser');
            $app->get('/:id', 'AdminController@showUser');
            $app->put('/:id', 'AdminController@updateUser');
            $app->delete('/:id', 'AdminController@deleteUser');
        });
        
        // Admin product management
        $app->group(['prefix' => 'products'], function($app) {
            $app->get('/', 'AdminController@products');
            $app->post('/', 'AdminController@createProduct');
            $app->get('/:id', 'AdminController@showProduct');
            $app->put('/:id', 'AdminController@updateProduct');
            $app->delete('/:id', 'AdminController@deleteProduct');
        });
    });
});
```

## 🔧 Middleware in Groups

### Middleware Registration

```php
// Register middleware first
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);
$app->registerMiddleware('throttle', ThrottleMiddleware::class);
$app->registerMiddleware('cors', CorsMiddleware::class);
```

### Middleware Application

```php
// Global middleware (applies to all routes)
$app->addMiddleware(new CorsMiddleware());

// Group-specific middleware
$app->group(['middleware' => ['auth']], function($app) {
    // All routes in this group require authentication
    $app->get('/profile', 'UserController@profile');
    $app->put('/profile', 'UserController@updateProfile');
});

// Multiple middleware
$app->group(['middleware' => ['auth', 'throttle']], function($app) {
    // Routes with authentication and rate limiting
    $app->get('/api/data', 'DataController@index');
    $app->post('/api/data', 'DataController@store');
});

// Parameterized middleware
$app->group(['middleware' => ['role:admin']], function($app) {
    // Admin-only routes
    $app->get('/admin/users', 'AdminController@users');
    $app->post('/admin/users', 'AdminController@createUser');
});
```

### Middleware Execution Order

```php
// Middleware executes in order: global → group → route-specific
$app->addMiddleware(new CorsMiddleware());        // 1. Global
$app->addMiddleware(new LoggingMiddleware());     // 2. Global

$app->group(['middleware' => ['auth']], function($app) {
    // 3. Group middleware (auth)
    $app->get('/profile', 'UserController@profile');
    
    $app->group(['middleware' => ['role:admin']], function($app) {
        // 4. Nested group middleware (role:admin)
        $app->get('/admin/users', 'AdminController@users');
    });
});
```

## 🏷 Namespace Management

### Controller Namespaces

```php
// Set multiple namespaces
$router->setControllerNamespaces([
    'App\\Controllers\\',
    'App\\Admin\\Controllers\\',
    'App\\Api\\Controllers\\'
]);

// Group with specific namespace
$router->group(['namespace' => 'App\\Controllers'], function($router) {
    $router->get('/users', 'UserController@index');
    $router->post('/users', 'UserController@store');
    $router->get('/users/:id', 'UserController@show');
});

// Different namespace for admin
$router->group(['namespace' => 'App\\Admin\\Controllers'], function($router) {
    $router->get('/admin/dashboard', 'DashboardController@index');
    $router->get('/admin/users', 'UserController@index');
});
```

### Controller Class Example

```php
<?php
namespace App\Controllers;

use FASTAPI\Request;
use FASTAPI\Response;

class UserController
{
    public function index(Request $request)
    {
        return (new Response())->setJsonResponse([
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ]
        ]);
    }
    
    public function store(Request $request)
    {
        $data = $request->getData();
        
        // Validation
        if (empty($data['name']) || empty($data['email'])) {
            return (new Response())->setJsonResponse([
                'error' => 'Name and email are required'
            ], 400);
        }
        
        // Create user logic
        $user = [
            'id' => uniqid(),
            'name' => $data['name'],
            'email' => $data['email']
        ];
        
        return (new Response())->setJsonResponse([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }
    
    public function show(Request $request, $id)
    {
        // Get user logic
        $user = [
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        return (new Response())->setJsonResponse(['user' => $user]);
    }
}
```

## 🚀 Advanced Patterns

### API Versioning

```php
// API v1
$app->group(['prefix' => 'api/v1'], function($app) {
    $app->get('/users', 'UserController@index');
    $app->post('/users', 'UserController@store');
});

// API v2 (with different structure)
$app->group(['prefix' => 'api/v2'], function($app) {
    $app->group(['prefix' => 'users'], function($app) {
        $app->get('/', 'UserController@index');
        $app->post('/', 'UserController@store');
        $app->get('/:id', 'UserController@show');
        $app->put('/:id', 'UserController@update');
        $app->delete('/:id', 'UserController@destroy');
    });
});
```

### Multi-Tenant Applications

```php
// Tenant-specific routes
$app->group(['prefix' => 'tenant/:tenantId'], function($app) {
    $app->get('/dashboard', 'TenantController@dashboard');
    
    $app->group(['middleware' => ['auth']], function($app) {
        $app->get('/users', 'TenantController@users');
        $app->post('/users', 'TenantController@createUser');
    });
});
```

### Modular Applications

```php
// User module
$app->group(['prefix' => 'users', 'namespace' => 'App\\Modules\\Users\\Controllers'], function($app) {
    $app->get('/', 'UserController@index');
    $app->post('/', 'UserController@store');
    $app->get('/:id', 'UserController@show');
    $app->put('/:id', 'UserController@update');
    $app->delete('/:id', 'UserController@destroy');
});

// Product module
$app->group(['prefix' => 'products', 'namespace' => 'App\\Modules\\Products\\Controllers'], function($app) {
    $app->get('/', 'ProductController@index');
    $app->post('/', 'ProductController@store');
    $app->get('/:id', 'ProductController@show');
    $app->put('/:id', 'ProductController@update');
    $app->delete('/:id', 'ProductController@destroy');
});
```

### Conditional Groups

```php
// Development routes
if ($_ENV['APP_ENV'] === 'development') {
    $app->group(['prefix' => 'dev'], function($app) {
        $app->get('/debug', 'DevController@debug');
        $app->get('/logs', 'DevController@logs');
    });
}

// Admin routes (only if user is admin)
$app->group(['prefix' => 'admin', 'middleware' => ['auth', 'role:admin']], function($app) {
    $app->get('/dashboard', 'AdminController@dashboard');
    $app->get('/users', 'AdminController@users');
});
```

## 🎯 Complete Example

```php
<?php
require_once 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

$app = App::getInstance();

// Configure application
$app->setControllerNamespaces(['App\\Controllers\\']);

// Register middleware
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);
$app->registerMiddleware('throttle', ThrottleMiddleware::class);

// Global middleware
$app->addMiddleware(function($request, $next) {
    // CORS headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($request->getMethod() === 'OPTIONS') {
        http_response_code(200);
        return;
    }
    
    $next();
});

// Public routes
$app->get('/', function() {
    return (new Response())->setJsonResponse([
        'message' => 'Welcome to FastAPI',
        'version' => '2.2.4'
    ]);
});

$app->post('/auth/login', function($request) {
    $data = $request->getData();
    // Authentication logic
    return (new Response())->setJsonResponse(['token' => 'jwt_token']);
});

// API routes
$app->group(['prefix' => 'api/v1'], function($app) {
    
    // Public API routes
    $app->get('/status', function() {
        return (new Response())->setJsonResponse(['status' => 'online']);
    });
    
    $app->get('/products', 'ProductController@index');
    $app->get('/products/:id', 'ProductController@show');
    
    // Protected routes
    $app->group(['middleware' => ['auth', 'throttle']], function($app) {
        
        // User routes
        $app->group(['prefix' => 'user'], function($app) {
            $app->get('/profile', 'UserController@profile');
            $app->put('/profile', 'UserController@updateProfile');
            
            // User orders
            $app->group(['prefix' => 'orders'], function($app) {
                $app->get('/', 'OrderController@index');
                $app->post('/', 'OrderController@store');
                $app->get('/:id', 'OrderController@show');
                $app->put('/:id', 'OrderController@update');
            });
        });
        
        // Admin routes
        $app->group(['prefix' => 'admin', 'middleware' => ['role:admin']], function($app) {
            $app->get('/dashboard', 'AdminController@dashboard');
            
            // Admin user management
            $app->group(['prefix' => 'users'], function($app) {
                $app->get('/', 'AdminController@users');
                $app->post('/', 'AdminController@createUser');
                $app->get('/:id', 'AdminController@showUser');
                $app->put('/:id', 'AdminController@updateUser');
                $app->delete('/:id', 'AdminController@deleteUser');
            });
            
            // Admin product management
            $app->group(['prefix' => 'products'], function($app) {
                $app->get('/', 'AdminController@products');
                $app->post('/', 'AdminController@createProduct');
                $app->get('/:id', 'AdminController@showProduct');
                $app->put('/:id', 'AdminController@updateProduct');
                $app->delete('/:id', 'AdminController@deleteProduct');
            });
        });
    });
});

// Custom 404 handler
$app->setNotFoundHandler(function($request) {
    return (new Response())->setJsonResponse([
        'error' => 'Route not found',
        'path' => $request->getUri()
    ], 404);
});

// Start the application
$app->run();
```

## 🏆 Best Practices

### 1. Organization

```php
// ✅ Good - Organize by feature
$app->group(['prefix' => 'api/v1'], function($app) {
    $app->group(['prefix' => 'users'], function($app) {
        $app->get('/', 'UserController@index');
        $app->post('/', 'UserController@store');
        $app->get('/:id', 'UserController@show');
        $app->put('/:id', 'UserController@update');
        $app->delete('/:id', 'UserController@destroy');
    });
    
    $app->group(['prefix' => 'products'], function($app) {
        $app->get('/', 'ProductController@index');
        $app->post('/', 'ProductController@store');
        $app->get('/:id', 'ProductController@show');
        $app->put('/:id', 'ProductController@update');
        $app->delete('/:id', 'ProductController@destroy');
    });
});
```

### 2. Middleware Management

```php
// ✅ Good - Register middleware once, use everywhere
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);

// Use consistently
$app->group(['middleware' => ['auth']], function($app) {
    // Protected routes
});

$app->group(['middleware' => ['auth', 'role:admin']], function($app) {
    // Admin routes
});
```

### 3. Namespace Organization

```php
// ✅ Good - Use namespaces for organization
$router->setControllerNamespaces([
    'App\\Controllers\\',
    'App\\Admin\\Controllers\\'
]);

// Group by namespace
$router->group(['namespace' => 'App\\Controllers'], function($router) {
    $router->get('/users', 'UserController@index');
});

$router->group(['namespace' => 'App\\Admin\\Controllers'], function($router) {
    $router->get('/admin/dashboard', 'DashboardController@index');
});
```

### 4. Prefix Management

```php
// ✅ Good - Use meaningful prefixes
$app->group(['prefix' => 'api/v1'], function($app) {
    // API version 1
});

$app->group(['prefix' => 'api/v2'], function($app) {
    // API version 2
});

// ✅ Good - Use feature-based prefixes
$app->group(['prefix' => 'admin'], function($app) {
    // Admin routes
});

$app->group(['prefix' => 'user'], function($app) {
    // User routes
});
```

### 5. Error Handling

```php
// ✅ Good - Set custom error handlers
$app->setNotFoundHandler(function($request) {
    return (new Response())->setJsonResponse([
        'error' => 'Route not found',
        'path' => $request->getUri()
    ], 404);
});
```

## 🔍 Troubleshooting

### Common Issues

1. **Routes not working**
   ```php
   // Check if middleware is registered before use
   $app->registerMiddleware('auth', AuthMiddleware::class);
   
   // Then use in groups
   $app->group(['middleware' => ['auth']], function($app) {
       // Routes here
   });
   ```

2. **Prefix not working**
   ```php
   // Ensure prefix is a string
   $app->group(['prefix' => 'api/v1'], function($app) {
       // Routes here
   });
   ```

3. **Namespace not resolving**
   ```php
   // Set controller namespaces
   $router->setControllerNamespaces(['App\\Controllers\\']);
   
   // Use in groups
   $router->group(['namespace' => 'App\\Controllers'], function($router) {
       $router->get('/users', 'UserController@index');
   });
   ```

## 📖 Related Documentation

- **[App Class Guide](app-class.md)** - Application lifecycle management
- **[Router Class Guide](router-class.md)** - Advanced routing capabilities
- **[Middleware Guide](middleware-complete-guide.md)** - Middleware system
- **[Controller Integration](controller-integration.md)** - Laravel-style controllers
- **[Complete API Reference](api-reference.md)** - All available methods

---

**Next**: [Middleware Guide](middleware-complete-guide.md) → [Controller Integration](controller-integration.md) → [Error Handling Guide](error-handling.md) 