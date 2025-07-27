<?php

namespace FASTAPI\WebSocket;

use FASTAPI\App;
use FASTAPI\Request;
use FASTAPI\Response;

/**
 * WebSocket Server for FastAPI
 * Provides WebSocket capabilities while maintaining backward compatibility
 */
class WebSocketServer
{
    /** @var App */
    private $app;
    
    /** @var array */
    private $connections = [];
    
    /** @var array */
    private $handlers = [];
    
    /** @var bool */
    private $isRunning = false;
    
    /** @var int */
    private $port = 8080;
    
    /** @var string */
    private $host = '0.0.0.0';
    
    /** @var resource|null */
    private $socket = null;

    public function __construct(App $app = null)
    {
        $this->app = $app ?? App::getInstance();
    }

    /**
     * Register a WebSocket route handler
     *
     * @param string $path
     * @param callable $handler
     * @return WebSocketServer
     */
    public function on($path, callable $handler)
    {
        $this->handlers[$path] = $handler;
        return $this;
    }

    /**
     * Set the WebSocket server port
     *
     * @param int $port
     * @return WebSocketServer
     */
    public function port($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Set the WebSocket server host
     *
     * @param string $host
     * @return WebSocketServer
     */
    public function host($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Start the WebSocket server
     *
     * @return void
     */
    public function start()
    {
        if ($this->isRunning) {
            return;
        }

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        if (!$this->socket) {
            throw new \Exception('Failed to create WebSocket socket');
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            throw new \Exception("Failed to bind WebSocket to {$this->host}:{$this->port}");
        }

        if (!socket_listen($this->socket)) {
            throw new \Exception('Failed to listen on WebSocket socket');
        }

        $this->isRunning = true;
        
        echo "WebSocket server started on ws://{$this->host}:{$this->port}\n";
        
        $this->run();
    }

    /**
     * Stop the WebSocket server
     *
     * @return void
     */
    public function stop()
    {
        $this->isRunning = false;
        
        if ($this->socket) {
            socket_close($this->socket);
        }
        
        foreach ($this->connections as $connection) {
            $connection->close();
        }
        
        $this->connections = [];
    }

    /**
     * Main WebSocket server loop
     *
     * @return void
     */
    private function run()
    {
        while ($this->isRunning) {
            $read = [$this->socket];
            $write = null;
            $except = null;
            
            if (socket_select($read, $write, $except, 0) > 0) {
                $client = socket_accept($this->socket);
                
                if ($client) {
                    $this->handleNewConnection($client);
                }
            }
            
            // Handle existing connections
            $this->handleExistingConnections();
            
            usleep(10000); // 10ms delay to prevent CPU overuse
        }
    }

    /**
     * Handle new WebSocket connection
     *
     * @param resource $client
     * @return void
     */
    private function handleNewConnection($client)
    {
        $connection = new WebSocketConnection($client);
        
        if ($connection->handshake()) {
            $this->connections[] = $connection;
            
            // Find matching handler
            $path = $connection->getPath();
            if (isset($this->handlers[$path])) {
                $handler = $this->handlers[$path];
                $handler($connection);
            }
        }
    }

    /**
     * Handle existing WebSocket connections
     *
     * @return void
     */
    private function handleExistingConnections()
    {
        foreach ($this->connections as $key => $connection) {
            if (!$connection->isConnected()) {
                unset($this->connections[$key]);
                continue;
            }
            
            $message = $connection->read();
            if ($message !== null) {
                $this->handleMessage($connection, $message);
            }
        }
    }

    /**
     * Handle incoming WebSocket message
     *
     * @param WebSocketConnection $connection
     * @param string $message
     * @return void
     */
    private function handleMessage($connection, $message)
    {
        $data = json_decode($message, true);
        
        if ($data && isset($data['event'])) {
            $event = $data['event'];
            $payload = $data['payload'] ?? null;
            
            // Emit event to all connections
            $this->broadcast($event, $payload);
        }
    }

    /**
     * Broadcast message to all connections
     *
     * @param string $event
     * @param mixed $payload
     * @return void
     */
    public function broadcast($event, $payload = null)
    {
        $message = json_encode([
            'event' => $event,
            'payload' => $payload,
            'timestamp' => time()
        ]);
        
        foreach ($this->connections as $connection) {
            if ($connection->isConnected()) {
                $connection->send($message);
            }
        }
    }

    /**
     * Get current connection count
     *
     * @return int
     */
    public function getConnectionCount()
    {
        return count($this->connections);
    }

    /**
     * Get all active connections
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }
} 