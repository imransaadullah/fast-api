<?php
/**
 * API with WebSocket Queue Integration Example
 * 
 * This demonstrates how to integrate WebSocket event queueing
 * into your regular FastAPI endpoints.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\WebSocket\WebSocketServer;

$app = App::getInstance();

// Configuration
$queuePath = __DIR__ . '/../logs/ws_queue';

// API Endpoint: Create Appointment
$app->post('/api/appointments', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    // Get appointment data from request
    $data = $request->body();
    
    // Validate data (simplified)
    if (!isset($data['patient_name']) || !isset($data['doctor_name'])) {
        return $response->setJsonResponse([
            'success' => false,
            'message' => 'Missing required fields'
        ], 400);
    }
    
    // Save appointment to database (simplified)
    $appointmentId = rand(1000, 9999); // In real app, this would be DB insert ID
    
    // Queue WebSocket event to notify all connected clients
    $queued = WebSocketServer::queueEvent($queuePath, 'appointment_created', [
        'appointment_id' => $appointmentId,
        'patient_name' => $data['patient_name'],
        'doctor_name' => $data['doctor_name'],
        'appointment_time' => $data['appointment_time'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse([
        'success' => true,
        'appointment_id' => $appointmentId,
        'websocket_queued' => $queued,
        'message' => 'Appointment created successfully'
    ], 201);
});

// API Endpoint: Update Appointment Status
$app->put('/api/appointments/:id/status', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $appointmentId = $request->params('id');
    $data = $request->body();
    
    if (!isset($data['status'])) {
        return $response->setJsonResponse([
            'success' => false,
            'message' => 'Status is required'
        ], 400);
    }
    
    // Update appointment status in database (simplified)
    $status = $data['status'];
    
    // Queue WebSocket event for real-time status update
    WebSocketServer::queueEvent($queuePath, 'appointment_status_updated', [
        'appointment_id' => $appointmentId,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse([
        'success' => true,
        'message' => 'Appointment status updated'
    ]);
});

// API Endpoint: Delete Appointment
$app->delete('/api/appointments/:id', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $appointmentId = $request->params('id');
    
    // Delete appointment from database (simplified)
    
    // Notify connected clients
    WebSocketServer::queueEvent($queuePath, 'appointment_deleted', [
        'appointment_id' => $appointmentId,
        'deleted_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse([
        'success' => true,
        'message' => 'Appointment deleted'
    ]);
});

// API Endpoint: Send Notification
$app->post('/api/notifications', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $data = $request->body();
    
    if (!isset($data['message'])) {
        return $response->setJsonResponse([
            'success' => false,
            'message' => 'Message is required'
        ], 400);
    }
    
    // Queue notification to all connected clients
    WebSocketServer::queueEvent($queuePath, 'notification', [
        'type' => $data['type'] ?? 'info',
        'message' => $data['message'],
        'title' => $data['title'] ?? 'Notification',
        'timestamp' => time()
    ]);
    
    return $response->setJsonResponse([
        'success' => true,
        'message' => 'Notification queued'
    ]);
});

// API Endpoint: Broadcast Custom Event
$app->post('/api/broadcast', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $data = $request->body();
    
    if (!isset($data['event'])) {
        return $response->setJsonResponse([
            'success' => false,
            'message' => 'Event name is required'
        ], 400);
    }
    
    // Queue custom event
    $queued = WebSocketServer::queueEvent(
        $queuePath,
        $data['event'],
        $data['payload'] ?? null
    );
    
    return $response->setJsonResponse([
        'success' => $queued,
        'message' => $queued ? 'Event queued successfully' : 'Failed to queue event'
    ]);
});

// API Endpoint: Health Check
$app->get('/api/health', function(Request $request) {
    $response = new Response();
    
    return $response->setJsonResponse([
        'status' => 'ok',
        'timestamp' => time(),
        'server' => 'FastAPI with WebSocket Queue'
    ]);
});

echo "=== FastAPI with WebSocket Queue Integration ===\n\n";
echo "API Endpoints:\n";
echo "  POST   /api/appointments          - Create appointment\n";
echo "  PUT    /api/appointments/:id/status - Update appointment status\n";
echo "  DELETE /api/appointments/:id      - Delete appointment\n";
echo "  POST   /api/notifications         - Send notification\n";
echo "  POST   /api/broadcast             - Broadcast custom event\n";
echo "  GET    /api/health                - Health check\n\n";

echo "WebSocket Queue Path: $queuePath\n\n";

echo "How it works:\n";
echo "1. Your API receives a request\n";
echo "2. Processes the request (save to DB, etc.)\n";
echo "3. Queues a WebSocket event using WebSocketServer::queueEvent()\n";
echo "4. Your WebSocket server (running separately) picks up the event\n";
echo "5. Broadcasts to all connected WebSocket clients\n\n";

echo "To test:\n";
echo "1. Start the WebSocket server: php examples/event_queue_websocket_server.php\n";
echo "2. Start this API server: php -S localhost:8080 examples/api_with_websocket_queue.php\n";
echo "3. Make API requests to trigger WebSocket broadcasts\n\n";

// Run the app
$app->run();

