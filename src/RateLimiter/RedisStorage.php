<?php

namespace FASTAPI\RateLimiter;

/**
 * Redis-based rate limiting storage
 * High-performance in-memory storage with automatic expiration
 */
class RedisStorage implements StorageInterface
{
    /** @var \Redis|null */
    private $redis;
    private $prefix = 'rate_limit:';
    private $connected = false;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * Check if Redis is available and working
     */
    public function isAvailable(): bool
    {
        if (!$this->connected) {
            $this->connect();
        }
        return $this->connected && $this->test();
    }

    /**
     * Test Redis connection
     */
    public function test(): bool
    {
        try {
            if (!$this->redis) {
                return false;
            }
            return $this->redis->ping() === '+PONG';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Connect to Redis
     */
    private function connect(): void
    {
        try {
            if (!class_exists('\Redis')) {
                $this->connected = false;
                return;
            }

            $this->redis = new \Redis();
            $this->connected = $this->redis->connect(
                $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                $_ENV['REDIS_PORT'] ?? 6379,
                $_ENV['REDIS_TIMEOUT'] ?? 1.0
            );

            if ($this->connected && isset($_ENV['REDIS_PASSWORD'])) {
                $this->redis->auth($_ENV['REDIS_PASSWORD']);
            }

            if ($this->connected && isset($_ENV['REDIS_DATABASE'])) {
                $this->redis->select($_ENV['REDIS_DATABASE']);
            }

        } catch (\Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            $this->connected = false;
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

            $fullKey = $this->prefix . $key;
            $currentTime = time();
            $windowStart = $currentTime - $timeWindow;

            // Use Redis sorted set for precise time-based counting
            $this->redis->zremrangebyscore($fullKey, 0, $windowStart);
            
            return $this->redis->zcard($fullKey);
        } catch (\Exception $e) {
            error_log("Redis getCurrentCount failed: " . $e->getMessage());
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

            $fullKey = $this->prefix . $key;
            $currentTime = time();

            // Add current timestamp to sorted set
            $this->redis->zadd($fullKey, $currentTime, $currentTime . '_' . uniqid());
            
            // Set expiration on the key
            $this->redis->expire($fullKey, $timeWindow);

            return true;
        } catch (\Exception $e) {
            error_log("Redis incrementCount failed: " . $e->getMessage());
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

            $fullKey = $this->prefix . $key;
            $currentTime = time();
            $windowStart = $currentTime - $timeWindow;

            // Clean expired entries
            $this->redis->zremrangebyscore($fullKey, 0, $windowStart);
            
            // Get current count
            $currentCount = $this->redis->zcard($fullKey);

            if ($currentCount >= $maxRequests) {
                return true;
            }

            // Increment count
            $this->redis->zadd($fullKey, $currentTime, $currentTime . '_' . uniqid());
            $this->redis->expire($fullKey, $timeWindow);

            return false;
        } catch (\Exception $e) {
            error_log("Redis isLimited failed: " . $e->getMessage());
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

            $fullKey = $this->prefix . $key;
            return $this->redis->del($fullKey) > 0;
        } catch (\Exception $e) {
            error_log("Redis reset failed: " . $e->getMessage());
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

            $fullKey = $this->prefix . $key;
            $currentTime = time();
            $windowStart = $currentTime - $timeWindow;

            // Clean expired entries
            $this->redis->zremrangebyscore($fullKey, 0, $windowStart);
            
            $currentCount = $this->redis->zcard($fullKey);
            $ttl = $this->redis->ttl($fullKey);

            return [
                'count' => $currentCount,
                'remaining' => max(0, 100 - $currentCount),
                'reset_time' => $currentTime + $timeWindow,
                'storage' => 'redis',
                'ttl' => $ttl > 0 ? $ttl : $timeWindow
            ];
        } catch (\Exception $e) {
            error_log("Redis getInfo failed: " . $e->getMessage());
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

            $fullKey = $this->prefix . $key;
            $ttl = $this->redis->ttl($fullKey);
            return $ttl > 0 ? $ttl : null;
        } catch (\Exception $e) {
            error_log("Redis getTTL failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get default info when Redis is unavailable
     */
    private function getDefaultInfo(): array
    {
        return [
            'count' => 0,
            'remaining' => 100,
            'reset_time' => time() + 60,
            'storage' => 'redis',
            'ttl' => 60
        ];
    }
}
