# Request/Response Guide

The Request and Response classes are the core HTTP handling components of FastAPI, providing rich functionality for processing incoming requests and generating outgoing responses.

## 📋 Table of Contents

- [Overview](#overview)
- [Request Class](#request-class)
- [Response Class](#response-class)
- [Data Handling](#data-handling)
- [Headers & Cookies](#headers--cookies)
- [File Uploads](#file-uploads)
- [Validation](#validation)
- [Error Handling](#error-handling)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)

## 🎯 Overview

FastAPI provides two main classes for HTTP handling:

- **Request Class**: Handles incoming HTTP requests with data extraction, validation, and dynamic properties
- **Response Class**: Manages outgoing HTTP responses with multiple content types and advanced features

## 📥 Request Class

### Basic Usage

```php
use FASTAPI\Request;

// Create a request object
$request = new Request('GET', '/users', ['name' => 'John']);

// Access request properties
$method = $request->getMethod();        // 'GET'
$uri = $request->getUri();             // '/users'
$data = $request->getData();           // ['name' => 'John']
```

### Constructor Parameters

```php
/**
 * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
 * @param string $uri Request URI
 * @param array $data Request data (POST, PUT, etc.)
 */
$request = new Request('POST', '/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Core Methods

#### `getMethod()`
```php
/**
 * Gets the HTTP method.
 * @return string The HTTP method
 */
$method = $request->getMethod(); // 'GET', 'POST', 'PUT', etc.
```

#### `getUri()`
```php
/**
 * Gets the request URI.
 * @return string The request URI
 */
$uri = $request->getUri(); // '/users/123', '/api/v1/posts', etc.
```

#### `getData()`
```php
/**
 * Gets all request data.
 * @return array The request data
 */
$data = $request->getData();
// Returns: ['name' => 'John', 'email' => 'john@example.com']
```

### Data Access Methods

#### `getJsonData()`
```php
/**
 * Gets JSON data from request body.
 * @return array|null The JSON data or null if invalid
 */
$jsonData = $request->getJsonData();
// For Content-Type: application/json requests
```

#### `getQueryParam($key, $default = null)`
```php
/**
 * Gets a query parameter.
 * @param string $key The parameter key
 * @param mixed $default Default value if not found
 * @return mixed The parameter value
 */
$page = $request->getQueryParam('page', 1);
$limit = $request->getQueryParam('limit', 10);
$search = $request->getQueryParam('search');
```

#### `getQueryParams()`
```php
/**
 * Gets all query parameters.
 * @return array All query parameters
 */
$queryParams = $request->getQueryParams();
// Returns: ['page' => '1', 'limit' => '10', 'search' => 'john']
```

### Header Management

#### `getHeaders()`
```php
/**
 * Gets all request headers.
 * @return array All headers
 */
$headers = $request->getHeaders();
// Returns: ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token']
```

#### `getHeader($key)`
```php
/**
 * Gets a specific header.
 * @param string $key The header key
 * @return string|null The header value
 */
$contentType = $request->getHeader('Content-Type');
$authToken = $request->getHeader('Authorization');
$userAgent = $request->getHeader('User-Agent');
```

#### `withHeader($key, $value)`
```php
/**
 * Sets a header value.
 * @param string $key The header key
 * @param string $value The header value
 * @return Request Returns $this for method chaining
 */
$request->withHeader('X-Custom', 'value')
        ->withHeader('X-API-Version', '1.0');
```

### File Uploads

#### `getFiles()`
```php
/**
 * Gets uploaded files.
 * @return array Uploaded files
 */
$files = $request->getFiles();
// Returns: ['avatar' => ['name' => 'photo.jpg', 'tmp_name' => '/tmp/...', ...]]
```

#### `getFile($key)`
```php
/**
 * Gets a specific uploaded file.
 * @param string $key The file key
 * @return array|null The file data
 */
$avatar = $request->getFile('avatar');
if ($avatar) {
    $fileName = $avatar['name'];
    $tempPath = $avatar['tmp_name'];
    $fileSize = $avatar['size'];
    $fileType = $avatar['type'];
}
```

### Dynamic Properties

#### Magic Methods
```php
// Set dynamic properties
$request->user_id = 123;
$request->session_token = 'abc123';

// Get dynamic properties
$userId = $request->user_id;
$token = $request->session_token;

// Check if property exists
if (isset($request->user_id)) {
    // Property exists
}
```

#### Attribute Management

#### `setAttribute($key, $value)`
```php
/**
 * Sets a request attribute.
 * @param string $key The attribute key
 * @param mixed $value The attribute value
 * @return Request Returns $this for method chaining
 */
$request->setAttribute('user_id', 123)
        ->setAttribute('role', 'admin')
        ->setAttribute('permissions', ['read', 'write']);
```

#### `getAttribute($key, $default = null)`
```php
/**
 * Gets a request attribute.
 * @param string $key The attribute key
 * @param mixed $default Default value if not found
 * @return mixed The attribute value
 */
$userId = $request->getAttribute('user_id');
$role = $request->getAttribute('role', 'user');
$permissions = $request->getAttribute('permissions', []);
```

#### `hasAttribute($key)`
```php
/**
 * Checks if an attribute exists.
 * @param string $key The attribute key
 * @return bool True if attribute exists
 */
if ($request->hasAttribute('user_id')) {
    // Attribute exists
}
```

### Validation

#### `validateData($rules)`
```php
/**
 * Validates request data against rules.
 * @param array $rules Validation rules
 * @return bool True if validation passes
 */
$rules = [
    'name' => 'required|min:2|max:50',
    'email' => 'required|email',
    'age' => 'numeric|min:18'
];

$isValid = $request->validateData($rules);
if (!$isValid) {
    // Handle validation errors
}
```

#### `getValidationErrors()`
```php
/**
 * Gets validation errors.
 * @return array Validation errors
 */
$errors = $request->getValidationErrors();
// Returns: ['email' => 'Invalid email format', 'age' => 'Must be at least 18']
```

### Array Conversion

#### `toArray()`
```php
/**
 * Converts request to array.
 * @return array Request data as array
 */
$array = $request->toArray();
// Returns: ['method' => 'POST', 'uri' => '/users', 'data' => [...], ...]
```

## 📤 Response Class

### Basic Usage

```php
use FASTAPI\Response;

// Create a response object
$response = new Response();

// Set response content
$response->setJsonResponse(['message' => 'Success']);
$response->setHtmlResponse('<h1>Hello World</h1>');
$response->setErrorResponse('Something went wrong', 500);
```

### JSON Responses

#### `setJsonResponse($data, $statusCode = 200)`
```php
/**
 * Sets JSON response.
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 * @return Response Returns $this for method chaining
 */

// Basic JSON response
$response->setJsonResponse(['message' => 'Success']);

// JSON response with status code
$response->setJsonResponse(['user' => $userData], 201);

// Error JSON response
$response->setJsonResponse(['error' => 'Not found'], 404);

// Complex JSON response
$response->setJsonResponse([
    'success' => true,
    'data' => $data,
    'meta' => [
        'page' => 1,
        'total' => 100,
        'per_page' => 10
    ]
]);
```

### HTML Responses

#### `setHtmlResponse($html, $statusCode = 200)`
```php
/**
 * Sets HTML response.
 * @param string $html HTML content
 * @param int $statusCode HTTP status code
 * @return Response Returns $this for method chaining
 */

// Basic HTML response
$response->setHtmlResponse('<h1>Welcome to FastAPI</h1>');

// HTML response with status code
$response->setHtmlResponse('<h1>Page Not Found</h1>', 404);

// Complex HTML response
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>FastAPI</title>
</head>
<body>
    <h1>Welcome to FastAPI</h1>
    <p>Your application is running successfully.</p>
</body>
</html>
';
$response->setHtmlResponse($html);
```

### File Responses

#### `setFileResponse($filePath, $downloadName = null, $contentType = null)`
```php
/**
 * Sets file response for download.
 * @param string $filePath Path to the file
 * @param string|null $downloadName Custom download name
 * @param string|null $contentType Custom content type
 * @return Response Returns $this for method chaining
 */

// Basic file download
$response->setFileResponse('/path/to/document.pdf');

// File download with custom name
$response->setFileResponse('/path/to/document.pdf', 'report.pdf');

// File download with custom content type
$response->setFileResponse('/path/to/image.jpg', 'photo.jpg', 'image/jpeg');

// CSV file download
$response->setFileResponse('/path/to/data.csv', 'export.csv', 'text/csv');
```

### Error Responses

#### `setErrorResponse($message, $statusCode = 500)`
```php
/**
 * Sets error response.
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 * @return Response Returns $this for method chaining
 */

// Basic error response
$response->setErrorResponse('Something went wrong');

// Specific error responses
$response->setErrorResponse('Not found', 404);
$response->setErrorResponse('Unauthorized', 401);
$response->setErrorResponse('Forbidden', 403);
$response->setErrorResponse('Bad request', 400);
```

### Template Rendering

#### `renderHtmlTemplate($templatePath, $data = [])`
```php
/**
 * Renders HTML template with data.
 * @param string $templatePath Path to template file
 * @param array $data Template data
 * @return Response Returns $this for method chaining
 */

// Render template with data
$response->renderHtmlTemplate('/path/to/template.php', [
    'title' => 'Welcome',
    'user' => $userData,
    'posts' => $posts
]);

// Template file example (template.php):
/*
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
</head>
<body>
    <h1>Welcome, <?= $user['name'] ?></h1>
    <div class="posts">
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h2><?= $post['title'] ?></h2>
                <p><?= $post['content'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
*/
```

### Header Management

#### `setHeader($key, $value)`
```php
/**
 * Sets a response header.
 * @param string $key Header key
 * @param string $value Header value
 * @return Response Returns $this for method chaining
 */
$response->setHeader('X-Custom', 'value')
         ->setHeader('X-API-Version', '1.0')
         ->setHeader('Cache-Control', 'no-cache');
```

#### `addHeader($key, $value)`
```php
/**
 * Adds a response header (allows multiple values).
 * @param string $key Header key
 * @param string $value Header value
 * @return Response Returns $this for method chaining
 */
$response->addHeader('Set-Cookie', 'session=abc123')
         ->addHeader('Set-Cookie', 'theme=dark');
```

### Cookie Management

#### `addCookie($name, $value, $expiry = null, $path = '/', $domain = null, $secure = false, $httpOnly = true)`
```php
/**
 * Adds a cookie to the response.
 * @param string $name Cookie name
 * @param string $value Cookie value
 * @param int|null $expiry Expiry timestamp
 * @param string $path Cookie path
 * @param string|null $domain Cookie domain
 * @param bool $secure Secure flag
 * @param bool $httpOnly HttpOnly flag
 * @return Response Returns $this for method chaining
 */

// Basic cookie
$response->addCookie('session', 'abc123');

// Cookie with expiry
$response->addCookie('remember', 'user123', time() + 3600);

// Secure cookie
$response->addCookie('auth', 'token123', time() + 86400, '/', null, true, true);

// Multiple cookies
$response->addCookie('theme', 'dark')
         ->addCookie('language', 'en')
         ->addCookie('preferences', json_encode($prefs));
```

### Status Code Management

#### `setStatusCode($code)`
```php
/**
 * Sets HTTP status code.
 * @param int $code HTTP status code
 * @return Response Returns $this for method chaining
 */
$response->setStatusCode(201);  // Created
$response->setStatusCode(204);  // No Content
$response->setStatusCode(301);  // Moved Permanently
$response->setStatusCode(400);  // Bad Request
$response->setStatusCode(500);  // Internal Server Error
```

### Streaming Responses

#### `setStreamingResponse($callback)`
```php
/**
 * Sets streaming response.
 * @param callable $callback Streaming callback
 * @return Response Returns $this for method chaining
 */

// Basic streaming
$response->setStreamingResponse(function() {
    echo "Chunk 1\n";
    flush();
    sleep(1);
    echo "Chunk 2\n";
    flush();
    sleep(1);
    echo "Chunk 3\n";
});

// Large file streaming
$response->setStreamingResponse(function() {
    $file = fopen('/path/to/large/file.txt', 'r');
    while (!feof($file)) {
        echo fread($file, 8192);
        flush();
    }
    fclose($file);
});

// Real-time data streaming
$response->setStreamingResponse(function() {
    for ($i = 0; $i < 10; $i++) {
        echo json_encode(['count' => $i, 'timestamp' => time()]) . "\n";
        flush();
        sleep(1);
    }
});
```

### Caching & Performance

#### `setEtag($etag)`
```php
/**
 * Sets ETag header.
 * @param string $etag ETag value
 * @return Response Returns $this for method chaining
 */
$response->setEtag('abc123def456');
```

#### `setLastModified($date)`
```php
/**
 * Sets Last-Modified header.
 * @param DateTime $date Last modified date
 * @return Response Returns $this for method chaining
 */
$response->setLastModified(new DateTime());
```

#### `setCacheControl($directive, $value = null)`
```php
/**
 * Sets Cache-Control header.
 * @param string $directive Cache directive
 * @param string|null $value Directive value
 * @return Response Returns $this for method chaining
 */
$response->setCacheControl('public', 'max-age=3600')
         ->setCacheControl('no-cache')
         ->setCacheControl('private', 'max-age=300');
```

### Sending Responses

#### `send()`
```php
/**
 * Sends the response to the client.
 * @return void
 */
$response->setJsonResponse(['message' => 'Success'])
         ->setHeader('X-Custom', 'value')
         ->send();
```

## 🎯 Complete Examples

### Request Processing Example

```php
<?php
use FASTAPI\Request;
use FASTAPI\Response;

// Handle user creation
$app->post('/users', function(Request $request) {
    // Get request data
    $data = $request->getData();
    
    // Validate required fields
    $rules = [
        'name' => 'required|min:2|max:50',
        'email' => 'required|email',
        'password' => 'required|min:8'
    ];
    
    if (!$request->validateData($rules)) {
        $errors = $request->getValidationErrors();
        return (new Response())->setJsonResponse([
            'error' => 'Validation failed',
            'errors' => $errors
        ], 400);
    }
    
    // Check if user already exists
    $existingUser = getUserByEmail($data['email']);
    if ($existingUser) {
        return (new Response())->setJsonResponse([
            'error' => 'User already exists'
        ], 409);
    }
    
    // Create user
    $user = createUser($data);
    
    // Set user data in request for middleware
    $request->setAttribute('user', $user);
    
    return (new Response())->setJsonResponse([
        'message' => 'User created successfully',
        'user' => $user
    ], 201);
});
```

### File Upload Example

```php
<?php
use FASTAPI\Request;
use FASTAPI\Response;

// Handle file upload
$app->post('/upload', function(Request $request) {
    // Get uploaded file
    $file = $request->getFile('document');
    
    if (!$file) {
        return (new Response())->setJsonResponse([
            'error' => 'No file uploaded'
        ], 400);
    }
    
    // Validate file
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
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
    $uploadPath = '/uploads/' . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return (new Response())->setJsonResponse([
            'message' => 'File uploaded successfully',
            'file' => [
                'name' => $fileName,
                'size' => $file['size'],
                'type' => $file['type']
            ]
        ], 201);
    } else {
        return (new Response())->setJsonResponse([
            'error' => 'Failed to upload file'
        ], 500);
    }
});
```

### API Response Example

```php
<?php
use FASTAPI\Request;
use FASTAPI\Response;

// API response with pagination
$app->get('/api/users', function(Request $request) {
    // Get query parameters
    $page = (int)$request->getQueryParam('page', 1);
    $limit = (int)$request->getQueryParam('limit', 10);
    $search = $request->getQueryParam('search');
    
    // Validate parameters
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 10;
    
    // Get users from database
    $users = getUsers($page, $limit, $search);
    $total = getTotalUsers($search);
    
    // Calculate pagination
    $totalPages = ceil($total / $limit);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;
    
    // Build response
    $response = new Response();
    $response->setJsonResponse([
        'data' => $users,
        'meta' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev
        ]
    ]);
    
    // Add headers
    $response->setHeader('X-Total-Count', $total)
             ->setHeader('X-Page', $page)
             ->setHeader('X-Limit', $limit);
    
    // Add pagination links
    $baseUrl = '/api/users?limit=' . $limit;
    $links = [];
    
    if ($hasPrev) {
        $links['prev'] = $baseUrl . '&page=' . ($page - 1);
    }
    if ($hasNext) {
        $links['next'] = $baseUrl . '&page=' . ($page + 1);
    }
    
    if (!empty($links)) {
        $response->setHeader('Link', implode(', ', array_map(
            fn($rel, $url) => "<$url>; rel=\"$rel\"",
            array_keys($links),
            $links
        )));
    }
    
    return $response;
});
```

## 🏆 Best Practices

### Request Best Practices

1. **Always validate input data**
   ```php
   $rules = ['email' => 'required|email', 'password' => 'required|min:8'];
   if (!$request->validateData($rules)) {
       return (new Response())->setJsonResponse([
           'error' => 'Validation failed',
           'errors' => $request->getValidationErrors()
       ], 400);
   }
   ```

2. **Use attributes for middleware communication**
   ```php
   // In middleware
   $request->setAttribute('user', $userData);
   
   // In route handler
   $user = $request->getAttribute('user');
   ```

3. **Handle file uploads safely**
   ```php
   $file = $request->getFile('upload');
   if ($file && is_uploaded_file($file['tmp_name'])) {
       // Process file safely
   }
   ```

### Response Best Practices

1. **Use appropriate status codes**
   ```php
   $response->setJsonResponse($data, 201);  // Created
   $response->setJsonResponse($data, 204);  // No Content
   $response->setJsonResponse($error, 400); // Bad Request
   ```

2. **Set proper headers**
   ```php
   $response->setHeader('Content-Type', 'application/json')
            ->setHeader('Cache-Control', 'no-cache')
            ->setHeader('X-API-Version', '1.0');
   ```

3. **Handle errors consistently**
   ```php
   function errorResponse($message, $code = 500) {
       return (new Response())->setJsonResponse([
           'error' => $message,
           'code' => $code,
           'timestamp' => time()
       ], $code);
   }
   ```

4. **Use streaming for large responses**
   ```php
   $response->setStreamingResponse(function() {
       $file = fopen('/path/to/large/file', 'r');
       while (!feof($file)) {
           echo fread($file, 8192);
           flush();
       }
       fclose($file);
   });
   ```

## 🔍 Troubleshooting

### Common Issues

1. **Request data not available**
   ```php
   // Check content type
   $contentType = $request->getHeader('Content-Type');
   if (strpos($contentType, 'application/json') !== false) {
       $data = $request->getJsonData();
   } else {
       $data = $request->getData();
   }
   ```

2. **File upload issues**
   ```php
   // Check file upload settings
   $maxFileSize = ini_get('upload_max_filesize');
   $maxPostSize = ini_get('post_max_size');
   
   // Validate file
   $file = $request->getFile('upload');
   if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
       // Handle upload error
   }
   ```

3. **Response not sending**
   ```php
   // Ensure response is sent
   $response->setJsonResponse($data)->send();
   
   // Or return the response object
   return $response->setJsonResponse($data);
   ```

## 📖 Related Documentation

- **[App Class Guide](app-class.md)** - Application lifecycle management
- **[Router Class Guide](router-class.md)** - Routing and request handling
- **[Middleware Guide](middleware-complete-guide.md)** - Request/response middleware
- **[Validation Guide](validation-guide.md)** - Data validation patterns
- **[Complete API Reference](api-reference.md)** - All available methods

---

**Next**: [Middleware Guide](middleware-complete-guide.md) → [Validation Guide](validation-guide.md) → [Error Handling Guide](error-handling.md)
