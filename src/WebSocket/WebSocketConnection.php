<?php

namespace FASTAPI\WebSocket;

/**
 * WebSocket Connection Handler
 * Manages individual WebSocket connections
 */
class WebSocketConnection
{
    /** @var resource|\Socket */
    private $socket;
    
    /** @var bool */
    private $connected = false;
    
    /** @var string */
    private $path = '/';
    
    /** @var array */
    private $headers = [];

    /**
     * @param resource|\Socket $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    /**
     * Perform WebSocket handshake
     *
     * @return bool
     */
    public function handshake()
    {
        $request = $this->readRaw();
        
        if (!$request) {
            return false;
        }
        
        $lines = explode("\r\n", $request);
        $firstLine = $lines[0];
        
        // Parse request line
        if (preg_match('/^GET\s+(\S+)\s+HTTP\/1\.1$/', $firstLine, $matches)) {
            $this->path = $matches[1];
        }
        
        // Parse headers
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $this->headers[trim($key)] = trim($value);
            }
        }
        
        // Check if this is a WebSocket upgrade request
        if (!isset($this->headers['Upgrade']) || 
            strtolower($this->headers['Upgrade']) !== 'websocket') {
            return false;
        }
        
        // Generate WebSocket accept key
        $key = $this->headers['Sec-WebSocket-Key'] ?? '';
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        
        // Send handshake response
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: {$acceptKey}\r\n";
        $response .= "\r\n";
        
        $this->write($response);
        $this->connected = true;
        
        return true;
    }

    /**
     * Read raw data from socket
     *
     * @return string|null
     */
    private function readRaw()
    {
        $data = '';
        $buffer = '';
        
        while (true) {
            $bytes = socket_recv($this->socket, $buffer, 1024, 0);
            
            if ($bytes === false || $bytes === 0) {
                return null;
            }
            
            $data .= $buffer;
            
            // Check if we have received the complete HTTP request
            if (strpos($data, "\r\n\r\n") !== false) {
                break;
            }
        }
        
        return $data;
    }

    /**
     * Read WebSocket frame
     *
     * @return string|null
     */
    public function read()
    {
        if (!$this->connected) {
            return null;
        }
        
        $data = socket_recv($this->socket, $buffer, 2048, 0);
        
        if ($data === false || $data === 0) {
            $this->connected = false;
            return null;
        }
        
        return $this->decodeFrame($buffer);
    }

    /**
     * Send WebSocket frame
     *
     * @param string $message
     * @return bool
     */
    public function send($message)
    {
        if (!$this->connected) {
            return false;
        }
        
        $frame = $this->encodeFrame($message);
        return socket_send($this->socket, $frame, strlen($frame), 0) !== false;
    }

    /**
     * Write raw data to socket
     *
     * @param string $data
     * @return bool
     */
    private function write($data)
    {
        return socket_send($this->socket, $data, strlen($data), 0) !== false;
    }

    /**
     * Encode message to WebSocket frame
     *
     * @param string $message
     * @return string
     */
    private function encodeFrame($message)
    {
        $length = strlen($message);
        $frame = chr(129); // FIN + opcode (text frame)
        
        if ($length <= 125) {
            $frame .= chr($length);
        } elseif ($length <= 65535) {
            $frame .= chr(126) . pack('n', $length);
        } else {
            $frame .= chr(127) . pack('J', $length);
        }
        
        $frame .= $message;
        return $frame;
    }

    /**
     * Decode WebSocket frame to message
     *
     * @param string $frame
     * @return string|null
     */
    private function decodeFrame($frame)
    {
        if (strlen($frame) < 2) {
            return null;
        }
        
        $firstByte = ord($frame[0]);
        $secondByte = ord($frame[1]);
        
        $fin = ($firstByte & 128) !== 0;
        $opcode = $firstByte & 15;
        $masked = ($secondByte & 128) !== 0;
        $payloadLength = $secondByte & 127;
        
        $offset = 2;
        
        // Extended payload length
        if ($payloadLength === 126) {
            if (strlen($frame) < $offset + 2) {
                return null;
            }
            $payloadLength = unpack('n', substr($frame, $offset, 2))[1];
            $offset += 2;
        } elseif ($payloadLength === 127) {
            if (strlen($frame) < $offset + 8) {
                return null;
            }
            $payloadLength = unpack('J', substr($frame, $offset, 8))[1];
            $offset += 8;
        }
        
        // Masking key
        if ($masked) {
            if (strlen($frame) < $offset + 4) {
                return null;
            }
            $mask = substr($frame, $offset, 4);
            $offset += 4;
        }
        
        // Payload
        if (strlen($frame) < $offset + $payloadLength) {
            return null;
        }
        
        $payload = substr($frame, $offset, $payloadLength);
        
        // Unmask payload if masked
        if ($masked) {
            $unmasked = '';
            for ($i = 0; $i < $payloadLength; $i++) {
                $unmasked .= chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
            }
            $payload = $unmasked;
        }
        
        // Handle different opcodes
        switch ($opcode) {
            case 1: // Text frame
                return $payload;
            case 8: // Close frame
                $this->connected = false;
                return null;
            case 9: // Ping frame
                $this->sendPong();
                return null;
            case 10: // Pong frame
                return null;
            default:
                return null;
        }
    }

    /**
     * Send pong response
     *
     * @return void
     */
    private function sendPong()
    {
        $frame = chr(138); // FIN + pong opcode
        $frame .= chr(0); // No payload
        socket_send($this->socket, $frame, strlen($frame), 0);
    }

    /**
     * Close the connection
     *
     * @return void
     */
    public function close()
    {
        if ($this->connected) {
            $frame = chr(136); // FIN + close opcode
            $frame .= chr(0); // No payload
            socket_send($this->socket, $frame, strlen($frame), 0);
        }
        
        socket_close($this->socket);
        $this->connected = false;
    }

    /**
     * Check if connection is active
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Get connection path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get connection headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get specific header
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }
} 