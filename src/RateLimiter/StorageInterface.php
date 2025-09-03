<?php

namespace FASTAPI\RateLimiter;

/**
 * Interface for rate limiting storage backends
 * Supports automatic fallback between different storage types
 */
interface StorageInterface
{
    /**
     * Check if this storage backend is available and working
     */
    public function isAvailable(): bool;

    /**
     * Get current count for a specific key and time window
     */
    public function getCurrentCount(string $key, int $timeWindow): int;

    /**
     * Increment count for a specific key and time window
     */
    public function incrementCount(string $key, int $timeWindow): bool;

    /**
     * Reset rate limit for a specific key
     */
    public function reset(string $key): bool;

    /**
     * Get rate limit information for a specific key
     */
    public function getInfo(string $key, int $timeWindow): array;

    /**
     * Check if a key is rate limited
     */
    public function isLimited(string $key, int $maxRequests, int $timeWindow): bool;

    /**
     * Get TTL (time to live) for a key
     */
    public function getTTL(string $key): ?int;

    /**
     * Test the storage backend (for health checks)
     */
    public function test(): bool;
}
