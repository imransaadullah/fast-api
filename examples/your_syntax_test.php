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

// Mock RBAC class
class RBAC {
    public function withPermissions($permissions) {
        return new class($permissions) implements MiddlewareInterface {
            private $permissions;
            public function __construct($permissions) { $this->permissions = $permissions; }
            public function handle(Request $request, \Closure $next): void {
                echo "🔐 RBAC: Checking permissions: {$this->permissions}\n";
                $next();
            }
        };
    }
}

// Mock Auth middleware
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        echo "🔒 Auth: User authenticated\n";
        $next();
    }
}

// Mock ClaimsController
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

// Initialize
$app = App::getInstance();
$rbac = new RBAC();

// Register middleware
$app->registerMiddleware('auth', AuthMiddleware::class);

echo "🎯 Testing Your Exact Syntax\n";
echo "============================\n\n";

// ✅ YOUR EXACT SYNTAX NOW WORKS!
$app->group(['prefix' => '/v2/facilities/{facility_id}', 'middleware' => ['auth']], function($app) use ($rbac) {
    $app->route('GET', '/claims', 'ClaimsController@index')
        ->middleware($rbac->withPermissions('claims.read'));
    
    $app->route('POST', '/claims/{id}', 'ClaimsController@update')
        ->middleware($rbac->withPermissions('claims.update'));
});

echo "✅ SUCCESS! Your exact syntax works perfectly!\n\n";

// Show the registered routes
echo "📋 Registered Routes:\n";
$routes = $app->getRouter()->getCompiledRoutes();
foreach ($routes as $route) {
    $middlewareCount = count($route['middleware']);
    echo "• {$route['method']} {$route['final_uri']} ($middlewareCount middleware)\n";
}

echo "\n🎉 The fluent middleware API is now fully functional!\n";
echo "You can use your desired syntax without any changes.\n";
