# Route Groups Documentation

The Fast-API router now supports route groups, which allow you to organize and apply common attributes (such as prefixes, middleware, and namespaces) to multiple routes at once.

## 🔒 **100% Backward Compatible**

**All existing code will continue to work exactly as before.** The route groups implementation is fully backward compatible:

- ✅ **Existing `addRoute()` calls work unchanged**
- ✅ **Route array structure preserved** (`method`, `uri`, `handler`)
- ✅ **`getRoutes()` returns original format**
- ✅ **Route parameters work the same**
- ✅ **Array handlers work the same**
- ✅ **Mixed usage** (old + new methods) works seamlessly

**No migration required!** You can start using route groups immediately without changing any existing code.

## Features

- **Route Prefixes**: Apply common URL prefixes to grouped routes
- **Middleware Support**: Apply middleware to entire route groups
- **Nested Groups**: Create nested route groups with inheritance
- **Convenience Methods**: Use shorthand methods for HTTP verbs
- **Fluent API**: Chain methods for cleaner syntax

## Basic Usage

### Simple Route Group with Prefix

```php
use FASTAPI\Router;

$router = new Router();

$router->group(['prefix' => 'api'], function($router) {
    $router->get('/status', function($request) {
        // Accessible at: /api/status
    });
    
    $router->get('/version', function($request) {
        // Accessible at: /api/version
    });
});
```

### Route Group with Middleware

```php
$router->group(['middleware' => [new AuthMiddleware()]], function($router) {
    $router->get('/profile', function($request) {
        // This route will execute AuthMiddleware first
    });
    
    $router->post('/logout', function($request) {
        // This route will also execute AuthMiddleware first
    });
});
```

### Combined Prefix and Middleware

```php
$router->group([
    'prefix' => 'api/v1',
    'middleware' => [new LoggingMiddleware(), new AuthMiddleware()]
], function($router) {
    $router->get('/users', [$userController, 'index']);
    // Accessible at: /api/v1/users
    // Executes: LoggingMiddleware -> AuthMiddleware -> handler
});
```

## Nested Route Groups

Route groups can be nested, and child groups inherit attributes from their parents:

```php
$router->group(['prefix' => 'admin', 'middleware' => [new AuthMiddleware()]], function($router) {
    $router->get('/dashboard', [$adminController, 'dashboard']);
    // URL: /admin/dashboard, Middleware: [AuthMiddleware]
    
    // Nested group with additional middleware
    $router->group(['middleware' => [new AdminMiddleware()]], function($router) {
        $router->get('/users', [$adminController, 'users']);
        // URL: /admin/users, Middleware: [AuthMiddleware, AdminMiddleware]
    });
    
    // Nested group with additional prefix
    $router->group(['prefix' => 'settings'], function($router) {
        $router->get('/general', function($request) {
            // URL: /admin/settings/general, Middleware: [AuthMiddleware]
        });
    });
});
```

## Convenience Methods

Use HTTP verb methods directly within groups:

```php
$router->group(['prefix' => 'api'], function($router) {
    $router->get('/users', $handler);          // GET /api/users
    $router->post('/users', $handler);         // POST /api/users
    $router->put('/users/:id', $handler);      // PUT /api/users/:id
    $router->patch('/users/:id', $handler);    // PATCH /api/users/:id
    $router->delete('/users/:id', $handler);   // DELETE /api/users/:id
    $router->options('/users', $handler);      // OPTIONS /api/users
});
```

## Fluent API

Chain methods for a more readable syntax:

```php
$router->prefix('api/v1')
       ->middleware([new AuthMiddleware()])
       ->group([], function($router) {
           $router->get('/protected', $handler);
       });
```

## Middleware Execution Order

Middleware executes in the order it's defined:

1. Parent group middleware (outermost first)
2. Child group middleware
3. Route-specific middleware (if any)

```php
$router->group(['middleware' => [new Middleware1()]], function($router) {
    $router->group(['middleware' => [new Middleware2()]], function($router) {
        $router->get('/test', $handler);
        // Execution order: Middleware1 -> Middleware2 -> handler
    });
});
```

## Group Attributes

### Prefix
- Automatically adds leading/trailing slashes as needed
- Nested prefixes are concatenated
- Empty prefixes are ignored

### Middleware
- Can be a single middleware instance or array of middleware
- Child middleware is appended to parent middleware
- All middleware must implement `MiddlewareInterface`

### Namespace
- For future use in controller resolution
- Currently stored but not actively used

## Example Middleware Implementation

```php
use FASTAPI\Middlewares\MiddlewareInterface;
use FASTAPI\Request;

class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        // Check authentication
        if (!$this->isAuthenticated($request)) {
            (new Response())->setJsonResponse(['error' => 'Unauthorized'], 401)->send();
            return;
        }
        
        // Continue to next middleware/handler
        $next();
    }
    
    private function isAuthenticated(Request $request): bool {
        // Your authentication logic here
        return true;
    }
}
```

## Complete Example

```php
use FASTAPI\Router;

$router = new Router();

// Public API routes
$router->group(['prefix' => 'api/v1'], function($router) {
    $router->get('/status', function($request) {
        // GET /api/v1/status
    });
});

// Authenticated API routes
$router->group([
    'prefix' => 'api/v1',
    'middleware' => [new AuthMiddleware()]
], function($router) {
    $router->get('/profile', [$userController, 'profile']);
    $router->put('/profile', [$userController, 'updateProfile']);
    
    // Admin-only routes
    $router->group(['middleware' => [new AdminMiddleware()]], function($router) {
        $router->get('/admin/users', [$adminController, 'listUsers']);
        $router->delete('/admin/users/:id', [$adminController, 'deleteUser']);
    });
});

// Process requests
$request = new Request();
$router->dispatch($request);
```

## Migration from Basic Routing

### Before (Basic Routing)
```php
$router->addRoute('GET', '/api/users', [$controller, 'index']);
$router->addRoute('POST', '/api/users', [$controller, 'store']);
$router->addRoute('GET', '/api/users/:id', [$controller, 'show']);
```

### After (Route Groups)
```php
$router->group(['prefix' => 'api'], function($router) use ($controller) {
    $router->get('/users', [$controller, 'index']);
    $router->post('/users', [$controller, 'store']);
    $router->get('/users/:id', [$controller, 'show']);
});
```

## Best Practices

1. **Group Related Routes**: Group routes that share common functionality
2. **Use Descriptive Prefixes**: Make URLs clear and RESTful
3. **Apply Middleware at Group Level**: Avoid repeating middleware on individual routes
4. **Limit Nesting Depth**: Keep nested groups shallow for maintainability
5. **Document Group Structure**: Clearly document your route group hierarchy

## Accessing New Features While Maintaining Compatibility

### getCompiledRoutes() - See Final URLs

Use `getCompiledRoutes()` to see routes with their final URLs (including group prefixes):

```php
$router = new Router();

// Old style route
$router->addRoute('GET', '/users', $handler);

// New style route with group
$router->group(['prefix' => 'api'], function($router) {
    $router->get('/posts', $handler);
});

// Original format (backward compatible)
$routes = $router->getRoutes();
// $routes[0]['uri'] = '/users'
// $routes[1]['uri'] = '/posts'  (original URI preserved)

// New format with final URLs
$compiled = $router->getCompiledRoutes();
// $compiled[0]['final_uri'] = '/users'
// $compiled[1]['final_uri'] = '/api/posts'  (includes prefix)
// $compiled[1]['middleware'] = [...]        (middleware stack)
```

## API Reference

### Router::group(array $attributes, callable $callback)
- `$attributes`: Array of group attributes ('prefix', 'middleware', 'namespace')
- `$callback`: Function that receives the router instance

### Router::getCompiledRoutes()
- Returns routes with final URIs and group information
- **NEW:** Use this to see actual URLs that will be matched
- Maintains backward compatibility by not changing `getRoutes()`

### Router::prefix(string $prefix)
- Sets prefix for current group context
- Returns `$this` for method chaining

### Router::middleware($middleware)
- Adds middleware to current group context
- Accepts single middleware or array of middleware
- Returns `$this` for method chaining

### Router::namespace(string $namespace)
- Sets namespace for current group context
- Returns `$this` for method chaining 