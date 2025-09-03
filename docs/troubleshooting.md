# Troubleshooting Guide

Common issues and solutions for FastAPI framework.

## 🚦 Rate Limiting Issues

### Redis Extension Not Available

**Error**: `Undefined type 'Redis'` or `Class 'Redis' not found`

**Solution**:
```bash
# Install Redis extension
sudo apt-get install php-redis  # Ubuntu/Debian
sudo yum install php-redis       # CentOS/RHEL
brew install php-redis           # macOS

# Or via PECL
sudo pecl install redis

# Restart web server
sudo systemctl restart apache2   # Apache
sudo systemctl restart nginx     # Nginx
sudo systemctl restart php-fpm   # PHP-FPM
```

**Verification**:
```bash
php -m | grep redis
php -r "echo phpversion('redis');"
```

### Database Connection Failed

**Error**: `SQLSTATE[HY000] [2002] Connection refused` or `PDOException`

**Solution**:
```bash
# Check environment variables
echo $DB_HOST
echo $DB_DATABASE
echo $DB_USER

# Test database connection
mysql -h $DB_HOST -u $DB_USER -p $DB_DATABASE

# Check if database service is running
sudo systemctl status mysql
sudo systemctl status postgresql
```

**Common Issues**:
- Database service not running
- Wrong credentials
- Firewall blocking connection
- Database not accepting connections from web server IP

### File Permission Denied

**Error**: `Permission denied` or `Unable to create directory`

**Solution**:
```bash
# Fix directory permissions
sudo mkdir -p /var/log/fastapi
sudo chown www-data:www-data /var/log/fastapi
sudo chmod 755 /var/log/fastapi

# Fix file permissions
sudo touch /var/log/fastapi/rate_limit.json
sudo chown www-data:www-data /var/log/fastapi/rate_limit.json
sudo chmod 644 /var/log/fastapi/rate_limit.json

# Alternative: Use temp directory
export RATE_LIMIT_FILE=/tmp/fastapi_rate_limit.json
```

### Rate Limiting Not Working

**Symptoms**: No rate limiting applied, all requests allowed

**Diagnosis**:
```php
$app = App::getInstance();

// Check if rate limiting is enabled
echo "Active storage: " . $app->getRateLimitStorage();
echo "Available storages: " . implode(', ', $app->getAvailableRateLimitStorages());

// Check configuration
$info = $app->getRateLimitInfo();
print_r($info);
```

**Solutions**:
1. Ensure `setRateLimit()` is called before routes
2. Check if storage backends are available
3. Verify environment variables are set
4. Check web server logs for errors

## 🔐 Authentication Issues

### JWT Token Invalid

**Error**: `Signature verification failed` or `Token expired`

**Solutions**:
```bash
# Check secret key
echo $JWT_SECRET_KEY

# Verify timezone
echo $TIMEZONE

# Check token expiration
php -r "echo date('Y-m-d H:i:s');"
```

**Common Issues**:
- Clock skew between servers
- Wrong secret key
- Token format incorrect
- Expired token

### Secret Key Issues

**Error**: `Secret key not found` or `Invalid secret key`

**Solution**:
```bash
# Set environment variables
export SECRETS_DIR=/path/to/secrets
export JWT_SECRET_KEY=your_secret_key
export TIMEZONE=UTC

# Or use .env file
echo "JWT_SECRET_KEY=your_secret_key" >> .env
echo "TIMEZONE=UTC" >> .env
```

## 🌐 WebSocket Issues

### Connection Refused

**Error**: `Connection refused` or `WebSocket handshake failed`

**Solutions**:
```bash
# Check if port is open
netstat -tlnp | grep :8080

# Check firewall
sudo ufw status
sudo iptables -L

# Test with simple client
wscat -c ws://localhost:8080
```

### Handshake Failed

**Error**: `Invalid WebSocket key` or `Upgrade header missing`

**Solutions**:
1. Ensure proper headers in request
2. Check if WebSocket server is running
3. Verify URL format (`ws://` not `http://`)
4. Check browser console for errors

## 🛣️ Routing Issues

### Route Not Found

**Error**: `404 Not Found` or route not matching

**Diagnosis**:
```php
// Check registered routes
$app = App::getInstance();
$routes = $app->getRouter()->getRoutes();
print_r($routes);

// Check route parameters
echo "Current URI: " . $request->getUri();
echo "Method: " . $request->getMethod();
```

**Solutions**:
1. Verify route registration order
2. Check middleware blocking requests
3. Ensure route patterns match exactly
4. Check for typos in route definitions

### Middleware Not Working

**Error**: Middleware not executing or unexpected behavior

**Diagnosis**:
```php
// Check registered middleware
$middleware = $app->getRouter()->getMiddleware();
print_r($middleware);

// Check middleware order
$globalMiddleware = $app->getRouter()->getGlobalMiddleware();
print_r($globalMiddleware);
```

**Solutions**:
1. Ensure middleware implements `MiddlewareInterface`
2. Check middleware registration order
3. Verify middleware class exists
4. Check for syntax errors in middleware

## ⏰ CustomTime Issues

### DateObjectError

**Error**: `Object has not been correctly initialized by calling parent::__construct()`

**Solution**:
```php
// Use getInstance() method
$time = CustomTime::getInstance();

// Or ensure proper inheritance
class CustomTime extends DateTimeImmutable
{
    public function __construct($date = 'now')
    {
        parent::__construct($date);
    }
}
```

### Method Chaining Not Working

**Error**: Methods not returning `$this` for chaining

**Solution**:
```php
// Ensure methods return $this
public function add_hours(int $hours): self
{
    $newTime = $this->add(new DateInterval("PT{$hours}H"));
    return new static($newTime->format('Y-m-d H:i:s'));
}
```

## 🔧 General Issues

### Class Not Found

**Error**: `Class 'FASTAPI\ClassName' not found`

**Solutions**:
```bash
# Check autoloader
composer dump-autoload

# Verify namespace
php -l src/ClassName.php

# Check file permissions
ls -la src/ClassName.php
```

### Memory Issues

**Error**: `Allowed memory size exhausted` or slow performance

**Solutions**:
```php
// Increase memory limit
ini_set('memory_limit', '512M');

// Use streaming for large responses
$response->setStreamingResponse($callback);

// Optimize database queries
// Use pagination
// Implement caching
```

### Performance Issues

**Symptoms**: Slow response times, high CPU usage

**Diagnosis**:
```php
// Add timing
$start = microtime(true);
// ... your code ...
$end = microtime(true);
echo "Execution time: " . ($end - $start) * 1000 . "ms";
```

**Solutions**:
1. Enable OPcache
2. Use Redis for caching
3. Optimize database queries
4. Implement response compression
5. Use CDN for static assets

## 📋 Debug Checklist

### Before Reporting Issues

- [ ] Check PHP error logs
- [ ] Verify environment variables
- [ ] Test with minimal code
- [ ] Check extension availability
- [ ] Verify file permissions
- [ ] Test in different environments
- [ ] Check browser console (for WebSocket)
- [ ] Verify route registration order

### Common Debug Commands

```bash
# Check PHP extensions
php -m

# Check PHP version
php -v

# Check syntax
php -l filename.php

# Check environment
php -r "print_r(getenv());"

# Check loaded extensions
php -r "print_r(get_loaded_extensions());"
```

## 🆘 Getting Help

### Information to Include

1. **Error Message**: Exact error text
2. **Code**: Minimal reproducing example
3. **Environment**: PHP version, OS, extensions
4. **Steps**: How to reproduce the issue
5. **Expected vs Actual**: What should happen vs what happens

### Example Issue Report

```
Error: Rate limiting not working
PHP Version: 8.1.0
OS: Ubuntu 20.04
Extensions: redis, pdo_mysql

Code:
$app = App::getInstance();
$app->setRateLimit(10, 60);

Expected: 10 requests per minute limit
Actual: No rate limiting applied

Steps:
1. Set rate limit
2. Make 15 requests quickly
3. All requests succeed (should fail after 10)
```

## 🔗 Related Documentation

- **[Rate Limiting Guide](rate-limiting.md)** - Comprehensive rate limiting documentation
- **[Rate Limiting Quick Reference](rate-limiting-quick-reference.md)** - Quick setup guide
- **[App Class Guide](app-class.md)** - Application configuration
- **[Middleware Guide](middleware-complete-guide.md)** - Middleware implementation
- **[WebSocket Guide](websocket.md)** - WebSocket implementation

---

**Remember**: Most issues can be resolved by checking environment variables, file permissions, and extension availability. When in doubt, start with the debug checklist above.
