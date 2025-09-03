<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Router;
use FASTAPI\Token\Token;
use FASTAPI\CustomTime\CustomTime;
use FASTAPI\Inspector;
use FASTAPI\WebSocket\WebSocketServer;

echo "Testing getInstance() methods for all classes...\n\n";

// Test App singleton
echo "1. Testing App::getInstance()...\n";
$app1 = App::getInstance();
$app2 = App::getInstance();
if ($app1 === $app2) {
    echo "✓ App singleton works correctly - same instance returned\n";
} else {
    echo "✗ App singleton failed - different instances returned\n";
}

// Test Router singleton
echo "\n2. Testing Router::getInstance()...\n";
$router1 = Router::getInstance();
$router2 = Router::getInstance();
if ($router1 === $router2) {
    echo "✓ Router singleton works correctly - same instance returned\n";
} else {
    echo "✗ Router singleton failed - different instances returned\n";
}

// Test Token singleton
echo "\n3. Testing Token::getInstance()...\n";
try {
    // Set required environment variables for Token
    $_ENV['SECRET_KEY'] = 'test_secret_key';
    $_ENV['SECRETS_DIR'] = __DIR__ . '/../test_keys/';
    $_ENV['TIMEZONE'] = 'UTC';
    $_ENV['TOKEN_ISSUER'] = 'test_issuer';
    
    // Create test keys directory if it doesn't exist
    if (!is_dir($_ENV['SECRETS_DIR'])) {
        mkdir($_ENV['SECRETS_DIR'], 0755, true);
    }
    
    // Create test key files if they don't exist
    if (!file_exists($_ENV['SECRETS_DIR'] . 'private.pem')) {
        $config = array(
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        file_put_contents($_ENV['SECRETS_DIR'] . 'private.pem', $privateKey);
        
        $publicKey = openssl_pkey_get_details($res)['key'];
        file_put_contents($_ENV['SECRETS_DIR'] . 'public.pem', $publicKey);
    }
    
    $token1 = Token::getInstance('test_audience');
    $token2 = Token::getInstance('different_audience'); // Should return same instance
    if ($token1 === $token2) {
        echo "✓ Token singleton works correctly - same instance returned\n";
    } else {
        echo "✗ Token singleton failed - different instances returned\n";
    }
} catch (Exception $e) {
    echo "✗ Token singleton test failed: " . $e->getMessage() . "\n";
}

// Test CustomTime singleton
echo "\n4. Testing CustomTime::getInstance()...\n";
$customTime1 = CustomTime::getInstance();
$customTime2 = CustomTime::getInstance();
if ($customTime1 === $customTime2) {
    echo "✓ CustomTime singleton works correctly - same instance returned\n";
} else {
    echo "✗ CustomTime singleton failed - different instances returned\n";
}

// Test CustomTime::now() still works
echo "\n5. Testing CustomTime::now() with singleton...\n";
$now1 = CustomTime::now();
$now2 = CustomTime::now();
if (is_numeric($now1) && is_numeric($now2)) {
    echo "✓ CustomTime::now() works correctly with singleton pattern\n";
} else {
    echo "✗ CustomTime::now() failed with singleton pattern\n";
}

// Test Inspector singleton
echo "\n6. Testing Inspector::getInstance()...\n";
$inspector1 = Inspector::getInstance('FASTAPI\App');
$inspector2 = Inspector::getInstance('FASTAPI\Router');
if ($inspector1 === $inspector2) {
    echo "✓ Inspector singleton works correctly - same instance returned\n";
} else {
    echo "✗ Inspector singleton failed - different instances returned\n";
}

// Test WebSocketServer singleton
echo "\n7. Testing WebSocketServer::getInstance()...\n";
$websocket1 = WebSocketServer::getInstance();
$websocket2 = WebSocketServer::getInstance();
if ($websocket1 === $websocket2) {
    echo "✓ WebSocketServer singleton works correctly - same instance returned\n";
} else {
    echo "✗ WebSocketServer singleton failed - different instances returned\n";
}

// Test App's websocket method uses singleton
echo "\n8. Testing App::websocket() uses WebSocketServer singleton...\n";
$appWebsocket1 = $app1->websocket();
$appWebsocket2 = $app1->websocket();
if ($appWebsocket1 === $appWebsocket2) {
    echo "✓ App::websocket() uses WebSocketServer singleton correctly\n";
} else {
    echo "✗ App::websocket() does not use WebSocketServer singleton\n";
}

// Test that App uses Router singleton
echo "\n9. Testing App uses Router singleton...\n";
$appRouter = $app1->getRouter();
if ($appRouter === $router1) {
    echo "✓ App uses Router singleton correctly\n";
} else {
    echo "✗ App does not use Router singleton\n";
}

// Test cloning prevention
echo "\n10. Testing cloning prevention...\n";
try {
    $clonedApp = clone $app1;
    echo "✗ App cloning prevention failed\n";
} catch (Error $e) {
    echo "✓ App cloning prevention works correctly\n";
}

try {
    $clonedRouter = clone $router1;
    echo "✗ Router cloning prevention failed\n";
} catch (Error $e) {
    echo "✓ Router cloning prevention works correctly\n";
}

try {
    $clonedToken = clone $token1;
    echo "✗ Token cloning prevention failed\n";
} catch (Error $e) {
    echo "✓ Token cloning prevention works correctly\n";
}

try {
    $clonedCustomTime = clone $customTime1;
    echo "✗ CustomTime cloning prevention failed\n";
} catch (Error $e) {
    echo "✓ CustomTime cloning prevention works correctly\n";
}

try {
    $clonedInspector = clone $inspector1;
    echo "✗ Inspector cloning prevention failed\n";
} catch (Error $e) {
    echo "✓ Inspector cloning prevention works correctly\n";
}

try {
    $clonedWebSocket = clone $websocket1;
    echo "✗ WebSocketServer cloning prevention failed\n";
} catch (Error $e) {
    echo "✓ WebSocketServer cloning prevention works correctly\n";
}

echo "\n=== Singleton Pattern Implementation Complete ===\n";
echo "All classes now support getInstance() method for container integration.\n";
echo "Classes with getInstance(): App, Router, Token, CustomTime, Inspector, WebSocketServer\n";
echo "Utility classes (static methods only): StringMethods, ArrayMethods\n";
echo "Request/Response classes: Not singleton (per-request instances)\n";

