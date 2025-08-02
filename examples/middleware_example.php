<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Middlewares\MiddlewareInterface;
use FASTAPI\Request;

/**
 * Example Auth Middleware
 * Shows how to properly implement middleware for FastAPI
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): void
    {
        // Check if user is authenticated
        $token = $request->getHeader('Authorization');
        
        if (!$token || !$this->validateToken($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        // User is authenticated, continue to next middleware/route
        $next();
    }
    
    private function validateToken($token): bool
    {
        // Simple token validation example
        // In real applications, you would validate JWT tokens here
        return strpos($token, 'Bearer ') === 0;
    }
}

/**
 * Example Guest Middleware
 * Allows access only to non-authenticated users
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): void
    {
        $token = $request->getHeader('Authorization');
        
        if ($token && $this->validateToken($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Already authenticated']);
            return;
        }
        
        $next();
    }
    
    private function validateToken($token): bool
    {
        return strpos($token, 'Bearer ') === 0;
    }
}

/**
 * Example CORS Middleware
 * Handles Cross-Origin Resource Sharing
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): void
    {
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            http_response_code(200);
            return;
        }
        
        $next();
    }
}

/**
 * Example Throttle Middleware
 * Limits request frequency
 */
class ThrottleMiddleware implements MiddlewareInterface
{
    private $maxRequests;
    private $timeWindow;
    
    public function __construct(int $maxRequests = 60, int $timeWindow = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function handle(Request $request, \Closure $next): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "throttle:{$ip}";
        
        // Simple in-memory throttling (use Redis in production)
        $current = $this->getCurrentRequests($key);
        
        if ($current >= $this->maxRequests) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests']);
            return;
        }
        
        $this->incrementRequests($key);
        $next();
    }
    
    private function getCurrentRequests($key): int
    {
        // Simple implementation - use Redis in production
        return 0;
    }
    
    private function incrementRequests($key): void
    {
        // Simple implementation - use Redis in production
    }
}

/**
 * Example Role Middleware
 * Checks user roles
 */
class RoleMiddleware implements MiddlewareInterface
{
    private $requiredRole;
    
    public function __construct(string $role)
    {
        $this->requiredRole = $role;
    }
    
    public function handle(Request $request, \Closure $next): void
    {
        // Get user role from token (simplified)
        $token = $request->getHeader('Authorization');
        $userRole = $this->getUserRoleFromToken($token);
        
        if ($userRole !== $this->requiredRole) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            return;
        }
        
        $next();
    }
    
    private function getUserRoleFromToken($token): string
    {
        // Simplified - in real apps, decode JWT and get role
        return 'user';
    }
}

// Example usage
$app = App::getInstance();

// Register middleware
// Note: In a real application, you would register middleware through the Router
// This is just an example of how to implement middleware classes
echo "Middleware classes defined successfully!\n";
echo "To use these middleware classes, you need to register them with your Router.\n";
echo "Example:\n";
echo "\$app->router->registerMiddleware('auth', AuthMiddleware::class);\n";

// Routes with middleware
$app->get('/protected', function() {
    echo json_encode(['message' => 'Protected route accessed']);
});

$app->get('/login', function() {
    echo json_encode(['message' => 'Login page']);
});

$app->get('/api/data', function() {
    echo json_encode(['data' => 'Some data']);
});

$app->get('/admin', function() {
    echo json_encode(['message' => 'Admin panel']);
});

// Route groups with middleware
$app->group(['prefix' => 'api'], function($app) {
    $app->get('/users', function() {
        echo json_encode(['users' => []]);
    });
    
    $app->post('/users', function() {
        echo json_encode(['message' => 'User created']);
    });
});

echo "Example routes registered successfully!\n";
echo "Available routes:\n";
echo "- GET /protected\n";
echo "- GET /login\n";
echo "- GET /api/data\n";
echo "- GET /admin\n";
echo "- GET /api/users\n";
echo "- POST /api/users\n";
echo "\nTo use middleware, implement the MiddlewareInterface and register with your Router.\n"; 