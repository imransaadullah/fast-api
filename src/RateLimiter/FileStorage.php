<?php

namespace FASTAPI\RateLimiter;

/**
 * File-based rate limiting storage
 * Simple, reliable fallback storage with file locking
 */
class FileStorage implements StorageInterface
{
    private $storageFile;
    private $cacheDir;
    private $available = false;

    public function __construct()
    {
        $this->storageFile = $_ENV['RATE_LIMIT_FILE'] ?? sys_get_temp_dir() . '/fastapi_rate_limit.json';
        $this->cacheDir = dirname($this->storageFile);
        $this->ensureDirectoryExists();
        $this->available = $this->test();
    }

    /**
     * Check if file storage is available and working
     */
    public function isAvailable(): bool
    {
        return $this->available && $this->test();
    }

    /**
     * Test file storage functionality
     */
    public function test(): bool
    {
        try {
            // Test if we can read/write to the directory
            if (!is_dir($this->cacheDir) || !is_writable($this->cacheDir)) {
                return false;
            }

            // Test if we can create a test file
            $testFile = $this->cacheDir . '/test_' . uniqid() . '.tmp';
            if (file_put_contents($testFile, 'test') === false) {
                return false;
            }

            // Test if we can read the test file
            if (file_get_contents($testFile) !== 'test') {
                unlink($testFile);
                return false;
            }

            // Clean up test file
            unlink($testFile);
            return true;

        } catch (\Exception $e) {
            error_log("File storage test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure storage directory exists
     */
    private function ensureDirectoryExists(): void
    {
        try {
            if (!is_dir($this->cacheDir)) {
                if (!mkdir($this->cacheDir, 0755, true)) {
                    error_log("Failed to create rate limit directory: {$this->cacheDir}");
                    return;
                }
            }

            // Ensure proper permissions
            if (!is_writable($this->cacheDir)) {
                chmod($this->cacheDir, 0755);
            }

        } catch (\Exception $e) {
            error_log("Failed to ensure directory exists: " . $e->getMessage());
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

            $data = $this->readStorageData();
            $currentTime = time();
            $windowStart = $currentTime - $timeWindow;

            if (!isset($data[$key])) {
                return 0;
            }

            // Filter entries within time window
            $validEntries = array_filter($data[$key], function($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            });

            return count($validEntries);
        } catch (\Exception $e) {
            error_log("File storage getCurrentCount failed: " . $e->getMessage());
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

            $data = $this->readStorageData();
            $currentTime = time();

            if (!isset($data[$key])) {
                $data[$key] = [];
            }

            // Add current timestamp
            $data[$key][] = $currentTime;

            // Clean old entries
            $windowStart = $currentTime - $timeWindow;
            $data[$key] = array_filter($data[$key], function($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            });

            return $this->writeStorageData($data);
        } catch (\Exception $e) {
            error_log("File storage incrementCount failed: " . $e->getMessage());
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

            // Increment count but do NOT treat success as limited
            // Under the limit → increment usage and allow request
            $this->incrementCount($key, $timeWindow);
            return false;
        } catch (\Exception $e) {
            error_log("File storage isLimited failed: " . $e->getMessage());
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

            $data = $this->readStorageData();
            
            if (isset($data[$key])) {
                unset($data[$key]);
                return $this->writeStorageData($data);
            }

            return true;
        } catch (\Exception $e) {
            error_log("File storage reset failed: " . $e->getMessage());
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

            $currentCount = $this->getCurrentCount($key, $timeWindow);
            $ttl = $this->getTTL($key);

            return [
                'count' => $currentCount,
                'remaining' => max(0, 100 - $currentCount),
                'reset_time' => time() + $timeWindow,
                'storage' => 'file',
                'ttl' => $ttl ?: $timeWindow
            ];
        } catch (\Exception $e) {
            error_log("File storage getInfo failed: " . $e->getMessage());
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

            $data = $this->readStorageData();
            
            if (!isset($data[$key]) || empty($data[$key])) {
                return null;
            }

            // Get oldest timestamp
            $oldestTimestamp = min($data[$key]);
            $elapsed = time() - $oldestTimestamp;
            
            return max(0, 60 - $elapsed);
        } catch (\Exception $e) {
            error_log("File storage getTTL failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Read storage data with file locking
     */
    private function readStorageData(): array
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }

        $handle = fopen($this->storageFile, 'r');
        if (!$handle) {
            return [];
        }

        // Acquire shared lock for reading
        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            return [];
        }

        $content = file_get_contents($this->storageFile);
        flock($handle, LOCK_UN);
        fclose($handle);

        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Write storage data with file locking
     */
    private function writeStorageData(array $data): bool
    {
        $handle = fopen($this->storageFile, 'w');
        if (!$handle) {
            return false;
        }

        // Acquire exclusive lock for writing
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return false;
        }

        $content = json_encode($data, JSON_PRETTY_PRINT);
        $result = fwrite($handle, $content);
        
        flock($handle, LOCK_UN);
        fclose($handle);

        return $result !== false;
    }

    /**
     * Get default info when file storage is unavailable
     */
    private function getDefaultInfo(): array
    {
        return [
            'count' => 0,
            'remaining' => 100,
            'reset_time' => time() + 60,
            'storage' => 'file',
            'ttl' => 60
        ];
    }

    /**
     * Clean up old entries (garbage collection)
     */
    public function cleanup(): void
    {
        try {
            if (!$this->isAvailable()) {
                return;
            }

            $data = $this->readStorageData();
            $currentTime = time();
            $cleaned = false;

            foreach ($data as $key => $timestamps) {
                $originalCount = count($timestamps);
                
                // Remove entries older than 1 hour
                $timestamps = array_filter($timestamps, function($timestamp) use ($currentTime) {
                    return $timestamp > ($currentTime - 3600);
                });

                if (count($timestamps) !== $originalCount) {
                    $data[$key] = $timestamps;
                    $cleaned = true;
                }
            }

            if ($cleaned) {
                $this->writeStorageData($data);
            }

        } catch (\Exception $e) {
            error_log("File storage cleanup failed: " . $e->getMessage());
        }
    }
}
