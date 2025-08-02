# Middleware Documentation

## Overview

Middleware in FastAPI allows you to execute code before and after requests are processed. This is useful for authentication, logging, CORS handling, and other cross-cutting concerns.

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Middlewares\MiddlewareInterface;
use FASTAPI\Request;

// Create your middleware class
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): void
    {
        $token = $request->getHeader('Authorization');
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $next();
    }
}

// Register middleware
$app = App::getInstance();
$app->router->registerMiddleware('auth', AuthMiddleware::class);
```

## Middleware Interface

All middleware classes must implement the `FASTAPI\Middlewares\MiddlewareInterface`:

```php
interface MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void;
}
```

## Common Middleware Examples

### Authentication Middleware

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): void
    {
        $token = $request->getHeader('Authorization');
        
        if (!$token || !$this->validateToken($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $next();
    }
    
    private function validateToken($token): bool
    {
        return strpos($token, 'Bearer ') === 0;
    }
}
```

### CORS Middleware

```php
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($request->getMethod() === 'OPTIONS') {
            http_response_code(200);
            return;
        }
        
        $next();
    }
}
```

### Rate Limiting Middleware

```php
class ThrottleMiddleware implements MiddlewareInterface
{
    private $maxRequests;
    private $timeWindow;
    
    public function __construct(int $maxRequests = 60, int $timeWindow = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function handle(Request $request, \Closure $next): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "throttle:{$ip}";
        
        if ($this->isRateLimited($key)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests']);
            return;
        }
        
        $this->incrementRequestCount($key);
        $next();
    }
    
    private function isRateLimited($key): bool
    {
        // Implement your rate limiting logic
        return false;
    }
    
    private function incrementRequestCount($key): void
    {
        // Implement your rate limiting logic
    }
}
```

## Registering Middleware

```php
$app = App::getInstance();

// Register middleware with alias
$app->router->registerMiddleware('auth', AuthMiddleware::class);
$app->router->registerMiddleware('cors', CorsMiddleware::class);
$app->router->registerMiddleware('throttle', ThrottleMiddleware::class);
```

## Auto-Resolution

The framework automatically resolves common middleware names:

- `auth` → `App\Middleware\AuthMiddleware`
- `guest` → `App\Middleware\GuestMiddleware`
- `cors` → `App\Middleware\CorsMiddleware`
- `throttle` → `App\Middleware\ThrottleMiddleware`

## Best Practices

1. **Keep middleware focused** - Single responsibility
2. **Handle errors gracefully** - Use try-catch blocks
3. **Return early for failures** - Don't continue on errors
4. **Use dependency injection** - Pass dependencies to constructor

## Error Handling

The framework now handles middleware errors gracefully. If a middleware class doesn't implement the `MiddlewareInterface`, it will be skipped instead of throwing an error.

## Examples

See `examples/middleware_example.php` for complete working examples. 