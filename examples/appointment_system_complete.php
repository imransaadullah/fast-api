<?php
/**
 * Complete Appointment System Example with WebSocket Event Queue
 * 
 * This example demonstrates a complete appointment system with real-time notifications.
 * Run this file to see the full implementation.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\WebSocket\WebSocketServer;

echo "=== Appointment System with Real-Time Notifications ===\n\n";

$app = App::getInstance();
$queuePath = __DIR__ . '/../logs/ws_queue';

// =============================================================================
// API ENDPOINTS
// =============================================================================

echo "Setting up API endpoints...\n";

// 1. Create Appointment
$app->post('/api/appointments', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $data = $request->body();
    
    // Validate
    if (!isset($data['patient_name']) || !isset($data['doctor_name'])) {
        return $response->setJsonResponse([
            'success' => false,
            'message' => 'Missing required fields'
        ], 400);
    }
    
    // Generate appointment ID (in real app, this would be from database)
    $appointmentId = 'APT-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Save to database (simplified)
    echo "[API] Creating appointment: $appointmentId\n";
    
    // Queue WebSocket event for real-time notification
    $queued = WebSocketServer::queueEvent($queuePath, 'appointment_created', [
        'appointment_id' => $appointmentId,
        'patient_name' => $data['patient_name'],
        'patient_email' => $data['patient_email'] ?? null,
        'patient_phone' => $data['patient_phone'] ?? null,
        'doctor_name' => $data['doctor_name'],
        'appointment_time' => $data['appointment_time'] ?? null,
        'appointment_date' => $data['appointment_date'] ?? date('Y-m-d'),
        'department' => $data['department'] ?? 'General',
        'status' => 'pending',
        'notes' => $data['notes'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($queued) {
        echo "[API] ✓ WebSocket notification queued\n";
    }
    
    return $response->setJsonResponse([
        'success' => true,
        'appointment_id' => $appointmentId,
        'message' => 'Appointment created successfully',
        'websocket_queued' => $queued
    ], 201);
});

// 2. Get All Appointments
$app->get('/api/appointments', function(Request $request) {
    $response = new Response();
    
    // In real app, fetch from database
    $appointments = [
        [
            'appointment_id' => 'APT-12345678',
            'patient_name' => 'John Doe',
            'doctor_name' => 'Dr. Smith',
            'appointment_date' => '2025-10-15',
            'appointment_time' => '14:00:00',
            'status' => 'confirmed'
        ]
    ];
    
    return $response->setJsonResponse([
        'success' => true,
        'count' => count($appointments),
        'appointments' => $appointments
    ]);
});

// 3. Get Single Appointment
$app->get('/api/appointments/:id', function(Request $request) {
    $response = new Response();
    
    $appointmentId = $request->params('id');
    
    // In real app, fetch from database
    $appointment = [
        'appointment_id' => $appointmentId,
        'patient_name' => 'John Doe',
        'doctor_name' => 'Dr. Smith',
        'appointment_date' => '2025-10-15',
        'appointment_time' => '14:00:00',
        'status' => 'confirmed',
        'created_at' => '2025-10-14 10:00:00'
    ];
    
    return $response->setJsonResponse([
        'success' => true,
        'appointment' => $appointment
    ]);
});

// 4. Update Appointment Status
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
    
    $newStatus = $data['status'];
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no-show'];
    
    if (!in_array($newStatus, $validStatuses)) {
        return $response->setJsonResponse([
            'success' => false,
            'message' => 'Invalid status. Valid: ' . implode(', ', $validStatuses)
        ], 400);
    }
    
    // Update in database (simplified)
    echo "[API] Updating appointment $appointmentId status to: $newStatus\n";
    
    // Queue WebSocket event
    $queued = WebSocketServer::queueEvent($queuePath, 'appointment_status_changed', [
        'appointment_id' => $appointmentId,
        'old_status' => 'pending', // In real app, get from database
        'new_status' => $newStatus,
        'updated_by' => $data['updated_by'] ?? 'system',
        'reason' => $data['reason'] ?? null,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($queued) {
        echo "[API] ✓ Status change notification queued\n";
    }
    
    return $response->setJsonResponse([
        'success' => true,
        'message' => "Appointment status updated to: $newStatus",
        'websocket_queued' => $queued
    ]);
});

// 5. Update Appointment Details
$app->put('/api/appointments/:id', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $appointmentId = $request->params('id');
    $data = $request->body();
    
    // Update in database (simplified)
    echo "[API] Updating appointment $appointmentId details\n";
    
    // Queue WebSocket event
    WebSocketServer::queueEvent($queuePath, 'appointment_updated', [
        'appointment_id' => $appointmentId,
        'updates' => $data,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse([
        'success' => true,
        'message' => 'Appointment updated successfully'
    ]);
});

// 6. Cancel Appointment
$app->delete('/api/appointments/:id', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $appointmentId = $request->params('id');
    
    // Delete from database or mark as cancelled (simplified)
    echo "[API] Cancelling appointment: $appointmentId\n";
    
    // Queue WebSocket event
    $queued = WebSocketServer::queueEvent($queuePath, 'appointment_cancelled', [
        'appointment_id' => $appointmentId,
        'cancelled_by' => 'admin', // In real app, get from auth
        'reason' => $request->body()['reason'] ?? 'No reason provided',
        'cancelled_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($queued) {
        echo "[API] ✓ Cancellation notification queued\n";
    }
    
    return $response->setJsonResponse([
        'success' => true,
        'message' => 'Appointment cancelled successfully',
        'websocket_queued' => $queued
    ]);
});

// 7. Send Reminder
$app->post('/api/appointments/:id/reminder', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $appointmentId = $request->params('id');
    
    echo "[API] Sending reminder for appointment: $appointmentId\n";
    
    // Queue WebSocket event
    WebSocketServer::queueEvent($queuePath, 'appointment_reminder', [
        'appointment_id' => $appointmentId,
        'patient_name' => 'John Doe', // In real app, fetch from database
        'doctor_name' => 'Dr. Smith',
        'appointment_time' => '14:00:00',
        'reminder_type' => 'manual',
        'sent_at' => date('Y-m-d H:i:s')
    ]);
    
    return $response->setJsonResponse([
        'success' => true,
        'message' => 'Reminder sent successfully'
    ]);
});

// 8. Broadcast General Notification
$app->post('/api/notifications/broadcast', function(Request $request) use ($queuePath) {
    $response = new Response();
    
    $data = $request->body();
    
    if (!isset($data['message'])) {
        return $response->setJsonResponse([
            'success' => false,
            'message' => 'Message is required'
        ], 400);
    }
    
    echo "[API] Broadcasting notification: {$data['message']}\n";
    
    // Queue WebSocket event
    WebSocketServer::queueEvent($queuePath, 'general_notification', [
        'type' => $data['type'] ?? 'info',
        'title' => $data['title'] ?? 'Notification',
        'message' => $data['message'],
        'priority' => $data['priority'] ?? 'normal',
        'timestamp' => time()
    ]);
    
    return $response->setJsonResponse([
        'success' => true,
        'message' => 'Notification broadcast queued'
    ]);
});

// 9. Health Check
$app->get('/api/health', function(Request $request) {
    $response = new Response();
    
    return $response->setJsonResponse([
        'status' => 'healthy',
        'service' => 'Appointment System API',
        'timestamp' => time(),
        'version' => '1.0.0'
    ]);
});

echo "✓ API endpoints configured\n\n";

// =============================================================================
// DISPLAY AVAILABLE ENDPOINTS
// =============================================================================

echo "Available API Endpoints:\n";
echo "------------------------\n";
echo "POST   /api/appointments                - Create new appointment\n";
echo "GET    /api/appointments                - Get all appointments\n";
echo "GET    /api/appointments/:id            - Get single appointment\n";
echo "PUT    /api/appointments/:id            - Update appointment details\n";
echo "PUT    /api/appointments/:id/status     - Update appointment status\n";
echo "DELETE /api/appointments/:id            - Cancel appointment\n";
echo "POST   /api/appointments/:id/reminder   - Send appointment reminder\n";
echo "POST   /api/notifications/broadcast     - Broadcast general notification\n";
echo "GET    /api/health                      - Health check\n\n";

echo "WebSocket Configuration:\n";
echo "------------------------\n";
echo "Queue Path: $queuePath\n";
echo "WebSocket Events:\n";
echo "  - appointment_created\n";
echo "  - appointment_status_changed\n";
echo "  - appointment_updated\n";
echo "  - appointment_cancelled\n";
echo "  - appointment_reminder\n";
echo "  - general_notification\n\n";

echo "How to Run:\n";
echo "-----------\n";
echo "1. Start WebSocket Server:\n";
echo "   php examples/event_queue_websocket_server.php\n\n";
echo "2. Start API Server (this file):\n";
echo "   php -S localhost:8080 examples/appointment_system_complete.php\n\n";
echo "3. Test with curl:\n";
echo "   curl -X POST http://localhost:8080/api/appointments \\\n";
echo "     -H 'Content-Type: application/json' \\\n";
echo "     -d '{\"patient_name\":\"John Doe\",\"doctor_name\":\"Dr. Smith\"}'\n\n";
echo "4. Open examples/websocket_client_test.html in browser to see real-time updates\n\n";

echo "===========================================\n";
echo "Starting API server...\n";
echo "===========================================\n\n";

// Run the app
$app->run();

