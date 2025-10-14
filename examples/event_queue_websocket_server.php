<?php
/**
 * WebSocket Server with Event Queue
 * 
 * This server runs continuously and broadcasts events queued by your API.
 * Run this separately from your main API server.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;
use FASTAPI\WebSocket\WebSocketConnection;

// Bootstrap app
$app = App::getInstance();

// Configuration
$queuePath = __DIR__ . '/../logs/ws_queue';
$host = $_ENV['WS_HOST'] ?? '0.0.0.0';
$port = (int)($_ENV['WS_PORT'] ?? 8081);

echo "=== WebSocket Server with Event Queue ===\n\n";

// Create and configure WebSocket server
$server = WebSocketServer::getInstance($app)
    ->host($host)
    ->port($port)
    ->eventQueue($queuePath); // Enable event queue processing

echo "✓ WebSocket server configured\n";
echo "✓ Host: $host\n";
echo "✓ Port: $port\n";
echo "✓ Queue path: $queuePath\n\n";

// Route for appointment notifications
$server->on('/appointments', function (WebSocketConnection $conn) {
    echo "[" . date('Y-m-d H:i:s') . "] New connection to /appointments\n";
    
    // Send welcome message
    $conn->send(json_encode([
        'event' => 'connected',
        'payload' => [
            'message' => 'Connected to appointment notifications',
            'timestamp' => time()
        ]
    ]));
});

// Route for general notifications
$server->on('/notifications', function (WebSocketConnection $conn) {
    echo "[" . date('Y-m-d H:i:s') . "] New connection to /notifications\n";
    
    // Send welcome message
    $conn->send(json_encode([
        'event' => 'connected',
        'payload' => [
            'message' => 'Connected to notifications',
            'timestamp' => time()
        ]
    ]));
});

// Route for broadcast messages
$server->on('/broadcast', function (WebSocketConnection $conn) {
    echo "[" . date('Y-m-d H:i:s') . "] New connection to /broadcast\n";
    
    // Send welcome message
    $conn->send(json_encode([
        'event' => 'connected',
        'payload' => [
            'message' => 'Connected to broadcast channel',
            'timestamp' => time()
        ]
    ]));
});

echo "WebSocket Routes:\n";
echo "  ws://$host:$port/appointments  - Appointment notifications\n";
echo "  ws://$host:$port/notifications - General notifications\n";
echo "  ws://$host:$port/broadcast     - Broadcast messages\n\n";

echo "How to test:\n";
echo "1. Connect a WebSocket client to one of the routes above\n";
echo "2. Make API calls to queue events (see api_with_websocket_queue.php)\n";
echo "3. Events will be automatically broadcast to all connected clients\n\n";

echo "Example test with curl:\n";
echo "  curl -X POST http://localhost:8080/api/notifications \\\n";
echo "    -H 'Content-Type: application/json' \\\n";
echo "    -d '{\"message\":\"Test notification\",\"type\":\"info\"}'\n\n";

echo "Press Ctrl+C to stop the server\n";
echo "===========================================\n\n";

// Start server (blocking call)
$server->start();

