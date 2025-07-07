# FastAPI Framework

FastPHP is a lightweight PHP framework designed to make building APIs fast, simple, and efficient. It provides a set of powerful features and tools to streamline the development process, allowing developers to focus on writing clean and maintainable code.

## Features

- **Routing**: FastAPI uses a simple and intuitive routing system to define API endpoints and their corresponding handlers.
- **Route Groups**: Organize routes with common prefixes, middleware, and nested structures for better API organization.
- **Middleware**: Middleware can be added to the request-response cycle to perform tasks such as authentication, logging, or request modification.
- **Dependency Injection**: FastAPI supports dependency injection to manage and inject dependencies into route handlers and middleware.
- **JWT Token Handling**: The framework includes classes for generating, verifying, and refreshing JWT tokens, making authentication and authorization easy to implement.
- **Custom Time Handling**: FastAPI provides a custom time class with additional functionalities for date and time manipulation.
- **Error Handling**: Error handling is built into the framework, allowing developers to handle errors gracefully and return appropriate responses to clients.
- **100% Backward Compatible**: All new features are fully backward compatible with existing code.
- **Customizable**: FastAPI is highly customizable and can be extended with additional functionality as needed.

## Installation

To install FastAPI, simply run:

```bash
composer require progrmanial/fast-api
```

## Getting Started

### Creating Routes

Routes in FastAPI are defined using the `Router` class. Here's an example of defining routes for a simple API:

```php
use FASTAPI\Router;
use FASTAPI\Request;

$router = new Router();

// Basic routes
$router->addRoute('GET', '/users', function($request) {
    // Handle GET request to /users
});

$router->addRoute('POST', '/users', function($request) {
    // Handle POST request to /users
});

// Or use convenience methods
$router->get('/posts', function($request) {
    // Handle GET request to /posts
});

$router->post('/posts', function($request) {
    // Handle POST request to /posts
});
```

### Route Groups

Organize related routes with common prefixes and middleware:

```php
use FASTAPI\Router;

$router = new Router();

// Group routes with common prefix
$router->group(['prefix' => 'api/v1'], function($router) {
    $router->get('/users', function($request) {
        // GET /api/v1/users
    });
    
    $router->post('/users', function($request) {
        // POST /api/v1/users
    });
});

// Group with middleware
$router->group(['middleware' => [new AuthMiddleware()]], function($router) {
    $router->get('/profile', function($request) {
        // Protected route
    });
});

// Nested groups with inheritance
$router->group(['prefix' => 'admin', 'middleware' => [new AuthMiddleware()]], function($router) {
    $router->get('/dashboard', function($request) {
        // GET /admin/dashboard (with auth)
    });
    
    $router->group(['middleware' => [new AdminMiddleware()]], function($router) {
        $router->delete('/users/:id', function($request, $id) {
            // DELETE /admin/users/:id (with auth + admin)
        });
    });
});
```

### Adding Middleware

Middleware can be applied to individual routes or entire route groups:

```php
use FASTAPI\Router;
use FASTAPI\Middlewares\MiddlewareInterface;

// Custom middleware class
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        // Check authentication
        if (!$this->isAuthenticated($request)) {
            (new Response())->setJsonResponse(['error' => 'Unauthorized'], 401)->send();
            return;
        }
        $next();
    }
}

$router = new Router();

// Apply middleware to route groups
$router->group(['middleware' => [new AuthMiddleware()]], function($router) {
    $router->get('/protected', function($request) {
        // This route is protected by AuthMiddleware
    });
});
```

### Backward Compatibility

**All existing code continues to work without changes!** FastAPI maintains 100% backward compatibility:

```php
// Existing code works exactly the same
$router = new Router();
$router->addRoute('GET', '/users', function($request) {
    // Still works perfectly
});

// While new features are available
$router->group(['prefix' => 'api'], function($router) {
    $router->get('/posts', function($request) {
        // New route groups feature
    });
});
```

### Generating JWT Tokens

FastAPI includes a `Token` class for generating and verifying JWT tokens:

```php
use FASTAPI\Token\Token;

$token = new Token('audience');

$jwtToken = $token->make(['user_id' => 123]);

// Verify token
$decodedToken = $token->verify($jwtToken);

// Access token data
$user_id = $decodedToken->data['user_id'];
```

### Custom Time Handling

FastAPI provides a `CustomTime` class for handling custom date and time operations:

```php
use FASTAPI\CustomTime\CustomTime;

$time = new CustomTime();

$currentTime = $time->get_date();
```

## Use Cases

### Building RESTful APIs

FastAPI is perfect for building RESTful APIs for web and mobile applications. Its simple routing system, route groups, and middleware support make it easy to define API endpoints and add functionality such as authentication and error handling.

### Organizing Large APIs with Route Groups

Route groups make it easy to organize large APIs with clean URL structures and shared middleware:

```php
// API versioning and organization
$router->group(['prefix' => 'api/v1'], function($router) {
    // User management routes
    $router->group(['prefix' => 'users'], function($router) {
        $router->get('/', [$userController, 'index']);
        $router->post('/', [$userController, 'store']);
        $router->get('/:id', [$userController, 'show']);
    });
    
    // Admin routes with authentication
    $router->group(['prefix' => 'admin', 'middleware' => [new AuthMiddleware(), new AdminMiddleware()]], function($router) {
        $router->get('/dashboard', [$adminController, 'dashboard']);
        $router->get('/users', [$adminController, 'manageUsers']);
    });
});
```

### Token-based Authentication

With FastAPI's built-in `Token` class, implementing token-based authentication is straightforward. Developers can generate, verify, and refresh JWT tokens with ease, ensuring secure authentication for their APIs.

### Custom Time Manipulation

The `CustomTime` class in FastAPI allows developers to perform various date and time manipulations, such as adding days, weeks, months, or years to a given date, comparing dates, or formatting dates in different formats.

### Error Handling

FastAPI comes with built-in error handling capabilities, allowing developers to handle errors gracefully and return meaningful responses to clients. This ensures a smooth and consistent user experience when interacting with the API.

### Middleware Integration

FastAPI supports middleware integration, enabling developers to add custom middleware to the request-response cycle. With route groups, middleware can be applied to entire groups of routes, making it easy to implement authentication, logging, request modification, or response formatting across multiple endpoints efficiently.

### Rapid Prototyping

FastAPI's lightweight and flexible architecture makes it ideal for rapid prototyping of API projects. Developers can quickly define routes, add middleware, and implement functionality without the need for extensive configuration or setup.

### Data Transformation

FastAPI can be used for data transformation tasks such as converting data between different formats (e.g., JSON to XML) or manipulating data structures. Its customizable nature allows developers to easily extend and adapt the framework to suit their specific data transformation needs.

### Real-time Applications

FastAPI can be used to build real-time applications such as chat applications, live updates, or real-time analytics dashboards. Its asynchronous capabilities and event-driven architecture make it well-suited for handling concurrent connections and processing real-time data streams.

## Documentation

For detailed documentation on route groups and advanced features, see:
- **Route Groups Documentation**: `docs/route-groups.md` - Comprehensive guide to route groups, middleware, and nested grouping
- **Examples**: `examples/route_groups_example.php` - Working examples of all route group features

## Contributing

FastAPI is an open-source project, and contributions are welcome! If you'd like to contribute, please fork the repository, make your changes, and submit a pull request. Be sure to follow the project's coding standards and guidelines.

## License

FastAPI is licensed under the MIT License.