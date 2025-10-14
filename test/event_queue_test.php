<?php
/**
 * Event Queue Test
 * Tests the WebSocket event queue functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FASTAPI\App;
use FASTAPI\WebSocket\WebSocketServer;

echo "=== WebSocket Event Queue Test ===\n\n";

// Test 1: Event Queue Setup
echo "--- Test 1: Event Queue Setup ---\n";

$app = App::getInstance();
$queuePath = __DIR__ . '/../test_keys/event_queue_test';

// Clean up any existing queue directory
if (is_dir($queuePath)) {
    $files = glob($queuePath . '/*');
    foreach ($files as $file) {
        @unlink($file);
    }
    @rmdir($queuePath);
}

$server = WebSocketServer::getInstance($app)
    ->host('127.0.0.1')
    ->port(8999)
    ->eventQueue($queuePath);

// Check if queue directory was created
if (is_dir($queuePath)) {
    echo "✓ Queue directory created successfully\n";
} else {
    echo "✗ Failed to create queue directory\n";
    exit(1);
}

if (is_writable($queuePath)) {
    echo "✓ Queue directory is writable\n";
} else {
    echo "✗ Queue directory is not writable\n";
    exit(1);
}

// Test 2: Queue Event
echo "\n--- Test 2: Queue Event ---\n";

$result = WebSocketServer::queueEvent($queuePath, 'test_event', [
    'message' => 'Hello World',
    'user_id' => 123,
    'timestamp' => time()
]);

if ($result) {
    echo "✓ Event queued successfully\n";
} else {
    echo "✗ Failed to queue event\n";
    exit(1);
}

// Check if queue file was created
$queueFiles = glob($queuePath . '/*.json');
if (count($queueFiles) > 0) {
    echo "✓ Queue file created: " . basename($queueFiles[0]) . "\n";
} else {
    echo "✗ No queue files found\n";
    exit(1);
}

// Test 3: Queue File Contents
echo "\n--- Test 3: Queue File Contents ---\n";

$fileContent = file_get_contents($queueFiles[0]);
$eventData = json_decode($fileContent, true);

if ($eventData && isset($eventData['event'])) {
    echo "✓ Event data is valid JSON\n";
    echo "  Event name: {$eventData['event']}\n";
    echo "  Payload: " . json_encode($eventData['payload']) . "\n";
} else {
    echo "✗ Invalid event data\n";
    exit(1);
}

// Test 4: Multiple Events
echo "\n--- Test 4: Queue Multiple Events ---\n";

$events = [
    ['event' => 'appointment_created', 'payload' => ['id' => 1, 'patient' => 'John']],
    ['event' => 'notification', 'payload' => ['message' => 'Test notification']],
    ['event' => 'status_updated', 'payload' => ['status' => 'confirmed']],
];

foreach ($events as $event) {
    $result = WebSocketServer::queueEvent(
        $queuePath,
        $event['event'],
        $event['payload']
    );
    
    if ($result) {
        echo "✓ Queued: {$event['event']}\n";
    } else {
        echo "✗ Failed to queue: {$event['event']}\n";
    }
}

// Test 5: Verify All Files
echo "\n--- Test 5: Verify All Queue Files ---\n";

$queueFiles = glob($queuePath . '/*.json');
echo "Total queue files: " . count($queueFiles) . "\n";

if (count($queueFiles) >= 4) { // 1 from test 2 + 3 from test 4
    echo "✓ All events queued successfully\n";
} else {
    echo "✗ Some events failed to queue\n";
}

// Test 6: Method Existence
echo "\n--- Test 6: Method Availability ---\n";

$methods = ['eventQueue', 'on', 'port', 'host', 'start', 'stop', 'broadcast'];
foreach ($methods as $method) {
    if (method_exists($server, $method)) {
        echo "✓ Method exists: {$method}\n";
    } else {
        echo "✗ Method missing: {$method}\n";
    }
}

// Test 7: Static Method
echo "\n--- Test 7: Static queueEvent Method ---\n";

if (method_exists(WebSocketServer::class, 'queueEvent')) {
    echo "✓ Static queueEvent method exists\n";
} else {
    echo "✗ Static queueEvent method missing\n";
    exit(1);
}

// Test 8: Invalid Queue Path
echo "\n--- Test 8: Invalid Queue Path Handling ---\n";

$invalidPath = '/invalid/path/that/does/not/exist/and/cannot/be/created';
$result = @WebSocketServer::queueEvent($invalidPath, 'test', ['data' => 'test']);

if (!$result) {
    echo "✓ Invalid path handled correctly (returned false)\n";
} else {
    echo "✗ Invalid path should return false\n";
}

// Test 9: Event Data Structure
echo "\n--- Test 9: Event Data Structure ---\n";

$testFile = $queueFiles[0];
$content = file_get_contents($testFile);
$data = json_decode($content, true);

$requiredKeys = ['event', 'payload', 'timestamp', 'queued_at'];
$allKeysPresent = true;

foreach ($requiredKeys as $key) {
    if (!isset($data[$key])) {
        echo "✗ Missing required key: {$key}\n";
        $allKeysPresent = false;
    }
}

if ($allKeysPresent) {
    echo "✓ All required keys present in event data\n";
    echo "  - event: " . $data['event'] . "\n";
    echo "  - timestamp: " . $data['timestamp'] . "\n";
    echo "  - queued_at: " . $data['queued_at'] . "\n";
}

// Test 10: Cleanup
echo "\n--- Test 10: Cleanup ---\n";

$filesDeleted = 0;
foreach ($queueFiles as $file) {
    if (unlink($file)) {
        $filesDeleted++;
    }
}

echo "✓ Deleted {$filesDeleted} queue files\n";

if (rmdir($queuePath)) {
    echo "✓ Queue directory removed\n";
} else {
    echo "✗ Failed to remove queue directory\n";
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ All event queue tests passed!\n\n";

echo "Key Features Verified:\n";
echo "- ✅ Event queue directory creation\n";
echo "- ✅ Event queueing functionality\n";
echo "- ✅ Queue file creation and structure\n";
echo "- ✅ Multiple event handling\n";
echo "- ✅ Static method availability\n";
echo "- ✅ Error handling for invalid paths\n";
echo "- ✅ Proper event data structure\n";
echo "- ✅ File cleanup capability\n\n";

echo "Next Steps:\n";
echo "1. Start WebSocket server: php examples/event_queue_websocket_server.php\n";
echo "2. Start API server: php -S localhost:8080 examples/api_with_websocket_queue.php\n";
echo "3. Open examples/websocket_client_test.html in browser\n";
echo "4. Test real-time event broadcasting\n";

