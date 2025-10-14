# WebSocket Event Queue - Quick Reference

## Setup (5 Minutes)

### 1. WebSocket Server

```php
use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

$app = App::getInstance();

$server = WebSocketServer::getInstance($app)
    ->host('0.0.0.0')
    ->port(8081)
    ->eventQueue(__DIR__ . '/logs/ws_queue') // Enable event queue
    ->on('/notifications', function ($conn) {
        // Handle connections
    });

$server->start(); // Blocking call
```

### 2. API Endpoints

```php
use FASTAPI\WebSocket\WebSocketServer;

$queuePath = __DIR__ . '/logs/ws_queue';

$app->post('/api/notification', function($request) use ($queuePath) {
    // Process request
    
    // Queue WebSocket event
    WebSocketServer::queueEvent($queuePath, 'event_name', [
        'data' => 'value'
    ]);
    
    return $response->setJsonResponse(['success' => true]);
});
```

## Common Patterns

### Notification System

```php
// Queue notification
WebSocketServer::queueEvent($queuePath, 'notification', [
    'type' => 'info',
    'title' => 'New Message',
    'message' => 'You have a new message',
    'timestamp' => time()
]);
```

### Resource Creation

```php
// Create resource and notify
$app->post('/api/appointments', function($request) use ($queuePath) {
    $id = createAppointment($request->body());
    
    WebSocketServer::queueEvent($queuePath, 'appointment_created', [
        'appointment_id' => $id,
        'patient' => $request->body()['patient_name'],
        'time' => $request->body()['time']
    ]);
    
    return $response->setJsonResponse(['id' => $id]);
});
```

### Status Updates

```php
// Update status and broadcast
$app->put('/api/orders/:id/status', function($request) use ($queuePath) {
    $orderId = $request->params('id');
    $status = $request->body()['status'];
    
    updateStatus($orderId, $status);
    
    WebSocketServer::queueEvent($queuePath, 'status_updated', [
        'order_id' => $orderId,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse(['success' => true]);
});
```

### Delete Events

```php
// Delete and notify
$app->delete('/api/items/:id', function($request) use ($queuePath) {
    $id = $request->params('id');
    
    deleteItem($id);
    
    WebSocketServer::queueEvent($queuePath, 'item_deleted', [
        'item_id' => $id,
        'deleted_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse(['success' => true]);
});
```

## Client-Side (JavaScript)

```javascript
// Connect to WebSocket
const ws = new WebSocket('ws://localhost:8081/notifications');

ws.onopen = () => {
    console.log('Connected');
};

ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    console.log('Event:', data.event);
    console.log('Payload:', data.payload);
    
    // Handle different events
    switch(data.event) {
        case 'notification':
            showNotification(data.payload);
            break;
        case 'appointment_created':
            updateAppointmentList(data.payload);
            break;
        case 'status_updated':
            updateStatus(data.payload);
            break;
    }
};

ws.onerror = (error) => {
    console.error('WebSocket error:', error);
};

ws.onclose = () => {
    console.log('Disconnected');
};
```

## Testing

### Start Servers

```bash
# Terminal 1: Start WebSocket server
php examples/event_queue_websocket_server.php

# Terminal 2: Start API server
php -S localhost:8080 examples/api_with_websocket_queue.php

# Terminal 3: Test with curl
curl -X POST http://localhost:8080/api/notifications \
  -H 'Content-Type: application/json' \
  -d '{"message":"Test","type":"info"}'
```

### Test Client

Open `examples/websocket_client_test.html` in your browser to see real-time events.

## Environment Variables

```env
# .env file
WS_HOST=0.0.0.0
WS_PORT=8081
WS_QUEUE_PATH=/path/to/queue
```

```php
// Use in your code
$server = WebSocketServer::getInstance($app)
    ->host($_ENV['WS_HOST'] ?? '0.0.0.0')
    ->port((int)($_ENV['WS_PORT'] ?? 8081))
    ->eventQueue($_ENV['WS_QUEUE_PATH'] ?? __DIR__ . '/logs/ws_queue');
```

## Error Handling

```php
// Check if event was queued successfully
$queued = WebSocketServer::queueEvent($queuePath, 'event', $data);

if (!$queued) {
    error_log('Failed to queue WebSocket event');
    // Handle error (retry, log, etc.)
}
```

## Key Methods

| Method | Type | Description |
|--------|------|-------------|
| `eventQueue($path)` | Instance | Enable event queue processing |
| `queueEvent($path, $event, $payload)` | Static | Queue an event from anywhere |

## Event Structure

### Queued Event

```json
{
    "event": "event_name",
    "payload": { "your": "data" },
    "timestamp": 1729000000,
    "queued_at": 1729000000.123456
}
```

### Broadcast Event

```json
{
    "event": "event_name",
    "payload": { "your": "data" },
    "timestamp": 1729000000
}
```

## Best Practices

✅ **DO:**
- Use descriptive event names (`appointment_created`, not `event1`)
- Structure your payloads consistently
- Check queue operation success
- Use environment variables for configuration
- Validate data before queueing

❌ **DON'T:**
- Queue sensitive data without encryption
- Use the same queue for different applications
- Ignore queueEvent() return value
- Queue events in tight loops (consider batching)

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Events not broadcasting | Check if WebSocket server is running and queue path is correct |
| Permission denied | Ensure queue directory is writable (chmod 0755) |
| Files accumulating | Verify WebSocket server is processing queue |
| High memory usage | Consider reducing check interval or using alternative storage |

## Performance Tips

- Queue check interval: 100ms (configurable)
- Automatic cleanup of processed files
- Minimal overhead on main request/response cycle
- Events processed asynchronously

## Full Example

```php
<?php
// server.php - Run this continuously
require_once __DIR__ . '/vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

$app = App::getInstance();

WebSocketServer::getInstance($app)
    ->host('0.0.0.0')
    ->port(8081)
    ->eventQueue(__DIR__ . '/logs/ws_queue')
    ->on('/notifications', function ($conn) {
        echo "New connection\n";
    })
    ->start();
```

```php
<?php
// api.php - Your regular API
require_once __DIR__ . '/vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

$app = App::getInstance();
$queuePath = __DIR__ . '/logs/ws_queue';

$app->post('/api/notify', function($request) use ($queuePath) {
    $response = new \FASTAPI\Response();
    
    WebSocketServer::queueEvent($queuePath, 'notification', [
        'message' => $request->body()['message']
    ]);
    
    return $response->setJsonResponse(['success' => true]);
});

$app->run();
```

## See Also

- [Full WebSocket Event Queue Documentation](websocket-event-queue.md)
- [WebSocket Documentation](websocket.md)
- [Getting Started](getting-started.md)

