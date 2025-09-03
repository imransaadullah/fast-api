<?php

namespace FASTAPI\RateLimiter;

/**
 * File storage backend for rate limiting (fallback)
 */
class FileStorage implements StorageInterface
{
    /** @var string */
    private $storageFile;

    public function __construct()
    {
        // Use a configurable file path for rate limit storage, defaulting to a writable directory outside source
        $this->storageFile = $_ENV['RATE_LIMIT_FILE'] ?? sys_get_temp_dir() . '/fastapi_rate_limit.json';
    }

    /**
     * Get current count for a key within time window
     */
    public function getCurrentCount(string $key, int $timeWindow): int
    {
        $rateData = $this->readStorageFile();
        $currentTime = time();

        if (isset($rateData[$key])) {
            $timeDifference = $currentTime - $rateData[$key]['timestamp'];

            if ($timeDifference > $timeWindow) {
                // Reset if time window has passed
                return 0;
            }

            return $rateData[$key]['count'];
        }

        return 0;
    }

    /**
     * Increment count for a key
     */
    public function incrementCount(string $key, int $timeWindow): bool
    {
        $rateData = $this->readStorageFile();
        $currentTime = time();

        if (isset($rateData[$key])) {
            $timeDifference = $currentTime - $rateData[$key]['timestamp'];

            if ($timeDifference > $timeWindow) {
                // Reset count if time window has passed
                $rateData[$key] = ['timestamp' => $currentTime, 'count' => 1];
            } else {
                // Increment request count within the time window
                $rateData[$key]['count']++;
            }
        } else {
            // Initialize rate limit data for the first request
            $rateData[$key] = ['timestamp' => $currentTime, 'count' => 1];
        }

        return $this->writeStorageFile($rateData);
    }

    /**
     * Reset count for a key
     */
    public function reset(string $key): bool
    {
        $rateData = $this->readStorageFile();
        
        if (isset($rateData[$key])) {
            unset($rateData[$key]);
            return $this->writeStorageFile($rateData);
        }

        return true;
    }

    /**
     * Get detailed info for a key
     */
    public function getInfo(string $key, int $timeWindow): array
    {
        $rateData = $this->readStorageFile();
        $currentTime = time();

        if (isset($rateData[$key])) {
            $timeDifference = $currentTime - $rateData[$key]['timestamp'];

            if ($timeDifference > $timeWindow) {
                // Reset if time window has passed
                return [
                    'count' => 0,
                    'remaining' => ($_ENV['RATE_LIMIT_MAX'] ?? 100),
                    'reset_time' => $currentTime + $timeWindow,
                    'storage' => 'file'
                ];
            }

            $count = $rateData[$key]['count'];
            $remaining = max(0, ($_ENV['RATE_LIMIT_MAX'] ?? 100) - $count);
            $resetTime = $rateData[$key]['timestamp'] + $timeWindow;

            return [
                'count' => $count,
                'remaining' => $remaining,
                'reset_time' => $resetTime,
                'storage' => 'file'
            ];
        }

        return [
            'count' => 0,
            'remaining' => ($_ENV['RATE_LIMIT_MAX'] ?? 100),
            'reset_time' => $currentTime + $timeWindow,
            'storage' => 'file'
        ];
    }

    /**
     * Read rate limit data from file
     */
    private function readStorageFile(): array
    {
        // Create the file if it doesn't exist
        if (!file_exists($this->storageFile)) {
            $this->ensureDirectoryExists();
            file_put_contents($this->storageFile, json_encode([]));
        }

        // Read and decode the file content
        $data = file_get_contents($this->storageFile);
        return json_decode($data, true) ?: [];
    }

    /**
     * Write rate limit data to file
     */
    private function writeStorageFile(array $rateData): bool
    {
        try {
            $this->ensureDirectoryExists();
            
            // Use file locking for concurrency safety
            $fp = fopen($this->storageFile, 'c+');
            if (flock($fp, LOCK_EX)) {
                // Truncate file before writing
                ftruncate($fp, 0);
                fwrite($fp, json_encode($rateData));
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Ensure the directory exists for the storage file
     */
    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->storageFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
