<?php

require_once '../src/Router.php';
require_once '../src/Request.php';
require_once '../src/Response.php';
require_once '../src/Middlewares/MiddlewareInterface.php';

use FASTAPI\Router;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\Middlewares\MiddlewareInterface;

class TestMiddleware implements MiddlewareInterface {
    private $name;
    
    public function __construct($name) {
        $this->name = $name;
    }
    
    public function handle(Request $request, \Closure $next): void {
        echo "Middleware {$this->name} - Before\n";
        $next();
        echo "Middleware {$this->name} - After\n";
    }
}

// Test 1: Basic route group functionality
function testBasicRouteGroup() {
    echo "\n=== Test 1: Basic Route Group ===\n";
    
    $router = new Router();
    
    $router->group(['prefix' => 'api'], function($router) {
        $router->get('/test', function($request) {
            echo "Handler: API test endpoint\n";
        });
    });
    
    $routes = $router->getRoutes();
    $testRoute = $routes[0];
    
    // Verify the route was registered with correct prefix
    assert($testRoute['uri'] === '/api/test', 'Route URI should be /api/test');
    assert($testRoute['method'] === 'GET', 'Route method should be GET');
    
    echo "✓ Basic route group test passed\n";
}

// Test 2: Nested route groups
function testNestedRouteGroups() {
    echo "\n=== Test 2: Nested Route Groups ===\n";
    
    $router = new Router();
    
    $router->group(['prefix' => 'api'], function($router) {
        $router->group(['prefix' => 'v1'], function($router) {
            $router->get('/users', function($request) {
                echo "Handler: API v1 users endpoint\n";
            });
        });
    });
    
    $routes = $router->getRoutes();
    $testRoute = $routes[0];
    
    // Verify nested prefixes are combined
    assert($testRoute['uri'] === '/api/v1/users', 'Nested route URI should be /api/v1/users');
    
    echo "✓ Nested route groups test passed\n";
}

// Test 3: Middleware inheritance
function testMiddlewareInheritance() {
    echo "\n=== Test 3: Middleware Inheritance ===\n";
    
    $router = new Router();
    $middleware1 = new TestMiddleware('Level1');
    $middleware2 = new TestMiddleware('Level2');
    
    $router->group(['middleware' => [$middleware1]], function($router) use ($middleware2) {
        $router->group(['middleware' => [$middleware2]], function($router) {
            $router->get('/protected', function($request) {
                echo "Handler: Protected endpoint\n";
            });
        });
    });
    
    $routes = $router->getRoutes();
    $testRoute = $routes[0];
    
    // Verify middleware inheritance
    assert(count($testRoute['middleware']) === 2, 'Route should have 2 middleware instances');
    assert($testRoute['middleware'][0] === $middleware1, 'First middleware should be Level1');
    assert($testRoute['middleware'][1] === $middleware2, 'Second middleware should be Level2');
    
    echo "✓ Middleware inheritance test passed\n";
}

// Test 4: Convenience methods
function testConvenienceMethods() {
    echo "\n=== Test 4: Convenience Methods ===\n";
    
    $router = new Router();
    
    $router->group(['prefix' => 'api'], function($router) {
        $router->get('/get-test', function($request) {});
        $router->post('/post-test', function($request) {});
        $router->put('/put-test', function($request) {});
        $router->delete('/delete-test', function($request) {});
        $router->patch('/patch-test', function($request) {});
        $router->options('/options-test', function($request) {});
    });
    
    $routes = $router->getRoutes();
    
    // Verify all methods are registered correctly
    assert($routes[0]['method'] === 'GET', 'First route should be GET');
    assert($routes[1]['method'] === 'POST', 'Second route should be POST');
    assert($routes[2]['method'] === 'PUT', 'Third route should be PUT');
    assert($routes[3]['method'] === 'DELETE', 'Fourth route should be DELETE');
    assert($routes[4]['method'] === 'PATCH', 'Fifth route should be PATCH');
    assert($routes[5]['method'] === 'OPTIONS', 'Sixth route should be OPTIONS');
    
    echo "✓ Convenience methods test passed\n";
}

// Test 5: Fluent API
function testFluentAPI() {
    echo "\n=== Test 5: Fluent API ===\n";
    
    $router = new Router();
    $middleware = new TestMiddleware('Fluent');
    
    $router->prefix('fluent')->middleware([$middleware])->group([], function($router) {
        $router->get('/test', function($request) {
            echo "Handler: Fluent API test\n";
        });
    });
    
    $routes = $router->getRoutes();
    $testRoute = $routes[0];
    
    // Verify fluent API works
    assert($testRoute['uri'] === '/fluent/test', 'Fluent route URI should be /fluent/test');
    assert(count($testRoute['middleware']) === 1, 'Fluent route should have 1 middleware');
    
    echo "✓ Fluent API test passed\n";
}

// Test 6: Route parameter handling
function testRouteParameters() {
    echo "\n=== Test 6: Route Parameters ===\n";
    
    $router = new Router();
    
    $router->group(['prefix' => 'api'], function($router) {
        $router->get('/users/:id', function($request, $id) {
            echo "Handler: User ID = $id\n";
        });
    });
    
    $routes = $router->getRoutes();
    $testRoute = $routes[0];
    
    // Verify route parameters are preserved
    assert($testRoute['uri'] === '/api/users/:id', 'Route with parameters should be preserved');
    
    echo "✓ Route parameters test passed\n";
}

// Run all tests
echo "Starting Route Groups Tests...\n";

try {
    testBasicRouteGroup();
    testNestedRouteGroups();
    testMiddlewareInheritance();
    testConvenienceMethods();
    testFluentAPI();
    testRouteParameters();
    
    echo "\n🎉 All tests passed! Route groups functionality is working correctly.\n";
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (AssertionError $e) {
    echo "\n❌ Assertion failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\nTest Summary:\n";
echo "- Basic route groups with prefixes\n";
echo "- Nested route groups with prefix inheritance\n";
echo "- Middleware inheritance in nested groups\n";
echo "- HTTP convenience methods (GET, POST, PUT, DELETE, PATCH, OPTIONS)\n";
echo "- Fluent API for method chaining\n";
echo "- Route parameter preservation\n"; 