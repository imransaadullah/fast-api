# Auto-Fallback Rate Limiting - Quick Reference

## 🚀 Quick Start

### Zero Configuration Setup
```php
// Just use it - fallbacks happen automatically!
$app->registerMiddleware('rate_limit', RateLimitMiddleware::class . '::api');

// The system automatically:
// 1. Detects available storage backends
// 2. Selects the best performing one
// 3. Falls back when needed
// 4. Recovers automatically
```

### Check System Status
```php
$rateLimiter = RateLimiter::getInstance();

// Current storage
echo "Active: " . $rateLimiter->getActiveStorage();

// All storage status
$status = $rateLimiter->getStorageStatus();
print_r($status);
```

## 🔧 Configuration

### Environment Variables
```bash
# Redis (Primary)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_password

# Database (Secondary)
DB_DRIVER=mysql
DB_HOST=localhost
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass

# Rate Limiting
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW=60
```

### Custom Storage Priority
```php
$rateLimiter->configure([
    'storage_priority' => ['database', 'redis', 'memory', 'file']
]);
```

## 📊 Monitoring

### Storage Health Check
```php
// Test all storages
$results = $rateLimiter->testAllStorages();
foreach ($results as $type => $result) {
    echo "{$type}: " . ($result['test'] ? '✅' : '❌');
    if ($result['error']) echo " ({$result['error']})";
    echo "\n";
}
```

### Performance Metrics
```php
// Memory usage (if using memory storage)
$stats = $rateLimiter->getMemoryStats();
if ($stats) {
    echo "Memory: " . number_format($stats['memory_usage'] / 1024, 2) . "KB\n";
    echo "Keys: " . $stats['total_keys'] . "\n";
}
```

## 🚨 Troubleshooting

### Common Issues & Solutions

#### Redis Not Working
```bash
# Check Redis service
sudo systemctl status redis

# Check PHP extension
php -m | grep redis

# Test connection
redis-cli ping
```

#### Database Connection Failed
```bash
# Check database service
sudo systemctl status mysql

# Check PDO extension
php -m | grep pdo

# Test connection
mysql -u username -p database_name
```

#### File Storage Permissions
```bash
# Check permissions
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
$available = $rateLimiter->getAvailableStorages();
print_r($available);
```

## 🔄 Fallback Scenarios

### Automatic Fallback Chain
```
Redis → Database → Memory → File
  ↓        ↓        ↓       ↓
Fastest → Fast → Medium → Reliable
```

### Manual Fallback Control
```php
// Force fallback to next storage
$rateLimiter->forceFallback();

// Get current storage
echo "Current: " . $rateLimiter->getActiveStorage();
```

## 📈 Performance

### Storage Performance Comparison

| Storage | Speed | Best For | Requirements |
|---------|-------|----------|--------------|
| Redis | ⚡⚡⚡ | Production | PHP Redis extension |
| Database | ⚡⚡ | Compliance | PDO extension |
| Memory | ⚡⚡⚡ | Development | PHP memory functions |
| File | ⚡ | Fallback | Writable filesystem |

### Performance Testing
```php
// Test with 1000 requests
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

### Production Setup
1. **Use Redis as primary storage**
2. **Set up Redis clustering for high availability**
3. **Monitor fallback events**
4. **Set up alerts for storage failures**

### Development Setup
1. **Use Memory storage for testing**
2. **File storage for persistence**
3. **No external dependencies required**

### Security
1. **Secure Redis connections**
2. **Database access controls**
3. **File system permissions**
4. **Use secure rate limit keys**

## 🔮 Advanced Features

### Custom Storage Backends
```php
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

### Adaptive Rate Limiting
```php
// Dynamic rate limits based on server load
$load = sys_getloadavg()[0];
$adjustedLimit = max(1, (int)(100 * (1 - $load)));

$limited = $rateLimiter->isLimited($key, $adjustedLimit, 60);
```

## 📚 API Reference

### Key Methods
- `getInstance()` - Get singleton instance
- `isLimited(key, maxRequests, timeWindow)` - Check rate limit
- `getActiveStorage()` - Get current storage type
- `getStorageStatus()` - Get comprehensive status
- `forceFallback()` - Force fallback to next storage
- `testAllStorages()` - Test all storage backends
- `configure(config)` - Configure rate limiter

### Storage Interface
- `isAvailable()` - Check if storage is available
- `test()` - Test storage functionality
- `isLimited(key, maxRequests, timeWindow)` - Check if limited
- `getInfo(key, timeWindow)` - Get rate limit info
- `reset(key)` - Reset rate limit
- `getTTL(key)` - Get time to live

## 🎉 Benefits

### Zero Downtime
- Automatic fallback prevents system crashes
- Requests continue even if all storages fail
- Seamless recovery when storage becomes available

### Best Performance
- Always uses the fastest available storage
- Automatic storage selection and optimization
- No manual configuration required

### Easy Management
- Transparent operation for users
- Comprehensive monitoring and debugging
- Production-ready reliability

## 🆘 Getting Help

### Check Logs
```bash
# Check error logs
tail -f /var/log/php_errors.log

# Check system logs
tail -f /var/log/syslog
```

### Common Error Messages
- `"Rate limiter using redis storage"` - Normal operation
- `"Rate limiter switched to database storage"` - Fallback occurred
- `"Rate limiter falling back to file storage"` - Multiple fallbacks
- `"All rate limiting storages failed, allowing request"` - Critical error

### Support
- Check storage availability
- Verify environment configuration
- Test individual storage backends
- Monitor fallback events
