# WebSocket Event Queue

The WebSocket Event Queue feature allows you to broadcast messages to WebSocket clients from your regular PHP scripts without maintaining a persistent connection. This is perfect for sending real-time notifications from your API endpoints.

## Table of Contents

- [Overview](#overview)
- [How It Works](#how-it-works)
- [Setup](#setup)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Examples](#examples)
- [Best Practices](#best-practices)

## Overview

The event queue system uses file-based queueing to bridge your stateless PHP API with the stateful WebSocket server:

1. Your API endpoints queue events to a directory
2. The WebSocket server continuously monitors this directory
3. When new events are found, they are broadcast to all connected clients
4. Processed events are automatically cleaned up

## How It Works

```
┌─────────────────┐         ┌──────────────┐         ┌─────────────────┐
│   API Request   │         │ Event Queue  │         │ WebSocket Server│
│  (POST /api)    │ ──────▶ │  (files)     │ ──────▶ │   (broadcast)   │
└─────────────────┘         └──────────────┘         └─────────────────┘
                                                              │
                                                              ▼
                                                      ┌─────────────────┐
                                                      │ Connected Clients│
                                                      └─────────────────┘
```

## Setup

### 1. Configure WebSocket Server with Event Queue

```php
use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

$app = App::getInstance();

$server = WebSocketServer::getInstance($app)
    ->host('0.0.0.0')
    ->port(8081)
    ->eventQueue(__DIR__ . '/logs/ws_queue'); // Enable event queue

// Define routes
$server->on('/notifications', function ($conn) {
    // Handle connection
    $conn->send(json_encode([
        'event' => 'connected',
        'payload' => ['message' => 'Welcome!']
    ]));
});

// Start server (blocking)
$server->start();
```

### 2. Queue Events from Your API

```php
use FASTAPI\WebSocket\WebSocketServer;

// In your API endpoint
$app->post('/api/appointments', function($request) {
    $queuePath = __DIR__ . '/logs/ws_queue';
    
    // Save appointment to database
    $appointmentId = saveAppointment($request->body());
    
    // Queue WebSocket event
    WebSocketServer::queueEvent($queuePath, 'appointment_created', [
        'appointment_id' => $appointmentId,
        'patient_name' => 'John Doe',
        'doctor_name' => 'Dr. Smith',
        'time' => '2025-10-15 14:00:00'
    ]);
    
    return $response->setJsonResponse([
        'success' => true,
        'appointment_id' => $appointmentId
    ]);
});
```

## Usage

### Basic Event Queueing

```php
use FASTAPI\WebSocket\WebSocketServer;

// Queue a simple event
WebSocketServer::queueEvent(
    '/path/to/queue',
    'user_logged_in',
    ['user_id' => 123, 'username' => 'john']
);
```

### Event Structure

Each queued event automatically includes:

```json
{
    "event": "your_event_name",
    "payload": {
        // Your custom data
    },
    "timestamp": 1729000000,
    "queued_at": 1729000000.123456
}
```

### Broadcasting to Clients

The WebSocket server automatically broadcasts queued events to all connected clients in this format:

```json
{
    "event": "your_event_name",
    "payload": {
        // Your custom data
    },
    "timestamp": 1729000000
}
```

## API Reference

### WebSocketServer Methods

#### `eventQueue(string $path): WebSocketServer`

Enable event queue processing.

**Parameters:**
- `$path` - Directory path where queue files will be stored

**Returns:** `WebSocketServer` (for chaining)

**Example:**
```php
$server->eventQueue(__DIR__ . '/logs/ws_queue');
```

#### `queueEvent(string $queuePath, string $event, mixed $payload = null): bool`

Static method to queue an event from anywhere in your application.

**Parameters:**
- `$queuePath` - Directory path for queue files
- `$event` - Event name/type
- `$payload` - Data to send with the event (optional)

**Returns:** `bool` - Success status

**Example:**
```php
WebSocketServer::queueEvent(
    __DIR__ . '/logs/ws_queue',
    'notification',
    ['message' => 'Hello World!']
);
```

## Examples

### Example 1: Appointment Notifications

**WebSocket Server (runs continuously):**
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

$app = App::getInstance();

$server = WebSocketServer::getInstance($app)
    ->host('0.0.0.0')
    ->port(8081)
    ->eventQueue(__DIR__ . '/logs/ws_queue');

$server->on('/appointments', function ($conn) {
    echo "New connection\n";
});

$server->start();
```

**API Endpoint (your regular API):**
```php
<?php
use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

$app = App::getInstance();

$app->post('/api/appointments', function($request) {
    $response = new \FASTAPI\Response();
    $queuePath = __DIR__ . '/logs/ws_queue';
    
    // Save appointment
    $appointmentId = saveToDatabase($request->body());
    
    // Notify all connected clients
    WebSocketServer::queueEvent($queuePath, 'appointment_created', [
        'appointment_id' => $appointmentId,
        'patient' => $request->body()['patient_name'],
        'time' => $request->body()['time']
    ]);
    
    return $response->setJsonResponse(['success' => true]);
});

$app->run();
```

### Example 2: Real-time Status Updates

```php
// Update order status
$app->put('/api/orders/:id/status', function($request) use ($queuePath) {
    $orderId = $request->params('id');
    $status = $request->body()['status'];
    
    // Update database
    updateOrderStatus($orderId, $status);
    
    // Broadcast to all clients
    WebSocketServer::queueEvent($queuePath, 'order_status_changed', [
        'order_id' => $orderId,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse(['success' => true]);
});
```

### Example 3: Chat Messages

```php
// Send chat message
$app->post('/api/chat/messages', function($request) use ($queuePath) {
    $message = $request->body();
    
    // Save message to database
    $messageId = saveMessage($message);
    
    // Broadcast to all chat participants
    WebSocketServer::queueEvent($queuePath, 'new_message', [
        'message_id' => $messageId,
        'user_id' => $message['user_id'],
        'username' => $message['username'],
        'text' => $message['text'],
        'sent_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse(['success' => true]);
});
```

## Best Practices

### 1. Queue Path Management

- Use a dedicated directory for queue files
- Ensure the directory has proper write permissions (0755)
- Keep it outside your web root for security
- Consider using environment variables for the path

```php
$queuePath = $_ENV['WS_QUEUE_PATH'] ?? __DIR__ . '/logs/ws_queue';
```

### 2. Error Handling

Always check if event queueing was successful:

```php
$queued = WebSocketServer::queueEvent($queuePath, 'event_name', $data);

if (!$queued) {
    // Log error or handle gracefully
    error_log('Failed to queue WebSocket event');
}
```

### 3. Event Naming

Use descriptive, consistent event names:

```php
// Good
'appointment_created'
'order_status_changed'
'user_logged_in'

// Avoid
'event1'
'update'
'data'
```

### 4. Payload Structure

Keep payloads consistent and well-structured:

```php
// Good
WebSocketServer::queueEvent($queuePath, 'notification', [
    'type' => 'info',
    'title' => 'New Message',
    'message' => 'You have a new message',
    'timestamp' => time()
]);

// Avoid
WebSocketServer::queueEvent($queuePath, 'notification', 'You have a message');
```

### 5. Queue Monitoring

The server automatically:
- Checks the queue every 100ms
- Processes events in chronological order
- Removes processed files
- Handles invalid/corrupt files gracefully

### 6. Performance Considerations

- Events are processed asynchronously
- Queue processing adds minimal overhead (~100ms check interval)
- File I/O is optimized with suppressed errors (@)
- Old queue files are automatically cleaned up

### 7. Testing

Use the provided test client:

1. Start WebSocket server: `php examples/event_queue_websocket_server.php`
2. Start API server: `php -S localhost:8080 examples/api_with_websocket_queue.php`
3. Open `examples/websocket_client_test.html` in your browser
4. Test API calls and see real-time broadcasts

## Security Considerations

1. **Queue Directory Permissions**: Ensure only your application can write to the queue directory
2. **Input Validation**: Validate data before queueing to prevent injection attacks
3. **Rate Limiting**: Consider implementing rate limits on event queueing
4. **Authentication**: Implement WebSocket authentication for sensitive data

## Troubleshooting

### Events Not Broadcasting

1. Check queue directory exists and is writable
2. Verify WebSocket server is running
3. Check server logs for errors
4. Ensure queue path is consistent between API and WebSocket server

### Queue Files Accumulating

1. Check if WebSocket server is running
2. Verify server has read/write permissions
3. Check for errors in queue processing
4. Manually clean old queue files if needed

### High Memory Usage

1. Reduce queue check interval if processing many events
2. Implement queue file cleanup for old events
3. Consider alternative storage (Redis, etc.) for high-volume scenarios

## Advanced Usage

### Custom Queue Processing Interval

The default check interval is 100ms. You can modify this by editing the WebSocketServer class:

```php
// In processEventQueue() method
if ($now - $this->lastQueueCheck < 0.1) { // 100ms
    return;
}
```

### Alternative Storage

For high-volume applications, consider replacing file-based queueing with:
- Redis Pub/Sub
- RabbitMQ
- Database-based queuing

## See Also

- [WebSocket Documentation](websocket.md)
- [WebSocket Quick Reference](websocket-quick-reference.md)
- [API Reference](api-reference.md)

