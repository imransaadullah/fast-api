# Getting Started with FastAPI Framework

Welcome to FastAPI! This guide will help you get up and running with the framework quickly.

## 📋 Table of Contents

- [Installation](#installation)
- [Basic Setup](#basic-setup)
- [Your First Application](#your-first-application)
- [Understanding the Structure](#understanding-the-structure)
- [Next Steps](#next-steps)

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- Composer (PHP package manager)
- Web server (Apache, Nginx, or PHP built-in server)

### Install via Composer

```bash
# Create a new project
composer create-project progrmanial/fast-api my-api

# Or add to existing project
composer require progrmanial/fast-api
```

### Verify Installation

```bash
# Check if FastAPI is installed
composer show progrmanial/fast-api
```

## 🛠 Basic Setup

### 1. Create Your Entry Point

Create a `public/index.php` file:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

// Get the application instance
$app = App::getInstance();

// Your routes will go here

// Start the application
$app->run();
```

### 2. Configure Your Web Server

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

#### PHP Built-in Server
```bash
php -S localhost:8000 -t public
```

## 🎯 Your First Application

### Basic Hello World

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

$app = App::getInstance();

// Simple GET route
$app->get('/hello', function(Request $request) {
    return (new Response())->setJsonResponse([
        'message' => 'Hello, World!',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Route with parameters
$app->get('/hello/:name', function(Request $request, $name) {
    return (new Response())->setJsonResponse([
        'message' => "Hello, $name!",
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// POST route with data
$app->post('/users', function(Request $request) {
    $data = $request->getData();
    
    return (new Response())->setJsonResponse([
        'message' => 'User created successfully',
        'data' => $data,
        'id' => uniqid()
    ], 201);
});

$app->run();
```

### Test Your Application

```bash
# Start the server
php -S localhost:8000 -t public

# Test the routes
curl http://localhost:8000/hello
curl http://localhost:8000/hello/John
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com"}'
```

## 🏗 Understanding the Structure

### Application Flow

```
Request → App → Router → Middleware → Handler → Response
```

### Key Components

1. **App Class**: Singleton application instance
2. **Router**: Handles route registration and dispatching
3. **Request**: HTTP request object with data and headers
4. **Response**: HTTP response object for sending data
5. **Middleware**: Request processing pipeline

### File Structure

```
my-api/
├── public/
│   └── index.php          # Entry point
├── src/
│   ├── Controllers/       # Your controllers
│   ├── Middleware/        # Custom middleware
│   └── Models/           # Data models
├── vendor/               # Composer dependencies
├── composer.json
└── .htaccess            # Apache configuration
```

## 📚 Basic Concepts

### Routes

```php
// HTTP method routes
$app->get('/path', $handler);
$app->post('/path', $handler);
$app->put('/path', $handler);
$app->delete('/path', $handler);
$app->patch('/path', $handler);
```

### Route Parameters

```php
// Named parameters
$app->get('/users/:id', function($request, $id) {
    return (new Response())->setJsonResponse(['user_id' => $id]);
});

// Multiple parameters
$app->get('/posts/:postId/comments/:commentId', function($request, $postId, $commentId) {
    return (new Response())->setJsonResponse([
        'post_id' => $postId,
        'comment_id' => $commentId
    ]);
});
```

### Request Data

```php
$app->post('/api/data', function(Request $request) {
    // Get all request data
    $data = $request->getData();
    
    // Get specific fields
    $name = $data['name'] ?? null;
    $email = $data['email'] ?? null;
    
    // Get headers
    $authHeader = $request->getHeader('Authorization');
    
    // Get query parameters
    $page = $request->getQueryParam('page');
    
    return (new Response())->setJsonResponse([
        'received' => $data,
        'name' => $name,
        'email' => $email
    ]);
});
```

### Response Types

```php
// JSON response
$app->get('/api/users', function() {
    return (new Response())->setJsonResponse([
        'users' => ['John', 'Jane', 'Bob']
    ]);
});

// HTML response
$app->get('/welcome', function() {
    return (new Response())->setHtmlResponse(
        '<h1>Welcome to FastAPI!</h1><p>Your application is running.</p>'
    );
});

// Error response
$app->get('/error', function() {
    return (new Response())->setErrorResponse('Something went wrong', 500);
});
```

## 🔧 Configuration

### Environment Setup

```php
// Set environment variables
$_ENV['APP_ENV'] = 'development';
$_ENV['APP_DEBUG'] = true;
$_ENV['APP_URL'] = 'http://localhost:8000';

// Configure rate limiting
$app->setRateLimit(100, 60); // 100 requests per minute
```

### Error Handling

```php
// Custom 404 handler
$app->setNotFoundHandler(function(Request $request) {
    return (new Response())->setJsonResponse([
        'error' => 'Route not found',
        'path' => $request->getUri()
    ], 404);
});
```

## 🚀 Next Steps

### 1. Explore Core Features

- **[App Class Guide](app-class.md)** - Learn about the application lifecycle
- **[Router Class Guide](router-class.md)** - Advanced routing capabilities
- **[Request/Response Guide](request-response.md)** - HTTP handling

### 2. Add Middleware

```php
// Global middleware
$app->addMiddleware(function($request, $next) {
    // Log request
    error_log("Request: " . $request->getMethod() . " " . $request->getUri());
    
    // Continue to next middleware/route
    $next();
});
```

### 3. Use Route Groups

```php
// API routes
$app->group(['prefix' => 'api/v1'], function($app) {
    $app->get('/users', function($request) {
        return (new Response())->setJsonResponse(['users' => []]);
    });
    
    $app->post('/users', function($request) {
        $data = $request->getData();
        return (new Response())->setJsonResponse(['message' => 'User created']);
    });
});
```

### 4. Add Authentication

```php
// Register middleware
$app->registerMiddleware('auth', AuthMiddleware::class);

// Protected routes
$app->group(['middleware' => ['auth']], function($app) {
    $app->get('/profile', function($request) {
        return (new Response())->setJsonResponse(['profile' => 'User data']);
    });
});
```

## 🧪 Testing Your Setup

### Create a Test Script

```php
<?php
// test.php
require_once 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

$app = App::getInstance();

// Test routes
$app->get('/test', function() {
    return (new Response())->setJsonResponse(['status' => 'OK']);
});

$app->get('/test/:id', function($request, $id) {
    return (new Response())->setJsonResponse(['id' => $id]);
});

// Simulate a request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/test/123';

$app->run();
```

### Run Tests

```bash
# Test basic functionality
php test.php

# Test with curl
curl http://localhost:8000/test
curl http://localhost:8000/test/123
```

## 🔍 Troubleshooting

### Common Issues

1. **Autoloader not found**
   ```bash
   composer install
   composer dump-autoload
   ```

2. **Routes not working**
   - Check web server configuration
   - Verify .htaccess file
   - Check file permissions

3. **500 Internal Server Error**
   - Enable error reporting
   - Check PHP error logs
   - Verify PHP version compatibility

### Debug Mode

```php
// Enable debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add debug middleware
$app->addMiddleware(function($request, $next) {
    error_log("Debug: " . $request->getMethod() . " " . $request->getUri());
    $next();
});
```

## 📖 Additional Resources

- **[Complete API Reference](api-reference.md)** - All available methods
- **[Examples Directory](../examples/)** - Working code examples
- **[GitHub Repository](https://github.com/imransaadullah/fast-api)** - Source code and issues

---

**Next**: [App Class Guide](app-class.md) → [Router Class Guide](router-class.md) → [Request/Response Guide](request-response.md)
