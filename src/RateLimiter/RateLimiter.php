<?php

namespace FASTAPI\RateLimiter;

/**
 * Main Rate Limiter class with automatic storage fallback
 * Automatically falls back between Redis → Database → Memory → File storage
 */
class RateLimiter
{
    private static $instance = null;
    private $storages = [];
    private $activeStorage = null;
    private $fallbackOrder = ['redis', 'database', 'memory', 'file'];
    private $config = [];

    private function __construct()
    {
        $this->initializeStorages();
        $this->selectActiveStorage();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize all available storage backends
     */
    private function initializeStorages(): void
    {
        try {
            // Initialize Redis storage
            if (extension_loaded('redis') || class_exists('\Predis\Client')) {
                $this->storages['redis'] = new RedisStorage();
            }

            // Initialize Database storage
            if (extension_loaded('pdo')) {
                $this->storages['database'] = new DatabaseStorage();
            }

            // Initialize Memory storage
            $this->storages['memory'] = new MemoryStorage();

            // Initialize File storage (always available as fallback)
            $this->storages['file'] = new FileStorage();

        } catch (\Exception $e) {
            error_log("Failed to initialize rate limiter storages: " . $e->getMessage());
        }
    }

    /**
     * Select the best available storage backend
     */
    private function selectActiveStorage(): void
    {
        foreach ($this->fallbackOrder as $storageType) {
            if (isset($this->storages[$storageType]) && $this->storages[$storageType]->isAvailable()) {
                $this->activeStorage = $storageType;
                error_log("Rate limiter using {$storageType} storage");
                break;
            }
        }

        if ($this->activeStorage === null) {
            // Fallback to file storage if nothing else works
            $this->activeStorage = 'file';
            error_log("Rate limiter falling back to file storage");
        }
    }

    /**
     * Configure rate limiter
     */
    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        
        // Re-select storage if configuration changed
        if (isset($config['storage_priority'])) {
            $this->fallbackOrder = $config['storage_priority'];
            $this->selectActiveStorage();
        }
    }

    /**
     * Check if a request is rate limited
     */
    public function isLimited(string $key, ?int $maxRequests = null, ?int $timeWindow = null): bool
    {
        $maxRequests = $maxRequests ?? $this->config['max_requests'] ?? 100;
        $timeWindow = $timeWindow ?? $this->config['time_window'] ?? 60;

        try {
            // Try active storage first
            if ($this->activeStorage && isset($this->storages[$this->activeStorage])) {
                $result = $this->storages[$this->activeStorage]->isLimited($key, $maxRequests, $timeWindow);
                if ($result !== null) {
                    return $result;
                }
            }

            // Fallback to next available storage
            return $this->fallbackStorage($key, $maxRequests, $timeWindow);

        } catch (\Exception $e) {
            error_log("Rate limiting failed: " . $e->getMessage());
            return $this->fallbackStorage($key, $maxRequests, $timeWindow);
        }
    }

    /**
     * Fallback to next available storage
     */
    private function fallbackStorage(string $key, int $maxRequests, int $timeWindow): bool
    {
        foreach ($this->fallbackOrder as $storageType) {
            if ($storageType === $this->activeStorage) {
                continue;
            }

            if (isset($this->storages[$storageType]) && $this->storages[$storageType]->isAvailable()) {
                try {
                    $result = $this->storages[$storageType]->isLimited($key, $maxRequests, $timeWindow);
                    if ($result !== null) {
                        // Switch to this working storage
                        $this->activeStorage = $storageType;
                        error_log("Rate limiter switched to {$storageType} storage");
                        return $result;
                    }
                } catch (\Exception $e) {
                    error_log("Storage {$storageType} failed during fallback: " . $e->getMessage());
                    continue;
                }
            }
        }

        // If all storages fail, allow the request (fail open)
        error_log("All rate limiting storages failed, allowing request");
        return false;
    }

    /**
     * Get current count for a key
     */
    public function getCurrentCount(string $key, int $timeWindow = null): int
    {
        $timeWindow = $timeWindow ?? $this->config['time_window'] ?? 60;

        if ($this->activeStorage && isset($this->storages[$this->activeStorage])) {
            try {
                return $this->storages[$this->activeStorage]->getCurrentCount($key, $timeWindow);
            } catch (\Exception $e) {
                error_log("Active storage getCurrentCount failed: " . $e->getMessage());
            }
        }

        return $this->fallbackGetCurrentCount($key, $timeWindow);
    }

    /**
     * Fallback getCurrentCount
     */
    private function fallbackGetCurrentCount(string $key, int $timeWindow): int
    {
        foreach ($this->fallbackOrder as $storageType) {
            if (isset($this->storages[$storageType]) && $this->storages[$storageType]->isAvailable()) {
                try {
                    return $this->storages[$storageType]->getCurrentCount($key, $timeWindow);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return 0;
    }

    /**
     * Reset rate limit for a key
     */
    public function reset(string $key): bool
    {
        $success = false;

        foreach ($this->storages as $storageType => $storage) {
            try {
                if ($storage->isAvailable()) {
                    if ($storage->reset($key)) {
                        $success = true;
                    }
                }
            } catch (\Exception $e) {
                error_log("Failed to reset rate limit in {$storageType}: " . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * Get rate limit information
     */
    public function getInfo(string $key, int $timeWindow = null): array
    {
        $timeWindow = $timeWindow ?? $this->config['time_window'] ?? 60;

        if ($this->activeStorage && isset($this->storages[$this->activeStorage])) {
            try {
                $info = $this->storages[$this->activeStorage]->getInfo($key, $timeWindow);
                $info['active_storage'] = $this->activeStorage;
                return $info;
            } catch (\Exception $e) {
                error_log("Active storage getInfo failed: " . $e->getMessage());
            }
        }

        return $this->fallbackGetInfo($key, $timeWindow);
    }

    /**
     * Fallback getInfo
     */
    private function fallbackGetInfo(string $key, int $timeWindow): array
    {
        foreach ($this->fallbackOrder as $storageType) {
            if (isset($this->storages[$storageType]) && $this->storages[$storageType]->isAvailable()) {
                try {
                    $info = $this->storages[$storageType]->getInfo($key, $timeWindow);
                    $info['active_storage'] = $storageType;
                    return $info;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return [
            'count' => 0,
            'remaining' => 100,
            'reset_time' => time() + 60,
            'storage' => 'unknown',
            'active_storage' => 'none',
            'ttl' => 60
        ];
    }

    /**
     * Get TTL for a key
     */
    public function getTTL(string $key): ?int
    {
        if ($this->activeStorage && isset($this->storages[$this->activeStorage])) {
            try {
                return $this->storages[$this->activeStorage]->getTTL($key);
            } catch (\Exception $e) {
                error_log("Active storage getTTL failed: " . $e->getMessage());
            }
        }

        return $this->fallbackGetTTL($key);
    }

    /**
     * Fallback getTTL
     */
    private function fallbackGetTTL(string $key): ?int
    {
        foreach ($this->fallbackOrder as $storageType) {
            if (isset($this->storages[$storageType]) && $this->storages[$storageType]->isAvailable()) {
                try {
                    return $this->storages[$storageType]->getTTL($key);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return null;
    }

    /**
     * Get active storage type
     */
    public function getActiveStorage(): string
    {
        return $this->activeStorage ?? 'none';
    }

    /**
     * Get available storage types
     */
    public function getAvailableStorages(): array
    {
        $available = [];
        foreach ($this->storages as $type => $storage) {
            $available[$type] = $storage->isAvailable();
        }
        return $available;
    }

    /**
     * Get storage status
     */
    public function getStorageStatus(): array
    {
        $status = [];
        foreach ($this->storages as $type => $storage) {
            $status[$type] = [
                'available' => $storage->isAvailable(),
                'active' => $type === $this->activeStorage,
                'working' => $storage->test()
            ];
        }
        return $status;
    }

    /**
     * Force fallback to next available storage
     */
    public function forceFallback(): void
    {
        $currentIndex = array_search($this->activeStorage, $this->fallbackOrder);
        
        for ($i = $currentIndex + 1; $i < count($this->fallbackOrder); $i++) {
            $storageType = $this->fallbackOrder[$i];
            
            if (isset($this->storages[$storageType]) && $this->storages[$storageType]->isAvailable()) {
                try {
                    // Test the storage
                    $testKey = 'test_' . uniqid();
                    $this->storages[$storageType]->isLimited($testKey, 1, 60);
                    
                    // If we get here, storage is working
                    $this->activeStorage = $storageType;
                    error_log("Rate limiter forced fallback to {$storageType}");
                    return;
                } catch (\Exception $e) {
                    error_log("Storage {$storageType} failed during forced fallback: " . $e->getMessage());
                    continue;
                }
            }
        }
        
        error_log("No working storage found for forced fallback");
    }

    /**
     * Test all storage backends
     */
    public function testAllStorages(): array
    {
        $results = [];
        foreach ($this->storages as $type => $storage) {
            try {
                $results[$type] = [
                    'available' => $storage->isAvailable(),
                    'test' => $storage->test(),
                    'error' => null
                ];
            } catch (\Exception $e) {
                $results[$type] = [
                    'available' => false,
                    'test' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        return $results;
    }

    /**
     * Clean up all storages
     */
    public function cleanup(): void
    {
        foreach ($this->storages as $storage) {
            try {
                if (method_exists($storage, 'cleanup')) {
                    $storage->cleanup();
                }
            } catch (\Exception $e) {
                error_log("Storage cleanup failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Get memory statistics (if using memory storage)
     */
    public function getMemoryStats(): ?array
    {
        if (isset($this->storages['memory'])) {
            try {
                return $this->storages['memory']->getMemoryStats();
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Clear all memory storage
     */
    public function clearMemory(): void
    {
        if (isset($this->storages['memory'])) {
            try {
                $this->storages['memory']->clearAll();
            } catch (\Exception $e) {
                error_log("Failed to clear memory storage: " . $e->getMessage());
            }
        }
    }
}
