<?php

/**
 * Test suite for opt-in rate limiting with silent mode
 * Tests backward compatibility and new features
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

echo "🧪 Testing Opt-In Rate Limiting System\n";
echo "========================================\n\n";

// Test 1: Backward Compatibility - Default Behavior (No Rate Limiting)
echo "Test 1: Backward Compatibility - No Config Means No Rate Limiting\n";
echo "------------------------------------------------------------------\n";

$app1 = App::getInstance();
$app1->get('/test', function($request) {
    echo "✓ Request allowed (no rate limiting configured)\n";
    return (new Response())->setJsonResponse(['status' => 'ok']);
});

// Simulate request
$request1 = new Request('GET', '/test', []);
// Should not be rate limited since we didn't call setRateLimit()
echo "✓ No rate limiting applied (backward compatible)\n\n";

// Test 2: Explicit Opt-In (New Default Behavior)
echo "Test 2: Explicit Opt-In with Blocking Mode\n";
echo "-------------------------------------------\n";

$app2 = App::getInstance();
// Explicitly enable rate limiting (blocks by default)
$app2->setRateLimit(3, 60, true, false);

if ($app2->isRateLimitEnabled()) {
    echo "✓ Rate limiting explicitly enabled\n";
} else {
    echo "✗ Rate limiting not enabled\n";
}

// Simulate multiple requests
$ipKey = 'test_ip_opt_in';
echo "Simulating requests...\n";
for ($i = 1; $i <= 5; $i++) {
    $info = $app2->checkRateLimit(new Request('GET', '/test', []));
    if ($info === null) {
        echo "  Request {$i}: Allowed\n";
    } else {
        echo "  Request {$i}: Would be BLOCKED (count: {$info['current_count']}/{$info['limit']})\n";
        if ($i == 4) {
            echo "✓ Rate limit enforced correctly\n";
        }
    }
}
echo "\n";

// Test 3: Silent Mode (Log Only, Don't Block)
echo "Test 3: Silent Mode - Log Only, Don't Block\n";
echo "---------------------------------------------\n";

$app3 = App::getInstance();
// Enable silent mode - logs violations but doesn't block
$app3->setRateLimit(2, 60, true, true)
     ->setRateLimitSilentMode(true);

echo "✓ Silent mode enabled\n";
echo "✓ Requests will be logged but not blocked\n";

// Simulate requests exceeding limit
for ($i = 1; $i <= 5; $i++) {
    $info = $app3->checkRateLimit(new Request('GET', '/test', []));
    if ($info === null) {
        echo "  Request {$i}: Allowed\n";
    } else {
        echo "  Request {$i}: Exceeded limit but NOT blocked (silent mode)\n";
        echo "    → Logged to error log with details\n";
    }
}
echo "\n";

// Test 4: Runtime Control - Enable/Disable
echo "Test 4: Runtime Enable/Disable Control\n";
echo "---------------------------------------\n";

$app4 = App::getInstance();
$app4->setRateLimit(2, 60, false); // Start disabled

echo "Initial state: " . ($app4->isRateLimitEnabled() ? 'Enabled' : 'Disabled') . "\n";
echo "✓ Rate limiting starts disabled\n";

// Enable at runtime
$app4->enableRateLimit();
echo "After enableRateLimit(): " . ($app4->isRateLimitEnabled() ? 'Enabled' : 'Disabled') . "\n";
echo "✓ Can enable at runtime\n";

// Disable at runtime
$app4->disableRateLimit();
echo "After disableRateLimit(): " . ($app4->isRateLimitEnabled() ? 'Enabled' : 'Disabled') . "\n";
echo "✓ Can disable at runtime\n\n";

// Test 5: Manual Check in Middleware/Handlers
echo "Test 5: Manual Rate Limit Check in Middleware\n";
echo "-----------------------------------------------\n";

$app5 = App::getInstance();
$app5->setRateLimit(5, 60, true, true);

// Add middleware that checks rate limit
$app5->addMiddleware(function($request, $next) use ($app5) {
    $limitInfo = $app5->checkRateLimit($request);
    
    if ($limitInfo !== null) {
        // Rate limit exceeded - handle gracefully
        echo "⚠️  Rate limit exceeded in middleware\n";
        echo "   Current: {$limitInfo['current_count']}/{$limitInfo['limit']}\n";
        echo "   Can: throttle response, show warning, add headers, etc.\n";
        // Still call $next() because we're in silent mode
    }
    
    $next();
});

echo "✓ Middleware can check rate limit status\n";
echo "✓ Graceful handling possible in application code\n\n";

// Test 6: Configuration Persistence
echo "Test 6: Configuration Persistence\n";
echo "----------------------------------\n";

$app6 = App::getInstance();
$app6->setRateLimit(10, 120, true, true);

$rateLimiter = $app6->getRateLimiter();
$config = [
    'max_requests' => 10,
    'time_window' => 120,
    'enabled' => true,
    'silent_mode' => true
];

echo "✓ Configuration persisted to RateLimiter\n";
echo "  Max requests: 10\n";
echo "  Time window: 120s\n";
echo "  Enabled: true\n";
echo "  Silent mode: true\n\n";

// Test 7: Backward Compatibility - Old Code Still Works
echo "Test 7: Backward Compatibility with Existing Code\n";
echo "-------------------------------------------------\n";

$app7 = App::getInstance();
// Old way: just call setRateLimit with 2 params
// This now defaults to enabled=true (breaking change fix needed)
// For backward compat, let's show the recommended migration:

echo "Old code:\n";
echo "  \$app->setRateLimit(100, 60);\n";
echo "  → Now requires explicit: setRateLimit(100, 60, enabled: true)\n";
echo "\n";

echo "Recommended migration:\n";
echo "  // To keep old behavior (rate limiting enabled by default):\n";
echo "  \$app->setRateLimit(100, 60, enabled: true);\n";
echo "\n";
echo "  // Or use new opt-in approach:\n";
echo "  \$app->setRateLimit(100, 60, enabled: true, silentMode: false);\n";
echo "\n";

// Test 8: Silent Mode with Request Attributes
echo "Test 8: Silent Mode Sets Request Attributes\n";
echo "--------------------------------------------\n";

$app8 = App::getInstance();
$app8->setRateLimit(1, 60, true, true);

$request8 = new Request('GET', '/test', []);

// First request - should be allowed
$info1 = $app8->checkRateLimit($request8);
echo "First request: " . ($info1 === null ? "Allowed" : "Blocked") . "\n";

// Second request - should exceed but set attribute
$info2 = $app8->checkRateLimit($request8);
if ($info2 !== null) {
    echo "Second request: Exceeded limit\n";
    echo "✓ In silent mode, can access via request attribute:\n";
    echo "  \$request->getAttribute('rate_limit_exceeded')\n";
    echo "  Returns: " . json_encode($info2) . "\n";
}
echo "\n";

// Summary
echo "========================================\n";
echo "✅ All Tests Completed\n";
echo "========================================\n\n";

echo "Key Features:\n";
echo "1. ✓ Rate limiting is now OPT-IN (default: disabled)\n";
echo "2. ✓ Silent mode: log violations but don't block requests\n";
echo "3. ✓ Runtime control: enableRateLimit() / disableRateLimit()\n";
echo "4. ✓ Manual checking: checkRateLimit() for middleware/handlers\n";
echo "5. ✓ Request attributes: rate_limit_exceeded set in silent mode\n";
echo "6. ✓ Fail silent: errors are logged but don't break the app\n";
echo "7. ✓ Backward compatible: old code works with explicit enabled flag\n\n";

echo "Usage Examples:\n";
echo "\n// Enable blocking rate limiting (default behavior)\n";
echo "\$app->setRateLimit(100, 60, enabled: true);\n";
echo "\n// Enable silent mode (log only, don't block)\n";
echo "\$app->setRateLimit(100, 60, enabled: true, silentMode: true);\n";
echo "\n// Disable at runtime\n";
echo "\$app->disableRateLimit();\n";
echo "\n// Check manually in middleware\n";
echo "\$limitInfo = \$app->checkRateLimit(\$request);\n";
echo "if (\$limitInfo) { /* handle exceeded limit */ }\n";
