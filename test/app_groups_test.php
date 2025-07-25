<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;

// Test App-level route groups
function testAppRouteGroups() {
    echo "\n=== Testing App-Level Route Groups ===\n";
    
    $app = App::getInstance();
    
    // Test 1: Basic route group with prefix
    echo "\n--- Test 1: Basic Route Group with Prefix ---\n";
    
    $app->group(['prefix' => 'api'], function($app) {
        $app->get('/users', function($request) {
            echo "GET /api/users - Success\n";
            return ['message' => 'Users list'];
        });
        
        $app->post('/users', function($request) {
            echo "POST /api/users - Success\n";
            return ['message' => 'User created'];
        });
    });
    
    // Test 2: Nested route groups
    echo "\n--- Test 2: Nested Route Groups ---\n";
    
    $app->group(['prefix' => 'admin'], function($app) {
        $app->group(['prefix' => 'v1'], function($app) {
            $app->get('/dashboard', function($request) {
                echo "GET /admin/v1/dashboard - Success\n";
                return ['message' => 'Admin dashboard'];
            });
        });
    });
    
    // Test 3: Route group with middleware
    echo "\n--- Test 3: Route Group with Middleware ---\n";
    
    $authMiddleware = function($request, $next) {
        echo "Auth middleware executed\n";
        return $next($request);
    };
    
    $app->group(['prefix' => 'secure', 'middleware' => [$authMiddleware]], function($app) {
        $app->get('/profile', function($request) {
            echo "GET /secure/profile - Success\n";
            return ['message' => 'User profile'];
        });
    });
    
    // Display all routes
    echo "\n--- All Registered Routes ---\n";
    $routes = $app->getRoutes();
    foreach ($routes as $route) {
        echo "Method: {$route['method']}, URI: {$route['uri']}, Final URI: {$route['_final_uri']}\n";
    }
    
    echo "\n✅ App-level route groups test completed successfully!\n";
}

// Run the test
testAppRouteGroups(); 