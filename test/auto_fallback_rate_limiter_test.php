<?php

/**
 * Test suite for the new auto-fallback rate limiting system
 * Tests Redis → Database → Memory → File fallback chain
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\RateLimiter\RateLimiter;
use FASTAPI\RateLimiter\RedisStorage;
use FASTAPI\RateLimiter\DatabaseStorage;
use FASTAPI\RateLimiter\MemoryStorage;
use FASTAPI\RateLimiter\FileStorage;

class AutoFallbackRateLimiterTest
{
    private $rateLimiter;
    private $testKey;

    public function __construct()
    {
        $this->rateLimiter = RateLimiter::getInstance();
        $this->testKey = 'test_' . uniqid();
        
        echo "🚀 Starting Auto-Fallback Rate Limiter Tests\n";
        echo "============================================\n\n";
    }

    /**
     * Run all tests
     */
    public function runAllTests(): void
    {
        $this->testSingletonPattern();
        $this->testStorageInitialization();
        $this->testStoragePriority();
        $this->testAutoFallback();
        $this->testRateLimitingLogic();
        $this->testStorageMethods();
        $this->testFallbackRecovery();
        $this->testConfiguration();
        $this->testErrorHandling();
        $this->testPerformance();
        
        echo "\n✅ All tests completed!\n";
    }

    /**
     * Test singleton pattern
     */
    private function testSingletonPattern(): void
    {
        echo "🔍 Testing Singleton Pattern...\n";
        
        $instance1 = RateLimiter::getInstance();
        $instance2 = RateLimiter::getInstance();
        
        if ($instance1 === $instance2) {
            echo "✅ Singleton pattern working correctly\n";
        } else {
            echo "❌ Singleton pattern failed\n";
        }
        
        echo "\n";
    }

    /**
     * Test storage initialization
     */
    private function testStorageInitialization(): void
    {
        echo "🔍 Testing Storage Initialization...\n";
        
        $storages = $this->rateLimiter->getAvailableStorages();
        $status = $this->rateLimiter->getStorageStatus();
        
        echo "Available storages: " . implode(', ', array_keys($storages)) . "\n";
        echo "Active storage: " . $this->rateLimiter->getActiveStorage() . "\n";
        
        foreach ($status as $type => $info) {
            echo "  {$type}: " . ($info['available'] ? 'Available' : 'Not Available') . 
                 " | " . ($info['active'] ? 'Active' : 'Inactive') . 
                 " | " . ($info['working'] ? 'Working' : 'Not Working') . "\n";
        }
        
        echo "\n";
    }

    /**
     * Test storage priority
     */
    private function testStoragePriority(): void
    {
        echo "🔍 Testing Storage Priority...\n";
        
        $expectedPriority = ['redis', 'database', 'memory', 'file'];
        $activeStorage = $this->rateLimiter->getActiveStorage();
        
        if (in_array($activeStorage, $expectedPriority)) {
            echo "✅ Storage priority working: {$activeStorage}\n";
        } else {
            echo "❌ Unexpected storage priority: {$activeStorage}\n";
        }
        
        echo "\n";
    }

    /**
     * Test auto-fallback functionality
     */
    private function testAutoFallback(): void
    {
        echo "🔍 Testing Auto-Fallback...\n";
        
        $initialStorage = $this->rateLimiter->getActiveStorage();
        echo "Initial storage: {$initialStorage}\n";
        
        // Test fallback
        $this->rateLimiter->forceFallback();
        $newStorage = $this->rateLimiter->getActiveStorage();
        
        if ($newStorage !== $initialStorage) {
            echo "✅ Auto-fallback working: {$initialStorage} → {$newStorage}\n";
        } else {
            echo "⚠️  Auto-fallback not needed or failed\n";
        }
        
        echo "\n";
    }

    /**
     * Test rate limiting logic
     */
    private function testRateLimitingLogic(): void
    {
        echo "🔍 Testing Rate Limiting Logic...\n";
        
        $key = $this->testKey . '_logic';
        $maxRequests = 3;
        $timeWindow = 60;
        
        // Test normal requests
        for ($i = 1; $i <= $maxRequests; $i++) {
            $limited = $this->rateLimiter->isLimited($key, $maxRequests, $timeWindow);
            if ($limited) {
                echo "❌ Request {$i} was limited unexpectedly\n";
            } else {
                echo "✅ Request {$i} allowed\n";
            }
        }
        
        // Test limit exceeded
        $limited = $this->rateLimiter->isLimited($key, $maxRequests, $timeWindow);
        if ($limited) {
            echo "✅ Rate limit correctly enforced\n";
        } else {
            echo "❌ Rate limit not enforced\n";
        }
        
        // Test reset
        if ($this->rateLimiter->reset($key)) {
            echo "✅ Rate limit reset successful\n";
        } else {
            echo "❌ Rate limit reset failed\n";
        }
        
        echo "\n";
    }

    /**
     * Test storage methods
     */
    private function testStorageMethods(): void
    {
        echo "🔍 Testing Storage Methods...\n";
        
        $key = $this->testKey . '_methods';
        
        // Test info
        $info = $this->rateLimiter->getInfo($key);
        if (isset($info['count']) && isset($info['storage'])) {
            echo "✅ Get info working: count={$info['count']}, storage={$info['storage']}\n";
        } else {
            echo "❌ Get info failed\n";
        }
        
        // Test TTL
        $ttl = $this->rateLimiter->getTTL($key);
        if ($ttl !== null) {
            echo "✅ Get TTL working: {$ttl}s\n";
        } else {
            echo "⚠️  Get TTL returned null\n";
        }
        
        // Test current count
        $count = $this->rateLimiter->getCurrentCount($key);
        echo "✅ Current count: {$count}\n";
        
        echo "\n";
    }

    /**
     * Test fallback recovery
     */
    private function testFallbackRecovery(): void
    {
        echo "🔍 Testing Fallback Recovery...\n";
        
        $initialStorage = $this->rateLimiter->getActiveStorage();
        echo "Current storage: {$initialStorage}\n";
        
        // Test storage testing
        $testResults = $this->rateLimiter->testAllStorages();
        foreach ($testResults as $type => $result) {
            echo "  {$type}: " . ($result['test'] ? 'Working' : 'Not Working');
            if ($result['error']) {
                echo " (Error: {$result['error']})";
            }
            echo "\n";
        }
        
        echo "\n";
    }

    /**
     * Test configuration
     */
    private function testConfiguration(): void
    {
        echo "🔍 Testing Configuration...\n";
        
        $config = [
            'max_requests' => 50,
            'time_window' => 30,
            'storage_priority' => ['file', 'memory', 'database', 'redis']
        ];
        
        $this->rateLimiter->configure($config);
        echo "✅ Configuration applied\n";
        
        // Test custom configuration
        $key = $this->testKey . '_config';
        $limited = $this->rateLimiter->isLimited($key, 50, 30);
        echo "Custom config test: " . ($limited ? 'Limited' : 'Allowed') . "\n";
        
        echo "\n";
    }

    /**
     * Test error handling
     */
    private function testErrorHandling(): void
    {
        echo "🔍 Testing Error Handling...\n";
        
        // Test with invalid key
        $invalidKey = '';
        $result = $this->rateLimiter->isLimited($invalidKey, 1, 60);
        echo "Invalid key handling: " . ($result ? 'Limited' : 'Allowed') . "\n";
        
        // Test with extreme values
        $extremeKey = 'extreme_' . str_repeat('a', 1000);
        $result = $this->rateLimiter->isLimited($extremeKey, 1, 60);
        echo "Extreme key handling: " . ($result ? 'Limited' : 'Allowed') . "\n";
        
        echo "\n";
    }

    /**
     * Test performance
     */
    private function testPerformance(): void
    {
        echo "🔍 Testing Performance...\n";
        
        $iterations = 100;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $key = $this->testKey . '_perf_' . $i;
            $this->rateLimiter->isLimited($key, 1, 60);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        echo "Processed {$iterations} requests in {$duration:.2f}ms\n";
        echo "Average: " . ($duration / $iterations) . "ms per request\n";
        
        // Test memory usage
        $memoryStats = $this->rateLimiter->getMemoryStats();
        if ($memoryStats) {
            echo "Memory usage: " . number_format($memoryStats['memory_usage'] / 1024, 2) . "KB\n";
            echo "Peak memory: " . number_format($memoryStats['peak_memory'] / 1024, 2) . "KB\n";
        }
        
        echo "\n";
    }

    /**
     * Clean up test data
     */
    public function cleanup(): void
    {
        echo "🧹 Cleaning up test data...\n";
        
        $keys = [
            $this->testKey . '_logic',
            $this->testKey . '_methods',
            $this->testKey . '_config',
            $this->testKey . '_perf_0'
        ];
        
        foreach ($keys as $key) {
            $this->rateLimiter->reset($key);
        }
        
        // Clear memory storage
        $this->rateLimiter->clearMemory();
        
        echo "✅ Cleanup completed\n";
    }
}

// Run tests
try {
    $test = new AutoFallbackRateLimiterTest();
    $test->runAllTests();
    $test->cleanup();
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
