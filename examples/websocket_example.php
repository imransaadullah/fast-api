<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;
use FASTAPI\WebSocket\WebSocketConnection;

// Get the app instance
$app = App::getInstance();

echo "=== FastAPI WebSocket Example ===\n\n";

// Example 1: Basic WebSocket server
echo "=== Example 1: Basic WebSocket Server ===\n";

$websocket = $app->websocket(8080, 'localhost');

// Chat room WebSocket
$websocket->on('/chat', function(WebSocketConnection $connection) {
    echo "New chat connection established\n";
    
    // Send welcome message
    $connection->send(json_encode([
        'event' => 'welcome',
        'payload' => [
            'message' => 'Welcome to the chat room!',
            'timestamp' => time()
        ]
    ]));
    
    // Handle incoming messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        
        if ($message) {
            $data = json_decode($message, true);
            
            if ($data && isset($data['event'])) {
                echo "Received event: {$data['event']}\n";
                
                // Echo back the message
                $connection->send(json_encode([
                    'event' => 'message_received',
                    'payload' => [
                        'original_event' => $data['event'],
                        'data' => $data['payload'] ?? null,
                        'timestamp' => time()
                    ]
                ]));
            }
        }
        
        usleep(100000); // 100ms delay
    }
});

// Notification WebSocket
$websocket->on('/notifications', function(WebSocketConnection $connection) {
    echo "New notification connection established\n";
    
    // Send initial notification
    $connection->send(json_encode([
        'event' => 'notification',
        'payload' => [
            'type' => 'info',
            'message' => 'Notification system connected',
            'timestamp' => time()
        ]
    ]));
    
    // Simulate periodic notifications
    $counter = 0;
    while ($connection->isConnected()) {
        $counter++;
        
        if ($counter % 10 == 0) { // Every 10 iterations
            $connection->send(json_encode([
                'event' => 'notification',
                'payload' => [
                    'type' => 'update',
                    'message' => "System update #{$counter}",
                    'timestamp' => time()
                ]
            ]));
        }
        
        usleep(1000000); // 1 second delay
    }
});

// Real-time data WebSocket
$websocket->on('/realtime', function(WebSocketConnection $connection) {
    echo "New realtime connection established\n";
    
    // Send initial data
    $connection->send(json_encode([
        'event' => 'data_update',
        'payload' => [
            'type' => 'initial',
            'data' => [
                'users_online' => rand(10, 100),
                'system_load' => rand(20, 80),
                'active_sessions' => rand(50, 200)
            ],
            'timestamp' => time()
        ]
    ]));
    
    // Simulate real-time data updates
    while ($connection->isConnected()) {
        $connection->send(json_encode([
            'event' => 'data_update',
            'payload' => [
                'type' => 'live',
                'data' => [
                    'users_online' => rand(10, 100),
                    'system_load' => rand(20, 80),
                    'active_sessions' => rand(50, 200),
                    'memory_usage' => rand(60, 95),
                    'cpu_usage' => rand(30, 90)
                ],
                'timestamp' => time()
            ]
        ]));
        
        usleep(2000000); // 2 second delay
    }
});

// Example 2: WebSocket with authentication
echo "\n=== Example 2: WebSocket with Authentication ===\n";

$authenticatedWebsocket = $app->websocket(8081, 'localhost');

$authenticatedWebsocket->on('/secure', function(WebSocketConnection $connection) {
    echo "New secure connection attempt\n";
    
    // Check for authentication token
    $token = $connection->getHeader('Authorization');
    
    if (!$token || !validateToken($token)) {
        echo "Authentication failed\n";
        $connection->send(json_encode([
            'event' => 'error',
            'payload' => [
                'message' => 'Authentication required',
                'code' => 'AUTH_REQUIRED'
            ]
        ]));
        $connection->close();
        return;
    }
    
    echo "Secure connection authenticated\n";
    
    // Send authenticated welcome
    $connection->send(json_encode([
        'event' => 'authenticated',
        'payload' => [
            'message' => 'Welcome to secure channel',
            'user_id' => extractUserId($token),
            'timestamp' => time()
        ]
    ]));
    
    // Handle secure messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        
        if ($message) {
            $data = json_decode($message, true);
            
            if ($data && isset($data['event'])) {
                echo "Secure event: {$data['event']}\n";
                
                // Process secure event
                $connection->send(json_encode([
                    'event' => 'secure_response',
                    'payload' => [
                        'original_event' => $data['event'],
                        'processed' => true,
                        'timestamp' => time()
                    ]
                ]));
            }
        }
        
        usleep(100000); // 100ms delay
    }
});

// Example 3: Broadcasting messages
echo "\n=== Example 3: Broadcasting Messages ===\n";

$broadcastWebsocket = $app->websocket(8082, 'localhost');

$broadcastWebsocket->on('/broadcast', function(WebSocketConnection $connection) use ($broadcastWebsocket) {
    echo "New broadcast connection established\n";
    
    // Send welcome
    $connection->send(json_encode([
        'event' => 'welcome',
        'payload' => [
            'message' => 'Welcome to broadcast channel',
            'timestamp' => time()
        ]
    ]));
    
    // Handle broadcast messages
    while ($connection->isConnected()) {
        $message = $connection->read();
        
        if ($message) {
            $data = json_decode($message, true);
            
            if ($data && isset($data['event'])) {
                echo "Broadcasting event: {$data['event']}\n";
                
                // Broadcast to all connections
                $broadcastWebsocket->broadcast($data['event'], $data['payload'] ?? null);
            }
        }
        
        usleep(100000); // 100ms delay
    }
});

// Helper functions
function validateToken($token) {
    // Simple token validation (replace with real JWT validation)
    return strpos($token, 'Bearer ') === 0 && strlen($token) > 20;
}

function extractUserId($token) {
    // Extract user ID from token (replace with real JWT decoding)
    return rand(1, 1000);
}

// Start the WebSocket servers
echo "\n=== Starting WebSocket Servers ===\n";
echo "Chat server: ws://localhost:8080/chat\n";
echo "Notifications: ws://localhost:8080/notifications\n";
echo "Realtime data: ws://localhost:8080/realtime\n";
echo "Secure channel: ws://localhost:8081/secure\n";
echo "Broadcast: ws://localhost:8082/broadcast\n\n";

echo "Press Ctrl+C to stop the servers\n\n";

// Start all WebSocket servers
$websocket->start(); 