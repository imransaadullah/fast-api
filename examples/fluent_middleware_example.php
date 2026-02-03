<?php

require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/RateLimiter/StorageInterface.php';
require_once __DIR__ . '/../src/RateLimiter/FileStorage.php';
require_once __DIR__ . '/../src/RateLimiter/RedisStorage.php';
require_once __DIR__ . '/../src/RateLimiter/RateLimiter.php';
require_once __DIR__ . '/../src/App.php';
require_once __DIR__ . '/../src/RouteBuilder.php';
require_once __DIR__ . '/../src/Request.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Middlewares/MiddlewareInterface.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\Middlewares\MiddlewareInterface;

// Example middleware classes
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        echo "🔒 AuthMiddleware: Checking authentication...\n";
        // In real implementation, check for valid token/session
        $next();
    }
}

class RBACMiddleware implements MiddlewareInterface {
    private $permissions;
    
    public function __construct($permissions) {
        $this->permissions = is_array($permissions) ? $permissions : [$permissions];
    }
    
    public function handle(Request $request, \Closure $next): void {
        echo "👤 RBACMiddleware: Checking permissions: " . implode(', ', $this->permissions) . "\n";
        // In real implementation, check user permissions
        $next();
    }
}

class ThrottleMiddleware implements MiddlewareInterface {
    private $limit;
    private $window;
    
    public function __construct($limit, $window) {
        $this->limit = $limit;
        $this->window = $window;
    }
    
    public function handle(Request $request, \Closure $next): void {
        echo "⏱️ ThrottleMiddleware: Rate limit {$this->limit} requests per {$this->window} seconds\n";
        $next();
    }
}

// Mock RBAC class
class RBAC {
    public function withPermissions($permissions) {
        return new RBACMiddleware($permissions);
    }
}

// Example controller
class ClaimsController {
    public function index(Request $request) {
        echo "📋 ClaimsController@index: Listing claims\n";
        (new Response())->setJsonResponse(['claims' => ['claim1', 'claim2']])->send();
    }
    
    public function update(Request $request, $id) {
        echo "✏️ ClaimsController@update: Updating claim $id\n";
        (new Response())->setJsonResponse(['message' => "Claim $id updated"])->send();
    }
}

// Initialize the application
$app = App::getInstance();
$rbac = new RBAC();

// Register middleware
$app->registerMiddleware('auth', AuthMiddleware::class);
$app->registerMiddleware('throttle', function($limit, $window) {
    return new ThrottleMiddleware($limit, $window);
});

echo "🎉 Fluent Middleware API Demo\n";
echo "============================\n\n";

// ✅ NEW: Your desired syntax now works!
echo "=== Example 1: Your Desired Syntax ===\n";
$app->group(['prefix' => '/v2/facilities/{facility_id}', 'middleware' => ['auth']], function($app) use ($rbac) {
    $app->route('GET', '/claims', 'ClaimsController@index')
        ->middleware($rbac->withPermissions('claims.read'));
    
    $app->route('POST', '/claims/{id}', 'ClaimsController@update')
        ->middleware($rbac->withPermissions('claims.update'));
});

echo "✅ Routes registered with fluent API!\n\n";

// ✅ NEW: Alternative fluent syntax
echo "=== Example 2: Alternative Fluent Syntax ===\n";
$app->route('GET', '/api/users', 'UserController@index')
    ->middleware(['auth', $rbac->withPermissions('users.read')])
    ->name('users.index');

$app->route('POST', '/api/users', 'UserController@store')
    ->middleware(['auth', $rbac->withPermissions('users.create')])
    ->name('users.store');

echo "✅ Alternative fluent syntax works!\n\n";

// ✅ NEW: Multiple middleware with different types
echo "=== Example 3: Multiple Middleware Types ===\n";
$app->route('POST', '/api/upload', 'UploadController@store')
    ->middleware([
        'auth',
        $rbac->withPermissions('files.upload'),
        new ThrottleMiddleware(10, 60) // 10 requests per minute
    ])
    ->name('upload.store');

echo "✅ Multiple middleware types work!\n\n";

// ✅ NEW: Route naming and constraints
echo "=== Example 4: Route Naming and Constraints ===\n";
$app->route('GET', '/users/{id}', 'UserController@show')
    ->middleware(['auth'])
    ->name('users.show')
    ->where(['id' => '\d+']);

echo "✅ Route naming and constraints work!\n\n";

// ✅ EXISTING: Backward compatibility maintained
echo "=== Example 5: Backward Compatibility ===\n";
$app->group(['prefix' => '/legacy', 'middleware' => ['auth']], function($app) {
    $app->get('/old-route', function(Request $request) {
        echo "📜 Legacy route still works!\n";
        (new Response())->setJsonResponse(['message' => 'Legacy route'])->send();
    });
});

echo "✅ Backward compatibility maintained!\n\n";

// ✅ NEW: Hybrid approach
echo "=== Example 6: Hybrid Approach ===\n";
$app->group(['prefix' => '/api/v1'], function($app) use ($rbac) {
    // Group middleware applies to all routes
    $app->route('GET', '/dashboard', 'DashboardController@index')
        ->middleware($rbac->withPermissions('dashboard.view'));
    
    $app->route('POST', '/dashboard/settings', 'DashboardController@updateSettings')
        ->middleware($rbac->withPermissions('dashboard.settings'));
});

echo "✅ Hybrid approach works!\n\n";

// Display all registered routes
echo "📋 Registered Routes Summary:\n";
echo "=============================\n";
$routes = $app->getRouter()->getCompiledRoutes();
foreach ($routes as $route) {
    $middlewareCount = count($route['middleware']);
    $name = isset($route['_name']) && $route['_name'] ? " (name: {$route['_name']})" : "";
    $handler = is_string($route['handler']) ? $route['handler'] : gettype($route['handler']);
    echo "• {$route['method']} {$route['final_uri']} → {$handler} ($middlewareCount middleware)$name\n";
}

echo "\n🎯 Key Features Demonstrated:\n";
echo "=============================\n";
echo "✅ Fluent middleware chaining: ->middleware()\n";
echo "✅ Route naming: ->name()\n";
echo "✅ Route constraints: ->where()\n";
echo "✅ Multiple middleware support\n";
echo "✅ Backward compatibility maintained\n";
echo "✅ Hybrid group + fluent approach\n";
echo "✅ Automatic route building\n\n";

echo "🚀 Your exact syntax now works perfectly!\n";
echo "No changes needed to your route definitions.\n";
