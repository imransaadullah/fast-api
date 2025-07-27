# Token Class Documentation

The Token class provides comprehensive JWT (JSON Web Token) handling with encryption support, token refresh capabilities, and secure token management for authentication and authorization.

## Table of Contents

- [Quick Start](#quick-start)
- [Basic Usage](#basic-usage)
- [Token Generation](#token-generation)
- [Token Verification](#token-verification)
- [Token Refresh](#token-refresh)
- [Encryption Support](#encryption-support)
- [Security Features](#security-features)
- [Advanced Usage](#advanced-usage)
- [Examples](#examples)

## Quick Start

```php
use FASTAPI\Token\Token;

// Initialize token handler
$token = new Token('api-users');

// Generate token
$jwtToken = $token->make(['user_id' => 123, 'role' => 'admin']);

// Verify token
$decoded = $token->verify($jwtToken);
```

## Basic Usage

### Initialization

```php
use FASTAPI\Token\Token;

// Basic initialization
$token = new Token('api-users');

// With custom configuration
$token = new Token('api-users', null, false); // No SSL

// With SSL support
$token = new Token('api-users', null, true); // With SSL
```

### Environment Configuration

The Token class uses environment variables for configuration:

```php
// Set environment variables
$_ENV['SECRET_KEY'] = 'your-secret-key';
$_ENV['SECRETS_DIR'] = '/path/to/secrets/';
$_ENV['TIMEZONE'] = 'UTC';
$_ENV['TOKEN_ISSUER'] = 'your-app';

// Initialize token
$token = new Token('api-users');
```

## Token Generation

### Basic Token Creation

```php
use FASTAPI\Token\Token;

$token = new Token('api-users');

// Set secret key
$token->set_secret_key('your-secret-key');

// Generate token with payload
$payload = [
    'user_id' => 123,
    'role' => 'admin',
    'email' => 'user@example.com'
];

$jwtToken = $token->make($payload);
$tokenString = $token->get_token();
```

### Token with Custom Expiry

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Custom expiry time
$expiry = time() + (24 * 60 * 60); // 24 hours
$jwtToken = $token->make($payload, $expiry);

// Or set expiry in constructor
$token->set_expiry(time() + 3600); // 1 hour
$jwtToken = $token->make($payload);
```

### Token with Custom Claims

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Add custom claims
$token->add_claim('custom_field', 'value')
      ->set_issuer('my-app')
      ->set_audience('my-users')
      ->set_expiry(time() + 3600);

$jwtToken = $token->make($payload);
```

## Token Verification

### Basic Verification

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Verify token
$decoded = $token->verify($jwtToken);

if ($decoded) {
    $userData = $token->get_data();
    echo "User ID: " . $userData['user_id'];
} else {
    echo "Invalid token";
}
```

### Check Token Expiry

```php
$token = new Token('api-users');

// Check if token is expired
$isExpired = $token->is_expired($jwtToken);

if ($isExpired) {
    echo "Token has expired";
} else {
    echo "Token is valid";
}
```

### Get Token Information

```php
$token = new Token('api-users');

// Get token data
$data = $token->get_data();

// Get token header
$header = $token->get_header();

// Get token payload
$payload = $token->get_payload();

// Get token signature
$signature = $token->get_signature();
```

## Token Refresh

### Refresh Existing Token

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Refresh token with new expiry
$refreshedToken = $token->refresh($jwtToken, 3600); // Extend by 1 hour

// Refresh with custom expiry
$newExpiry = time() + (7 * 24 * 60 * 60); // 7 days
$refreshedToken = $token->refresh($jwtToken, $newExpiry);
```

### Conditional Refresh

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Refresh only if token expires soon
$expiryTime = $token->get_expiry_time($jwtToken);
$timeUntilExpiry = $expiryTime - time();

if ($timeUntilExpiry < 300) { // Less than 5 minutes
    $refreshedToken = $token->refresh($jwtToken, 3600);
    echo "Token refreshed";
} else {
    echo "Token still valid";
}
```

## Encryption Support

### Encrypt Token Payload

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Encrypt sensitive data
$sensitiveData = [
    'credit_card' => '4111-1111-1111-1111',
    'ssn' => '123-45-6789'
];

$encrypted = $token->encrypt_token_payload($sensitiveData, 'encryption-key');
$jwtToken = $token->make(['encrypted_data' => $encrypted]);
```

### Decrypt Token Payload

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Decrypt data
$decoded = $token->verify($jwtToken);
$encryptedData = $decoded['encrypted_data'];

$decrypted = $token->decrypt_token_payload($encryptedData, 'encryption-key');
echo "Credit Card: " . $decrypted['credit_card'];
```

## Security Features

### SSL/RSA Configuration

```php
// Initialize with SSL support
$token = new Token('api-users', null, true);

// Set RSA keys
$token->set_private_key_file_openssl('/path/to/private.pem')
      ->set_public_key_file_openssl('/path/to/public.pem');

// Generate token with RSA signing
$jwtToken = $token->make($payload);
```

### Token Validation

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Validate token structure
$isValid = $token->validate_token_structure($jwtToken);

// Validate token signature
$isValid = $token->validate_token_signature($jwtToken);

// Validate token expiry
$isValid = $token->validate_token_expiry($jwtToken);
```

### Blacklist Support

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Add token to blacklist
$token->blacklist_token($jwtToken);

// Check if token is blacklisted
$isBlacklisted = $token->is_token_blacklisted($jwtToken);

// Remove from blacklist
$token->remove_from_blacklist($jwtToken);
```

## Advanced Usage

### Custom Algorithms

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Use different algorithms
$token->set_algorithm('HS512'); // SHA-512
$token->set_algorithm('RS256'); // RSA-SHA256
$token->set_algorithm('ES256'); // ECDSA-SHA256

$jwtToken = $token->make($payload);
```

### Token with Multiple Audiences

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Set multiple audiences
$token->set_audience(['api-users', 'mobile-app', 'web-app']);

$jwtToken = $token->make($payload);
```

### Token with Custom Headers

```php
$token = new Token('api-users');
$token->set_secret_key('your-secret-key');

// Add custom headers
$token->add_header('kid', 'key-id-123')
      ->add_header('x5t', 'thumbprint');

$jwtToken = $token->make($payload);
```

## Examples

### Authentication System

```php
use FASTAPI\Token\Token;

class AuthController {
    private $token;
    
    public function __construct() {
        $this->token = new Token('api-users');
        $this->token->set_secret_key($_ENV['SECRET_KEY']);
    }
    
    public function login($request) {
        $credentials = $request->getData();
        
        // Validate credentials
        if ($this->validateCredentials($credentials)) {
            $user = $this->getUser($credentials['email']);
            
            // Generate token
            $payload = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            
            $jwtToken = $this->token->make($payload);
            
            return (new Response())->setJsonResponse([
                'token' => $jwtToken,
                'user' => $user
            ]);
        }
        
        return (new Response())->setJsonResponse(['error' => 'Invalid credentials'], 401);
    }
    
    public function verify($request) {
        $authHeader = $request->getHeader('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        
        $decoded = $this->token->verify($token);
        
        if ($decoded) {
            return (new Response())->setJsonResponse([
                'valid' => true,
                'user' => $this->token->get_data()
            ]);
        }
        
        return (new Response())->setJsonResponse(['valid' => false], 401);
    }
}
```

### Middleware Integration

```php
use FASTAPI\Token\Token;
use FASTAPI\Middlewares\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface {
    private $token;
    
    public function __construct() {
        $this->token = new Token('api-users');
        $this->token->set_secret_key($_ENV['SECRET_KEY']);
    }
    
    public function handle(Request $request, \Closure $next): void {
        $authHeader = $request->getHeader('Authorization');
        
        if (!$authHeader) {
            (new Response())->setJsonResponse(['error' => 'No token provided'], 401)->send();
            return;
        }
        
        $token = str_replace('Bearer ', '', $authHeader);
        
        $decoded = $this->token->verify($token);
        
        if (!$decoded) {
            (new Response())->setJsonResponse(['error' => 'Invalid token'], 401)->send();
            return;
        }
        
        // Add user data to request
        $request->setAttribute('user', $this->token->get_data());
        
        $next();
    }
}
```

### API Response Examples

```php
// Login response
$loginResponse = [
    'success' => true,
    'token' => $jwtToken,
    'expires_in' => 3600,
    'token_type' => 'Bearer',
    'user' => [
        'id' => 123,
        'email' => 'user@example.com',
        'role' => 'admin'
    ]
];

// Token verification response
$verifyResponse = [
    'valid' => true,
    'user_id' => 123,
    'role' => 'admin',
    'expires_at' => $token->get_expiry_time($jwtToken)
];

// Error response
$errorResponse = [
    'success' => false,
    'error' => 'Token expired',
    'code' => 'TOKEN_EXPIRED'
];
```

### Database Integration

```php
class TokenService {
    private $token;
    private $db;
    
    public function __construct() {
        $this->token = new Token('api-users');
        $this->token->set_secret_key($_ENV['SECRET_KEY']);
    }
    
    public function createToken($userId) {
        $user = $this->db->getUser($userId);
        
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'permissions' => $user['permissions']
        ];
        
        $jwtToken = $this->token->make($payload);
        
        // Store token in database
        $this->db->storeToken($userId, $jwtToken, $this->token->get_expiry_time($jwtToken));
        
        return $jwtToken;
    }
    
    public function revokeToken($token) {
        $decoded = $this->token->verify($token);
        
        if ($decoded) {
            $userId = $decoded['user_id'];
            $this->db->revokeToken($userId, $token);
            $this->token->blacklist_token($token);
        }
    }
}
```

### Security Best Practices

```php
class SecureTokenService {
    private $token;
    
    public function __construct() {
        $this->token = new Token('api-users');
        $this->token->set_secret_key($_ENV['SECRET_KEY']);
        
        // Use strong algorithm
        $this->token->set_algorithm('HS512');
        
        // Set reasonable expiry
        $this->token->set_expiry(time() + 3600); // 1 hour
    }
    
    public function createSecureToken($user) {
        // Never include sensitive data in token
        $payload = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'session_id' => uniqid()
        ];
        
        // Add fingerprint for additional security
        $fingerprint = hash('sha256', $user['id'] . $_ENV['SECRET_KEY']);
        $payload['fingerprint'] = $fingerprint;
        
        return $this->token->make($payload);
    }
    
    public function validateToken($token, $userAgent) {
        $decoded = $this->token->verify($token);
        
        if (!$decoded) {
            return false;
        }
        
        // Validate fingerprint
        $expectedFingerprint = hash('sha256', $decoded['user_id'] . $_ENV['SECRET_KEY']);
        if ($decoded['fingerprint'] !== $expectedFingerprint) {
            return false;
        }
        
        // Check if token is blacklisted
        if ($this->token->is_token_blacklisted($token)) {
            return false;
        }
        
        return true;
    }
}
```

## Performance Considerations

- **Token Size**: Keep payload minimal to reduce token size
- **Caching**: Cache verified tokens to avoid repeated verification
- **Blacklist**: Use efficient storage for token blacklists
- **Expiry**: Set appropriate expiry times to balance security and performance

## Error Handling

```php
try {
    $token = new Token('api-users');
    $token->set_secret_key($_ENV['SECRET_KEY']);
    
    $jwtToken = $token->make($payload);
    $decoded = $token->verify($jwtToken);
    
} catch (Exception $e) {
    // Handle token errors
    error_log("Token error: " . $e->getMessage());
    
    return (new Response())->setJsonResponse([
        'error' => 'Token processing failed',
        'code' => 'TOKEN_ERROR'
    ], 500);
}
```

The Token class provides a secure, flexible, and feature-rich JWT implementation that handles all aspects of token management while maintaining high security standards. 