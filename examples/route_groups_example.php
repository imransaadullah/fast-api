<?php

require_once '../src/Router.php';
require_once '../src/Request.php';
require_once '../src/Response.php';
require_once '../src/Middlewares/MiddlewareInterface.php';

use FASTAPI\Router;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\Middlewares\MiddlewareInterface;

// Example middleware classes
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        // Check if user is authenticated
        echo "AuthMiddleware: Checking authentication...\n";
        // In real implementation, check for valid token/session
        $next();
    }
}

class AdminMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        echo "AdminMiddleware: Checking admin privileges...\n";
        // Check if user has admin privileges
        $next();
    }
}

class LoggingMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        echo "LoggingMiddleware: Logging request to " . $request->getUri() . "\n";
        $next();
        echo "LoggingMiddleware: Request completed\n";
    }
}

// Example controller classes
class UserController {
    public function index(Request $request) {
        echo "UserController: Listing all users\n";
        (new Response())->setJsonResponse(['users' => ['user1', 'user2']])->send();
    }
    
    public function show(Request $request, $id) {
        echo "UserController: Showing user $id\n";
        (new Response())->setJsonResponse(['user' => "user_$id"])->send();
    }
    
    public function store(Request $request) {
        echo "UserController: Creating new user\n";
        (new Response())->setJsonResponse(['message' => 'User created', 'id' => 123])->send();
    }
}

class AdminController {
    public function dashboard(Request $request) {
        echo "AdminController: Admin dashboard\n";
        (new Response())->setJsonResponse(['dashboard' => 'admin_data'])->send();
    }
    
    public function users(Request $request) {
        echo "AdminController: Admin users management\n";
        (new Response())->setJsonResponse(['admin_users' => 'data'])->send();
    }
}

// Create router instance
$router = new Router();

// Example 1: Basic route group with prefix
echo "=== Example 1: Basic Route Group with Prefix ===\n";
$router->group(['prefix' => 'api'], function($router) {
    $router->get('/status', function(Request $request) {
        echo "API Status endpoint\n";
        (new Response())->setJsonResponse(['status' => 'active'])->send();
    });
    
    $router->get('/version', function(Request $request) {
        echo "API Version endpoint\n";
        (new Response())->setJsonResponse(['version' => '1.0.0'])->send();
    });
});

// Example 2: Route group with middleware
echo "\n=== Example 2: Route Group with Middleware ===\n";
$router->group(['middleware' => [new AuthMiddleware()]], function($router) {
    $router->get('/profile', function(Request $request) {
        echo "User profile (authenticated)\n";
        (new Response())->setJsonResponse(['profile' => 'user_data'])->send();
    });
    
    $router->post('/logout', function(Request $request) {
        echo "User logout (authenticated)\n";
        (new Response())->setJsonResponse(['message' => 'Logged out'])->send();
    });
});

// Example 3: Route group with both prefix and middleware
echo "\n=== Example 3: Route Group with Prefix and Middleware ===\n";
$userController = new UserController();

$router->group(['prefix' => 'api/v1', 'middleware' => [new LoggingMiddleware(), new AuthMiddleware()]], function($router) use ($userController) {
    $router->get('/users', [$userController, 'index']);
    $router->get('/users/:id', [$userController, 'show']);
    $router->post('/users', [$userController, 'store']);
});

// Example 4: Nested route groups
echo "\n=== Example 4: Nested Route Groups ===\n";
$adminController = new AdminController();

$router->group(['prefix' => 'admin', 'middleware' => [new AuthMiddleware()]], function($router) use ($adminController) {
    // This group inherits 'admin' prefix and AuthMiddleware
    
    $router->get('/dashboard', [$adminController, 'dashboard']);
    
    // Nested group with additional middleware
    $router->group(['middleware' => [new AdminMiddleware()]], function($router) use ($adminController) {
        // This inherits: prefix 'admin', AuthMiddleware + AdminMiddleware
        $router->get('/users', [$adminController, 'users']);
        $router->delete('/users/:id', function(Request $request, $id) {
            echo "AdminController: Deleting user $id (requires admin)\n";
            (new Response())->setJsonResponse(['message' => "User $id deleted"])->send();
        });
    });
    
    // Another nested group with different prefix
    $router->group(['prefix' => 'settings'], function($router) {
        // This inherits: prefix 'admin/settings', AuthMiddleware
        $router->get('/general', function(Request $request) {
            echo "Admin general settings\n";
            (new Response())->setJsonResponse(['settings' => 'general'])->send();
        });
        
        $router->get('/security', function(Request $request) {
            echo "Admin security settings\n";
            (new Response())->setJsonResponse(['settings' => 'security'])->send();
        });
    });
});

// Example 5: Using convenience methods
echo "\n=== Example 5: Using Convenience Methods ===\n";
$router->group(['prefix' => 'api/v2'], function($router) {
    $router->get('/get-endpoint', function(Request $request) {
        echo "GET endpoint\n";
    });
    
    $router->post('/post-endpoint', function(Request $request) {
        echo "POST endpoint\n";
    });
    
    $router->put('/put-endpoint', function(Request $request) {
        echo "PUT endpoint\n";
    });
    
    $router->delete('/delete-endpoint', function(Request $request) {
        echo "DELETE endpoint\n";
    });
    
    $router->patch('/patch-endpoint', function(Request $request) {
        echo "PATCH endpoint\n";
    });
});

// Example 6: Fluent API usage
echo "\n=== Example 6: Fluent API Usage ===\n";
$router->prefix('fluent')->middleware([new LoggingMiddleware()])->group([], function($router) {
    $router->get('/test', function(Request $request) {
        echo "Fluent API test endpoint\n";
        (new Response())->setJsonResponse(['message' => 'Fluent API works!'])->send();
    });
});

// Display all registered routes
echo "\n=== Registered Routes Summary ===\n";
$routes = $router->getRoutes();
foreach ($routes as $index => $route) {
    echo ($index + 1) . ". {$route['method']} {$route['uri']} ";
    echo "(middleware: " . count($route['middleware']) . ")\n";
}

echo "\n=== Usage Instructions ===\n";
echo "1. Basic grouping: Use \$router->group(['prefix' => 'api'], \$callback)\n";
echo "2. With middleware: Use \$router->group(['middleware' => [\$middleware]], \$callback)\n";
echo "3. Combined: Use \$router->group(['prefix' => 'api', 'middleware' => [\$auth]], \$callback)\n";
echo "4. Nested groups: Create groups within group callbacks for inheritance\n";
echo "5. Convenience methods: Use \$router->get(), \$router->post(), etc. within groups\n";
echo "6. Fluent API: Chain \$router->prefix()->middleware()->group()\n";

// Example dispatch simulation (commented out to avoid conflicts)
/*
// To test dispatch:
$request = new Request();
$router->dispatch($request);
*/ 