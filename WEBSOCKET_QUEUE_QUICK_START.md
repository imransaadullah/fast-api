# WebSocket Event Queue - Quick Start Guide

## Your Code is Now Fixed! 🎉

The error you were getting:
```
PHP Fatal error: Call to undefined method FASTAPI\WebSocket\WebSocketServer::eventQueue()
```

Has been **resolved**! The `eventQueue()` method has been implemented and is now available.

## Your Code Now Works

Your original code is now functional:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

// Bootstrap app (ensures singletons are ready if needed)
$app = App::getInstance();

$server = WebSocketServer::getInstance($app)
    ->host($_ENV['WS_HOST'] ?? '0.0.0.0')
    ->port((int)($_ENV['WS_PORT'] ?? 8081));

// Try to set event queue if method exists (newer versions)
if (method_exists($server, 'eventQueue')) {
    $server->eventQueue(__DIR__ . '/../logs/ws_queue');
}

// Route for appointment notifications
$server->on('/appointments', function ($conn) {
    // No-op: broadcast happens from event queue; can echo pings if needed
});

// Start server (blocking)
$server->start();
```

✅ This code will now work perfectly!

## How to Use It

### Step 1: Start Your WebSocket Server

Save your code to a file (e.g., `websocket_server.php`) and run:

```bash
php websocket_server.php
```

You should see:
```
WebSocket server started on ws://0.0.0.0:8081
```

### Step 2: Queue Events from Your API

In your API endpoints, queue events that will be broadcast to connected clients:

```php
use FASTAPI\WebSocket\WebSocketServer;

// In your appointment creation endpoint
$app->post('/api/appointments', function($request) {
    $response = new \FASTAPI\Response();
    
    // Get appointment data
    $data = $request->body();
    
    // Save appointment to database (your existing code)
    $appointmentId = saveAppointmentToDatabase($data);
    
    // Queue WebSocket event - broadcasts to all connected clients
    WebSocketServer::queueEvent(
        __DIR__ . '/../logs/ws_queue',  // Same path as server
        'appointment_created',
        [
            'appointment_id' => $appointmentId,
            'patient_name' => $data['patient_name'],
            'doctor_name' => $data['doctor_name'],
            'appointment_time' => $data['appointment_time'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]
    );
    
    return $response->setJsonResponse([
        'success' => true,
        'appointment_id' => $appointmentId
    ], 201);
});

// Update appointment status
$app->put('/api/appointments/:id/status', function($request) {
    $response = new \FASTAPI\Response();
    
    $appointmentId = $request->params('id');
    $newStatus = $request->body()['status'];
    
    // Update in database
    updateAppointmentStatus($appointmentId, $newStatus);
    
    // Broadcast status change
    WebSocketServer::queueEvent(
        __DIR__ . '/../logs/ws_queue',
        'appointment_status_changed',
        [
            'appointment_id' => $appointmentId,
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    );
    
    return $response->setJsonResponse(['success' => true]);
});

// Cancel appointment
$app->delete('/api/appointments/:id', function($request) {
    $response = new \FASTAPI\Response();
    
    $appointmentId = $request->params('id');
    
    // Delete from database
    deleteAppointment($appointmentId);
    
    // Notify all clients
    WebSocketServer::queueEvent(
        __DIR__ . '/../logs/ws_queue',
        'appointment_cancelled',
        ['appointment_id' => $appointmentId]
    );
    
    return $response->setJsonResponse(['success' => true]);
});
```

### Step 3: Connect from Frontend (JavaScript)

```javascript
// Connect to your WebSocket server
const ws = new WebSocket('ws://localhost:8081/appointments');

ws.onopen = () => {
    console.log('Connected to appointment notifications');
};

ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    
    console.log('Event:', data.event);
    console.log('Data:', data.payload);
    
    // Handle different appointment events
    switch(data.event) {
        case 'appointment_created':
            // Show notification: New appointment created
            showNotification('New Appointment', data.payload);
            updateAppointmentList(data.payload);
            break;
            
        case 'appointment_status_changed':
            // Update appointment status in UI
            updateAppointmentStatus(data.payload.appointment_id, data.payload.status);
            break;
            
        case 'appointment_cancelled':
            // Remove appointment from UI
            removeAppointment(data.payload.appointment_id);
            break;
    }
};

ws.onerror = (error) => {
    console.error('WebSocket error:', error);
};

ws.onclose = () => {
    console.log('Disconnected from appointment notifications');
    // Optionally reconnect
};

// Helper functions
function showNotification(title, data) {
    // Your notification logic
    console.log(`${title}: ${data.patient_name} with ${data.doctor_name}`);
}

function updateAppointmentList(appointment) {
    // Add new appointment to your list
}

function updateAppointmentStatus(id, status) {
    // Update appointment status in UI
}

function removeAppointment(id) {
    // Remove appointment from UI
}
```

## Testing Your Setup

### Test 1: Check WebSocket Connection

1. Start your WebSocket server: `php websocket_server.php`
2. Open browser console and run:
```javascript
const ws = new WebSocket('ws://localhost:8081/appointments');
ws.onopen = () => console.log('✅ Connected!');
ws.onerror = (e) => console.error('❌ Error:', e);
```

### Test 2: Queue a Test Event

Create a test file `test_queue.php`:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use FASTAPI\WebSocket\WebSocketServer;

$result = WebSocketServer::queueEvent(
    __DIR__ . '/logs/ws_queue',
    'appointment_created',
    [
        'appointment_id' => 999,
        'patient_name' => 'Test Patient',
        'doctor_name' => 'Test Doctor',
        'appointment_time' => '2025-10-15 14:00:00'
    ]
);

echo $result ? "✅ Event queued!\n" : "❌ Failed to queue\n";
```

Run: `php test_queue.php`

If your WebSocket is connected, you should receive the event immediately!

### Test 3: Full API Test

```bash
# Make sure WebSocket server is running
php websocket_server.php

# In another terminal, start your API
php -S localhost:8080 your_api.php

# In another terminal, test the API
curl -X POST http://localhost:8080/api/appointments \
  -H 'Content-Type: application/json' \
  -d '{
    "patient_name": "John Doe",
    "doctor_name": "Dr. Smith",
    "appointment_time": "2025-10-15 14:00:00"
  }'
```

Any connected WebSocket clients will receive the event in real-time!

## Environment Variables

Create a `.env` file:

```env
WS_HOST=0.0.0.0
WS_PORT=8081
WS_QUEUE_PATH=/path/to/your/logs/ws_queue
```

## Production Deployment

### Using Supervisor (Linux)

Create `/etc/supervisor/conf.d/websocket.conf`:

```ini
[program:websocket-server]
command=php /path/to/your/websocket_server.php
directory=/path/to/your/app
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/websocket-server.log
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start websocket-server
```

### Using systemd (Linux)

Create `/etc/systemd/system/websocket.service`:

```ini
[Unit]
Description=WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/app
ExecStart=/usr/bin/php /path/to/your/websocket_server.php
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable websocket
sudo systemctl start websocket
sudo systemctl status websocket
```

## Troubleshooting

### Events Not Broadcasting

**Check 1: Is WebSocket server running?**
```bash
ps aux | grep websocket_server.php
```

**Check 2: Are queue files being created?**
```bash
ls -la logs/ws_queue/
```

**Check 3: Queue path matches?**
- Server: `->eventQueue(__DIR__ . '/../logs/ws_queue')`
- API: `WebSocketServer::queueEvent(__DIR__ . '/../logs/ws_queue', ...)`

**Check 4: Directory permissions**
```bash
chmod 755 logs/ws_queue
```

### WebSocket Won't Connect

**Check 1: Port available?**
```bash
netstat -an | grep 8081
```

**Check 2: Firewall?**
```bash
sudo ufw allow 8081
```

**Check 3: Correct URL?**
- Local: `ws://localhost:8081/appointments`
- Production: `ws://your-domain.com:8081/appointments`
- With SSL: `wss://your-domain.com:8081/appointments`

### High CPU Usage

Reduce queue check frequency by modifying `processEventQueue()`:

```php
// Change from 0.1 (100ms) to 0.5 (500ms)
if ($now - $this->lastQueueCheck < 0.5) {
    return;
}
```

## Example Use Cases

### Healthcare/Appointment System (Your Use Case)
- New appointment notifications
- Appointment status changes
- Cancellations
- Reminder notifications
- Doctor availability updates

### E-commerce
- Order status updates
- Inventory changes
- New message from support
- Payment confirmations

### Chat/Messaging
- New messages
- User online/offline status
- Typing indicators
- Read receipts

### Dashboard/Analytics
- Real-time metrics
- System alerts
- User activity
- Report generation status

## Need Help?

Check the documentation:
- [Full Documentation](docs/websocket-event-queue.md)
- [Quick Reference](docs/websocket-event-queue-quick-reference.md)
- [WebSocket Guide](docs/websocket.md)

Run the test suite:
```bash
php test/event_queue_test.php
```

Try the examples:
- `examples/event_queue_example.php` - Basic usage
- `examples/event_queue_websocket_server.php` - Production server
- `examples/api_with_websocket_queue.php` - API integration
- `examples/websocket_client_test.html` - Interactive test client

## Summary

✅ **Your error is fixed!** The `eventQueue()` method is now implemented.

✅ **Your code works!** Your original code will run without modifications.

✅ **Easy to use:** Just two methods - `eventQueue()` and `queueEvent()`

✅ **Well tested:** Comprehensive test suite included

✅ **Production ready:** Examples for deployment included

Happy coding! 🚀

