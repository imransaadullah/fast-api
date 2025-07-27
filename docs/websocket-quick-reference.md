# WebSocket Quick Reference

## Basic Setup

```php
use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;
use FASTAPI\WebSocket\WebSocketConnection;

$app = App::getInstance();
$websocket = $app->websocket(8080, 'localhost');
```

## Common Patterns

### 1. Simple Echo Server

```php
$websocket->on('/echo', function(WebSocketConnection $connection) {
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            $connection->send("Echo: $message");
        }
    }
});
```

### 2. Chat Room

```php
$websocket->on('/chat', function(WebSocketConnection $connection) {
    // Send welcome
    $connection->send(json_encode(['event' => 'welcome']));
    
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            $data = json_decode($message, true);
            // Broadcast to all clients
            $websocket->broadcast('chat_message', $data);
        }
    }
});
```

### 3. Real-time Notifications

```php
$websocket->on('/notifications', function(WebSocketConnection $connection) {
    // Send initial notifications
    $connection->send(json_encode([
        'event' => 'notifications',
        'data' => getNotifications()
    ]));
    
    while ($connection->isConnected()) {
        $message = $connection->read();
        if ($message) {
            // Handle client requests
        }
    }
});

// Trigger from HTTP route
$app->post('/api/notify', function($request) use ($websocket) {
    $websocket->broadcast('new_notification', $request->getData());
});
```

### 4. Live Dashboard

```php
$websocket->on('/dashboard', function(WebSocketConnection $connection) {
    // Send initial data
    $connection->send(json_encode([
        'event' => 'dashboard_data',
        'data' => getDashboardData()
    ]));
    
    // Update every 5 seconds
    $lastUpdate = time();
    while ($connection->isConnected()) {
        $currentTime = time();
        if ($currentTime - $lastUpdate >= 5) {
            $connection->send(json_encode([
                'event' => 'dashboard_update',
                'data' => getDashboardData()
            ]));
            $lastUpdate = $currentTime;
        }
        usleep(100000); // 100ms delay
    }
});
```

## Server Methods

| Method | Description | Example |
|--------|-------------|---------|
| `port($port)` | Set server port | `$websocket->port(8080)` |
| `host($host)` | Set server host | `$websocket->host('0.0.0.0')` |
| `on($path, $handler)` | Register route | `$websocket->on('/chat', $handler)` |
| `start()` | Start server | `$websocket->start()` |
| `stop()` | Stop server | `$websocket->stop()` |
| `broadcast($event, $payload)` | Broadcast to all | `$websocket->broadcast('message', $data)` |
| `getConnectionCount()` | Get active connections | `$count = $websocket->getConnectionCount()` |
| `getConnections()` | Get all connections | `$connections = $websocket->getConnections()` |

## Connection Methods

| Method | Description | Example |
|--------|-------------|---------|
| `send($message)` | Send message | `$connection->send('Hello')` |
| `read()` | Read message | `$message = $connection->read()` |
| `close()` | Close connection | `$connection->close()` |
| `isConnected()` | Check status | `if ($connection->isConnected())` |
| `getPath()` | Get request path | `$path = $connection->getPath()` |
| `getHeaders()` | Get request headers | `$headers = $connection->getHeaders()` |
| `getHeader($name)` | Get specific header | `$ua = $connection->getHeader('User-Agent')` |

## Message Formats

### JSON Messages

```php
// Send structured data
$connection->send(json_encode([
    'event' => 'user_joined',
    'data' => [
        'user' => 'John',
        'timestamp' => time()
    ]
]));

// Read and parse
$message = $connection->read();
$data = json_decode($message, true);
if ($data && isset($data['event'])) {
    // Handle event
}
```

### Text Messages

```php
// Simple text
$connection->send('Hello World');

// Read text
$message = $connection->read();
echo "Received: $message";
```

## Error Handling

### Try-Catch Pattern

```php
try {
    $websocket = $app->websocket(8080);
    $websocket->start();
} catch (Exception $e) {
    echo "Server error: " . $e->getMessage();
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
            }
        }
    } catch (Exception $e) {
        echo "Connection error: " . $e->getMessage();
        $connection->close();
    }
});
```

## Multiple Servers

```php
// Chat server
$chatServer = $app->websocket(8080)
    ->on('/chat', $chatHandler);

// Notifications server
$notifServer = $app->websocket(8081)
    ->on('/notifications', $notifHandler);

// Data server
$dataServer = $app->websocket(8082)
    ->on('/realtime', $dataHandler);
```

## Integration with HTTP

```php
// HTTP routes
$app->get('/api/users', function($request) {
    return (new Response())->setJsonResponse(['users' => $users]);
});

$app->post('/api/messages', function($request) use ($websocket) {
    $data = $request->getData();
    $websocket->broadcast('new_message', $data);
    return (new Response())->setJsonResponse(['status' => 'sent']);
});

// WebSocket routes
$websocket->on('/chat', function($connection) {
    // Handle real-time chat
});
```

## Performance Tips

1. **Use efficient message handling**
   ```php
   while ($connection->isConnected()) {
       $message = $connection->read();
       if ($message) {
           $data = json_decode($message, true);
           if ($data) {
               // Process structured data
           }
       }
   }
   ```

2. **Avoid blocking operations**
   ```php
   // Good: Non-blocking
   $connection->send($message);
   
   // Avoid: Blocking operations in WebSocket handlers
   sleep(1); // Don't do this
   ```

3. **Use appropriate delays**
   ```php
   // For periodic updates
   usleep(100000); // 100ms delay
   ```

## Common Use Cases

### 1. Chat Application
- Real-time messaging
- User presence
- Message broadcasting

### 2. Live Notifications
- Push notifications
- Status updates
- Event broadcasting

### 3. Real-time Dashboard
- Live data updates
- System monitoring
- Performance metrics

### 4. Collaborative Tools
- Document editing
- Shared whiteboards
- Live collaboration

### 5. Gaming
- Real-time game state
- Player interactions
- Live scoring

## Troubleshooting

### Connection Issues
- Check port availability
- Verify firewall settings
- Ensure proper WebSocket handshake

### Performance Issues
- Monitor connection count
- Check memory usage
- Optimize message handling

### Debugging
```php
// Enable error reporting
error_reporting(E_ALL);

// Add logging
echo "New connection: " . $connection->getPath() . "\n";
echo "Received: $message\n";
``` 