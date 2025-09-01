# Router Class Guide

The Router class is the heart of FastAPI's routing system, providing advanced route registration, middleware management, and request dispatching capabilities.

## 📋 Table of Contents

- [Overview](#overview)
- [Basic Routing](#basic-routing)
- [Route Parameters](#route-parameters)
- [Route Groups](#route-groups)
- [Middleware System](#middleware-system)
- [Controller Integration](#controller-integration)
- [Advanced Features](#advanced-features)
- [Fluent API](#fluent-api)
- [Request Dispatching](#request-dispatching)
- [Best Practices](#best-practices)

## 🎯 Overview

The Router class provides:

- **Route Registration**: HTTP method-based route handling
- **Parameter Extraction**: Dynamic route parameters
- **Route Groups**: Organized route structures with inheritance
- **Middleware Management**: Route-specific middleware with aliasing
- **Controller Resolution**: Laravel-style controller syntax
- **Request Dispatching**: Efficient route matching and execution

## 🛠 Basic Routing

### HTTP Method Routes

```php
use FASTAPI\Router;
use FASTAPI\Request;
use FASTAPI\Response;

$router = new Router();

// GET routes
$router->get('/users', function($request) {
    return (new Response())->setJsonResponse(['users' => []]);
});

// POST routes
$router->post('/users', function($request) {
    $data = $request->getData();
    return (new Response())->setJsonResponse(['message' => 'User created']);
});

// PUT routes
$router->put('/users/:id', function($request, $id) {
    $data = $request->getData();
    return (new Response())->setJsonResponse(['message' => 'User updated']);
});

// DELETE routes
$router->delete('/users/:id', function($request, $id) {
    return (new Response())->setJsonResponse(['message' => 'User deleted']);
});

// PATCH routes
$router->patch('/users/:id', function($request, $id) {
    $data = $request->getData();
    return (new Response())->setJsonResponse(['message' => 'User partially updated']);
});
```

### Generic Route Registration

```php
// Using addRoute method
$router->addRoute('GET', '/users', function($request) {
    return (new Response())->setJsonResponse(['users' => []]);
});

$router->addRoute('POST', '/users', function($request) {
    $data = $request->getData();
    return (new Response())->setJsonResponse(['message' => 'User created']);
});

// Multiple routes at once
$routes = [
    ['GET', '/users', $userIndexHandler],
    ['POST', '/users', $userCreateHandler],
    ['GET', '/users/:id', $userShowHandler],
    ['PUT', '/users/:id', $userUpdateHandler],
    ['DELETE', '/users/:id', $userDeleteHandler]
];

foreach ($routes as [$method, $uri, $handler]) {
    $router->addRoute($method, $uri, $handler);
}
```

## 🔗 Route Parameters

### Named Parameters

```php
// Single parameter
$router->get('/users/:id', function($request, $id) {
    return (new Response())->setJsonResponse(['user_id' => $id]);
});

// Multiple parameters
$router->get('/posts/:postId/comments/:commentId', function($request, $postId, $commentId) {
    return (new Response())->setJsonResponse([
        'post_id' => $postId,
        'comment_id' => $commentId
    ]);
});

// Mixed parameter formats
$router->get('/users/:id/posts/{postId}', function($request, $id, $postId) {
    return (new Response())->setJsonResponse([
        'user_id' => $id,
        'post_id' => $postId
    ]);
});
```

### Parameter Validation

```php
// Numeric parameter validation
$router->get('/users/:id', function($request, $id) {
    if (!is_numeric($id)) {
        return (new Response())->setJsonResponse(['error' => 'Invalid user ID'], 400);
    }
    
    return (new Response())->setJsonResponse(['user_id' => (int)$id]);
});

// UUID parameter validation
$router->get('/posts/:uuid', function($request, $uuid) {
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
        return (new Response())->setJsonResponse(['error' => 'Invalid UUID'], 400);
    }
    
    return (new Response())->setJsonResponse(['post_uuid' => $uuid]);
});
```

## 🏗 Route Groups

### Basic Groups

```php
// Prefix groups
$router->group(['prefix' => 'api/v1'], function($router) {
    $router->get('/users', function($request) {
        return (new Response())->setJsonResponse(['users' => []]);
    });
    
    $router->post('/users', function($request) {
        $data = $request->getData();
        return (new Response())->setJsonResponse(['message' => 'User created']);
    });
});

// Results in:
// GET /api/v1/users
// POST /api/v1/users
```

### Groups with Middleware

```php
// Register middleware
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('role', RoleMiddleware::class);

// Group with middleware
$router->group(['middleware' => ['auth']], function($router) {
    $router->get('/profile', function($request) {
        return (new Response())->setJsonResponse(['profile' => 'User data']);
    });
    
    $router->put('/profile', function($request) {
        $data = $request->getData();
        return (new Response())->setJsonResponse(['message' => 'Profile updated']);
    });
});

// Results in:
// GET /profile (with auth middleware)
// PUT /profile (with auth middleware)
```

### Nested Groups

```php
// Nested groups with inheritance
$router->group(['prefix' => 'admin', 'middleware' => ['auth']], function($router) {
    $router->get('/dashboard', function($request) {
        return (new Response())->setJsonResponse(['dashboard' => 'Admin data']);
    });
    
    // Nested group inherits prefix and middleware
    $router->group(['middleware' => ['role:admin']], function($router) {
        $router->get('/users', function($request) {
            return (new Response())->setJsonResponse(['users' => 'All users']);
        });
        
        $router->post('/users', function($request) {
            $data = $request->getData();
            return (new Response())->setJsonResponse(['message' => 'User created']);
        });
    });
});

// Results in:
// GET /admin/dashboard (with auth middleware)
// GET /admin/users (with auth + role:admin middleware)
// POST /admin/users (with auth + role:admin middleware)
```

### Groups with Namespaces

```php
// Set controller namespaces
$router->setControllerNamespaces(['App\\Controllers\\']);

// Group with namespace
$router->group(['prefix' => 'api/v2', 'namespace' => 'App\\Controllers'], function($router) {
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

## 🔧 Middleware System

### Middleware Registration

```php
// Register middleware with alias
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('role', RoleMiddleware::class);
$router->registerMiddleware('throttle', ThrottleMiddleware::class);

// Register with closure factory
$router->registerMiddleware('custom', function() {
    return new CustomMiddleware();
});

// Register parameterized middleware
$router->registerMiddleware('permission', PermissionMiddleware::class);
```

### Auto-Resolution

```php
// These work without explicit registration
$router->group(['middleware' => ['auth']], function($router) {
    // Automatically resolves to App\Middleware\AuthMiddleware
});

$router->group(['middleware' => ['cors']], function($router) {
    // Automatically resolves to App\Middleware\CorsMiddleware
});

// Auto-resolved middleware:
// - auth → App\Middleware\AuthMiddleware
// - guest → App\Middleware\GuestMiddleware
// - cors → App\Middleware\CorsMiddleware
// - throttle → App\Middleware\ThrottleMiddleware
```

### Parameterized Middleware

```php
// Register parameterized middleware
$router->registerMiddleware('role', RoleMiddleware::class);

// Use with parameters
$router->group(['middleware' => ['role:admin']], function($router) {
    // RoleMiddleware will receive 'admin' as parameter
});

$router->group(['middleware' => ['role:user']], function($router) {
    // RoleMiddleware will receive 'user' as parameter
});

$router->group(['middleware' => ['role:moderator']], function($router) {
    // RoleMiddleware will receive 'moderator' as parameter
});
```

## 🎮 Controller Integration

### Laravel-Style Syntax

```php
// Set controller namespaces
$router->setControllerNamespaces([
    'App\\Controllers\\',
    'App\\Admin\\Controllers\\'
]);

// Controller@method syntax
$router->get('/users', 'UserController@index');
$router->post('/users', 'UserController@store');
$router->get('/users/:id', 'UserController@show');
$router->put('/users/:id', 'UserController@update');
$router->delete('/users/:id', 'UserController@destroy');

// Admin controllers
$router->get('/admin/dashboard', 'AdminController@dashboard');
$router->get('/admin/users', 'AdminController@users');
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
    
    public function update(Request $request, $id)
    {
        $data = $request->getData();
        
        // Update user logic
        $user = [
            'id' => $id,
            'name' => $data['name'] ?? 'John Doe',
            'email' => $data['email'] ?? 'john@example.com'
        ];
        
        return (new Response())->setJsonResponse([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }
    
    public function destroy(Request $request, $id)
    {
        // Delete user logic
        
        return (new Response())->setJsonResponse([
            'message' => 'User deleted successfully'
        ]);
    }
}
```

## 🚀 Advanced Features

### Fluent API

```php
// Fluent route registration
$router->prefix('api/v1')
       ->middleware(['auth'])
       ->group([], function($router) {
           $router->get('/protected', function($request) {
               return (new Response())->setJsonResponse(['data' => 'Protected']);
           });
       });

// Fluent middleware registration
$router->registerMiddleware('auth', AuthMiddleware::class)
       ->registerMiddleware('role', RoleMiddleware::class)
       ->registerMiddleware('throttle', ThrottleMiddleware::class);
```

### Route Information

```php
// Get all routes
$routes = $router->getRoutes();
print_r($routes);

// Get compiled routes (with group information)
$compiledRoutes = $router->getCompiledRoutes();
print_r($compiledRoutes);
```

### Custom Route Patterns

```php
// Custom parameter patterns
$router->get('/users/:id(\d+)', function($request, $id) {
    // Only matches numeric IDs
    return (new Response())->setJsonResponse(['user_id' => (int)$id]);
});

$router->get('/posts/:slug([a-z0-9-]+)', function($request, $slug) {
    // Only matches alphanumeric slugs with hyphens
    return (new Response())->setJsonResponse(['post_slug' => $slug]);
});
```

## 🔄 Request Dispatching

### Manual Dispatching

```php
// Create a request
$request = new Request('GET', '/users/123', []);

// Dispatch the request
$dispatched = $router->dispatch($request);

if ($dispatched) {
    echo "Route found and executed";
} else {
    echo "No route found";
}
```

### Route Matching

```php
// The router matches routes in the following order:
// 1. Exact matches (no parameters)
// 2. Parameterized routes
// 3. Wildcard routes (if any)

// Example matching:
// Request: GET /users/123
// Routes:
// 1. GET /users (exact match for /users, but different method)
// 2. GET /users/:id (parameterized match - wins!)
// 3. GET /users/* (wildcard match, not used)
```

## 🏆 Best Practices

### 1. Route Organization

```php
// ✅ Good - Organize routes logically
$router->group(['prefix' => 'api/v1'], function($router) {
    
    // Public routes
    $router->get('/status', 'StatusController@index');
    $router->post('/auth/login', 'AuthController@login');
    
    // Protected routes
    $router->group(['middleware' => ['auth']], function($router) {
        
        // User routes
        $router->group(['prefix' => 'users'], function($router) {
            $router->get('/', 'UserController@index');
            $router->post('/', 'UserController@store');
            $router->get('/:id', 'UserController@show');
            $router->put('/:id', 'UserController@update');
            $router->delete('/:id', 'UserController@destroy');
        });
        
        // Admin routes
        $router->group(['prefix' => 'admin', 'middleware' => ['role:admin']], function($router) {
            $router->get('/dashboard', 'AdminController@dashboard');
            $router->get('/users', 'AdminController@users');
        });
    });
});
```

### 2. Middleware Management

```php
// ✅ Good - Register middleware once, use everywhere
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('role', RoleMiddleware::class);
$router->registerMiddleware('throttle', ThrottleMiddleware::class);

// Use consistently
$router->group(['middleware' => ['auth', 'throttle']], function($router) {
    // Protected routes with rate limiting
});

$router->group(['middleware' => ['auth', 'role:admin']], function($router) {
    // Admin-only routes
});
```

### 3. Controller Organization

```php
// ✅ Good - Use namespaces for organization
$router->setControllerNamespaces([
    'App\\Controllers\\',
    'App\\Admin\\Controllers\\',
    'App\\Api\\Controllers\\'
]);

// Group by namespace
$router->group(['namespace' => 'App\\Controllers'], function($router) {
    $router->get('/users', 'UserController@index');
});

$router->group(['namespace' => 'App\\Admin\\Controllers'], function($router) {
    $router->get('/admin/dashboard', 'DashboardController@index');
});
```

### 4. Parameter Validation

```php
// ✅ Good - Validate parameters in routes
$router->get('/users/:id(\d+)', function($request, $id) {
    $userId = (int)$id;
    
    if ($userId <= 0) {
        return (new Response())->setJsonResponse(['error' => 'Invalid user ID'], 400);
    }
    
    // Process request
    return (new Response())->setJsonResponse(['user_id' => $userId]);
});
```

## 🎯 Complete Example

```php
<?php
require_once 'vendor/autoload.php';

use FASTAPI\Router;
use FASTAPI\Request;
use FASTAPI\Response;

// Create router
$router = new Router();

// Set controller namespaces
$router->setControllerNamespaces(['App\\Controllers\\']);

// Register middleware
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('role', RoleMiddleware::class);
$router->registerMiddleware('throttle', ThrottleMiddleware::class);

// API routes
$router->group(['prefix' => 'api/v1'], function($router) {
    
    // Public routes
    $router->get('/status', function() {
        return (new Response())->setJsonResponse(['status' => 'online']);
    });
    
    $router->post('/auth/login', function($request) {
        $data = $request->getData();
        // Authentication logic
        return (new Response())->setJsonResponse(['token' => 'jwt_token']);
    });
    
    // Protected routes
    $router->group(['middleware' => ['auth', 'throttle']], function($router) {
        
        // User routes
        $router->group(['prefix' => 'users'], function($router) {
            $router->get('/', 'UserController@index');
            $router->post('/', 'UserController@store');
            $router->get('/:id(\d+)', 'UserController@show');
            $router->put('/:id(\d+)', 'UserController@update');
            $router->delete('/:id(\d+)', 'UserController@destroy');
        });
        
        // Admin routes
        $router->group(['prefix' => 'admin', 'middleware' => ['role:admin']], function($router) {
            $router->get('/dashboard', 'AdminController@dashboard');
            $router->get('/users', 'AdminController@users');
            $router->post('/users', 'AdminController@createUser');
        });
    });
});

// Handle request
$request = new Request($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_POST);
$router->dispatch($request);
```

## 🔍 Troubleshooting

### Common Issues

1. **Routes not matching**
   ```php
   // Check route registration
   $routes = $router->getRoutes();
   print_r($routes);
   
   // Check request details
   echo "Method: " . $request->getMethod() . "\n";
   echo "URI: " . $request->getUri() . "\n";
   ```

2. **Middleware not executing**
   ```php
   // Ensure middleware is registered before use
   $router->registerMiddleware('auth', AuthMiddleware::class);
   
   // Check middleware class implements interface
   class AuthMiddleware implements MiddlewareInterface {
       public function handle(Request $request, \Closure $next): void {
           // Middleware logic
           $next();
       }
   }
   ```

3. **Controller not found**
   ```php
   // Check namespace configuration
   $router->setControllerNamespaces(['App\\Controllers\\']);
   
   // Ensure class exists and method is public
   class UserController {
       public function index(Request $request) {
           // Controller logic
       }
   }
   ```

## 📖 Related Documentation

- **[App Class Guide](app-class.md)** - Application lifecycle management
- **[Request/Response Guide](request-response.md)** - HTTP handling
- **[Middleware Guide](middleware-complete-guide.md)** - Middleware system
- **[Route Groups Guide](route-groups.md)** - Advanced routing with groups
- **[Controller Integration](controller-integration.md)** - Laravel-style controllers
- **[Complete API Reference](api-reference.md)** - All available methods

---

**Next**: [Request/Response Guide](request-response.md) → [Middleware Guide](middleware-complete-guide.md) → [Route Groups Guide](route-groups.md)
