<?php
/**
 * WebSocket Event Queue Example
 * 
 * This example demonstrates how to use the event queue feature to broadcast
 * WebSocket messages from your regular PHP scripts without maintaining a 
 * persistent connection.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

echo "=== WebSocket Event Queue Example ===\n\n";

// Example 1: Setting up the WebSocket server with event queue
echo "--- Example 1: WebSocket Server Setup ---\n";

$app = App::getInstance();

$server = WebSocketServer::getInstance($app)
    ->host('0.0.0.0')
    ->port(8081)
    ->eventQueue(__DIR__ . '/../logs/ws_queue'); // Enable event queue

// Route for appointment notifications
$server->on('/appointments', function ($conn) {
    echo "New appointment notification connection established\n";
    
    // Send welcome message
    $conn->send(json_encode([
        'event' => 'welcome',
        'payload' => [
            'message' => 'Connected to appointment notifications',
            'timestamp' => time()
        ]
    ]));
});

// Route for general notifications
$server->on('/notifications', function ($conn) {
    echo "New notification connection established\n";
    
    $conn->send(json_encode([
        'event' => 'connected',
        'payload' => [
            'message' => 'Connected to notifications',
            'timestamp' => time()
        ]
    ]));
});

echo "✓ WebSocket server configured with event queue\n";
echo "✓ Queue path: " . __DIR__ . '/../logs/ws_queue' . "\n";
echo "✓ Server will start on ws://0.0.0.0:8081\n\n";

// Example 2: Demonstrate how to queue events from external scripts
echo "--- Example 2: Queueing Events from External Scripts ---\n\n";

echo "In your regular PHP scripts (controllers, API endpoints, etc.),\n";
echo "you can queue events like this:\n\n";

echo "<?php\n";
echo "use FASTAPI\\WebSocket\\WebSocketServer;\n\n";
echo "// Queue an appointment notification\n";
echo "WebSocketServer::queueEvent(\n";
echo "    __DIR__ . '/../logs/ws_queue',\n";
echo "    'appointment_created',\n";
echo "    [\n";
echo "        'appointment_id' => 123,\n";
echo "        'patient_name' => 'John Doe',\n";
echo "        'doctor_name' => 'Dr. Smith',\n";
echo "        'time' => '2025-10-15 14:00:00'\n";
echo "    ]\n";
echo ");\n\n";

echo "// The WebSocket server will automatically pick up and broadcast this event\n";
echo "// to all connected clients on the /appointments route.\n\n";

// Example 3: Real-world usage scenarios
echo "--- Example 3: Real-World Usage Scenarios ---\n\n";

echo "Scenario 1: Appointment Creation\n";
echo "When a new appointment is created in your API:\n";
echo "  - Your API endpoint saves the appointment to database\n";
echo "  - Calls WebSocketServer::queueEvent() to notify clients\n";
echo "  - All connected clients receive real-time notification\n\n";

echo "Scenario 2: Status Updates\n";
echo "When an appointment status changes:\n";
echo "  - Update status in database\n";
echo "  - Queue event with new status\n";
echo "  - Clients see real-time status update\n\n";

echo "Scenario 3: Chat Messages\n";
echo "When a new message is sent:\n";
echo "  - Save message to database\n";
echo "  - Queue event with message data\n";
echo "  - All chat participants receive message instantly\n\n";

// Example 4: Starting the server
echo "--- Starting WebSocket Server ---\n";
echo "Press Ctrl+C to stop the server\n\n";

// Start the server (this is a blocking call)
$server->start();

