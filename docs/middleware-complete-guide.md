# Complete Middleware Guide

## Overview

FastAPI provides two distinct middleware systems that work together to handle request processing:

1. **App-Level Middleware** - Global middleware that runs on every request
2. **Router-Level Middleware** - Route-specific middleware with aliasing support

## Table of Contents

- [App vs Router Middleware](#app-vs-router-middleware)
- [App-Level Middleware](#app-level-middleware)
- [Router-Level Middleware](#router-level-middleware)
- [Middleware Aliasing](#middleware-aliasing)
- [Execution Order](#execution-order)
- [Best Practices](#best-practices)
- [Common Patterns](#common-patterns)
- [Troubleshooting](#troubleshooting)

## App vs Router Middleware

| Feature | App-Level | Router-Level |
|---------|-----------|--------------|
| **Scope** | Global (every request) | Route-specific |
| **Aliasing** | ❌ No | ✅ Yes |
| **Auto-resolution** | ❌ No | ✅ Yes |
| **Registration** | `addMiddleware()`, `use()` | `registerMiddleware()` |
| **Usage** | Direct instances/closures | String aliases |
| **Execution** | Before route matching | After route matching |

## App-Level Middleware

### Purpose
App-level middleware runs on **every request** before route matching occurs. Use this for:
- CORS handling
- Global logging
- Security headers
- Rate limiting
- Request preprocessing

### Methods

#### `addMiddleware($middleware)`
```php
$app = App::getInstance();

// Add middleware instance
$app->addMiddleware(new CorsMiddleware());

// Add closure middleware
$app->addMiddleware(function($request, $next) {
    // Log request
    error_log("Request: " . $request->getMethod() . " " . $request->getUri());
    
    // Continue to next middleware/route
    $next();
});

// Add multiple middleware
$app->addMiddleware(new SecurityMiddleware())
    ->addMiddleware(new LoggingMiddleware());
```

#### `use($middleware)` (Alias)
```php
$app = App::getInstance();

// Same as addMiddleware
$app->use(new CorsMiddleware());
$app->use(function($request, $next) {
    // Middleware logic
    $next();
});
```

### App-Level Middleware Examples

#### CORS Middleware
```php
$app->addMiddleware(function($request, $next) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($request->getMethod() === 'OPTIONS') {
        http_response_code(200);
        return;
    }
    
    $next();
});
```

#### Logging Middleware
```php
$app->addMiddleware(function($request, $next) {
    $startTime = microtime(true);
    
    // Continue to next middleware/route
    $next();
    
    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000;
    
    error_log(sprintf(
        "[%s] %s %s - %dms",
        date('Y-m-d H:i:s'),
        $request->getMethod(),
        $request->getUri(),
        round($duration, 2)
    ));
});
```

#### Security Middleware
```php
$app->addMiddleware(function($request, $next) {
    // Add security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    $next();
});
```

## Router-Level Middleware

### Purpose
Router-level middleware runs only for **specific routes** after route matching. Use this for:
- Authentication
- Authorization
- Role-based access
- Route-specific validation
- API versioning

### Registration

#### `registerMiddleware($alias, $middleware)`
```php
$app = App::getInstance();

// Method 1: Direct App method (Recommended)
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);
$app->registerMiddleware('throttle', ThrottleMiddleware::class);

// Method 2: Via router access
$app->getRouter()->registerMiddleware('auth', AuthMiddleware::class);

// Register with closure factory
$app->registerMiddleware('custom', function() {
    return new CustomMiddleware();
});
```

### Auto-Resolution

The Router automatically resolves common middleware names:

```php
// These work without explicit registration
$app->router->group(['middleware' => ['auth']], function($app) {
    // Automatically resolves to App\Middleware\AuthMiddleware
});

$app->router->group(['middleware' => ['cors']], function($app) {
    // Automatically resolves to App\Middleware\CorsMiddleware
});
```

**Auto-resolved middleware:**
- `auth` → `App\Middleware\AuthMiddleware`
- `guest` → `App\Middleware\GuestMiddleware`
- `cors` → `App\Middleware\CorsMiddleware`
- `throttle` → `App\Middleware\ThrottleMiddleware`

### Parameterized Middleware

```php
// Register parameterized middleware
$app->router->registerMiddleware('role', RoleMiddleware::class);

// Use with parameters
$app->router->group(['middleware' => ['role:admin']], function($app) {
    // RoleMiddleware will receive 'admin' as parameter
});

$app->router->group(['middleware' => ['role:user']], function($app) {
    // RoleMiddleware will receive 'user' as parameter
});
```

## Middleware Aliasing

### Router-Level Aliasing (Supported)
```php
// ✅ WORKS - App provides direct middleware registration
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->group(['middleware' => ['auth']], function($app) {
    // Uses AuthMiddleware
});

// Alternative: Via router access
$app->getRouter()->registerMiddleware('auth', AuthMiddleware::class);
```

### App-Level Aliasing (NOT Supported)
```php
// ❌ DOESN'T WORK - App doesn't support aliasing
$app->addMiddleware('auth'); // Error: expects instance, not string

// ✅ CORRECT - App requires direct instances
$app->addMiddleware(new AuthMiddleware());
$app->addMiddleware(AuthMiddleware::class); // If class implements MiddlewareInterface
```

## Execution Order

```php
$app = App::getInstance();

// 1. App-level global middleware (runs first)
$app->addMiddleware(new GlobalMiddleware());
$app->addMiddleware(new LoggingMiddleware());

// 2. Route matching occurs

// 3. Router-level route-specific middleware (runs after matching)
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->group(['middleware' => ['auth']], function($app) {
    $app->get('/profile', $profileHandler);
});

// Execution order:
// 1. GlobalMiddleware
// 2. LoggingMiddleware
// 3. Route matching for /profile
// 4. AuthMiddleware
// 5. $profileHandler
```

## Best Practices

### 1. Use App-Level for Global Concerns
```php
$app = App::getInstance();

// Global middleware for cross-cutting concerns
$app->addMiddleware(new CorsMiddleware());
$app->addMiddleware(new LoggingMiddleware());
$app->addMiddleware(new SecurityMiddleware());
```

### 2. Use Router-Level for Route-Specific Logic
```php
// Route-specific middleware with aliasing
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);

$app->group(['middleware' => ['auth']], function($app) {
    // Protected routes
});
```

### 3. Combine Both Systems
```php
$app = App::getInstance();

// Global middleware
$app->addMiddleware(new CorsMiddleware());
$app->addMiddleware(new LoggingMiddleware());

// Route-specific middleware
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);

// Use both in groups
$app->group(['middleware' => ['auth']], function($app) {
    $app->get('/profile', $profileHandler);
    
    $app->group(['middleware' => ['role:admin']], function($app) {
        $app->get('/admin', $adminHandler);
    });
});
```

### 4. Implement MiddlewareInterface
```php
use FASTAPI\Middlewares\MiddlewareInterface;
use FASTAPI\Request;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): void
    {
        // Authentication logic
        if (!$this->isAuthenticated($request)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $next();
    }
    
    private function isAuthenticated(Request $request): bool
    {
        // Your authentication logic
        return true;
    }
}
```

## Common Patterns

### Authentication Pattern
```php
$app = App::getInstance();

// Register auth middleware
$app->router->registerMiddleware('auth', AuthMiddleware::class);

// Public routes
$app->get('/login', $loginHandler);
$app->get('/register', $registerHandler);

// Protected routes
$app->router->group(['middleware' => ['auth']], function($app) {
    $app->get('/profile', $profileHandler);
    $app->put('/settings', $settingsHandler);
    
    // Admin routes
    $app->router->group(['middleware' => ['role:admin']], function($app) {
        $app->get('/admin', $adminHandler);
    });
});
```

### API Versioning Pattern
```php
$app = App::getInstance();

// Register version middleware
$app->router->registerMiddleware('v1', V1Middleware::class);
$app->router->registerMiddleware('v2', V2Middleware::class);

// V1 API
$app->router->group(['prefix' => 'api/v1', 'middleware' => ['v1']], function($app) {
    $app->get('/users', $v1UserHandler);
});

// V2 API
$app->router->group(['prefix' => 'api/v2', 'middleware' => ['v2']], function($app) {
    $app->get('/users', $v2UserHandler);
});
```

### Rate Limiting Pattern
```php
$app = App::getInstance();

// Global rate limiting
$app->setRateLimit(100, 60); // 100 requests per minute

// Route-specific rate limiting
$app->router->registerMiddleware('throttle', ThrottleMiddleware::class);

$app->router->group(['middleware' => ['throttle:10,60']], function($app) {
    // 10 requests per minute for these routes
    $app->post('/api/upload', $uploadHandler);
});
```

## Troubleshooting

### 1. Middleware Not Executing

**Problem:** Middleware isn't running
```php
// ❌ Wrong - App doesn't support string aliases
$app->addMiddleware('auth');

// ✅ Correct - Use direct instance
$app->addMiddleware(new AuthMiddleware());
```

**Solution:**
```php
// For App-level middleware
$app->addMiddleware(new AuthMiddleware());

// For Router-level middleware
$app->router->registerMiddleware('auth', AuthMiddleware::class);
$app->router->group(['middleware' => ['auth']], function($app) {
    // Routes
});
```

### 2. Middleware Interface Error

**Problem:** `TypeError: Return value must be of type MiddlewareInterface`
```php
// ❌ Wrong - Class doesn't implement interface
class AuthMiddleware {
    public function handle($request, $next) {
        $next();
    }
}

// ✅ Correct - Implements interface
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        $next();
    }
}
```

### 3. Middleware Order Issues

**Problem:** Middleware executing in wrong order
```php
// ✅ Correct order
$app->addMiddleware(new GlobalMiddleware()); // Runs first
$app->router->registerMiddleware('auth', AuthMiddleware::class); // Runs after route matching
```

### 4. Parameterized Middleware Not Working

**Problem:** Parameters not being passed
```php
// ❌ Wrong - No parameter
$app->router->group(['middleware' => ['role']], function($app) {
    // No parameter passed
});

// ✅ Correct - With parameter
$app->router->group(['middleware' => ['role:admin']], function($app) {
    // 'admin' parameter passed to RoleMiddleware
});
```

## Complete Example

```php
<?php
require_once 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Middlewares\MiddlewareInterface;
use FASTAPI\Request;

// Middleware classes
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        $token = $request->getHeader('Authorization');
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $next();
    }
}

class RoleMiddleware implements MiddlewareInterface {
    private $role;
    
    public function __construct(string $role) {
        $this->role = $role;
    }
    
    public function handle(Request $request, \Closure $next): void {
        // Check user role
        if ($this->getUserRole($request) !== $this->role) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            return;
        }
        $next();
    }
    
    private function getUserRole(Request $request): string {
        // Your role logic
        return 'user';
    }
}

// Application setup
$app = App::getInstance();

// Global middleware (App-level)
$app->addMiddleware(function($request, $next) {
    header('Access-Control-Allow-Origin: *');
    $next();
});

$app->addMiddleware(function($request, $next) {
    error_log("Request: " . $request->getMethod() . " " . $request->getUri());
    $next();
});

// Route-specific middleware (Router-level)
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);

// Routes
$app->get('/public', function() {
    echo json_encode(['message' => 'Public route']);
});

$app->router->group(['middleware' => ['auth']], function($app) {
    $app->get('/profile', function() {
        echo json_encode(['message' => 'Profile data']);
    });
    
    $app->router->group(['middleware' => ['role:admin']], function($app) {
        $app->get('/admin', function() {
            echo json_encode(['message' => 'Admin panel']);
        });
    });
});

$app->run();
```

This comprehensive guide should resolve the documentation gaps and provide clear guidance on using both middleware systems effectively.
