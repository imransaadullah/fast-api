# Auto-Fallback Rate Limiting System

## Overview

The FastAPI framework now includes a **revolutionary auto-fallback rate limiting system** that automatically switches between different storage backends based on availability and performance. This system ensures your application never goes down due to rate limiting failures and always provides the best possible performance.

## 🚀 Key Features

### **Automatic Storage Detection**
- Automatically detects available storage backends
- Tests each storage for functionality and performance
- No manual configuration required

### **Intelligent Fallback Chain**
```
Redis → File
  ↓       ↓
Fastest → Reliable
```

### **Fail-Open Design**
- If all storages fail, requests continue (prevents system crashes)
- Comprehensive error logging for debugging
- Automatic recovery when storage becomes available

### **Transparent Operation**
- Users don't need to know about fallbacks
- System automatically handles all storage switching
- Zero downtime during storage failures

## 🏗️ Architecture

### Storage Backends

#### 1. **Redis Storage** (Highest Priority)
- **Performance**: Ultra-fast in-memory storage
- **Features**: Automatic expiration, sorted sets for precise timing
- **Fallback**: When Redis connection fails or becomes slow
- **Requirements**: PHP Redis extension or Predis client

#### 2. **File Storage** (Final Fallback)
- **Performance**: Reliable but slower storage
- **Features**: File locking, automatic directory creation
- **Fallback**: Always available as last resort
- **Requirements**: Writable file system

## 📖 Usage

### Basic Usage (Zero Configuration)

```php
// Just use it - fallbacks happen automatically!
$app->registerMiddleware('rate_limit', RateLimitMiddleware::class . '::api');

// The system automatically:
// 1. Detects available storage backends
// 2. Selects the best performing one
// 3. Falls back when needed
// 4. Recovers automatically
```

### Advanced Configuration

```php
use FASTAPI\RateLimiter\RateLimiter;

$rateLimiter = RateLimiter::getInstance();

// Configure storage priority
$rateLimiter->configure([
    'max_requests' => 100,
    'time_window' => 60,
    'storage_priority' => ['redis', 'file']
]);

// Custom storage priority (e.g., prefer file over Redis)
$rateLimiter->configure([
    'storage_priority' => ['file', 'redis']
]);
```

### Monitoring and Control

```php
// Check current storage status
$status = RateLimiter::getInstance()->getStorageStatus();
print_r($status);

// Get active storage type
$activeStorage = RateLimiter::getInstance()->getActiveStorage();
echo "Currently using: {$activeStorage}";

// Force fallback to next available storage
RateLimiter::getInstance()->forceFallback();

// Test all storage backends
$testResults = RateLimiter::getInstance()->testAllStorages();
print_r($testResults);
```

## 🔧 Configuration

### Environment Variables

```bash
# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_password
REDIS_DATABASE=0
REDIS_TIMEOUT=1.0

# File Storage Configuration (Optional)
RATE_LIMIT_FILE_PATH=/tmp/rate_limits.json

# Rate Limiting Configuration
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW=60
RATE_LIMIT_FILE=/path/to/rate_limit.json
```

### Storage Priority Configuration

```php
// Default priority (fastest to most reliable)
$defaultPriority = ['redis', 'file'];

// Custom priority (e.g., prefer file for simplicity)
$customPriority = ['file', 'redis'];

// Development priority (e.g., prefer file for testing)
$devPriority = ['file', 'redis'];
```

## 📊 Monitoring and Debugging

### Storage Status

```php
$rateLimiter = RateLimiter::getInstance();

// Get comprehensive storage status
$status = $rateLimiter->getStorageStatus();
foreach ($status as $type => $info) {
    echo "{$type}: " . 
         ($info['available'] ? 'Available' : 'Not Available') . " | " .
         ($info['active'] ? 'Active' : 'Inactive') . " | " .
         ($info['working'] ? 'Working' : 'Not Working') . "\n";
}
```

### Performance Monitoring

```php
// Get file storage statistics
$fileStats = $rateLimiter->getFileStats();
if ($fileStats) {
    echo "File size: " . number_format($fileStats['file_size'] / 1024, 2) . "KB\n";
    echo "Total keys: " . $fileStats['total_keys'] . "\n";
    echo "Last cleanup: " . $fileStats['last_cleanup'] . "\n";
}

// Test all storage backends
$testResults = $rateLimiter->testAllStorages();
foreach ($testResults as $type => $result) {
    echo "{$type}: " . ($result['test'] ? 'Working' : 'Not Working');
    if ($result['error']) {
        echo " (Error: {$result['error']})";
    }
    echo "\n";
}
```

### Error Logging

The system automatically logs all important events:

```php
// Example log messages:
// "Rate limiter using redis storage"
// "Rate limiter switched to database storage"
// "Rate limiter falling back to file storage"
// "All rate limiting storages failed, allowing request"
```

## 🚨 Troubleshooting

### Common Issues

#### Redis Connection Failed
```bash
# Check Redis service
sudo systemctl status redis

# Check Redis extension
php -m | grep redis

# Test Redis connection
redis-cli ping
```

#### File Storage Permission Issues
```bash
# Check directory permissions
ls -la /tmp/

# Fix permissions
sudo chmod 755 /tmp/
sudo chown www-data:www-data /tmp/
```

### Debug Mode

```php
// Enable detailed logging
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Check storage availability
$rateLimiter = RateLimiter::getInstance();
$available = $rateLimiter->getAvailableStorages();
print_r($available);
```

## 🔄 Fallback Scenarios

### Scenario 1: Redis Failure
```
1. System detects Redis is down
2. Automatically switches to File storage
3. Continues operating normally
4. Logs the fallback for monitoring
5. Attempts Redis recovery in background
```

### Scenario 2: Complete Storage Failure
```
1. All storage backends fail
2. System allows requests to continue (fail-open)
3. Logs critical error for immediate attention
4. Continues attempting recovery
5. Resumes rate limiting when storage becomes available
```

## 📈 Performance Characteristics

### Storage Performance Comparison

| Storage | Speed | Persistence | Memory Usage | Network | Best For |
|---------|-------|-------------|--------------|---------|----------|
| Redis | ⚡⚡⚡ | ❌ | Low | Required | Production |
| File | ⚡ | ✅ | Low | ❌ | Fallback |

### Performance Benchmarks

```php
// Test performance with 1000 requests
$iterations = 1000;
$startTime = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    $key = 'test_' . $i;
    $rateLimiter->isLimited($key, 100, 60);
}

$duration = (microtime(true) - $startTime) * 1000;
echo "Processed {$iterations} requests in {$duration:.2f}ms\n";
echo "Average: " . ($duration / $iterations) . "ms per request\n";
```

## 🎯 Best Practices

### Production Deployment

1. **Use Redis as Primary Storage**
   - Install Redis extension: `sudo apt-get install php-redis`
   - Configure Redis for persistence
   - Set up Redis clustering for high availability

2. **Monitor Storage Health**
   - Set up alerts for storage failures
   - Monitor fallback events
   - Track performance metrics

### Development Environment

1. **Use File Storage for Development**
   - Good for development testing
   - No database setup required
   - Easy to inspect and modify

### Security Considerations

1. **Rate Limit Keys**
   - Use secure, unique keys
   - Avoid predictable patterns
   - Implement key rotation

2. **Storage Security**
   - Secure Redis connections
   - File system permissions

## 🔮 Future Enhancements

### Planned Features

1. **Adaptive Rate Limiting**
   - Dynamic rate limits based on server load
   - User-based rate limiting
   - IP range-based rate limiting

2. **Advanced Storage Backends**
   - MongoDB support
   - Cloud storage integration

3. **Enhanced Monitoring**
   - Real-time dashboards
   - Performance analytics
   - Predictive fallback

### Custom Storage Backends

```php
// Implement custom storage backend
class CustomStorage implements StorageInterface
{
    public function isAvailable(): bool { /* ... */ }
    public function test(): bool { /* ... */ }
    public function isLimited(string $key, int $maxRequests, int $timeWindow): bool { /* ... */ }
    // ... implement all required methods
}

// Register custom storage
$rateLimiter->registerStorage('custom', new CustomStorage());
```

## 📚 API Reference

### RateLimiter Class

#### Methods

- `getInstance(): self` - Get singleton instance
- `configure(array $config): void` - Configure rate limiter
- `isLimited(string $key, int $maxRequests, int $timeWindow): bool` - Check rate limit
- `getCurrentCount(string $key, int $timeWindow): int` - Get current count
- `reset(string $key): bool` - Reset rate limit
- `getInfo(string $key, int $timeWindow): array` - Get rate limit info
- `getTTL(string $key): ?int` - Get time to live
- `getActiveStorage(): string` - Get active storage type
- `getAvailableStorages(): array` - Get available storage types
- `getStorageStatus(): array` - Get storage status
- `forceFallback(): void` - Force fallback to next storage
- `testAllStorages(): array` - Test all storage backends
- `cleanup(): void` - Clean up all storages

### StorageInterface

#### Methods

- `isAvailable(): bool` - Check if storage is available
- `test(): bool` - Test storage functionality
- `getCurrentCount(string $key, int $timeWindow): int` - Get current count
- `incrementCount(string $key, int $timeWindow): bool` - Increment count
- `reset(string $key): bool` - Reset rate limit
- `getInfo(string $key, int $timeWindow): array` - Get rate limit info
- `isLimited(string $key, int $maxRequests, int $timeWindow): bool` - Check if limited
- `getTTL(string $key): ?int` - Get time to live

## 🎉 Conclusion

The auto-fallback rate limiting system provides:

- **Zero Downtime**: Automatic fallback prevents system crashes
- **Best Performance**: Always uses the fastest available storage
- **Easy Management**: No manual configuration required
- **Comprehensive Monitoring**: Full visibility into system health
- **Production Ready**: Enterprise-grade reliability and performance

This system ensures your FastAPI application never goes down due to rate limiting failures and always provides the best possible performance for your users.
