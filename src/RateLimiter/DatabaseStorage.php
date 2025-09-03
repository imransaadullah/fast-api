<?php

namespace FASTAPI\RateLimiter;

/**
 * Database-based rate limiting storage
 * Persistent storage with automatic cleanup and transaction support
 */
class DatabaseStorage implements StorageInterface
{
    /** @var \PDO|null */
    private $pdo;
    private $tableName = 'rate_limits';
    private $connected = false;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * Check if database is available and working
     */
    public function isAvailable(): bool
    {
        if (!$this->connected) {
            $this->connect();
        }
        return $this->connected && $this->test();
    }

    /**
     * Test database connection
     */
    public function test(): bool
    {
        try {
            if (!$this->pdo) {
                return false;
            }
            $this->pdo->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Connect to database
     */
    private function connect(): void
    {
        try {
            if (!extension_loaded('pdo')) {
                $this->connected = false;
                return;
            }

            $dsn = $this->buildDSN();
            $this->pdo = new \PDO($dsn, 
                $_ENV['DB_USERNAME'] ?? 'root', 
                $_ENV['DB_PASSWORD'] ?? '');
            
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            
            $this->connected = true;
            $this->ensureTableExists();

        } catch (\Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->connected = false;
        }
    }

    /**
     * Build database DSN
     */
    private function buildDSN(): string
    {
        if (isset($_ENV['DATABASE_URL'])) {
            return $_ENV['DATABASE_URL'];
        }

        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '';
        $database = $_ENV['DB_DATABASE'] ?? 'test';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        switch ($driver) {
            case 'mysql':
                $port = $port ? ":{$port}" : '';
                return "mysql:host={$host}{$port};dbname={$database};charset={$charset}";
            
            case 'pgsql':
                $port = $port ? ";port={$port}" : '';
                return "pgsql:host={$host}{$port};dbname={$database}";
            
            case 'sqlite':
                return "sqlite:{$database}";
            
            default:
                return "mysql:host={$host};dbname={$database};charset={$charset}";
        }
    }

    /**
     * Ensure rate limits table exists
     */
    private function ensureTableExists(): void
    {
        try {
            $driver = $_ENV['DB_DRIVER'] ?? 'mysql';
            
            if ($driver === 'mysql') {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    `key` VARCHAR(255) NOT NULL,
                    count INT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_key_time (`key`, created_at),
                    INDEX idx_created_at (created_at)
                )";
            } elseif ($driver === 'pgsql') {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    id BIGSERIAL PRIMARY KEY,
                    \"key\" VARCHAR(255) NOT NULL,
                    count INTEGER DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT idx_key_time UNIQUE (\"key\", created_at)
                )";
                
                // Create indexes
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_key_time ON {$this->tableName} (\"key\", created_at)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON {$this->tableName} (created_at)");
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    `key` VARCHAR(255) NOT NULL,
                    count INTEGER DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                // Create indexes
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_key_time ON {$this->tableName} (`key`, created_at)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON {$this->tableName} (created_at)");
            }
            
            $this->pdo->exec($sql);
            
        } catch (\Exception $e) {
            error_log("Failed to create rate limits table: " . $e->getMessage());
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

            $this->cleanupExpired($timeWindow);
            
            $stmt = $this->pdo->prepare("
                SELECT SUM(count) as total 
                FROM {$this->tableName} 
                WHERE `key` = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            $stmt->execute([$key, $timeWindow]);
            $result = $stmt->fetch();
            
            return (int)($result['total'] ?? 0);
        } catch (\Exception $e) {
            error_log("Database getCurrentCount failed: " . $e->getMessage());
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

            $this->cleanupExpired($timeWindow);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tableName} (`key`, count, created_at) 
                VALUES (?, 1, NOW())
            ");
            
            return $stmt->execute([$key]);
        } catch (\Exception $e) {
            error_log("Database incrementCount failed: " . $e->getMessage());
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

            $this->cleanupExpired($timeWindow);
            
            $currentCount = $this->getCurrentCount($key, $timeWindow);
            
            if ($currentCount >= $maxRequests) {
                return true;
            }

            // Increment count
            return $this->incrementCount($key, $timeWindow);
        } catch (\Exception $e) {
            error_log("Database isLimited failed: " . $e->getMessage());
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

            $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE `key` = ?");
            return $stmt->execute([$key]);
        } catch (\Exception $e) {
            error_log("Database reset failed: " . $e->getMessage());
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

            $this->cleanupExpired($timeWindow);
            
            $currentCount = $this->getCurrentCount($key, $timeWindow);
            $ttl = $this->getTTL($key);

            return [
                'count' => $currentCount,
                'remaining' => max(0, 100 - $currentCount),
                'reset_time' => time() + $timeWindow,
                'storage' => 'database',
                'ttl' => $ttl ?: $timeWindow
            ];
        } catch (\Exception $e) {
            error_log("Database getInfo failed: " . $e->getMessage());
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

            $stmt = $this->pdo->prepare("
                SELECT created_at 
                FROM {$this->tableName} 
                WHERE `key` = ? 
                ORDER BY created_at ASC 
                LIMIT 1
            ");
            
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            if ($result && isset($result['created_at'])) {
                $createdTime = strtotime($result['created_at']);
                $elapsed = time() - $createdTime;
                return max(0, 60 - $elapsed);
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Database getTTL failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean up expired entries
     */
    private function cleanupExpired(int $timeWindow): void
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->tableName} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            $stmt->execute([$timeWindow]);
        } catch (\Exception $e) {
            error_log("Database cleanup failed: " . $e->getMessage());
        }
    }

    /**
     * Get default info when database is unavailable
     */
    private function getDefaultInfo(): array
    {
        return [
            'count' => 0,
            'remaining' => 100,
            'reset_time' => time() + 60,
            'storage' => 'database',
            'ttl' => 60
        ];
    }
}
