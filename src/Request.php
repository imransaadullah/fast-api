<?php
namespace FASTAPI;

class Request
{
    private $method;
    private $uri;
    private $data; 

    private $headers;

    public function __construct($method, $uri, $data = [])
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->data = $data;
        $this->getHeaders();
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getFiles() {
        return $_FILES;
    }

    public function getHeaders()
    {
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[$name] = $value;
        }
        $this->$headers = $headers;
        return $headers;
    }

    public function getHeader($key)
    {
        $headers = $this->headers;
        return $headers[$key] ?? null;
    }
}
