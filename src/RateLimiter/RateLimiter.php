<?php

namespace FASTAPI\RateLimiter;

use FASTAPI\Response;

/**
 * Flexible Rate Limiter with multiple storage backends
 * Priority: Redis > Database > File
 */
class RateLimiter
{
    private static $instance = null;

    /** @var array Rate limiting configuration */
    private $config = [
        'max_requests' => 100,
        'time_window' => 60,
        'storage_priority' => ['redis', 'database', 'file']
    ];

    /** @var array Storage backends */
    private $storages = [];

    /** @var string Current active storage backend */
    private $activeStorage = 'file';

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        $this->initializeStorages();
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Retrieves the singleton instance of the RateLimiter class.
     *
     * @return RateLimiter The singleton instance.
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize available storage backends
     */
    private function initializeStorages()
    {
        // Try Redis first
        if ($this->isRedisAvailable()) {
            $this->storages['redis'] = new RedisStorage();
            $this->activeStorage = 'redis';
        }

        // Try Database second
        if ($this->isDatabaseAvailable()) {
            $this->storages['database'] = new DatabaseStorage();
            if ($this->activeStorage === 'file') {
                $this->activeStorage = 'database';
            }
        }

        // File storage as fallback
        $this->storages['file'] = new FileStorage();
    }

    /**
     * Check if Redis is available
     */
    private function isRedisAvailable(): bool
    {
        try {
            if (!extension_loaded('redis')) {
                return false;
            }

            $redis = new \Redis();
            return $redis->connect(
                $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                $_ENV['REDIS_PORT'] ?? 6379,
                $_ENV['REDIS_TIMEOUT'] ?? 1.0
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if database is available
     */
    private function isDatabaseAvailable(): bool
    {
        try {
            // Check for common database extensions and environment variables
            $hasPdo = extension_loaded('pdo');
            $hasMysql = extension_loaded('pdo_mysql');
            $hasPostgres = extension_loaded('pdo_pgsql');
            $hasSqlite = extension_loaded('pdo_sqlite');
            
            $hasDbConfig = isset($_ENV['DB_HOST']) || isset($_ENV['DATABASE_URL']);
            
            return ($hasPdo && ($hasMysql || $hasPostgres || $hasSqlite)) && $hasDbConfig;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Configure rate limiting
     */
    public function configure(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Check if request is rate limited
     */
    public function isLimited(string $key): bool
    {
        $storage = $this->storages[$this->activeStorage];
        
        try {
            $currentCount = $storage->getCurrentCount($key, $this->config['time_window']);
            
            if ($currentCount >= $this->config['max_requests']) {
                return true;
            }
            
            // Increment count
            $storage->incrementCount($key, $this->config['time_window']);
            return false;
            
        } catch (\Exception $e) {
            // Fallback to next available storage
            return $this->fallbackStorage($key);
        }
    }

    /**
     * Fallback to next available storage
     */
    private function fallbackStorage(string $key): bool
    {
        $fallbackOrder = ['redis', 'database', 'file'];
        $currentIndex = array_search($this->activeStorage, $fallbackOrder);
        
        for ($i = $currentIndex + 1; $i < count($fallbackOrder); $i++) {
            $storageType = $fallbackOrder[$i];
            
            if (isset($this->storages[$storageType])) {
                try {
                    $this->activeStorage = $storageType;
                    $storage = $this->storages[$storageType];
                    
                    $currentCount = $storage->getCurrentCount($key, $this->config['time_window']);
                    
                    if ($currentCount >= $this->config['max_requests']) {
                        return true;
                    }
                    
                    $storage->incrementCount($key, $this->config['time_window']);
                    return false;
                    
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        
        // If all storages fail, allow the request (fail open)
        return false;
    }

    /**
     * Get current storage backend
     */
    public function getActiveStorage(): string
    {
        return $this->activeStorage;
    }

    /**
     * Get available storage backends
     */
    public function getAvailableStorages(): array
    {
        return array_keys($this->storages);
    }

    /**
     * Reset rate limit for a specific key
     */
    public function reset(string $key): bool
    {
        $storage = $this->storages[$this->activeStorage];
        
        try {
            return $storage->reset($key);
        } catch (\Exception $e) {
            // Try fallback storages
            foreach ($this->storages as $storageType => $storageInstance) {
                if ($storageType !== $this->activeStorage) {
                    try {
                        return $storageInstance->reset($key);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Get rate limit info for a key
     */
    public function getInfo(string $key): array
    {
        $storage = $this->storages[$this->activeStorage];
        
        try {
            return $storage->getInfo($key, $this->config['time_window']);
        } catch (\Exception $e) {
            // Try fallback storages
            foreach ($this->storages as $storageType => $storageInstance) {
                if ($storageType !== $this->activeStorage) {
                    try {
                        return $storageInstance->getInfo($key, $this->config['time_window']);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }
        
        return [
            'count' => 0,
            'remaining' => $this->config['max_requests'],
            'reset_time' => time() + $this->config['time_window'],
            'storage' => 'none'
        ];
    }
}
