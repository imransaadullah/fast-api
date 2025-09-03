<?php

namespace FASTAPI\RateLimiter;

/**
 * Redis storage backend for rate limiting
 */
class RedisStorage implements StorageInterface
{
    /** @var \Redis|null */
    private $redis;

    /** @var string */
    private $prefix = 'rate_limit:';

    public function __construct()
    {
        $this->connect();
    }

    /**
     * Connect to Redis
     */
    private function connect(): bool
    {
        try {
            $this->redis = new \Redis();
            return $this->redis->connect(
                $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                $_ENV['REDIS_PORT'] ?? 6379,
                $_ENV['REDIS_TIMEOUT'] ?? 1.0
            );
        } catch (\Exception $e) {
            $this->redis = null;
            return false;
        }
    }

    /**
     * Get current count for a key within time window
     */
    public function getCurrentCount(string $key, int $timeWindow): int
    {
        if (!$this->redis) {
            throw new \Exception('Redis not available');
        }

        $redisKey = $this->prefix . $key;
        $currentTime = time();
        $windowStart = $currentTime - $timeWindow;

        // Use Redis sorted set to track requests with timestamps
        $this->redis->zRemRangeByScore($redisKey, 0, $windowStart);
        
        return $this->redis->zCard($redisKey);
    }

    /**
     * Increment count for a key
     */
    public function incrementCount(string $key, int $timeWindow): bool
    {
        if (!$this->redis) {
            throw new \Exception('Redis not available');
        }

        $redisKey = $this->prefix . $key;
        $currentTime = time();
        $expireTime = $currentTime + $timeWindow;

        // Add current timestamp to sorted set
        $this->redis->zAdd($redisKey, $currentTime, $currentTime . ':' . uniqid());
        
        // Set expiration on the key
        $this->redis->expire($redisKey, $timeWindow);

        return true;
    }

    /**
     * Reset count for a key
     */
    public function reset(string $key): bool
    {
        if (!$this->redis) {
            throw new \Exception('Redis not available');
        }

        $redisKey = $this->prefix . $key;
        return $this->redis->del($redisKey) > 0;
    }

    /**
     * Get detailed info for a key
     */
    public function getInfo(string $key, int $timeWindow): array
    {
        if (!$this->redis) {
            throw new \Exception('Redis not available');
        }

        $redisKey = $this->prefix . $key;
        $currentTime = time();
        $windowStart = $currentTime - $timeWindow;

        // Clean expired entries
        $this->redis->zRemRangeByScore($redisKey, 0, $windowStart);
        
        $count = $this->redis->zCard($redisKey);
        $remaining = max(0, ($_ENV['RATE_LIMIT_MAX'] ?? 100) - $count);
        
        // Get oldest entry to calculate reset time
        $oldestEntry = $this->redis->zRange($redisKey, 0, 0, true);
        $resetTime = $oldestEntry ? array_values($oldestEntry)[0] + $timeWindow : $currentTime + $timeWindow;

        return [
            'count' => $count,
            'remaining' => $remaining,
            'reset_time' => $resetTime,
            'storage' => 'redis'
        ];
    }
}
