# Rate Limiting Quick Reference

Quick setup and troubleshooting guide for FastAPI's flexible rate limiting system.

## 🚀 Quick Setup

### 1. Basic Configuration
```php
use FASTAPI\App;

$app = App::getInstance();
$app->setRateLimit(100, 60); // 100 requests per minute
```

### 2. Environment Variables
```bash
# Redis (Priority 1)
export REDIS_HOST=127.0.0.1
export REDIS_PORT=6379

# Database (Priority 2)
export DB_HOST=localhost
export DB_DATABASE=your_app

# File (Fallback)
export RATE_LIMIT_FILE=./storage/rate_limit.json
```

## 🔧 Extension Installation

### Redis Extension
```bash
# Ubuntu/Debian
sudo apt-get install php-redis

# CentOS/RHEL
sudo yum install php-redis

# macOS
brew install php-redis

# PECL
sudo pecl install redis
```

### Database Extensions
```bash
# MySQL
sudo apt-get install php-mysql

# PostgreSQL
sudo apt-get install php-pgsql

# SQLite (usually included)
```

## 📊 Storage Backend Priority

1. **Redis** - Fastest, production-ready
2. **Database** - Persistent, distributed
3. **File** - Simple, development

## 🔍 Quick Diagnostics

### Check Available Backends
```php
$app = App::getInstance();
echo "Available: " . implode(', ', $app->getAvailableRateLimitStorages());
echo "Active: " . $app->getRateLimitStorage();
```

### Check Extension Status
```bash
php -m | grep -E "(redis|pdo|mysql|pgsql)"
```

### Test Rate Limiting
```php
$info = $app->getRateLimitInfo();
print_r($info);
```

## 🚨 Common Issues & Solutions

### Redis Not Available
```bash
# Install extension
sudo apt-get install php-redis

# Restart web server
sudo systemctl restart apache2
```

### Database Connection Failed
```bash
# Check credentials
echo $DB_HOST
echo $DB_DATABASE

# Test connection
mysql -h $DB_HOST -u $DB_USER -p $DB_DATABASE
```

### File Permission Denied
```bash
# Fix permissions
sudo chown www-data:www-data /path/to/directory
sudo chmod 755 /path/to/directory
sudo chmod 644 /path/to/rate_limit.json
```

## 📈 Performance Tuning

### Redis Configuration
```bash
# High-performance Redis
export REDIS_HOST=redis-cluster.example.com
export REDIS_TIMEOUT=0.1
```

### Database Optimization
```bash
# Connection pooling
export DB_CONNECTION_POOL_SIZE=10
export DB_TIMEOUT=1.0
```

### Time Window Optimization
```php
// Security-focused (strict)
$app->setRateLimit(10, 60);    // 10 requests per minute

// Performance-focused (lenient)
$app->setRateLimit(1000, 3600); // 1000 requests per hour
```

## 🛠️ Advanced Usage

### Custom Rate Limiting
```php
use FASTAPI\RateLimiter\RateLimiter;

$rateLimiter = RateLimiter::getInstance();
if ($rateLimiter->isLimited($ip . ':sensitive')) {
    return 'Rate limited';
}
```

### Route-Specific Limits
```php
$app->group(['prefix' => 'admin'], function($app) {
    $app->setRateLimit(5, 60); // 5 requests per minute
    
    $app->get('/dashboard', function() {
        return 'Admin panel';
    });
});
```

### Monitoring Endpoint
```php
$app->get('/admin/rate-limit-status', function() {
    $app = App::getInstance();
    return [
        'storage' => $app->getRateLimitStorage(),
        'available' => $app->getAvailableRateLimitStorages(),
        'current_ip' => $app->getRateLimitInfo()
    ];
});
```

## 📋 Environment Checklist

- [ ] Redis extension installed (`php -m | grep redis`)
- [ ] Database extensions installed (`php -m | grep pdo`)
- [ ] Environment variables set (`echo $REDIS_HOST`)
- [ ] File permissions correct (`ls -la /path/to/file`)
- [ ] Web server restarted after extension installation
- [ ] Rate limiting configured (`$app->setRateLimit()`)

## 🔗 Related Documentation

- **[Full Rate Limiting Guide](rate-limiting.md)** - Comprehensive documentation
- **[App Class Guide](app-class.md)** - Application configuration
- **[Troubleshooting Guide](troubleshooting.md)** - Common issues and solutions

---

**Quick Tip**: Use `$app->getRateLimitStorage()` to see which backend is currently active and `$app->getAvailableRateLimitStorages()` to see all available options.
