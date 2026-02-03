# FastAPI Framework - Agent Guidelines

Guidelines for agentic coding assistants working on FastAPI PHP framework.

## Build / Lint / Test Commands

```bash
# Run specific test files (standalone PHP scripts)
php test/test.php
php test/route_groups_test.php
php test/auto_fallback_rate_limiter_test.php
php test/websocket_test.php
php test/event_queue_test.php

# Composer commands
composer install
composer dump-autoload

# Run applications
php your_app.php
php websocket_server.php
```

## Code Style Guidelines

### Namespace & Imports
- Root namespace: `FASTAPI`, PSR-4 autoloading maps to `src/`
- Subdirectories: `FASTAPI\Middlewares`, `FASTAPI\WebSocket`, `FASTAPI\RateLimiter`
- Use aliases for name collision prevention; group imports (framework first)

```php
namespace FASTAPI;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\Middlewares\MiddlewareInterface;
```

### Naming Conventions
- **Classes**: PascalCase (`class Request {}`)
- **Methods**: camelCase (`public function getHeader($key) {}`)
- **Properties**: camelCase with `$` prefix, private/protected (`private $router;`)
- **Constants**: UPPER_SNAKE_CASE (`const MAX_REQUESTS = 100;`)

### Type Hints & Return Types (PHP 8.0+)
- Use type hints for parameters and returns; `?` for nullable; `mixed` when uncertain
- Use `array` type hints; `void` for methods returning nothing

```php
public function getHeader(string $key): ?string {}
public function getData(): array {}
private function createMiddleware(string $alias): ?MiddlewareInterface {}
```

### Visibility
- Properties: Always `private` or `protected` (never public)
- Methods: `public` for API only; `private` for internal logic; `protected` for overrideable

### Documentation
- PHPDoc for all public methods with `@param`, `@return`, `@throws`
- Concise but informative; no inline comments for self-evident code

```php
/**
 * Retrieves a specific header from the request.
 * @param string $key The header key.
 * @return string|null The header value, or null if not found.
 */
public function getHeader($key) { return $this->getHeaders()[$key] ?? null; }
```

### Error Handling
- Return `null` for expected failures; throw exceptions for unexpected
- Use `try-catch` for fallible operations; log with `error_log()`
- Return JSON error responses for API endpoints

```php
try {
    $result = $this->someOperation();
} catch (Exception $e) {
    error_log("Operation failed: " . $e->getMessage());
    return null;
}
```

### Method Chaining (Fluent API)
- Return `$this` for configuration methods to enable chaining

```php
public function addMiddleware($middleware) {
    $this->middlewares[] = $middleware;
    return $this;
}
```

### Singleton Pattern
- Core classes (App, Router, WebSocketServer, RateLimiter) use singleton
- Private constructor, private `__clone()`, `__wakeup()` throws exception

```php
private static $instance = null;
private function __construct() {}
private function __clone() {}
public function __wakeup() { throw new \Exception("Cannot unserialize a singleton."); }
public static function getInstance() { /* ... */ }
```

### Route Parameters
- Support both `:param` and `{param}` syntax; use `parse_url($uri, PHP_URL_PATH)` to ignore query strings

```php
$app->get('/users/:id', function($request, $id) { });
$app->get('/posts/{id}', function($request, $id) { });
```

### Middleware
- Must implement `MiddlewareInterface`; `handle()` receives `Request` and `$next` closure; call `$next()` to continue

```php
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        // Logic here
        $next();
    }
}
```

### Controller Resolution
- Support Laravel-style `Controller@method` syntax
- Register middleware: `registerMiddleware($alias, $class)`
- Set namespaces: `setControllerNamespaces(['App\\Controllers\\'])`

```php
$router->get('/users', 'UserController@index');
$router->registerMiddleware('auth', AuthMiddleware::class);
```

### Static Utility Classes
- Only static methods; private constructor prevents instantiation

```php
class StringMethods {
    private function __construct() {}
    public static function match($string, $pattern) { }
}
```

### Response Formatting
- Use JSON with consistent structure; `Response::setJsonResponse($data, $statusCode)`
- Error responses include `error`, `message`, and optionally `data` fields

```php
(new Response())->setJsonResponse(['error' => 1, 'message' => 'Not found'], 404)->send();
```

### Backward Compatibility
- Maintain compatibility; use `_` prefix for new internal properties
- Preserve existing method signatures; add new methods instead of modifying

### File Organization
- Classes: `src/ClassName.php`; subdirectories: `src/Namespace/ClassName.php`
- Tests: `test/test_name.php` (standalone PHP scripts)
- Examples: `examples/example_name.php`

### WebSocket Implementation
- Singleton pattern; routes via `WebSocketServer::on($path, $handler)`
- Event queue system for API-triggered broadcasts; file-based event storage

### Rate Limiting
- **OPT-IN by default** - disabled until explicitly enabled with `setRateLimit($maxRequests, $timeWindow, $enabled, $silentMode)`
- Auto-fallback: Redis → File storage
- Two modes: **Blocking** (sends 429) or **Silent** (logs only, doesn't block)
- Use `enableRateLimit()` / `disableRateLimit()` for runtime control
- Use `checkRateLimit($request)` to manually check status in middleware/handlers
- In silent mode, violations set `request->getAttribute('rate_limit_exceeded')` with details
- All errors logged with `error_log()`; system fails open on storage failures (never breaks app)

```php
// Enable blocking rate limiting (production)
$app->setRateLimit(100, 60, true, false);

// Enable silent mode (development/monitoring)
$app->setRateLimit(100, 60, true, true);

// Runtime control
$app->enableRateLimit();
$app->disableRateLimit();

// Manual check in middleware
$limitInfo = $app->checkRateLimit($request);
if ($limitInfo) { /* handle exceeded limit gracefully */ }
```

### Testing Guidelines
- Standalone PHP scripts output `✓` for success, `✗` for failure
- Use simple assertions with echo statements; clear section headers; test both happy path and error cases
