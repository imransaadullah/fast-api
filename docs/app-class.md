# App Class Guide

The App class is the core of the FastAPI framework, implementing the singleton pattern to manage the application lifecycle, routing, middleware, and request processing.

## 📋 Table of Contents

- [Overview](#overview)
- [Singleton Pattern](#singleton-pattern)
- [Core Methods](#core-methods)
- [Route Registration](#route-registration)
- [Middleware Management](#middleware-management)
- [Route Groups](#route-groups)
- [WebSocket Support](#websocket-support)
- [Configuration](#configuration)
- [Error Handling](#error-handling)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)

## 🎯 Overview

The App class serves as the main entry point for your FastAPI application, providing:

- **Singleton Instance**: Ensures only one application instance exists
- **Route Management**: Delegates routing to the Router class
- **Middleware Pipeline**: Manages global middleware execution
- **Request Processing**: Handles HTTP requests and responses
- **Rate Limiting**: Built-in request throttling
- **WebSocket Support**: Real-time communication capabilities

## 🔄 Singleton Pattern

### Getting the Instance

```php
use FASTAPI\App;

// Get the singleton instance
$app = App::getInstance();

// This always returns the same instance
$app2 = App::getInstance();
var_dump($app === $app2); // true
```

### Preventing Multiple Instances

```php
// ❌ This will throw an exception
$app = new App(); // Error: Cannot instantiate singleton

// ❌ This will also throw an exception
$app = clone App::getInstance(); // Error: Cannot clone singleton

// ❌ This will throw an exception
$app = unserialize(serialize(App::getInstance())); // Error: Cannot unserialize singleton
```

## 🛠 Core Methods

### Application Lifecycle

#### `getInstance()`
```php
/**
 * Retrieves the singleton instance of the App class.
 * @return App The singleton instance.
 */
$app = App::getInstance();
```

#### `run()`
```php
/**
 * Runs the application, dispatching the incoming HTTP request.
 * @return void
 */
$app->run();
```

### Route Registration Methods

#### `get($uri, $handler)`
```php
/**
 * Registers a GET route.
 * @param string $uri The URI pattern
 * @param mixed $handler The route handler
 * @return App Returns $this for method chaining
 */
$app->get('/users', function($request) {
    return (new Response())->setJsonResponse(['users' => []]);
});
```

#### `post($uri, $handler)`
```php
/**
 * Registers a POST route.
 * @param string $uri The URI pattern
 * @param mixed $handler The route handler
 * @return App Returns $this for method chaining
 */
$app->post('/users', function($request) {
    $data = $request->getData();
    return (new Response())->setJsonResponse(['message' => 'User created']);
});
```

#### `put($uri, $handler)`
```php
/**
 * Registers a PUT route.
 * @param string $uri The URI pattern
 * @param mixed $handler The route handler
 * @return App Returns $this for method chaining
 */
$app->put('/users/:id', function($request, $id) {
    $data = $request->getData();
    return (new Response())->setJsonResponse(['message' => 'User updated']);
});
```

#### `delete($uri, $handler)`
```php
/**
 * Registers a DELETE route.
 * @param string $uri The URI pattern
 * @param mixed $handler The route handler
 * @return App Returns $this for method chaining
 */
$app->delete('/users/:id', function($request, $id) {
    return (new Response())->setJsonResponse(['message' => 'User deleted']);
});
```

#### `patch($uri, $handler)`
```php
/**
 * Registers a PATCH route.
 * @param string $uri The URI pattern
 * @param mixed $handler The route handler
 * @return App Returns $this for method chaining
 */
$app->patch('/users/:id', function($request, $id) {
    $data = $request->getData();
    return (new Response())->setJsonResponse(['message' => 'User partially updated']);
});
```

## 🔧 Middleware Management

### App-Level Middleware

#### `addMiddleware($middleware)`
```php
/**
 * Adds middleware to the global middleware stack.
 * @param mixed $middleware Middleware instance, class, or closure
 * @return App Returns $this for method chaining
 */

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
/**
 * Alias for addMiddleware().
 * @param callable $middleware Middleware function
 * @return App Returns $this for method chaining
 */
$app->use(function($request, $next) {
    // Middleware logic
    $next();
});
```

### Router-Level Middleware

#### `registerMiddleware($alias, $middleware)`
```php
/**
 * Registers middleware with alias for route-specific use.
 * @param string $alias The middleware alias
 * @param mixed $middleware The middleware class or factory
 * @return App Returns $this for method chaining
 */

// Register middleware class
$app->registerMiddleware('auth', AuthMiddleware::class);

// Register with closure factory
$app->registerMiddleware('custom', function() {
    return new CustomMiddleware();
});

// Register parameterized middleware
$app->registerMiddleware('role', RoleMiddleware::class);
```

#### `getRouter()`
```php
/**
 * Gets the router instance for advanced configuration.
 * @return Router The router instance
 */
$router = $app->getRouter();
$router->registerMiddleware('advanced', AdvancedMiddleware::class);
```

## 🏗 Route Groups

### `group($attributes, $callback)`
```php
/**
 * Creates a route group with common attributes.
 * @param array $attributes Group attributes (prefix, middleware, namespace)
 * @param callable $callback Callback function to define routes
 * @return App Returns $this for method chaining
 */

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

// Group with middleware
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->group(['middleware' => ['auth']], function($app) {
    $app->get('/profile', function($request) {
        return (new Response())->setJsonResponse(['profile' => 'User data']);
    });
});

// Nested groups
$app->group(['prefix' => 'admin', 'middleware' => ['auth']], function($app) {
    $app->get('/dashboard', function($request) {
        return (new Response())->setJsonResponse(['dashboard' => 'Admin data']);
    });
    
    // Nested group inherits prefix and middleware
    $app->group(['middleware' => ['role:admin']], function($app) {
        $app->get('/users', function($request) {
            return (new Response())->setJsonResponse(['users' => 'All users']);
        });
    });
});
```

## 🌐 WebSocket Support

### `websocket($port, $host)`
```php
/**
 * Creates a WebSocket server instance.
 * @param int $port WebSocket server port (default: 8080)
 * @param string $host WebSocket server host (default: '0.0.0.0')
 * @return WebSocketServer The WebSocket server instance
 */

// Basic WebSocket server
$websocket = $app->websocket(8080, 'localhost');

// Configure WebSocket routes
$websocket->on('/chat', function($connection) {
    $connection->send('Welcome to chat!');
});

// Start the server
$websocket->start();

// Fluent API configuration
$websocket = $app->websocket()
    ->port(8080)
    ->host('0.0.0.0')
    ->on('/notifications', function($connection) {
        // Handle notifications
    })
    ->on('/realtime', function($connection) {
        // Handle real-time data
    });
```

## ⚙️ Configuration

### Rate Limiting

#### `setRateLimit($maxRequests, $timeWindow)`
```php
/**
 * Sets rate limiting configuration.
 * @param int $maxRequests Maximum requests per time window
 * @param int $timeWindow Time window in seconds
 * @return App Returns $this for method chaining
 */

// 100 requests per minute
$app->setRateLimit(100, 60);

// 1000 requests per hour
$app->setRateLimit(1000, 3600);

// 10 requests per second
$app->setRateLimit(10, 1);
```

### Controller Namespaces

#### `setControllerNamespaces($namespaces)`
```php
/**
 * Sets controller namespaces for automatic resolution.
 * @param array $namespaces Array of namespace prefixes
 * @return App Returns $this for method chaining
 */

// Set controller namespaces
$app->setControllerNamespaces([
    'App\\Controllers\\',
    'App\\Admin\\Controllers\\'
]);

// Use in routes
$app->get('/users', 'UserController@index');
$app->post('/users', 'UserController@store');
```

## 🚨 Error Handling

### Custom 404 Handler

#### `setNotFoundHandler($handler)`
```php
/**
 * Sets a custom 404 handler.
 * @param callable $handler The 404 handler function
 * @return App Returns $this for method chaining
 */

// Custom 404 handler
$app->setNotFoundHandler(function($request) {
    return (new Response())->setJsonResponse([
        'error' => 'Route not found',
        'path' => $request->getUri(),
        'method' => $request->getMethod()
    ], 404);
});

// HTML 404 handler
$app->setNotFoundHandler(function($request) {
    return (new Response())->setHtmlResponse(
        '<h1>404 - Page Not Found</h1><p>The requested page does not exist.</p>'
    );
});
```

## 📊 Advanced Features

### Route Information

#### `getRoutes()`
```php
/**
 * Gets all registered routes.
 * @return array Array of registered routes
 */

// Get all routes
$routes = $app->getRoutes();
print_r($routes);

// Example output:
// [
//     'GET' => [
//         '/users' => [handler],
//         '/users/:id' => [handler]
//     ],
//     'POST' => [
//         '/users' => [handler]
//     ]
// ]
```

### Fluent API

```php
// Chain multiple operations
$app->get('/users', $userHandler)
    ->post('/users', $createUserHandler)
    ->put('/users/:id', $updateUserHandler)
    ->delete('/users/:id', $deleteUserHandler)
    ->addMiddleware(new LoggingMiddleware())
    ->setRateLimit(100, 60);
```

## 🎯 Complete Example

```php
<?php
require_once 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

// Get application instance
$app = App::getInstance();

// Configure application
$app->setRateLimit(100, 60)
    ->setControllerNamespaces(['App\\Controllers\\']);

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

$app->addMiddleware(function($request, $next) {
    // Logging
    error_log("Request: " . $request->getMethod() . " " . $request->getUri());
    $next();
});

// Register route-specific middleware
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);

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
    
    // Protected routes
    $app->group(['middleware' => ['auth']], function($app) {
        
        $app->get('/profile', function($request) {
            return (new Response())->setJsonResponse(['profile' => 'User data']);
        });
        
        $app->put('/profile', function($request) {
            $data = $request->getData();
            return (new Response())->setJsonResponse(['message' => 'Profile updated']);
        });
        
        // Admin routes
        $app->group(['middleware' => ['role:admin']], function($app) {
            $app->get('/admin/users', function($request) {
                return (new Response())->setJsonResponse(['users' => 'All users']);
            });
            
            $app->post('/admin/users', function($request) {
                $data = $request->getData();
                return (new Response())->setJsonResponse(['message' => 'User created']);
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

### 1. Singleton Usage
```php
// ✅ Correct - Use getInstance()
$app = App::getInstance();

// ❌ Wrong - Don't try to instantiate directly
$app = new App(); // Error
```

### 2. Middleware Organization
```php
// ✅ Good - Organize middleware logically
$app->addMiddleware(new CorsMiddleware());        // CORS first
$app->addMiddleware(new SecurityMiddleware());    // Security second
$app->addMiddleware(new LoggingMiddleware());     // Logging third

// Register route-specific middleware
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);
```

### 3. Route Grouping
```php
// ✅ Good - Use groups for organization
$app->group(['prefix' => 'api/v1'], function($app) {
    $app->group(['prefix' => 'users'], function($app) {
        $app->get('/', 'UserController@index');
        $app->post('/', 'UserController@store');
        $app->get('/:id', 'UserController@show');
        $app->put('/:id', 'UserController@update');
        $app->delete('/:id', 'UserController@destroy');
    });
});
```

### 4. Error Handling
```php
// ✅ Good - Set custom error handlers
$app->setNotFoundHandler(function($request) {
    return (new Response())->setJsonResponse([
        'error' => 'Not found',
        'path' => $request->getUri()
    ], 404);
});
```

### 5. Configuration
```php
// ✅ Good - Configure early
$app = App::getInstance();
$app->setRateLimit(100, 60)
    ->setControllerNamespaces(['App\\Controllers\\']);
```

## 🔍 Troubleshooting

### Common Issues

1. **Routes not working**
   ```php
   // Check if routes are registered
   $routes = $app->getRoutes();
   print_r($routes);
   ```

2. **Middleware not executing**
   ```php
   // Ensure middleware is added before routes
   $app->addMiddleware(new LoggingMiddleware());
   // Then add routes
   $app->get('/test', $handler);
   ```

3. **Rate limiting too strict**
   ```php
   // Adjust rate limits
   $app->setRateLimit(1000, 60); // 1000 requests per minute
   ```

## 📖 Related Documentation

- **[Router Class Guide](router-class.md)** - Advanced routing capabilities
- **[Request/Response Guide](request-response.md)** - HTTP handling
- **[Middleware Guide](middleware-complete-guide.md)** - Middleware system
- **[WebSocket Guide](websocket.md)** - Real-time communication
- **[Complete API Reference](api-reference.md)** - All available methods

---

**Next**: [Router Class Guide](router-class.md) → [Request/Response Guide](request-response.md) → [Middleware Guide](middleware-complete-guide.md)
