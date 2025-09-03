<?php

namespace FASTAPI\RateLimiter;

/**
 * Database storage backend for rate limiting
 */
class DatabaseStorage implements StorageInterface
{
    /** @var \PDO|null */
    private $pdo;

    /** @var string */
    private $tableName = 'rate_limits';

    public function __construct()
    {
        $this->connect();
    }

    /**
     * Connect to database
     */
    private function connect(): bool
    {
        try {
            $dsn = $_ENV['DATABASE_URL'] ?? $this->buildDsn();
            
            if (!$dsn) {
                throw new \Exception('No database configuration found');
            }

            $this->pdo = new \PDO($dsn, 
                $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? '', 
                $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $this->createTable();
            return true;

        } catch (\Exception $e) {
            $this->pdo = null;
            return false;
        }
    }

    /**
     * Build DSN from environment variables
     */
    private function buildDsn(): ?string
    {
        $host = $_ENV['DB_HOST'] ?? null;
        $port = $_ENV['DB_PORT'] ?? null;
        $database = $_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? null;
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        if (!$host || !$database) {
            return null;
        }

        switch ($driver) {
            case 'mysql':
                $port = $port ?: 3306;
                return "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            
            case 'pgsql':
                $port = $port ?: 5432;
                return "pgsql:host={$host};port={$port};dbname={$database}";
            
            case 'sqlite':
                return "sqlite:{$database}";
            
            default:
                return null;
        }
    }

    /**
     * Create rate limits table if it doesn't exist
     */
    private function createTable(): void
    {
        if (!$this->pdo) {
            return;
        }

        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        switch ($driver) {
            case 'mysql':
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    `key` VARCHAR(255) NOT NULL,
                    `timestamp` BIGINT NOT NULL,
                    `count` INT DEFAULT 1,
                    PRIMARY KEY (`key`, `timestamp`),
                    INDEX `idx_key_time` (`key`, `timestamp`)
                ) ENGINE=InnoDB";
                break;
            
            case 'pgsql':
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    \"key\" VARCHAR(255) NOT NULL,
                    timestamp BIGINT NOT NULL,
                    count INTEGER DEFAULT 1,
                    PRIMARY KEY (\"key\", timestamp)
                )";
                break;
            
            case 'sqlite':
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    \"key\" TEXT NOT NULL,
                    timestamp INTEGER NOT NULL,
                    count INTEGER DEFAULT 1,
                    PRIMARY KEY (\"key\", timestamp)
                )";
                break;
            
            default:
                return;
        }

        $this->pdo->exec($sql);
    }

    /**
     * Get current count for a key within time window
     */
    public function getCurrentCount(string $key, int $timeWindow): int
    {
        if (!$this->pdo) {
            throw new \Exception('Database not available');
        }

        $currentTime = time();
        $windowStart = $currentTime - $timeWindow;

        // Clean expired entries
        $this->cleanExpiredEntries($key, $windowStart);

        // Get current count
        $stmt = $this->pdo->prepare("
            SELECT SUM(count) as total 
            FROM {$this->tableName} 
            WHERE `key` = ? AND timestamp > ?
        ");
        
        $stmt->execute([$key, $windowStart]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int)($result['total'] ?? 0);
    }

    /**
     * Increment count for a key
     */
    public function incrementCount(string $key, int $timeWindow): bool
    {
        if (!$this->pdo) {
            throw new \Exception('Database not available');
        }

        $currentTime = time();
        $windowStart = $currentTime - $timeWindow;

        // Clean expired entries first
        $this->cleanExpiredEntries($key, $windowStart);

        // Try to update existing entry for current minute
        $minuteTimestamp = floor($currentTime / 60) * 60;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->tableName} (`key`, timestamp, count) 
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE count = count + 1
        ");

        try {
            return $stmt->execute([$key, $minuteTimestamp]);
        } catch (\PDOException $e) {
            // Handle non-MySQL databases
            if (strpos($e->getMessage(), 'ON DUPLICATE KEY') !== false) {
                return $this->incrementCountFallback($key, $minuteTimestamp);
            }
            throw $e;
        }
    }

    /**
     * Fallback increment for non-MySQL databases
     */
    private function incrementCountFallback(string $key, int $timestamp): bool
    {
        // Check if entry exists
        $stmt = $this->pdo->prepare("
            SELECT count FROM {$this->tableName} 
            WHERE `key` = ? AND timestamp = ?
        ");
        $stmt->execute([$key, $timestamp]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result) {
            // Update existing
            $stmt = $this->pdo->prepare("
                UPDATE {$this->tableName} 
                SET count = count + 1 
                WHERE `key` = ? AND timestamp = ?
            ");
            return $stmt->execute([$key, $timestamp]);
        } else {
            // Insert new
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tableName} (`key`, timestamp, count) 
                VALUES (?, ?, 1)
            ");
            return $stmt->execute([$key, $timestamp]);
        }
    }

    /**
     * Clean expired entries
     */
    private function cleanExpiredEntries(string $key, int $windowStart): void
    {
        if (!$this->pdo) {
            return;
        }

        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->tableName} 
            WHERE `key` = ? AND timestamp <= ?
        ");
        $stmt->execute([$key, $windowStart]);
    }

    /**
     * Reset count for a key
     */
    public function reset(string $key): bool
    {
        if (!$this->pdo) {
            throw new \Exception('Database not available');
        }

        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE `key` = ?");
        return $stmt->execute([$key]);
    }

    /**
     * Get detailed info for a key
     */
    public function getInfo(string $key, int $timeWindow): array
    {
        if (!$this->pdo) {
            throw new \Exception('Database not available');
        }

        $currentTime = time();
        $windowStart = $currentTime - $timeWindow;

        // Clean expired entries
        $this->cleanExpiredEntries($key, $windowStart);

        // Get count and oldest timestamp
        $stmt = $this->pdo->prepare("
            SELECT SUM(count) as total, MIN(timestamp) as oldest 
            FROM {$this->tableName} 
            WHERE `key` = ? AND timestamp > ?
        ");
        
        $stmt->execute([$key, $windowStart]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $count = (int)($result['total'] ?? 0);
        $oldestTimestamp = (int)($result['oldest'] ?? $currentTime);
        $remaining = max(0, ($_ENV['RATE_LIMIT_MAX'] ?? 100) - $count);
        $resetTime = $oldestTimestamp + $timeWindow;

        return [
            'count' => $count,
            'remaining' => $remaining,
            'reset_time' => $resetTime,
            'storage' => 'database'
        ];
    }
}
