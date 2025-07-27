# WebSocket Support

FastAPI includes a pure PHP WebSocket implementation for real-time communication. This feature is 100% backward compatible and integrates seamlessly with existing HTTP routes.

## Table of Contents

- [Quick Start](#quick-start)
- [Basic Usage](#basic-usage)
- [Server Configuration](#server-configuration)
- [Connection Management](#connection-management)
- [Broadcasting](#broadcasting)
- [Multiple Servers](#multiple-servers)
- [Error Handling](#error-handling)
- [Protocol Support](#protocol-support)
- [Performance](#performance)
- [Examples](#examples)

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;
use FASTAPI\WebSocket\WebSocketConnection;

$app = App::getInstance();

// Create WebSocket server
$websocket = $app->websocket(8080, 'localhost');

// Register WebSocket route
$websocket->on('/chat', function(WebSocketConnection $connection) {
    echo "New chat connection established\n";
    
    // Send welcome message
    $connection->send(json_encode([
        'event' => 'welcome',
        'message' => 'Welcome to the chat!'
    ]));
    
    // Handle incoming messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            $data = json_decode($message, true);
            echo "Received: " . $data['message'] . "\n";
            
            // Echo back
            $connection->send(json_encode([
                'event' => 'message',
                'data' => $data
            ]));
        }
    }
});

// Start the server
$websocket->start();
```

## Basic Usage

### Creating a WebSocket Server

```php
// Basic server
$websocket = $app->websocket(8080, 'localhost');

// With fluent API
$websocket = $app->websocket()
    ->port(8080)
    ->host('0.0.0.0');
```

### Registering Routes

```php
// Simple route
$websocket->on('/chat', function(WebSocketConnection $connection) {
    $connection->send('Hello from chat!');
});

// Route with message handling
$websocket->on('/notifications', function(WebSocketConnection $connection) {
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            // Process notification
            $connection->send('Notification received');
        }
    }
});
```

## Server Configuration

### Port and Host

```php
// Default configuration
$websocket = $app->websocket(8080, 'localhost');

// Custom configuration
$websocket = $app->websocket()
    ->port(9000)
    ->host('0.0.0.0');  // Listen on all interfaces
```

### Server Control

```php
$websocket = $app->websocket(8080);

// Start server (blocking)
$websocket->start();

// Stop server
$websocket->stop();

// Check if running
if ($websocket->isRunning()) {
    echo "Server is running\n";
}
```

## Connection Management

### Connection Information

```php
$websocket->on('/example', function(WebSocketConnection $connection) {
    // Get connection details
    $path = $connection->getPath();        // /example
    $headers = $connection->getHeaders();  // Request headers
    $userAgent = $connection->getHeader('User-Agent');
    
    // Check connection status
    if ($connection->isConnected()) {
        echo "Connection is active\n";
    }
});
```

### Message Communication

```php
$websocket->on('/chat', function(WebSocketConnection $connection) {
    // Send text message
    $connection->send('Hello from server!');
    
    // Send JSON message
    $connection->send(json_encode([
        'event' => 'message',
        'data' => 'Hello from server!',
        'timestamp' => time()
    ]));
    
    // Read incoming messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            echo "Received: $message\n";
            
            // Echo back
            $connection->send("Echo: $message");
        }
    }
});
```

### Connection Lifecycle

```php
$websocket->on('/chat', function(WebSocketConnection $connection) {
    echo "Connection established\n";
    
    // Send welcome message
    $connection->send(json_encode([
        'event' => 'connected',
        'message' => 'Welcome to the chat!'
    ]));
    
    // Handle messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            // Process message
            $connection->send("Processed: $message");
        }
    }
    
    // Connection closed
    echo "Connection closed\n";
});
```

## Broadcasting

### Broadcast to All Connections

```php
// Broadcast simple message
$websocket->broadcast('user_joined', [
    'user' => 'John Doe',
    'timestamp' => time()
]);

// Broadcast with custom data
$websocket->broadcast('chat_message', [
    'user' => 'Alice',
    'message' => 'Hello everyone!',
    'room' => 'general',
    'timestamp' => time()
]);
```

### Broadcast from HTTP Routes

```php
// HTTP route that broadcasts to WebSocket
$app->post('/api/messages', function($request) {
    $data = $request->getData();
    
    // Broadcast to WebSocket clients
    $websocket->broadcast('new_message', $data);
    
    return (new Response())->setJsonResponse([
        'status' => 'sent',
        'message' => 'Message broadcasted'
    ]);
});
```

### Connection Information

```php
// Get connection count
$count = $websocket->getConnectionCount();
echo "Active connections: $count\n";

// Get all connections
$connections = $websocket->getConnections();
foreach ($connections as $connection) {
    if ($connection->isConnected()) {
        $connection->send('Server message');
    }
}
```

## Multiple Servers

### Separate Servers for Different Purposes

```php
// Chat server
$chatServer = $app->websocket(8080, 'localhost')
    ->on('/chat', function($connection) {
        // Handle chat messages
    });

// Notifications server
$notifServer = $app->websocket(8081, 'localhost')
    ->on('/notifications', function($connection) {
        // Handle notifications
    });

// Real-time data server
$dataServer = $app->websocket(8082, 'localhost')
    ->on('/realtime', function($connection) {
        // Handle real-time data
    });
```

### Server Management

```php
// Start all servers
$chatServer->start();
$notifServer->start();
$dataServer->start();

// Stop all servers
$chatServer->stop();
$notifServer->stop();
$dataServer->stop();
```

## Error Handling

### Server Error Handling

```php
try {
    $websocket = $app->websocket(8080);
    $websocket->start();
} catch (Exception $e) {
    echo "WebSocket server error: " . $e->getMessage() . "\n";
}
```

### Connection Error Handling

```php
$websocket->on('/chat', function(WebSocketConnection $connection) {
    try {
        while ($connection->isConnected()) {
            $message = $connection->read();
            if ($message) {
                // Process message
                $connection->send("Processed: $message");
            }
        }
    } catch (Exception $e) {
        echo "Connection error: " . $e->getMessage() . "\n";
        $connection->close();
    }
});
```

### Graceful Shutdown

```php
// Handle shutdown signals
pcntl_signal(SIGINT, function() use ($websocket) {
    echo "Shutting down WebSocket server...\n";
    $websocket->stop();
    exit(0);
});

$websocket->start();
```

## Protocol Support

### WebSocket Protocol Features

The WebSocket implementation supports the full WebSocket protocol:

- **Handshake**: Automatic WebSocket upgrade handshake
- **Framing**: Text, binary, ping, pong, and close frames
- **Masking**: Client-to-server message masking
- **Extensions**: Support for WebSocket extensions
- **Status Codes**: Proper close codes and status handling

### Frame Types

```php
// Text frame (default)
$connection->send('Hello World');

// Binary frame
$connection->send(binary_data);

// Ping/Pong (automatic)
// The server automatically responds to ping frames with pong

// Close frame
$connection->close();
```

## Performance

### Performance Characteristics

- **Pure PHP**: No external dependencies for WebSocket functionality
- **Lightweight**: Minimal memory footprint
- **Scalable**: Supports multiple concurrent connections
- **Efficient**: Non-blocking I/O with proper resource management

### Optimization Tips

```php
// Use efficient message handling
$websocket->on('/chat', function(WebSocketConnection $connection) {
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            // Process message efficiently
            $data = json_decode($message, true);
            if ($data) {
                // Handle structured data
                $connection->send(json_encode(['status' => 'ok']));
            }
        }
    }
});
```

## Examples

### Chat Application

```php
<?php
require 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketConnection;

$app = App::getInstance();
$websocket = $app->websocket(8080, 'localhost');

$websocket->on('/chat', function(WebSocketConnection $connection) {
    echo "New chat connection\n";
    
    // Send welcome
    $connection->send(json_encode([
        'event' => 'welcome',
        'message' => 'Welcome to the chat!'
    ]));
    
    // Handle messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            $data = json_decode($message, true);
            
            if ($data && isset($data['message'])) {
                // Broadcast to all clients
                $websocket->broadcast('chat_message', [
                    'user' => $data['user'] ?? 'Anonymous',
                    'message' => $data['message'],
                    'timestamp' => time()
                ]);
            }
        }
    }
});

$websocket->start();
```

### Real-time Notifications

```php
<?php
require 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketConnection;

$app = App::getInstance();
$websocket = $app->websocket(8081, 'localhost');

$websocket->on('/notifications', function(WebSocketConnection $connection) {
    echo "New notification connection\n";
    
    // Send initial notifications
    $connection->send(json_encode([
        'event' => 'notifications',
        'data' => [
            ['id' => 1, 'message' => 'Welcome!'],
            ['id' => 2, 'message' => 'System ready']
        ]
    ]));
    
    // Keep connection alive
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            // Handle client requests
            $data = json_decode($message, true);
            if ($data && $data['action'] === 'mark_read') {
                $connection->send(json_encode([
                    'event' => 'notification_read',
                    'id' => $data['id']
                ]));
            }
        }
    }
});

// HTTP route to trigger notifications
$app->post('/api/notify', function($request) use ($websocket) {
    $data = $request->getData();
    
    $websocket->broadcast('new_notification', [
        'id' => uniqid(),
        'message' => $data['message'],
        'type' => $data['type'] ?? 'info',
        'timestamp' => time()
    ]);
    
    return (new Response())->setJsonResponse(['status' => 'sent']);
});

$websocket->start();
```

### Live Dashboard

```php
<?php
require 'vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketConnection;

$app = App::getInstance();
$websocket = $app->websocket(8082, 'localhost');

$websocket->on('/dashboard', function(WebSocketConnection $connection) {
    echo "New dashboard connection\n";
    
    // Send initial data
    $connection->send(json_encode([
        'event' => 'dashboard_data',
        'data' => [
            'users_online' => 42,
            'messages_today' => 156,
            'system_status' => 'healthy'
        ]
    ]));
    
    // Update dashboard every 5 seconds
    $lastUpdate = time();
    while ($connection->isConnected()) {
        $currentTime = time();
        
        if ($currentTime - $lastUpdate >= 5) {
            // Send updated data
            $connection->send(json_encode([
                'event' => 'dashboard_update',
                'data' => [
                    'users_online' => rand(40, 50),
                    'messages_today' => rand(150, 160),
                    'system_status' => 'healthy',
                    'last_update' => $currentTime
                ]
            ]));
            
            $lastUpdate = $currentTime;
        }
        
        // Check for client messages
        $message = $connection->read();
        if ($message) {
            $data = json_decode($message, true);
            // Handle client requests
        }
        
        usleep(100000); // 100ms delay
    }
});

$websocket->start();
```

## Integration with HTTP Routes

WebSocket functionality works seamlessly alongside HTTP routes:

```php
// HTTP routes
$app->get('/api/users', function($request) {
    return (new Response())->setJsonResponse(['users' => $users]);
});

$app->post('/api/messages', function($request) use ($websocket) {
    $data = $request->getData();
    
    // Broadcast to WebSocket clients
    $websocket->broadcast('new_message', $data);
    
    return (new Response())->setJsonResponse(['status' => 'sent']);
});

// WebSocket routes
$websocket->on('/chat', function($connection) {
    // Handle real-time chat
});

$websocket->on('/notifications', function($connection) {
    // Handle real-time notifications
});
```

This comprehensive WebSocket implementation provides all the tools needed for real-time communication while maintaining the framework's pure, focused, and fast philosophy. 