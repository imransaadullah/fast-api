<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

// Example middleware classes
class LoggingMiddleware {
    public function __invoke($request, $next = null) {
        echo "LoggingMiddleware: Logging request to " . $request->getUri() . "\n";
        if ($next) {
            return $next($request);
        }
        return null;
    }
}

class AuthMiddleware {
    public function __invoke($request, $next = null) {
        echo "AuthMiddleware: Checking authentication for " . $request->getUri() . "\n";
        // Simulate authentication check
        $token = $request->getHeader('Authorization');
        if (!$token) {
            echo "AuthMiddleware: No token provided, returning 401\n";
            (new Response())->setJsonResponse(['error' => 'Unauthorized'], 401)->send();
            return null;
        }
        echo "AuthMiddleware: Token validated successfully\n";
        if ($next) {
            return $next($request);
        }
        return null;
    }
}

class RateLimitMiddleware {
    public function __invoke($request, $next = null) {
        echo "RateLimitMiddleware: Checking rate limit for " . $request->getUri() . "\n";
        // Simulate rate limiting
        echo "RateLimitMiddleware: Rate limit check passed\n";
        if ($next) {
            return $next($request);
        }
        return null;
    }
}

// Get the app instance
$app = App::getInstance();

echo "=== FastAPI App-Level Route Groups Example ===\n\n";

// Example 1: Basic route group with prefix
echo "=== Example 1: Basic Route Group with Prefix ===\n";
$app->group(['prefix' => 'api'], function($app) {
    $app->get('/users', function($request) {
        echo "GET /api/users - Returning users list\n";
        return ['users' => ['John', 'Jane', 'Bob']];
    });
    
    $app->post('/users', function($request) {
        $data = $request->getData();
        echo "POST /api/users - Creating user: " . json_encode($data) . "\n";
        return ['message' => 'User created successfully', 'data' => $data];
    });
    
    $app->get('/users/{id}', function($request) {
        $id = $request->getAttribute('id');
        echo "GET /api/users/{$id} - Returning user details\n";
        return ['user' => ['id' => $id, 'name' => 'John Doe']];
    });
});

// Example 2: Route group with middleware
echo "\n=== Example 2: Route Group with Middleware ===\n";
$app->group(['middleware' => [new LoggingMiddleware()]], function($app) {
    $app->get('/public/info', function($request) {
        echo "GET /public/info - Returning public information\n";
        return ['info' => 'This is public information'];
    });
});

// Example 3: Route group with both prefix and middleware
echo "\n=== Example 3: Route Group with Prefix and Middleware ===\n";
$app->group(['prefix' => 'api/v1', 'middleware' => [new LoggingMiddleware(), new AuthMiddleware()]], function($app) {
    $app->get('/profile', function($request) {
        echo "GET /api/v1/profile - Returning user profile\n";
        return ['profile' => ['name' => 'John Doe', 'email' => 'john@example.com']];
    });
    
    $app->put('/profile', function($request) {
        $data = $request->getData();
        echo "PUT /api/v1/profile - Updating user profile: " . json_encode($data) . "\n";
        return ['message' => 'Profile updated successfully', 'data' => $data];
    });
});

// Example 4: Nested route groups
echo "\n=== Example 4: Nested Route Groups ===\n";
$app->group(['prefix' => 'admin', 'middleware' => [new AuthMiddleware()]], function($app) {
    $app->get('/dashboard', function($request) {
        echo "GET /admin/dashboard - Returning admin dashboard\n";
        return ['dashboard' => ['stats' => 'Admin statistics']];
    });
    
    $app->group(['prefix' => 'users', 'middleware' => [new RateLimitMiddleware()]], function($app) {
        $app->get('/', function($request) {
            echo "GET /admin/users - Returning all users\n";
            return ['users' => ['Admin1', 'Admin2', 'Admin3']];
        });
        
        $app->post('/', function($request) {
            $data = $request->getData();
            echo "POST /admin/users - Creating admin user: " . json_encode($data) . "\n";
            return ['message' => 'Admin user created', 'data' => $data];
        });
    });
});

// Example 5: Route group with namespace (for controller organization)
echo "\n=== Example 5: Route Group with Namespace ===\n";
$app->group(['prefix' => 'api/v2', 'namespace' => 'App\\Controllers'], function($app) {
    $app->get('/products', function($request) {
        echo "GET /api/v2/products - Returning products list\n";
        return ['products' => ['Product1', 'Product2', 'Product3']];
    });
    
    $app->post('/products', function($request) {
        $data = $request->getData();
        echo "POST /api/v2/products - Creating product: " . json_encode($data) . "\n";
        return ['message' => 'Product created', 'data' => $data];
    });
});

// Example 6: Fluent API with groups
echo "\n=== Example 6: Fluent API with Groups ===\n";
$app->group(['prefix' => 'fluent'], function($app) {
    $app->group(['middleware' => [new LoggingMiddleware()]], function($app) {
        $app->get('/test', function($request) {
            echo "GET /fluent/test - Fluent API test\n";
            return ['message' => 'Fluent API working'];
        });
    });
});

// Display all registered routes
echo "\n=== All Registered Routes ===\n";
$routes = $app->getRoutes();
foreach ($routes as $route) {
    echo "Method: {$route['method']}, URI: {$route['uri']}, Final URI: {$route['_final_uri']}\n";
    if (!empty($route['_group']['middleware'])) {
        echo "  Middleware: " . count($route['_group']['middleware']) . " middleware(s)\n";
    }
    if (!empty($route['_group']['prefix'])) {
        echo "  Prefix: {$route['_group']['prefix']}\n";
    }
    if (!empty($route['_group']['namespace'])) {
        echo "  Namespace: {$route['_group']['namespace']}\n";
    }
    echo "\n";
}

echo "\n🎉 App-level route groups example completed successfully!\n";
echo "\nKey Features Demonstrated:\n";
echo "- Basic route groups with prefixes\n";
echo "- Route groups with middleware\n";
echo "- Nested route groups with inheritance\n";
echo "- Route groups with namespaces\n";
echo "- Fluent API with groups\n";
echo "- Backward compatibility with existing routes\n"; 