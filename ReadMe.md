# FastAPI Framework

FastAPI is a lightweight, powerful PHP framework designed to make building APIs fast, simple, and efficient. It provides a comprehensive set of features and tools to streamline the development process, allowing developers to focus on writing clean and maintainable code.

## 🚀 Features

- **Advanced Routing**: Intuitive routing system with route groups, prefixes, and nested structures
- **Laravel-Style Syntax**: String middleware and Controller@method syntax support
- **Middleware System**: Powerful middleware pipeline with auto-resolution
- **JWT Token Handling**: Complete JWT token generation, verification, and refresh capabilities
- **Request/Response Handling**: Rich HTTP request and response objects with multiple content types
- **Custom Time Utilities**: Advanced date/time manipulation and formatting
- **String & Array Utilities**: Comprehensive utility methods for common operations
- **Rate Limiting**: Built-in request rate limiting with IP-based tracking
- **WebSocket Support**: Real-time WebSocket server with broadcasting capabilities
- **100% Backward Compatible**: All new features preserve existing functionality
- **Type Safety**: Proper error handling and validation throughout
- **Singleton App Pattern**: Efficient application lifecycle management

## 📦 Installation

Install FastAPI using Composer:

```bash
composer require progrmanial/fast-api
```

## 🏁 Quick Start

### Basic Application Setup

```php
<?php
require 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

$app = App::getInstance();

// Basic route
$app->get('/hello', function(Request $request) {
    return (new Response())->setJsonResponse(['message' => 'Hello World!']);
});

// Route with parameters
$app->get('/users/:id', function(Request $request, $id) {
    return (new Response())->setJsonResponse(['user_id' => $id]);
});

// Route groups for organized API structure
$app->group(['prefix' => 'api'], function($app) {
    $app->get('/users', function($request) {
        return (new Response())->setJsonResponse(['users' => ['John', 'Jane']]);
    });
    
    $app->post('/users', function($request) {
        $data = $request->getData();
        return (new Response())->setJsonResponse(['message' => 'User created', 'data' => $data]);
    });
});

$app->run();
```

## 🛠 Core Components

### 1. WebSocket Support - Real-time Communication

FastAPI includes a pure PHP WebSocket implementation for real-time communication. The WebSocket functionality is 100% backward compatible and integrates seamlessly with existing HTTP routes.

#### Basic WebSocket Server

```php
use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;
use FASTAPI\WebSocket\WebSocketConnection;

$app = App::getInstance();

// Create WebSocket server
$websocket = $app->websocket(8080, 'localhost');

// Register WebSocket routes
$websocket->on('/chat', function(WebSocketConnection $connection) {
    echo "New chat connection established\n";
    
    // Send welcome message
    $connection->send(json_encode([
        'event' => 'welcome',
        'message' => 'Welcome to the chat!'
    ]));
});

// Start the server
$websocket->start();
```

#### Fluent API Configuration

```php
// Configure WebSocket server with fluent API
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

#### Broadcasting Messages

```php
// Broadcast to all connected clients
$websocket->broadcast('user_joined', [
    'user' => 'John Doe',
    'timestamp' => time()
]);

// Broadcast to specific event
$websocket->broadcast('chat_message', [
    'user' => 'Alice',
    'message' => 'Hello everyone!',
    'room' => 'general'
]);
```

#### WebSocket Connection Management

```php
$websocket->on('/chat', function(WebSocketConnection $connection) {
    // Get connection details
    $path = $connection->getPath();        // /chat
    $headers = $connection->getHeaders();  // Request headers
    
    // Send messages
    $connection->send('Hello from server!');
    
    // Read incoming messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            $data = json_decode($message, true);
            // Process message
            $connection->send(json_encode([
                'event' => 'message_received',
                'data' => $data
            ]));
        }
    }
    
    // Close connection
    $connection->close();
});
```

#### Multiple WebSocket Servers

```php
// Chat server
$chatServer = $app->websocket(8080, 'localhost')
    ->on('/chat', $chatHandler);

// Notifications server
$notifServer = $app->websocket(8081, 'localhost')
    ->on('/notifications', $notifHandler);

// Real-time data server
$dataServer = $app->websocket(8082, 'localhost')
    ->on('/realtime', $dataHandler);
```

#### WebSocket with HTTP Routes

```php
// HTTP routes work alongside WebSocket
$app->get('/api/users', function($request) {
    return (new Response())->setJsonResponse(['users' => $users]);
});

$app->post('/api/messages', function($request) {
    $data = $request->getData();
    
    // Broadcast to WebSocket clients
    $websocket->broadcast('new_message', $data);
    
    return (new Response())->setJsonResponse(['status' => 'sent']);
});

// WebSocket routes
$websocket->on('/chat', function($connection) {
    // Handle real-time chat
});
```

#### WebSocket Server Methods

```php
$websocket = $app->websocket(8080);

// Configuration
$websocket->port(8080);           // Set port
$websocket->host('0.0.0.0');      // Set host

// Route registration
$websocket->on('/path', $handler); // Register route

// Server control
$websocket->start();               // Start server
$websocket->stop();                // Stop server

// Broadcasting
$websocket->broadcast($event, $payload); // Broadcast to all

// Connection info
$count = $websocket->getConnectionCount(); // Active connections
$connections = $websocket->getConnections(); // All connections
```

#### WebSocket Connection Methods

```php
$websocket->on('/example', function(WebSocketConnection $connection) {
    // Connection state
    $isConnected = $connection->isConnected();
    
    // Connection info
    $path = $connection->getPath();
    $headers = $connection->getHeaders();
    $header = $connection->getHeader('User-Agent');
    
    // Communication
    $connection->send($message);    // Send message
    $message = $connection->read(); // Read message
    
    // Cleanup
    $connection->close();           // Close connection
});
```

#### WebSocket Protocol Support

The WebSocket implementation supports the full WebSocket protocol:

- **Handshake**: Automatic WebSocket upgrade handshake
- **Framing**: Text, binary, ping, pong, and close frames
- **Masking**: Client-to-server message masking
- **Extensions**: Support for WebSocket extensions
- **Status Codes**: Proper close codes and status handling

#### Error Handling

```php
try {
    $websocket = $app->websocket(8080);
    $websocket->start();
} catch (Exception $e) {
    echo "WebSocket error: " . $e->getMessage();
}

// Handle connection errors
$websocket->on('/chat', function(WebSocketConnection $connection) {
    try {
        $message = $connection->read();
        // Process message
    } catch (Exception $e) {
        echo "Connection error: " . $e->getMessage();
        $connection->close();
    }
});
```

#### Performance Considerations

- **Pure PHP**: No external dependencies for WebSocket functionality
- **Lightweight**: Minimal memory footprint
- **Scalable**: Supports multiple concurrent connections
- **Efficient**: Non-blocking I/O with proper resource management

### 2. Router Class - Advanced Routing

The Router class provides powerful routing capabilities with support for groups, middleware, and Laravel-style syntax.

#### Basic Routing

```php
use FASTAPI\Router;
use FASTAPI\Request;

$router = new Router();

// HTTP method routes
$router->get('/users', function($request) { /* GET handler */ });
$router->post('/users', function($request) { /* POST handler */ });
$router->put('/users/:id', function($request, $id) { /* PUT handler */ });
$router->delete('/users/:id', function($request, $id) { /* DELETE handler */ });
$router->patch('/users/:id', function($request, $id) { /* PATCH handler */ });

// Alternative syntax
$router->addRoute('GET', '/posts', $handler);
```

#### Route Groups

Organize routes with common attributes:

```php
// Basic prefix grouping
$router->group(['prefix' => 'api/v1'], function($router) {
    $router->get('/users', $userHandler);     // GET /api/v1/users
    $router->post('/posts', $postHandler);    // POST /api/v1/posts
});

// Group with middleware
$router->group(['middleware' => ['auth']], function($router) {
    $router->get('/profile', $profileHandler);
    $router->put('/settings', $settingsHandler);
});

// Nested groups with inheritance
$router->group(['prefix' => 'admin', 'middleware' => ['auth']], function($router) {
    $router->get('/dashboard', $dashboardHandler);
    
    // Nested group inherits prefix and middleware
    $router->group(['middleware' => ['admin']], function($router) {
        $router->delete('/users/:id', $deleteUserHandler);
        // Results in: DELETE /admin/users/:id with ['auth', 'admin'] middleware
    });
});
```

#### Laravel-Style Syntax

```php
// Register middleware
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('role', RoleMiddleware::class);

// Use string middleware with parameters
$router->group(['middleware' => ['auth', 'role:admin']], function($router) {
    // Controller@method syntax
    $router->get('/users', 'App\Controllers\UserController@index');
    $router->post('/users', 'App\Controllers\UserController@store');
    
    // Both parameter formats supported
    $router->get('/users/:id', 'App\Controllers\UserController@show');        // :param
    $router->put('/users/{id}', 'App\Controllers\UserController@update');     // {param}
});
```

#### Fluent API

```php
$router->prefix('api/v1')
       ->middleware(['auth'])
       ->group([], function($router) {
           $router->get('/protected', $handler);
       });
```

### 2. App Class - Application Management

The App class manages the application lifecycle with singleton pattern and built-in features.

```php
use FASTAPI\App;

$app = App::getInstance();

// Route registration
$app->get('/route', $handler)
    ->post('/route', $handler)
    ->put('/route', $handler)
    ->delete('/route', $handler)
    ->patch('/route', $handler);

// Global middleware
$app->addMiddleware($middleware);

// Rate limiting
$app->setRateLimit(100, 60); // 100 requests per 60 seconds

// Custom 404 handler
$app->setNotFoundHandler(function($request) {
    return (new Response())->setJsonResponse(['error' => 'Not Found'], 404);
});

// Start the application
$app->run();
```

#### App-Level Route Groups

The App class supports the same powerful route grouping features as the Router class:

```php
// Basic route group with prefix
$app->group(['prefix' => 'api'], function($app) {
    $app->get('/users', function($request) {
        return (new Response())->setJsonResponse(['users' => ['John', 'Jane']]);
    });
    
    $app->post('/users', function($request) {
        $data = $request->getData();
        return (new Response())->setJsonResponse(['message' => 'User created', 'data' => $data]);
    });
});

// Group with middleware
$app->group(['middleware' => [new AuthMiddleware()]], function($app) {
    $app->get('/profile', function($request) {
        return (new Response())->setJsonResponse(['profile' => 'User profile data']);
    });
    
    $app->put('/settings', function($request) {
        return (new Response())->setJsonResponse(['message' => 'Settings updated']);
    });
});

// Nested groups with inheritance
$app->group(['prefix' => 'admin', 'middleware' => [new AuthMiddleware()]], function($app) {
    $app->get('/dashboard', function($request) {
        return (new Response())->setJsonResponse(['dashboard' => 'Admin dashboard']);
    });
    
    // Nested group inherits prefix and middleware
    $app->group(['prefix' => 'users', 'middleware' => [new RateLimitMiddleware()]], function($app) {
        $app->get('/', function($request) {
            return (new Response())->setJsonResponse(['users' => 'All users']);
        });
        
        $app->post('/', function($request) {
            return (new Response())->setJsonResponse(['message' => 'User created']);
        });
    });
});

// Group with namespace for controller organization
$app->group(['prefix' => 'api/v2', 'namespace' => 'App\\Controllers'], function($app) {
    $app->get('/products', 'ProductController@index');
    $app->post('/products', 'ProductController@store');
    $app->get('/products/{id}', 'ProductController@show');
});
```

#### WebSocket Support

FastAPI includes built-in WebSocket server capabilities for real-time applications:

```php
// Basic WebSocket server
$websocket = $app->websocket(8080, 'localhost');

// Chat room WebSocket
$websocket->on('/chat', function($connection) {
    // Send welcome message
    $connection->send(json_encode([
        'event' => 'welcome',
        'payload' => ['message' => 'Welcome to chat!']
    ]));
    
    // Handle incoming messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            $data = json_decode($message, true);
            // Process message and respond
            $connection->send(json_encode([
                'event' => 'message_received',
                'payload' => $data
            ]));
        }
    }
});

// Start the WebSocket server
$websocket->start();
```

**WebSocket Features:**
- **Real-time Communication**: Full WebSocket protocol support
- **Broadcasting**: Send messages to all connected clients
- **Authentication**: Secure WebSocket connections with tokens
- **Multiple Servers**: Run multiple WebSocket servers on different ports
- **Event-based**: JSON-based event system for structured communication
- **Fluent API**: Chainable methods for easy configuration

### 3. Request Class - HTTP Request Handling

Rich request object with dynamic properties and validation.

```php
use FASTAPI\Request;

$request = new Request('GET', '/users', ['name' => 'John']);

// Basic methods
$method = $request->getMethod();        // 'GET'
$uri = $request->getUri();             // '/users'
$data = $request->getData();           // ['name' => 'John']

// Headers
$headers = $request->getHeaders();
$auth = $request->getHeader('Authorization');
$request->withHeader('Custom', 'value');

// Dynamic attributes
$request->setAttribute('user_id', 123);
$userId = $request->getAttribute('user_id');

// Magic methods
$request->custom_property = 'value';
$value = $request->custom_property;

// File uploads
$files = $request->getFiles();

// JSON data
$jsonData = $request->getJsonData();

// Query parameters
$page = $request->getQueryParam('page');

// Validation
$rules = ['name' => 'required', 'email' => 'email'];
$isValid = $request->validateData($rules);

// Array conversion
$array = $request->toArray();
```

### 4. Response Class - HTTP Response Handling

Comprehensive response handling with multiple content types.

```php
use FASTAPI\Response;

$response = new Response();

// JSON responses
$response->setJsonResponse(['message' => 'Success'], 200);
$response->setJsonResponse(['error' => 'Not found'], 404);

// HTML responses
$response->setHtmlResponse('<h1>Hello World</h1>');

// File responses
$response->setFileResponse('/path/to/file.pdf', 'download.pdf');

// Error responses
$response->setErrorResponse('Something went wrong', 500);

// Template rendering
$response->renderHtmlTemplate('/path/to/template.php', ['name' => 'John']);

// Headers and cookies
$response->setHeader('X-Custom', 'value')
         ->addCookie('session', 'abc123', time() + 3600)
         ->setStatusCode(201);

// Streaming responses
$response->setStreamingResponse(function() {
    echo "Chunk 1\n";
    flush();
    sleep(1);
    echo "Chunk 2\n";
});

// ETag and caching
$response->setEtag('unique-hash')
         ->setLastModified(new DateTime());

// Send response
$response->send();
```

### 5. Token Class - JWT Token Management

Complete JWT token handling with encryption support.

```php
use FASTAPI\Token\Token;

// Initialize with audience
$token = new Token('api-users');

// Generate token
$jwtToken = $token->make(['user_id' => 123, 'role' => 'admin']);
$tokenString = $token->get_token();

// Verify token
$decoded = $token->verify($tokenString);
$userData = $token->get_data(); // ['user_id' => 123, 'role' => 'admin']

// Token with custom expiry
$customExpiry = time() + (24 * 60 * 60); // 24 hours
$token->make(['user_id' => 123], $customExpiry);

// Refresh token
$refreshed = $token->refresh($tokenString, 3600); // Extend by 1 hour

// Check if expired
$isExpired = $token->is_expired($tokenString);

// Encrypt token payload
$encrypted = $token->encrypt_token_payload($data, $encryptionKey);
$decrypted = $token->decrypt_token_payload($encrypted, $encryptionKey);

// Custom claims
$token->add_claim('custom_field', 'value')
      ->set_issuer('my-app')
      ->set_audience('my-users')
      ->set_expiry(time() + 3600);

// SSL/RSA configuration
$token = new Token('audience', null, true); // Use SSL
$token->set_private_key_file_openssl('/path/to/private.pem')
      ->set_public_key_file_openssl('/path/to/public.pem');
```

### 6. CustomTime Class - Advanced Date/Time Utilities

Powerful date and time manipulation with timezone support.

```php
use FASTAPI\CustomTime\CustomTime;

// Create time objects
$time = new CustomTime();                    // Current time
$specific = new CustomTime('2024-01-01');    // Specific date
$utc = new CustomTime('now');                // Current time in UTC

// Formatting
$formatted = $time->get_date('Y-m-d H:i:s');
$timestamp = $time->get_timestamp();
$utcTime = $time->get_utc_time('H:i:s');

// Static methods
$now = CustomTime::now();                    // Current timestamp
$formatted = CustomTime::now('Y-m-d');      // Formatted current time

// Date arithmetic
$future = $time->add_days(7)
               ->add_hours(2)
               ->add_minutes(30);

$past = $time->subtract_months(1)
             ->subtract_weeks(2);

// Specific additions
$time->add_years(1)
     ->add_months(6)
     ->add_weeks(2)
     ->add_days(10)
     ->add_hours(5)
     ->add_minutes(30)
     ->add_seconds(45);

// Comparisons
$time1 = new CustomTime('2024-01-01');
$time2 = new CustomTime('2024-01-02');

$isBefore = $time1->isBefore($time2);   // true
$isAfter = $time1->isAfter($time2);     // false
$isEqual = $time1->equals($time2);      // false

// Differences
$daysDiff = $time1->diffInDays($time2); // 1

// Timezone handling
$time->set_timezone('America/New_York')
     ->set_format('Y-m-d H:i:s T');

// Extend by multiple units
$time->extend_date(7, 2, 30, 0); // 7 days, 2 hours, 30 minutes

// Serialization
$serialized = $time->serialize();
$restored = CustomTime::deserialize($serialized);
```

### 7. Middleware System

Create and use middleware for request processing.

```php
use FASTAPI\Middlewares\MiddlewareInterface;
use FASTAPI\Request;

// Create custom middleware
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        // Check authentication
        $token = $request->getHeader('Authorization');
        if (!$this->isValidToken($token)) {
            (new Response())->setJsonResponse(['error' => 'Unauthorized'], 401)->send();
            return;
        }
        
        // Add user info to request
        $request->setAttribute('user', $this->getUserFromToken($token));
        
        // Continue to next middleware/handler
        $next();
    }
    
    private function isValidToken($token) { /* validation logic */ }
    private function getUserFromToken($token) { /* user extraction */ }
}

// Register and use middleware
$router->registerMiddleware('auth', AuthMiddleware::class);

// Parameterized middleware
class RoleMiddleware implements MiddlewareInterface {
    private $requiredRole;
    
    public function __construct($role) {
        $this->requiredRole = $role;
    }
    
    public function handle(Request $request, \Closure $next): void {
        $user = $request->getAttribute('user');
        if ($user['role'] !== $this->requiredRole) {
            (new Response())->setJsonResponse(['error' => 'Forbidden'], 403)->send();
            return;
        }
        $next();
    }
}

$router->registerMiddleware('role', RoleMiddleware::class);

// Use in routes
$router->group(['middleware' => ['auth', 'role:admin']], function($router) {
    $router->get('/admin/users', $handler);
});
```

### 8. Utility Classes

#### StringMethods - String Utilities

```php
use FASTAPI\StringMethods;

// Pattern matching
$matches = StringMethods::match('Hello World', 'l+');
$parts = StringMethods::split('a,b,c', ',');

// String manipulation
$sanitized = StringMethods::sanitize('hello@#world', ['@', '#']);
$unique = StringMethods::unique('hello'); // 'helo'

// Pluralization
$plural = StringMethods::plural('cat');     // 'cats'
$singular = StringMethods::singular('cats'); // 'cat'

// Case conversion
$camelCase = StringMethods::toCamelCase('hello-world'); // 'helloWorld'

// String replacement
$replaced = StringMethods::replaceString('hello-world', '-', '_'); // 'hello_world'

// Index finding
$index = StringMethods::indexOf('hello world', 'world'); // 6
```

#### ArrayMethods - Array Utilities

```php
use FASTAPI\ArrayMethods;

$array = ['', 'hello', null, 'world', false, 'test'];

// Array cleaning
$clean = ArrayMethods::clean($array);      // ['hello', 'world', 'test']
$trimmed = ArrayMethods::trim([' hello ', ' world ']); // ['hello', 'world']

// Structure manipulation
$object = ArrayMethods::toObject(['name' => 'John']); // stdClass object
$flat = ArrayMethods::flatten([1, [2, [3, 4]]]); // [1, 2, 3, 4]

// Element access
$first = ArrayMethods::first([1, 2, 3]);    // 1
$last = ArrayMethods::last([1, 2, 3]);      // 3
$value = ArrayMethods::get($array, 'key', 'default');

// Array inspection
$hasKey = ArrayMethods::has($array, 'key');
$keys = ArrayMethods::keys(['a' => 1, 'b' => 2]); // ['a', 'b']

// Array modification
$removed = ArrayMethods::remove($array, 'key');
```

## 🔧 Advanced Usage

### Complete Healthcare API Example

```php
<?php
require 'vendor/autoload.php';

use FASTAPI\Router;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\Middlewares\MiddlewareInterface;

// Middleware classes
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        // JWT token validation
        $token = $request->getHeader('Authorization');
        if (!$this->validateJWT($token)) {
            (new Response())->setJsonResponse(['error' => 'Unauthorized'], 401)->send();
            return;
        }
        $request->setAttribute('user', $this->getUserFromToken($token));
        $next();
    }
}

class RoleMiddleware implements MiddlewareInterface {
    private $role;
    public function __construct($role) { $this->role = $role; }
    
    public function handle(Request $request, \Closure $next): void {
        $user = $request->getAttribute('user');
        if ($user['role'] !== $this->role) {
            (new Response())->setJsonResponse(['error' => 'Forbidden'], 403)->send();
            return;
        }
        $next();
    }
}

// Controllers
class DoctorController {
    public function dashboard(Request $request) {
        return (new Response())->setJsonResponse(['dashboard' => 'doctor_data']);
    }
    
    public function patients(Request $request) {
        return (new Response())->setJsonResponse(['patients' => []]);
    }
}

class PatientController {
    public function profile(Request $request) {
        return (new Response())->setJsonResponse(['profile' => 'patient_data']);
    }
}

// Setup router
$router = new Router();
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('role', RoleMiddleware::class);
$router->setControllerNamespaces(['App\\Controllers\\']);

// API routes
$router->group(['prefix' => 'api/v1'], function($router) {
    
    // Public routes
    $router->post('/auth/login', 'AuthController@login');
    $router->post('/auth/register', 'AuthController@register');
    
    // Authenticated routes
    $router->group(['middleware' => ['auth']], function($router) {
        
        // Doctor routes
        $router->group(['middleware' => ['role:doctor']], function($router) {
            $router->get('/doctors/dashboard', 'DoctorController@dashboard');
            $router->get('/doctors/patients', 'DoctorController@patients');
            $router->get('/doctors/schedule', 'ScheduleController@index');
            $router->post('/doctors/schedule', 'ScheduleController@update');
            
            // Consultations
            $router->get('/consultations', 'ConsultationController@index');
            $router->post('/consultations', 'ConsultationController@store');
            $router->put('/consultations/{id}', 'ConsultationController@update');
        });
        
        // Patient routes
        $router->group(['middleware' => ['role:patient']], function($router) {
            $router->get('/patients/profile', 'PatientController@profile');
            $router->put('/patients/profile', 'PatientController@updateProfile');
            $router->get('/patients/appointments', 'AppointmentController@index');
            $router->post('/patients/appointments', 'AppointmentController@book');
        });
        
        // Admin routes
        $router->group(['prefix' => 'admin', 'middleware' => ['role:admin']], function($router) {
            $router->get('/dashboard', 'AdminController@dashboard');
            $router->get('/users', 'AdminController@users');
            $router->post('/users', 'AdminController@createUser');
            $router->delete('/users/{id}', 'AdminController@deleteUser');
        });
    });
});

// Handle request
$request = new Request($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_POST);
$router->dispatch($request);
```

### Rate Limited API with Custom Middleware

```php
use FASTAPI\App;

$app = App::getInstance();

// Set rate limiting
$app->setRateLimit(1000, 3600); // 1000 requests per hour

// Custom logging middleware
$app->addMiddleware(function($request, $next) {
    $start = microtime(true);
    
    // Log request
    error_log("Request: {$request->getMethod()} {$request->getUri()}");
    
    $next();
    
    // Log response time
    $duration = microtime(true) - $start;
    error_log("Response time: {$duration}s");
});

// API routes
$app->group(['prefix' => 'api'], function($app) {
    $app->get('/status', function($request) {
        return (new Response())->setJsonResponse([
            'status' => 'online',
            'timestamp' => time(),
            'version' => '1.1.0'
        ]);
    });
});

$app->run();
```

## 📚 Documentation

- **[API Reference](docs/api-reference.md)** - Complete API reference with method signatures
- **[Route Groups Guide](docs/route-groups.md)** - Comprehensive route groups documentation
- **[WebSocket Documentation](docs/websocket.md)** - Complete WebSocket implementation guide
- **[WebSocket Quick Reference](docs/websocket-quick-reference.md)** - Quick reference for WebSocket patterns
- **[CustomTime Documentation](docs/customtime.md)** - Advanced date/time manipulation guide
- **[Token Documentation](docs/token.md)** - JWT token handling and security guide
- **[Utilities Documentation](docs/utilities.md)** - StringMethods and ArrayMethods utilities
- **Examples**: `examples/` - Working examples for all features
- **Tests**: `test/` - Compatibility and functionality tests

## 🔒 Security Features

- **JWT Token Security**: RSA encryption support, token refresh, expiry validation
- **Rate Limiting**: IP-based request throttling with configurable limits
- **Input Validation**: Request data validation with custom rules
- **CORS Support**: Cross-origin request handling
- **Header Security**: Secure header management and validation
- **Middleware Pipeline**: Security middleware for authentication and authorization

## 🚀 Performance

- **Efficient Routing**: Optimized route matching with regex compilation
- **Middleware Caching**: Smart middleware resolution and caching
- **Memory Management**: Careful memory usage in group handling
- **Streaming Support**: Memory-efficient streaming responses
- **Singleton Pattern**: Efficient application lifecycle management

## 🔄 Migration and Compatibility

### From Version 1.0.x to 1.1.x

**✅ Zero Breaking Changes** - All existing code continues to work:

```php
// This existing code works unchanged
$router = new Router();
$router->addRoute('GET', '/users', $handler);

// While new features are available
$router->group(['prefix' => 'api'], function($router) {
    $router->get('/posts', $handler);
});
```

### Accessing New Features

```php
// Original routes (backward compatible)
$routes = $router->getRoutes();

// Routes with group information (new)
$compiled = $router->getCompiledRoutes();
```

## 🛠 Best Practices

### 1. Route Organization

```php
// Group related routes
$router->group(['prefix' => 'api/v1'], function($router) {
    $router->group(['prefix' => 'users'], function($router) {
        $router->get('/', 'UserController@index');
        $router->post('/', 'UserController@store');
        $router->get('/{id}', 'UserController@show');
        $router->put('/{id}', 'UserController@update');
        $router->delete('/{id}', 'UserController@destroy');
    });
});
```

### 2. Middleware Management

```php
// Register middleware once
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('admin', AdminMiddleware::class);
$router->registerMiddleware('throttle', ThrottleMiddleware::class);

// Use consistently
$router->group(['middleware' => ['auth', 'admin']], function($router) {
    // Admin routes
});
```

### 3. Error Handling

```php
class ApiController {
    protected function handleError($exception) {
        return (new Response())->setJsonResponse([
            'error' => true,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ], 500);
    }
}
```

### 4. Response Consistency

```php
class ApiResponse {
    public static function success($data, $message = 'Success') {
        return (new Response())->setJsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    public static function error($message, $code = 400) {
        return (new Response())->setJsonResponse([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $code);
    }
}
```

## 🔍 Troubleshooting

### Common Issues

1. **Middleware Not Executing**
   ```php
   // Ensure middleware is registered
   $router->registerMiddleware('auth', AuthMiddleware::class);
   
   // Check middleware implements MiddlewareInterface
   class AuthMiddleware implements MiddlewareInterface { /* ... */ }
   ```

2. **Controller Not Found**
   ```php
   // Set correct namespace
   $router->setControllerNamespaces(['App\\Controllers\\']);
   
   // Ensure class exists and method is public
   class UserController {
       public function index(Request $request) { /* ... */ }
   }
   ```

3. **Route Parameters Not Working**
   ```php
   // Both formats supported
   $router->get('/users/:id', $handler);     // :param
   $router->get('/users/{id}', $handler);    // {param}
   ```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📚 Documentation

- **[WebSocket Documentation](docs/websocket.md)** - Comprehensive WebSocket guide with examples
- **[WebSocket Quick Reference](docs/websocket-quick-reference.md)** - Quick reference for common patterns
- **[Route Groups Documentation](docs/route-groups.md)** - Advanced routing with groups and middleware

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Links

- **GitHub**: [https://github.com/imransaadullah/fast-api](https://github.com/imransaadullah/fast-api)
- **Packagist**: [https://packagist.org/packages/progrmanial/fast-api](https://packagist.org/packages/progrmanial/fast-api)
- **Issues**: [https://github.com/imransaadullah/fast-api/issues](https://github.com/imransaadullah/fast-api/issues)

---

**FastAPI Framework** - Making PHP API development fast, simple, and powerful! 🚀