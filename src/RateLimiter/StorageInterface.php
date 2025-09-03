<?php

namespace FASTAPI\RateLimiter;

/**
 * Interface for rate limiting storage backends
 */
interface StorageInterface
{
    /**
     * Get current count for a key within time window
     */
    public function getCurrentCount(string $key, int $timeWindow): int;

    /**
     * Increment count for a key
     */
    public function incrementCount(string $key, int $timeWindow): bool;

    /**
     * Reset count for a key
     */
    public function reset(string $key): bool;

    /**
     * Get detailed info for a key
     */
    public function getInfo(string $key, int $timeWindow): array;
}
