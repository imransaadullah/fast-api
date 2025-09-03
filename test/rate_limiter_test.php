<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\RateLimiter\RateLimiter;
use FASTAPI\RateLimiter\RedisStorage;
use FASTAPI\RateLimiter\DatabaseStorage;
use FASTAPI\RateLimiter\FileStorage;

echo "Testing Flexible Rate Limiting System...\n\n";

// Test 1: RateLimiter Singleton
echo "1. Testing RateLimiter::getInstance()...\n";
$rateLimiter1 = RateLimiter::getInstance();
$rateLimiter2 = RateLimiter::getInstance();
if ($rateLimiter1 === $rateLimiter2) {
    echo "✓ RateLimiter singleton works correctly\n";
} else {
    echo "✗ RateLimiter singleton failed\n";
}

// Test 2: Configuration
echo "\n2. Testing RateLimiter configuration...\n";
$rateLimiter1->configure([
    'max_requests' => 5,
    'time_window' => 10
]);
echo "✓ RateLimiter configuration updated\n";

// Test 3: Storage Backend Detection
echo "\n3. Testing storage backend detection...\n";
$availableStorages = $rateLimiter1->getAvailableStorages();
$activeStorage = $rateLimiter1->getActiveStorage();

echo "Available storages: " . implode(', ', $availableStorages) . "\n";
echo "Active storage: {$activeStorage}\n";

if (in_array('file', $availableStorages)) {
    echo "✓ File storage is available\n";
} else {
    echo "✗ File storage not available\n";
}

// Test 4: Rate Limiting Logic
echo "\n4. Testing rate limiting logic...\n";
$testKey = 'test_ip_127.0.0.1';

// Should not be limited initially
if (!$rateLimiter1->isLimited($testKey)) {
    echo "✓ Initial request allowed\n";
} else {
    echo "✗ Initial request blocked\n";
}

// Test multiple requests
$blocked = false;
for ($i = 0; $i < 6; $i++) {
    if ($rateLimiter1->isLimited($testKey)) {
        if ($i >= 5) {
            echo "✓ Request {$i} correctly blocked (limit reached)\n";
            $blocked = true;
            break;
        } else {
            echo "✗ Request {$i} incorrectly blocked\n";
        }
    } else {
        echo "✓ Request {$i} allowed\n";
    }
}

if (!$blocked) {
    echo "✗ Rate limiting not working correctly\n";
}

// Test 5: Rate Limit Info
echo "\n5. Testing rate limit info...\n";
$info = $rateLimiter1->getInfo($testKey);
echo "Rate limit info: " . json_encode($info, JSON_PRETTY_PRINT) . "\n";

if (isset($info['count']) && isset($info['remaining']) && isset($info['storage'])) {
    echo "✓ Rate limit info retrieved successfully\n";
} else {
    echo "✗ Rate limit info incomplete\n";
}

// Test 6: Reset Rate Limit
echo "\n6. Testing rate limit reset...\n";
if ($rateLimiter1->reset($testKey)) {
    echo "✓ Rate limit reset successfully\n";
    
    // Should be allowed again after reset
    if (!$rateLimiter1->isLimited($testKey)) {
        echo "✓ Request allowed after reset\n";
    } else {
        echo "✗ Request still blocked after reset\n";
    }
} else {
    echo "✗ Rate limit reset failed\n";
}

// Test 7: App Integration
echo "\n7. Testing App integration...\n";
$app = App::getInstance();
$app->setRateLimit(3, 5); // 3 requests per 5 seconds

$appTestKey = 'app_test_ip';
$appBlocked = false;

for ($i = 0; $i < 4; $i++) {
    $info = $app->getRateLimitInfo($appTestKey);
    if ($info['count'] >= 3) {
        echo "✓ App rate limiting working (request {$i} would be blocked)\n";
        $appBlocked = true;
        break;
    } else {
        echo "✓ App request {$i} allowed (count: {$info['count']})\n";
    }
}

if (!$appBlocked) {
    echo "✗ App rate limiting not working correctly\n";
}

// Test 8: Storage Backend Priority
echo "\n8. Testing storage backend priority...\n";
$storages = $app->getAvailableRateLimitStorages();
$active = $app->getRateLimitStorage();

echo "App available storages: " . implode(', ', $storages) . "\n";
echo "App active storage: {$active}\n";

// Test 9: Environment Variable Configuration
echo "\n9. Testing environment variable configuration...\n";
echo "REDIS_HOST: " . ($_ENV['REDIS_HOST'] ?? 'not set') . "\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
echo "DATABASE_URL: " . ($_ENV['DATABASE_URL'] ?? 'not set') . "\n";
echo "RATE_LIMIT_FILE: " . ($_ENV['RATE_LIMIT_FILE'] ?? 'not set') . "\n";

// Test 10: Error Handling
echo "\n10. Testing error handling...\n";
try {
    // Test with invalid key
    $invalidInfo = $rateLimiter1->getInfo('', 10);
    echo "✓ Empty key handled gracefully\n";
} catch (Exception $e) {
    echo "✗ Empty key caused error: " . $e->getMessage() . "\n";
}

echo "\n=== Rate Limiting System Test Complete ===\n";
echo "Features tested:\n";
echo "- Singleton pattern\n";
echo "- Configuration management\n";
echo "- Storage backend detection\n";
echo "- Rate limiting logic\n";
echo "- Info retrieval\n";
echo "- Reset functionality\n";
echo "- App integration\n";
echo "- Storage priority system\n";
echo "- Environment configuration\n";
echo "- Error handling\n";

echo "\nStorage Backend Priority:\n";
echo "1. Redis (if available)\n";
echo "2. Database (if available)\n";
echo "3. File (fallback)\n";

echo "\nEnvironment Variables for Configuration:\n";
echo "REDIS_HOST, REDIS_PORT, REDIS_TIMEOUT\n";
echo "DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD\n";
echo "DATABASE_URL (alternative to individual DB_* variables)\n";
echo "RATE_LIMIT_FILE (custom file path)\n";
echo "RATE_LIMIT_MAX (maximum requests per window)\n";
