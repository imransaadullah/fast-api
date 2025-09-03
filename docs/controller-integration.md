# Controller Integration Guide

FastAPI provides Laravel-style controller integration, allowing you to organize your application logic into controller classes with automatic method resolution and namespace management.

## 📋 Table of Contents

- [Overview](#overview)
- [Basic Controller Usage](#basic-controller-usage)
- [Controller Namespaces](#controller-namespaces)
- [Controller Methods](#controller-methods)
- [Route Parameters](#route-parameters)
- [Request Handling](#request-handling)
- [Response Methods](#response-methods)
- [Advanced Patterns](#advanced-patterns)
- [Best Practices](#best-practices)

## 🎯 Overview

Controller integration provides:

- **Laravel-Style Syntax**: Use `Controller@method` syntax for route handlers
- **Automatic Resolution**: Controllers are automatically resolved from namespaces
- **Method Injection**: Request and route parameters are automatically injected
- **Code Organization**: Keep related logic in dedicated controller classes
- **Namespace Management**: Organize controllers by feature or module

## 🏗 Basic Controller Usage

### Simple Controller

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
    
    public function show(Request $request, $id)
    {
        $user = [
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        return (new Response())->setJsonResponse(['user' => $user]);
    }
}
```

### Route Registration

```php
use FASTAPI\App;

$app = App::getInstance();

// Set controller namespaces
$app->setControllerNamespaces(['App\\Controllers\\']);

// Register routes with controller methods
$app->get('/users', 'UserController@index');
$app->get('/users/:id', 'UserController@show');
$app->post('/users', 'UserController@store');
$app->put('/users/:id', 'UserController@update');
$app->delete('/users/:id', 'UserController@destroy');
```

### Complete CRUD Controller

```php
<?php
namespace App\Controllers;

use FASTAPI\Request;
use FASTAPI\Response;

class UserController
{
    public function index(Request $request)
    {
        // Get query parameters
        $page = (int)$request->getQueryParam('page', 1);
        $limit = (int)$request->getQueryParam('limit', 10);
        
        // Get users from database
        $users = $this->getUsers($page, $limit);
        
        return (new Response())->setJsonResponse([
            'users' => $users,
            'meta' => [
                'page' => $page,
                'limit' => $limit
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
        
        // Create user
        $user = $this->createUser($data);
        
        return (new Response())->setJsonResponse([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }
    
    public function show(Request $request, $id)
    {
        $user = $this->getUser($id);
        
        if (!$user) {
            return (new Response())->setJsonResponse([
                'error' => 'User not found'
            ], 404);
        }
        
        return (new Response())->setJsonResponse(['user' => $user]);
    }
    
    public function update(Request $request, $id)
    {
        $data = $request->getData();
        
        // Check if user exists
        $user = $this->getUser($id);
        if (!$user) {
            return (new Response())->setJsonResponse([
                'error' => 'User not found'
            ], 404);
        }
        
        // Update user
        $updatedUser = $this->updateUser($id, $data);
        
        return (new Response())->setJsonResponse([
            'message' => 'User updated successfully',
            'user' => $updatedUser
        ]);
    }
    
    public function destroy(Request $request, $id)
    {
        // Check if user exists
        $user = $this->getUser($id);
        if (!$user) {
            return (new Response())->setJsonResponse([
                'error' => 'User not found'
            ], 404);
        }
        
        // Delete user
        $this->deleteUser($id);
        
        return (new Response())->setJsonResponse([
            'message' => 'User deleted successfully'
        ]);
    }
    
    // Private helper methods
    private function getUsers($page, $limit)
    {
        // Database logic here
        return [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane']
        ];
    }
    
    private function createUser($data)
    {
        // Database logic here
        return [
            'id' => uniqid(),
            'name' => $data['name'],
            'email' => $data['email']
        ];
    }
    
    private function getUser($id)
    {
        // Database logic here
        return [
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
    }
    
    private function updateUser($id, $data)
    {
        // Database logic here
        return [
            'id' => $id,
            'name' => $data['name'] ?? 'John Doe',
            'email' => $data['email'] ?? 'john@example.com'
        ];
    }
    
    private function deleteUser($id)
    {
        // Database logic here
        return true;
    }
}
```

## 🏷 Controller Namespaces

### Setting Namespaces

```php
use FASTAPI\App;

$app = App::getInstance();

// Single namespace
$app->setControllerNamespaces(['App\\Controllers\\']);

// Multiple namespaces
$app->setControllerNamespaces([
    'App\\Controllers\\',
    'App\\Admin\\Controllers\\',
    'App\\Api\\Controllers\\'
]);
```

### Namespace Resolution

```php
// With namespace: App\Controllers
$app->get('/users', 'UserController@index');
// Resolves to: App\Controllers\UserController@index

// With namespace: App\Admin\Controllers
$app->get('/admin/users', 'UserController@index');
// Resolves to: App\Admin\Controllers\UserController@index

// With namespace: App\Api\Controllers
$app->get('/api/users', 'UserController@index');
// Resolves to: App\Api\Controllers\UserController@index
```

### Group-Specific Namespaces

```php
// Set default namespaces
$app->setControllerNamespaces(['App\\Controllers\\']);

// Group with specific namespace
$app->group(['namespace' => 'App\\Admin\\Controllers'], function($app) {
    $app->get('/admin/dashboard', 'DashboardController@index');
    $app->get('/admin/users', 'UserController@index');
});

// Group with different namespace
$app->group(['namespace' => 'App\\Api\\Controllers'], function($app) {
    $app->get('/api/v1/users', 'UserController@index');
    $app->post('/api/v1/users', 'UserController@store');
});
```

## 🔧 Controller Methods

### Method Signature

```php
public function methodName(Request $request, ...$parameters)
{
    // Controller logic here
    return (new Response())->setJsonResponse($data);
}
```

### Parameter Injection

```php
class UserController
{
    // Route: GET /users/:id/posts/:postId
    public function showPost(Request $request, $id, $postId)
    {
        // $id = route parameter value
        // $postId = route parameter value
        
        return (new Response())->setJsonResponse([
            'user_id' => $id,
            'post_id' => $postId
        ]);
    }
    
    // Route: GET /users/:id/comments/:commentId/replies/:replyId
    public function showReply(Request $request, $id, $commentId, $replyId)
    {
        return (new Response())->setJsonResponse([
            'user_id' => $id,
            'comment_id' => $commentId,
            'reply_id' => $replyId
        ]);
    }
}
```

### Request Data Access

```php
class UserController
{
    public function store(Request $request)
    {
        // Get all request data
        $data = $request->getData();
        
        // Get specific fields
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        
        // Get JSON data
        $jsonData = $request->getJsonData();
        
        // Get query parameters
        $page = $request->getQueryParam('page', 1);
        $limit = $request->getQueryParam('limit', 10);
        
        // Get headers
        $authHeader = $request->getHeader('Authorization');
        
        // Get files
        $avatar = $request->getFile('avatar');
        
        // Process data...
        return (new Response())->setJsonResponse(['message' => 'Success']);
    }
}
```

## 🔗 Route Parameters

### Single Parameter

```php
// Route: GET /users/:id
$app->get('/users/:id', 'UserController@show');

class UserController
{
    public function show(Request $request, $id)
    {
        // $id contains the route parameter value
        return (new Response())->setJsonResponse(['user_id' => $id]);
    }
}
```

### Multiple Parameters

```php
// Route: GET /users/:userId/posts/:postId
$app->get('/users/:userId/posts/:postId', 'UserController@showPost');

class UserController
{
    public function showPost(Request $request, $userId, $postId)
    {
        return (new Response())->setJsonResponse([
            'user_id' => $userId,
            'post_id' => $postId
        ]);
    }
}
```

### Complex Parameters

```php
// Route: GET /users/:id/posts/:postId/comments/:commentId
$app->get('/users/:id/posts/:postId/comments/:commentId', 'UserController@showComment');

class UserController
{
    public function showComment(Request $request, $id, $postId, $commentId)
    {
        return (new Response())->setJsonResponse([
            'user_id' => $id,
            'post_id' => $postId,
            'comment_id' => $commentId
        ]);
    }
}
```

## 📥 Request Handling

### Validation

```php
class UserController
{
    public function store(Request $request)
    {
        $data = $request->getData();
        
        // Basic validation
        if (empty($data['name'])) {
            return (new Response())->setJsonResponse([
                'error' => 'Name is required'
            ], 400);
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return (new Response())->setJsonResponse([
                'error' => 'Valid email is required'
            ], 400);
        }
        
        // Process valid data
        $user = $this->createUser($data);
        
        return (new Response())->setJsonResponse([
            'message' => 'User created',
            'user' => $user
        ], 201);
    }
}
```

### File Uploads

```php
class UserController
{
    public function uploadAvatar(Request $request, $id)
    {
        $file = $request->getFile('avatar');
        
        if (!$file) {
            return (new Response())->setJsonResponse([
                'error' => 'No file uploaded'
            ], 400);
        }
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return (new Response())->setJsonResponse([
                'error' => 'Invalid file type'
            ], 400);
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            return (new Response())->setJsonResponse([
                'error' => 'File too large'
            ], 400);
        }
        
        // Process file
        $fileName = uniqid() . '_' . $file['name'];
        $uploadPath = '/uploads/avatars/' . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return (new Response())->setJsonResponse([
                'message' => 'Avatar uploaded successfully',
                'file' => $fileName
            ]);
        } else {
            return (new Response())->setJsonResponse([
                'error' => 'Failed to upload file'
            ], 500);
        }
    }
}
```

### Authentication

```php
class UserController
{
    public function profile(Request $request)
    {
        // Get user from request attributes (set by middleware)
        $user = $request->getAttribute('user');
        
        if (!$user) {
            return (new Response())->setJsonResponse([
                'error' => 'Unauthorized'
            ], 401);
        }
        
        return (new Response())->setJsonResponse([
            'user' => $user
        ]);
    }
    
    public function updateProfile(Request $request)
    {
        $user = $request->getAttribute('user');
        $data = $request->getData();
        
        // Update user profile
        $updatedUser = $this->updateUser($user['id'], $data);
        
        return (new Response())->setJsonResponse([
            'message' => 'Profile updated successfully',
            'user' => $updatedUser
        ]);
    }
}
```

## 📤 Response Methods

### JSON Responses

```php
class UserController
{
    public function index(Request $request)
    {
        $users = $this->getUsers();
        
        return (new Response())->setJsonResponse([
            'users' => $users,
            'count' => count($users)
        ]);
    }
    
    public function show(Request $request, $id)
    {
        $user = $this->getUser($id);
        
        if (!$user) {
            return (new Response())->setJsonResponse([
                'error' => 'User not found'
            ], 404);
        }
        
        return (new Response())->setJsonResponse(['user' => $user]);
    }
}
```

### Error Responses

```php
class UserController
{
    public function store(Request $request)
    {
        $data = $request->getData();
        
        // Validation errors
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        }
        
        if (!empty($errors)) {
            return (new Response())->setJsonResponse([
                'error' => 'Validation failed',
                'errors' => $errors
            ], 400);
        }
        
        // Success response
        $user = $this->createUser($data);
        
        return (new Response())->setJsonResponse([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }
}
```

### Custom Headers

```php
class UserController
{
    public function index(Request $request)
    {
        $users = $this->getUsers();
        
        $response = new Response();
        $response->setJsonResponse(['users' => $users])
                 ->setHeader('X-Total-Count', count($users))
                 ->setHeader('X-Page', 1)
                 ->setHeader('Cache-Control', 'public, max-age=3600');
        
        return $response;
    }
}
```

## 🚀 Advanced Patterns

### Resource Controllers

```php
<?php
namespace App\Controllers;

use FASTAPI\Request;
use FASTAPI\Response;

class ResourceController
{
    protected $model;
    protected $validationRules;
    
    public function index(Request $request)
    {
        $items = $this->model->all();
        return (new Response())->setJsonResponse(['data' => $items]);
    }
    
    public function store(Request $request)
    {
        $data = $request->getData();
        
        // Validate data
        if (!$this->validate($data)) {
            return (new Response())->setJsonResponse([
                'error' => 'Validation failed',
                'errors' => $this->getValidationErrors()
            ], 400);
        }
        
        $item = $this->model->create($data);
        return (new Response())->setJsonResponse(['data' => $item], 201);
    }
    
    public function show(Request $request, $id)
    {
        $item = $this->model->find($id);
        
        if (!$item) {
            return (new Response())->setJsonResponse([
                'error' => 'Not found'
            ], 404);
        }
        
        return (new Response())->setJsonResponse(['data' => $item]);
    }
    
    public function update(Request $request, $id)
    {
        $data = $request->getData();
        
        if (!$this->validate($data)) {
            return (new Response())->setJsonResponse([
                'error' => 'Validation failed',
                'errors' => $this->getValidationErrors()
            ], 400);
        }
        
        $item = $this->model->update($id, $data);
        
        if (!$item) {
            return (new Response())->setJsonResponse([
                'error' => 'Not found'
            ], 404);
        }
        
        return (new Response())->setJsonResponse(['data' => $item]);
    }
    
    public function destroy(Request $request, $id)
    {
        $deleted = $this->model->delete($id);
        
        if (!$deleted) {
            return (new Response())->setJsonResponse([
                'error' => 'Not found'
            ], 404);
        }
        
        return (new Response())->setJsonResponse([
            'message' => 'Deleted successfully'
        ]);
    }
    
    protected function validate($data)
    {
        // Validation logic
        return true;
    }
    
    protected function getValidationErrors()
    {
        return [];
    }
}
```

### Base Controller

```php
<?php
namespace App\Controllers;

use FASTAPI\Request;
use FASTAPI\Response;

abstract class BaseController
{
    protected function success($data, $message = null, $statusCode = 200)
    {
        $response = ['data' => $data];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        return (new Response())->setJsonResponse($response, $statusCode);
    }
    
    protected function error($message, $statusCode = 400, $errors = null)
    {
        $response = ['error' => $message];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        return (new Response())->setJsonResponse($response, $statusCode);
    }
    
    protected function notFound($message = 'Not found')
    {
        return $this->error($message, 404);
    }
    
    protected function unauthorized($message = 'Unauthorized')
    {
        return $this->error($message, 401);
    }
    
    protected function forbidden($message = 'Forbidden')
    {
        return $this->error($message, 403);
    }
    
    protected function validationError($errors, $message = 'Validation failed')
    {
        return $this->error($message, 400, $errors);
    }
}
```

### Using Base Controller

```php
<?php
namespace App\Controllers;

use FASTAPI\Request;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        $users = $this->getUsers();
        return $this->success($users);
    }
    
    public function show(Request $request, $id)
    {
        $user = $this->getUser($id);
        
        if (!$user) {
            return $this->notFound('User not found');
        }
        
        return $this->success($user);
    }
    
    public function store(Request $request)
    {
        $data = $request->getData();
        
        // Validation
        $errors = $this->validateUser($data);
        if (!empty($errors)) {
            return $this->validationError($errors);
        }
        
        $user = $this->createUser($data);
        return $this->success($user, 'User created successfully', 201);
    }
    
    private function validateUser($data)
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        }
        
        return $errors;
    }
    
    private function getUsers()
    {
        // Database logic
        return [];
    }
    
    private function getUser($id)
    {
        // Database logic
        return null;
    }
    
    private function createUser($data)
    {
        // Database logic
        return $data;
    }
}
```

### API Resource Controllers

```php
<?php
namespace App\Controllers;

use FASTAPI\Request;

class ApiController extends BaseController
{
    public function index(Request $request)
    {
        $page = (int)$request->getQueryParam('page', 1);
        $limit = (int)$request->getQueryParam('limit', 10);
        $search = $request->getQueryParam('search');
        
        $data = $this->getPaginatedData($page, $limit, $search);
        
        return $this->success($data['items'], null, 200, [
            'pagination' => $data['pagination']
        ]);
    }
    
    protected function success($data, $message = null, $statusCode = 200, $meta = [])
    {
        $response = [
            'success' => true,
            'data' => $data
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        return (new Response())->setJsonResponse($response, $statusCode);
    }
    
    private function getPaginatedData($page, $limit, $search)
    {
        // Database logic with pagination
        return [
            'items' => [],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'pages' => 0
            ]
        ];
    }
}
```

## 🎯 Complete Example

```php
<?php
require_once 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

$app = App::getInstance();

// Set controller namespaces
$app->setControllerNamespaces([
    'App\\Controllers\\',
    'App\\Admin\\Controllers\\'
]);

// Register middleware
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('role', RoleMiddleware::class);

// Public routes
$app->get('/', function() {
    return (new Response())->setJsonResponse([
        'message' => 'Welcome to FastAPI',
        'version' => '2.2.4'
    ]);
});

$app->post('/auth/login', 'AuthController@login');
$app->post('/auth/register', 'AuthController@register');

// API routes
$app->group(['prefix' => 'api/v1'], function($app) {
    
    // Public API routes
    $app->get('/status', 'StatusController@index');
    $app->get('/products', 'ProductController@index');
    $app->get('/products/:id', 'ProductController@show');
    
    // Protected routes
    $app->group(['middleware' => ['auth']], function($app) {
        
        // User routes
        $app->group(['prefix' => 'user'], function($app) {
            $app->get('/profile', 'UserController@profile');
            $app->put('/profile', 'UserController@updateProfile');
            $app->post('/avatar', 'UserController@uploadAvatar');
            
            // User orders
            $app->group(['prefix' => 'orders'], function($app) {
                $app->get('/', 'OrderController@index');
                $app->post('/', 'OrderController@store');
                $app->get('/:id', 'OrderController@show');
                $app->put('/:id', 'OrderController@update');
                $app->delete('/:id', 'OrderController@destroy');
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

### 1. Controller Organization

```php
// ✅ Good - Organize by feature
namespace App\Controllers;

class UserController
{
    // User-related methods
}

class ProductController
{
    // Product-related methods
}

class OrderController
{
    // Order-related methods
}
```

### 2. Method Naming

```php
// ✅ Good - Use standard REST method names
class UserController
{
    public function index()    // GET /users
    public function store()    // POST /users
    public function show()     // GET /users/:id
    public function update()   // PUT /users/:id
    public function destroy()  // DELETE /users/:id
}
```

### 3. Error Handling

```php
// ✅ Good - Consistent error responses
class UserController extends BaseController
{
    public function show(Request $request, $id)
    {
        $user = $this->getUser($id);
        
        if (!$user) {
            return $this->notFound('User not found');
        }
        
        return $this->success($user);
    }
}
```

### 4. Validation

```php
// ✅ Good - Validate input data
class UserController
{
    public function store(Request $request)
    {
        $data = $request->getData();
        
        $errors = $this->validateUser($data);
        if (!empty($errors)) {
            return (new Response())->setJsonResponse([
                'error' => 'Validation failed',
                'errors' => $errors
            ], 400);
        }
        
        // Process valid data
    }
}
```

### 5. Namespace Management

```php
// ✅ Good - Use meaningful namespaces
$app->setControllerNamespaces([
    'App\\Controllers\\',           // Main controllers
    'App\\Admin\\Controllers\\',    // Admin controllers
    'App\\Api\\Controllers\\'       // API controllers
]);
```

## 🔍 Troubleshooting

### Common Issues

1. **Controller not found**
   ```php
   // Check namespace configuration
   $app->setControllerNamespaces(['App\\Controllers\\']);
   
   // Ensure class exists and is in correct namespace
   namespace App\Controllers;
   
   class UserController
   {
       // Controller methods
   }
   ```

2. **Method not found**
   ```php
   // Ensure method is public
   class UserController
   {
       public function index(Request $request)  // ✅ Public
       {
           // Method logic
       }
       
       private function helper()  // ❌ Private - not accessible
       {
           // Helper logic
       }
   }
   ```

3. **Parameter injection issues**
   ```php
   // Route: GET /users/:id/posts/:postId
   // Method signature must match route parameters
   public function showPost(Request $request, $id, $postId)  // ✅ Correct
   {
       // $id and $postId will be injected
   }
   
   public function showPost(Request $request, $id)  // ❌ Missing parameter
   {
       // $postId will be null
   }
   ```

## 📖 Related Documentation

- **[App Class Guide](app-class.md)** - Application lifecycle management
- **[Router Class Guide](router-class.md)** - Advanced routing capabilities
- **[Route Groups Guide](route-groups.md)** - Route organization
- **[Request/Response Guide](request-response.md)** - HTTP handling
- **[Complete API Reference](api-reference.md)** - All available methods

---

**Next**: [Error Handling Guide](error-handling.md) → [Validation Guide](validation-guide.md) → [Testing Guide](testing-guide.md)
