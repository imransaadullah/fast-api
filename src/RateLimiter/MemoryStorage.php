<?php

namespace FASTAPI\RateLimiter;

/**
 * Memory-based rate limiting storage
 * Fast in-memory storage with automatic cleanup
 */
class MemoryStorage implements StorageInterface
{
    private static $storage = [];
    private static $lastCleanup = 0;
    private $available = false;

    public function __construct()
    {
        $this->available = $this->test();
    }

    /**
     * Check if memory storage is available and working
     */
    public function isAvailable(): bool
    {
        return $this->available && $this->test();
    }

    /**
     * Test memory storage functionality
     */
    public function test(): bool
    {
        try {
            // Test if we can store and retrieve data
            $testKey = 'test_' . uniqid();
            $testData = [time()];
            
            self::$storage[$testKey] = $testData;
            $retrieved = self::$storage[$testKey];
            unset(self::$storage[$testKey]);
            
            return $retrieved === $testData;
        } catch (\Exception $e) {
            error_log("Memory storage test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current count for a key within time window
     */
    public function getCurrentCount(string $key, int $timeWindow): int
    {
        try {
            if (!$this->isAvailable()) {
                return 0;
            }

            $this->cleanup();
            
            if (!isset(self::$storage[$key])) {
                return 0;
            }

            $currentTime = time();
            $windowStart = $currentTime - $timeWindow;

            // Filter entries within time window
            $validEntries = array_filter(self::$storage[$key], function($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            });

            return count($validEntries);
        } catch (\Exception $e) {
            error_log("Memory storage getCurrentCount failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Increment count for a key
     */
    public function incrementCount(string $key, int $timeWindow): bool
    {
        try {
            if (!$this->isAvailable()) {
                return false;
            }

            $this->cleanup();
            $currentTime = time();

            if (!isset(self::$storage[$key])) {
                self::$storage[$key] = [];
            }

            // Add current timestamp
            self::$storage[$key][] = $currentTime;

            // Clean old entries for this key
            $windowStart = $currentTime - $timeWindow;
            self::$storage[$key] = array_filter(self::$storage[$key], function($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            });

            return true;
        } catch (\Exception $e) {
            error_log("Memory storage incrementCount failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a key is rate limited
     */
    public function isLimited(string $key, int $maxRequests, int $timeWindow): bool
    {
        try {
            if (!$this->isAvailable()) {
                return false;
            }

            $currentCount = $this->getCurrentCount($key, $timeWindow);

            if ($currentCount >= $maxRequests) {
                return true;
            }

            // Increment count
            return $this->incrementCount($key, $timeWindow);
        } catch (\Exception $e) {
            error_log("Memory storage isLimited failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset rate limit for a key
     */
    public function reset(string $key): bool
    {
        try {
            if (!$this->isAvailable()) {
                return false;
            }

            if (isset(self::$storage[$key])) {
                unset(self::$storage[$key]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Memory storage reset failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get rate limit information for a key
     */
    public function getInfo(string $key, int $timeWindow): array
    {
        try {
            if (!$this->isAvailable()) {
                return $this->getDefaultInfo();
            }

            $this->cleanup();
            
            $currentCount = $this->getCurrentCount($key, $timeWindow);
            $ttl = $this->getTTL($key);

            return [
                'count' => $currentCount,
                'remaining' => max(0, 100 - $currentCount),
                'reset_time' => time() + $timeWindow,
                'storage' => 'memory',
                'ttl' => $ttl ?: $timeWindow
            ];
        } catch (\Exception $e) {
            error_log("Memory storage getInfo failed: " . $e->getMessage());
            return $this->getDefaultInfo();
        }
    }

    /**
     * Get TTL for a key
     */
    public function getTTL(string $key): ?int
    {
        try {
            if (!$this->isAvailable()) {
                return null;
            }

            if (!isset(self::$storage[$key]) || empty(self::$storage[$key])) {
                return null;
            }

            // Get oldest timestamp
            $oldestTimestamp = min(self::$storage[$key]);
            $elapsed = time() - $oldestTimestamp;
            
            return max(0, 60 - $elapsed);
        } catch (\Exception $e) {
            error_log("Memory storage getTTL failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean up old entries
     */
    private function cleanup(): void
    {
        $currentTime = time();
        
        // Only cleanup every 60 seconds to avoid performance impact
        if ($currentTime - self::$lastCleanup < 60) {
            return;
        }

        try {
            $cleaned = false;
            $cutoffTime = $currentTime - 3600; // Remove entries older than 1 hour

            foreach (self::$storage as $key => $timestamps) {
                $originalCount = count($timestamps);
                
                // Remove old timestamps
                $timestamps = array_filter($timestamps, function($timestamp) use ($cutoffTime) {
                    return $timestamp > $cutoffTime;
                });

                if (count($timestamps) !== $originalCount) {
                    if (empty($timestamps)) {
                        unset(self::$storage[$key]);
                    } else {
                        self::$storage[$key] = $timestamps;
                    }
                    $cleaned = true;
                }
            }

            if ($cleaned) {
                self::$storage = array_filter(self::$storage, function($timestamps) {
                    return !empty($timestamps);
                });
            }

            self::$lastCleanup = $currentTime;

        } catch (\Exception $e) {
            error_log("Memory storage cleanup failed: " . $e->getMessage());
        }
    }

    /**
     * Get default info when memory storage is unavailable
     */
    private function getDefaultInfo(): array
    {
        return [
            'count' => 0,
            'remaining' => 100,
            'reset_time' => time() + 60,
            'storage' => 'memory',
            'ttl' => 60
        ];
    }

    /**
     * Get memory usage statistics
     */
    public static function getMemoryStats(): array
    {
        $totalKeys = count(self::$storage);
        $totalTimestamps = 0;
        
        foreach (self::$storage as $timestamps) {
            $totalTimestamps += count($timestamps);
        }

        return [
            'total_keys' => $totalKeys,
            'total_timestamps' => $totalTimestamps,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }

    /**
     * Clear all memory storage
     */
    public static function clearAll(): void
    {
        self::$storage = [];
        self::$lastCleanup = 0;
    }
}
