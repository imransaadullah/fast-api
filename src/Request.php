<?php
namespace FASTAPI;

/**
 * Class Request
 * 
 * Represents an HTTP request.
 */
class Request
{
    private $method;
    private $uri;
    private $data; 

    /**
     * Request constructor.
     *
     * @param string $method The HTTP method of the request.
     * @param string $uri The URI of the request.
     * @param array $data The data associated with the request (optional).
     */
    public function __construct($method, $uri, $data = [])
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->data = $data;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string The HTTP method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Retrieves the URI of the request.
     *
     * @return string The URI.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Retrieves the data associated with the request.
     *
     * @return array The data.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Retrieves the uploaded files associated with the request.
     *
     * @return array The uploaded files.
     */
    public function getFiles() {
        return $_FILES;
    }

    /**
     * Retrieves the headers of the request.
     *
     * @return array An associative array containing the headers.
     */
    public function getHeaders()
    {
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[$name] = $value;
        }
        return $headers;
    }

    /**
     * Retrieves the value of a specific header from the request.
     *
     * @param string $key The header key.
     * @return string|null The header value, or null if the header does not exist.
     */
    public function getHeader($key)
    {
        return $this->getHeaders()[$key] ?? null;
    }
}
