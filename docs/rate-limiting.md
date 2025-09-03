# Rate Limiting Guide

FastAPI provides a flexible and robust rate limiting system that automatically selects the best available storage backend based on your environment configuration.

## 📋 Table of Contents

- [Overview](#overview)
- [Storage Backend Priority](#storage-backend-priority)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
- [Advanced Features](#advanced-features)
- [Storage Backends](#storage-backends)
- [Environment Variables](#environment-variables)
- [Examples](#examples)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

The rate limiting system automatically detects available storage backends and uses them in order of preference:

1. **Redis** - Fastest, most scalable (recommended for production)
2. **Database** - Persistent, good for distributed systems
3. **File** - Simple fallback for development

The system automatically falls back to the next available backend if the preferred one fails, ensuring your application continues to work even if Redis or the database becomes unavailable.

### 🔧 Extension Requirements

- **Redis**: Requires `php-redis` extension
- **Database**: Requires `pdo` and database-specific extensions (`pdo_mysql`, `pdo_pgsql`, etc.)
- **File**: No extensions required (always available as fallback)

The system gracefully handles missing extensions by automatically skipping unavailable backends.

## 🏗 Storage Backend Priority

### Automatic Detection

The system automatically detects available backends:

```php
use FASTAPI\App;

$app = App::getInstance();

// Check which storage backend is active
$activeStorage = $app->getRateLimitStorage();
echo "Active storage: " . $activeStorage; // redis, database, or file

// Check all available backends
$availableStorages = $app->getAvailableRateLimitStorages();
print_r($availableStorages); // ['redis', 'database', 'file']
```

### Fallback Behavior

If Redis becomes unavailable, the system automatically switches to the database. If the database is also unavailable, it falls back to file storage. This ensures your rate limiting continues to work even during infrastructure issues.

## ⚙️ Configuration

### Basic Configuration

```php
use FASTAPI\App;

$app = App::getInstance();

// Set rate limit: 100 requests per minute
$app->setRateLimit(100, 60);

// Set rate limit: 1000 requests per hour
$app->setRateLimit(1000, 3600);

// Set rate limit: 10 requests per second
$app->setRateLimit(10, 1);
```

### Advanced Configuration

```php
use FASTAPI\RateLimiter\RateLimiter;

$rateLimiter = RateLimiter::getInstance();

$rateLimiter->configure([
    'max_requests' => 500,
    'time_window' => 300, // 5 minutes
    'storage_priority' => ['redis', 'database', 'file']
]);
```

## 🚀 Basic Usage

### Simple Rate Limiting

```php
use FASTAPI\App;

$app = App::getInstance();

// Set rate limit
$app->setRateLimit(100, 60); // 100 requests per minute

// The rate limiting is automatically applied to all requests
$app->get('/', function() {
    return 'Hello World';
});
```

### Route-Specific Rate Limiting

```php
use FASTAPI\App;

$app = App::getInstance();

// Different rate limits for different routes
$app->group(['prefix' => 'api'], function($app) {
    $app->setRateLimit(1000, 3600); // 1000 requests per hour
    
    $app->get('/users', function() {
        return 'Users list';
    });
    
    $app->get('/posts', function() {
        return 'Posts list';
    });
});

// Admin routes with stricter limits
$app->group(['prefix' => 'admin'], function($app) {
    $app->setRateLimit(100, 3600); // 100 requests per hour
    
    $app->get('/dashboard', function() {
        return 'Admin dashboard';
    });
});
```

## 🔧 Advanced Features

### Rate Limit Information

```php
use FASTAPI\App;

$app = App::getInstance();

// Get rate limit info for current IP
$info = $app->getRateLimitInfo();
echo "Requests made: " . $info['count'];
echo "Remaining: " . $info['remaining'];
echo "Reset time: " . date('Y-m-d H:i:s', $info['reset_time']);
echo "Storage backend: " . $info['storage'];

// Get info for specific key
$info = $app->getRateLimitInfo('192.168.1.100');
```

### Manual Rate Limit Management

```php
use FASTAPI\App;

$app = App::getInstance();

// Reset rate limit for current IP
$app->resetRateLimit();

// Reset rate limit for specific key
$app->resetRateLimit('192.168.1.100');

// Check if specific key is limited
use FASTAPI\RateLimiter\RateLimiter;

$rateLimiter = RateLimiter::getInstance();
if ($rateLimiter->isLimited('192.168.1.100')) {
    echo "IP is rate limited";
}
```

### Custom Rate Limiting Logic

```php
use FASTAPI\App;
use FASTAPI\RateLimiter\RateLimiter;

$app = App::getInstance();

$app->get('/api/sensitive', function() {
    $rateLimiter = RateLimiter::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Custom rate limit for sensitive operations
    if ($rateLimiter->isLimited($ip . ':sensitive')) {
        return (new Response())->setJsonResponse([
            'error' => 'Too many sensitive operations'
        ], 429);
    }
    
    // Process sensitive operation
    return 'Sensitive data';
});
```

## 🗄️ Storage Backends

### Redis Storage

Redis is the fastest and most scalable option, perfect for production environments.

#### Prerequisites

```bash
# Install Redis extension for PHP
# Ubuntu/Debian
sudo apt-get install php-redis

# CentOS/RHEL
sudo yum install php-redis

# macOS with Homebrew
brew install php-redis

# Or install via PECL
sudo pecl install redis
```

#### Environment Variables

```bash
# Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_TIMEOUT=1.0
REDIS_PASSWORD=your_password
REDIS_DATABASE=0
```

#### Features

- **Fast**: In-memory storage with sub-millisecond response times
- **Scalable**: Supports clustering and replication
- **Automatic expiration**: Keys automatically expire after the time window
- **Atomic operations**: Thread-safe increment and expiration
- **Automatic detection**: System automatically detects if Redis extension is available

### Database Storage

Database storage provides persistence and is good for distributed systems.

#### Environment Variables

```bash
# MySQL/PostgreSQL configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_DRIVER=mysql

# Alternative: DATABASE_URL
DATABASE_URL=mysql://username:password@localhost:3306/database
```

#### Supported Databases

- **MySQL**: Full support with optimized queries
- **PostgreSQL**: Full support with optimized queries
- **SQLite**: Basic support for development

#### Features

- **Persistent**: Data survives server restarts
- **Distributed**: Works across multiple server instances
- **Transactional**: ACID compliance for data integrity
- **Automatic cleanup**: Expired entries are automatically removed

### File Storage

File storage is the simplest option, suitable for development and single-server deployments.

#### Environment Variables

```bash
# Custom file path
RATE_LIMIT_FILE=/path/to/rate_limit.json
```

#### Features

- **Simple**: No external dependencies
- **Portable**: Easy to deploy and backup
- **File locking**: Thread-safe with file locking
- **Fallback**: Always available as last resort

## 🌍 Environment Variables

### Complete Configuration Example

```bash
# Rate Limiting Configuration
RATE_LIMIT_MAX=100
RATE_LIMIT_FILE=/var/log/rate_limit.json

# Redis Configuration (Priority 1)
REDIS_HOST=redis.example.com
REDIS_PORT=6379
REDIS_TIMEOUT=1.0
REDIS_PASSWORD=your_redis_password

# Database Configuration (Priority 2)
DB_HOST=db.example.com
DB_PORT=3306
DB_DATABASE=your_app
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
DB_DRIVER=mysql

# Alternative: Single DATABASE_URL
DATABASE_URL=mysql://username:password@host:port/database
```

### Environment-Specific Configurations

#### Development

```bash
# Simple file-based rate limiting
RATE_LIMIT_FILE=./rate_limit.json
RATE_LIMIT_MAX=1000
```

#### Staging

```bash
# Database-based rate limiting
DB_HOST=staging-db.example.com
DB_DATABASE=staging_app
DB_USERNAME=staging_user
DB_PASSWORD=staging_pass
```

#### Production

```bash
# Redis-based rate limiting with database fallback
REDIS_HOST=redis-cluster.example.com
REDIS_PORT=6379
REDIS_PASSWORD=prod_redis_password

DB_HOST=prod-db.example.com
DB_DATABASE=prod_app
DB_USERNAME=prod_user
DB_PASSWORD=prod_pass
```

## 📝 Examples

### Complete Application Example

```php
<?php
require_once 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Response;

$app = App::getInstance();

// Configure rate limiting
$app->setRateLimit(100, 60); // 100 requests per minute

// Public routes with standard rate limiting
$app->get('/', function() {
    return 'Welcome to FastAPI';
});

$app->get('/api/public', function() {
    return 'Public API endpoint';
});

// API routes with stricter rate limiting
$app->group(['prefix' => 'api'], function($app) {
    $app->setRateLimit(50, 60); // 50 requests per minute
    
    $app->get('/users', function() {
        return 'Users list';
    });
    
    $app->post('/users', function() {
        return 'Create user';
    });
});

// Admin routes with very strict rate limiting
$app->group(['prefix' => 'admin'], function($app) {
    $app->setRateLimit(10, 60); // 10 requests per minute
    
    $app->get('/dashboard', function() {
        return 'Admin dashboard';
    });
    
    $app->get('/logs', function() {
        return 'System logs';
    });
});

// Custom rate limiting for sensitive operations
$app->post('/api/sensitive-operation', function() {
    $rateLimiter = \FASTAPI\RateLimiter\RateLimiter::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Very strict rate limit for sensitive operations
    if ($rateLimiter->isLimited($ip . ':sensitive')) {
        return (new Response())->setJsonResponse([
            'error' => 'Too many sensitive operations. Please wait.',
            'retry_after' => 300 // 5 minutes
        ], 429);
    }
    
    // Process sensitive operation
    return 'Sensitive operation completed';
});

// Rate limit monitoring endpoint
$app->get('/api/rate-limit-status', function() {
    $app = App::getInstance();
    
    return [
        'storage_backend' => $app->getRateLimitStorage(),
        'available_backends' => $app->getAvailableRateLimitStorages(),
        'current_ip_info' => $app->getRateLimitInfo()
    ];
});

$app->run();
```

### Middleware Integration

```php
<?php
namespace App\Middleware;

use FASTAPI\RateLimiter\RateLimiter;

class CustomRateLimitMiddleware
{
    private $rateLimiter;
    
    public function __construct()
    {
        $this->rateLimiter = RateLimiter::getInstance();
    }
    
    public function handle($request, $next)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $route = $request->getUri();
        
        // Different rate limits for different routes
        $key = $ip . ':' . $route;
        
        if ($this->rateLimiter->isLimited($key)) {
            return (new Response())->setJsonResponse([
                'error' => 'Rate limit exceeded for this route'
            ], 429);
        }
        
        return $next($request);
    }
}
```

## 🏆 Best Practices

### 1. Choose Appropriate Limits

```php
// Public API: Generous limits
$app->setRateLimit(1000, 3600); // 1000 requests per hour

// Authentication endpoints: Moderate limits
$app->setRateLimit(100, 3600); // 100 login attempts per hour

// Admin operations: Strict limits
$app->setRateLimit(10, 3600); // 10 admin operations per hour
```

### 2. Use Environment-Specific Configuration

```bash
# Development: File storage
export RATE_LIMIT_FILE=./storage/rate_limit.json

# Staging: Database storage  
export DB_HOST=staging-db.example.com
export DB_DATABASE=staging_app

# Production: Redis with database fallback
export REDIS_HOST=redis-cluster.example.com
export DB_HOST=prod-db.example.com
```

### 3. Monitor Rate Limiting

```php
// Add monitoring endpoints
$app->get('/admin/rate-limit-stats', function() {
    $app = App::getInstance();
    
    return [
        'active_storage' => $app->getRateLimitStorage(),
        'available_storages' => $app->getAvailableRateLimitStorages(),
        'sample_ips' => [
            '127.0.0.1' => $app->getRateLimitInfo('127.0.0.1'),
            '192.168.1.1' => $app->getRateLimitInfo('192.168.1.1')
        ]
    ];
});
```

### 4. Handle Rate Limit Errors Gracefully

```php
$app->setNotFoundHandler(function($request) {
    return (new Response())->setJsonResponse([
        'error' => 'Route not found',
        'path' => $request->getUri(),
        'rate_limit_info' => App::getInstance()->getRateLimitInfo()
    ], 404);
});
```

### 5. Deployment Considerations

```bash
# Ensure proper permissions for file storage
sudo mkdir -p /var/log/fastapi
sudo chown www-data:www-data /var/log/fastapi
sudo chmod 755 /var/log/fastapi

# Set environment variables in production
export RATE_LIMIT_FILE=/var/log/fastapi/rate_limit.json

# For Redis, ensure connection details are secure
export REDIS_HOST=internal-redis.example.com
export REDIS_PASSWORD=secure_password

# For database, use connection pooling
export DB_HOST=db-cluster.example.com
export DB_CONNECTION_POOL_SIZE=10
```

```php
// Public API: Generous limits
$app->setRateLimit(1000, 3600); // 1000 requests per hour

// Authentication endpoints: Moderate limits
$app->setRateLimit(100, 3600); // 100 login attempts per hour

// Admin operations: Strict limits
$app->setRateLimit(10, 3600); // 10 admin operations per hour
```

### 2. Use Environment-Specific Configuration

```php
// Development: File storage
// Staging: Database storage  
// Production: Redis with database fallback
```

### 3. Monitor Rate Limiting

```php
// Add monitoring endpoints
$app->get('/admin/rate-limit-stats', function() {
    $app = App::getInstance();
    
    return [
        'active_storage' => $app->getRateLimitStorage(),
        'available_storages' => $app->getAvailableRateLimitStorages(),
        'sample_ips' => [
            '127.0.0.1' => $app->getRateLimitInfo('127.0.0.1'),
            '192.168.1.1' => $app->getRateLimitInfo('192.168.1.1')
        ]
    ];
});
```

### 4. Handle Rate Limit Errors Gracefully

```php
$app->setNotFoundHandler(function($request) {
    return (new Response())->setJsonResponse([
        'error' => 'Route not found',
        'path' => $request->getUri(),
        'rate_limit_info' => App::getInstance()->getRateLimitInfo()
    ], 404);
});
```

## 🔍 Troubleshooting

### Common Issues

#### 1. Rate Limiting Not Working

```php
// Check if rate limiting is enabled
$app = App::getInstance();
echo "Active storage: " . $app->getRateLimitStorage();
echo "Available storages: " . implode(', ', $app->getAvailableRateLimitStorages());

// Check configuration
$info = $app->getRateLimitInfo();
print_r($info);
```

#### 2. Redis Extension Not Available

```bash
# Install Redis extension
sudo apt-get install php-redis  # Ubuntu/Debian
sudo yum install php-redis       # CentOS/RHEL
brew install php-redis           # macOS

# Or via PECL
sudo pecl install redis

# Restart web server after installation
sudo systemctl restart apache2   # Apache
sudo systemctl restart nginx     # Nginx
sudo systemctl restart php-fpm   # PHP-FPM
```

#### 3. Database Connection Issues

```php
// Check if rate limiting is enabled
$app = App::getInstance();
echo "Active storage: " . $app->getRateLimitStorage();
echo "Available storages: " . implode(', ', $app->getAvailableRateLimitStorages());

// Check configuration
$info = $app->getRateLimitInfo();
print_r($info);
```

#### 2. Redis Connection Issues

```bash
# Check Redis connection
redis-cli -h your-redis-host -p 6379 ping

# Check environment variables
echo $REDIS_HOST
echo $REDIS_PORT

# Check if Redis extension is installed
php -m | grep redis

# Check Redis extension version
php -r "echo phpversion('redis');"
```

#### 3. Database Connection Issues

```bash
# Check database connection
mysql -h your-db-host -u your-username -p your-database

# Check environment variables
echo $DB_HOST
echo $DB_DATABASE
```

#### 4. File Permission Issues

```bash
# Check file permissions
ls -la /path/to/rate_limit.json

# Ensure directory is writable
chmod 755 /path/to/rate_limit/directory
chmod 644 /path/to/rate_limit.json
```

### Debug Mode

```php
// Enable debug mode for rate limiting
$rateLimiter = RateLimiter::getInstance();
$rateLimiter->configure([
    'debug' => true,
    'max_requests' => 100,
    'time_window' => 60
]);

// Check detailed information
$info = $rateLimiter->getInfo('test_key');
print_r($info);
```

### System Status Check

```php
use FASTAPI\App;

$app = App::getInstance();

// Check which storage backends are available
$availableStorages = $app->getAvailableRateLimitStorages();
echo "Available backends: " . implode(', ', $availableStorages);

// Check which backend is currently active
$activeStorage = $app->getRateLimitStorage();
echo "Active backend: " . $activeStorage;

// Check rate limit status for current IP
$status = $app->getRateLimitInfo();
echo "Current IP status: " . json_encode($status, JSON_PRETTY_PRINT);
```

### Extension Status Check

```bash
# Check all installed PHP extensions
php -m

# Check specific extensions
php -m | grep -E "(redis|pdo|mysql|pgsql|sqlite)"

# Check extension details
php -r "echo 'Redis: ' . (extension_loaded('redis') ? 'Installed' : 'Not installed') . PHP_EOL;"
php -r "echo 'PDO: ' . (extension_loaded('pdo') ? 'Installed' : 'Not installed') . PHP_EOL;"
php -r "echo 'PDO MySQL: ' . (extension_loaded('pdo_mysql') ? 'Installed' : 'Not installed') . PHP_EOL;"
```

## 📖 Related Documentation

- **[App Class Guide](app-class.md)** - Application lifecycle and configuration
- **[Middleware Guide](middleware-complete-guide.md)** - Custom middleware implementation
- **[Configuration Guide](configuration.md)** - Environment and application configuration
- **[Performance Guide](performance.md)** - Optimization and scaling strategies

## ⚡ Performance Considerations

### Storage Backend Performance

| Backend | Latency | Scalability | Memory Usage | Best For |
|---------|---------|-------------|--------------|----------|
| **Redis** | ~0.1ms | High | Low | Production, high-traffic |
| **Database** | ~1-10ms | Medium | Medium | Staging, distributed systems |
| **File** | ~1-5ms | Low | Low | Development, single server |

### Optimization Tips

```php
// Use Redis for high-traffic applications
export REDIS_HOST=redis-cluster.example.com

// Use database for persistence across server restarts
export DB_HOST=db-cluster.example.com

// Use file storage only for development/testing
export RATE_LIMIT_FILE=./storage/rate_limit.json

// Configure appropriate time windows
$app->setRateLimit(1000, 3600);  // 1 hour window for better performance
$app->setRateLimit(100, 60);     // 1 minute window for security
```

### Monitoring Performance

```php
// Add performance monitoring
$app->get('/admin/rate-limit-performance', function() {
    $start = microtime(true);
    
    $app = App::getInstance();
    $info = $app->getRateLimitInfo();
    
    $end = microtime(true);
    $latency = ($end - $start) * 1000; // Convert to milliseconds
    
    return [
        'storage_backend' => $app->getRateLimitStorage(),
        'latency_ms' => round($latency, 2),
        'rate_limit_info' => $info
    ];
});
```

---

**Next**: [Middleware Guide](middleware-complete-guide.md) → [Configuration Guide](configuration.md) → [Performance Guide](performance.md)
