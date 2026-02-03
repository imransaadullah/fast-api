<?php

/**
 * Example: Practical Rate Limiting Usage
 * 
 * This example demonstrates how to use the new opt-in rate limiting system
 * with different scenarios and best practices.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\Middlewares\MiddlewareInterface;

echo "🚀 Rate Limiting - Practical Examples\n";
echo "======================================\n\n";

// ============================================
// Example 1: Production API with Blocking
// ============================================
echo "Example 1: Production API with Blocking Mode\n";
echo "---------------------------------------------\n";

$app1 = new App();

// Enable rate limiting - block when exceeded
$app1->setRateLimit(100, 60, true, false); // 100 requests per minute, blocking

$app1->get('/api/users', function($request) {
    return (new Response())->setJsonResponse(['users' => []]);
});

$app1->post('/api/users', function($request) {
    return (new Response())->setJsonResponse(['created' => true]);
});

echo "✓ API protected: Blocks with 429 when limit exceeded\n";
echo "✓ Default behavior for production APIs\n\n";

// ============================================
// Example 2: Development with Silent Mode
// ============================================
echo "Example 2: Development with Silent Mode\n";
echo "----------------------------------------\n";

$app2 = new App();

// Enable silent mode - log but don't block (great for development)
$app2->setRateLimit(10, 60, true, true); // Silent mode enabled

$app2->get('/dev/data', function($request) {
    // In silent mode, you can still check if limit was exceeded
    $limitInfo = $request->getAttribute('rate_limit_exceeded');
    if ($limitInfo) {
        // Add warning header but still respond
        return (new Response())
            ->setHeader('X-RateLimit-Exceeded', 'true')
            ->setHeader('X-RateLimit-Count', $limitInfo['current_count'])
            ->setJsonResponse([
                'data' => ['sample' => 'data'],
                'warning' => 'Rate limit exceeded but request allowed (dev mode)'
            ]);
    }
    return (new Response())->setJsonResponse(['data' => ['sample' => 'data']]);
});

echo "✓ Development mode: Logs violations but doesn't block\n";
echo "✓ Great for testing without interruptions\n\n";

// ============================================
// Example 3: Gradual Rollout with Runtime Control
// ============================================
echo "Example 3: Gradual Rollout with Runtime Control\n";
echo "-----------------------------------------------\n";

$app3 = new App();

// Start with rate limiting disabled
$app3->setRateLimit(50, 60, false, false); // Disabled initially

// Later, enable it based on feature flag, environment, etc.
$enableRateLimit = $_ENV['ENABLE_RATE_LIMIT'] ?? false;
if ($enableRateLimit) {
    $app3->enableRateLimit();
    echo "✓ Rate limiting enabled via environment variable\n";
} else {
    echo "✓ Rate limiting disabled (safe default)\n";
}

// Can also enable/disable based on time, traffic, etc.
if (date('H') >= 9 && date('H') <= 17) {
    // Business hours - stricter limits
    $app3->getRateLimiter()->configure(['max_requests' => 30, 'time_window' => 60]);
    echo "✓ Stricter limits during business hours\n";
} else {
    // Off hours - relaxed limits
    $app3->getRateLimiter()->configure(['max_requests' => 100, 'time_window' => 60]);
    echo "✓ Relaxed limits during off hours\n";
}

echo "\n";

// ============================================
// Example 4: Custom Middleware with Graceful Handling
// ============================================
echo "Example 4: Custom Middleware with Graceful Handling\n";
echo "---------------------------------------------------\n";

class GracefulRateLimitMiddleware implements MiddlewareInterface
{
    private $app;
    
    public function __construct(App $app)
    {
        $this->app = $app;
    }
    
    public function handle(\FASTAPI\Request $request, \Closure $next): void
    {
        // Check rate limit manually
        $limitInfo = $this->app->checkRateLimit($request);
        
        if ($limitInfo) {
            // Custom handling - don't block, just add headers
            $request->setAttribute('rate_limit_warning', true);
            
            // Could also: 
            // - Add user-facing warning in response
            // - Queue request for later processing
            // - Throttle response time
            // - Send alert to monitoring
        }
        
        $next();
    }
}

$app4 = new App();
$app4->setRateLimit(5, 60, true, true); // Silent mode
$app4->addMiddleware(new GracefulRateLimitMiddleware($app4));

$app4->get('/api/search', function($request) {
    $warning = $request->getAttribute('rate_limit_warning');
    $response = new Response();
    
    if ($warning) {
        $response->setHeader('X-RateLimit-Warning', 'true');
        return $response->setJsonResponse([
            'results' => [],
            'warning' => 'Rate limit approaching. Please slow down.'
        ]);
    }
    
    return $response->setJsonResponse(['results' => ['item1', 'item2']]);
});

echo "✓ Custom middleware checks rate limit\n";
echo "✓ Graceful degradation instead of hard blocks\n";
echo "✓ User-friendly warnings\n\n";

// ============================================
// Example 5: Per-Route Rate Limiting
// ============================================
echo "Example 5: Selective Rate Limiting by Route\n";
echo "--------------------------------------------\n";

$app5 = new App();

// Global rate limiting (silent mode)
$app5->setRateLimit(100, 60, true, true);

// Public endpoints - use global silent rate limiting
$app5->get('/api/status', function($request) {
    return (new Response())->setJsonResponse(['status' => 'ok']);
});

// Expensive endpoints - add custom middleware with stricter limits
class StrictRateLimitMiddleware implements MiddlewareInterface
{
    private $app;
    private $maxRequests;
    private $timeWindow;
    
    public function __construct(App $app, int $maxRequests, int $timeWindow)
    {
        $this->app = $app;
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function handle(\FASTAPI\Request $request, \Closure $next): void
    {
        // Custom key for this expensive endpoint
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = "rate_limit:expensive:{$ip}";
        
        $rateLimiter = $this->app->getRateLimiter();
        $isLimited = $rateLimiter->isLimited($key, $this->maxRequests, $this->timeWindow);
        
        if ($isLimited) {
            // Block with custom message
            (new Response())->setJsonResponse([
                'error' => 'Too many requests on expensive endpoint',
                'limit' => $this->maxRequests,
                'window' => $this->timeWindow
            ], 429)->send();
            return;
        }
        
        $next();
    }
}

$app5->addMiddleware(
    new StrictRateLimitMiddleware($app5, 10, 60), // 10 requests per minute
    function($request) use ($app5) {
        $app5->get('/api/expensive-report', function($request) {
            return (new Response())->setJsonResponse(['report' => 'data']);
        });
    }
);

echo "✓ Public endpoints: Global silent rate limiting\n";
echo "✓ Expensive endpoints: Custom strict rate limiting\n";
echo "✓ Different limits per route type\n\n";

// ============================================
// Example 6: Monitoring and Analytics
// ============================================
echo "Example 6: Rate Limit Monitoring\n";
echo "---------------------------------\n";

$app6 = new App();
$app6->setRateLimit(50, 60, true, true); // Silent mode for monitoring

// Middleware that tracks rate limit violations
class RateLimitMonitoringMiddleware implements MiddlewareInterface
{
    private $app;
    
    public function __construct(App $app)
    {
        $this->app = $app;
    }
    
    public function handle(\FASTAPI\Request $request, \Closure $next): void
    {
        $limitInfo = $this->app->checkRateLimit($request);
        
        if ($limitInfo) {
            // Log to monitoring system
            $logData = [
                'timestamp' => date('c'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'endpoint' => $request->getUri(),
                'current_count' => $limitInfo['current_count'],
                'limit' => $limitInfo['limit'],
                'storage' => $limitInfo['storage']
            ];
            
            error_log("[RATE LIMIT MONITOR] " . json_encode($logData));
            
            // Could send to: Prometheus, DataDog, CloudWatch, etc.
            // Could trigger alerts for abuse detection
        }
        
        $next();
    }
}

$app6->addMiddleware(new RateLimitMonitoringMiddleware($app6));

$app6->get('/api/analytics', function($request) {
    return (new Response())->setJsonResponse(['data' => 'analytics']);
});

echo "✓ Rate limit violations tracked\n";
echo "✓ Can integrate with monitoring systems\n";
echo "✓ Abuse detection capabilities\n\n";

// ============================================
// Summary and Best Practices
// ============================================
echo "========================================\n";
echo "📋 Best Practices Summary\n";
echo "========================================\n\n";

echo "1. Development Environment:\n";
echo "   → Use silent mode: setRateLimit(100, 60, true, true)\n";
echo "   → Logs violations but doesn't block development\n\n";

echo "2. Production API:\n";
echo "   → Use blocking mode: setRateLimit(100, 60, true, false)\n";
echo "   → Hard blocks protect against abuse\n\n";

echo "3. Gradual Rollout:\n";
echo "   → Start disabled: setRateLimit(100, 60, false)\n";
echo "   → Enable later: enableRateLimit() when ready\n\n";

echo "4. Custom Handling:\n";
echo "   → Use checkRateLimit() in middleware\n";
echo "   → Implement custom logic (throttle, warn, queue)\n\n";

echo "5. Monitoring:\n";
echo "   → Silent mode + monitoring middleware\n";
echo "   → Track violations without blocking\n";
echo "   → Build analytics and abuse detection\n\n";

echo "6. Safe Defaults:\n";
echo "   → Rate limiting is OPT-IN (disabled by default)\n";
echo "   → Explicitly enable when needed\n";
echo "   → No breaking changes to existing apps\n\n";

echo "✅ Rate limiting is now flexible and safe!\n";
